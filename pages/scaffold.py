import os

base_path = r'e:\Degree\shortsemYear1\SeDaP App\sedap2\xampp - backup\htdocs\sedap\sedap2.0\pages'

def ensure_dir(d):
    if not os.path.exists(d):
        os.makedirs(d)

def write_file(rel_path, content):
    full = os.path.join(base_path, rel_path)
    ensure_dir(os.path.dirname(full))
    with open(full, 'w', encoding='utf-8') as f:
        f.write(content.strip() + '\n')
    print("Created:", rel_path)

def head_html(title, css_path):
    return f"""  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>{title} — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {{
      darkMode: 'class',
      theme: {{
        extend: {{
          colors: {{
            'primary': '#087383', 'primary-dark': '#065a68', 'primary-light': '#0a9db3',
            'surface': '#F6E5D1', 'surface-dark': '#eddcc0',
            'on-primary': '#ffffff', 'on-surface': '#1a1a1a', 'on-surface-muted': '#5a5a5a',
            'triage-red': '#C0392B', 'triage-yellow': '#D4A017', 'triage-green': '#1E8449',
          }},
          fontFamily: {{ sans: ['Inter', 'sans-serif'] }},
          borderRadius: {{ 'DEFAULT': '0.75rem', 'xl': '1rem', '2xl': '1.5rem', '3xl': '2rem', 'full': '9999px' }}
        }}
      }}
    }}
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../../shared/css/sedap.css"/>
  <link rel="stylesheet" href="{css_path}"/>"""

# --- Auth Pages ---
login_php = """<?php
session_start();
require_once '../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active'");
    $stmt->execute([$input, $input]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['dark_mode'] = $user['dark_mode'];
        $redirect = match($user['role']) {
            'admin' => '../admin/dashboard.php',
            'doctor' => '../doctor/cdashboard.php',
            'volunteer' => '../volunteer/dashboard.php',
            'user' => '../dashboard/dashboard.php',
            default => '../auth/login.php'
        };
        header("Location: $redirect"); exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
""" + head_html("Login", "css/login.css") + """
</head>
<body class="bg-surface text-on-surface h-screen flex">
  <div class="hidden lg:flex flex-col justify-center items-center w-1/2 bg-primary text-white p-12">
    <h1 class="text-5xl font-bold mb-4">SeDaP</h1>
    <p class="text-xl">Sistem e-Data Pesakit</p>
  </div>
  <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-surface">
    <div class="w-full max-w-md bg-white p-8 rounded-3xl shadow-sm border border-primary/20">
      <h2 class="text-3xl font-bold text-primary mb-6 text-center">Log Masuk</h2>
      <?php if($error): ?><p class="text-triage-red text-center mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="POST" action="login.php" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Role</label>
          <div class="flex gap-2">
            <label class="flex-1 cursor-pointer"><input type="radio" name="role" value="admin" class="peer sr-only" checked/><span class="block text-center p-2 rounded-full border peer-checked:bg-primary peer-checked:text-white text-sm">Admin</span></label>
            <label class="flex-1 cursor-pointer"><input type="radio" name="role" value="doctor" class="peer sr-only"/><span class="block text-center p-2 rounded-full border peer-checked:bg-primary peer-checked:text-white text-sm">Doctor</span></label>
            <label class="flex-1 cursor-pointer"><input type="radio" name="role" value="volunteer" class="peer sr-only"/><span class="block text-center p-2 rounded-full border peer-checked:bg-primary peer-checked:text-white text-sm">Volunteer</span></label>
            <label class="flex-1 cursor-pointer"><input type="radio" name="role" value="user" class="peer sr-only"/><span class="block text-center p-2 rounded-full border peer-checked:bg-primary peer-checked:text-white text-sm">Patient</span></label>
          </div>
        </div>
        <div><label class="block text-sm font-medium mb-1">Username / Email</label><input type="text" name="username" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="password" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div class="flex justify-end"><a href="forgotpass.php" class="text-sm text-primary hover:underline">Forgot Password?</a></div>
        <button type="submit" class="w-full bg-primary text-white rounded-full py-3 font-semibold hover:bg-primary-dark transition">Log Masuk</button>
      </form>
      <p class="mt-6 text-center text-sm">New user? <a href="register.php" class="text-primary font-semibold hover:underline">Register</a></p>
    </div>
  </div>
  <script src="js/login.js"></script>
</body>
</html>
"""
write_file('auth/login.php', login_php)
write_file('auth/css/login.css', '/* css */')
write_file('auth/js/login.js', '/* js */')

