<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../includes/access.php';
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

$patientId = (int) ($_GET['patient_id'] ?? 0);
$type = $_GET['type'] ?? '';

if ($patientId <= 0 || !getPatientById($pdo, $patientId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid patient.']);
    exit();
}

switch ($type) {
    case 'bristol':
        echo json_encode(['success' => true, 'logs' => getBristolLogs($pdo, $patientId)]);
        break;

    case 'water':
        echo json_encode([
            'success' => true,
            'logs' => getWaterLogs($pdo, $patientId),
            'total_today_ml' => getWaterTotalToday($pdo, $patientId),
        ]);
        break;

    case 'mood':
        echo json_encode(['success' => true, 'logs' => getMoodEntries($pdo, $patientId)]);
        break;

    case 'medicine':
        echo json_encode(['success' => true, 'medicines' => getMedicines($pdo, $patientId)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown type.']);
}
