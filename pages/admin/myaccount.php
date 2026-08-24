<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../auth/login.php'); exit; }
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Account — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/css/sedap.css">
  <link rel="stylesheet" href="css/myaccount.css">
  
</head>
<body class="bg-[#f7f9fb] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '../shared/includes/sidebar_admin.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '../shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      
<div class="mb-6"><h1 class="text-2xl font-bold">My Account</h1></div>
<div class="bg-white rounded-2xl shadow-sm border p-6 max-w-2xl">
    <div class="flex items-center gap-6 mb-8">
        <div class="w-24 h-24 rounded-full bg-[#0058bd] text-white flex items-center justify-center text-3xl font-bold">A</div>
        <div>
            <h2 class="text-xl font-bold"><?= $userName ?></h2>
            <div class="text-sm text-gray-500 mb-2">Administrator</div>
            <span class="px-2 py-1 bg-[#1E8449]/10 text-[#1E8449] rounded text-xs font-bold">ACTIVE</span>
        </div>
    </div>
    <form class="space-y-4">
        <div><label class="block text-sm mb-1 text-gray-600">Full Name</label><input type="text" value="<?= $userName ?>" class="w-full rounded-xl border-gray-200"></div>
        <div><label class="block text-sm mb-1 text-gray-600">Email</label><input type="email" class="w-full rounded-xl border-gray-200"></div>
        <div><label class="block text-sm mb-1 text-gray-600">Contact Number</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
        <button class="bg-[#0058bd] text-white px-6 py-2 rounded-full font-medium mt-4">Save Changes</button>
    </form>
</div>

    </main>
  </div>
</div>
<script src="js/myaccount.js"></script>
</body>
</html>
