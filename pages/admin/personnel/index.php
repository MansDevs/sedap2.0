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
  <title>Personnel — SeDaP</title>
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
    <h1 class="text-2xl font-bold">Personnel Management</h1>
    <div class="space-x-2">
        <button class="bg-[#0058bd] text-white px-4 py-2 rounded-full text-sm font-medium">+ Add Personnel</button>
        <button class="bg-white border border-gray-300 px-4 py-2 rounded-full text-sm font-medium">Export CSV</button>
    </div>
</div>
<div class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex gap-4 border-b mb-4">
        <button class="px-4 py-2 border-b-2 border-[#0058bd] text-[#0058bd] font-semibold">All</button>
        <button class="px-4 py-2 text-gray-500">Staff</button>
        <button class="px-4 py-2 text-gray-500">Volunteers</button>
    </div>
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b"><th class="pb-2">Name</th><th class="pb-2">Role</th><th class="pb-2">Phone</th><th class="pb-2">Status</th><th class="pb-2 text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td class="py-3">Dr. Ali</td><td>Doctor</td><td>012-3456789</td><td><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Active</span></td><td class="text-right"><button class="text-[#0058bd]">Edit</button></td></tr>
        </tbody>
    </table>
</div>

    </main>
  </div>
</div>
<script src="js/index.js"></script>
</body>
</html>
