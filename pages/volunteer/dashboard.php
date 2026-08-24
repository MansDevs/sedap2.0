<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['volunteer'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Volunteer Dashboard";

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SeDaP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
          darkMode: 'class',
          theme: {
            extend: {
              colors: {
                primary: '#0058bd', 'primary-dark': '#004494', 'primary-light': '#2771df',
                surface: '#f7f9fb', 'surface-dark': '#e0e3e5',
                'on-primary': '#ffffff', 'on-surface': '#1a1a1a', 'on-surface-muted': '#5a5a5a',
                'triage-red': '#C0392B', 'triage-yellow': '#D4A017', 'triage-green': '#1E8449',
              },
              fontFamily: { sans: ['Inter', 'sans-serif'] },
              borderRadius: { 'DEFAULT': '0.75rem', 'xl': '1rem', '2xl': '1.5rem', '3xl': '2rem', 'full': '9999px' }
            }
          }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../shared/includes/sidebar_volunteer.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../shared/includes/header.php'; ?>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <div class="bg-primary text-white rounded-3xl p-8 mb-6 shadow-md bg-[url('../shared/assets/pattern.png')] bg-cover">
        <h1 class="text-3xl font-bold mb-2">Welcome Back, Volunteer!</h1>
        <p class="opacity-90">Thank you for contributing to community health.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6 flex flex-col items-center justify-center">
            <span class="text-primary font-bold text-lg">Triages Registered Today</span>
            <span class="text-5xl font-extrabold text-primary mt-4">12</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6 flex flex-col gap-3 justify-center">
            <a href="triage_counter.php" class="bg-primary text-white text-center rounded-xl py-3 font-bold hover:bg-primary-dark transition shadow-sm">New Triage Entry</a>
            <a href="triage_list.php" class="bg-surface-dark text-primary border border-primary/20 text-center rounded-xl py-3 font-bold hover:bg-surface transition">View Triage List</a>
            <a href="announcements.php" class="bg-gray-50 text-gray-700 border border-gray-200 text-center rounded-xl py-3 font-medium hover:bg-gray-100 transition">View Announcements</a>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/dashboard.js"></script>
</body>
</html>