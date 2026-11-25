<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/payments.php';
require_once __DIR__ . '/includes/coach_schedule.php';
requireRole('user');
requireGymMembership();

$uid = (int) $_SESSION['user_id'];
$error = '';
$action = (string) ($_POST['action'] ?? 'book');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($action === 'pay') {
        /*
         * Payment portal: only for a session the admin has APPROVED, and only while it is
         * still unpaid (or the previous payment was rejected).
         */
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $query = $pdo->prepare(
            'SELECT b.id, b.status, b.payment_status, b.session_start, c.fee, c.name AS coach_name
             FROM coach_bookings b JOIN coaches c ON c.id = b.coach_id
             WHERE b.id = ? AND b.user_id = ?'
        );
        $query->execute([$bookingId, $uid]);
        $booking = $query->fetch();
        $method = fittrackNormalizePaymentMethod($_POST['payment_method'] ?? '');
        $reference = trim((string) ($_POST['payment_reference'] ?? ''));

        if (!$booking) {
            $error = 'Session not found.';
        } elseif ($booking['status'] !== 'approved') {
            $error = 'Payment opens only after the admin approves your session request.';
        } elseif (!in_array($booking['payment_status'], ['unpaid', 'rejected'], true)) {
            $error = 'A payment for this session has already been submitted.';
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
                $amount = $booking['fee'] === null ? null : (float) $booking['fee']; // always taken from the coach, never from the form
                $pdo->prepare(
                    "UPDATE coach_bookings
                     SET payment_method = ?, payment_status = 'pending', payment_amount = ?, payment_reference = ?,
                         payment_proof = ?, payment_note = NULL, payment_submitted_at = NOW(), payment_reviewed_at = NULL
                     WHERE id = ? AND user_id = ?"
                )->execute([$method, $amount, $reference ?: null, $proof, $bookingId, $uid]);

                $pdo->prepare('INSERT INTO admin_notifications (title, message, related_type, related_id) VALUES (?, ?, ?, ?)')
                    ->execute([
                        'Coaching payment submitted',
                        ($_SESSION['user_name'] ?? 'A member') . ' submitted a ' . fittrackPaymentMethodLabel($method) . ' payment for a coaching session with ' . $booking['coach_name'] . '.',
                        'booking',
                        $bookingId,
                    ]);
                fittrackEmailAdmins(
                    $pdo,
                    'Coaching payment to review - ' . ($_SESSION['user_name'] ?? 'member'),
                    ($_SESSION['user_name'] ?? 'A member') . ' submitted a ' . fittrackPaymentMethodLabel($method) . ' payment for the coaching session with '
                    . $booking['coach_name'] . ' on ' . $booking['session_start'] . '. Please review it in Admin > Bookings.'
                );

                flash($method === 'qr'
                    ? 'Payment proof submitted. The admin will verify your QR payment.'
                    : 'Cash payment noted. Please pay at the gym reception; the admin will confirm it.');
                redirect('coaching.php');
            }
        }
    } else {
        // Request a session
        $coachId = (int) ($_POST['coach_id'] ?? 0);
        $when = str_replace('T', ' ', (string) ($_POST['session_start'] ?? ''));
        $message = trim((string) ($_POST['user_message'] ?? ''));
        $date = DateTime::createFromFormat('Y-m-d H:i', $when);

        $coachQuery = $pdo->prepare('SELECT id FROM coaches WHERE id = ? AND is_active = 1');
        $coachQuery->execute([$coachId]);

        if (!$coachQuery->fetch()) {
            $error = 'Select an available coach.';
        } elseif (!$date || $date < new DateTime('+1 hour')) {
            $error = 'Choose a session at least one hour in the future.';
        } else {
            $weekday = (int) $date->format('N');
            $clock = $date->format('H:i:s');
            $endMoment = (clone $date)->modify('+60 minutes');
            $endClock = $endMoment->format('H:i:s');
            $sameDay = $endMoment->format('Y-m-d') === $date->format('Y-m-d');

            $scheduleCountQuery = $pdo->prepare('SELECT COUNT(*) FROM coach_availability WHERE coach_id = ?');
            $scheduleCountQuery->execute([$coachId]);
            $hasSchedule = (int) $scheduleCountQuery->fetchColumn() > 0;

            $available = false;

            if ($hasSchedule && $sameDay) {
                // The whole 60-minute session must fit inside the coach's hours for that weekday.
                $availabilityQuery = $pdo->prepare('SELECT id FROM coach_availability WHERE coach_id = ? AND weekday = ? AND start_time <= ? AND end_time >= ? LIMIT 1');
                $availabilityQuery->execute([$coachId, $weekday, $clock, $endClock]);
                $available = (bool) $availabilityQuery->fetch();
            }

            if (!$available) {
                $error = 'That coach is not available at the selected time. Pick one of the open slots shown.';
            } else {
                $conflictQuery = $pdo->prepare("SELECT id FROM coach_bookings WHERE coach_id = ? AND status IN ('pending','approved') AND session_start < DATE_ADD(?, INTERVAL 60 MINUTE) AND DATE_ADD(session_start, INTERVAL duration_minutes MINUTE) > ? LIMIT 1");
                $selectedTime = $date->format('Y-m-d H:i:s');
                $conflictQuery->execute([$coachId, $selectedTime, $selectedTime]);

                if ($conflictQuery->fetch()) {
                    $error = 'That time is already requested or booked. Choose another time.';
                } else {
                    $insert = $pdo->prepare('INSERT INTO coach_bookings (user_id, coach_id, session_start, user_message) VALUES (?, ?, ?, ?)');
                    $insert->execute([$uid, $coachId, $selectedTime, $message ?: null]);
                    $bookingId = (int) $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO admin_notifications (title, message, related_type, related_id) VALUES (?, ?, ?, ?)')
                        ->execute(['New coaching request', $_SESSION['user_name'] . ' requested a coaching session.', 'booking', $bookingId]);
                    flash('Coaching request submitted for admin approval. You can pay once the admin approves it.');
                    redirect('coaching.php');
                }
            }
        }
    }
}

