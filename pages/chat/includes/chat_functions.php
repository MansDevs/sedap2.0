<?php
/**
 * Chat helper functions.
 * Every function expects a live PDO instance (see config/db.php).
 */

/**
 * Find an existing 1-to-1 conversation between two users, or create one.
 * Returns the conversation id either way.
 */
function getOrCreateDirectConversation(PDO $pdo, int $userId, int $otherUserId): int
{
    $stmt = $pdo->prepare("
        SELECT cp1.conversation_id
        FROM conversation_participants cp1
        INNER JOIN conversation_participants cp2
            ON cp1.conversation_id = cp2.conversation_id
        INNER JOIN conversations c
            ON c.id = cp1.conversation_id
        WHERE c.type = 'direct'
          AND cp1.user_id = ?
          AND cp2.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $otherUserId]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        return (int) $existing;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO conversations (type, created_by) VALUES ('direct', ?)");
        $stmt->execute([$userId]);
        $conversationId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
        $stmt->execute([$conversationId, $userId]);
        $stmt->execute([$conversationId, $otherUserId]);

        $pdo->commit();
        return $conversationId;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Is this user an active (not-left) participant of this conversation?
 * Always check this before showing/writing to a conversation.
 */
function isConversationParticipant(PDO $pdo, int $conversationId, int $userId): bool
{
    $stmt = $pdo->prepare("
        SELECT 1 FROM conversation_participants
        WHERE conversation_id = ? AND user_id = ? AND left_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $userId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * All conversations for a user (inbox list), newest activity first,
 * with last-message preview and unread count.
 */
function getUserConversations(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT
            c.id AS conversation_id,
            c.type,
            c.name AS group_name,
            other.id AS other_user_id,
            other.name AS other_user_name,
            lm.content AS last_message,
            lm.created_at AS last_message_at,
            lm.sender_id AS last_message_sender_id,
            (
                SELECT COUNT(*) FROM messages m
                WHERE m.conversation_id = c.id
                  AND m.deleted_at IS NULL
                  AND m.sender_id != ?
                  AND m.id > COALESCE(cp.last_read_message_id, 0)
            ) AS unread_count
        FROM conversation_participants cp
        INNER JOIN conversations c ON c.id = cp.conversation_id
        LEFT JOIN conversation_participants other_cp
            ON other_cp.conversation_id = c.id
            AND other_cp.user_id != ?
            AND c.type = 'direct'
        LEFT JOIN users other ON other.id = other_cp.user_id
        LEFT JOIN messages lm ON lm.id = (
            SELECT id FROM messages
            WHERE conversation_id = c.id AND deleted_at IS NULL
            ORDER BY id DESC LIMIT 1
        )
        WHERE cp.user_id = ? AND cp.left_at IS NULL
        ORDER BY COALESCE(lm.created_at, c.created_at) DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

/**
 * Messages in a conversation, oldest first.
 * Pass $afterId to fetch only new messages since that id (used by polling).
 */
function getConversationMessages(PDO $pdo, int $conversationId, ?int $afterId = null, int $limit = 100): array
{
    if ($afterId !== null) {
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, u.name AS sender_name, m.content, m.created_at, m.deleted_at
            FROM messages m
            INNER JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.id ASC
        ");
        $stmt->execute([$conversationId, $afterId]);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, u.name AS sender_name, m.content, m.created_at, m.deleted_at
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $conversationId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_reverse($stmt->fetchAll());
}

/**
 * Insert a message, stamp 'sent' status for every other participant
 * (this is what powers per-user read receipts later), and mark it
 * already-read for the sender.
 */
function sendMessage(PDO $pdo, int $conversationId, int $senderId, string $content): array
{
    $content = trim($content);
    if ($content === '') {
        throw new InvalidArgumentException('Message content cannot be empty.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$conversationId, $senderId, $content]);
        $messageId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            SELECT user_id FROM conversation_participants
            WHERE conversation_id = ? AND user_id != ? AND left_at IS NULL
        ");
        $stmt->execute([$conversationId, $senderId]);
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $statusStmt = $pdo->prepare("INSERT INTO message_status (message_id, user_id, status) VALUES (?, ?, 'sent')");
        foreach ($recipients as $recipientId) {
            $statusStmt->execute([$messageId, $recipientId]);
        }

        $pdo->prepare("
            UPDATE conversation_participants
            SET last_read_message_id = ?
            WHERE conversation_id = ? AND user_id = ?
        ")->execute([$messageId, $conversationId, $senderId]);

        $pdo->commit();

        return [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    } catch (\PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Mark every message in a conversation as read by this user.
 */
function markConversationRead(PDO $pdo, int $conversationId, int $userId): void
{
    $stmt = $pdo->prepare("SELECT MAX(id) FROM messages WHERE conversation_id = ?");
    $stmt->execute([$conversationId]);
    $latestId = $stmt->fetchColumn();

    if (!$latestId) {
        return;
    }

    $pdo->prepare("
        UPDATE conversation_participants
        SET last_read_message_id = ?
        WHERE conversation_id = ? AND user_id = ?
    ")->execute([$latestId, $conversationId, $userId]);

    $pdo->prepare("
        UPDATE message_status ms
        INNER JOIN messages m ON m.id = ms.message_id
        SET ms.status = 'read'
        WHERE m.conversation_id = ? AND ms.user_id = ? AND ms.status != 'read'
    ")->execute([$conversationId, $userId]);
}

/**
 * Display name for the "other side" of a conversation header.
 * For direct chats: the other participant's name. For groups: the group name.
 */
function getConversationTitle(PDO $pdo, int $conversationId, int $currentUserId): string
{
    $stmt = $pdo->prepare("SELECT type, name FROM conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    $conversation = $stmt->fetch();

    if (!$conversation) {
        return 'Chat';
    }

    if ($conversation['type'] === 'group') {
        return $conversation['name'] ?: 'Group Chat';
    }

    $stmt = $pdo->prepare("
        SELECT u.name FROM conversation_participants cp
        INNER JOIN users u ON u.id = cp.user_id
        WHERE cp.conversation_id = ? AND cp.user_id != ?
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $currentUserId]);
    $name = $stmt->fetchColumn();

    return $name ?: 'Chat';
}

/**
 * All other users available to start a new chat with.
 */
function getAllOtherUsers(PDO $pdo, int $currentUserId): array
{
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id != ? ORDER BY name ASC");
    $stmt->execute([$currentUserId]);
    return $stmt->fetchAll();
}
