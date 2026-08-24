<?php
/**
 * Every admin page must set $adminBase before requiring this file:
 *   admin/dashboard.php           -> $adminBase = '';
 *   admin/<module>/index.php      -> $adminBase = '../';
 * This lets redirects and links resolve correctly regardless of depth.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $adminBase . "../auth/login.php");
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
    header("Location: " . $adminBase . "../auth/login.php");
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

// Real unread chat count, used by the notification bell in the topbar.
// Wrapped in try/catch: if chat_schema.sql hasn't been imported yet on
// this install, the panel should still load instead of fatal-erroring.
$unreadChatCount = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM messages m
        INNER JOIN conversation_participants cp
            ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
        WHERE m.sender_id != ?
          AND m.deleted_at IS NULL
          AND m.id > COALESCE(cp.last_read_message_id, 0)
          AND cp.left_at IS NULL
    ");
    $stmt->execute([$currentUserId, $currentUserId]);
    $unreadChatCount = (int) $stmt->fetchColumn();
} catch (\PDOException $e) {
    // Chat tables not present — leave count at 0.
}
