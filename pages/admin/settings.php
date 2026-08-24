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
  <title>Settings — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/css/sedap.css">
  <link rel="stylesheet" href="css/settings.css">
  
</head>
<body class="bg-[#f7f9fb] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '../shared/includes/sidebar_admin.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '../shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      
<div class="mb-6"><h1 class="text-2xl font-bold">Settings</h1></div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="font-bold mb-4">Account Preferences</h2>
        <div class="flex justify-between items-center py-3 border-b">
            <span>Dark Mode</span>
            <button class="bg-gray-200 w-12 h-6 rounded-full relative"><div class="bg-white w-5 h-5 rounded-full absolute left-0.5 top-0.5 shadow"></div></button>
        </div>
        <div class="py-4">
            <a href="myaccount.php" class="text-[#0058bd] font-medium block mb-2">Edit My Profile</a>
            <a href="../auth/login.php" class="text-red-500 font-medium block">Log Out</a>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="font-bold mb-4">Reset Password</h2>
        <form>
            <input type="password" placeholder="Current Password" class="w-full mb-3 rounded-xl border-gray-200">
            <input type="password" placeholder="New Password" class="w-full mb-3 rounded-xl border-gray-200">
            <input type="password" placeholder="Confirm New Password" class="w-full mb-4 rounded-xl border-gray-200">
            <button class="bg-[#0058bd] text-white px-6 py-2 rounded-full font-medium w-full">Update Password</button>
        </form>
    </div>
</div>

    </main>
  </div>
</div>
<script src="js/settings.js"></script>
</body>
</html>
