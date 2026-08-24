<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../includes/health_functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not allowed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

$patientId = (int) ($_POST['patient_id'] ?? 0);
$medicineId = (int) ($_POST['medicine_id'] ?? 0);
$reminderTime = trim($_POST['reminder_time'] ?? '');
$days = $_POST['days'] ?? []; // array of 1-7 (Mon-Sun)

if (!getPatientById($pdo, $patientId) || !medicineBelongsToPatient($pdo, $medicineId, $patientId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid patient or medicine.']);
    exit();
}

if ($reminderTime === '' || !preg_match('/^\d{2}:\d{2}$/', $reminderTime)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Enter a valid time.']);
    exit();
}

$days = array_filter(array_map('intval', (array) $days), function ($d) {
    return $d >= 1 && $d <= 7;
});

if (empty($days)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Select at least one day.']);
    exit();
}

sort($days);
$daysOfWeek = implode(',', $days);

try {
    $id = addReminder($pdo, $medicineId, $reminderTime . ':00', $daysOfWeek);
    echo json_encode(['success' => true, 'id' => $id]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save reminder.']);
}