register_php = """<?php
session_start();
require_once '../config/db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$name, $email, $phone, $username, $hash, $role])) {
        header("Location: login.php?registered=1"); exit;
    } else {
        $msg = "Error registering user.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
""" + head_html("Register", "css/register.css") + """
</head>
<body class="bg-surface text-on-surface h-screen flex">
  <div class="hidden lg:flex flex-col justify-center items-center w-1/2 bg-primary text-white p-12">
    <h1 class="text-5xl font-bold mb-4">SeDaP</h1>
    <p class="text-xl">Register Account</p>
  </div>
  <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-surface overflow-y-auto">
    <div class="w-full max-w-md bg-white p-8 rounded-3xl shadow-sm border border-primary/20">
      <h2 class="text-3xl font-bold text-primary mb-6 text-center">Daftar</h2>
      <?php if($msg): ?><p class="text-triage-red text-center mb-4"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <form method="POST" action="register.php" class="space-y-4">
        <div><label class="block text-sm font-medium mb-1">Full Name</label><input type="text" name="name" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Contact Number</label><input type="text" name="phone" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Username</label><input type="text" name="username" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="password" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Confirm Password</label><input type="password" name="cpassword" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div>
          <label class="block text-sm font-medium mb-1">Role</label>
          <select name="role" class="w-full rounded-full border-gray-300 px-4 py-2"><option value="user">Patient</option><option value="volunteer">Volunteer</option><option value="doctor">Doctor/MA/Nurse</option><option value="admin">Admin</option></select>
        </div>
        <button type="submit" class="w-full bg-primary text-white rounded-full py-3 font-semibold hover:bg-primary-dark transition">Daftar</button>
      </form>
      <p class="mt-6 text-center text-sm">Already have an account? <a href="login.php" class="text-primary font-semibold hover:underline">Log In</a></p>
    </div>
  </div>
  <script src="js/register.js"></script>
</body>
</html>
"""
write_file('auth/register.php', register_php)
write_file('auth/css/register.css', '/* css */')
write_file('auth/js/register.js', '/* js */')

forgot_php = """<?php
session_start();
require_once '../config/db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND email=?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE username=?")->execute([$hash, $username]);
        header("Location: login.php?reset=1"); exit;
    } else {
        $msg = "Invalid username or email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
""" + head_html("Forgot Password", "css/forgotpass.css") + """
</head>
<body class="bg-surface text-on-surface h-screen flex justify-center items-center">
    <div class="w-full max-w-md bg-white p-8 rounded-3xl shadow-sm border border-primary/20">
      <h2 class="text-3xl font-bold text-primary mb-6 text-center">Reset Password</h2>
      <?php if($msg): ?><p class="text-triage-red text-center mb-4"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <form method="POST" class="space-y-4">
        <div><label class="block text-sm font-medium mb-1">Username</label><input type="text" name="username" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <div><label class="block text-sm font-medium mb-1">New Password</label><input type="password" name="password" class="w-full rounded-full border-gray-300 px-4 py-2" required /></div>
        <button type="submit" class="w-full bg-primary text-white rounded-full py-3 font-semibold hover:bg-primary-dark transition">Reset</button>
      </form>
      <p class="mt-6 text-center text-sm"><a href="login.php" class="text-primary font-semibold hover:underline">Back to Login</a></p>
    </div>
</body>
</html>
"""
write_file('auth/forgotpass.php', forgot_php)
write_file('auth/css/forgotpass.css', '/* css */')
write_file('auth/js/forgotpass.js', '/* js */')

