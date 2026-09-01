<?php
/**
 * Real-Time Chat API Endpoint for Doctor & Patient Portals
 * Accurate Read Receipts, Dynamic Unread Badges, Photo Uploads & Message Deletion
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'] ?? 'doctor';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to calculate initials
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
            if (mb_strlen($initials) >= 2) break;
        }
    }
    return $initials ?: 'U';
}

if ($action === 'get_unread_total') {
    if (in_array($currentUserRole, ['doctor', 'admin'])) {
        // Count number of unique people (patients) who sent unread messages
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.sender_id) as total_unread
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ?
            WHERE u.role = 'user'
              AND m.id > IFNULL(cp.last_read_message_id, 0)
              AND m.deleted_at IS NULL
        ");
        $stmt->execute([$currentUserId]);
        $totalUnread = (int)$stmt->fetchColumn();
    } else {
        // Patient: count unique medical staff senders who sent unread messages
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.sender_id) as total_unread
            FROM messages m
            JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ?
            WHERE m.sender_id != ?
              AND m.id > IFNULL(cp.last_read_message_id, 0)
              AND m.deleted_at IS NULL
        ");
        $stmt->execute([$currentUserId, $currentUserId]);
        $totalUnread = (int)$stmt->fetchColumn();
    }

    echo json_encode(['ok' => true, 'total_unread' => $totalUnread, 'user_role' => $currentUserRole]);
    exit;
}

if ($action === 'get_queue') {
    // Return all users where role = 'user'
    $stmt = $pdo->query("SELECT u.id, u.name, u.email, u.contact_number, u.created_at 
                         FROM users u 
                         WHERE u.role = 'user' AND u.status = 'active' 
                         ORDER BY u.id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $queue = [];
    foreach ($users as $u) {
        $uid = (int)$u['id'];
        
        // Find or create direct conversation
        $convStmt = $pdo->prepare("SELECT c.id FROM conversations c 
                                  JOIN conversation_participants cp ON c.id=cp.conversation_id 
                                  WHERE cp.user_id=? LIMIT 1");
        $convStmt->execute([$uid]);
        $convId = $convStmt->fetchColumn();

        if (!$convId) {
            $pdo->prepare("INSERT INTO conversations (type, created_by, created_at) VALUES ('direct', ?, NOW())")->execute([$uid]);
            $convId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $uid]);
            $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $currentUserId]);
        } else {
            // Ensure current doctor is a participant
            $chkPart = $pdo->prepare("SELECT id FROM conversation_participants WHERE conversation_id=? AND user_id=?");
            $chkPart->execute([$convId, $currentUserId]);
            if (!$chkPart->fetchColumn()) {
                $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $currentUserId]);
            }
        }

        // Get latest message
        $lastMsgStmt = $pdo->prepare("SELECT id, content, DATE_FORMAT(created_at, '%h:%i %p') as time, sender_id, deleted_at 
                                      FROM messages 
                                      WHERE conversation_id = ? 
                                      ORDER BY created_at DESC LIMIT 1");
        $lastMsgStmt->execute([$convId]);
        $lastMsg = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);

        // Get doctor's last read message ID for this conversation
        $docLastReadStmt = $pdo->prepare("SELECT last_read_message_id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
        $docLastReadStmt->execute([$convId, $currentUserId]);
        $lastReadId = (int)($docLastReadStmt->fetchColumn() ?: 0);

        // Count ONLY active messages sent by the other party that are newer than last_read_message_id
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages 
                                      WHERE conversation_id = ? 
                                        AND sender_id != ? 
                                        AND id > ? 
                                        AND deleted_at IS NULL");
        $unreadStmt->execute([$convId, $currentUserId, $lastReadId]);
        $unreadCount = (int)$unreadStmt->fetchColumn();

        // Check priority / room alert
        $isPriority = (stripos($u['name'], 'Priority') !== false || stripos($u['name'], 'Room 4') !== false);

        // Get initials
        $initials = $isPriority ? '!' : getInitials($u['name']);

        // Color coding
        $avatarClass = 'chat-avatar-em';
        if ($isPriority) {
            $avatarClass = 'chat-avatar-priority';
        } elseif ($uid % 3 === 1) {
            $avatarClass = 'chat-avatar-sr';
        } elseif ($uid % 3 === 2) {
            $avatarClass = 'chat-avatar-jd';
        }

        // Strip [img] and [audio] for snippet preview
        $snippet = 'Session connected. Awaiting chat.';
        if ($lastMsg) {
            if (!empty($lastMsg['deleted_at'])) {
                $snippet = ' Deleted message';
            } elseif (stripos($lastMsg['content'], '[audio]') !== false) {
                $snippet = ' [Mesej Suara / Voice Note]';
            } elseif (stripos($lastMsg['content'], '[img]') !== false) {
                $snippet = ' [Foto / Photo Attachment]';
            } else {
                $snippet = $lastMsg['content'];
            }
        }

        $queue[] = [
            'key' => 'user_' . $uid,
            'user_id' => $uid,
            'conversation_id' => (int)$convId,
            'name' => $u['name'],
            'initials' => $initials,
            'avatar_class' => $avatarClass,
            'time' => $lastMsg ? $lastMsg['time'] : date('h:i A', strtotime($u['created_at'])),
            'snippet' => $snippet,
            'unread' => $unreadCount,
            'priority' => $isPriority,
            'patient_id' => '#' . str_pad($uid + 84900, 5, '0', STR_PAD_LEFT),
            'phone' => $u['contact_number'] ?: '—',
            'email' => $u['email'] ?: '—'
        ];
    }

    echo json_encode(['ok' => true, 'queue' => $queue]);
    exit;
}

if ($action === 'get_messages') {
    $convId = (int)($_GET['conversation_id'] ?? 0);
    if (!$convId) {
        echo json_encode(['ok' => false, 'error' => 'Missing conversation_id']);
        exit;
    }

    // Mark all messages as read by updating last_read_message_id
    $maxMsgIdStmt = $pdo->prepare("SELECT MAX(id) FROM messages WHERE conversation_id = ? AND deleted_at IS NULL");
    $maxMsgIdStmt->execute([$convId]);
    $maxMsgId = (int)$maxMsgIdStmt->fetchColumn();

    if ($maxMsgId > 0) {
        $upd = $pdo->prepare("UPDATE conversation_participants 
                              SET last_read_message_id = ? 
                              WHERE conversation_id = ? AND user_id = ?");
        $upd->execute([$maxMsgId, $convId, $currentUserId]);
    }

    $stmt = $pdo->prepare("SELECT m.id, m.conversation_id, m.sender_id, m.content, m.deleted_at,
                                  DATE_FORMAT(m.created_at, '%h:%i %p') as time,
                                  u.name as sender_name, u.role as sender_role
                           FROM messages m
                           JOIN users u ON m.sender_id = u.id
                           WHERE m.conversation_id = ?
                           ORDER BY m.created_at ASC");
    $stmt->execute([$convId]);
    $rawMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($rawMessages as $m) {
        $isDeleted = !empty($m['deleted_at']);
        $content = $m['content'];
        $hasImage = stripos($content, '[img]') !== false;
        $hasAudio = stripos($content, '[audio]') !== false;

        if ($isDeleted) {
            if ($hasImage) {
                $content = ' Deleted Content';
            } elseif ($hasAudio) {
                $content = ' Deleted Voice Note';
            } else {
                $content = ' Deleted message';
            }
        }

        $messages[] = [
            'id' => (int)$m['id'],
            'conversation_id' => (int)$m['conversation_id'],
            'sender_id' => (int)$m['sender_id'],
            'content' => $content,
            'raw_content' => $isDeleted ? '' : $m['content'],
            'is_deleted' => $isDeleted,
            'time' => $m['time'],
            'sender_name' => $m['sender_name'],
            'sender_role' => $m['sender_role'],
            'can_delete' => ($m['sender_id'] == $currentUserId || in_array($currentUserRole, ['doctor', 'admin']))
        ];
    }

    echo json_encode(['ok' => true, 'messages' => $messages, 'current_user_id' => $currentUserId]);
    exit;
}

if ($action === 'delete_message') {
    $msgId = (int)($_POST['message_id'] ?? 0);
    if (!$msgId) {
        echo json_encode(['ok' => false, 'error' => 'Missing message_id']);
        exit;
    }

    $chk = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
    $chk->execute([$msgId]);
    $senderId = $chk->fetchColumn();

    if (!$senderId) {
        echo json_encode(['ok' => false, 'error' => 'Message not found']);
        exit;
    }

    if ($senderId != $currentUserId && !in_array($currentUserRole, ['doctor', 'admin'])) {
        echo json_encode(['ok' => false, 'error' => 'Unauthorized to delete this message']);
        exit;
    }

    $upd = $pdo->prepare("UPDATE messages SET deleted_at = NOW() WHERE id = ?");
    $upd->execute([$msgId]);

    echo json_encode(['ok' => true, 'message_id' => $msgId]);
    exit;
}

if ($action === 'upload_photo' || (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
    $convId = (int)($_POST['conversation_id'] ?? 0);
    $caption = trim($_POST['caption'] ?? $_POST['content'] ?? '');

    if (!$convId) {
        echo json_encode(['ok' => false, 'error' => 'Missing conversation_id']);
        exit;
    }

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Photo upload failed']);
        exit;
    }

    $file = $_FILES['photo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid file format. Please upload JPG, PNG, or WEBP.']);
        exit;
    }

    // Save directory
    $uploadDir = __DIR__ . '/../../../uploads/chat/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $newFileName = 'chat_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['ok' => false, 'error' => 'Failed to save uploaded photo']);
        exit;
    }

    $photoUrl = '<?= $_ROOT ?>/uploads/chat/' . $newFileName;
    $content = '[img]' . $photoUrl . '[/img]';
    if (!empty($caption)) {
        $content = $caption . "\n" . $content;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$convId, $currentUserId, $content]);
    $msgId = (int)$pdo->lastInsertId();

    // Update sender's own last_read_message_id
    $upd = $pdo->prepare("UPDATE conversation_participants 
                          SET last_read_message_id = ? 
                          WHERE conversation_id = ? AND user_id = ?");
    $upd->execute([$msgId, $convId, $currentUserId]);

    $timeStr = date('h:i A');

    echo json_encode([
        'ok' => true,
        'message' => [
            'id' => $msgId,
            'conversation_id' => $convId,
            'sender_id' => $currentUserId,
            'content' => $content,
            'time' => $timeStr,
            'sender_name' => $_SESSION['user_name'] ?? 'User',
            'sender_role' => $currentUserRole
        ]
    ]);
    exit;
}

if ($action === 'upload_voice' || (isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] === UPLOAD_ERR_OK)) {
    $convId = (int)($_POST['conversation_id'] ?? 0);

    if (!$convId) {
        echo json_encode(['ok' => false, 'error' => 'Missing conversation_id']);
        exit;
    }

    if (!isset($_FILES['voice_note']) || $_FILES['voice_note']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Voice recording upload failed']);
        exit;
    }

    $file = $_FILES['voice_note'];
    $origName = $file['name'] ?? 'voice.webm';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!$ext || !in_array($ext, ['webm', 'ogg', 'mp3', 'wav', 'm4a', 'mp4', 'aac'])) {
        $ext = 'webm';
    }

    // Save directory
    $uploadDir = __DIR__ . '/../../../uploads/chat_audio/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $newFileName = 'voice_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['ok' => false, 'error' => 'Failed to save voice note on server']);
        exit;
    }

    $audioUrl = '<?= $_ROOT ?>/uploads/chat_audio/' . $newFileName;
    $content = '[audio]' . $audioUrl . '[/audio]';

    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$convId, $currentUserId, $content]);
    $msgId = (int)$pdo->lastInsertId();

    // Update sender's own last_read_message_id
    $upd = $pdo->prepare("UPDATE conversation_participants 
                          SET last_read_message_id = ? 
                          WHERE conversation_id = ? AND user_id = ?");
    $upd->execute([$msgId, $convId, $currentUserId]);

    $timeStr = date('h:i A');

    echo json_encode([
        'ok' => true,
        'message' => [
            'id' => $msgId,
            'conversation_id' => $convId,
            'sender_id' => $currentUserId,
            'content' => $content,
            'time' => $timeStr,
            'sender_name' => $_SESSION['user_name'] ?? 'User',
            'sender_role' => $currentUserRole
        ]
    ]);
    exit;
}

if ($action === 'send_message') {
    $convId = (int)($_POST['conversation_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (!$convId || empty($content)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid parameters']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$convId, $currentUserId, $content]);
    $msgId = (int)$pdo->lastInsertId();

    // Update doctor's own last_read_message_id
    $upd = $pdo->prepare("UPDATE conversation_participants 
                          SET last_read_message_id = ? 
                          WHERE conversation_id = ? AND user_id = ?");
    $upd->execute([$msgId, $convId, $currentUserId]);

    $timeStr = date('h:i A');

    echo json_encode([
        'ok' => true,
        'message' => [
            'id' => $msgId,
            'conversation_id' => $convId,
            'sender_id' => $currentUserId,
            'content' => $content,
            'time' => $timeStr,
            'sender_name' => $_SESSION['user_name'] ?? 'Doctor',
            'sender_role' => $currentUserRole
        ]
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid action']);
