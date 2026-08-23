<?php
$doctorBase = '../';
$activeNav = 'announcements';
$pageTitle = 'Announcements';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'campaign';
$moduleTitle = 'Announcements';
$moduleDescription = 'Read the latest published announcements. Editing is limited to admins.';
$moduleFeatures = [
    'View all published announcements',
    'Newest announcements shown first',
    'Read-only — no edit or delete access',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
