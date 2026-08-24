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
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
</head>
<body class="bg-[#f7f9fb] min-h-screen flex items-center justify-center p-4" style="font-family: 'Roboto Flex', sans-serif;">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-[#0058bd] mb-6">Reset Password</h2>
        <?php if($error): ?><div class="text-red-600 mb-4 text-center text-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="text-green-600 mb-4 text-center text-sm"><?= htmlspecialchars($success) ?> <br><a href="login.php" class="underline text-[#0058bd]">Login</a></div><?php endif; ?>
        <form method="POST" action="" class="space-y-4">
            <input type="text" name="username" required placeholder="Username" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="email" name="email" required placeholder="Email used for account" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="password" name="password" required placeholder="New Password" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <input type="password" name="confirm" required placeholder="Confirm New Password" class="w-full px-4 py-2 rounded-xl border border-gray-200">
            <button type="submit" class="w-full bg-[#0058bd] text-white py-3 rounded-full font-semibold hover:bg-[#004494]">Reset Password</button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-sm text-[#0058bd]">Back to Login</a>
        </div>
    </div>
</body>
</html>
