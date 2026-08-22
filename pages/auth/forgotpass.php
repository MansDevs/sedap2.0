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
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-error-container": "#93000a",
                        "on-tertiary": "#ffffff",
                        "on-primary": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "secondary-fixed-dim": "#a6caf3",
                        "on-tertiary-container": "#f8fdff",
                        "on-background": "#191c1e",
                        "surface": "#f7f9fb",
                        "tertiary-fixed-dim": "#7bd3e5",
                        "primary-container": "#2771df",
                        "inverse-on-surface": "#eff1f3",
                        "primary-fixed": "#d8e2ff",
                        "outline-variant": "#c2c6d5",
                        "error-container": "#ffdad6",
                        "secondary": "#3d6185",
                        "surface-container-highest": "#e0e3e5",
                        "tertiary": "#006673",
                        "secondary-container": "#b1d5ff",
                        "outline": "#727785",
                        "tertiary-container": "#148090",
                        "on-error": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "surface-tint": "#005ac1",
                        "on-surface": "#191c1e",
                        "on-secondary-container": "#395c80",
                        "surface-container": "#eceef0",
                        "primary": "#0058bd",
                        "tertiary-fixed": "#a1efff",
                        "secondary-fixed": "#d0e4ff",
                        "background": "#ffffff",
                        "surface-bright": "#f7f9fb",
                        "on-secondary": "#ffffff",
                        "inverse-surface": "#2d3133",
                        "surface-container-high": "#e6e8ea",
                        "on-primary-container": "#fefcff",
                        "on-primary-fixed": "#001a41",
                        "error": "#ba1a1a",
                        "primary-fixed-dim": "#adc6ff",
                        "on-tertiary-fixed-variant": "#004e59",
                        "on-secondary-fixed-variant": "#24496c",
                        "on-surface-variant": "#424753",
                        "inverse-primary": "#adc6ff",
                        "surface-dim": "#d8dadc",
                        "on-tertiary-fixed": "#001f25",
                        "surface-variant": "#e0e3e5",
                        "on-primary-fixed-variant": "#004494",
                        "on-secondary-fixed": "#001d35"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px",
                        "expressive": "2rem 4rem 2rem 4rem",
                    },
                    "spacing": {
                        "gutter": "16px",
                        "stack-md": "16px",
                        "margin-desktop": "32px",
                        "stack-lg": "24px",
                        "margin-mobile": "16px",
                        "margin-tablet": "24px",
                        "stack-sm": "8px"
                    },
                    "fontFamily": {
                        "headline-lg-mobile": ["Roboto Flex"],
                        "display-lg": ["Roboto Flex"],
                        "headline-md": ["Roboto Flex"],
                        "title-lg": ["Roboto Flex"],
                        "label-sm": ["Roboto Flex"],
                        "body-md": ["Roboto Flex"],
                        "body-lg": ["Roboto Flex"],
                        "title-md": ["Roboto Flex"],
                        "label-lg": ["Roboto Flex"],
                        "display-md": ["Roboto Flex"],
                        "headline-lg": ["Roboto Flex"]
                    },
                    "fontSize": {
                        "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "400" }],
                        "display-lg": ["57px", { "lineHeight": "64px", "letterSpacing": "-0.25px", "fontWeight": "400" }],
                        "headline-md": ["28px", { "lineHeight": "36px", "fontWeight": "400" }],
                        "title-lg": ["22px", { "lineHeight": "28px", "fontWeight": "500" }],
                        "label-sm": ["11px", { "lineHeight": "16px", "letterSpacing": "0.5px", "fontWeight": "500" }],
                        "body-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.25px", "fontWeight": "400" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "letterSpacing": "0.5px", "fontWeight": "400" }],
                        "title-md": ["16px", { "lineHeight": "24px", "letterSpacing": "0.15px", "fontWeight": "500" }],
                        "label-lg": ["14px", { "lineHeight": "20px", "letterSpacing": "0.1px", "fontWeight": "500" }],
                        "display-md": ["45px", { "lineHeight": "52px", "fontWeight": "400" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "400" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body
    class="bg-background min-h-screen flex items-center justify-center font-body-lg text-on-surface antialiased overflow-hidden p-4">
    <!-- Canvas 1920x1080 constraint container -->
    <div class="relative w-full max-w-[1920px] min-h-screen flex items-center justify-center bg-background mx-auto">
        <!-- Abstract Background Elements for Material 3 Expressive feel -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-30">
            <div
                class="absolute -top-64 -left-64 w-[800px] h-[800px] bg-primary-fixed rounded-full blur-[120px] mix-blend-multiply">
            </div>
            <div
                class="absolute bottom-[-20%] right-[-10%] w-[1000px] h-[1000px] bg-tertiary-fixed rounded-full blur-[150px] mix-blend-multiply">
            </div>
        </div>
        <!-- Main Card Container -->
        <div
            class="relative z-10 w-full max-w-lg bg-surface-container-lowest rounded-expressive shadow-[0_8px_40px_rgba(26,28,30,0.08)] p-8 sm:p-12 flex flex-col gap-6 border border-surface-variant/30">
            <!-- Header & Brand -->
            <div class="flex flex-col items-center text-center gap-4">
                <div
                    class="w-16 h-16 bg-primary-container text-on-primary-container rounded-2xl flex items-center justify-center rotate-3 shadow-sm">
                    <span class="material-symbols-outlined filled text-4xl">vpn_key</span>
                </div>
                <div>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2 tracking-tight">Reset Password</h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant">Enter your account email and choose a new password.</p>
                </div>
            </div>

            <!-- Feedback Messages -->
            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-2xl bg-error-container text-error text-center font-body-md border border-error/20">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="p-4 rounded-2xl bg-secondary-container text-on-secondary-container text-center font-body-md border border-secondary/20 flex flex-col gap-2">
                    <span><?php echo htmlspecialchars($success); ?></span>
                    <a href="login.php" class="font-label-lg text-primary underline font-medium hover:text-primary/80">Click here to Log In</a>
                </div>
            <?php endif; ?>

            <!-- Reset Form -->
            <form action="" method="POST" class="flex flex-col gap-5 relative">
                <!-- Recovery Email -->
                <div class="flex flex-col gap-1.5">
                    <label class="font-label-lg text-label-lg text-on-surface px-1" for="email">Registered Email</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">mail</span>
                        <input
                            class="w-full h-14 pl-12 pr-4 bg-transparent border border-outline rounded-[32px] font-body-lg text-body-lg text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="email" name="email" placeholder="name@sedaphealth.org" required="" type="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- New Password -->
                <div class="flex flex-col gap-1.5">
                    <label class="font-label-lg text-label-lg text-on-surface px-1" for="password">New Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock</span>
                        <input
                            class="w-full h-14 pl-12 pr-12 bg-transparent border border-outline rounded-[32px] font-body-lg text-body-lg text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="password" name="password" placeholder="••••••••" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant cursor-pointer hover:text-primary transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div class="flex flex-col gap-1.5">
                    <label class="font-label-lg text-label-lg text-on-surface px-1" for="confirm_password">Confirm New Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock_reset</span>
                        <input
                            class="w-full h-14 pl-12 pr-12 bg-transparent border border-outline rounded-[32px] font-body-lg text-body-lg text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                            id="confirm_password" name="confirm_password" placeholder="••••••••" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-pass-btn absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant cursor-pointer hover:text-primary transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    class="w-full h-14 mt-2 bg-primary text-on-primary hover:bg-surface-tint shadow-[0_4px_14px_0_rgba(0,88,189,0.39)] hover:shadow-[0_6px_20px_rgba(0,88,189,0.23)] hover:-translate-y-0.5 transition-all rounded-[32px] font-label-lg text-label-lg flex items-center justify-center gap-2"
                    type="submit">
                    <span class="material-symbols-outlined text-lg">check_circle</span>
                    <span>Reset Password</span>
                </button>
            </form>

            <!-- Footer Link -->
            <div class="text-center mt-2">
                <a class="font-label-lg text-label-lg text-primary hover:text-surface-tint transition-colors inline-flex items-center justify-center gap-1.5 hover:underline underline-offset-4"
                    href="login.php">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Return to Sign In
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtns = document.querySelectorAll('.toggle-pass-btn');
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const input = btn.parentElement.querySelector('input');
                    const icon = btn.querySelector('.material-symbols-outlined');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.textContent = 'visibility';
                    } else {
                        input.type = 'password';
                        icon.textContent = 'visibility_off';
                    }
                });
            });
        });
    </script>
</body>

</html>
