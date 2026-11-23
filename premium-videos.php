<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/videos.php';
requireRole('user');
requireGymMembership();
ensurePremiumVideosTable($pdo);

$uid = (int) $_SESSION['user_id'];
$isPremium = hasActivePremium($pdo, $uid);

$cat = $_GET['cat'] ?? 'all';
if (!in_array($cat, ['all', 'workout', 'diet', 'general'], true)) $cat = 'all';

$videos = [];
if ($isPremium) {
    $q = $pdo->prepare('SELECT fitness_level FROM users WHERE id = ?');
    $q->execute([$uid]);
    $level = $q->fetchColumn();
    $level = $level === 'advanced' ? 'expert' : $level;

    $sql = "SELECT * FROM premium_videos WHERE is_active = 1 AND (fitness_level = 'all' OR fitness_level = ?)";
    $params = [$level];
    if ($cat !== 'all') { $sql .= ' AND category = ?'; $params[] = $cat; }
    $q = $pdo->prepare($sql . ' ORDER BY created_at DESC');
    $q->execute($params);
    $videos = $q->fetchAll();
}

$pageTitle = 'Demo videos';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">PREMIUM SUBSCRIPTION</p>
        <h1>Demo videos</h1>
        <p>Watch exercise and nutrition demos added by your trainers.</p>
    </div>
</section>

<?php if (!$isPremium): ?>
    <section class="panel premium-lock">
        <h2>🔒 Premium members only</h2>
        <p>Demo videos unlock after your Premium subscription is approved by the admin.</p>
        <a class="button" href="subscription.php">Go to Premium Subscription</a>
    </section>
<?php else: ?>
    <div class="filter-row">
        <?php foreach (['all' => 'All', 'workout' => 'Workout', 'diet' => 'Diet', 'general' => 'General'] as $k => $label): ?>
            <a class="<?= $cat === $k ? 'active' : '' ?>" href="premium-videos.php?cat=<?= e($k) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$videos): ?>
        <section class="panel"><p>No videos available in this category yet. Please check back soon.</p></section>
    <?php else: ?>
        <div class="video-grid">
            <?php foreach ($videos as $v): ?>
                <article class="video-card">
                    <div class="video-frame">
                        <iframe src="https://www.youtube-nocookie.com/embed/<?= e($v['youtube_id']) ?>" title="<?= e($v['title']) ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                    <div class="video-body">
                        <h3><?= e($v['title']) ?></h3>
                        <p><span class="badge"><?= e(ucfirst($v['category'])) ?></span></p>
                        <?php if ($v['description']): ?><p><?= nl2br(e($v['description'])) ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
