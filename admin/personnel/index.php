<?php
$adminBase = '../';
$activeNav = 'personnel';
$pageTitle = 'Staff & Volunteers';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase); // admin only
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'groups';
$moduleTitle = 'Staff & Volunteer Registration';
$moduleDescription = 'Register staff and volunteers and keep their details in one roster, ready to export.';
$moduleFeatures = [
    'Registration form for staff and volunteers',
    'Searchable, filterable roster (backed by the `personnel` table)',
    'Export the full list to CSV',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
