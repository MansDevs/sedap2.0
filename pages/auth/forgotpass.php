<?php
session_start();

// Include database connection
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Process password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if user with this email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "No account found with this email address.";
            } else {
                // Hash new password and update in database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                
                if ($update_stmt->execute([$hashed_password, $email])) {
                    $success = "Your password has been successfully reset! You can now log in.";
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        } catch (\PDOException $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SeDaP Health - Password Reset</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400..900&amp;family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:wght@100..900&amp;display=swap" rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body
    class="bg-background h-screen w-screen overflow-hidden flex items-center justify-center font-body-lg text-on-surface antialiased p-6">
    <!-- Responsive Material 3 Layout Container (No scroll) -->
    <div class="relative w-full max-w-[1920px] h-full flex items-center justify-center bg-background mx-auto overflow-hidden">
        <!-- Abstract Background Elements for Material 3 Expressive feel -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-30">
            <div
                class="absolute -top-64 -left-64 w-[800px] h-[800px] bg-primary-fixed rounded-full blur-[120px] mix-blend-multiply">
            </div>
            <div
                class="absolute bottom-[-20%] right-[-10%] w-[1000px] h-[1000px] bg-tertiary-fixed rounded-full blur-[150px] mix-blend-multiply">
            </div>
        </div>
        <!-- Main Card Container (24dp padding & spacing) -->
        <div
            class="relative z-10 w-full max-w-md bg-surface-container-lowest rounded-expressive shadow-[0_8px_40px_rgba(26,28,30,0.08)] p-5 sm:p-6 flex flex-col gap-4 border border-surface-variant/30">
            <!-- Header & Brand -->
            <div class="flex flex-col items-center text-center gap-2">
                <div
                    class="w-12 h-12 bg-primary-container text-on-primary-container rounded-2xl flex items-center justify-center rotate-3 shadow-sm">
                    <span class="material-symbols-outlined filled text-2xl">vpn_key</span>
                </div>
                <div>
                    <h1 class="font-headline-lg text-on-surface text-xl font-semibold tracking-tight">Reset Password</h1>
                    <p class="font-body-md text-xs text-on-surface-variant">Enter your account email and choose a new password.</p>
                </div>
            </div>

            <!-- Feedback Messages -->
            <?php if (!empty($error)): ?>
                <div class="p-2.5 rounded-2xl bg-error-container text-error text-center text-xs font-body-md border border-error/20">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="p-2.5 rounded-2xl bg-secondary-container text-on-secondary-container text-center text-xs font-body-md border border-secondary/20 flex flex-col gap-1">
                    <span><?php echo htmlspecialchars($success); ?></span>
                    <a href="login.php" class="text-xs text-primary underline font-medium hover:text-primary/80">Click here to Log In</a>
                </div>
            <?php endif; ?>

            <!-- Reset Form -->
            <form action="" method="POST" class="flex flex-col gap-3 relative">
                <!-- Recovery Email -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-on-surface px-1 font-medium" for="email">Registered Email</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[18px]">mail</span>
                        <input
                            class="w-full h-11 pl-10 pr-4 bg-transparent border border-outline rounded-[32px] text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="email" name="email" placeholder="name@sedaphealth.org" required="" type="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- New Password -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-on-surface px-1 font-medium" for="password">New Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[18px]">lock</span>
                        <input
                            class="w-full h-11 pl-10 pr-10 bg-transparent border border-outline rounded-[32px] text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="password" name="password" placeholder="••••••••" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant cursor-pointer hover:text-primary transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-on-surface px-1 font-medium" for="confirm_password">Confirm New Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[18px]">lock_reset</span>
                        <input
                            class="w-full h-11 pl-10 pr-10 bg-transparent border border-outline rounded-[32px] text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="confirm_password" name="confirm_password" placeholder="••••••••" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant cursor-pointer hover:text-primary transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button (Icon left, smaller) -->
                <div class="flex justify-end mt-1">
                    <button
                        class="w-auto px-4 h-9 bg-primary text-on-primary hover:bg-surface-tint shadow-sm transition-all rounded-full text-sm font-medium inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap"
                        type="submit">
                        <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                        <span>Reset password</span>
                    </button>
                </div>
            </form>

            <!-- Footer Link -->
            <div class="text-center">
                <a class="text-xs text-primary hover:text-surface-tint transition-colors inline-flex items-center justify-center gap-2 hover:underline underline-offset-4"
                    href="login.php">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Return to Sign In
                </a>
            </div>
        </div>
    </div>

    <script src="js/forgotpass.js"></script>
</body>

</html>
