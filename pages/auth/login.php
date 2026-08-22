<?php
session_start();

// Include your database connection
require_once __DIR__ . '/../config/db.php';

$error = '';

// If the user is already logged in, redirect them to the homepage
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

// Process the form when it is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email and password.";
    } else {
        try {
            // Find the user by their email
            $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify if the user exists AND the password matches the hash in the database
            if ($user && password_verify($password, $user['password'])) {
                // Login successful! Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                
                // Redirect to the main app dashboard (adjust this path if your main page is named differently)
                header("Location: ../dashboard/dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch (\PDOException $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" style="">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SeDaP - Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400;8..144,500;8..144,700;8..144,900&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-surface": "#191c1e",
                        "secondary": "#3d6185",
                        "surface-variant": "#e0e3e5",
                        "inverse-primary": "#adc6ff",
                        "error-container": "#ffdad6",
                        "secondary-container": "#b1d5ff",
                        "surface-tint": "#005ac1",
                        "primary": "#0058bd",
                        "on-secondary-fixed-variant": "#24496c",
                        "tertiary-container": "#148090",
                        "on-tertiary": "#ffffff",
                        "on-primary-container": "#fefcff",
                        "outline-variant": "#c2c6d5",
                        "error": "#ba1a1a",
                        "tertiary-fixed": "#a1efff",
                        "surface-container-high": "#e6e8ea",
                        "on-primary": "#ffffff",
                        "on-secondary-container": "#395c80",
                        "primary-fixed-dim": "#adc6ff",
                        "on-error-container": "#93000a",
                        "tertiary": "#006673",
                        "outline": "#727785",
                        "primary-container": "#2771df",
                        "inverse-on-surface": "#eff1f3",
                        "inverse-surface": "#2d3133",
                        "secondary-fixed-dim": "#a6caf3",
                        "surface-container-highest": "#e0e3e5",
                        "on-tertiary-container": "#f8fdff",
                        "surface": "#f7f9fb",
                        "on-primary-fixed": "#001a41",
                        "on-tertiary-fixed": "#001f25",
                        "on-background": "#191c1e",
                        "secondary-fixed": "#d0e4ff",
                        "on-error": "#ffffff",
                        "surface-dim": "#d8dadc",
                        "tertiary-fixed-dim": "#7bd3e5",
                        "background": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#424753",
                        "on-tertiary-fixed-variant": "#004e59",
                        "on-primary-fixed-variant": "#004494",
                        "primary-fixed": "#d8e2ff",
                        "on-secondary": "#ffffff",
                        "on-secondary-fixed": "#001d35",
                        "surface-bright": "#f7f9fb",
                        "surface-container": "#eceef0"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-lg": "24px",
                        "stack-md": "16px",
                        "margin-mobile": "16px",
                        "margin-desktop": "32px",
                        "margin-tablet": "24px",
                        "gutter": "16px",
                        "stack-sm": "8px"
                    },
                    "fontFamily": {
                        "title-md": ["Roboto Flex"],
                        "display-lg": ["Roboto Flex"],
                        "title-lg": ["Roboto Flex"],
                        "label-sm": ["Roboto Flex"],
                        "label-lg": ["Roboto Flex"],
                        "body-md": ["Roboto Flex"],
                        "body-lg": ["Roboto Flex"],
                        "headline-lg-mobile": ["Roboto Flex"],
                        "display-md": ["Roboto Flex"],
                        "headline-lg": ["Roboto Flex"],
                        "headline-md": ["Roboto Flex"]
                    },
                    "fontSize": {
                        "title-md": ["16px", { "lineHeight": "24px", "letterSpacing": "0.15px", "fontWeight": "500" }],
                        "display-lg": ["57px", { "lineHeight": "64px", "letterSpacing": "-0.25px", "fontWeight": "400" }],
                        "title-lg": ["22px", { "lineHeight": "28px", "fontWeight": "500" }],
                        "label-sm": ["11px", { "lineHeight": "16px", "letterSpacing": "0.5px", "fontWeight": "500" }],
                        "label-lg": ["14px", { "lineHeight": "20px", "letterSpacing": "0.1px", "fontWeight": "500" }],
                        "body-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.25px", "fontWeight": "400" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "letterSpacing": "0.5px", "fontWeight": "400" }],
                        "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "400" }],
                        "display-md": ["45px", { "lineHeight": "52px", "fontWeight": "400" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "400" }],
                        "headline-md": ["28px", { "lineHeight": "36px", "fontWeight": "400" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        /* Hide scrollbar for chip container */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body
    class="bg-background min-h-screen flex items-center justify-center p-4 antialiased selection:bg-primary-container selection:text-on-primary-container">
    <!-- 1920x1080 Container -->
    <div class="w-[1920px] h-[1080px] flex relative overflow-hidden bg-background">
        <!-- Left Split Area -->
        <div
            class="flex-1 relative overflow-hidden bg-gradient-to-br from-primary-fixed/60 via-secondary-fixed/30 to-tertiary-fixed/40">
            <div class="absolute inset-0 flex items-center justify-center">
                <img alt="Community Health Connect Illustration" class="w-full h-full object-cover"
                    src="screen.png">
            </div>
            <div
                class="absolute -top-32 -left-32 w-[800px] h-[800px] bg-primary/20 rounded-full blur-[120px] mix-blend-multiply opacity-70">
            </div>
            <div
                class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-secondary/15 rounded-full blur-[100px] mix-blend-multiply opacity-60">
            </div>
            <div
                class="absolute top-1/2 left-1/4 w-[400px] h-[400px] bg-tertiary/20 rounded-full blur-[80px] mix-blend-multiply opacity-50">
            </div>
        </div>
        <!-- Right Split Area -->
        <div class="w-1/2 flex items-center justify-center relative bg-surface">
            <!-- Elevated Expressive Card -->
            <div
                class="w-full max-w-[420px] bg-surface-container-lowest rounded-tl-[120px] rounded-br-3xl rounded-tr-3xl rounded-bl-3xl shadow-[0_12px_40px_-12px_rgba(26,28,30,0.08)] p-8 sm:p-10 flex flex-col gap-8 relative overflow-hidden border border-surface-variant/30">
                <!-- Subtle Background Accent (Expressive) -->
                <div
                    class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none">
                </div>
                <!-- Header -->
                <div class="flex flex-col items-center text-center gap-2 relative z-10">
                    <div
                        class="w-16 h-16 bg-primary-container rounded-[24px] rounded-tr-sm flex items-center justify-center mb-2 shadow-sm text-primary">
                        <span class="material-symbols-outlined !text-[36px]"
                            style="font-variation-settings: 'FILL' 1;">health_and_safety</span>
                    </div>
                    <h1 class="font-headline-md text-headline-md text-on-surface">Welcome to SeDaP</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Sign in to continue to the platform.
                    </p>
                </div>
                <!-- Role Selector -->
                <div class="flex flex-col gap-3 relative z-10">
                    <label
                        class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider pl-1">Select
                        Role</label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Active Chip (Default) -->
                        <button aria-pressed="true"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-secondary-container text-on-secondary-container rounded-[32px] border border-transparent transition-all hover:bg-secondary-container/80 focus:ring-2 focus:ring-secondary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">medical_services</span>
                            <span class="font-label-lg text-label-lg">Doctor/MA/Nurse</span>
                        </button>
                        <!-- Inactive Chips -->
                        <button aria-pressed="false"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">admin_panel_settings</span>
                            <span class="font-label-lg text-label-lg">Administrative</span>
                        </button>
                        <button aria-pressed="false"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">volunteer_activism</span>
                            <span class="font-label-lg text-label-lg">Volunteer</span>
                        </button>
                        <button aria-pressed="false"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">person</span>
                            <span class="font-label-lg text-label-lg">User</span>
                        </button>
                    </div>
                </div>

                <!-- Error Message Display -->
                <?php if (!empty($error)): ?>
                    <div class="relative z-10 p-3 rounded-lg bg-error-container text-error text-center font-body-md text-body-md border border-error/20">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="" method="POST" class="flex flex-col gap-5 relative z-10">
                    <!-- Username Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">mail</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3.5 pl-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="email" name="email" placeholder="Username or Email" required="" type="text" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <!-- Password Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3.5 pl-12 pr-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="password" name="password" placeholder="Password" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none"
                            type="button" id="togglePassword">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                    <!-- Submit Button -->
                    <button
                        class="w-full bg-primary text-on-primary font-label-lg text-label-lg py-4 rounded-[32px] hover:bg-primary/90 transition-colors shadow-sm flex justify-center items-center gap-2 mt-2 focus:ring-4 focus:ring-primary/20 focus:outline-none"
                        type="submit">
                        <span class="">Log In</span>
                        <span class="material-symbols-outlined !text-[20px]">arrow_forward</span>
                    </button>
                </form>
                <!-- Footer Links -->
                <div class="flex flex-col items-center gap-4 relative z-10 mt-2">
                    <a class="font-label-lg text-label-lg text-primary hover:text-primary/80 transition-colors hover:underline underline-offset-4 decoration-primary/50"
                        href="#">Forgot password?</a>
                    <div class="w-16 h-px bg-outline-variant/50"></div>
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary transition-colors"
                        href="register.php">
                        New here? <span
                            class="font-label-lg text-label-lg text-primary hover:underline underline-offset-4">Sign
                            up</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Simple script to handle chip selection visual state
        document.addEventListener('DOMContentLoaded', () => {
            const chips = document.querySelectorAll('button[aria-pressed]');

            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    // Reset all chips
                    chips.forEach(c => {
                        c.setAttribute('aria-pressed', 'false');
                        c.className = 'w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none';
                    });

                    // Set active chip
                    chip.setAttribute('aria-pressed', 'true');
                    chip.className = 'w-full flex items-center gap-2 px-4 py-2 bg-secondary-container text-on-secondary-container rounded-[32px] border border-transparent transition-all hover:bg-secondary-container/80 focus:ring-2 focus:ring-secondary focus:outline-none';
                });
            });

            // Password visibility toggle
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', () => {
                    const icon = toggleBtn.querySelector('.material-symbols-outlined');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.textContent = 'visibility';
                    } else {
                        passwordInput.type = 'password';
                        icon.textContent = 'visibility_off';
                    }
                });
            }
        });
    </script>
</body>

</html>