<?php
session_start();

// Protect the dashboard: If no session exists, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$userName = $user['name'] ?? ($_SESSION['user_name'] ?? 'User');
$userRole = $user['role'] ?? 'staff';

$portalUrl = null;
$portalLabel = null;
if ($userRole === 'admin') {
    $portalUrl = '../admin/dashboard.php';
    $portalLabel = 'Admin Panel';
} elseif (in_array($userRole, ['doctor', 'nurse', 'medical_assistant'], true)) {
    $portalUrl = '../doctor/dashboard.php';
    $portalLabel = ucwords(str_replace('_', ' ', $userRole)) . ' Panel';
}
?>

<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Dashboard - Sedap</title>
    <link rel="icon" type="image/jpeg" href="../auth/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/theme-config.js"></script>
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        .mesh-bg {
            background-color: #f7f9fb;
            background-image: 
                radial-gradient(at 10% 20%, hsla(212, 100%, 37%, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(188, 100%, 75%, 0.12) 0px, transparent 50%);
        }
    </style>
</head>
<body class="h-full mesh-bg flex flex-col items-center justify-center p-4 antialiased font-body relative">

    <!-- Top Right Settings Button -->
    <a href="tetapan.php" class="absolute top-6 right-6 md:top-8 md:right-8 bg-surface-container-lowest hover:bg-surface-container-low text-primary p-3 rounded-full shadow-sm border border-outline-variant/40 flex items-center justify-center transition-all group active:scale-95">
        <span class="material-symbols-outlined group-hover:rotate-90 transition-transform duration-300">settings</span>
    </a>

    <!-- Top Left Chat Button -->
    <a href="../chat/index.php" class="absolute top-6 left-6 md:top-8 md:left-8 bg-surface-container-lowest hover:bg-surface-container-low text-primary p-3 rounded-full shadow-sm border border-outline-variant/40 flex items-center justify-center transition-all group active:scale-95">
        <span class="material-symbols-outlined">chat</span>
    </a>

    <main class="w-full max-w-[440px]">
        <div class="bg-surface-container-lowest p-8 md:p-10 rounded-[32px] shadow-lg border border-outline-variant/30 text-center">
            
            <div class="w-20 h-20 rounded-full overflow-hidden mx-auto mb-6 shadow-md border-2 border-outline-variant/30 p-0.5 bg-surface-container-lowest flex items-center justify-center">
                <img src="../auth/logo.jpg" alt="SeDaP Logo" class="w-full h-full object-cover rounded-full">
            </div>
            
            <h1 class="font-headline text-3xl font-bold text-primary mb-2">Dashboard</h1>
            <p class="text-lg text-on-surface-variant mb-8">
                Welcome back, <strong class="text-on-surface"><?php echo htmlspecialchars($userName); ?></strong>!
            </p>

            <!-- Portal Button (role-based) -->
            <?php if ($portalUrl): ?>
            <a href="<?php echo htmlspecialchars($portalUrl); ?>"
               class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-4 px-6 rounded-full transition-all shadow-sm hover:shadow flex justify-center items-center gap-2 group mb-3">
                <span class="material-symbols-outlined text-[20px]">space_dashboard</span>
                <span><?php echo htmlspecialchars($portalLabel); ?></span>
            </a>
            <?php endif; ?>

            <!-- The Logout Button -->
            <a href="../auth/logout.php" 
               class="w-full bg-error hover:bg-on-error-container text-on-error font-semibold py-4 px-6 rounded-full transition-all shadow-sm hover:shadow flex justify-center items-center gap-2 group">
                <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">logout</span>
                <span>Sign Out</span>
            </a>

        </div>
    </main>

</body>
</html>