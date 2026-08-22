<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/chat_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$otherUserId = (int) ($_POST['other_user_id'] ?? 0);

if ($otherUserId <= 0 || $otherUserId === $userId) {
    header("Location: ../new.php?error=invalid_user");
    exit();
}

// Make sure the target user actually exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$otherUserId]);
if (!$stmt->fetchColumn()) {
    header("Location: ../new.php?error=invalid_user");
    exit();
}

$conversationId = getOrCreateDirectConversation($pdo, $userId, $otherUserId);

header("Location: ../conversation.php?id=" . $conversationId);
exit();
