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
  <title>New Triage — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../shared/css/sedap.css">
  <link rel="stylesheet" href="css/add.css">
  
</head>
<body class="bg-[#f7f9fb] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '../../shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      
<div class="mb-6"><h1 class="text-2xl font-bold">New Triage Entry</h1></div>
<div class="bg-white rounded-2xl shadow-sm border p-6">
    <form action="" method="POST">
        <!-- Section 1 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#0058bd]">1. Personal Info</h3>
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div><label class="block text-sm mb-1">Full Name</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">IC / ID</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Age</label><input type="number" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Gender</label>
                <div class="flex gap-4 mt-2"><label><input type="radio" name="gender" value="M"> Male</label><label><input type="radio" name="gender" value="F"> Female</label></div>
            </div>
        </div>
        
        <!-- Section 2 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#0058bd]">2. Vitals & Symptoms</h3>
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div><label class="block text-sm mb-1">Temp (°C)</label><input type="number" step="0.1" id="t_temp" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">BP</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Glucose</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Lipid</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
        </div>
        <div class="mb-6">
            <label class="block text-sm mb-2 font-medium">Symptoms Check</label>
            <div class="flex gap-4 flex-wrap">
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="diarrhea"> Cirit-birit</label>
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="vomit"> Muntah</label>
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="fever"> Demam</label>
            </div>
        </div>

        <!-- Section 4 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#0058bd]">3. Triage Code</h3>
        <div class="p-6 bg-gray-50 rounded-xl mb-6 text-center border-2 border-dashed" id="triage-result">
            <div class="text-sm text-gray-500 mb-2">Auto-calculated Code</div>
            <div class="text-3xl font-bold px-6 py-2 rounded-full inline-block bg-gray-200" id="triage-badge">PENDING</div>
        </div>
        
        <button type="submit" class="w-full bg-[#0058bd] text-white py-3 rounded-full font-bold text-lg hover:bg-[#004494]">Submit Triage</button>
    </form>
</div>

    </main>
  </div>
</div>
<script src="js/add.js"></script>
</body>
</html>
