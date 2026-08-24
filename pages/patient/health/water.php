<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

if (isset($_POST['add_water'])) {
    $amount = (int)$_POST['amount'];
    $stmt = $pdo->prepare("INSERT INTO water_intake_logs (user_id, amount_ml) VALUES (?, ?)");
    $stmt->execute([$userId, $amount]);
    header("Location: water.php");
    exit;
}

$stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_intake_logs WHERE user_id=? AND DATE(created_at)=?");
$stmt->execute([$userId, $today]);
$intake = $stmt->fetchColumn() ?: 0;
$target = 2500;
$percent = min(100, round(($intake / $target) * 100));
$color = $percent >= 100 ? 'text-green-500' : ($percent > 50 ? 'text-yellow-500' : 'text-primary');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Water Tracker</title>
    <link rel="stylesheet" href="../../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../../shared/includes/header.php'; ?>
        <main class="p-6 max-w-lg mx-auto w-full flex flex-col items-center">
            <h1 class="text-3xl font-bold mb-8 text-primary">Water Tracker</h1>

            <div class="bg-white rounded-full w-64 h-64 shadow-lg border-8 border-gray-100 flex flex-col items-center justify-center mb-8 relative">
                <span class="material-symbols-outlined text-4xl mb-2 <?= $color ?>">water_drop</span>
                <div class="text-4xl font-bold <?= $color ?>"><?= $intake ?></div>
                <div class="text-gray-400 text-sm">/ <?= $target ?> ml</div>
            </div>

            <form method="POST" class="grid grid-cols-3 gap-4 w-full mb-8">
                <button type="submit" name="amount" value="150" class="bg-white p-4 rounded-2xl shadow-sm border border-primary/20 hover:bg-primary/5 flex flex-col items-center">
                    <span class="material-symbols-outlined text-blue-300 text-3xl">local_cafe</span>
                    <span class="font-bold mt-2">150 ml</span>
                </button>
                <button type="submit" name="amount" value="250" class="bg-white p-4 rounded-2xl shadow-sm border border-primary/20 hover:bg-primary/5 flex flex-col items-center">
                    <span class="material-symbols-outlined text-blue-400 text-3xl">water_full</span>
                    <span class="font-bold mt-2">250 ml</span>
                </button>
                <button type="submit" name="amount" value="500" class="bg-white p-4 rounded-2xl shadow-sm border border-primary/20 hover:bg-primary/5 flex flex-col items-center">
                    <span class="material-symbols-outlined text-blue-500 text-3xl">water_bottle</span>
                    <span class="font-bold mt-2">500 ml</span>
                </button>
            </form>
        </main>
    </div>
</body>
</html>
