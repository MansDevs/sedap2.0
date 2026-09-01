import os
import textwrap

BASE_DIR = r"e:\Degree\shortsemYear1\SeDaP App\sedap2\xampp - backup\htdocs\sedap\sedap2.0\pages"

def write_file(path, content):
    full_path = os.path.join(BASE_DIR, path)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "w", encoding="utf-8") as f:
        f.write(content.strip() + "\n")
    print(f"Created: {path}")

# ==========================================
# AUTH PAGES
# ==========================================
auth_login_php = """
<?php
require_once '../config/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active'");
    $stmt->execute([$input, $input]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['dark_mode'] = $user['dark_mode'] ?? 0;
        
        $redirect = match($user['role']) {
            'admin' => '../admin/dashboard.php',
            'doctor' => '../doctor/cdashboard.php',
            'volunteer' => '../volunteer/dashboard.php',
            'user' => '../dashboard/dashboard.php',
            default => 'login.php'
        };
        header("Location: $redirect"); 
        exit;
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/css/sedap.css">
  <link rel="stylesheet" href="css/login.css">
</head>
<body class="bg-[#F6E5D1] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-4xl flex overflow-hidden auth-card">
        <!-- Left Panel -->
        <div class="w-1/2 bg-gradient-to-br from-[#087383] to-[#0a9db3] p-12 text-white flex flex-col justify-center items-center relative overflow-hidden hidden md:flex">
            <h1 class="text-5xl font-bold mb-4 z-10">SeDaP</h1>
            <p class="text-lg text-center z-10 text-white/90">Sistem e-Data Pesakit<br>Your comprehensive healthcare companion.</p>
            <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -top-10 -left-10 w-48 h-48 bg-[#1E8449]/20 rounded-full blur-xl"></div>
        </div>
        <!-- Right Panel -->
        <div class="w-full md:w-1/2 p-10 flex flex-col justify-center">
            <h2 class="text-2xl font-bold text-[#1a1a1a] mb-2">Welcome Back</h2>
            <p class="text-[#5a5a5a] mb-8">Please login to your account.</p>
            
            <?php if($error): ?>
                <div class="bg-[#C0392B]/10 text-[#C0392B] p-3 rounded-xl mb-6 text-sm font-medium"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Role Selector -->
                <div class="flex flex-wrap gap-2 mb-6" id="role-selector">
                    <button type="button" class="role-btn active px-4 py-2 rounded-full text-sm font-medium border transition-colors" data-role="user">Patient</button>
                    <button type="button" class="role-btn px-4 py-2 rounded-full text-sm font-medium border transition-colors" data-role="admin">Admin</button>
                    <button type="button" class="role-btn px-4 py-2 rounded-full text-sm font-medium border transition-colors" data-role="doctor">Medical</button>
                    <button type="button" class="role-btn px-4 py-2 rounded-full text-sm font-medium border transition-colors" data-role="volunteer">Volunteer</button>
                </div>
                <input type="hidden" name="role" id="role-input" value="user">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-[#1a1a1a] mb-1">Username or Email</label>
                    <input type="text" name="username" required class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#087383]/50 focus:border-[#087383] transition-all bg-[#F6E5D1]/30">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-[#1a1a1a] mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#087383]/50 focus:border-[#087383] transition-all bg-[#F6E5D1]/30">
                </div>
                
                <div class="flex justify-between items-center mb-6">
                    <a href="forgotpass.php" class="text-sm font-medium text-[#087383] hover:text-[#0a9db3]">Forgot password?</a>
                </div>

                <button type="submit" class="w-full bg-[#087383] text-white py-3 rounded-full font-semibold hover:bg-[#065a68] transition-colors shadow-md hover:shadow-lg">
                    Log Masuk / Login
                </button>
            </form>
            
            <div class="mt-8 text-center text-sm text-[#5a5a5a]">
                Don't have an account? <a href="register.php" class="font-semibold text-[#087383] hover:underline">Register here</a>
            </div>
        </div>
    </div>
    <script src="js/login.js"></script>
</body>
</html>
"""

