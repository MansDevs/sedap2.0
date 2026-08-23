<?php
$adminBase = '../';
$activeNav = 'triage';
$pageTitle = 'Triage List';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase); // admin only — doctor/nurse/medical_assistant use the /doctor portal
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'emergency';
$moduleTitle = 'Triage Patient List';
$moduleDescription = 'A live-updating triage board sorted by severity, with full vitals and CSV export.';
$moduleFeatures = [
    'Live view sorted by triage color (red / yellow / green / black)',
    'Vitals and chief complaint per patient',
    'Export the triage list to CSV',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