$coaches = $pdo->query('SELECT c.* FROM coaches c WHERE c.is_active = 1 ORDER BY c.name')->fetchAll();
$schedules = fittrackLoadSchedules($pdo);

// Time ranges already taken by pending/approved sessions, so they are not offered again.
$busy = [];
$busyRows = $pdo->query("SELECT coach_id, session_start, duration_minutes FROM coach_bookings WHERE status IN ('pending','approved') AND session_start >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchAll();

foreach ($busyRows as $row) {
    $start = new DateTimeImmutable($row['session_start']);
    $busy[(int) $row['coach_id']][] = [$start, $start->modify('+' . (int) $row['duration_minutes'] . ' minutes')];
}

$bookingQuery = $pdo->prepare(
    'SELECT b.*, c.name coach_name, c.specialization, c.fee coach_fee
     FROM coach_bookings b JOIN coaches c ON c.id = b.coach_id
     WHERE b.user_id = ? ORDER BY b.session_start DESC'
);
$bookingQuery->execute([$uid]);
$bookings = $bookingQuery->fetchAll();

// Sessions that are approved and still need to be paid for.
$dueBookings = array_values(array_filter(
    $bookings,
    static fn (array $b): bool => $b['status'] === 'approved' && in_array($b['payment_status'] ?? 'unpaid', ['unpaid', 'rejected'], true)
));

function coachingPaymentCell(array $b): string
{
    $status = (string) ($b['status'] ?? '');
    $pay = (string) ($b['payment_status'] ?? 'unpaid');
    $method = fittrackPaymentMethodLabel($b['payment_method'] ?? null);

    if ($status === 'pending') {
        return '<span class="muted">Available after admin approval</span>';
    }

    if (in_array($status, ['rejected', 'cancelled'], true) && $pay === 'unpaid') {
        return '—';
    }

    return match ($pay) {
        'paid' => '<span class="badge">Paid</span> <small>' . e($method) . '</small>',
        'pending' => '<span class="badge badge-pending">Under review</span> <small>' . e($method) . '</small>',
        'rejected' => $status === 'approved'
            ? '<span class="badge badge-expired">Rejected</span> <a href="#pay-' . (int) $b['id'] . '">Pay again ↓</a>'
            : '<span class="badge badge-expired">Rejected</span>',
        default => $status === 'approved'
            ? '<a class="button btn-small" href="#pay-' . (int) $b['id'] . '">Pay now ↓</a>'
            : '—',
    };
}

$pageTitle = 'Coaching';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">PERSONAL SUPPORT</p>
        <h1>Find your coach</h1>
        <p>See when each coach is available, pick an open slot and request a session. Once the admin approves it, you can pay by cash or QR.</p>
    </div>
</section>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php
endif; ?>
<?php if (!$coaches):
?>
    <section class="panel">
        <p>No coaches are available right now. Please check back soon.</p>
    </section>
<?php endif; ?>
<div class="coach-grid">
<?php
foreach ($coaches as $coach):
    $cid = (int) $coach['id'];
    $byDay = $schedules[$cid] ?? [];
    $slots = fittrackOpenSlots($byDay, $busy[$cid] ?? []);
    $firstDate = array_key_first($slots);
    $firstSlot = $firstDate !== null ? $slots[$firstDate][0] : null;
?>
    <article class="panel coach-card">
        <div class="coach-avatar"><?= e(strtoupper(substr($coach['name'], 0, 1))) ?></div>
        <h3><?= e($coach['name']) ?></h3>
        <p><strong><?= e($coach['specialization']) ?></strong></p>
        <p><?= nl2br(e($coach['bio'] ?? '')) ?></p>
        <p class="muted"><?= e((string) ($coach['experience_years'] ?? 'Not specified')) ?> years experience · <?= e($coach['fee'] === null ? 'Fee on request' : 'Rs. ' . number_format((float) $coach['fee'], 2) . ' per session') ?></p>
        <h4 class="coach-sub">Weekly availability</h4>
        <?php if ($byDay): ?>
            <?php fittrackScheduleTable($byDay); ?>
        <?php else: ?>
            <p class="muted">Schedule to be confirmed by admin.</p>
        <?php endif; ?>
        <?php if ($firstSlot): ?>
            <p class="next-slot">Next available: <strong><?= e($firstSlot->format('D, d M Y')) ?> · <?= e($firstSlot->format('g:i A')) ?></strong></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="book">
            <input type="hidden" name="coach_id" value="<?= $cid ?>"> <?php if ($slots): ?> <label>Choose an open date and time <small class="muted">(next 14 days, 60-minute sessions)</small></label>
            <select name="session_start" required>
                <option value="">Select a slot…</option>
                <?php foreach ($slots as $list): ?>
                <optgroup label="<?= e($list[0]->format('l, d M Y')) ?>">
                    <?php foreach ($list as $slot): ?>
                    <option value="<?= e($slot->format('Y-m-d\TH:i')) ?>"><?= e($slot->format('g:i A')) ?> – <?= e($slot->modify('+60 minutes')->format('g:i A')) ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <label>Message (optional)</label><textarea name="user_message" rows="2"></textarea>
            <button class="button full">Request session</button> <?php else: ?>
            <p class="muted"><?= $byDay ? 'No open slots in the next 14 days. Please check again soon.' : 'Booking opens once the admin sets this coach\'s schedule.' ?></p>
            <?php endif; ?>
        </form>
    </article>
    <?php endforeach; ?>
</div>
<?php if ($dueBookings): ?>
    <section class="panel" id="payments">
        <h2>Payment due</h2>
        <p class="muted">These sessions were approved by the admin. Choose Cash or QR to pay for each one.</p>
<?php
foreach ($dueBookings as $b):
        $amountText = $b['coach_fee'] === null ? 'To be confirmed by the admin' : fittrackMoney((float) $b['coach_fee']);
?>
        <article class="pay-card" id="pay-<?= (int) $b['id'] ?>">
            <header><strong><?= e($b['coach_name']) ?></strong> · <?= e((new DateTimeImmutable($b['session_start']))->format('D, d M Y · g:i A')) ?> <span class="badge">Approved</span></header>
            <?php if (($b['payment_status'] ?? '') === 'rejected'): ?>
                <div class="alert error">Your previous payment was not accepted<?= !empty($b['payment_note']) ? ': ' . e($b['payment_note']) : '' ?>. Please pay again.</div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                <?php fittrackPaymentPortalFields('cash', $amountText); ?>
                <button class="button" type="submit">Submit payment</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Your coaching requests</h2>
    <?php if (!$bookings): ?>
        <p>No coaching bookings yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Coach</th>
                        <th>Specialization</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Admin note</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= e($booking['coach_name']) ?></td>
                            <td><?= e($booking['specialization']) ?></td>
                            <td><?= e((new DateTimeImmutable($booking['session_start']))->format('D, d M Y · g:i A')) ?></td>
                            <td><?= e(ucfirst($booking['status'])) ?></td>
                            <td><?= e($booking['admin_message'] ?? '—') ?></td>
                            <td><?= coachingPaymentCell($booking) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