auth_login_css = """
body { font-family: 'Inter', sans-serif; }
.role-btn { border-color: #e5e7eb; color: #5a5a5a; background: white; }
.role-btn.active { background: #087383; color: white; border-color: #087383; }
.role-btn:hover:not(.active) { background: #F6E5D1; border-color: #087383; }
"""

auth_login_js = """
document.addEventListener('DOMContentLoaded', () => {
    const btns = document.querySelectorAll('.role-btn');
    const input = document.getElementById('role-input');
    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            input.value = btn.dataset.role;
        });
    });
});
"""

write_file('auth/login.php', auth_login_php)
write_file('auth/css/login.css', auth_login_css)
write_file('auth/js/login.js', auth_login_js)


auth_register_php = """
<?php
require_once '../config/db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $contact, $username, $hash, $role])) {
                $success = 'Registration successful. You can now login.';
            }
        } catch (PDOException $e) {
            $error = 'Error registering user. Username or email might be taken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/css/sedap.css">
  <link rel="stylesheet" href="css/register.css">
</head>
<body class="bg-[#F6E5D1] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-4xl flex overflow-hidden">
        <!-- Left Panel -->
        <div class="w-1/2 bg-gradient-to-br from-[#087383] to-[#0a9db3] p-12 text-white flex flex-col justify-center items-center relative overflow-hidden hidden md:flex">
            <h1 class="text-5xl font-bold mb-4 z-10">Join SeDaP</h1>
            <p class="text-lg text-center z-10 text-white/90">Be part of the comprehensive healthcare network.</p>
        </div>
        <!-- Right Panel -->
        <div class="w-full md:w-1/2 p-8 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-[#1a1a1a] mb-2">Create Account</h2>
            <p class="text-[#5a5a5a] mb-6">Fill in the details below to register.</p>
            
            <?php if($error): ?><div class="bg-[#C0392B]/10 text-[#C0392B] p-3 rounded-xl mb-4 text-sm font-medium"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if($success): ?><div class="bg-[#1E8449]/10 text-[#1E8449] p-3 rounded-xl mb-4 text-sm font-medium"><?= htmlspecialchars($success) ?> <a href="login.php" class="underline">Login here</a></div><?php endif; ?>

            <form method="POST" action="">
                <div class="flex flex-wrap gap-2 mb-4" id="role-selector">
                    <button type="button" class="role-btn active px-3 py-1 rounded-full text-xs font-medium border" data-role="user">Patient</button>
                    <button type="button" class="role-btn px-3 py-1 rounded-full text-xs font-medium border" data-role="admin">Admin</button>
                    <button type="button" class="role-btn px-3 py-1 rounded-full text-xs font-medium border" data-role="doctor">Medical</button>
                    <button type="button" class="role-btn px-3 py-1 rounded-full text-xs font-medium border" data-role="volunteer">Volunteer</button>
                </div>
                <input type="hidden" name="role" id="role-input" value="user">

                <div class="space-y-3">
                    <div><input type="text" name="name" required placeholder="Full Name" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                    <div><input type="email" name="email" required placeholder="Email Address" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                    <div><input type="text" name="contact" required placeholder="Contact Number" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                    <div><input type="text" name="username" required placeholder="Username" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                    <div><input type="password" name="password" required placeholder="Password" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                    <div><input type="password" name="confirm" required placeholder="Confirm Password" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:border-[#087383] bg-[#F6E5D1]/30"></div>
                </div>

                <button type="submit" class="w-full bg-[#087383] text-white py-3 rounded-full font-semibold hover:bg-[#065a68] transition-colors mt-6">Register</button>
            </form>
            
            <div class="mt-6 text-center text-sm text-[#5a5a5a]">
                Already have an account? <a href="login.php" class="font-semibold text-[#087383] hover:underline">Login</a>
            </div>
        </div>
    </div>
    <script src="js/register.js"></script>
</body>
</html>
"""
write_file('auth/register.php', auth_register_php)
write_file('auth/css/register.css', ".role-btn { border-color: #e5e7eb; color: #5a5a5a; background: white; } .role-btn.active { background: #087383; color: white; border-color: #087383; }")
write_file('auth/js/register.js', auth_login_js) # reuse same js logic for role btn

