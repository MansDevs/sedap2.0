<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Doctor Dashboard";

    $waitCounts = ['red' => 0, 'yellow' => 0, 'green' => 0];
    try {
        $stmt = $pdo->query("SELECT triage_level, COUNT(*) cnt FROM triage_records WHERE status='waiting' GROUP BY triage_level");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $waitCounts[strtolower($row['triage_level'])] = $row['cnt'];
        }
    } catch (Exception $e) {}
    
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
    <link rel="stylesheet" href="css/cdashboard.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../shared/includes/sidebar.php'; ?>
    <div class="sedap-main">
        <?php include '../shared/includes/header.php'; ?>
        <div class="sedap-content">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-triage-red/20 p-6 flex flex-col items-center">
            <span class="text-triage-red font-bold text-lg">Waiting Critical</span>
            <span class="text-4xl font-extrabold text-triage-red mt-2"><?= $waitCounts['red'] ?? 0 ?></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-triage-yellow/20 p-6 flex flex-col items-center">
            <span class="text-triage-yellow font-bold text-lg">Waiting Urgent</span>
            <span class="text-4xl font-extrabold text-triage-yellow mt-2"><?= $waitCounts['yellow'] ?? 0 ?></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-triage-green/20 p-6 flex flex-col items-center">
            <span class="text-triage-green font-bold text-lg">Waiting Standard</span>
            <span class="text-4xl font-extrabold text-triage-green mt-2"><?= $waitCounts['green'] ?? 0 ?></span>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
            <h2 class="text-xl font-bold text-primary mb-4">Urgent Action Queue</h2>
            <div id="urgent-queue" class="space-y-3">
                <p class="text-on-surface-muted text-sm">Loading queue...</p>
            </div>
        </div>
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
                <h2 class="text-xl font-bold text-primary mb-4">Admin Actions</h2>
                <div class="flex gap-4">
                    <a href="triage_counter.php" class="bg-primary hover:bg-primary-dark text-white rounded-full px-6 py-2 transition shadow-sm font-medium">Register Walk-in</a>
                    <button class="bg-surface-dark hover:bg-surface text-primary rounded-full px-6 py-2 transition shadow-sm font-medium border border-primary/20">Bed Assignment</button>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-primary/20 p-6">
                <h2 class="text-xl font-bold text-primary mb-4">Team Comms</h2>
                <div class="space-y-3 text-sm">
                    <div class="p-3 bg-surface rounded-xl border border-primary/10">Dr. Sarah: Please check bed 4.</div>
                    <div class="p-3 bg-surface rounded-xl border border-primary/10">MA John: Triage updated for pt #102.</div>
                </div>
            </div>
        </div>
    </div>
    
            </div>
        </div>
    </div>
    <script src="js/cdashboard.js"></script>
</body>
</html>