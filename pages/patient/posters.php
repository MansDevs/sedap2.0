<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }

$stmt = $pdo->query("SELECT * FROM posters ORDER BY created_at DESC");
$posters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Health Posters</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' }, fontFamily: { sans: ['Inter', 'sans-serif'] } }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-6xl mx-auto w-full">
            <h1 class="text-3xl font-bold mb-8 text-primary">Health Posters</h1>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php foreach($posters as $poster): ?>
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-primary/20 hover:shadow-lg transition-shadow cursor-pointer" onclick="openLightbox('../../uploads/posters/<?= htmlspecialchars($poster['image_file']) ?>', '<?= htmlspecialchars($poster['title']) ?>')">
                    <div class="aspect-[3/4] bg-gray-100 flex items-center justify-center overflow-hidden relative">
                        <img src="../../uploads/posters/<?= htmlspecialchars($poster['image_file']) ?>" alt="<?= htmlspecialchars($poster['title']) ?>" class="object-cover w-full h-full" onerror="this.onerror=null; this.src='https://placehold.co/300x400?text=No+Image';">
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg truncate"><?= htmlspecialchars($poster['title']) ?></h3>
                        <?php if($poster['description']): ?>
                        <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($poster['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(!$posters): ?>
                    <p class="text-center text-gray-500 col-span-full mt-10">No posters available yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="fixed inset-0 bg-black/80 z-50 hidden flex-col items-center justify-center p-4">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300">
            <span class="material-symbols-outlined text-4xl">close</span>
        </button>
        <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
        <h3 id="lightbox-title" class="text-white text-xl mt-4 font-semibold text-center"></h3>
    </div>

    <script>
        function openLightbox(src, title) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox-title').textContent = title;
            document.getElementById('lightbox').classList.remove('hidden');
            document.getElementById('lightbox').classList.add('flex');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
            document.getElementById('lightbox').classList.remove('flex');
        }
    </script>
</body>
</html>
