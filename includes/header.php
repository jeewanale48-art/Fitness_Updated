<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'FitTrack';
$baseUrl = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '';
$assetBase = $baseUrl . 'assets/';
$isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
$isUser = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user';
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= escapeHtml($pageTitle) ?> | FitTrack</title><link rel="stylesheet" href="<?= $assetBase ?>css/style.css"><link rel="stylesheet" href="<?= $assetBase ?>css/style2.css"></head><body>
<?php if ($isUser): ?>
<div class="ft-user-shell"><aside class="ft-sidebar"><a class="ft-brand" href="<?= $baseUrl ?>dashboard.php"><span class="brand-mark">✣</span>FitTrack</a><nav class="ft-nav">
<?php $nav=[['dashboard.php','⌂','Dashboard'],['fitness_hub.php','✦','Fitness Hub'],['membership.php','▣','Gym Membership'],['subscription.php','♛','Premium Subscription'],['coaching.php','♧','Personal Coach'],['notifications.php','♢','Notifications'],['profile.php','♙','Profile'],['progress.php','↗','Progress']]; foreach($nav as [$url,$icon,$label]): ?><a class="<?= $current===$url?'active':'' ?>" href="<?= $baseUrl.$url ?>"><span><?= $icon ?></span><?= $label ?></a><?php endforeach; ?>
</nav><form class="ft-sidebar-logout" method="POST" action="<?= $baseUrl ?>logout.php"><input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>"><button type="submit">↪ Sign out</button></form></aside><div class="ft-main"><header class="ft-topbar"><strong><?= escapeHtml($pageTitle) ?></strong><span class="ft-user-name"><?= escapeHtml($_SESSION['user_name'] ?? 'Member') ?></span></header><main class="ft-content">
<?php elseif ($isAdmin): ?>
<header class="site-header"><div class="wrap header-inner"><a class="brand" href="<?= $baseUrl ?>admin/dashboard.php"><span class="brand-mark">✣</span>FitTrack</a><nav class="small-nav"><a href="<?= $baseUrl ?>admin/dashboard.php">Overview</a><a href="<?= $baseUrl ?>admin/users.php">Users</a><a href="<?= $baseUrl ?>admin/requests.php">Subscriptions</a><a href="<?= $baseUrl ?>admin/plans.php">Plans</a><a href="<?= $baseUrl ?>admin/gym-memberships.php">Gym Fees</a><a href="<?= $baseUrl ?>admin/coaches.php">Coaches</a><a href="<?= $baseUrl ?>admin/bookings.php">Bookings</a><a href="<?= $baseUrl ?>admin/notifications.php">Notifications</a><span class="nav-user"><?= escapeHtml($_SESSION['user_name'] ?? 'Admin') ?></span><form class="logout-form" method="POST" action="<?= $baseUrl ?>logout.php"><input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>"><button class="btn btn-small" type="submit">Logout</button></form></nav></div></header><main class="wrap section legacy-content">
<?php else: ?>
<header class="site-header"><div class="wrap header-inner"><a class="brand" href="<?= $baseUrl ?>index.php"><span class="brand-mark">✣</span>FitTrack</a><nav class="small-nav"><a href="<?= $baseUrl ?>login.php">Login</a><a class="btn btn-small" href="<?= $baseUrl ?>register.php">Register</a></nav></div></header><main class="wrap section legacy-content">
<?php endif; ?>
