<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor'])) {
    header('Location: ../auth/login.php');
    exit;
}
$page_title = "Triage Counter";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Logic to insert into triage_records
        // $stmt = $pdo->prepare("INSERT INTO triage_records...");
        // header('Location: triage_list.php'); exit;
    }
    
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
    <link rel="stylesheet" href="css/triage_counter.css">
</head>
<body class="bg-surface text-on-surface flex min-h-screen">
    <?php include '../shared/includes/sidebar_doctor.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../shared/includes/header.php'; ?>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($page_title) ?></h1>
                </div>
                
    <form action="" method="POST" class="space-y-8 bg-white p-8 rounded-3xl shadow-sm border border-primary/20">
        <!-- Section 1: Personal Info -->
        <section>
            <h2 class="text-2xl font-bold text-primary mb-4 flex items-center gap-2"><span class="material-symbols-outlined">person</span> Personal Info</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium mb-1">Full Name</label><input type="text" name="name" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary focus:ring-primary" required></div>
                <div><label class="block text-sm font-medium mb-1">IC / SeDaP ID</label><input type="text" name="ic" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary" required></div>
                <div><label class="block text-sm font-medium mb-1">Zone Code</label><input type="text" name="zone" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div><label class="block text-sm font-medium mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div><label class="block text-sm font-medium mb-1">Age</label><input type="number" name="age" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div>
                    <label class="block text-sm font-medium mb-1">Gender</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center gap-1"><input type="radio" name="gender" value="M"> M</label>
                        <label class="flex items-center gap-1"><input type="radio" name="gender" value="F"> F</label>
                    </div>
                </div>
                <div><label class="block text-sm font-medium mb-1">Occupation</label><input type="text" name="occupation" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div>
                    <label class="block text-sm font-medium mb-1">Education Level</label>
                    <select name="education" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary">
                        <option value="None">None</option>
                        <option value="Primary">Primary</option>
                        <option value="Secondary">Secondary</option>
                        <option value="Tertiary">Tertiary</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Section 2: Vitals & Lab -->
        <section>
            <h2 class="text-2xl font-bold text-primary mb-4 flex items-center gap-2"><span class="material-symbols-outlined">favorite</span> Vitals & Lab</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div><label class="block text-sm font-medium mb-1">Temp (°C)</label><input type="number" step="0.1" name="temp" id="val_temp" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div><label class="block text-sm font-medium mb-1">BP (mmHg)</label><input type="text" placeholder="120/80" name="bp" id="val_bp" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div><label class="block text-sm font-medium mb-1">Blood Glucose</label><input type="number" step="0.1" name="glucose" id="val_gluc" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
                <div><label class="block text-sm font-medium mb-1">Lipid Profile</label><input type="number" step="0.1" name="lipid" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Acute Symptoms</label>
                <div class="flex flex-wrap gap-4" id="symptoms_container">
                    <label class="flex items-center gap-1"><input type="checkbox" name="symptoms[]" value="Diarrhea" class="symptom-cb"> Diarrhea</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="symptoms[]" value="Vomiting/Nausea" class="symptom-cb"> Vomiting/Nausea</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="symptoms[]" value="Fever" class="symptom-cb"> Fever</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="symptoms[]" value="Abdominal Pain" class="symptom-cb"> Abdominal Pain</label>
                    <label class="flex items-center gap-1"><input type="checkbox" name="symptoms[]" value="Dizziness" class="symptom-cb"> Dizziness</label>
                </div>
            </div>
        </section>

        <!-- Section 3: History & Notes -->
        <section>
            <h2 class="text-2xl font-bold text-primary mb-4 flex items-center gap-2"><span class="material-symbols-outlined">history</span> Medical History & Notes</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">Medical History</label><textarea name="history" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></textarea></div>
                <div><label class="block text-sm font-medium mb-1">Interview Notes</label><textarea name="notes" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm p-2 border focus:border-primary"></textarea></div>
            </div>
        </section>

        <!-- Section 4: Triage Rating -->
        <section class="bg-surface rounded-2xl p-6 border border-primary/10">
            <h2 class="text-2xl font-bold text-primary mb-4 flex items-center gap-2"><span class="material-symbols-outlined">local_hospital</span> Triage Rating</h2>
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1 text-center">
                    <div id="auto-badge" class="w-48 h-48 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold shadow-inner border-4 border-white mx-auto transition-colors duration-500">PENDING</div>
                    <p class="text-sm mt-2 font-medium text-on-surface-muted">Auto-determined Level</p>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-2">Manual Override</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50"><input type="radio" name="manual_level" value="Red"> 🔴 Red (Severe)</label>
                        <label class="flex items-center gap-2 p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50"><input type="radio" name="manual_level" value="Yellow"> 🟡 Yellow (Moderate)</label>
                        <label class="flex items-center gap-2 p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50"><input type="radio" name="manual_level" value="Green" checked> 🟢 Green (Mild)</label>
                    </div>
                </div>
            </div>
        </section>

        <div class="text-right">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-full px-8 py-3 transition shadow-md font-bold text-lg">Submit Triage</button>
        </div>
    </form>

            </div>
        </main>
    </div>
    <script src="js/triage_counter.js"></script>
</body>
</html>