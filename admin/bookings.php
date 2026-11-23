<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/payments.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? 'status');
    $id = (int) ($_POST['id'] ?? 0);
    $note = trim((string) ($_POST['note'] ?? ''));

    if ($action === 'payment') {
        // Confirm or reject a coaching payment (cash received / QR proof verified).
        $decision = $_POST['decision'] ?? '';
        if (in_array($decision, ['paid', 'rejected'], true)) {
            $q = $pdo->prepare("SELECT user_id, session_start, payment_method FROM coach_bookings WHERE id = ? AND payment_status = 'pending'");
            $q->execute([$id]);
            $b = $q->fetch();
            if ($b) {
                $pdo->prepare('UPDATE coach_bookings SET payment_status = ?, payment_note = ?, payment_reviewed_at = NOW() WHERE id = ?')
                    ->execute([$decision, $note ?: null, $id]);
                $paid = $decision === 'paid';
                fittrackNotify(
                    $pdo,
                    (int) $b['user_id'],
                    $paid ? 'Coaching payment confirmed' : 'Coaching payment not accepted',
                    $paid
                        ? 'Your ' . fittrackPaymentMethodLabel($b['payment_method']) . ' payment for the coaching session on ' . $b['session_start'] . ' was confirmed. ' . $note
                        : 'Your payment for the coaching session on ' . $b['session_start'] . ' was not accepted. ' . ($note ?: 'Please open Personal Coach and pay again.'),
                    $paid ? 'FitTrack coaching payment confirmed' : 'Update on your FitTrack coaching payment'
                );
                flash($paid ? 'Payment confirmed.' : 'Payment rejected - the member can pay again.');
            }
        }
        redirect('bookings.php');
    }

    $status = $_POST['status'] ?? '';
    if (in_array($status, ['approved', 'rejected', 'completed', 'cancelled'], true)) {
        $q = $pdo->prepare('SELECT user_id, session_start FROM coach_bookings WHERE id = ?');
        $q->execute([$id]);
        $b = $q->fetch();
        if ($b) {
            $pdo->prepare('UPDATE coach_bookings SET status = ?, admin_message = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?')
                ->execute([$status, $note ?: null, $_SESSION['user_id'], $id]);
            $message = 'Your coaching request for ' . $b['session_start'] . ' is ' . $status . '. ' . ($note ?: '');
            if ($status === 'approved') {
                $message .= ' You can now pay for the session (cash or QR) from the Personal Coach page.';
            }
            fittrackNotify($pdo, (int) $b['user_id'], 'Coaching booking ' . $status, $message, 'FitTrack coaching booking update');
        }
    }
    redirect('bookings.php');
}

$rows = $pdo->query(
    'SELECT b.*, u.name user_name, u.email, c.name coach_name, c.fee coach_fee
     FROM coach_bookings b
     JOIN users u ON u.id = b.user_id
     JOIN coaches c ON c.id = b.coach_id
     ORDER BY b.session_start DESC'
)->fetchAll();
$pageTitle = 'Coaching bookings';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">COACHING</p><h1>Coaching bookings</h1><p>Approve session requests, then confirm each member's cash or QR payment.</p></div></section>
<section class="panel"><div class="table-wrap"><table>
<thead><tr><th>User</th><th>Coach</th><th>Session time</th><th>Status</th><th>Payment</th><th>Decision</th></tr></thead>
<tbody>
<?php foreach ($rows as $b):
    $pay = (string) ($b['payment_status'] ?? 'unpaid');
    $amount = $b['payment_amount'] !== null ? (float) $b['payment_amount'] : ($b['coach_fee'] !== null ? (float) $b['coach_fee'] : null);
    ?>
<tr>
    <td><?= e($b['user_name']) ?><br><small><?= e($b['email']) ?></small></td>
    <td><?= e($b['coach_name']) ?></td>
    <td><?= e($b['session_start']) ?></td>
    <td><?= e(ucfirst($b['status'])) ?></td>
    <td>
        <?php if ($pay === 'unpaid'): ?>
            <?= in_array($b['status'], ['approved', 'completed'], true) ? '<small>Awaiting payment</small>' : '—' ?>
        <?php else: ?>
            <span class="badge <?= $pay === 'paid' ? '' : ($pay === 'pending' ? 'badge-pending' : 'badge-expired') ?>"><?= e(ucfirst($pay === 'pending' ? 'to review' : $pay)) ?></span><br>
            <strong><?= e(fittrackPaymentMethodLabel($b['payment_method'] ?? null)) ?></strong><?= $amount !== null ? ' · ' . e(fittrackMoney($amount)) : '' ?>
            <?php if (!empty($b['payment_reference'])): ?><br><small>Ref: <?= e($b['payment_reference']) ?></small><?php endif; ?>
            <?php if (!empty($b['payment_proof'])): ?><br><a target="_blank" rel="noopener" href="payment-proof.php?type=coach&amp;id=<?= (int) $b['id'] ?>">View payment proof</a><?php endif; ?>
            <?php if ($pay === 'pending'): ?>
                <form method="post" class="admin-decision">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="payment">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <input name="note" placeholder="Optional note">
                    <button class="button btn-small" name="decision" value="paid"><?= ($b['payment_method'] ?? '') === 'cash' ? 'Cash received' : 'Confirm payment' ?></button>
                    <button class="button danger btn-small" name="decision" value="rejected">Reject</button>
                </form>
            <?php elseif (!empty($b['payment_note'])): ?><br><small>Note: <?= e($b['payment_note']) ?></small><?php endif; ?>
        <?php endif; ?>
    </td>
    <td><form method="post" class="admin-decision"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input name="note" placeholder="Optional note"><select name="status"><option value="approved">Approve</option><option value="rejected">Reject</option><option value="completed">Completed</option><option value="cancelled">Cancel</option></select><button class="button btn-small">Update</button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
