<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/chat_functions.php';

$userId = (int) $_SESSION['user_id'];
$conversations = getUserConversations($pdo, $userId);

function timeAgoShort(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('d/m/y', strtotime($datetime));
}

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
    <title>Chats - Sedap</title>
    <link rel="icon" type="image/jpeg" href="../auth/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../../assets/js/theme-config.js"></script>
    <link rel="stylesheet" href="../../assets/css/animations.css">
    <style>
        .mesh-bg {
            background-color: #f7f9fb;
            background-image:
                radial-gradient(at 10% 20%, hsla(212, 100%, 37%, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(188, 100%, 75%, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
        }
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="w-full max-w-[600px] mx-auto min-h-screen flex flex-col p-4 md:p-8">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="../dashboard/dashboard.php" class="bg-surface-container-lowest hover:bg-surface-container-low text-primary p-3 rounded-full shadow-sm border border-outline-variant/40 flex items-center justify-center transition-all active:scale-95">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="font-headline text-3xl font-bold text-primary">Chats</h1>
        </div>
        <a href="new.php" class="bg-primary hover:bg-primary-container text-on-primary p-3 rounded-full shadow-sm flex items-center justify-center transition-all active:scale-95" title="New chat">
            <span class="material-symbols-outlined">add_comment</span>
        </a>
    </div>

    <!-- Coming Soon Card -->
    <div class="interactive-card bg-surface-container-lowest rounded-[32px] p-8 sm:p-12 text-center border border-outline-variant/40 shadow-sm flex-1 flex flex-col items-center justify-center">
        <div class="w-16 h-16 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[36px]">chat</span>
        </div>

        <h2 class="font-headline text-2xl font-bold text-on-surface mb-2">Live Chat</h2>
        <p class="text-on-surface-variant text-sm max-w-md mb-6 leading-relaxed">
            Real-time messaging between healthcare providers, volunteers, staff, and patients is currently under development.
        </p>

        <div class="w-full max-w-sm text-left bg-surface-container-low rounded-2xl p-5 space-y-2.5 mb-6 border border-outline-variant/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-secondary mb-1">Planned features</p>
            <div class="flex items-center gap-2.5 text-xs text-on-surface">
                <span class="material-symbols-outlined text-[18px] text-primary/60">radio_button_unchecked</span>
                <span>Direct 1-on-1 messaging</span>
            </div>
            <div class="flex items-center gap-2.5 text-xs text-on-surface">
                <span class="material-symbols-outlined text-[18px] text-primary/60">radio_button_unchecked</span>
                <span>Care team group channels</span>
            </div>
            <div class="flex items-center gap-2.5 text-xs text-on-surface">
                <span class="material-symbols-outlined text-[18px] text-primary/60">radio_button_unchecked</span>
                <span>Instant sound and badge alerts</span>
            </div>
        </div>

        <span class="inline-block text-xs font-semibold bg-secondary/10 text-secondary px-4 py-1.5 rounded-full uppercase tracking-wider">
            Coming soon
        </span>
    </div>
</div>

</body>
</html>
