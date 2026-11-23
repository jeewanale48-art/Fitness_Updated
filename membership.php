<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

requireRole('user');

$uid = (int) $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $plan = (int) ($_POST['plan_id'] ?? 0);

    $q = $pdo->prepare(
        'SELECT id, name, duration_months, price
         FROM gym_membership_plans
         WHERE id = ? AND is_active = 1'
    );

    $q->execute([$plan]);
    $p = $q->fetch();

    $pc = $pdo->prepare("SELECT 1 FROM gym_memberships WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $pc->execute([$uid]);

    if (!$p) {
        $error = 'Please choose an available package.';
    } elseif ($pc->fetchColumn()) {
        $error = 'You already have a membership request waiting for admin approval.';
    } else {
        $q = $pdo->prepare(
            "INSERT INTO gym_memberships
            (user_id, plan_id, start_date, end_date, amount, payment_status, status)
            VALUES (?, ?, CURDATE(), CURDATE(), ?, 'pending', 'pending')"
        );

        $q->execute([
            $uid,
            $p['id'],
            $p['price']
        ]);

        $pdo->prepare('INSERT INTO admin_notifications(title,message,related_type,related_id) VALUES(?,?,?,?)')
            ->execute([
                'New gym membership request',
                ($_SESSION['user_name'] ?? 'A member') . ' applied for the ' . $p['name'] . '.',
                'gym_membership',
                (int) $pdo->lastInsertId()
            ]);

        fittrackEmailAdmins(
            $pdo,
            'New gym membership request - ' . ($_SESSION['user_name'] ?? 'member'),
            ($_SESSION['user_name'] ?? 'A member') . ' (' . ($_SESSION['user_email'] ?? '') . ') applied for the "' . $p['name']
            . '" gym membership (' . $p['duration_months'] . ' month(s), Rs. ' . number_format((float) $p['price'], 2) . ').'
            . "\nPlease review it in Admin > Gym Fees."
        );

        flash(
            'Membership request submitted. Please pay at the gym reception; the other sections unlock once an admin approves it.'
        );

        redirect('membership.php');
    }
}

$plans = $pdo->query(
    'SELECT *
     FROM gym_membership_plans
     WHERE is_active = 1
     ORDER BY duration_months, price'
)->fetchAll();

$q = $pdo->prepare(
    "SELECT m.*, p.name AS plan_name, p.duration_months
     FROM gym_memberships m
     JOIN gym_membership_plans p ON p.id = m.plan_id
     WHERE m.user_id = ?
     ORDER BY m.created_at DESC"
);

$q->execute([$uid]);

$history = $q->fetchAll();

$active = null;

foreach ($history as $row) {
    if (
        $row['status'] === 'active' &&
        $row['end_date'] >= date('Y-m-d')
    ) {
        $active = $row;
        break;
    }
}

$hasPending = false;
foreach ($history as $row) {
    if ($row['status'] === 'pending') { $hasPending = true; break; }
}

$pageTitle = 'Gym membership';

require __DIR__ . '/includes/header.php';

?>

<section class="page-heading">
    <div>
        <p class="eyebrow">GYM ACCESS</p>

        <h1>Gym membership</h1>

        <p>
            Choose the membership duration and pay at the gym reception.
        </p>
    </div>
</section>

<?php if (!$active): ?>
    <div class="gate-banner">
        <?php if ($hasPending): ?>
            <strong>Waiting for approval.</strong> Your request was submitted. All other sections stay locked until the admin confirms your payment.
        <?php else: ?>
            <strong>Welcome!</strong> Choose a membership package below to get started. All other sections unlock once the admin approves your fee.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="gate-banner ok">Your membership is active. All sections are unlocked.</div>
<?php endif; ?>

<?php if ($error): ?>

    <div class="alert error">
        <?= e($error) ?>
    </div>

<?php endif; ?>

<section class="panel">

    <h2>Your membership status</h2>

    <?php if ($active): ?>

        <p>
            <strong><?= e($active['plan_name']) ?></strong>
            ·
            <span class="pill">Active</span>
        </p>

        <p>
            Valid until <?= e($active['end_date']) ?>
            ·
            Paid Rs. <?= number_format((float) $active['amount'], 2) ?>
        </p>

    <?php else: ?>

        <p>
            No active gym membership currently.
            Select a package below and pay at reception.
        </p>

    <?php endif; ?>

</section>

<section class="page-heading">

    <div>
        <h2>Available packages</h2>

        <p>
            Prices and durations are set by FitTrack administration.
        </p>
    </div>

</section>

<?php if (!$plans): ?>

    <section class="panel">

        <p>
            Membership packages are not yet available.
            Please check with the gym administrator.
        </p>

    </section>

<?php else: ?>

    <div class="card-grid">

        <?php foreach ($plans as $p): ?>

            <article class="exercise-card">

                <h3>
                    <?= e($p['name']) ?>
                </h3>

                <p>
                    <strong>
                        <?= $p['duration_months'] ?>
                        month<?= $p['duration_months'] === 1 ? '' : 's' ?>
                    </strong>
                </p>

                <h2>
                    Rs. <?= number_format((float) $p['price'], 2) ?>
                </h2>

                <?php if ($p['description']): ?>

                    <p>
                        <?= e($p['description']) ?>
                    </p>

                <?php endif; ?>

                <?php if ($hasPending): ?>
                    <button class="button" type="button" disabled>Request pending</button>
                <?php else: ?>
                <form method="post">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="plan_id"
                        value="<?= $p['id'] ?>"
                    >

                    <button class="button" type="submit">
                        Request this membership
                    </button>

                </form>
                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<section class="panel">

    <h2>Membership history / requests</h2>

    <?php if (!$history): ?>

        <p>No requests yet.</p>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Dates</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($history as $h): ?>

                        <tr>

                            <td>
                                <?= e($h['plan_name']) ?>
                            </td>

                            <td>
                                Rs. <?= number_format((float) $h['amount'], 2) ?>
                            </td>

                            <td>
                                <?= e($h['start_date']) ?>
                                –
                                <?= e($h['end_date']) ?>
                            </td>

                            <td>
                                <?= e($h['payment_status']) ?>
                            </td>

                            <td>
                                <?= e($h['status']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>