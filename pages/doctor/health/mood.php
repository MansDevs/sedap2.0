<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../../auth/login.php');
    exit;
}
$page_title = "Mood Journal Viewer";

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
    <link rel="stylesheet" href="../../shared/css/sedap.css">
    <link rel="stylesheet" href="css/mood.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../../shared/includes/sidebar_doctor.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../../shared/includes/header.php'; ?>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
        <div class="mb-6"><select class="w-full rounded-xl border-gray-300 p-2 border max-w-xs"><option>Select Patient</option></select></div>
        <div class="space-y-4">
            <div class="flex gap-4 p-4 border rounded-xl border-gray-100 bg-gray-50">
                <div class="text-4xl">😊</div>
                <div>
                    <h4 class="font-bold text-gray-800">Good Day <span class="text-sm font-normal text-gray-500">- Oct 10, 2023</span></h4>
                    <p class="text-sm text-gray-600 mt-1">Feeling much better after the medication.</p>
                    <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">Stress: Low</span>
                </div>
            </div>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/mood.js"></script>
</body>
</html>