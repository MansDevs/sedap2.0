<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mood = $_POST['mood'] ?? 3;
    $stmt = $pdo->prepare("INSERT INTO mood_journal_entries (user_id, mood_score, gratitude_note) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $mood, $_POST['gratitude'] ?? '']);
    header("Location: mood.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Mood Journal</title>
    <link rel="stylesheet" href="../../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../../shared/includes/header.php'; ?>
        <main class="p-6 max-w-2xl mx-auto w-full">
            <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded-xl mb-6 font-bold">Journal entry saved!</div>
            <?php endif; ?>
            
            <form method="POST" class="bg-white rounded-3xl p-8 shadow-sm border border-primary/20">
                <h1 class="text-3xl font-bold mb-6 text-primary">How are you feeling today?</h1>
                
                <div class="flex justify-between gap-2 mb-8">
                    <label class="flex flex-col items-center cursor-pointer hover:scale-110 transition-transform">
                        <input type="radio" name="mood" value="5" class="hidden peer">
                        <div class="text-5xl mb-2 peer-checked:bg-primary/20 rounded-full p-2">😄</div>
                        <span class="text-sm">Great</span>
                    </label>
                    <label class="flex flex-col items-center cursor-pointer hover:scale-110 transition-transform">
                        <input type="radio" name="mood" value="4" class="hidden peer">
                        <div class="text-5xl mb-2 peer-checked:bg-primary/20 rounded-full p-2">😊</div>
                        <span class="text-sm">Good</span>
                    </label>
                    <label class="flex flex-col items-center cursor-pointer hover:scale-110 transition-transform">
                        <input type="radio" name="mood" value="3" class="hidden peer" checked>
                        <div class="text-5xl mb-2 peer-checked:bg-primary/20 rounded-full p-2">😐</div>
                        <span class="text-sm">Okay</span>
                    </label>
                    <label class="flex flex-col items-center cursor-pointer hover:scale-110 transition-transform">
                        <input type="radio" name="mood" value="2" class="hidden peer">
                        <div class="text-5xl mb-2 peer-checked:bg-primary/20 rounded-full p-2">😟</div>
                        <span class="text-sm">Bad</span>
                    </label>
                    <label class="flex flex-col items-center cursor-pointer hover:scale-110 transition-transform">
                        <input type="radio" name="mood" value="1" class="hidden peer">
                        <div class="text-5xl mb-2 peer-checked:bg-primary/20 rounded-full p-2">😭</div>
                        <span class="text-sm">Awful</span>
                    </label>
                </div>

                <div class="mb-8">
                    <label class="block font-bold mb-2">What are you grateful for today?</label>
                    <textarea name="gratitude" rows="3" class="w-full p-4 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary outline-none" placeholder="I am grateful for..."></textarea>
                </div>

                <button type="submit" class="w-full bg-primary text-white p-4 rounded-full font-bold text-lg hover:bg-primary-dark transition-colors">
                    Save Journal Entry
                </button>
            </form>
        </main>
    </div>
</body>
</html>
