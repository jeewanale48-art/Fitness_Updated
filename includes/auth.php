<?php
declare(strict_types=1);
require_once __DIR__ . '/init.php';
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) { flash('Please sign in to continue.', 'error'); redirect('login.php'); }
}
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        $inAdmin = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
        if (($_SESSION['role'] ?? '') === 'admin') redirect($inAdmin ? 'dashboard.php' : 'admin/dashboard.php');
        redirect($inAdmin ? '../dashboard.php' : 'dashboard.php');
    }
}
function requireAdmin(): void { requireRole('admin'); }
function signInUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_name']=$user['name'];
    $_SESSION['user_email']=$user['email']; $_SESSION['role']=$user['role'];
}

/**
 * Gym-fee gate: a normal member only gets the rest of the system once an admin
 * has confirmed a gym membership payment (status = active) that has not expired.
 */
function hasActiveGymMembership(PDO $pdo, int $userId): bool {
    static $cache = [];
    if (isset($cache[$userId])) return $cache[$userId];
    $q = $pdo->prepare("SELECT 1 FROM gym_memberships WHERE user_id = ? AND status = 'active' AND payment_status = 'paid' AND end_date >= CURDATE() LIMIT 1");
    $q->execute([$userId]);
    return $cache[$userId] = (bool) $q->fetchColumn();
}
function requireGymMembership(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'user') return; // admins are never gated
    global $pdo;
    if (!hasActiveGymMembership($pdo, (int) $_SESSION['user_id'])) {
        flash('Please apply for a gym membership first. Other sections unlock after the admin approves your fee.', 'error');
        redirect('membership.php');
    }
}

/** True when the member has an approved Premium subscription that has not expired. */
function hasActivePremium(PDO $pdo, int $userId): bool {
    static $cache = [];
    if (isset($cache[$userId])) return $cache[$userId];
    $q = $pdo->prepare("SELECT 1 FROM users WHERE id = ? AND subscription_status = 'active' AND subscription_expires_at >= CURDATE() LIMIT 1");
    $q->execute([$userId]);
    return $cache[$userId] = (bool) $q->fetchColumn();
}
