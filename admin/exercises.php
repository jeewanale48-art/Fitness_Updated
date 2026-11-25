<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'General'));
        $muscle = trim((string) ($_POST['muscle_group'] ?? ''));
        $difficulty = $_POST['difficulty'] ?? 'beginner';
        $equipment = trim((string) ($_POST['equipment'] ?? ''));
        $instructions = trim((string) ($_POST['instructions'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) {
            $error = 'Enter an exercise name up to 150 characters.';
        } elseif (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) {
            $error = 'Choose a valid difficulty.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO exercises (name, category, muscle_group, difficulty, equipment, instructions) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $category ?: 'General', $muscle ?: null, $difficulty, $equipment ?: null, $instructions ?: null]);
            flash('Exercise added to the library.');
            redirect('exercises.php');
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['exercise_id'] ?? null, FILTER_VALIDATE_INT);

        if ($id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM exercises WHERE id = ?');
                $stmt->execute([$id]);
                flash('Exercise deleted.');
            } catch (PDOException $exception) {
                flash('This exercise is used by a workout plan or workout set and cannot be deleted yet.', 'error');
            }
            redirect('exercises.php');
        }
    }
}
$exercises = $pdo->query('SELECT id, name, category, muscle_group, difficulty, equipment FROM exercises ORDER BY name')->fetchAll();

$pageTitle = 'Exercise library';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">CONTENT MANAGEMENT</p>
        <h1>Exercise library</h1>
        <p>Exercises added here appear in every member's Exercise Library (Fitness Hub).</p>
    </div>
</section>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<section class="panel">
    <h2>Add an exercise</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="add">
        <div><label>Name</label><input name="name" maxlength="150" required></div>
        <div><label>Category</label><input name="category" maxlength="80" value="General"></div>
        <div><label>Muscle group</label><input name="muscle_group" maxlength="100"></div>
        <div>
            <label>Difficulty</label>
            <select name="difficulty">
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
        <div><label>Equipment</label><input name="equipment" maxlength="120"></div>
        <div class="full"><label>Instructions</label><textarea name="instructions" rows="4"></textarea></div>
        <div><button class="button" type="submit">Add exercise</button></div>
    </form>
</section>
<section class="panel">
    <h2>Current exercises (<?= count($exercises) ?>)</h2>
    <?php if (!$exercises): ?>
        <p>No exercises yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Muscle group</th>
                        <th>Difficulty</th>
                        <th>Equipment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exercises as $x): ?>
                        <tr>
                            <td><strong><?= e($x['name']) ?></strong></td>
                            <td><?= e($x['category']) ?></td>
                            <td><?= e($x['muscle_group'] ?: '—') ?></td>
                            <td><?= e(ucfirst($x['difficulty'])) ?></td>
                            <td><?= e($x['equipment'] ?: '—') ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this exercise?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="exercise_id" value="<?= (int) $x['id'] ?>">
                                    <button class="btn btn-small" type="submit">Delete</button>
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
