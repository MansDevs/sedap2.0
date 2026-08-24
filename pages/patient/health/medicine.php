<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - My Medicines</title>
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
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-primary">My Medicines</h1>
                <button class="bg-primary text-white px-6 py-2 rounded-full font-bold flex items-center gap-2 hover:bg-primary-dark transition-colors">
                    <span class="material-symbols-outlined">add</span> Add Medicine
                </button>
            </div>
            
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-primary/20 text-center text-gray-500">
                <span class="material-symbols-outlined text-6xl mb-4 text-gray-300">medication</span>
                <p>No medicines added yet.</p>
                <p class="text-sm mt-2">Click "Add Medicine" to track your prescriptions and set reminders.</p>
            </div>
        </main>
    </div>
</body>
</html>