auth_forgot_php = """
<?php
require_once '../config/db.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND email=?");
        $stmt->execute([$username, $email]);
        if ($user = $stmt->fetch()) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->execute([$hash, $user['id']]);
            $success = 'Password updated successfully. You can now login.';
        } else {
            $error = 'Username and Email combination not found.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — SeDaP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F6E5D1] min-h-screen flex items-center justify-center p-4" style="font-family: 'Inter', sans-serif;">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-[#087383] mb-6">Reset Password</h2>
        <?php if($error): ?><div class="text-red-600 mb-4 text-center text-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="text-green-600 mb-4 text-center text-sm"><?= htmlspecialchars($success) ?> <br><a href="login.php" class="underline text-[#087383]">Login</a></div><?php endif; ?>
        <form method="POST" action="" class="space-y-4">
            <input type="text" name="username" required placeholder="Username" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="email" name="email" required placeholder="Email used for account" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="password" name="password" required placeholder="New Password" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="password" name="confirm" required placeholder="Confirm New Password" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <button type="submit" class="w-full bg-[#087383] text-white py-3 rounded-full font-semibold hover:bg-[#065a68]">Reset Password</button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-sm text-[#087383]">Back to Login</a>
        </div>
    </div>
</body>
</html>
"""
write_file('auth/forgotpass.php', auth_forgot_php)
write_file('auth/css/forgotpass.css', "")
write_file('auth/js/forgotpass.js', "")


# ==========================================
# ADMIN PORTAL
# ==========================================

admin_shell = """
<?php
session_start();
require_once '{{depth}}config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: {{depth}}auth/login.php'); exit; }
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{title}} — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{depth}}shared/css/sedap.css">
  <link rel="stylesheet" href="css/{{cssname}}.css">
  {{extra_head}}
</head>
<body class="bg-[#F6E5D1] text-[#1a1a1a]">
<div class="flex h-screen overflow-hidden">
  <?php include '{{depth}}shared/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col h-screen overflow-hidden">
    <?php include '{{depth}}shared/includes/header.php'; ?>
    <main class="flex-1 overflow-y-auto p-6">
      {{content}}
    </main>
  </div>
</div>
<script src="js/{{jsname}}.js"></script>
</body>
</html>
"""

def generate_admin_page(path, title, content, depth="../", extra_head=""):
    cssname = os.path.basename(path).replace(".php", "")
    jsname = cssname
    php_content = admin_shell
    php_content = php_content.replace("{{depth}}", depth)
    php_content = php_content.replace("{{title}}", title)
    php_content = php_content.replace("{{content}}", content)
    php_content = php_content.replace("{{cssname}}", cssname)
    php_content = php_content.replace("{{jsname}}", jsname)
    php_content = php_content.replace("{{extra_head}}", extra_head)
    
    write_file(f"admin/{path}", php_content)
    write_file(f"admin/{os.path.dirname(path)}/css/{cssname}.css", "")
    write_file(f"admin/{os.path.dirname(path)}/js/{jsname}.js", "")


