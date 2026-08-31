<?php
$adminBase = '../';
$activeNav = 'patients';
$pageTitle = 'Patient Registration';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase); // admin only — doctor/nurse/medical_assistant use the /doctor portal
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'person_add';
$moduleTitle = 'Patient Registration';
$moduleDescription = 'Register new patients and manage their core details, feeding into triage, screening, and family info.';
$moduleFeatures = [
    'New patient registration form',
    'Auto-generated registration numbers',
    'Edit and search existing patient records',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
