<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SESSION['user_role'] !== 'user') { header('Location: ../auth/login.php'); exit; }
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $headName = $_POST['head_name'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO families (user_id, head_name, address) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $headName, $_POST['address'] ?? '']);
    
    $_SESSION['fam_success'] = "Family registered successfully!";
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeDaP - Family Register</title>
    <link rel="stylesheet" href="../shared/css/sedap.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'primary': '#0058bd', 'surface': '#f7f9fb' }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans antialiased flex h-screen overflow-hidden">
    <?php include '../shared/includes/sidebar_user.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
        <?php include '../shared/includes/header.php'; ?>
        <main class="p-6 max-w-4xl mx-auto w-full">
            <div class="bg-white rounded-3xl shadow-sm p-8 border border-primary/20">
                <div class="flex items-center gap-4 mb-8 pb-4 border-b">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">family_restroom</span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary">Household Registration</h1>
                </div>

                <form method="POST" action="">
                    <h3 class="text-xl font-semibold mb-4 text-primary">Part 1: Ketua Keluarga (Head of Household)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <div>
                            <label class="block text-sm mb-1">Nama Penuh</label>
                            <input type="text" name="head_name" required class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">No. Telefon</label>
                            <input type="text" name="head_phone" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Alamat (Zon)</label>
                            <textarea name="address" rows="2" class="w-full p-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary outline-none"></textarea>
                        </div>
                    </div>

                    <h3 class="text-xl font-semibold mb-4 text-primary">Part 2: Senarai Ahli Keluarga</h3>
                    <div id="members-container" class="space-y-4 mb-4">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm mb-1">Nama</label><input type="text" name="member_name[]" class="w-full p-2 rounded-lg border border-gray-300"></div>
                                <div><label class="block text-sm mb-1">Hubungan</label>
                                    <select name="member_rel[]" class="w-full p-2 rounded-lg border border-gray-300">
                                        <option>Pasangan</option><option>Anak</option><option>Ibu/Bapa</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addMember()" class="flex items-center gap-2 text-primary font-medium hover:bg-primary/5 px-4 py-2 rounded-lg mb-8">
                        <span class="material-symbols-outlined">add</span> Tambah Ahli
                    </button>

                    <button type="submit" class="w-full bg-primary text-white p-4 rounded-full font-bold text-lg hover:bg-primary-dark transition-colors">
                        Simpan & Daftar
                    </button>
                </form>
            </div>
        </main>
    </div>
    <script>
        function addMember() {
            const html = `
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 relative mt-4">
                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-red-500"><span class="material-symbols-outlined">close</span></button>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm mb-1">Nama</label><input type="text" name="member_name[]" class="w-full p-2 rounded-lg border border-gray-300"></div>
                    <div><label class="block text-sm mb-1">Hubungan</label>
                        <select name="member_rel[]" class="w-full p-2 rounded-lg border border-gray-300">
                            <option>Pasangan</option><option>Anak</option><option>Ibu/Bapa</option><option>Lain-lain</option>
                        </select>
                    </div>
                </div>
            </div>`;
            document.getElementById('members-container').insertAdjacentHTML('beforeend', html);
        }
    </script>
</body>
</html>
