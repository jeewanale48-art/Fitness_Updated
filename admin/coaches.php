<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/coach_schedule.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE coaches SET is_active = 1 - is_active WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        redirect('coaches.php');
    }

    if ($action === 'schedule') {
        // Replace one coach's weekly availability (any of the 7 days).
        $coachId = (int) ($_POST['coach_id'] ?? 0);
        $check = $pdo->prepare('SELECT id FROM coaches WHERE id = ?');
        $check->execute([$coachId]);

        if (!$check->fetch()) {
            $error = 'Coach not found.';
        } else {
            $rows = fittrackParseSchedulePost($_POST, $error);

            if ($rows !== null) {
                fittrackSaveSchedule($pdo, $coachId, $rows);
                flash('Weekly availability saved (' . count($rows) . ' of 7 days).');
                redirect('coaches.php#coach-' . $coachId);
            }
        }
    }

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $spec = trim((string) ($_POST['specialization'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $years = filter_var($_POST['experience_years'] ?? '', FILTER_VALIDATE_INT);
        $fee = trim((string) ($_POST['fee'] ?? ''));

        if ($name === '' || $spec === '') {
            $error = 'Coach name and specialization are required.';
        } else {
            $rows = fittrackParseSchedulePost($_POST, $error);

            if ($rows !== null) {
                $insert = $pdo->prepare('INSERT INTO coaches (name, email, specialization, bio, experience_years, fee) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->execute([
                    $name,
                    trim((string) ($_POST['email'] ?? '')) ?: null,
                    $spec,
                    $bio ?: null,
                    $years === false ? null : $years,
                    $fee === '' ? null : (float) $fee,
                ]);
                $coachId = (int) $pdo->lastInsertId();

                if ($rows) {
                    fittrackSaveSchedule($pdo, $coachId, $rows);
                }
                flash('Coach added with availability on ' . count($rows) . ' of 7 days.');
                redirect('coaches.php');
            }
        }
    }
}

$rows = $pdo->query('SELECT * FROM coaches ORDER BY created_at DESC')->fetchAll();
$schedules = fittrackLoadSchedules($pdo);
$pageTitle = 'Coach management';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">COACH DIRECTORY</p>
        <h1>Manage coaches</h1>
        <p>Add coaches, set their availability for any of the 7 days of the week, and control whether users can request sessions.</p>
    </div>
</section>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<section class="panel">
    <h2>Add coach</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="add">
        <div><label>Name</label><input name="name" required></div>
        <div><label>Email (optional)</label><input type="email" name="email"></div>
        <div><label>Specialization</label><input name="specialization" required></div>
        <div><label>Experience (years)</label><input type="number" name="experience_years" min="0" max="60"></div>
        <div><label>Fee (optional)</label><input type="number" step="0.01" min="0" name="fee"></div>
        <div class="full"><label>Biography</label><textarea name="bio" rows="3"></textarea></div>
        <div class="full">
            <label>Weekly availability <small class="muted">(tick the days the coach works, any of the 7)</small></label>
            <?php fittrackScheduleGrid([]); ?>
        </div>
        <div><button class="button">Add coach</button></div>
    </form>
</section>
<section class="panel">
    <h2>Coach directory</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Fee</th>
                    <th>Availability</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $c): $byDay = $schedules[(int) $c['id']] ?? []; ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['specialization']) ?></td>
                    <td><?= e((string) ($c['experience_years'] ?? '—')) ?></td>
                    <td><?= e((string) ($c['fee'] ?? 'On request')) ?></td>
                    <td>
                        <div class="day-chips">
<?php
foreach (fittrackWeekdays() as $day => $label):
                            $w = $byDay[$day] ?? [];
                            $title = $w ? fittrackFormatTime($w[0]['start']) . ' – ' . fittrackFormatTime(end($w)['end']) : 'Not available';
?>
                            <span class="day-chip <?= $w ? 'on' : 'off' ?>" title="<?= e($label . ': ' . $title) ?>"><?= e(substr($label, 0, 1)) ?></span> <?php endforeach; ?>
                        </div>
                        <small class="muted"><?= e(fittrackScheduleSummary($byDay)) ?></small>
                    </td>
                    <td><?= $c['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="button light btn-small">Toggle status</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Edit weekly availability</h2>
    <p class="muted">Members see these days and times on the Personal Coach page and can only book inside them.</p>
    <?php if (!$rows): ?>
        <p>Add a coach first.</p>
    <?php endif; ?>
    <?php foreach ($rows as $c): $byDay = $schedules[(int) $c['id']] ?? []; ?>
    <details class="schedule-details" id="coach-<?= (int) $c['id'] ?>">
        <summary><strong><?= e($c['name']) ?></strong> · <?= e($c['specialization']) ?> <span class="badge"><?= e(fittrackScheduleSummary($byDay)) ?></span></summary>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="schedule">
            <input type="hidden" name="coach_id" value="<?= (int) $c['id'] ?>">
            <?php fittrackScheduleGrid($byDay); ?>
            <p><button class="button">Save availability for <?= e($c['name']) ?></button></p>
        </form>
    </details>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
