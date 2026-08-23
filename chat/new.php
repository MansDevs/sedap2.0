<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/chat_functions.php';

$userId = (int) $_SESSION['user_id'];
$users = getAllOtherUsers($pdo, $userId);
$error = isset($_GET['error']) ? 'Could not start that chat. Please try again.' : '';

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(function ($p) { return mb_substr($p, 0, 1); }, array_slice($parts, 0, 2));
    return mb_strtoupper(implode('', $letters)) ?: '?';
}
?>
<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>New Chat - Sedap</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: "#fff8f2",
                        primary: "#005359",
                        "primary-container": "#136d74",
                        "on-primary": "#ffffff",
                        secondary: "#835500",
                        "surface-container": "#fcecd4",
                        "surface-container-low": "#fff2e0",
                        "on-surface": "#221a0c",
                        "on-surface-variant": "#3f494a",
                        "outline-variant": "#bec8c9",
                        error: "#ba1a1a",
                        "error-container": "#ffdad6",
                    },
                    fontFamily: {
                        body: ["Inter", "sans-serif"],
                        headline: ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        };
    </script>
    <style>
        .mesh-bg {
            background-color: #fff8f2;
            background-image:
                radial-gradient(at 10% 20%, hsla(184, 72%, 26%, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(33, 100%, 80%, 0.2) 0px, transparent 50%);
            background-attachment: fixed;
        }
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="w-full max-w-[600px] mx-auto min-h-screen flex flex-col p-4 md:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="bg-surface-container hover:bg-[#e7d8c1] text-primary p-3 rounded-full shadow-sm border border-[#e7d8c1] flex items-center justify-center transition-all active:scale-95">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1 class="font-headline text-3xl font-bold text-primary">New Chat</h1>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg bg-error-container text-error text-center font-medium border border-error/20">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="bg-surface-container-low rounded-[32px] shadow-sm border border-[#e7d8c1] flex-1 overflow-hidden">
        <?php if (empty($users)): ?>
            <div class="text-center py-16 px-8 text-on-surface-variant">
                No other users available yet.
            </div>
        <?php else: ?>
            <div class="divide-y divide-outline-variant/30">
                <?php foreach ($users as $u): ?>
                    <form action="actions/start_conversation.php" method="POST">
                        <input type="hidden" name="other_user_id" value="<?php echo (int) $u['id']; ?>">
                        <button type="submit" class="w-full flex items-center gap-4 p-4 hover:bg-surface-container transition-colors text-left">
                            <div class="w-12 h-12 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center shrink-0 font-headline">
                                <?php echo htmlspecialchars(initials($u['name'])); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-on-surface truncate"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="text-sm text-on-surface-variant truncate"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                            <span class="material-symbols-outlined text-outline-variant">chevron_right</span>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
