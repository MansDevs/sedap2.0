<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../../auth/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['msg'] = "Log saved!";
    header("Location: bristol.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Bristol Stool Scale</title>
    <link rel="stylesheet" href="../../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../../shared/includes/header.php'; ?>
        <main class="p-6 max-w-4xl mx-auto w-full">
            <h1 class="text-3xl font-bold mb-8 text-primary">Bristol Stool Scale</h1>
            
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-primary/20 mb-8">
                <h2 class="text-xl font-bold mb-4">Log Today</h2>
                <form method="POST" class="flex gap-4 items-center">
                    <select name="type" class="p-3 rounded-xl border border-gray-300 flex-1">
                        <option value="1">Type 1 - Severe Constipation</option>
                        <option value="2">Type 2 - Mild Constipation</option>
                        <option value="3">Type 3 - Normal</option>
                        <option value="4">Type 4 - Normal</option>
                        <option value="5">Type 5 - Lacking Fiber</option>
                        <option value="6">Type 6 - Mild Diarrhea</option>
                        <option value="7">Type 7 - Severe Diarrhea</option>
                    </select>
                    <button type="submit" class="bg-primary text-white px-6 py-3 rounded-xl font-bold">Save Log</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php for($i=1; $i<=7; $i++): ?>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex gap-4 items-center">
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xl"><?= $i ?></div>
                    <div>
                        <h3 class="font-bold">Type <?= $i ?></h3>
                        <p class="text-sm text-gray-500">Description for type <?= $i ?> goes here.</p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</body>
</html>
