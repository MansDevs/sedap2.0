<?php
/**
 * Language persistence endpoint
 * POST: { "lang": "ms" | "en" }
 */
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$lang = strtolower($input['lang'] ?? '');

if (in_array($lang, ['ms', 'en'], true)) {
    $_SESSION['lang'] = $lang;

    // Persist to DB if user is logged in
    if (!empty($_SESSION['user_id'])) {
        try {
            require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
            $pdo->prepare("UPDATE users SET lang=? WHERE id=?")
                ->execute([$lang, $_SESSION['user_id']]);
        } catch (Exception $e) {
            // fail safe
        }
    }
    echo json_encode(['ok' => true, 'lang' => $lang]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid language choice']);
}
