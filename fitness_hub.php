<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
requireRole('user');
requireGymMembership();
$pageTitle = 'Fitness Hub';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">YOUR EVERYDAY FITNESS TOOLS</p>
        <h1>Fitness Hub</h1>
        <p>Manage workouts, nutrition, hydration and progress from one place.</p>
    </div>
</section>
<div class="hub-grid">
    <a class="hub-card" href="workouts.php"><span class="hub-icon">⚒</span>
    <h2>Workout Log</h2>
    <p>Record sessions and review your training history.</p>
    </a> <a class="hub-card" href="workout_plans.php"><span class="hub-icon">▤</span>
    <h2>Workout Plans</h2>
    <p>Browse and manage your workout plans.</p>
    </a> <a class="hub-card" href="exercises.php"><span class="hub-icon">✦</span>
    <h2>Exercise Library</h2>
    <p>Explore exercises and movement guidance.</p>
    </a> <a class="hub-card" href="nutrition.php"><span class="hub-icon">♜</span>
    <h2>Nutrition</h2>
    <p>Record food and review your daily nutrition.</p>
    </a> <a class="hub-card" href="water.php"><span class="hub-icon">♢</span>
    <h2>Water Tracker</h2>
    <p>Track hydration throughout your day.</p>
    </a> <a class="hub-card" href="goals.php"><span class="hub-icon">◎</span>
    <h2>Fitness Goals</h2>
    <p>Set targets and monitor goal progress.</p>
    </a> <a class="hub-card" href="body_measurements.php"><span class="hub-icon">♙</span>
    <h2>Body Progress</h2>
    <p>Record body measurements and track changes.</p>
    </a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
