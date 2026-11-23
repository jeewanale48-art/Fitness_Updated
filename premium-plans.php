<?php
require_once __DIR__ . '/includes/auth.php';
requireRole('user');
requireGymMembership();

$uid = (int) $_SESSION['user_id'];
$isPremium = hasActivePremium($pdo, $uid);

$diets = $workouts = $assignedDiets = $assignedWorkouts = [];
$level = 'beginner';

if ($isPremium) {
    $q = $pdo->prepare('SELECT fitness_level, dietary_preference FROM users WHERE id = ?');
    $q->execute([$uid]);
    $u = $q->fetch();
    $level = $u['fitness_level'] === 'advanced' ? 'expert' : $u['fitness_level'];
    $pref = $u['dietary_preference'] ?: 'no_preference';

    // Premium-only content matched to the member's level (and diet preference for diet plans)
    $q = $pdo->prepare("SELECT * FROM diet_plans
        WHERE is_active = 1 AND access_type = 'premium' AND fitness_level = ?
          AND (dietary_preference IS NULL OR dietary_preference = 'no_preference' OR dietary_preference = ? OR ? = 'no_preference')
        ORDER BY title");
    $q->execute([$level, $pref, $pref]);
    $diets = $q->fetchAll();

    $q = $pdo->prepare("SELECT * FROM workout_templates
        WHERE is_active = 1 AND access_type = 'premium' AND fitness_level = ?
        ORDER BY title");
    $q->execute([$level]);
    $workouts = $q->fetchAll();

    // Premium plans the admin assigned personally to this member
    $q = $pdo->prepare("SELECT d.*, a.notes FROM user_plan_assignments a JOIN diet_plans d ON d.id = a.diet_plan_id
        WHERE a.user_id = ? AND d.access_type = 'premium' AND (a.ends_on IS NULL OR a.ends_on >= CURDATE())
        ORDER BY a.created_at DESC");
    $q->execute([$uid]);
    $assignedDiets = $q->fetchAll();

    $q = $pdo->prepare("SELECT w.*, a.notes FROM user_plan_assignments a JOIN workout_templates w ON w.id = a.workout_template_id
        WHERE a.user_id = ? AND w.access_type = 'premium' AND (a.ends_on IS NULL OR a.ends_on >= CURDATE())
        ORDER BY a.created_at DESC");
    $q->execute([$uid]);
    $assignedWorkouts = $q->fetchAll();
}

$pageTitle = 'My plans';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">PREMIUM SUBSCRIPTION</p>
        <h1>My premium plans</h1>
        <p>Premium diet and workout plans<?= $isPremium ? ' for your ' . e(ucfirst($level)) . ' level' : '' ?>.</p>
    </div>
    <?php if ($isPremium): ?><span class="badge">Premium access</span><?php endif; ?>
</section>

<?php if (!$isPremium): ?>
    <section class="panel premium-lock">
        <h2>🔒 Premium members only</h2>
        <p>Your premium diet and workout plans appear here once your Premium subscription is approved.</p>
        <a class="button" href="subscription.php">Go to Premium Subscription</a>
    </section>
<?php else: ?>
    <div class="cols">
        <section class="panel">
            <h2>Premium diet plans</h2>
            <?php foreach ($assignedDiets as $p): ?>
                <article class="suggestion-card">
                    <span class="badge">Assigned to you</span>
                    <h3><?= e($p['title']) ?></h3>
                    <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                    <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
                    <?php if (!empty($p['notes'])): ?><p><em><?= nl2br(e($p['notes'])) ?></em></p><?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php foreach ($diets as $p): ?>
                <?php if (in_array($p['id'], array_column($assignedDiets, 'id'))) continue; ?>
                <article class="suggestion-card">
                    <span class="badge">Premium</span>
                    <h3><?= e($p['title']) ?></h3>
                    <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                    <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if (!$diets && !$assignedDiets): ?><p>No premium diet plans have been published for your level yet.</p><?php endif; ?>
        </section>

        <section class="panel">
            <h2>Premium workout plans</h2>
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
            <?php foreach ($workouts as $p): ?>
                <?php if (in_array($p['id'], array_column($assignedWorkouts, 'id'))) continue; ?>
                <article class="suggestion-card">
                    <span class="badge">Premium</span>
                    <h3><?= e($p['title']) ?></h3>
                    <p><?= nl2br(e($p['description'] ?? '')) ?></p>
                    <p><?= nl2br(e($p['schedule_text'] ?? '')) ?></p>
                    <p><?= nl2br(e($p['guidance'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if (!$workouts && !$assignedWorkouts): ?><p>No premium workout plans have been published for your level yet.</p><?php endif; ?>
        </section>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
