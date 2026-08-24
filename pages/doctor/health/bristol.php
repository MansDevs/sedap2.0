<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../../auth/login.php');
    exit;
}
$page_title = "Bristol Scale Editor";

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
    <link rel="stylesheet" href="css/bristol.css">
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
                
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
            <h2 class="text-xl font-bold text-primary mb-4">Bristol Stool Scale Reference</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 border rounded-xl border-gray-200"><h4 class="font-bold">Type 1</h4><p class="text-sm text-gray-500">Separate hard lumps</p></div>
                <div class="p-4 border rounded-xl border-gray-200"><h4 class="font-bold">Type 2</h4><p class="text-sm text-gray-500">Sausage-shaped but lumpy</p></div>
                <div class="p-4 border rounded-xl border-gray-200"><h4 class="font-bold">Type 3</h4><p class="text-sm text-gray-500">Like a sausage but with cracks</p></div>
                <div class="p-4 border rounded-xl border-gray-200 bg-green-50"><h4 class="font-bold">Type 4</h4><p class="text-sm text-gray-500">Like a sausage or snake, smooth</p></div>
                <!-- ... -->
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
            <h2 class="text-xl font-bold text-primary mb-4">Log Entry</h2>
            <form class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">Patient</label><select class="w-full rounded-xl border-gray-300 p-2 border"><option>Select Patient</option></select></div>
                <div><label class="block text-sm font-medium mb-1">Type (1-7)</label><input type="number" min="1" max="7" class="w-full rounded-xl border-gray-300 p-2 border"></div>
                <div><label class="block text-sm font-medium mb-1">Notes</label><textarea class="w-full rounded-xl border-gray-300 p-2 border"></textarea></div>
                <button type="submit" class="w-full bg-primary text-white rounded-full py-2 font-bold shadow-sm">Save Log</button>
            </form>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/bristol.js"></script>
</body>
</html>