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
    $input = trim($_POST['email'] ?? $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($input) || empty($password)) {
        $error = "Please enter both your email/username and password.";
    } else {
        try {
            // Find the user by their email or username
            $stmt = $pdo->prepare("SELECT id, name, username, email, password, role, status FROM users WHERE (email = ? OR username = ?)");
            $stmt->execute([$input, $input]);
            $user = $stmt->fetch();

            // Verify if the user exists AND the password matches the hash in the database
            if ($user && password_verify($password, $user['password'])) {
                // Login successful! Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect by role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: ../admin/dashboard.php");
                        break;
                    case 'doctor':
                    case 'nurse':
                    case 'medical_assistant':
                        header("Location: ../doctor/dashboard.php");
                        break;
                    case 'volunteer':
                        header("Location: ../volunteer/dashboard.php");
                        break;
                    case 'user':
                    case 'patient':
                        header("Location: ../dashboard/dashboard.php");
                        break;
                    default:
                        header("Location: ../admin/dashboard.php");
                        break;
                }
                exit();
            } else {
                $error = "Invalid email/username or password.";
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
    <title>SeDaP - Sign In</title>
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
                    <h3 class="text-xs font-semibold text-on-surface">SeDaP Healthcare Portal</h3>
                    <p class="text-[11px] text-on-surface-variant">Connecting providers, volunteers, and communities effortlessly.</p>
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
                <div class="flex flex-col items-center text-center gap-1.5 relative z-10">
                    <div
                        class="w-14 h-14 rounded-full flex items-center justify-center mb-1 shadow-sm overflow-hidden">
                        <img src="sedap.jpg" alt="SEDAP logo" class="w-full h-full object-cover">
                    </div>
                    <h1 class="text-on-surface text-xl sm:text-2xl font-bold tracking-tight">Welcome back</h1>
                    <p class="text-on-surface-variant text-xs sm:text-sm">Sign in to continue to your dashboard</p>
                </div>

                <!-- Form Viewport Slot -->
                <div class="flex flex-col relative z-10 w-full my-auto">
                    <!-- Error Message Display -->
                    <?php if (!empty($error)): ?>
                        <div class="p-2.5 mb-2 rounded-2xl bg-error-container text-error text-center text-xs font-medium border border-error/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined !text-[16px]">error</span>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="flex flex-col gap-2 relative">
                        <!-- Email / Username Field -->
                        <div class="flex flex-col gap-0.5">
                            <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider pl-1" for="email">Username or Email</label>
                            <div class="relative">
                                <span
                                    class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none !text-[20px]">alternate_email</span>
                                <input
                                    class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-11 pr-4 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                    id="email" name="email" placeholder="Enter your email or username" required="" type="text" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center px-1">
                                <label class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider" for="password">Password</label>
                            </div>
                            <div class="relative">
                                <input
                                    class="w-full h-11 bg-surface-container-lowest border border-outline/70 text-on-surface text-sm rounded-[32px] pl-4 pr-11 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all placeholder:text-on-surface-variant/50"
                                    id="password" name="password" placeholder="Enter your password" required="" type="password">
                                <button aria-label="Toggle password visibility"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors focus:outline-none p-1 rounded-full hover:bg-surface-variant/50"
                                    type="button" id="togglePassword">
                                    <span class="material-symbols-outlined !text-[20px]">visibility_off</span>
                                </button>
                            </div>
                            <div class="flex justify-end px-1 mt-0.5">
                                <a class="text-xs text-primary hover:text-primary/80 transition-colors font-medium hover:underline underline-offset-4"
                                    href="forgotpass.php">Forgot password?</a>
                            </div>
                        </div>

                        <!-- Submit Button (Icon left, smaller, clear icon) -->
                        <div class="flex justify-end mt-2">
                            <button
                                class="w-auto px-4 h-9 bg-primary text-on-primary text-sm font-semibold rounded-full hover:bg-primary/90 transition-all shadow-sm hover:shadow-md inline-flex flex-row items-center justify-center gap-1.5 whitespace-nowrap focus:ring-4 focus:ring-primary/20 focus:outline-none active:scale-[0.99]"
                                type="submit">
                                <span class="material-symbols-outlined !text-[18px]">login</span>
                                <span>Sign in</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Pinned Footer Slot (8dp internal gap) -->
                <div class="flex flex-col items-center gap-2 relative z-10 text-xs">
                    <div class="w-16 h-px bg-outline-variant/50"></div>
                    <p class="text-on-surface-variant text-center">
                        Don't have an account? 
                        <a class="text-primary font-semibold hover:underline underline-offset-4 ml-1"
                            href="register.php">
                            Sign up
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script src="js/login.js"></script>
</body>

</html>