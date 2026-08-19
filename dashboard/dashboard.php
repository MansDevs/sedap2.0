<?php
session_start();

// Protect the dashboard: If no session exists, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get the user's name from the session (set during login)
$userName = $_SESSION['user_name'] ?? 'User';
?>

<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Dashboard - Sedap</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Using your existing Tailwind configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: "#fff8f2",
                        primary: "#005359",
                        error: "#ba1a1a",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "surface-container": "#fcecd4",
                    },
                    fontFamily: {
                        body: ["Inter", "sans-serif"],
                        headline: ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .mesh-bg {
            background-color: #fff8f2;
            background-image: 
                radial-gradient(at 10% 20%, hsla(184, 72%, 26%, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(33, 100%, 80%, 0.2) 0px, transparent 50%);
        }
    </style>
</head>
<body class="h-full mesh-bg flex flex-col items-center justify-center p-4 antialiased font-body">

    <main class="w-full max-w-[440px]">
        <div class="bg-surface-container p-8 md:p-10 rounded-[32px] shadow-md border border-[#e7d8c1] text-center">
            
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-[40px] text-primary">waving_hand</span>
            </div>
            
            <h1 class="font-headline text-3xl font-bold text-primary mb-2">Dashboard</h1>
            <p class="text-lg text-[#3f494a] mb-8">
                Welcome back, <strong class="text-[#221a0c]"><?php echo htmlspecialchars($userName); ?></strong>!
            </p>

            <!-- The Logout Button -->
            <a href="../auth/logout.php" 
               class="w-full bg-error hover:bg-on-error-container text-white font-semibold py-4 px-6 rounded-[32px] transition-colors shadow-sm flex justify-center items-center gap-2 group">
                <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">logout</span>
                <span>Sign Out</span>
            </a>

        </div>
    </main>

</body>
</html>