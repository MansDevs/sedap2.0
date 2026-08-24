<?php
/**
 * toggle_dark.php — Dark mode AJAX handler
 * POST: dark=1|0
 */
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }
$dark = ($_POST['dark'] ?? '0') === '1' ? 1 : 0;
$stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
$stmt->execute([$dark, $_SESSION['user_id']]);
$_SESSION['dark_mode'] = (bool)$dark;
echo json_encode(['ok' => true]);
