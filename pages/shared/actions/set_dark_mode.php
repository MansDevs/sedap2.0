<?php
/**
 * Dark mode persistence endpoint
 * POST: { "dark_mode": 1|0 } or form POST dark_mode=1|0
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$darkMode = null;

if (isset($input['dark_mode'])) {
    $darkMode = (int)(bool)$input['dark_mode'];
} elseif (isset($_POST['dark_mode'])) {
    $darkMode = (int)(bool)$_POST['dark_mode'];
} elseif (isset($_POST['dark'])) {
    $darkMode = (int)(bool)$_POST['dark'];
}

if ($darkMode !== null) {
    $_SESSION['dark_mode'] = (int)$darkMode;

    if (!empty($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
            $stmt->execute([$darkMode, (int)$_SESSION['user_id']]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['ok' => true, 'dark_mode' => $darkMode]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing dark_mode parameter']);
}
