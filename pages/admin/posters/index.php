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
  <title>Poster Editor — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../shared/css/sedap.css">
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
</head>
<body class="bg-[#f7f9fb] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '../../shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      
<div class="mb-6"><h1 class="text-2xl font-bold">Poster Editor</h1></div>
<div class="flex gap-6 h-[600px]">
    <div class="w-2/3 bg-white rounded-2xl border flex items-center justify-center overflow-hidden bg-gray-50">
        <canvas id="posterCanvas" width="800" height="550" class="border shadow-sm"></canvas>
    </div>
    <div class="w-1/3 bg-white rounded-2xl border p-6 flex flex-col">
        <h2 class="font-bold mb-4">Toolbar</h2>
        <div class="grid grid-cols-2 gap-2 mb-6">
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="addText()">Add Text</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="addRect()">Add Rectangle</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50 text-red-500" onclick="deleteSelected()">Delete</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="canvas.clear()">Clear</button>
        </div>
        <hr class="my-4">
        <h2 class="font-bold mb-4">Save Poster</h2>
        <input type="text" placeholder="Poster Title" class="w-full mb-3 rounded border-gray-200">
        <select class="w-full mb-4 rounded border-gray-200"><option>Draft</option><option>Published</option></select>
        <button class="w-full bg-[#0058bd] text-white py-2 rounded-full mb-2">Save</button>
    </div>
</div>

    </main>
  </div>
</div>
<script src="js/index.js"></script>
</body>
</html>
