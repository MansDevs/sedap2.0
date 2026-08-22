<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/chat_functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$conversationId = (int) ($_POST['conversation_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($conversationId <= 0 || $content === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing conversation_id or content.']);
    exit();
}

if (!isConversationParticipant($pdo, $conversationId, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You are not part of this conversation.']);
    exit();
}

try {
    $message = sendMessage($pdo, $conversationId, $userId, $content);
    echo json_encode(['success' => true, 'message' => $message]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not send message.']);
}
