<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['volunteer'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Posters Gallery";

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
    <link rel="stylesheet" href="css/posters.css">
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
                
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-3 shadow-sm border border-primary/20 cursor-pointer">
            <div class="w-full h-32 bg-gray-100 rounded-xl mb-2 flex items-center justify-center text-gray-400">Image</div>
            <p class="text-center text-sm font-medium">Poster A</p>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/posters.js"></script>
</body>
</html>