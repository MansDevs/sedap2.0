<?php
$doctorBase = '../';
$activeNav = 'family';
$pageTitle = 'Family Information';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'family_restroom';
$moduleTitle = "Patients' Family Information";
$moduleDescription = "Record and manage each patient's family and emergency contacts.";
$moduleFeatures = [
    'Link family members to a patient record',
    'Mark emergency contacts',
    'Export family details to CSV',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
