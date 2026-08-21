<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/chat_functions.php';

$userId = (int) $_SESSION['user_id'];
$conversationId = (int) ($_GET['id'] ?? 0);

if ($conversationId <= 0 || !isConversationParticipant($pdo, $conversationId, $userId)) {
    header("Location: index.php");
    exit();
}

markConversationRead($pdo, $conversationId, $userId);

$title = getConversationTitle($pdo, $conversationId, $userId);
$messages = getConversationMessages($pdo, $conversationId);
$lastMessageId = !empty($messages) ? (int) end($messages)['id'] : 0;

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
    <title><?php echo htmlspecialchars($title); ?> - Sedap</title>
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
        #messages::-webkit-scrollbar { width: 6px; }
        #messages::-webkit-scrollbar-thumb { background: #bec8c9; border-radius: 10px; }
        .bubble-mine {
            background-color: #005359;
            color: #ffffff;
            border-radius: 18px 18px 4px 18px;
        }
        .bubble-theirs {
            background-color: #fcecd4;
            color: #221a0c;
            border-radius: 18px 18px 18px 4px;
        }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="w-full max-w-[700px] mx-auto h-screen flex flex-col">

    <!-- Header -->
    <div class="flex items-center gap-3 p-4 md:px-8 md:py-5 bg-surface-container-low border-b border-outline-variant/30 shrink-0">
        <a href="index.php" class="text-primary p-2 -ml-2 rounded-full hover:bg-surface-container transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div class="w-10 h-10 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center shrink-0 font-headline">
            <?php echo htmlspecialchars(initials($title)); ?>
        </div>
        <h1 class="font-headline text-lg font-bold text-on-surface truncate"><?php echo htmlspecialchars($title); ?></h1>
    </div>

    <!-- Messages -->
    <div id="messages" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-3">
        <?php if (empty($messages)): ?>
            <p class="text-center text-on-surface-variant text-sm mt-10">No messages yet. Say hello 👋</p>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <?php $isMine = (int) $m['sender_id'] === $userId; ?>
            <div class="flex <?php echo $isMine ? 'justify-end' : 'justify-start'; ?>" data-message-id="<?php echo (int) $m['id']; ?>">
                <div class="max-w-[75%] px-4 py-2.5 <?php echo $isMine ? 'bubble-mine' : 'bubble-theirs'; ?>">
                    <p class="text-sm md:text-base whitespace-pre-wrap break-words"><?php echo htmlspecialchars($m['content']); ?></p>
                    <p class="text-[10px] mt-1 text-right opacity-70"><?php echo date('g:i A', strtotime($m['created_at'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Composer -->
    <form id="composer" class="flex items-center gap-2 p-3 md:p-4 bg-surface-container-low border-t border-outline-variant/30 shrink-0">
        <input
            id="messageInput"
            type="text"
            autocomplete="off"
            placeholder="Type a message"
            class="flex-1 px-4 py-3 bg-white/60 border border-outline-variant/40 rounded-full text-on-background placeholder:text-on-surface-variant focus:border-2 focus:border-primary focus:ring-0 outline-none transition-all"
        >
        <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary w-12 h-12 rounded-full flex items-center justify-center shrink-0 transition-colors active:scale-95">
            <span class="material-symbols-outlined">send</span>
        </button>
    </form>
</div>

<script>
(function () {
    const conversationId = <?php echo (int) $conversationId; ?>;
    const currentUserId = <?php echo (int) $userId; ?>;
    let lastMessageId = <?php echo (int) $lastMessageId; ?>;

    const messagesEl = document.getElementById('messages');
    const form = document.getElementById('composer');
    const input = document.getElementById('messageInput');

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatTime(dateString) {
        const d = new Date(dateString.replace(' ', 'T'));
        return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function appendMessage(m) {
        const isMine = parseInt(m.sender_id, 10) === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = 'flex ' + (isMine ? 'justify-end' : 'justify-start');
        wrapper.dataset.messageId = m.id;
        wrapper.innerHTML = `
            <div class="max-w-[75%] px-4 py-2.5 ${isMine ? 'bubble-mine' : 'bubble-theirs'}">
                <p class="text-sm md:text-base whitespace-pre-wrap break-words">${escapeHtml(m.content)}</p>
                <p class="text-[10px] mt-1 text-right opacity-70">${formatTime(m.created_at)}</p>
            </div>
        `;
        messagesEl.appendChild(wrapper);
    }

    scrollToBottom();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const content = input.value.trim();
        if (!content) return;

        input.value = '';
        input.disabled = true;

        try {
            const res = await fetch('actions/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ conversation_id: conversationId, content: content })
            });
            const data = await res.json();

            if (data.success) {
                appendMessage(data.message);
                lastMessageId = data.message.id;
                scrollToBottom();
            } else {
                alert(data.error || 'Could not send message.');
                input.value = content;
            }
        } catch (err) {
            alert('Network error sending message.');
            input.value = content;
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    async function poll() {
        try {
            const res = await fetch(`actions/fetch_messages.php?conversation_id=${conversationId}&after_id=${lastMessageId}`);
            const data = await res.json();

            if (data.success && data.messages.length > 0) {
                const wasNearBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 100;
                data.messages.forEach(m => {
                    appendMessage(m);
                    lastMessageId = Math.max(lastMessageId, parseInt(m.id, 10));
                });
                if (wasNearBottom) scrollToBottom();
            }
        } catch (err) {
            // Silent fail — next poll cycle will retry.
        }
    }

    setInterval(poll, 3000);
})();
</script>

</body>
</html>
