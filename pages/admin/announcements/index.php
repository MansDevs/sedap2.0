<?php
$adminBase = '../';
$activeNav = 'announcements';
$pageTitle = 'Announcements';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, ['doctor', 'nurse', 'medical_assistant'], $adminBase);
require_once __DIR__ . '/../includes/header.php';

$isViewer = $currentUser['role'] !== 'admin';

$moduleIcon = 'campaign';
$moduleTitle = $isViewer ? 'Announcements (View Only)' : 'Announcements';

if ($isViewer) {
    $moduleDescription = 'Read the latest published announcements. Editing is limited to admins.';
    $moduleFeatures = [
        'View all published announcements',
        'Newest announcements shown first',
        'Read-only — no edit or delete access',
    ];
} else {
    $moduleDescription = 'Post text announcements that can be edited any time, with a full history of every update.';
    $moduleFeatures = [
        'Create and edit announcements',
        'Draft / published / archived status',
        'Edit history (backed by `announcement_revisions`)',
    ];
}

require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
