<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];
$patient = $pdo->prepare("SELECT * FROM patients WHERE user_id=?");
$patient->execute([$userId]);
$patient = $patient->fetch() ?: [];
$patientName = $_SESSION['user_name'] ?? 'User';

// Get user info (water target, weight)
$userInfo = $pdo->prepare("SELECT water_target_ml, weight_kg FROM users WHERE id=?");
$userInfo->execute([$userId]);
$userInfo = $userInfo->fetch() ?: [];
$waterTarget = $userInfo['water_target_ml'] ?? 2100;

// Get today's water intake (uses patient_id)
$today = date('Y-m-d');
$patientId = $patient['id'] ?? null;
if ($patientId) {
    $waterStmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_intake_logs WHERE patient_id=? AND DATE(logged_at)=?");
    $waterStmt->execute([$patientId, $today]);
    $waterIntake = (int)($waterStmt->fetchColumn() ?: 0);
} else {
    $waterIntake = 0;
}

// Get last mood entry (uses patient_id)
$moodEmoji = '—';
$lastMood = null;
if ($patientId) {
    $moodStmt = $pdo->prepare("SELECT mood_score FROM mood_journal_entries WHERE patient_id=? ORDER BY logged_at DESC LIMIT 1");
    $moodStmt->execute([$patientId]);
    $lastMood = $moodStmt->fetchColumn();
}
$moodEmojis = [5 => '😄', 4 => '😊', 3 => '😐', 2 => '😟', 1 => '😭'];
$moodEmoji = $lastMood && isset($moodEmojis[$lastMood]) ? $moodEmojis[$lastMood] : '—';

// Get next medicine reminder
$nextMed = null;
if ($patientId) {
    $medStmt = $pdo->prepare("SELECT m.medicine_name, mr.reminder_time FROM medicines m JOIN medicine_reminders mr ON m.id=mr.medicine_id WHERE m.patient_id=? AND mr.is_active=1 AND mr.reminder_time > CURTIME() ORDER BY mr.reminder_time ASC LIMIT 1");
    $medStmt->execute([$patientId]);
    $nextMed = $medStmt->fetch();
}

// Get published announcements
$annStmt = $pdo->query("SELECT * FROM announcements WHERE status='published' ORDER BY published_at DESC LIMIT 3");
$announcements = $annStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Patient Dashboard</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary': '#0058bd',
                        'primary-dark': '#004494',
                        'primary-light': '#2771df',
                        'primary-container': '#2771df',
                        'secondary': '#3d6185',
                        'tertiary': '#006673',
                        'surface': '#f7f9fb',
                        'surface-container': '#eceef0',
                        'surface-container-low': '#f2f4f6',
                        'surface-container-lowest': '#ffffff',
                        'on-primary': '#ffffff',
                        'on-surface': '#191c1e',
                        'on-surface-variant': '#424753',
                        'outline': '#727785',
                    },
                    fontFamily: { sans: ['Roboto Flex', 'sans-serif'], }
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-7xl mx-auto w-full">
            <div class="bg-gradient-to-r from-primary to-primary-light rounded-3xl p-8 text-white shadow-md mb-8">
                <h1 class="text-3xl font-bold mb-2">Welcome back, <?= htmlspecialchars($patientName) ?>! 👋</h1>
                <p class="opacity-90">How are you feeling today?</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-primary/20 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Water Today</p>
                        <p class="text-xl font-bold text-primary"><?= $waterIntake ?> / <?= $waterTarget ?> ml</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-blue-400">water_drop</span>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-primary/20 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Last Mood</p>
                        <p class="text-2xl font-bold"><?= $moodEmoji ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-yellow-500">sentiment_satisfied</span>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-primary/20 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Next Medicine</p>
                        <p class="text-xl font-bold text-primary">No alerts</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-red-400">medication</span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8">
                <a href="screening.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">quiz</span>
                    </div>
                    <span class="font-semibold text-lg">Health Screening</span>
                </a>
                <a href="livechat.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">chat</span>
                    </div>
                    <span class="font-semibold text-lg">Talk to Doctor</span>
                </a>
                <a href="health/water.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">water_drop</span>
                    </div>
                    <span class="font-semibold text-lg">Water Tracker</span>
                </a>
                <a href="health/mood.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">sentiment_satisfied</span>
                    </div>
                    <span class="font-semibold text-lg">Mood Journal</span>
                </a>
                <a href="health/medicine.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">medication</span>
                    </div>
                    <span class="font-semibold text-lg">My Medicines</span>
                </a>
                <a href="family_register.php" class="bg-white hover:bg-surface transition-colors p-6 rounded-3xl shadow-sm border border-primary/20 flex flex-col items-center justify-center text-center group">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl text-primary">family_restroom</span>
                    </div>
                    <span class="font-semibold text-lg">Family Info</span>
                </a>
            </div>

            <h2 class="text-2xl font-bold mb-4">Announcements</h2>
            <div class="flex flex-col gap-4">
                <?php foreach($announcements as $ann): ?>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-primary/20 flex gap-4 items-center">
                    <span class="material-symbols-outlined text-primary text-3xl">campaign</span>
                    <div>
                        <h3 class="font-semibold"><?= htmlspecialchars($ann['title']) ?></h3>
                        <p class="text-sm text-gray-600 truncate max-w-xl"><?= htmlspecialchars($ann['content']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <script src="js/dashboard.js"></script>
</body>
</html>
