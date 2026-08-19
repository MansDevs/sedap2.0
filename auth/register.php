<?php
session_start();

// Database connection and user registration processing
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// Process the form when it is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and grab input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;

    // Basic Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!$terms) {
        $error = "You must agree to the Terms of Service.";
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
                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                
                if ($insert_stmt->execute([$name, $email, $hashed_password])) {
                    $success = "Registration successful! You can now log in.";
                    // Optional: redirect to login page
                    // header("Location: login.php");
                    // exit();
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
<html class="light" lang="en" style="">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Sign Up - Sedap</title>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Tailwind Config -->
    <script id="tailwind-config">tailwind.config = {darkMode: "class", theme: {extend: {colors: {"surface-container-low": "#fff2e0", "on-background": "#221a0c", "on-tertiary-container": "#ffd5cf", "secondary-fixed": "#ffddb4", "on-secondary-container": "#6b4500", "primary-fixed": "#a3eff7", "surface-variant": "#f0e0c9", "on-secondary": "#ffffff", "surface-dim": "#e7d8c1", "outline-variant": "#bec8c9", "inverse-on-surface": "#ffefd6", "on-secondary-fixed": "#291800", "secondary-fixed-dim": "#ffb955", "secondary-container": "#feae2c", background: "#fff8f2", "on-tertiary-fixed": "#410001", "on-primary-fixed": "#002022", "surface-container-lowest": "#ffffff", "on-primary": "#ffffff", error: "#ba1a1a", "error-container": "#ffdad6", surface: "#fff8f2", "on-primary-container": "#a0ecf4", secondary: "#835500", "on-tertiary-fixed-variant": "#83251c", "surface-container-highest": "#f0e0c9", "primary-fixed-dim": "#87d3da", "surface-container": "#fcecd4", "on-primary-fixed-variant": "#004f55", "tertiary-fixed": "#ffdad5", "on-tertiary": "#ffffff", "surface-tint": "#096970", "inverse-surface": "#382f1f", "inverse-primary": "#87d3da", "tertiary-container": "#a84035", "on-error": "#ffffff", "surface-container-high": "#f6e6ce", "tertiary-fixed-dim": "#ffb4a9", tertiary: "#882920", "on-error-container": "#93000a", primary: "#005359", "on-surface-variant": "#3f494a", "on-surface": "#221a0c", "primary-container": "#136d74", "on-secondary-fixed-variant": "#633f00", outline: "#6f797a", "surface-bright": "#fff8f2"}, borderRadius: {DEFAULT: "0.5rem", lg: "1rem", xl: "1.5rem", full: "9999px"}, spacing: {"max-width": "1280px", base: "8px", "margin-mobile": "16px", gutter: "24px", "margin-desktop": "64px"}, fontFamily: {"display-lg": ["Plus Jakarta Sans"], "headline-lg": ["Plus Jakarta Sans"], "body-lg": ["Inter"], "body-md": ["Inter"], "headline-lg-mobile": ["Plus Jakarta Sans"], "label-lg": ["Inter"], "label-md": ["Inter"], "title-lg": ["Plus Jakarta Sans"], headline: ["Plus Jakarta Sans"], display: ["Plus Jakarta Sans"], body: ["Inter"], label: ["Inter"]}}}};</script>
    <style>
        .mesh-gradient {
            background-color: #fff8f2;
            background-image: 
                radial-gradient(at 0% 0%, hsla(184,72%,27%,0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, hsla(33,100%,94%,1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, hsla(184,72%,27%,0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(33,100%,94%,1) 0px, transparent 50%);
        }
        
        .floating-input:focus-within label,
        .floating-input input:not(:placeholder-shown) + label {
            transform: translateY(-50%) scale(0.85);
            top: 0;
            background-color: var(--tw-colors-surface-container);
            padding: 0 4px;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="mesh-gradient h-[100dvh] w-screen flex flex-col items-center justify-center p-4 md:p-8 font-body-md text-on-background overflow-hidden relative">
    
    <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-primary-fixed opacity-20 blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-secondary-fixed opacity-30 blur-[120px] pointer-events-none"></div>
    
    <main class="w-full max-w-md z-10 max-h-full overflow-y-auto no-scrollbar py-2">
        
        <div class="flex justify-center mb-4 md:mb-6">
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-full overflow-hidden shadow-elevation-1 border-4 border-surface-container-lowest">
                <img alt="Sedap Logo" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBH9RP8tN4Kiblb2qvZRD6fI6s37DHC_07vkjqXtZ4xBsh3XRdVyNgT4r1PDi_XwEDUpQRxX2yW8z1dshF9WelBf6JL3NC7YFFrHArxJXZCx8vRZQrmZ33b3JvmeCvs10_9VxS_QQ7wkgYxpQmBhA63PVdnUuzkLPePNhzploXsrTcGFoAkiipLSYT5gBWioimpzVnQySgLB2Q2lI-aUU0nTZ1U4JN1KO2ZTLxxyyn9c3n2fF_EnIl4xo_QSl-FNnkNc30">
            </div>
        </div>

        <div class="bg-surface-container shadow-elevation-1 p-6 md:p-8 border border-surface-dim transition-all duration-300 hover:shadow-elevation-2 relative overflow-hidden group rounded-[32px]">
            <div class="absolute inset-0 border border-surface-container-lowest opacity-50 pointer-events-none rounded-[32px]"></div>
            
            <div class="text-center mb-6 relative z-10">
                <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-1">Create Account</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Join Sedap to experience culinary excellence.</p>
            </div>

            <!-- Dynamic Feedback Messages -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-lg bg-error-container text-error text-center font-body-md relative z-10 border border-error/20">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 rounded-lg bg-primary-container text-on-primary-container text-center font-body-md relative z-10 border border-primary/20">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form action="" class="space-y-4 md:space-y-5 relative z-10" method="POST">
                <div class="relative floating-input group/input">
                    <input class="w-full h-12 px-4 bg-transparent border border-outline-variant rounded-lg text-on-surface font-body-lg text-body-lg focus:outline-none focus:border-2 focus:border-primary focus:ring-0 transition-colors peer placeholder-transparent" id="name" name="name" placeholder=" " required="" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    <label class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-md text-body-md transition-all duration-200 pointer-events-none peer-focus:text-primary" for="name">Name</label>
                </div>

                <div class="relative floating-input group/input">
                    <input class="w-full h-12 px-4 bg-transparent border border-outline-variant rounded-lg text-on-surface font-body-lg text-body-lg focus:outline-none focus:border-2 focus:border-primary focus:ring-0 transition-colors peer placeholder-transparent" id="email" name="email" placeholder=" " required="" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <label class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-md text-body-md transition-all duration-200 pointer-events-none peer-focus:text-primary" for="email">Registered Email</label>
                </div>

                <div class="relative floating-input group/input">
                    <input class="w-full h-12 px-4 bg-transparent border border-outline-variant rounded-lg text-on-surface font-body-lg text-body-lg focus:outline-none focus:border-2 focus:border-primary focus:ring-0 transition-colors peer placeholder-transparent pr-12" id="password" name="password" placeholder=" " required="" type="password">
                    <label class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-md text-body-md transition-all duration-200 pointer-events-none peer-focus:text-primary" for="password">Password</label>
                    <button aria-label="Toggle password visibility" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors focus:outline-none" type="button">
                        <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                    </button>
                </div>

                <div class="relative floating-input group/input">
                    <input class="w-full h-12 px-4 bg-transparent border border-outline-variant rounded-lg text-on-surface font-body-lg text-body-lg focus:outline-none focus:border-2 focus:border-primary focus:ring-0 transition-colors peer placeholder-transparent pr-12" id="confirm-password" name="confirm-password" placeholder=" " required="" type="password">
                    <label class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-md text-body-md transition-all duration-200 pointer-events-none peer-focus:text-primary" for="confirm-password">Confirm Password</label>
                    <button aria-label="Toggle password visibility" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors focus:outline-none" type="button">
                        <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative flex items-center">
                        <input class="peer appearance-none w-5 h-5 border-2 border-outline rounded-[4px] checked:bg-primary checked:border-primary focus:outline-none focus:ring-2 focus:ring-primary-fixed focus:ring-offset-1 focus:ring-offset-surface-container transition-all cursor-pointer" id="terms" name="terms" required="" type="checkbox" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                        <span class="material-symbols-outlined text-[16px] text-on-primary absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                    </div>
                    <label class="font-body-md text-body-md text-on-surface-variant cursor-pointer select-none" for="terms">
                        I agree to the <a class="text-primary hover:underline font-medium" href="#">Terms of Service</a> &amp; <a class="text-primary hover:underline font-medium" href="#">Privacy Policy</a>
                    </label>
                </div>

                <button class="w-full h-12 bg-primary text-on-primary font-label-lg text-label-lg font-bold shadow-elevation-1 hover:shadow-elevation-2 hover:bg-on-primary-fixed-variant transition-all duration-300 active:scale-[0.98] flex items-center justify-center gap-2 mt-2 overflow-hidden relative rounded-[32px]" type="submit">
                    <span class="relative z-10">Get Started</span>
                    <span class="material-symbols-outlined relative z-10 text-[20px]">arrow_forward</span>
                    <div class="absolute inset-0 bg-white opacity-0 hover:opacity-10 transition-opacity duration-300 pointer-events-none"></div>
                </button>
            </form>
            
            <div class="mt-6 text-center relative z-10">
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Already have an account? 
                    <a class="text-secondary font-label-lg text-label-lg hover:underline hover:text-secondary-fixed-dim transition-colors ml-1" href="login.php">Sign In</a>
                </p>
            </div>
        </div>

        <div class="text-center mt-4 md:mt-6 text-on-surface-variant font-label-md text-label-md opacity-70">
            © 2024 Sedap Food-Tech
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            
            passwordInputs.forEach(input => {
                const toggleBtn = input.parentElement.querySelector('button');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', () => {
                        const icon = toggleBtn.querySelector('.material-symbols-outlined');
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.textContent = 'visibility';
                        } else {
                            input.type = 'password';
                            icon.textContent = 'visibility_off';
                        }
                    });
                }
            });
        });
    </script>
    
    <?php
    // Console log if the database is connected successfully
    if (isset($pdo)) {
        echo "<script>console.log('connected');</script>";
    }
    ?>
</body>
</html>