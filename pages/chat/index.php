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
    $letters = array_map(fn($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));
    return mb_strtoupper(implode('', $letters)) ?: '?';
}
?>
<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Chats - Sedap</title>
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

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="../dashboard/dashboard.php" class="bg-surface-container hover:bg-[#e7d8c1] text-primary p-3 rounded-full shadow-sm border border-[#e7d8c1] flex items-center justify-center transition-all active:scale-95">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="font-headline text-3xl font-bold text-primary">Chats</h1>
        </div>
        <a href="new.php" class="bg-primary hover:bg-primary-container text-on-primary p-3 rounded-full shadow-sm flex items-center justify-center transition-all active:scale-95" title="New chat">
            <span class="material-symbols-outlined">add_comment</span>
        </a>
    </div>

    <!-- Conversation list -->
    <div class="bg-surface-container-low rounded-[32px] shadow-sm border border-[#e7d8c1] flex-1 overflow-hidden">
        <?php if (empty($conversations)): ?>
            <div class="flex flex-col items-center justify-center text-center py-20 px-8">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-[32px] text-primary">chat_bubble</span>
                </div>
                <p class="text-on-surface-variant mb-6">No conversations yet.</p>
                <a href="new.php" class="bg-primary hover:bg-primary-container text-on-primary font-semibold py-3 px-6 rounded-full transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">add_comment</span>
                    Start a chat
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-outline-variant/30">
                <?php foreach ($conversations as $c): ?>
                    <?php
                        $title = $c['type'] === 'group' ? ($c['group_name'] ?: 'Group') : ($c['other_user_name'] ?? 'Unknown user');
                        $preview = $c['last_message'] ? mb_strimwidth($c['last_message'], 0, 50, '…') : 'Say hello 👋';
                        $unread = (int) $c['unread_count'];
                    ?>
                    <a href="conversation.php?id=<?php echo (int) $c['conversation_id']; ?>"
                       class="flex items-center gap-4 p-4 hover:bg-surface-container transition-colors">
                        <div class="w-12 h-12 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center shrink-0 font-headline">
                            <?php echo htmlspecialchars(initials($title)); ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-on-surface truncate"><?php echo htmlspecialchars($title); ?></span>
                                <span class="text-xs text-on-surface-variant shrink-0 ml-2"><?php echo timeAgoShort($c['last_message_at']); ?></span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="text-sm text-on-surface-variant truncate <?php echo $unread ? 'font-semibold text-on-surface' : ''; ?>">
                                    <?php echo htmlspecialchars($preview); ?>
                                </span>
                                <?php if ($unread > 0): ?>
                                    <span class="bg-primary text-on-primary text-xs font-bold rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center shrink-0 ml-2">
                                        <?php echo $unread > 99 ? '99+' : $unread; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
