<?php
session_start();

// Include database connection
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Process password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($current_password) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        try {
            // Check if user with this email exists
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "No account found with this email address.";
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = "Current password is incorrect.";
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
    <title>SeDaP - Reset Password</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400;8..144,500;8..144,600;8..144,700&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body
    class="bg-gradient-to-br from-[#e0edff] via-[#edf5fc] to-[#d6e7fc] h-screen w-screen overflow-hidden flex items-center justify-center font-sans text-on-surface antialiased p-4 sm:p-6">
    <!-- Responsive Material 3 Layout Container (Light Blue Ambient Background) -->
    <div class="relative w-full max-w-[1920px] h-full flex items-center justify-center mx-auto overflow-hidden">
        <!-- Abstract Ambient Light Blue Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-50">
            <div
                class="absolute -top-32 -left-32 w-[700px] h-[700px] bg-[#c5ddff] rounded-full blur-[100px] mix-blend-multiply">
            </div>
            <div
                class="absolute -bottom-32 -right-32 w-[700px] h-[700px] bg-[#d0e5ff] rounded-full blur-[100px] mix-blend-multiply">
            </div>
            <div
                class="absolute top-1/3 right-1/4 w-[500px] h-[500px] bg-[#bfe0ff]/60 rounded-full blur-[90px] mix-blend-multiply">
            </div>
        </div>

        <!-- Main Card Container -->
        <div
            class="relative z-10 w-full max-w-[420px] bg-surface-container-lowest rounded-3xl sm:rounded-tl-[72px] sm:rounded-br-[72px] shadow-[0_16px_48px_-12px_rgba(26,28,30,0.08)] p-6 sm:p-7 flex flex-col justify-between gap-3 border border-surface-variant/40 my-auto overflow-y-auto max-h-[95vh]">
            
            <!-- Header & Brand -->
            <div class="flex flex-col items-center text-center gap-1">
                <div
                    class="w-14 h-14 rounded-full flex items-center justify-center mb-0.5 shadow-sm overflow-hidden">
                    <img src="logo.jpg" alt="SEDAP logo" class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="text-on-surface text-xl sm:text-2xl font-bold tracking-tight">Reset Password</h1>
                    <p class="text-on-surface-variant text-xs sm:text-sm">Enter your credentials to update your password</p>
                </div>
            </div>

            <!-- Feedback Messages -->
            <?php if (!empty($error)): ?>
                <div class="p-2 rounded-2xl bg-error-container text-error text-center text-xs font-medium border border-error/20 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined !text-[16px]">error</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="p-2 rounded-2xl bg-secondary-container text-on-secondary-container text-center text-xs font-medium border border-secondary/20 flex flex-col items-center justify-center gap-0.5">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined !text-[16px]">check_circle</span>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <a href="login.php" class="text-[11px] text-primary font-semibold underline underline-offset-4 hover:text-primary/80">Click here to Sign in</a>
                </div>
            <?php endif; ?>

            <!-- Reset Form (4 Text Fields) -->
            <form action="" method="POST" class="flex flex-col gap-2 relative">
                <!-- 1. Registered Email -->
                <div class="flex flex-col gap-0.5">
                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider pl-1" for="email">Registered Email</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px]">alternate_email</span>
                        <input
                            class="w-full h-10 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-11 pr-4 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                            id="email" name="email" placeholder="name@example.com" required="" type="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- 2. Current Password -->
                <div class="flex flex-col gap-0.5">
                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider pl-1" for="current_password">Current Password</label>
                    <div class="relative">
                        <input
                            class="w-full h-10 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                            id="current_password" name="current_password" placeholder="Enter current password" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                            type="button">
                            <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- 3. New Password -->
                <div class="flex flex-col gap-0.5">
                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider pl-1" for="password">New Password</label>
                    <div class="relative">
                        <input
                            class="w-full h-10 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                            id="password" name="password" placeholder="At least 6 characters" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                            type="button">
                            <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- 4. Confirm Password -->
                <div class="flex flex-col gap-0.5">
                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider pl-1" for="confirm_password">Confirm Password</label>
                    <div class="relative">
                        <input
                            class="w-full h-10 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                            id="confirm_password" name="confirm_password" placeholder="Re-type new password" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                            type="button">
                            <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button (Icon left, smaller, sentence case) -->
                <div class="flex justify-end mt-1">
                    <button
                        class="w-auto px-4 h-9 bg-primary text-on-primary hover:bg-surface-tint shadow-sm transition-all rounded-full text-sm font-medium inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-4 focus:ring-primary/20 focus:outline-none active:scale-[0.99]"
                        type="submit">
                        <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                        <span>Reset password</span>
                    </button>
                </div>
            </form>

            <!-- Footer Link -->
            <div class="flex flex-col items-center gap-2 relative z-10 text-xs">
                <div class="w-16 h-px bg-outline-variant/50"></div>
                <a class="text-primary font-semibold hover:underline underline-offset-4 inline-flex items-center justify-center gap-1.5"
                    href="login.php">
                    <span class="material-symbols-outlined !text-[16px]">arrow_back</span>
                    <span>Return to Sign in</span>
                </a>
            </div>
        </div>
    </div>

    <script src="js/forgotpass.js"></script>
</body>

</html>
