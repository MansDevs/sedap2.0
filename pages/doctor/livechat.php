<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Live Chat";

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
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link rel="stylesheet" href="css/livechat.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../shared/includes/sidebar_doctor.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../shared/includes/header.php'; ?>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <audio id="notify-sound" src="../shared/assets/notify.mp3" preload="auto"></audio>
    <div class="flex h-[calc(100vh-140px)] bg-white rounded-3xl shadow-sm border border-primary/20 overflow-hidden">
        <div class="w-1/3 border-r border-gray-200 flex flex-col bg-surface/30">
            <div class="p-4 border-b border-gray-200"><input type="text" placeholder="Search chats..." class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:border-primary"></div>
            <div class="flex-1 overflow-y-auto" id="chat-list">
                <div class="p-4 border-b border-gray-100 hover:bg-white cursor-pointer transition">
                    <h4 class="font-bold text-gray-800">Community Group A</h4>
                    <p class="text-sm text-gray-500 truncate">Last message excerpt...</p>
                </div>
            </div>
        </div>
        <div class="w-2/3 flex flex-col bg-white">
            <div class="p-4 border-b border-gray-200 bg-surface/20 flex justify-between items-center">
                <h3 class="font-bold text-lg">Community Group A</h3>
                <button class="text-primary"><span class="material-symbols-outlined">info</span></button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                <div class="flex justify-end"><div class="bg-primary text-white p-3 rounded-2xl rounded-tr-sm shadow-sm max-w-[70%]">Hello!</div></div>
                <div class="flex justify-start"><div class="bg-gray-100 text-gray-800 p-3 rounded-2xl rounded-tl-sm shadow-sm max-w-[70%]">Hi Doctor.</div></div>
            </div>
            <div class="p-4 border-t border-gray-200 bg-surface/10 flex gap-2">
                <button class="p-2 text-gray-500 hover:text-primary"><span class="material-symbols-outlined">attach_file</span></button>
                <input type="text" id="msg-input" placeholder="Type a message..." class="flex-1 px-4 py-2 rounded-full border border-gray-300 focus:border-primary">
                <button class="px-6 py-2 bg-primary text-white rounded-full font-medium shadow-sm hover:bg-primary-dark">Send</button>
            </div>
        </div>
    </div>

            </div>
        </main>
    </div>
    <script src="js/livechat.js"></script>
</body>
</html>