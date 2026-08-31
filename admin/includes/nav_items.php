<?php
/**
 * Navigation config for the ADMIN portal only.
 * The Doctor/Nurse/Medical Assistant portal is a fully separate area
 * at /doctor with its own nav_items.php — see doctor/includes/nav_items.php.
 *
 * `path` is always relative to the /admin folder itself — header.php
 * prepends $adminBase so it resolves correctly no matter how deep
 * the current page is nested (admin/dashboard.php vs admin/personnel/index.php).
 */
$navItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'space_dashboard',
        'path' => 'dashboard.php',
        'description' => 'Overview of all modules.',
    ],
    [
        'key' => 'personnel',
        'label' => 'Staff & Volunteers',
        'icon' => 'groups',
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
        'icon' => 'emergency',
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
        'icon' => 'chat',
        'path' => 'chat/index.php',
        'description' => 'Message staff, volunteers, and patients.',
    ],
    [
        'key' => 'patients',
        'label' => 'Patient Registration',
        'icon' => 'person_add',
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
        'icon' => 'favorite',
        'path' => 'health/index.php',
        'description' => 'Bristol scale, water tracker, mood journal, medicine reminders.',
    ],
    [
        'key' => 'settings',
        'label' => 'Settings',
        'icon' => 'settings',
        'path' => 'settings/index.php',
        'description' => 'Account, password, and dark mode.',
    ],
];
