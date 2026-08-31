<?php
/**
 * Sound Notification persistence endpoint
 * POST: { "sound_notification": 1|0 }
 */
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['sound_notification'])) {
    $sound = (bool)$input['sound_notification'];
    $_SESSION['sound_notification'] = $sound;

    if (!empty($_SESSION['user_id'])) {
        try {
            require_once '../../config/db.php';
            $pdo->prepare("UPDATE users SET sound_notification=? WHERE id=?")
                ->execute([(int)$sound, (int)$_SESSION['user_id']]);
        } catch (Exception $e) {
            // Silently fail if database column not found
        }
    }
    echo json_encode(['ok' => true, 'sound_notification' => $sound]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
