<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['volunteer'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Settings";

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
    <link rel="stylesheet" href="css/settings.css">
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
                
    <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-sm border border-primary/20 p-6 space-y-6">
        <div>
            <h3 class="font-bold text-lg mb-2 border-b pb-2">Account</h3>
            <button class="block w-full text-left px-4 py-2 hover:bg-gray-50 rounded text-gray-700">Reset Password</button>
            <a href="../auth/logout.php" class="block w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 rounded mt-2 font-medium">Logout</a>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/settings.js"></script>
</body>
</html>