<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/payments.php';

requireRole('user');

$uid = (int) $_SESSION['user_id'];
$error = '';
$selectedPlan = 0;
$selectedMethod = 'cash';
$reference = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $selectedPlan = (int) ($_POST['plan_id'] ?? 0);
    $method = fittrackNormalizePaymentMethod($_POST['payment_method'] ?? '');
    $selectedMethod = $method ?? 'cash';
    $reference = trim((string) ($_POST['payment_reference'] ?? ''));

    $q = $pdo->prepare(
        'SELECT id, name, duration_months, price
         FROM gym_membership_plans
         WHERE id = ? AND is_active = 1'
    );
    $q->execute([$selectedPlan]);
    $p = $q->fetch();

    $pc = $pdo->prepare("SELECT 1 FROM gym_memberships WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $pc->execute([$uid]);

    if (!$p) {
        $error = 'Please choose an available package.';
    } elseif ($pc->fetchColumn()) {
        $error = 'You already have a membership request waiting for admin approval.';
    } elseif ($method === null) {
        $error = 'Choose how you will pay: Cash or QR.';
    } elseif (mb_strlen($reference) > 120) {
        $error = 'The payment reference must be 120 characters or fewer.';
    } else {
        $proof = null;

        if ($method === 'qr') {
            $proof = fittrackSavePaymentProof($_FILES['proof'] ?? [], $error);
        } else {
            $reference = '';
        }

        if ($method === 'cash' || $proof !== null) {
            $insert = $pdo->prepare(
                "INSERT INTO gym_memberships
                (user_id, plan_id, start_date, end_date, amount, payment_status, status, payment_method, payment_reference, payment_proof)
                VALUES (?, ?, CURDATE(), CURDATE(), ?, 'pending', 'pending', ?, ?, ?)"
            );
            $insert->execute([$uid, $p['id'], $p['price'], $method, $reference ?: null, $proof]);
            $membershipId = (int) $pdo->lastInsertId();

            $methodLabel = fittrackPaymentMethodLabel($method);

            $pdo->prepare('INSERT INTO admin_notifications(title,message,related_type,related_id) VALUES(?,?,?,?)')
                ->execute([
                    'New gym membership request',
                    ($_SESSION['user_name'] ?? 'A member') . ' applied for the ' . $p['name'] . ' (' . $methodLabel . ').',
                    'gym_membership',
                    $membershipId,
                ]);

            fittrackEmailAdmins(
                $pdo,
                'New gym membership request - ' . ($_SESSION['user_name'] ?? 'member'),
                ($_SESSION['user_name'] ?? 'A member') . ' (' . ($_SESSION['user_email'] ?? '') . ') applied for the "' . $p['name']
                . '" gym membership (' . $p['duration_months'] . ' month(s), Rs. ' . number_format((float) $p['price'], 2) . ').'
                . "\nPayment method: " . $methodLabel . ($method === 'qr' ? ' (screenshot uploaded)' : ' (to be paid at reception)') . '.'
                . "\nPlease review it in Admin > Gym Fees."
            );

            flash(
                $method === 'qr'
                    ? 'Membership request and QR payment proof submitted. The other sections unlock once an admin confirms your payment.'
                    : 'Membership request submitted. Please pay the cash amount at the gym reception; the other sections unlock once an admin confirms it.'
            );

            redirect('membership.php');
        }
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
    if ($row['status'] === 'active' && $row['end_date'] >= date('Y-m-d')) {
        $active = $row;
        break;
    }
}

$pendingRequest = null;

foreach ($history as $row) {
    if ($row['status'] === 'pending') {
        $pendingRequest = $row;
        break;
    }
}
$hasPending = $pendingRequest !== null;

$selectedPrice = null;

foreach ($plans as $plan) {
    if ((int) $plan['id'] === $selectedPlan) {
        $selectedPrice = (float) $plan['price'];
    }
}
$amountText = $selectedPrice !== null ? fittrackMoney($selectedPrice) : 'Select a package above';

$pageTitle = 'Gym membership';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">GYM ACCESS</p>
        <h1>Gym membership</h1>
        <p>Choose a membership package and pay by cash at reception or by QR.</p>
    </div>
</section>
<?php if (!$active): ?>
    <div class="gate-banner">
        <?php if ($hasPending): ?>
            <strong>Waiting for approval.</strong> Your request was submitted. All other sections stay locked until the admin confirms your payment.
        <?php else: ?>
            <strong>Welcome!</strong> Choose a membership package below and pay by cash or QR. All other sections unlock once the admin approves your payment.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="gate-banner ok">Your membership is active. All sections are unlocked.</div>
<?php
endif; ?>
<?php if ($error):
?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<section class="panel">
    <h2>Your membership status</h2>
    <?php if ($active): ?>
        <p><strong><?= e($active['plan_name']) ?></strong> · <span class="pill">Active</span></p>
        <p>Valid until <?= e($active['end_date']) ?> · Paid Rs. <?= number_format((float) $active['amount'], 2) ?> (<?= e(fittrackPaymentMethodLabel($active['payment_method'] ?? null)) ?>)</p>
    <?php elseif ($hasPending): ?>
        <p><strong><?= e($pendingRequest['plan_name']) ?></strong> · <span class="pill">Pending approval</span></p>
        <p>Rs. <?= number_format((float) $pendingRequest['amount'], 2) ?> · <?= e(fittrackPaymentMethodLabel($pendingRequest['payment_method'] ?? null)) ?> · <?= ($pendingRequest['payment_method'] ?? 'cash') === 'qr'
                ? 'Your payment screenshot is waiting for the admin to verify.'
                : 'Please pay the cash amount at the gym reception so the admin can confirm it.' ?></p>
    <?php else: ?>
        <p>No active gym membership currently. Select a package below and choose Cash or QR.</p>
    <?php endif; ?>
</section>
<?php if (!$plans): ?>
    <section class="panel">
        <p>Membership packages are not yet available. Please check with the gym administrator.</p>
    </section>
<?php elseif ($hasPending): ?>
    <section class="panel">
        <h2>Payment portal</h2>
        <p>You already have a membership request waiting for admin approval, so a new one cannot be submitted yet.</p>
    </section>
<?php else: ?>
    <form method="post" enctype="multipart/form-data" class="panel pay-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <h2>1. Choose your package</h2>
        <p class="muted">Prices and durations are set by FitTrack administration.</p>
        <div class="plan-options">
            <?php foreach ($plans as $p): ?>
                <label class="plan-option"> <input type="radio" name="plan_id" value="<?= (int) $p['id'] ?>" data-plan-price="<?= e(fittrackMoney((float) $p['price'])) ?>" <?= $selectedPlan === (int) $p['id'] ? 'checked' : '' ?> required> <span class="plan-body"> <strong><?= e($p['name']) ?></strong> <em><?= (int) $p['duration_months'] ?> month<?= (int) $p['duration_months'] === 1 ? '' : 's' ?></em> <b>Rs. <?= number_format((float) $p['price'], 2) ?></b> <?php if ($p['description']): ?><small><?= e($p['description']) ?></small><?php endif; ?> </span> </label>
            <?php endforeach; ?>
        </div>
        <h2>2. Payment portal</h2>
        <?php fittrackPaymentPortalFields($selectedMethod, $amountText, $reference); ?>
        <button class="button" type="submit">Submit membership request</button>
    </form>
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
                        <th>Payment method</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= e($h['plan_name']) ?></td>
                            <td>Rs. <?= number_format((float) $h['amount'], 2) ?></td>
                            <td><?= e($h['start_date']) ?> – <?= e($h['end_date']) ?></td>
                            <td><?= e(fittrackPaymentMethodLabel($h['payment_method'] ?? null)) ?></td>
                            <td><?= e($h['payment_status']) ?></td>
                            <td><?= e($h['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
