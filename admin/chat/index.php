<?php
$adminBase = '../';
$activeNav = 'chat';
$pageTitle = 'Live Chat';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase);
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'chat';
$moduleTitle = 'Live Chat';
$moduleDescription = 'Real-time messaging between healthcare providers, volunteers, staff, and patients.';
$moduleFeatures = [
    'Direct real-time 1-on-1 messaging',
    'Care team group discussions',
    'Voice note recordings & file attachments',
    'Unread message badges and instant push alerts',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
