<?php
require_once __DIR__ . '/includes/auth.php';
requireRole('user');
requireGymMembership();

$uid = (int) $_SESSION['user_id'];
$isPremium = hasActivePremium($pdo, $uid);

$q = $pdo->prepare('SELECT fitness_level, dietary_preference FROM users WHERE id = ?');
$q->execute([$uid]);
$u = $q->fetch();
$level = $u['fitness_level'] === 'advanced' ? 'expert' : $u['fitness_level'];
$pref = $u['dietary_preference'] ?: 'no_preference';

// Free content matched to the member's level (premium content lives under Premium Subscription > My Plans)
$q = $pdo->prepare("SELECT * FROM diet_plans
    WHERE is_active = 1 AND access_type = 'free' AND fitness_level = ?
      AND (dietary_preference IS NULL OR dietary_preference = 'no_preference' OR dietary_preference = ? OR ? = 'no_preference')
    ORDER BY title");
$q->execute([$level, $pref, $pref]);
$diets = $q->fetchAll();

$q = $pdo->prepare("SELECT * FROM workout_templates WHERE is_active = 1 AND access_type = 'free' AND fitness_level = ? ORDER BY title");
$q->execute([$level]);
$workouts = $q->fetchAll();

$q = $pdo->prepare("SELECT d.*, a.notes FROM user_plan_assignments a JOIN diet_plans d ON d.id = a.diet_plan_id
    WHERE a.user_id = ? AND d.access_type = 'free' AND (a.ends_on IS NULL OR a.ends_on >= CURDATE()) ORDER BY a.created_at DESC");
$q->execute([$uid]);
$assignedDiets = $q->fetchAll();

$q = $pdo->prepare("SELECT w.*, a.notes FROM user_plan_assignments a JOIN workout_templates w ON w.id = a.workout_template_id
    WHERE a.user_id = ? AND w.access_type = 'free' AND (a.ends_on IS NULL OR a.ends_on >= CURDATE()) ORDER BY a.created_at DESC");
$q->execute([$uid]);
$assignedWorkouts = $q->fetchAll();

$q = $pdo->prepare('SELECT s.*, u.name AS admin_name FROM admin_suggestions s LEFT JOIN users u ON u.id = s.admin_id WHERE s.user_id = ? ORDER BY s.created_at DESC');
$q->execute([$uid]);
$suggestions = $q->fetchAll();

$assignedDietIds = array_column($assignedDiets, 'id');
$assignedWorkoutIds = array_column($assignedWorkouts, 'id');

$pageTitle = 'Free plans';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">INCLUDED WITH YOUR GYM MEMBERSHIP</p>
        <h1>Free diet &amp; workout plans</h1>
        <p>Plans matched to your <?= e(ucfirst($level)) ?> level.
            <?php if ($isPremium): ?>Your Premium plans are under <a href="premium-plans.php">Premium Subscription → My Plans</a>.
            <?php else: ?>Want more? <a href="subscription.php">Upgrade to Premium</a> for premium plans and demo videos.<?php endif; ?>
        </p>
    </div>
</section>

<?php if ($suggestions): ?>
<section class="panel">
    <h2>Suggestions from your trainer</h2>
    <?php foreach ($suggestions as $s): ?>
        <article class="suggestion-card">
            <h3><?= e($s['title']) ?></h3>
            <p><?= nl2br(e($s['suggestion'])) ?></p>
            <small><?= e($s['created_at']) ?></small>
        </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<div class="cols">
    <section class="panel">
        <h2>Diet guidance</h2>
        <?php foreach ($assignedDiets as $p): ?>
            <article class="suggestion-card">
                <span class="badge">Assigned to you</span>
                <h3><?= e($p['title']) ?></h3>
                <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
                <?php if (!empty($p['notes'])): ?><p><em><?= nl2br(e($p['notes'])) ?></em></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php foreach ($diets as $p): if (in_array($p['id'], $assignedDietIds)) continue; ?>
            <article class="suggestion-card">
                <span class="badge">Free</span>
                <h3><?= e($p['title']) ?></h3>
                <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$diets && !$assignedDiets): ?><p>No free diet plans have been published for your level yet.</p><?php endif; ?>
    </section>

    <section class="panel">
        <h2>Workout guidance</h2>
        <?php foreach ($assignedWorkouts as $p): ?>
            <article class="suggestion-card">
                <span class="badge">Assigned to you</span>
                <h3><?= e($p['title']) ?></h3>
                <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                <p><?= nl2br(e($p['schedule_text'] ?? '')) ?></p>
                <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
                <?php if (!empty($p['notes'])): ?><p><em><?= nl2br(e($p['notes'])) ?></em></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php foreach ($workouts as $p): if (in_array($p['id'], $assignedWorkoutIds)) continue; ?>
            <article class="suggestion-card">
                <span class="badge">Free</span>
                <h3><?= e($p['title']) ?></h3>
                <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                <p><?= nl2br(e($p['schedule_text'] ?? '')) ?></p>
                <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$workouts && !$assignedWorkouts): ?><p>No free workout plans have been published for your level yet.</p><?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
