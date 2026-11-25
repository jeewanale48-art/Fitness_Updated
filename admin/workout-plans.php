<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $level = $_POST['fitness_level'] ?? '';
        $access = $_POST['access_type'] ?? '';
        $guidance = trim((string) ($_POST['guidance'] ?? ''));
        $schedule = trim((string) ($_POST['schedule_text'] ?? ''));

        if (
            $title === '' || mb_strlen($title) > 160
            || !in_array($level, ['beginner', 'intermediate', 'expert'], true)
            || !in_array($access, ['free', 'premium'], true)
        ) {
            $error = 'Complete all required fields with valid options.';
        } else {
            $pdo->prepare('INSERT INTO workout_templates(title,description,fitness_level,access_type,schedule_text,guidance,created_by) VALUES(?,?,?,?,?,?,?)')
                ->execute([$title, $desc ?: null, $level, $access, $schedule ?: null, $guidance ?: null, $_SESSION['user_id']]);
            flash('Workout plan published.');
            redirect('workout-plans.php');
        }
    }

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE workout_templates SET is_active = 1 - is_active WHERE id = ?')
            ->execute([(int) ($_POST['id'] ?? 0)]);
        redirect('workout-plans.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM workout_templates WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('Workout plan deleted.');
        redirect('workout-plans.php');
    }
}

$plans = $pdo->query('SELECT * FROM workout_templates ORDER BY access_type DESC, created_at DESC')->fetchAll();

$pageTitle = 'Workout plans';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">CONTENT MANAGEMENT</p>
        <h1>Workout plans</h1>
        <p>Create workout plans for each fitness level and choose whether they are free or Premium only. <a href="diet-plans.php">Go to diet plans →</a></p>
    </div>
</section>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<section class="panel">
    <h2>Create a workout plan</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <div class="full"><label>Title</label> <input name="title" required maxlength="160"></div>
        <div>
            <label>Fitness level</label>
            <select name="fitness_level">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
            </select>
        </div>
        <div>
            <label>Access</label>
            <select name="access_type">
                <option value="free">Free</option>
                <option value="premium">Premium</option>
            </select>
        </div>
        <div class="full">
            <label>Weekly schedule</label>
            <textarea name="schedule_text" rows="3" placeholder="Mon: Chest &amp; triceps&#10;Tue: Back &amp; biceps"></textarea>
        </div>
        <div class="full"><label>Description</label> <textarea name="description" rows="2"></textarea></div>
        <div class="full"><label>Guidance / details</label> <textarea name="guidance" rows="4"></textarea></div>
        <div><button class="button">Publish workout plan</button></div>
    </form>
</section>
<section class="panel">
    <h2>All workout plans (<?= count($plans) ?>)</h2>
    <?php if (!$plans): ?>
        <p>No workout plans yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Level</th>
                        <th>Access</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td><strong><?= e($p['title']) ?></strong></td>
                            <td><?= e(ucfirst($p['fitness_level'])) ?></td>
                            <td><span class="badge"><?= e(ucfirst($p['access_type'])) ?></span></td>
                            <td><?= e(mb_strimwidth((string) ($p['schedule_text'] ?? '—'), 0, 60, '…')) ?></td>
                            <td><?= $p['is_active'] ? 'Active' : 'Hidden' ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button class="btn btn-small" name="action" value="toggle"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
                                    <button class="btn btn-small" name="action" value="delete" onclick="return confirm('Delete this workout plan?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
