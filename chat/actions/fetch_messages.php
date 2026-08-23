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

$userId = (int) $_SESSION['user_id'];
$conversationId = (int) ($_GET['conversation_id'] ?? 0);
$afterId = isset($_GET['after_id']) ? (int) $_GET['after_id'] : null;

if ($conversationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing conversation_id.']);
    exit();
}

if (!isConversationParticipant($pdo, $conversationId, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You are not part of this conversation.']);
    exit();
}

try {
    $messages = getConversationMessages($pdo, $conversationId, $afterId);

    // Any messages we're handing back are effectively delivered/seen now.
    if (!empty($messages)) {
        markConversationRead($pdo, $conversationId, $userId);
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'current_user_id' => $userId,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not fetch messages.']);
}
