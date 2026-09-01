<?php
$doctorBase = '../';
$activeNav = 'posters';
$pageTitle = 'Posters';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'imagesmode';
$moduleTitle = 'Posters';
$moduleDescription = 'Browse published posters. Creating and editing posters is limited to admins.';
$moduleFeatures = [
    'View all published posters',
    'Download posters for printing or sharing',
    'Read-only — no editor access',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
