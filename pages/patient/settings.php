<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $name = $_POST['name'];
    $email = $_POST['email'];
    $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
    $stmt->execute([$name, $email, $userId]);
    $_SESSION['user_name'] = $name;
    header("Location: settings.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Settings</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-2xl mx-auto w-full">
            <h1 class="text-3xl font-bold mb-8 text-primary">Settings</h1>
            
            <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded-xl mb-6 font-bold">Profile updated!</div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl p-8 shadow-sm border border-primary/20 mb-8">
                <h2 class="text-xl font-bold mb-6 border-b pb-2">My Account</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-2">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50">
                    </div>
                    <button type="submit" class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primary-dark transition-colors">
                        Save Changes
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-3xl p-8 shadow-sm border border-red-200">
                <h2 class="text-xl font-bold mb-4 text-red-600">Danger Zone</h2>
                <a href="../auth/logout.php" class="inline-block bg-red-100 text-red-600 px-6 py-3 rounded-xl font-bold hover:bg-red-200 transition-colors">
                    Log Out
                </a>
            </div>
        </main>
    </div>
</body>
</html>
