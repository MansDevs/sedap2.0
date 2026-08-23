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
<html class="h-full" lang="en" style="">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Sign In - Sedap</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">tailwind.config = {darkMode: "class", theme: {extend: {colors: {"surface-container-low": "#fff2e0", "on-background": "#221a0c", "on-tertiary-container": "#ffd5cf", "secondary-fixed": "#ffddb4", "on-secondary-container": "#6b4500", "primary-fixed": "#a3eff7", "surface-variant": "#f0e0c9", "on-secondary": "#ffffff", "surface-dim": "#e7d8c1", "outline-variant": "#bec8c9", "inverse-on-surface": "#ffefd6", "on-secondary-fixed": "#291800", "secondary-fixed-dim": "#ffb955", "secondary-container": "#feae2c", background: "#fff8f2", "on-tertiary-fixed": "#410001", "on-primary-fixed": "#002022", "surface-container-lowest": "#ffffff", "on-primary": "#ffffff", error: "#ba1a1a", "error-container": "#ffdad6", surface: "#fff8f2", "on-primary-container": "#a0ecf4", secondary: "#835500", "on-tertiary-fixed-variant": "#83251c", "surface-container-highest": "#f0e0c9", "primary-fixed-dim": "#87d3da", "surface-container": "#fcecd4", "on-primary-fixed-variant": "#004f55", "tertiary-fixed": "#ffdad5", "on-tertiary": "#ffffff", "surface-tint": "#096970", "inverse-surface": "#382f1f", "inverse-primary": "#87d3da", "tertiary-container": "#a84035", "on-error": "#ffffff", "surface-container-high": "#f6e6ce", "tertiary-fixed-dim": "#ffb4a9", tertiary: "#882920", "on-error-container": "#93000a", primary: "#005359", "on-surface-variant": "#3f494a", "on-surface": "#221a0c", "primary-container": "#136d74", "on-secondary-fixed-variant": "#633f00", outline: "#6f797a", "surface-bright": "#fff8f2"}, borderRadius: {DEFAULT: "0.5rem", lg: "1rem", xl: "1.5rem", full: "9999px"}, spacing: {"max-width": "1280px", base: "8px", "margin-mobile": "16px", gutter: "24px", "margin-desktop": "64px"}, fontFamily: {"display-lg": ["Plus Jakarta Sans"], "headline-lg": ["Plus Jakarta Sans"], "body-lg": ["Inter"], "body-md": ["Inter"], "headline-lg-mobile": ["Plus Jakarta Sans"], "label-lg": ["Inter"], "label-md": ["Inter"], "title-lg": ["Plus Jakarta Sans"], headline: ["Plus Jakarta Sans"], display: ["Plus Jakarta Sans"], body: ["Inter"], label: ["Inter"]}, fontSize: {"display-lg": ["57px", {lineHeight: "64px", letterSpacing: "-0.25px", fontWeight: "400"}], "headline-lg": ["32px", {lineHeight: "40px", fontWeight: "400"}], "body-lg": ["16px", {lineHeight: "24px", letterSpacing: "0.5px", fontWeight: "400"}], "body-md": ["14px", {lineHeight: "20px", letterSpacing: "0.25px", fontWeight: "400"}], "headline-lg-mobile": ["28px", {lineHeight: "36px", fontWeight: "600"}], "label-lg": ["14px", {lineHeight: "20px", letterSpacing: "0.1px", fontWeight: "600"}], "label-md": ["12px", {lineHeight: "16px", letterSpacing: "0.5px", fontWeight: "600"}], "title-lg": ["22px", {lineHeight: "28px", fontWeight: "500"}]}}}};</script>
    <style>
        .mesh-bg {
            background-color: #fff8f2;
            background-image: 
                radial-gradient(at 10% 20%, hsla(184, 72%, 26%, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(33, 100%, 80%, 0.2) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(184, 72%, 26%, 0.1) 0px, transparent 50%);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        /* --- Hide the scrollbar --- */
        body::-webkit-scrollbar {
            display: none;
        }
        body {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="h-full bg-background text-on-background mesh-bg flex items-center justify-center p-margin-mobile md:p-margin-desktop antialiased">

<main class="w-full max-w-[440px]">
    <!-- Elevation Level 1 Card -->
    <div class="bg-surface-container-low p-8 md:p-10 relative rounded-[32px]" style="box-shadow: rgba(0, 83, 89, 0.08) 0px 4px 12px;">
        <div class="flex flex-col items-center mb-8">
            <img alt="Sedap Logo" class="w-20 h-20 rounded-xl object-cover mb-6 shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDlDgcuLOaDbJVc0JJKA6RPm08oSE9NVsd75txzvSJLLmBDfrMH-IRON9FBba0sxs-hyBClJLXxp3Do4K8nViXelq93eTLByRf0Ink0Jq1tR8XNrCBPDHwomQN8RiaZpwqfQO0xLCkYup0XtVcxcYUJ1F7ZoSzYWLBhvlAN3roSeotlbDKzz9f4kPIqZMzIm4gudZyKFbJ0JMztRKbIUsZ_kokRcO0amSllgLKkwAxqeidhgxh2IldV8642-W9T2iFx12Q">
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary text-center">Welcome Back</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mt-2 text-center">Sign in to continue to Sedap</p>
        </div>

        <!-- Error Message Display -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-3 rounded-lg bg-error-container text-error text-center font-body-md border border-error/20">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" class="flex flex-col gap-gutter" method="POST">
            <!-- Email Field (Changed from Username) -->
            <div class="relative">
                <label class="sr-only" for="email">Email</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                    <input class="w-full pl-12 pr-4 py-4 bg-transparent border border-outline-variant/40 rounded-[12px] text-on-background placeholder:text-on-surface-variant font-body-lg text-body-lg focus:border-2 focus:border-primary focus:ring-0 transition-all outline-none" id="email" name="email" placeholder="Registered Email" required="" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <!-- Password Field -->
            <div class="relative">
                <label class="sr-only" for="password">Password</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                    <input class="w-full pl-12 pr-12 py-4 bg-transparent border border-outline-variant/40 rounded-[12px] text-on-background placeholder:text-on-surface-variant font-body-lg text-body-lg focus:border-2 focus:border-primary focus:ring-0 transition-all outline-none" id="password" name="password" placeholder="Password" required="" type="password">
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors focus:outline-none" type="button" id="togglePassword">
                        <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between mt-[-8px]">
                <div class="flex items-center gap-2 cursor-pointer group">
                    <div class="relative flex items-center">
                        <input class="peer sr-only" id="remember" type="checkbox" name="remember">
                        <div class="w-5 h-5 rounded-[4px] border-2 border-outline peer-checked:bg-primary peer-checked:border-primary transition-all flex items-center justify-center">
                            <span class="material-symbols-outlined text-[16px] text-on-primary opacity-0 peer-checked:opacity-100 transition-opacity" style="font-variation-settings: &quot;FILL&quot; 1;">check</span>
                        </div>
                    </div>
                    <label class="font-body-md text-body-md text-on-surface-variant cursor-pointer group-hover:text-on-background transition-colors" for="remember">Remember me</label>
                </div>
                <a class="font-label-md text-label-md text-primary hover:text-primary-container transition-colors" href="#">Forgot password?</a>
            </div>

            <button class="w-full bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg py-4 rounded-[32px] mt-4 transition-colors shadow-sm flex justify-center items-center gap-2 group" type="submit">
                <span class="">Sign In</span>
                <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
        </form>

        <div class="mt-8 text-center font-body-md text-body-md text-on-surface-variant flex items-center justify-center gap-2">
            <span class="">Don't have an account?</span>
            <!-- Link updated to point to register.php -->
            <a class="font-label-lg text-label-lg text-secondary hover:text-secondary-container transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-secondary after:transition-all hover:after:w-full" href="register.php">Sign Up</a>
        </div>
    </div>
</main>

<!-- Script to handle password visibility toggle -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
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