dashboard_content = """
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
    <div class="space-x-2">
        <a href="triage/add.php" class="bg-[#087383] text-white px-4 py-2 rounded-full text-sm font-medium inline-flex items-center gap-2 hover:bg-[#065a68]"><span class="material-symbols-outlined text-sm">add</span> New Triage Entry</a>
        <a href="announcements/index.php" class="bg-white border border-[#087383] text-[#087383] px-4 py-2 rounded-full text-sm font-medium hover:bg-[#F6E5D1]">New Announcement</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#087383]/10">
        <div class="text-[#5a5a5a] text-sm font-medium mb-2">Total Patients</div>
        <div class="text-3xl font-bold text-[#087383]">1,248</div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#087383]/10">
        <div class="text-[#5a5a5a] text-sm font-medium mb-2">Today's Triage</div>
        <div class="flex gap-4 items-end mt-2">
            <div class="flex flex-col items-center"><div class="w-3 h-3 rounded-full bg-[#C0392B] mb-1"></div><span class="font-bold">5</span></div>
            <div class="flex flex-col items-center"><div class="w-3 h-3 rounded-full bg-[#D4A017] mb-1"></div><span class="font-bold">12</span></div>
            <div class="flex flex-col items-center"><div class="w-3 h-3 rounded-full bg-[#1E8449] mb-1"></div><span class="font-bold">45</span></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#087383]/10">
        <div class="text-[#5a5a5a] text-sm font-medium mb-2">Active Announcements</div>
        <div class="text-3xl font-bold text-[#087383]">3</div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#087383]/10">
        <div class="text-[#5a5a5a] text-sm font-medium mb-2">Total Personnel</div>
        <div class="text-3xl font-bold text-[#087383]">86</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-[#087383]/10 p-6">
        <div class="flex justify-between mb-4">
            <h2 class="text-lg font-bold">Recent Triage</h2>
            <a href="triage/index.php" class="text-sm text-[#087383]">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="text-[#5a5a5a] border-b"><th class="pb-2">Patient</th><th class="pb-2">Code</th><th class="pb-2">Time</th></tr></thead>
                <tbody>
                    <!-- Mock Data -->
                    <tr class="border-b last:border-0 border-l-4 border-l-[#C0392B]"><td class="py-3 px-2">Ahmad Bin Abu</td><td><span class="px-2 py-1 bg-[#C0392B]/10 text-[#C0392B] rounded-full text-xs font-bold">RED</span></td><td class="text-[#5a5a5a]">10 mins ago</td></tr>
                    <tr class="border-b last:border-0 border-l-4 border-l-[#D4A017]"><td class="py-3 px-2">Siti Aminah</td><td><span class="px-2 py-1 bg-[#D4A017]/10 text-[#D4A017] rounded-full text-xs font-bold">YELLOW</span></td><td class="text-[#5a5a5a]">25 mins ago</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-[#087383]/10 p-6">
        <h2 class="text-lg font-bold mb-4">Recent Announcements</h2>
        <div class="space-y-4">
            <div class="p-3 bg-[#F6E5D1]/30 rounded-xl">
                <div class="font-semibold text-sm">System Maintenance</div>
                <div class="text-xs text-[#5a5a5a] mt-1">Tomorrow 10:00 PM</div>
            </div>
            <div class="p-3 bg-[#F6E5D1]/30 rounded-xl">
                <div class="font-semibold text-sm">New Triage SOP</div>
                <div class="text-xs text-[#5a5a5a] mt-1">Updated yesterday</div>
            </div>
        </div>
    </div>
</div>
"""
generate_admin_page("dashboard.php", "Dashboard", dashboard_content)


announcements_content = """
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Announcements</h1>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-[#087383]/10 p-6 mb-6">
    <h2 class="font-semibold mb-4 text-lg">Create New Announcement</h2>
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><label class="block text-sm mb-1">Title</label><input type="text" name="title" class="w-full rounded-xl border-gray-200" required></div>
            <div><label class="block text-sm mb-1">Status</label><select name="status" class="w-full rounded-xl border-gray-200"><option value="published">Published</option><option value="draft">Draft</option></select></div>
        </div>
        <div class="mb-4">
            <label class="block text-sm mb-1">Content</label>
            <textarea name="content" rows="3" class="w-full rounded-xl border-gray-200" required></textarea>
        </div>
        <button type="submit" class="bg-[#087383] text-white px-6 py-2 rounded-full font-medium hover:bg-[#065a68]">Save Announcement</button>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-[#087383]/10 p-6">
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b"><th class="pb-2">Title</th><th class="pb-2">Status</th><th class="pb-2">Date</th><th class="pb-2 text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td class="py-3">System Update</td><td><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Published</span></td><td>2024-03-01</td><td class="text-right"><button class="text-[#087383]">Edit</button> | <button class="text-red-500">Delete</button></td></tr>
        </tbody>
    </table>
</div>
"""
generate_admin_page("announcements/index.php", "Announcements", announcements_content, "../../")

