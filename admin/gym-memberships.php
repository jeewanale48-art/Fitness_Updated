<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'plan') {

        $name = trim((string) ($_POST['name'] ?? ''));
        $months = filter_var(
            $_POST['duration_months'] ?? null,
            FILTER_VALIDATE_INT
        );

        $price = filter_var(
            $_POST['price'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $desc = trim((string) ($_POST['description'] ?? ''));

        if (
            $name === '' ||
            $months === false ||
            $months < 1 ||
            $months > 60 ||
            $price === false ||
            $price < 0
        ) {
            $error = 'Enter a plan name, duration from 1 to 60 months, and a valid price.';
        } else {

            $q = $pdo->prepare(
                'INSERT INTO gym_membership_plans
                (name, duration_months, price, description)
                VALUES (?, ?, ?, ?)'
            );

            $q->execute([
                $name,
                $months,
                $price,
                $desc ?: null
            ]);

            flash('Gym membership package added.');
            redirect('gym-memberships.php');
        }
    }

    if ($action === 'toggle') {

        $id = (int) ($_POST['id'] ?? 0);

        $q = $pdo->prepare(
            'UPDATE gym_membership_plans
             SET is_active = 1 - is_active
             WHERE id = ?'
        );

        $q->execute([$id]);

        redirect('gym-memberships.php');
    }

    if ($action === 'membership') {

        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['paid', 'rejected'], true)) {

            $error = 'Invalid payment status.';

        } else {

            $q = $pdo->prepare(
                'SELECT duration_months
                 FROM gym_membership_plans p
                 JOIN gym_memberships m ON m.plan_id = p.id
                 WHERE m.id = ?'
            );

            $q->execute([$id]);

            $months = (int) $q->fetchColumn();

            if ($status === 'paid' && $months > 0) {

                $q = $pdo->prepare(
                    "UPDATE gym_memberships
                     SET payment_status = 'paid',
                         status = 'active',
                         start_date = CURDATE(),
                         end_date = DATE_ADD(CURDATE(), INTERVAL ? MONTH)
                     WHERE id = ?"
                );

                $q->execute([$months, $id]);

            } else {

                $q = $pdo->prepare(
                    "UPDATE gym_memberships
                     SET payment_status = 'rejected',
                         status = 'rejected'
                     WHERE id = ?"
                );

                $q->execute([$id]);
            }

            $info = $pdo->prepare(
                'SELECT m.user_id, m.end_date, p.name AS plan_name
                 FROM gym_memberships m
                 JOIN gym_membership_plans p ON p.id = m.plan_id
                 WHERE m.id = ?'
            );
            $info->execute([$id]);
            $m = $info->fetch();

            if ($m) {
                $approved = ($status === 'paid' && $months > 0);
                fittrackNotify(
                    $pdo,
                    (int) $m['user_id'],
                    $approved ? 'Gym membership approved' : 'Gym membership rejected',
                    $approved
                        ? 'Your "' . $m['plan_name'] . '" gym membership has been approved and is valid until '
                            . $m['end_date'] . '. All FitTrack sections are now unlocked.'
                        : 'Your "' . $m['plan_name'] . '" gym membership request was rejected. Please contact the gym reception or submit a new request.',
                    $approved ? 'Your FitTrack gym membership is approved' : 'Update on your FitTrack gym membership request'
                );
            }

            flash('Membership payment status updated.');
            redirect('gym-memberships.php');
        }
    }
}


/*
 * IMPORTANT:
 * Your database column is `price`, not `fee`.
 */
$plans = $pdo->query(
    'SELECT *
     FROM gym_membership_plans
     ORDER BY duration_months, price'
)->fetchAll();


$members = $pdo->query(
    'SELECT
        m.*,
        u.name AS user_name,
        u.email,
        p.name AS plan_name,
        p.duration_months
     FROM gym_memberships m
     JOIN users u ON u.id = m.user_id
     JOIN gym_membership_plans p ON p.id = m.plan_id
     ORDER BY m.created_at DESC'
)->fetchAll();


$pageTitle = 'Gym membership management';

require __DIR__ . '/../includes/header.php';

?>

<section class="page-heading">

    <div>

        <p class="eyebrow">GYM FEES</p>

        <h1>Membership plans & payments</h1>

        <p>
            Create flexible price packages and review member payment requests.
        </p>

    </div>

</section>


<?php if ($error): ?>

    <div class="alert error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<section class="panel">

    <h2>Create membership package</h2>

    <form method="post" class="form-grid">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="plan"
        >

        <div>

            <label>Package name</label>

            <input
                name="name"
                required
                maxlength="100"
                placeholder="3-month membership"
            >

        </div>


        <div>

            <label>Duration (months)</label>

            <input
                type="number"
                name="duration_months"
                min="1"
                max="60"
                required
            >

        </div>


        <div>

            <label>Price (Rs.)</label>

            <input
                type="number"
                name="price"
                min="0"
                step="0.01"
                required
            >

        </div>


        <div class="full">

            <label>Description</label>

            <textarea
                name="description"
                maxlength="500"
            ></textarea>

        </div>


        <div>

            <button class="button" type="submit">
                Add package
            </button>

        </div>

    </form>

</section>


<section class="panel">

    <h2>Packages</h2>

    <?php if (!$plans): ?>

        <p>
            Add your first membership package.
        </p>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Package</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($plans as $p): ?>

                        <tr>

                            <td>
                                <?= e($p['name']) ?>
                            </td>

                            <td>
                                <?= $p['duration_months'] ?> months
                            </td>

                            <td>
                                Rs.
                                <?= number_format(
                                    (float) $p['price'],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= $p['is_active'] ? 'Active' : 'Disabled' ?>
                            </td>

                            <td>

                                <form method="post">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrfToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="toggle"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $p['id'] ?>"
                                    >

                                    <button
                                        class="btn btn-small"
                                        type="submit"
                                    >
                                        <?= $p['is_active']
                                            ? 'Disable'
                                            : 'Enable' ?>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<section class="panel">

    <h2>Membership requests</h2>

    <?php if (!$members): ?>

        <p>
            No membership requests yet.
        </p>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Member</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($members as $m): ?>

                        <tr>

                            <td>

                                <?= e($m['user_name']) ?>

                                <br>

                                <small>
                                    <?= e($m['email']) ?>
                                </small>

                            </td>


                            <td>

                                <?= e($m['plan_name']) ?>

                                (<?= $m['duration_months'] ?> mo.)

                            </td>


                            <td>

                                Rs.
                                <?= number_format(
                                    (float) $m['amount'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= e($m['start_date']) ?>

                                —

                                <?= e($m['end_date']) ?>

                            </td>


                            <td>
                                <?= e($m['payment_status']) ?>
                            </td>


                            <td>

                                <?php if ($m['payment_status'] === 'pending'): ?>

                                    <form
                                        method="post"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="membership"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= $m['id'] ?>"
                                        >

                                        <button
                                            class="btn btn-small"
                                            name="status"
                                            value="paid"
                                        >
                                            Confirm paid
                                        </button>

                                        <button
                                            class="btn btn-small"
                                            name="status"
                                            value="rejected"
                                        >
                                            Reject
                                        </button>

                                    </form>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>