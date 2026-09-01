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
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
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
                $username = trim($_POST['username'] ?? '');
                if (empty($username)) {
                    $username = strtolower(explode('@', $email)[0]);
                }

                // Insert the new user into the database matching sedap_latest.sql
                $insert_stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $inserted = $insert_stmt->execute([$name, $username, $email, $hashed_password, $role]);
                
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
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SeDaP - Create Account</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400;8..144,500;8..144,600;8..144,700&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .step-section {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .step-hidden {
            display: none !important;
        }
    </style>
</head>

<body
    class="bg-background h-screen w-screen overflow-hidden antialiased selection:bg-primary-container selection:text-on-primary-container font-sans">
    <!-- Responsive Material 3 12-Column Grid Layout Container -->
    <div class="w-full h-full grid grid-cols-1 md:grid-cols-12 relative bg-background overflow-hidden">
        <!-- Left Split Area (6 of 12 Columns - 50%) -->
        <div
            class="hidden md:flex md:col-span-6 h-full relative overflow-hidden bg-gradient-to-br from-primary-fixed/60 via-secondary-fixed/30 to-tertiary-fixed/40 items-center justify-center">
            <div class="absolute inset-0 flex items-center justify-center">
                <img alt="Community Health Connect Illustration" class="w-full h-full object-cover"
                    src="screen.png">
            </div>
            <!-- Ambient Glow Overlays -->
            <div
                class="absolute -top-32 -left-32 w-[800px] h-[800px] bg-primary/20 rounded-full blur-[120px] mix-blend-multiply opacity-70">
            </div>
            <div
                class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-secondary/15 rounded-full blur-[100px] mix-blend-multiply opacity-60">
            </div>
            <div
                class="absolute top-1/2 left-1/4 w-[400px] h-[400px] bg-tertiary/20 rounded-full blur-[80px] mix-blend-multiply opacity-50">
            </div>

            <!-- Floating Brand Card Badge -->
            <div class="absolute bottom-8 left-8 right-8 z-20 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl p-4 border border-white/40 shadow-lg flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined filled !text-[22px]">health_and_safety</span>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-on-surface">Join SeDaP Healthcare</h3>
                    <p class="text-[11px] text-on-surface-variant">Empowering patients, clinicians, and volunteers across communities.</p>
                </div>
            </div>
        </div>

        <!-- Right Split Area (6 of 12 Columns - 50%) -->
        <div class="col-span-1 md:col-span-6 h-full flex items-center justify-center relative bg-surface p-4 sm:p-6 overflow-y-auto">
            <!-- Elevated Expressive Card (Locked 420x580 M3 Container) -->
            <div
                class="w-full max-w-[420px] min-h-[580px] sm:h-[580px] bg-surface-container-lowest rounded-3xl sm:rounded-tl-[72px] sm:rounded-br-[72px] shadow-[0_16px_48px_-12px_rgba(26,28,30,0.08)] p-6 sm:p-8 flex flex-col justify-between relative overflow-hidden border border-surface-variant/40 my-auto">
                
                <!-- Subtle Background Accent -->
                <div
                    class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none">
                </div>

                <!-- Pinned Header Slot -->
                <div class="flex flex-col items-center text-center gap-1 relative z-10">
                    <div
                        class="w-14 h-14 rounded-full flex items-center justify-center mb-0.5 shadow-sm overflow-hidden">
                        <img src="sedap.jpg" alt="SEDAP logo" class="w-full h-full object-cover">
                    </div>
                    <h1 class="text-on-surface text-xl sm:text-2xl font-bold tracking-tight">Create an account</h1>
                    <p id="stepSubtitle" class="text-on-surface-variant text-xs sm:text-sm">Personal Information</p>

                    <!-- Step Progress Indicators (3 Progress Bars) -->
                    <div class="w-full grid grid-cols-3 gap-2 mt-1.5">
                        <div id="bar1" class="h-1.5 rounded-full bg-primary transition-all duration-300"></div>
                        <div id="bar2" class="h-1.5 rounded-full bg-surface-variant/80 transition-all duration-300"></div>
                        <div id="bar3" class="h-1.5 rounded-full bg-surface-variant/80 transition-all duration-300"></div>
                    </div>
                </div>

                <!-- Content Viewport Slot -->
                <div class="flex flex-col relative z-10 w-full my-auto">
                    <!-- Feedback Messages from Server -->
                    <?php if (!empty($error)): ?>
                        <div class="p-2 mb-2 rounded-2xl bg-error-container text-error text-center text-xs font-medium border border-error/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined !text-[16px]">error</span>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="p-2 mb-2 rounded-2xl bg-secondary-container text-on-secondary-container text-center text-xs font-medium border border-secondary/20 flex flex-col items-center justify-center gap-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined !text-[16px]">check_circle</span>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                            <a href="login.php" class="text-[11px] text-primary font-semibold underline underline-offset-4 hover:text-primary/80">Sign in now</a>
                        </div>
                    <?php endif; ?>

                    <!-- Multi-Step Registration Form -->
                    <form id="registerForm" class="flex flex-col relative w-full" method="POST" action="" novalidate>

                        <!-- ================= STAGE 1: PERSONAL INFO & ROLE ================= -->
                        <div id="step1" class="step-section">
                            <!-- Full Name -->
                            <div class="flex flex-col">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="name">Full Name</label>
                                    <span class="error-msg invisible text-[10.5px] font-medium text-error transition-all">Please enter full name</span>
                                </div>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined field-icon absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px] transition-colors">person</span>
                                    <input
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-11 pr-4 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                        id="name" name="name" placeholder="Enter your full name" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Role Selector Dropdown -->
                            <!-- <div class="flex flex-col mt-2">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="role">Select Role</label>
                                    <span class="invisible text-[10.5px]">&nbsp;</span>
                                </div>
                                <div class="relative">
                                    <select
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-10 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all appearance-none cursor-pointer"
                                        id="role" name="role">
                                        <option value="doctor" <?php echo (($_POST['role'] ?? 'doctor') === 'doctor') ? 'selected' : ''; ?>>Doctor / Medical Assistant</option>
                                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="volunteer" <?php echo (($_POST['role'] ?? '') === 'volunteer') ? 'selected' : ''; ?>>Volunteer</option>
                                        <option value="user" <?php echo (($_POST['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User / Patient</option>
                                    </select>
                                    <span
                                        class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px]">arrow_drop_down</span>
                                </div>
                            </div> -->

                            <!-- Spacing Balance Card -->
                            <div class="p-3 bg-surface-container-low/70 rounded-2xl border border-surface-variant/30 flex items-center gap-2.5 mt-3 mb-1">
                                <!-- <span class="material-symbols-outlined text-primary !text-[18px]">verified</span> -->
                                <!-- <p class="text-[11px] text-on-surface-variant">Select your healthcare role to access customized portal features.</p> -->
                            </div>

                            <!-- Step 1 Button -->
                            <div class="flex justify-end mt-2">
                                <button id="btnStep1Next"
                                    class="w-auto px-5 h-10 bg-primary text-on-primary text-sm font-semibold rounded-full hover:bg-primary/90 transition-all shadow-sm hover:shadow-md inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-4 focus:ring-primary/20 focus:outline-none active:scale-[0.99] cursor-pointer"
                                    type="button">
                                    <span>Continue</span>
                                    <span class="material-symbols-outlined !text-[18px]">arrow_forward</span>
                                </button>
                            </div>
                        </div>

                        <!-- ================= STAGE 2: ACCOUNT INFORMATION ================= -->
                        <div id="step2" class="step-section step-hidden">
                            <!-- Email Address -->
                            <div class="flex flex-col">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="email">Email Address</label>
                                    <span class="error-msg invisible text-[10.5px] font-medium text-error transition-all">Please enter valid email</span>
                                </div>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined field-icon absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px] transition-colors">alternate_email</span>
                                    <input
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-11 pr-4 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                        id="email" name="email" placeholder="name@example.com" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="flex flex-col mt-2">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="username">Username (Optional)</label>
                                    <span class="invisible text-[10.5px]">&nbsp;</span>
                                </div>
                                <div class="relative">
                                    <span
                                        class="material-symbols-outlined field-icon absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px] transition-colors">account_circle</span>
                                    <input
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-11 pr-4 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                        id="username" name="username" placeholder="Choose a username" type="text" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Spacing Balance Card -->
                            <div class="p-3 bg-surface-container-low/70 rounded-2xl border border-surface-variant/30 flex items-center gap-2.5 mt-2 mb-1">
                                <span class="material-symbols-outlined text-primary !text-[18px]">verified_user</span>
                                <p class="text-[11px] text-on-surface-variant">Your email or username allows you to log in securely from any device.</p>
                            </div>

                            <!-- Step 2 Dual Button Bar -->
                            <div class="flex items-center justify-end gap-2 mt-4">
                                <button id="btnStep2Back"
                                    class="w-auto px-4 h-10 bg-transparent text-primary border border-outline/70 hover:border-primary hover:bg-primary/5 active:bg-primary/10 text-sm font-semibold rounded-full transition-all inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-2 focus:ring-primary/20 focus:outline-none active:scale-[0.99] cursor-pointer"
                                    type="button">
                                    <span class="material-symbols-outlined !text-[18px]">arrow_back</span>
                                    <span>Back</span>
                                </button>
                                <button id="btnStep2Next"
                                    class="w-auto px-5 h-10 bg-primary text-on-primary text-sm font-semibold rounded-full hover:bg-primary/90 transition-all shadow-sm hover:shadow-md inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-4 focus:ring-primary/20 focus:outline-none active:scale-[0.99] cursor-pointer"
                                    type="button">
                                    <span>Continue</span>
                                    <span class="material-symbols-outlined !text-[18px]">arrow_forward</span>
                                </button>
                            </div>
                        </div>

                        <!-- ================= STAGE 3: PASSWORD SETUP ================= -->
                        <div id="step3" class="step-section step-hidden">
                            <!-- Password -->
                            <div class="flex flex-col">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="password">Create Password</label>
                                    <span class="error-msg invisible text-[10.5px] font-medium text-error transition-all">Min. 6 characters</span>
                                </div>
                                <div class="relative">
                                    <input
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                        id="password" name="password" placeholder="At least 6 characters" type="password">
                                    <button aria-label="Toggle password visibility"
                                        class="toggle-password-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                                        type="button">
                                        <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="flex flex-col mt-2">
                                <div class="flex items-center justify-between px-1">
                                    <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="confirm_password">Confirm Password</label>
                                    <span class="error-msg invisible text-[10.5px] font-medium text-error transition-all">Passwords must match</span>
                                </div>
                                <div class="relative">
                                    <input
                                        class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                        id="confirm_password" name="confirm_password" placeholder="Re-type password" type="password">
                                    <button aria-label="Toggle password visibility"
                                        class="toggle-password-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                                        type="button">
                                        <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Spacing Balance Card -->
                            <div class="p-3 bg-surface-container-low/70 rounded-2xl border border-surface-variant/30 flex items-center gap-2.5 mt-3 mb-1">
                                <span class="material-symbols-outlined text-primary !text-[18px]">lock_clock</span>
                                <p class="text-[11px] text-on-surface-variant">Use at least 6 characters for a strong password.</p>
                            </div>

                            <!-- Step 3 Dual Button Bar -->
                            <div class="flex items-center justify-end gap-2 mt-2">
                                <button id="btnStep3Back"
                                    class="w-auto px-4 h-10 bg-transparent text-primary border border-outline/70 hover:border-primary hover:bg-primary/5 active:bg-primary/10 text-sm font-semibold rounded-full transition-all inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-2 focus:ring-primary/20 focus:outline-none active:scale-[0.99] cursor-pointer"
                                    type="button">
                                    <span class="material-symbols-outlined !text-[18px]">arrow_back</span>
                                    <span>Back</span>
                                </button>
                                <button id="btnSubmit"
                                    class="w-auto px-5 h-10 bg-primary text-on-primary text-sm font-semibold rounded-full hover:bg-primary/90 transition-all shadow-sm hover:shadow-md inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-4 focus:ring-primary/20 focus:outline-none active:scale-[0.99] cursor-pointer"
                                    type="submit">
                                    <span class="material-symbols-outlined !text-[18px]">how_to_reg</span>
                                    <span>Sign up</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Pinned Footer Slot -->
                <div class="flex flex-col items-center gap-2 relative z-10 text-xs">
                    <div class="w-16 h-px bg-outline-variant/50"></div>
                    <p class="text-on-surface-variant text-center">
                        Already have an account? 
                        <a class="text-primary font-semibold hover:underline underline-offset-4 ml-1"
                            href="login.php">
                            Sign in
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Step Navigation Script (Prevents Any Browser Cache Stalling) -->
    <script>
        (function() {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            const steps = [step1, step2, step3];

            const bar1 = document.getElementById('bar1');
            const bar2 = document.getElementById('bar2');
            const bar3 = document.getElementById('bar3');
            const bars = [bar1, bar2, bar3];

            const subtitles = ['Personal Information', 'Account Information', 'Set Password'];
            const stepSubtitle = document.getElementById('stepSubtitle');

            function goToStep(index) {
                steps.forEach((s, i) => {
                    if (!s) return;
                    if (i === index) {
                        s.classList.remove('step-hidden');
                    } else {
                        s.classList.add('step-hidden');
                    }
                });

                bars.forEach((b, i) => {
                    if (!b) return;
                    if (i <= index) {
                        b.style.backgroundColor = '#0058bd';
                        b.classList.remove('bg-surface-variant/80');
                        b.classList.add('bg-primary');
                    } else {
                        b.style.backgroundColor = '#e0e3e5';
                        b.classList.remove('bg-primary');
                        b.classList.add('bg-surface-variant/80');
                    }
                });

                if (stepSubtitle) {
                    stepSubtitle.textContent = subtitles[index] || '';
                }
            }

            function showFieldError(input, message) {
                if (!input) return;
                input.classList.add('border-error', 'ring-2', 'ring-error/20');
                input.classList.remove('border-outline/70');
                const container = input.closest('.flex.flex-col');
                if (container) {
                    const msg = container.querySelector('.error-msg');
                    if (msg) {
                        if (message) msg.textContent = message;
                        msg.classList.remove('invisible');
                    }
                }
                const icon = input.closest('.relative')?.querySelector('.field-icon');
                if (icon) icon.classList.add('text-error');
                input.focus();
            }

            function clearFieldError(input) {
                if (!input) return;
                input.classList.remove('border-error', 'ring-2', 'ring-error/20');
                input.classList.add('border-outline/70');
                const container = input.closest('.flex.flex-col');
                if (container) {
                    const msg = container.querySelector('.error-msg');
                    if (msg) msg.classList.add('invisible');
                }
                const icon = input.closest('.relative')?.querySelector('.field-icon');
                if (icon) icon.classList.remove('text-error');
            }

            ['name', 'email', 'password', 'confirm_password'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => clearFieldError(el));
                }
            });

            function validateStep1() {
                const name = document.getElementById('name');
                if (!name || !name.value.trim()) {
                    showFieldError(name, 'Please enter full name');
                    return false;
                }
                clearFieldError(name);
                return true;
            }

            function validateStep2() {
                const email = document.getElementById('email');
                if (!email || !email.value.trim() || !email.value.includes('@')) {
                    showFieldError(email, 'Please enter valid email');
                    return false;
                }
                clearFieldError(email);
                return true;
            }

            function validateStep3() {
                const pass = document.getElementById('password');
                const confirmPass = document.getElementById('confirm_password');
                let valid = true;

                if (!pass || pass.value.length < 6) {
                    showFieldError(pass, 'Min. 6 characters');
                    valid = false;
                } else {
                    clearFieldError(pass);
                }

                if (!confirmPass || confirmPass.value !== (pass ? pass.value : '')) {
                    showFieldError(confirmPass, 'Passwords must match');
                    valid = false;
                } else {
                    clearFieldError(confirmPass);
                }

                return valid;
            }

            const btnStep1Next = document.getElementById('btnStep1Next');
            if (btnStep1Next) {
                btnStep1Next.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (validateStep1()) goToStep(1);
                });
            }

            const btnStep2Back = document.getElementById('btnStep2Back');
            if (btnStep2Back) {
                btnStep2Back.addEventListener('click', function(e) {
                    e.preventDefault();
                    goToStep(0);
                });
            }

            const btnStep2Next = document.getElementById('btnStep2Next');
            if (btnStep2Next) {
                btnStep2Next.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (validateStep2()) goToStep(2);
                });
            }

            const btnStep3Back = document.getElementById('btnStep3Back');
            if (btnStep3Back) {
                btnStep3Back.addEventListener('click', function(e) {
                    e.preventDefault();
                    goToStep(1);
                });
            }

            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    if (!validateStep1()) {
                        e.preventDefault();
                        goToStep(0);
                        return;
                    }
                    if (!validateStep2()) {
                        e.preventDefault();
                        goToStep(1);
                        return;
                    }
                    if (!validateStep3()) {
                        e.preventDefault();
                        goToStep(2);
                        return;
                    }
                });
            }

            document.querySelectorAll('.toggle-password-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const input = btn.closest('.relative')?.querySelector('input');
                    if (!input) return;
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    const icon = btn.querySelector('.material-symbols-outlined');
                    if (icon) icon.textContent = isPassword ? 'visibility' : 'visibility_off';
                });
            });

            // Initialize on step 1
            goToStep(0);
        })();
    </script>
</body>

</html>