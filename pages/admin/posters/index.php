<?php
$adminBase = '../';
$activeNav = 'posters';
$pageTitle = 'Poster Editor';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, ['doctor', 'nurse', 'medical_assistant'], $adminBase);
require_once __DIR__ . '/../includes/header.php';

$isViewer = $currentUser['role'] !== 'admin';

$moduleIcon = 'imagesmode';
$moduleTitle = $isViewer ? 'Posters (View Only)' : 'Poster Editor';

if ($isViewer) {
    $moduleDescription = 'Browse published posters. Creating and editing posters is limited to admins.';
    $moduleFeatures = [
        'View all published posters',
        'Download posters for printing or sharing',
        'Read-only — no editor access',
    ];
} else {
    $moduleDescription = 'Design and publish posters directly in the browser, no external design tool needed.';
    $moduleFeatures = [
        'Drag-and-drop poster editor',
        'Save drafts and publish',
        'Download or share finished posters',
    ];
}

require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
