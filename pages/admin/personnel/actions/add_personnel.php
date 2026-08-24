<?php
session_start();

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../includes/personnel_functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../../../auth/login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') {
    header("Location: ../../dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

try {
    addPersonnel($pdo, $_POST, (int) $_SESSION['user_id']);
    header("Location: ../index.php?success=1");
} catch (\InvalidArgumentException $e) {
    header("Location: ../index.php?error=" . urlencode($e->getMessage()));
} catch (\Throwable $e) {
    header("Location: ../index.php?error=" . urlencode('Could not save this record. Please try again.'));
}
exit();
