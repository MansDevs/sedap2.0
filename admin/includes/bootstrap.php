<?php
/**
 * Every admin page must set $adminBase before requiring this file:
 *   admin/dashboard.php           -> $adminBase = '';
 *   admin/<module>/index.php      -> $adminBase = '../';
 * This lets redirects and links resolve correctly regardless of depth.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /sedap2.0/pages/auth/login.php");
    exit();
}

require_once __DIR__ . '/../../config/db.php';

$currentUserId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, email, role, avatar_url, dark_mode FROM users WHERE id = ?");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    // Session points to a user that no longer exists
    session_destroy();
    header("Location: /sedap2.0/pages/auth/login.php");
    exit();
}

// This portal is admin-only. Send other roles to where they belong instead
// of letting them view (even read-only) an admin URL.
if ($currentUser['role'] !== 'admin') {
    if (in_array($currentUser['role'], ['doctor', 'nurse', 'medical_assistant'], true)) {
        header("Location: " . $adminBase . "../doctor/dashboard.php");
    } else {
        header("Location: " . $adminBase . "../dashboard/dashboard.php");
    }
    exit();
}
