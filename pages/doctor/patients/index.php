<?php
$doctorBase = '../';
$activeNav = 'patients';
$pageTitle = 'Patient Registration';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'person_add';
$moduleTitle = 'Patient Registration';
$moduleDescription = 'Register new patients and manage their core details.';
$moduleFeatures = [
    'New patient registration form',
    'Auto-generated registration numbers',
    'Edit and search existing patient records',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