posters_content = """
<div class="mb-6"><h1 class="text-2xl font-bold">Poster Editor</h1></div>
<div class="flex gap-6 h-[600px]">
    <div class="w-2/3 bg-white rounded-2xl border flex items-center justify-center overflow-hidden bg-gray-50">
        <canvas id="posterCanvas" width="800" height="550" class="border shadow-sm"></canvas>
    </div>
    <div class="w-1/3 bg-white rounded-2xl border p-6 flex flex-col">
        <h2 class="font-bold mb-4">Toolbar</h2>
        <div class="grid grid-cols-2 gap-2 mb-6">
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="addText()">Add Text</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="addRect()">Add Rectangle</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50 text-red-500" onclick="deleteSelected()">Delete</button>
            <button class="border p-2 rounded text-sm hover:bg-gray-50" onclick="canvas.clear()">Clear</button>
        </div>
        <hr class="my-4">
        <h2 class="font-bold mb-4">Save Poster</h2>
        <input type="text" placeholder="Poster Title" class="w-full mb-3 rounded border-gray-200">
        <select class="w-full mb-4 rounded border-gray-200"><option>Draft</option><option>Published</option></select>
        <button class="w-full bg-[#087383] text-white py-2 rounded-full mb-2">Save</button>
    </div>
</div>
"""
generate_admin_page("posters/index.php", "Poster Editor", posters_content, "../../", '<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>')

write_file("admin/posters/js/index.js", "let canvas = new fabric.Canvas('posterCanvas'); canvas.backgroundColor = '#ffffff'; canvas.renderAll(); function addText() { canvas.add(new fabric.IText('New Text', { left: 100, top: 100 })); } function addRect() { canvas.add(new fabric.Rect({ left: 100, top: 100, width: 100, height: 100, fill: '#087383' })); } function deleteSelected() { canvas.remove(canvas.getActiveObject()); }")

personnel_content = """
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Personnel Management</h1>
    <div class="space-x-2">
        <button class="bg-[#087383] text-white px-4 py-2 rounded-full text-sm font-medium">+ Add Personnel</button>
        <button class="bg-white border border-gray-300 px-4 py-2 rounded-full text-sm font-medium">Export CSV</button>
    </div>
</div>
<div class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex gap-4 border-b mb-4">
        <button class="px-4 py-2 border-b-2 border-[#087383] text-[#087383] font-semibold">All</button>
        <button class="px-4 py-2 text-gray-500">Staff</button>
        <button class="px-4 py-2 text-gray-500">Volunteers</button>
    </div>
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b"><th class="pb-2">Name</th><th class="pb-2">Role</th><th class="pb-2">Phone</th><th class="pb-2">Status</th><th class="pb-2 text-right">Actions</th></tr></thead>
        <tbody>
            <tr><td class="py-3">Dr. Ali</td><td>Doctor</td><td>012-3456789</td><td><span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Active</span></td><td class="text-right"><button class="text-[#087383]">Edit</button></td></tr>
        </tbody>
    </table>
</div>
"""
generate_admin_page("personnel/index.php", "Personnel", personnel_content, "../../")


