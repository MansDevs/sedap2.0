<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../includes/health_functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if (!in_array($role, ['doctor', 'nurse', 'medical_assistant'], true)) {
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
$type = $_POST['type'] ?? '';

if ($patientId <= 0 || !getPatientById($pdo, $patientId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid patient.']);
    exit();
}

try {
    switch ($type) {
        case 'bristol':
            $scaleType = (int) ($_POST['scale_type'] ?? 0);
            if ($scaleType < 1 || $scaleType > 7) {
                throw new InvalidArgumentException('Bristol scale type must be between 1 and 7.');
            }
            $notes = trim($_POST['notes'] ?? '') ?: null;
            $id = addBristolLog($pdo, $patientId, $scaleType, $notes);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'water':
            $amountMl = (int) ($_POST['amount_ml'] ?? 0);
            if ($amountMl <= 0 || $amountMl > 5000) {
                throw new InvalidArgumentException('Enter a water amount between 1 and 5000 ml.');
            }
            $id = addWaterLog($pdo, $patientId, $amountMl);
            echo json_encode([
                'success' => true,
                'id' => $id,
                'total_today_ml' => getWaterTotalToday($pdo, $patientId),
            ]);
            break;

        case 'mood':
            $mood = $_POST['mood'] ?? '';
            $note = trim($_POST['note'] ?? '') ?: null;
            $id = addMoodEntry($pdo, $patientId, $mood, $note);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'medicine':
            $name = trim($_POST['medicine_name'] ?? '');
            if ($name === '') {
                throw new InvalidArgumentException('Medicine name is required.');
            }
            $id = addMedicine(
                $pdo,
                $patientId,
                $name,
                trim($_POST['dosage'] ?? '') ?: null,
                trim($_POST['frequency'] ?? '') ?: null,
                trim($_POST['start_date'] ?? '') ?: null,
                trim($_POST['end_date'] ?? '') ?: null,
                trim($_POST['notes'] ?? '') ?: null
            );
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown type.']);
    }
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save entry.']);
}
