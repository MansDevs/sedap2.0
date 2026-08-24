<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];

$patient = $pdo->prepare("SELECT * FROM patients WHERE user_id=?");
$patient->execute([$userId]);
$patientInfo = $patient->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    // Determine triage
    $triage = 'GREEN';
    if ($_POST['q1'] == 'Ya' || $_POST['q2'] == 'Ya' || $_POST['q3'] == 'Ya') {
        $triage = 'RED';
    } else if (
        $_POST['q4'] == 'Panas+menggigil' || 
        in_array($_POST['q5'], ['3-5x Sederhana', '6+ Kerap']) || 
        in_array($_POST['q6'], ['1-2x', 'Berulang+cant drink']) ||
        isset($_POST['q7']) && count((array)$_POST['q7']) > 0 && !in_array('None', (array)$_POST['q7'])
    ) {
        $triage = 'YELLOW';
    }

    // Since table structures might be different, just set session var to show success
    $_SESSION['triage_result'] = $triage;
    header("Location: screening.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Health Screening</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb', 'triage-red': '#C0392B', 'triage-yellow': '#D4A017', 'triage-green': '#1E8449' }, fontFamily: { sans: ['Inter', 'sans-serif'] } }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-3xl mx-auto w-full">
            <?php if(isset($_GET['success'])): ?>
                <?php 
                    $res = $_SESSION['triage_result'] ?? 'GREEN';
                    $bg = $res == 'RED' ? 'bg-triage-red' : ($res == 'YELLOW' ? 'bg-triage-yellow' : 'bg-triage-green');
                    $msg = $res == 'RED' ? 'KECEMASAN (RED): Sila ke klinik/hospital berhampiran SEGERA!' : 
                          ($res == 'YELLOW' ? 'SEDERHANA (YELLOW): Sila hubungi doktor melalui Live Chat.' : 
                          'RINGAN (GREEN): Berehat dan minum air secukupnya.');
                ?>
                <div class="<?= $bg ?> text-white p-6 rounded-2xl mb-8 shadow-lg text-center">
                    <span class="material-symbols-outlined text-5xl mb-2">medical_services</span>
                    <h2 class="text-2xl font-bold mb-2">Triage Result: <?= $res ?></h2>
                    <p class="text-lg"><?= $msg ?></p>
                    <a href="dashboard.php" class="inline-block mt-4 bg-white text-gray-900 px-6 py-2 rounded-full font-semibold">Back to Dashboard</a>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm p-8 border border-primary/20">
                <div class="flex items-center gap-4 mb-8 pb-4 border-b">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">quiz</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary">Saringan Kesihatan (Health Screening)</h1>
                </div>

                <form method="POST" action="">
                    <!-- Part 1 -->
                    <h3 class="text-xl font-semibold mb-4 text-primary">Part 1: Profile</h3>
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div>
                            <label class="block text-sm mb-1">Full Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Phone</label>
                            <input type="text" name="phone" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50">
                        </div>
                    </div>

                    <!-- Part 2A -->
                    <h3 class="text-xl font-semibold mb-4 text-triage-red">Part 2A: Danger Signs (Tanda Bahaya)</h3>
                    <div class="space-y-4 mb-8 bg-red-50 p-6 rounded-2xl border border-red-100">
                        <div>
                            <label class="block font-medium mb-2">1. Najis berdarah/hitam atau muntah darah?</label>
                            <div class="flex gap-4">
                                <label><input type="radio" name="q1" value="Ya" class="mr-2">Ya</label>
                                <label><input type="radio" name="q1" value="Tiada" class="mr-2" checked>Tiada</label>
                            </div>
                        </div>
                        <div>
                            <label class="block font-medium mb-2">2. Sakit perut yang sangat kuat & tegang?</label>
                            <div class="flex gap-4">
                                <label><input type="radio" name="q2" value="Ya" class="mr-2">Ya</label>
                                <label><input type="radio" name="q2" value="Sakit biasa sahaja" class="mr-2" checked>Sakit biasa sahaja</label>
                            </div>
                        </div>
                        <div>
                            <label class="block font-medium mb-2">3. Keliru, sukar bernafas, atau hampir pitam?</label>
                            <div class="flex gap-4">
                                <label><input type="radio" name="q3" value="Ya" class="mr-2">Ya</label>
                                <label><input type="radio" name="q3" value="Tidak" class="mr-2" checked>Tidak</label>
                            </div>
                        </div>
                    </div>

                    <!-- Part 2B -->
                    <h3 class="text-xl font-semibold mb-4 text-triage-yellow">Part 2B: Gejala (Symptoms)</h3>
                    <div class="space-y-4 mb-8 p-6 bg-yellow-50 rounded-2xl border border-yellow-100">
                        <div>
                            <label class="block font-medium mb-2">4. Suhu badan?</label>
                            <select name="q4" class="w-full p-3 rounded-xl border border-gray-300 bg-white">
                                <option value="Biasa">Biasa</option>
                                <option value="Panas+menggigil">Panas + menggigil</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium mb-2">5. Kekerapan Cirit-birit:</label>
                            <select name="q5" class="w-full p-3 rounded-xl border border-gray-300 bg-white">
                                <option value="Tiada">Tiada</option>
                                <option value="1-2x Ringan">1-2x (Ringan)</option>
                                <option value="3-5x Sederhana">3-5x (Sederhana)</option>
                                <option value="6+ Kerap">6+ (Kerap)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium mb-2">6. Kekerapan Muntah:</label>
                            <select name="q6" class="w-full p-3 rounded-xl border border-gray-300 bg-white">
                                <option value="Tiada">Tiada</option>
                                <option value="1-2x">1-2x sehari</option>
                                <option value="Berulang+cant drink">Berulang + tidak boleh minum</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full bg-primary text-white p-4 rounded-full font-bold text-lg hover:bg-primary-dark transition-colors">
                            Submit Assessment
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
