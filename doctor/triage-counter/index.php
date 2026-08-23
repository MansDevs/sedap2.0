<?php
$doctorBase = '../';
$activeNav = 'triage_counter';
$pageTitle = 'Triage Counter';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$moduleIcon = 'exposure_plus_1';
$moduleTitle = 'Triage Patient Counter';
$moduleDescription = 'A fast tap-counter for tallying patients by triage color during an incident — no full record needed.';
$moduleFeatures = [
    'One-tap counter for each triage color (red / yellow / green / black)',
    'Live running totals, visible to the whole team',
    'Undo button for a mis-tap (backed by `triage_counter_logs`)',
];
require __DIR__ . '/../includes/placeholder.php';
require_once __DIR__ . '/../includes/footer.php';