triage_content = """
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Live Triage</h1>
    <a href="add.php" class="bg-[#087383] text-white px-4 py-2 rounded-full text-sm font-medium">+ New Triage</a>
</div>
<div class="flex gap-2 mb-4">
    <button class="px-4 py-1 rounded-full bg-gray-200 text-sm font-medium">All</button>
    <button class="px-4 py-1 rounded-full bg-[#C0392B]/10 text-[#C0392B] text-sm font-medium border border-[#C0392B]/20">RED</button>
    <button class="px-4 py-1 rounded-full bg-[#D4A017]/10 text-[#D4A017] text-sm font-medium border border-[#D4A017]/20">YELLOW</button>
    <button class="px-4 py-1 rounded-full bg-[#1E8449]/10 text-[#1E8449] text-sm font-medium border border-[#1E8449]/20">GREEN</button>
</div>
<div class="bg-white rounded-2xl shadow-sm border">
    <table class="w-full text-left text-sm">
        <thead><tr class="text-[#5a5a5a] border-b bg-gray-50 rounded-t-2xl"><th class="p-3">Patient</th><th class="p-3">Level</th><th class="p-3">Complaint</th><th class="p-3">Vitals</th><th class="p-3">Time</th></tr></thead>
        <tbody>
            <tr class="border-b border-l-4 border-l-[#C0392B]"><td class="p-3 font-medium">Ahmad (IC: 9010...)</td><td class="p-3"><span class="px-2 py-1 bg-[#C0392B] text-white rounded text-xs font-bold">RED</span></td><td class="p-3">Chest Pain</td><td class="p-3 text-xs">Temp: 38°C<br>BP: 140/90</td><td class="p-3 text-xs text-gray-500">10:30 AM</td></tr>
        </tbody>
    </table>
</div>
"""
generate_admin_page("triage/index.php", "Live Triage", triage_content, "../../")

triage_add_content = """
<div class="mb-6"><h1 class="text-2xl font-bold">New Triage Entry</h1></div>
<div class="bg-white rounded-2xl shadow-sm border p-6">
    <form action="" method="POST">
        <!-- Section 1 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#087383]">1. Personal Info</h3>
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div><label class="block text-sm mb-1">Full Name</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">IC / ID</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Age</label><input type="number" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Gender</label>
                <div class="flex gap-4 mt-2"><label><input type="radio" name="gender" value="M"> Male</label><label><input type="radio" name="gender" value="F"> Female</label></div>
            </div>
        </div>
        
        <!-- Section 2 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#087383]">2. Vitals & Symptoms</h3>
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div><label class="block text-sm mb-1">Temp (°C)</label><input type="number" step="0.1" id="t_temp" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">BP</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Glucose</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
            <div><label class="block text-sm mb-1">Lipid</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
        </div>
        <div class="mb-6">
            <label class="block text-sm mb-2 font-medium">Symptoms Check</label>
            <div class="flex gap-4 flex-wrap">
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="diarrhea"> Cirit-birit</label>
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="vomit"> Muntah</label>
                <label class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded"><input type="checkbox" class="symptom-cb" value="fever"> Demam</label>
            </div>
        </div>

        <!-- Section 4 -->
        <h3 class="text-lg font-bold border-b pb-2 mb-4 text-[#087383]">3. Triage Code</h3>
        <div class="p-6 bg-gray-50 rounded-xl mb-6 text-center border-2 border-dashed" id="triage-result">
            <div class="text-sm text-gray-500 mb-2">Auto-calculated Code</div>
            <div class="text-3xl font-bold px-6 py-2 rounded-full inline-block bg-gray-200" id="triage-badge">PENDING</div>
        </div>
        
        <button type="submit" class="w-full bg-[#087383] text-white py-3 rounded-full font-bold text-lg hover:bg-[#065a68]">Submit Triage</button>
    </form>
</div>
"""
generate_admin_page("triage/add.php", "New Triage", triage_add_content, "../../")
write_file("admin/triage/js/add.js", """
document.addEventListener('input', () => {
    const temp = parseFloat(document.getElementById('t_temp').value) || 0;
    const symptoms = document.querySelectorAll('.symptom-cb:checked');
    const badge = document.getElementById('triage-badge');
    
    let isRed = false, isYellow = false;
    
    let hasFever = temp > 38 || document.querySelector('.symptom-cb[value="fever"]').checked;
    let hasVomit = document.querySelector('.symptom-cb[value="vomit"]').checked;
    let hasDiarrhea = document.querySelector('.symptom-cb[value="diarrhea"]').checked;
    
    if (hasFever && hasVomit && hasDiarrhea) isRed = true;
    else if (hasFever || symptoms.length >= 2) isYellow = true;
    
    if (isRed) { badge.textContent = 'RED'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#C0392B] text-white'; }
    else if (isYellow) { badge.textContent = 'YELLOW'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#D4A017] text-white'; }
    else { badge.textContent = 'GREEN'; badge.className = 'text-3xl font-bold px-6 py-2 rounded-full inline-block bg-[#1E8449] text-white'; }
});
""")