# --- Admin Pages ---
admin_pages = [
    ('dashboard.php', 'Admin Dashboard', 'css/dashboard.css', 'js/dashboard.js', '../', '<h2 class="text-2xl font-bold mb-4">Admin Dashboard</h2><p>Welcome to Admin Portal</p>'),
    ('announcements/index.php', 'Announcements', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Announcements</h2>'),
    ('posters/index.php', 'Posters', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Posters Editor</h2>'),
    ('personnel/index.php', 'Personnel', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Personnel Management</h2>'),
    ('triage/index.php', 'Triage List', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Live Triage List</h2>'),
    ('triage/add.php', 'New Triage', 'css/add.css', 'js/add.js', '../../', '<h2 class="text-2xl font-bold mb-4">New Triage Record</h2>'),
    ('screening/index.php', 'Screening Responses', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Screening Viewer</h2>'),
    ('patients/index.php', 'Patients', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Patients</h2>'),
    ('family/index.php', 'Family Data', 'css/index.css', 'js/index.js', '../../', '<h2 class="text-2xl font-bold mb-4">Family Data Viewer</h2>'),
    ('health/bristol.php', 'Bristol Scale', 'css/bristol.css', 'js/bristol.js', '../../', '<h2 class="text-2xl font-bold mb-4">Bristol Scale</h2>'),
    ('health/water.php', 'Water Intake', 'css/water.css', 'js/water.js', '../../', '<h2 class="text-2xl font-bold mb-4">Water Intake Viewer</h2>'),
    ('health/mood.php', 'Mood Journal', 'css/mood.css', 'js/mood.js', '../../', '<h2 class="text-2xl font-bold mb-4">Mood Journal Viewer</h2>'),
    ('health/medicine.php', 'Medicines', 'css/medicine.css', 'js/medicine.js', '../../', '<h2 class="text-2xl font-bold mb-4">Medicine Viewer</h2>'),
    ('settings.php', 'Settings', 'css/settings.css', 'js/settings.js', '../', '<h2 class="text-2xl font-bold mb-4">Settings</h2>'),
    ('myaccount.php', 'My Account', 'css/myaccount.css', 'js/myaccount.js', '../', '<h2 class="text-2xl font-bold mb-4">My Account</h2>')
]

for file, title, rel_css, rel_js, depth, content in admin_pages:
    html = f"""<?php
session_start();
require_once '{depth}../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {{
    header('Location: {depth}auth/login.php'); exit;
}}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$userRole = $_SESSION['user_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>{title} — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {{
      darkMode: 'class',
      theme: {{
        extend: {{
          colors: {{
            'primary': '#087383', 'primary-dark': '#065a68', 'primary-light': '#0a9db3',
            'surface': '#F6E5D1', 'surface-dark': '#eddcc0',
            'on-primary': '#ffffff', 'on-surface': '#1a1a1a', 'on-surface-muted': '#5a5a5a',
            'triage-red': '#C0392B', 'triage-yellow': '#D4A017', 'triage-green': '#1E8449',
          }},
          fontFamily: {{ sans: ['Inter', 'sans-serif'] }},
          borderRadius: {{ 'DEFAULT': '0.75rem', 'xl': '1rem', '2xl': '1.5rem', '3xl': '2rem', 'full': '9999px' }}
        }}
      }}
    }}
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="{depth}shared/css/sedap.css"/>
  <link rel="stylesheet" href="{rel_css}"/>
</head>
<body class="sedap-body bg-surface text-on-surface">
<div class="sedap-layout flex min-h-screen">
  <?php include '{depth}shared/includes/sidebar.php'; ?>
  <div class="sedap-main flex-1 flex flex-col">
    <?php include '{depth}shared/includes/header.php'; ?>
    <div class="sedap-content p-6">
      {content}
    </div>
  </div>
</div>
<script src="{rel_js}"></script>
</body>
</html>"""
    write_file('admin/' + file, html)
    write_file('admin/' + os.path.dirname(file) + '/' + rel_css, '/* CSS */')
    write_file('admin/' + os.path.dirname(file) + '/' + rel_js, '/* JS */')

print("All done!")
