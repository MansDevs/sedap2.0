<?php
/**
 * Dark mode persistence endpoint
 * POST: { "dark_mode": 1|0 }
 */
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['dark_mode'])) {
    $_SESSION['dark_mode'] = (bool)$input['dark_mode'];

    // Optionally persist to DB
    if (!empty($_SESSION['user_id'])) {
        try {
            require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
            $pdo->prepare("UPDATE users SET dark_mode=? WHERE id=?")
                ->execute([(int)$_SESSION['dark_mode'], $_SESSION['user_id']]);
        } catch (Exception $e) {
            // silently fail if DB column doesn't exist yet
        }
    }
    echo json_encode(['ok' => true]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
