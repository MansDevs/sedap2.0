<?php
session_start();

// Database connection and user registration processing
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

// Process the form when it is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and grab input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? $_POST['confirm-password'] ?? '';
    $role = trim($_POST['role'] ?? 'user');

    // Basic Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All required fields must be filled.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if the email is already registered using PDO
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "This email is already registered.";
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database using PDO
                try {
                    $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $inserted = $insert_stmt->execute([$name, $email, $hashed_password, $role]);
                } catch (\PDOException $pe) {
                    $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $inserted = $insert_stmt->execute([$name, $email, $hashed_password]);
                }
                
                if ($inserted) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Error during registration. Please try again.";
                }
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
    <title>SeDaP - Sign Up</title>
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
    <div class="w-[1920px] h-[1080px] flex relative overflow-hidden bg-surface-bright">
        <!-- Left Split Area (Illustration focus) -->
        <div class="flex-1 relative overflow-hidden flex items-center justify-center">
            <div
                class="absolute -top-32 -left-32 w-[800px] h-[800px] bg-primary/20 rounded-full blur-[120px] mix-blend-multiply opacity-60">
            </div>
            <div
                class="absolute bottom-10 right-20 w-[600px] h-[600px] bg-primary-fixed/40 rounded-full blur-[100px] mix-blend-multiply opacity-50">
            </div>
            <div
                class="absolute top-1/2 left-1/4 w-[500px] h-[500px] bg-secondary-fixed/30 rounded-full blur-[90px] mix-blend-multiply opacity-40">
            </div>
            <img alt="Modern healthcare illustration"
                class="relative z-10 max-w-[900px] drop-shadow-xl w-full h-full object-cover"
                src="screen.png">
        </div>
        <!-- Right Split Area (Form) -->
        <div class="w-[700px] flex items-center justify-center relative p-12">
            <!-- Elevated Expressive Card -->
            <div
                class="w-full max-w-[500px] bg-surface-container-lowest rounded-tl-[120px] rounded-br-[80px] rounded-tr-3xl rounded-bl-3xl shadow-[0_24px_60px_-12px_rgba(26,28,30,0.12)] p-10 flex flex-col gap-8 relative overflow-hidden border border-surface-variant/20">
                <!-- Header -->
                <div class="flex flex-col items-center text-center gap-2 relative z-10">
                    <div
                        class="w-16 h-16 bg-primary-container rounded-[24px] rounded-tr-sm flex items-center justify-center mb-2 shadow-sm text-primary">
                        <span class="material-symbols-outlined !text-[36px]"
                            style="font-variation-settings: 'FILL' 1;">health_and_safety</span>
                    </div>
                    <h1 class="font-headline-md text-headline-md text-on-surface">Create your SeDaP account</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Join the community platform today.</p>
                </div>
                <!-- Role Selector -->
                <div class="flex flex-col gap-3 relative z-10">
                    <label
                        class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider pl-1">Select
                        Role</label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Active Chip (Default) -->
                        <button aria-pressed="true" data-role="doctor"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-secondary-container text-on-secondary-container rounded-[32px] border border-transparent transition-all hover:bg-secondary-container/80 focus:ring-2 focus:ring-secondary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">medical_services</span>
                            <span class="font-label-lg text-label-lg">Doctor/MA</span>
                        </button>
                        <!-- Inactive Chips -->
                        <button aria-pressed="false" data-role="admin"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">admin_panel_settings</span>
                            <span class="font-label-lg text-label-lg">Admin</span>
                        </button>
                        <button aria-pressed="false" data-role="volunteer"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">volunteer_activism</span>
                            <span class="font-label-lg text-label-lg">Volunteer</span>
                        </button>
                        <button aria-pressed="false" data-role="user"
                            class="w-full flex items-center gap-2 px-4 py-2 bg-surface text-on-surface-variant rounded-[32px] border border-outline transition-all hover:bg-surface-variant/50 focus:ring-2 focus:ring-primary focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined !text-[18px]">person</span>
                            <span class="font-label-lg text-label-lg">User</span>
                        </button>
                    </div>
                </div>
                <!-- Signup Form -->
                <form class="flex flex-col gap-4 relative z-10" method="POST" action="">
                    <input type="hidden" name="role" id="roleInput" value="doctor">

                    <!-- Feedback Messages -->
                    <?php if (!empty($error)): ?>
                        <div class="p-3 rounded-2xl bg-error-container text-error text-center font-body-md border border-error/20">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="p-3 rounded-2xl bg-secondary-container text-on-secondary-container text-center font-body-md border border-secondary/20">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Name Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">badge</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="name" name="name" placeholder="Full Name" required="" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <!-- Email Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">mail</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="email" name="email" placeholder="Email Address" required="" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <!-- Contact Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">call</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="contact" name="contact" placeholder="Contact Number" type="tel" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                    </div>
                    <!-- Username Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">account_circle</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="username" name="username" placeholder="Username" type="text" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <!-- Password Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 pr-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="password" name="password" placeholder="Password" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-password-btn absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                    <!-- Confirm Password Field -->
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock_reset</span>
                        <input
                            class="w-full bg-surface-container-lowest border border-outline text-on-surface font-body-lg text-body-lg rounded-[32px] px-4 py-3 pl-12 pr-12 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none transition-all placeholder:text-on-surface-variant/60"
                            id="confirm_password" name="confirm_password" placeholder="Confirm Password" required="" type="password">
                        <button aria-label="Toggle password visibility"
                            class="toggle-password-btn absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none"
                            type="button">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                    <!-- Submit Button -->
                    <button
                        class="w-full bg-[#4285F4] text-white font-label-lg text-label-lg py-4 rounded-[32px] hover:bg-[#4285F4]/90 transition-colors shadow-sm flex justify-center items-center gap-2 mt-4 focus:ring-4 focus:ring-[#4285F4]/30 focus:outline-none"
                        type="submit">
                        <span class="">Create Account</span>
                        <span class="material-symbols-outlined !text-[20px]">person_add</span>
                    </button>
                </form>
                <!-- Footer Links -->
                <div class="flex flex-col items-center gap-4 relative z-10 mt-2">
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary transition-colors"
                        href="login.php">
                        Already have an account? <span
                            class="font-label-lg text-label-lg text-primary hover:underline underline-offset-4">Log
                            in</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Simple script to handle chip selection visual state
        document.addEventListener('DOMContentLoaded', () => {
            const chips = document.querySelectorAll('button[data-role]');
            const roleInput = document.getElementById('roleInput');

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
                    
                    if (roleInput && chip.dataset.role) {
                        roleInput.value = chip.dataset.role;
                    }
                });
            });

            // Password visibility toggle
            const toggleButtons = document.querySelectorAll('.toggle-password-btn');
            toggleButtons.forEach(btn => {
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