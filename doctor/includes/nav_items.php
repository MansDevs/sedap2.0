<?php
/**
 * Navigation config for the Doctor / Nurse / Medical Assistant portal.
 * Fully separate from admin/includes/nav_items.php — this portal never
 * links into an /admin URL, and vice versa.
 *
 * `path` is always relative to the /doctor folder itself — header.php
 * prepends $doctorBase so it resolves correctly regardless of depth.
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
        'key' => 'announcements',
        'label' => 'Announcements',
        'icon' => 'campaign',
        'path' => 'announcements/index.php',
        'description' => 'View published announcements.',
    ],
    [
        'key' => 'posters',
        'label' => 'Posters',
        'icon' => 'imagesmode',
        'path' => 'posters/index.php',
        'description' => 'View published posters.',
    ],
    [
        'key' => 'triage_counter',
        'label' => 'Triage Counter',
        'icon' => 'exposure_plus_1',
        'path' => 'triage-counter/index.php',
        'description' => 'Fast tally of patients by triage color during an incident.',
    ],
    [
        'key' => 'triage',
        'label' => 'Triage List',
        'icon' => 'emergency',
        'path' => 'triage/index.php',
        'description' => 'Live triage board with vitals, sorted by severity.',
    ],
    [
        'key' => 'chat',
        'label' => 'Live Chat',
        'icon' => 'chat',
        'path' => '../chat/index.php',
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
        'path' => '../dashboard/tetapan.php',
        'description' => 'Account, password, and dark mode.',
    ],
];
