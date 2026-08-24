<?php
$adminBase = '../';
$activeNav = 'screening';
$pageTitle = 'Health Screening';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase); // admin only
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'assignment';
$moduleTitle = 'Patient Online Health Screening';
$moduleDescription = 'Build online screening forms for patients to fill out, then review submitted responses.';
$moduleFeatures = [
    'Form editor: add, edit, and reorder questions',
    'Publish forms for patients to fill in online',
    'Review and export submitted responses',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
