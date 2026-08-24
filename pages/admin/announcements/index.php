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
  <title>Announcements — SeDaP</title>
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
    <h1 class="text-2xl font-bold">Announcements</h1>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-[#0058bd]/10 p-6 mb-6">
    <h2 class="font-semibold mb-4 text-lg">Create New Announcement</h2>
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><label class="block text-sm mb-1">Title</label><input type="text" name="title" class="w-full rounded-xl border-gray-200" required></div>
            <div><label class="block text-sm mb-1">Status</label><select name="status" class="w-full rounded-xl border-gray-200"><option value="published">Published</option><option value="draft">Draft</option></select></div>
        </div>
        <div class="mb-4">
            <label class="block text-sm mb-1">Content</label>
            <textarea name="content" rows="3" class="w-full rounded-xl border-gray-200" required></textarea>
        </div>
        <button type="submit" class="bg-[#0058bd] text-white px-6 py-2 rounded-full font-medium hover:bg-[#004494]">Save Announcement</button>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-[#0058bd]/10 p-6">
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b"><th class="pb-2">Title</th><th class="pb-2">Status</th><th class="pb-2">Date</th><th class="pb-2 text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td class="py-3">System Update</td><td><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Published</span></td><td>2024-03-01</td><td class="text-right"><button class="text-[#0058bd]">Edit</button> | <button class="text-red-500">Delete</button></td></tr>
        </tbody>
    </table>
</div>

    </main>
  </div>
</div>
<script src="js/index.js"></script>
</body>
</html>
