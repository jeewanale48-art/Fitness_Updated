<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/videos.php';
requireAdmin();
ensurePremiumVideosTable($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $cat = $_POST['category'] ?? '';
        $level = $_POST['fitness_level'] ?? '';
        $ytId = youtubeIdFromUrl((string) ($_POST['youtube_url'] ?? ''));

        if ($title === '' || mb_strlen($title) > 160) {
            $error = 'Enter a video title (up to 160 characters).';
        } elseif ($ytId === null) {
            $error = 'Paste a valid YouTube link, for example https://www.youtube.com/watch?v=XXXXXXXXXXX or https://youtu.be/XXXXXXXXXXX.';
        } elseif (!in_array($cat, ['workout', 'diet', 'general'], true) || !in_array($level, ['all', 'beginner', 'intermediate', 'expert'], true)) {
            $error = 'Choose a valid category and level.';
        } else {
            $pdo->prepare('INSERT INTO premium_videos(title,description,youtube_id,category,fitness_level,created_by) VALUES(?,?,?,?,?,?)')
                ->execute([$title, $desc ?: null, $ytId, $cat, $level, $_SESSION['user_id']]);
            flash('Video added. Premium members can watch it now.');
            redirect('videos.php');
        }
    }

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE premium_videos SET is_active = 1 - is_active WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        redirect('videos.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM premium_videos WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('Video deleted.');
        redirect('videos.php');
    }
}

$videos = $pdo->query('SELECT * FROM premium_videos ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Demo videos';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">PREMIUM CONTENT</p>
        <h1>Demo videos</h1>
        <p>Add YouTube links. Only members with an active Premium subscription can watch them.</p>
    </div>
</section>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>
<section class="panel">
    <h2>Add a video</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <div class="full">
            <label>YouTube link</label>
            <input name="youtube_url" required placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="full"><label>Title</label> <input name="title" required maxlength="160"></div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="workout">Workout demo</option>
                <option value="diet">Diet / nutrition</option>
                <option value="general">General</option>
            </select>
        </div>
        <div>
            <label>Fitness level</label>
            <select name="fitness_level">
                <option value="all">All levels</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
            </select>
        </div>
        <div class="full">
            <label>Description (optional)</label> <textarea name="description" rows="2" maxlength="1000"></textarea>
        </div>
        <div><button class="button">Add video</button></div>
    </form>
</section>
<section class="panel">
    <h2>Published videos (<?= count($videos) ?>)</h2>
    <?php if (!$videos): ?>
        <p>No videos yet.</p>
    <?php else: ?>
        <div class="video-grid">
            <?php foreach ($videos as $v): ?>
                <article class="video-card">
                    <div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($v['youtube_id']) ?>" title="<?= e($v['title']) ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
                    <div class="video-body">
                        <h3><?= e($v['title']) ?></h3>
                        <p>
                            <span class="badge"><?= e(ucfirst($v['category'])) ?></span>
                            <span class="badge"><?= $v['fitness_level'] === 'all' ? 'All levels' : e(ucfirst($v['fitness_level'])) ?></span>
                            <?= $v['is_active'] ? '' : '<span class="badge badge-pending">Hidden</span>' ?>
                        </p>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                            <button class="btn btn-small" name="action" value="toggle"><?= $v['is_active'] ? 'Hide' : 'Show' ?></button>
                            <button class="btn btn-small" name="action" value="delete" onclick="return confirm('Delete this video?')">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
