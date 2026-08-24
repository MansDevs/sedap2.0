<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Announcements</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link rel="stylesheet" href="css/announcements.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary': '#0058bd', 'surface': '#f7f9fb',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'], }
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-4xl mx-auto w-full">
            <h1 class="text-3xl font-bold mb-8 text-primary">Announcements</h1>
            <div class="flex flex-col gap-4">
                <?php foreach($announcements as $ann): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-primary/20 hover:shadow-md transition-shadow cursor-pointer" onclick="this.classList.toggle('expanded')">
                    <div class="flex gap-4 items-start">
                        <span class="material-symbols-outlined text-primary text-4xl mt-1">campaign</span>
                        <div class="flex-1">
                            <h3 class="font-bold text-xl mb-1"><?= htmlspecialchars($ann['title']) ?></h3>
                            <p class="text-sm text-gray-500 mb-2"><?= date('d M Y, H:i', strtotime($ann['created_at'])) ?></p>
                            <div class="announcement-content text-gray-700">
                                <?= nl2br(htmlspecialchars($ann['content'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(!$announcements): ?>
                    <p class="text-center text-gray-500 mt-10">No announcements yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <style>
        .announcement-content {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }
        .expanded .announcement-content {
            display: block;
        }
    </style>
</body>
</html>
