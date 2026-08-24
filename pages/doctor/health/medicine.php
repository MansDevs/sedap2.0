<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../../auth/login.php');
    exit;
}
$page_title = "Medicine Manager";

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
    <link rel="stylesheet" href="css/medicine.css">
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
        <div class="flex justify-between items-center mb-6">
            <select class="rounded-xl border-gray-300 p-2 border w-64"><option>Select Patient</option></select>
            <button class="bg-primary text-white px-4 py-2 rounded-full font-medium shadow-sm">+ Add Medicine</button>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface text-primary border-b border-primary/20">
                    <th class="p-3 font-semibold">Medicine</th>
                    <th class="p-3 font-semibold">Dosage</th>
                    <th class="p-3 font-semibold">Schedule</th>
                    <th class="p-3 font-semibold">Adherence %</th>
                    <th class="p-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="p-3 font-medium">Amoxicillin</td>
                    <td class="p-3 text-gray-600">500mg</td>
                    <td class="p-3 text-gray-600">3x Daily</td>
                    <td class="p-3"><div class="w-full bg-gray-200 rounded-full h-2.5"><div class="bg-triage-green h-2.5 rounded-full" style="width: 85%"></div></div> <span class="text-xs text-gray-500 mt-1 inline-block">85%</span></td>
                    <td class="p-3"><button class="text-red-500 hover:underline text-sm font-medium">Remove</button></td>
                </tr>
            </tbody>
        </table>
    </div>

            </div>
        </main>
    </div>
    <script src="js/medicine.js"></script>
</body>
</html>