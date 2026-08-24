<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../auth/login.php'); exit; }
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Live Triage — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../shared/css/sedap.css">
  <link rel="stylesheet" href="css/index.css">
  
</head>
<body class="bg-[#f7f9fb] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '../../shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Live Triage</h1>
    <a href="add.php" class="bg-[#0058bd] text-white px-4 py-2 rounded-full text-sm font-medium">+ New Triage</a>
</div>
<div class="flex gap-2 mb-4">
    <button class="px-4 py-1 rounded-full bg-gray-200 text-sm font-medium">All</button>
    <button class="px-4 py-1 rounded-full bg-[#C0392B]/10 text-[#C0392B] text-sm font-medium border border-[#C0392B]/20">RED</button>
    <button class="px-4 py-1 rounded-full bg-[#D4A017]/10 text-[#D4A017] text-sm font-medium border border-[#D4A017]/20">YELLOW</button>
    <button class="px-4 py-1 rounded-full bg-[#1E8449]/10 text-[#1E8449] text-sm font-medium border border-[#1E8449]/20">GREEN</button>
</div>
<div class="bg-white rounded-2xl shadow-sm border">
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b bg-gray-50 rounded-t-2xl"><th class="p-3">Patient</th><th class="p-3">Level</th><th class="p-3">Complaint</th><th class="p-3">Vitals</th><th class="p-3">Time</th></tr></thead>
        <tbody>
            <tr class="border-b border-l-4 border-l-[#C0392B]"><td class="p-3 font-medium">Ahmad (IC: 9010...)</td><td class="p-3"><span class="px-2 py-1 bg-[#C0392B] text-white rounded text-xs font-bold">RED</span></td><td class="p-3">Chest Pain</td><td class="p-3 text-xs">Temp: 38°C<br>BP: 140/90</td><td class="p-3 text-xs text-gray-500">10:30 AM</td></tr>
        </tbody>
    </table>
</div>

    </main>
  </div>
</div>
<script src="js/index.js"></script>
</body>
</html>
