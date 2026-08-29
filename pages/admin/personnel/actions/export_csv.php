<?php
session_start();

require_once '../../../config/db.php';
require_once '../../../shared/includes/lang.php';
require_once __DIR__ . '/../includes/personnel_functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    exit('Not logged in.');
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int) $_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('Not allowed.');
}

$q = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');

$rows = getFilteredPersonnel($pdo, $q, $type, $status);

$filename = 'personnel_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens accented/Malay characters correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'Type', 'Full Name', 'IC/Passport Number', 'Gender', 'Date of Birth',
    'Phone', 'Email', 'Address', 'Department / Team', 'Skills',
    'Availability Date', 'Emergency Contact Name', 'Emergency Contact Phone',
    'Status', 'Registered At',
]);

foreach ($rows as $row) {
    fputcsv($out, [
        ucfirst($row['type']),
        $row['full_name'],
        $row['ic_number'],
        $row['gender'] ? ucfirst($row['gender']) : '',
        $row['date_of_birth'],
        $row['phone'],
        $row['email'],
        $row['address'],
        $row['department'],
        $row['skills'],
        $row['availability_date'],
        $row['emergency_contact_name'],
        $row['emergency_contact_phone'],
        ucfirst($row['status']),
        $row['created_at'],
    ]);
}

fclose($out);
exit();