screening_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Screening Responses</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Table coming soon...</div>"""
generate_admin_page("screening/index.php", "Screening", screening_content, "../../")

patients_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Patients</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Patient CRUD here.</div>"""
generate_admin_page("patients/index.php", "Patients", patients_content, "../../")

family_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Family Records</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Family viewer here.</div>"""
generate_admin_page("family/index.php", "Families", family_content, "../../")

health_bristol_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Bristol Stool Chart Editor</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Forms for Types 1-7.</div>"""
generate_admin_page("health/bristol.php", "Bristol Editor", health_bristol_content, "../../")

health_water_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Water Intake Logs</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Table viewer.</div>"""
generate_admin_page("health/water.php", "Water Logs", health_water_content, "../../")

health_mood_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Mood Journals</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Table viewer.</div>"""
generate_admin_page("health/mood.php", "Mood Journals", health_mood_content, "../../")

health_medicine_content = """<div class="mb-6"><h1 class="text-2xl font-bold">Medicine Logs</h1></div><div class="bg-white p-6 rounded-2xl border shadow-sm">Table viewer.</div>"""
generate_admin_page("health/medicine.php", "Medicine Logs", health_medicine_content, "../../")


settings_content = """
<div class="mb-6"><h1 class="text-2xl font-bold">Settings</h1></div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="font-bold mb-4">Account Preferences</h2>
        <div class="flex justify-between items-center py-3 border-b">
            <span>Dark Mode</span>
            <button class="bg-gray-200 w-12 h-6 rounded-full relative"><div class="bg-white w-5 h-5 rounded-full absolute left-0.5 top-0.5 shadow"></div></button>
        </div>
        <div class="py-4">
            <a href="myaccount.php" class="text-[#087383] font-medium block mb-2">Edit My Profile</a>
            <a href="../auth/login.php" class="text-red-500 font-medium block">Log Out</a>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border p-6">
        <h2 class="font-bold mb-4">Reset Password</h2>
        <form>
            <input type="password" placeholder="Current Password" class="w-full mb-3 rounded-xl border-gray-200">
            <input type="password" placeholder="New Password" class="w-full mb-3 rounded-xl border-gray-200">
            <input type="password" placeholder="Confirm New Password" class="w-full mb-4 rounded-xl border-gray-200">
            <button class="bg-[#087383] text-white px-6 py-2 rounded-full font-medium w-full">Update Password</button>
        </form>
    </div>
</div>
"""
generate_admin_page("settings.php", "Settings", settings_content, "../")

myaccount_content = """
<div class="mb-6"><h1 class="text-2xl font-bold">My Account</h1></div>
<div class="bg-white rounded-2xl shadow-sm border p-6 max-w-2xl">
    <div class="flex items-center gap-6 mb-8">
        <div class="w-24 h-24 rounded-full bg-[#087383] text-white flex items-center justify-center text-3xl font-bold">A</div>
        <div>
            <h2 class="text-xl font-bold"><?= $userName ?></h2>
            <div class="text-sm text-gray-500 mb-2">Administrator</div>
            <span class="px-2 py-1 bg-[#1E8449]/10 text-[#1E8449] rounded text-xs font-bold">ACTIVE</span>
        </div>
    </div>
    <form class="space-y-4">
        <div><label class="block text-sm mb-1 text-gray-600">Full Name</label><input type="text" value="<?= $userName ?>" class="w-full rounded-xl border-gray-200"></div>
        <div><label class="block text-sm mb-1 text-gray-600">Email</label><input type="email" class="w-full rounded-xl border-gray-200"></div>
        <div><label class="block text-sm mb-1 text-gray-600">Contact Number</label><input type="text" class="w-full rounded-xl border-gray-200"></div>
        <button class="bg-[#087383] text-white px-6 py-2 rounded-full font-medium mt-4">Save Changes</button>
    </form>
</div>
"""
generate_admin_page("myaccount.php", "My Account", myaccount_content, "../")

print("Files generated successfully.")
