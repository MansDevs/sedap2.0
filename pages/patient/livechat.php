<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];

// Minimal livechat mockup for UI completion
$messages = [
    ['sender_id' => 999, 'sender_name' => 'Dr. Aishah', 'body' => 'Hello, how can I help you today? Are you experiencing any symptoms?', 'is_own' => false],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    // just dummy for now
    $messages[] = ['sender_id' => $userId, 'sender_name' => 'Me', 'body' => htmlspecialchars($_POST['message']), 'is_own' => true];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Talk to Doctor</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../shared/includes/header.php'; ?>
        <main class="flex-1 p-6 flex justify-center items-start overflow-hidden">
            <div class="w-full max-w-4xl bg-white rounded-3xl shadow-sm border border-primary/20 flex flex-col h-[calc(100vh-120px)]">
                <!-- Chat Header -->
                <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-primary/5 rounded-t-3xl">
                    <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined">stethoscope</span>
                    </div>
                    <div>
                        <h2 class="font-bold">Medical Staff Support</h2>
                        <p class="text-xs text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> Online</p>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50 flex flex-col" id="chat-box">
                    <?php foreach($messages as $msg): ?>
                        <div class="flex <?= $msg['is_own'] ? 'justify-end' : 'justify-start' ?>">
                            <div class="max-w-[70%] rounded-2xl p-3 <?= $msg['is_own'] ? 'bg-primary text-white rounded-br-none' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-none shadow-sm' ?>">
                                <?= $msg['body'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chat Input -->
                <div class="p-4 bg-white border-t border-gray-100 rounded-b-3xl">
                    <form method="POST" class="flex gap-2">
                        <input type="text" name="message" placeholder="Type your message here..." class="flex-1 p-3 rounded-full bg-gray-100 border-none focus:ring-2 focus:ring-primary outline-none px-6">
                        <button type="submit" class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primary-dark transition-colors">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Scroll to bottom
        const box = document.getElementById('chat-box');
        box.scrollTop = box.scrollHeight;
    </script>
</body>
</html>
