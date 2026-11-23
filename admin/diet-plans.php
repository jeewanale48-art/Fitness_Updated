<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $title    = trim((string) ($_POST['title'] ?? ''));
        $desc     = trim((string) ($_POST['description'] ?? ''));
        $level    = $_POST['fitness_level'] ?? '';
        $access   = $_POST['access_type'] ?? '';
        $guidance = trim((string) ($_POST['guidance'] ?? ''));
    $pref = $_POST['dietary_preference'] ?? 'no_preference';

        if (
            $title === '' || mb_strlen($title) > 160
            || !in_array($level, ['beginner', 'intermediate', 'expert'], true)
            || !in_array($access, ['free', 'premium'], true)
        || !in_array($pref, ['no_preference', 'vegetarian', 'vegan', 'pescatarian', 'other'], true)
        ) {
            $error = 'Complete all required fields with valid options.';
        } else {
            $pdo->prepare('INSERT INTO diet_plans(title,description,fitness_level,access_type,dietary_preference,guidance,created_by) VALUES(?,?,?,?,?,?,?)')
                ->execute([$title, $desc ?: null, $level, $access, $pref, $guidance ?: null, $_SESSION['user_id']]);
            flash('Diet plan published.');
            redirect('diet-plans.php');
        }
    }

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE diet_plans SET is_active = 1 - is_active WHERE id = ?')
            ->execute([(int) ($_POST['id'] ?? 0)]);
        redirect('diet-plans.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM diet_plans WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('Diet plan deleted.');
        redirect('diet-plans.php');
    }
}

$plans = $pdo->query('SELECT * FROM diet_plans ORDER BY access_type DESC, created_at DESC')->fetchAll();

$pageTitle = 'Diet plans';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">CONTENT MANAGEMENT</p>
        <h1>Diet plans</h1>
        <p>Create diet plans for each fitness level and choose whether they are free or Premium only.
           <a href="workout-plans.php">Go to workout plans →</a></p>
    </div>
</section>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Create a diet plan</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <div class="full">
            <label>Title</label>
            <input name="title" required maxlength="160">
        </div>
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
        <div>
            <label>Diet preference</label>
            <select name="dietary_preference">
                <option value="no_preference">Any preference</option>
                <option value="vegetarian">Vegetarian</option>
                <option value="vegan">Vegan</option>
                <option value="pescatarian">Pescatarian</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="full">
            <label>Description</label>
            <textarea name="description" rows="2"></textarea>
        </div>
        <div class="full">
            <label>Guidance / details</label>
            <textarea name="guidance" rows="4"></textarea>
        </div>
        <div><button class="button">Publish diet plan</button></div>
    </form>
</section>

<section class="panel">
    <h2>All diet plans (<?= count($plans) ?>)</h2>
    <?php if (!$plans): ?>
        <p>No diet plans yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Title</th><th>Level</th><th>Access</th><th>Diet preference</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($plans as $p): ?>
                    <tr>
                        <td><strong><?= e($p['title']) ?></strong></td>
                        <td><?= e(ucfirst($p['fitness_level'])) ?></td>
                        <td><span class="badge"><?= e(ucfirst($p['access_type'])) ?></span></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string) ($p['dietary_preference'] ?? 'any')))) ?></td>
                        <td><?= $p['is_active'] ? 'Active' : 'Hidden' ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="btn btn-small" name="action" value="toggle"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
                                <button class="btn btn-small" name="action" value="delete" onclick="return confirm('Delete this diet plan?')">Delete</button>
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
