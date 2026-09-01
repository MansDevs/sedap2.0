<?php
/**
 * Every doctor-portal page must set $doctorBase before requiring this file:
 *   doctor/dashboard.php           -> $doctorBase = '';
 *   doctor/<module>/index.php      -> $doctorBase = '../';
 * This lets redirects and links resolve correctly regardless of depth.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $doctorBase . "../auth/login.php");
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
    header("Location: " . $doctorBase . "../auth/login.php");
    exit();
}

// This portal is for Doctor / Nurse / Medical Assistant only. Send anyone
// else to where they belong instead of letting them view this portal.
if (!in_array($currentUser['role'], ['doctor', 'nurse', 'medical_assistant'], true)) {
    if ($currentUser['role'] === 'admin') {
        header("Location: " . $doctorBase . "../admin/dashboard.php");
    } else {
        header("Location: " . $doctorBase . "../dashboard/dashboard.php");
    }
    exit();
}
