<?php
/**
 * Navigation config for the ADMIN portal.
 * `path` is always relative to the /admin folder itself — header.php
 * prepends $adminBase so it resolves correctly regardless of depth.
 */
$navItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'path' => 'dashboard.php',
        'description' => 'Overview of all modules.',
    ],
    [
        'key' => 'personnel',
        'label' => 'Staff & Volunteers',
        'icon' => 'badge',
        'path' => 'personnel/index.php',
        'description' => 'Register staff and volunteers, export roster to CSV.',
    ],
    [
        'key' => 'announcements',
        'label' => 'Announcements',
        'icon' => 'campaign',
        'path' => 'announcements/index.php',
        'description' => 'Post and edit text announcements over time.',
    ],
    [
        'key' => 'posters',
        'label' => 'Poster Editor',
        'icon' => 'imagesmode',
        'path' => 'posters/index.php',
        'description' => 'Design and publish posters.',
    ],
    [
        'key' => 'triage',
        'label' => 'Triage List',
        'icon' => 'emergency_home',
        'path' => 'triage/index.php',
        'description' => 'Live triage board with vitals, sorted by severity.',
    ],
    [
        'key' => 'screening',
        'label' => 'Health Screening',
        'icon' => 'assignment',
        'path' => 'screening/index.php',
        'description' => 'Build online screening forms, review responses.',
    ],
    [
        'key' => 'chat',
        'label' => 'Live Chat',
        'icon' => 'forum',
        'path' => '../chat/index.php',
        'description' => 'Message staff, volunteers, and patients.',
    ],
    [
        'key' => 'patients',
        'label' => 'Patient Registration',
        'icon' => 'group',
        'path' => 'patients/index.php',
        'description' => 'Register and manage patient records.',
    ],
    [
        'key' => 'family',
        'label' => 'Family Information',
        'icon' => 'family_restroom',
        'path' => 'family/index.php',
        'description' => 'Manage family and emergency contacts per patient.',
    ],
    [
        'key' => 'health',
        'label' => 'Health Module',
        'icon' => 'medical_services',
        'path' => 'health/index.php',
        'description' => 'Bristol scale, water tracker, mood journal, medicine reminders.',
    ],
    [
        'key' => 'settings',
        'label' => 'Settings',
        'icon' => 'settings',
        'path' => '../dashboard/tetapan.php',
        'description' => 'Account, password, and dark mode.',
    ],
];
