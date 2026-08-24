<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Live Triage List";

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
    <link rel="stylesheet" href="css/triage_list.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../shared/includes/sidebar_doctor.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../shared/includes/header.php'; ?>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <div class="mb-4 flex justify-between items-center">
        <div class="flex gap-2">
            <button class="px-4 py-1 bg-white border border-gray-300 rounded-full text-sm hover:bg-gray-50">All</button>
            <button class="px-4 py-1 bg-white border border-triage-red/50 text-triage-red rounded-full text-sm hover:bg-red-50">Red</button>
            <button class="px-4 py-1 bg-white border border-triage-yellow/50 text-triage-yellow rounded-full text-sm hover:bg-yellow-50">Yellow</button>
            <button class="px-4 py-1 bg-white border border-triage-green/50 text-triage-green rounded-full text-sm hover:bg-green-50">Green</button>
        </div>
        <button class="px-4 py-2 bg-primary text-white rounded-xl shadow-sm text-sm font-medium">Export CSV</button>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-primary/20 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface text-primary border-b border-primary/20">
                    <th class="p-4 font-semibold">Time</th>
                    <th class="p-4 font-semibold">Patient Name</th>
                    <th class="p-4 font-semibold">Symptoms</th>
                    <th class="p-4 font-semibold">Level</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody id="triage-table-body">
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="p-4 border-l-4 border-triage-red text-sm text-gray-500">10:30 AM</td>
                    <td class="p-4 font-medium">John Doe</td>
                    <td class="p-4 text-sm text-gray-600">Chest pain, Dizziness</td>
                    <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-triage-red">Red</span></td>
                    <td class="p-4 text-sm text-gray-500">Waiting</td>
                    <td class="p-4"><button class="text-primary hover:underline text-sm font-medium">View</button></td>
                </tr>
            </tbody>
        </table>
    </div>

            </div>
        </main>
    </div>
    <script src="js/triage_list.js"></script>
</body>
</html>