<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php'); exit;
}
$userId    = (int)$_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

// Find or create conversation for this patient
$convStmt = $pdo->prepare("SELECT c.id FROM conversations c 
                           JOIN conversation_participants cp ON c.id=cp.conversation_id 
                           WHERE cp.user_id=? LIMIT 1");
$convStmt->execute([$userId]);
$convId = $convStmt->fetchColumn();

if (!$convId) {
    $pdo->prepare("INSERT INTO conversations (type, created_by, created_at) VALUES ('direct', ?, NOW())")->execute([$userId]);
    $convId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $userId]);
    $docId = $pdo->query("SELECT id FROM users WHERE role='doctor' LIMIT 1")->fetchColumn() ?: 2;
    $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $docId]);
}

// Fetch initial messages for instant render
$initialMessages = [];
try {
    $msgStmt = $pdo->prepare("SELECT m.id, m.conversation_id, m.sender_id, m.content, m.deleted_at,
                                      DATE_FORMAT(m.created_at, '%h:%i %p') as time,
                                      u.name as sender_name, u.role as sender_role
                               FROM messages m
                               JOIN users u ON m.sender_id = u.id
                               WHERE m.conversation_id = ?
                               ORDER BY m.created_at ASC");
    $msgStmt->execute([(int)$convId]);
    $initialMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('nav_ask_doctor', 'Tanya Doktor') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link rel="stylesheet" href="../doctor/css/livechat.css?v=<?= time() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-3 px-md-4 py-3 py-md-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">chat</span><?= __('nav_ask_doctor', 'Tanya Doktor / Petugas') ?></h1>
        <p class="page-subtitle"><?= __('page_livechat_sub', 'Dapatkan nasihat perubatan dan panduan rawatan awal') ?></p>
      </div>

      <div class="card chat-app-card shadow-sm mx-auto" style="max-width:850px;height:620px;display:flex;flex-direction:column;">
        <div class="card-header d-flex align-items-center justify-content-between p-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <div class="chat-avatar chat-avatar-em" style="width:38px;height:38px;font-size:0.85rem;">
              <span class="material-symbols-outlined" style="font-size:20px;">medical_services</span>
            </div>
            <div>
              <strong class="chat-text-title">Dr. Sarah (Klinik Kesihatan)</strong>
              <div class="small text-muted" style="font-size:0.75rem;"><span class="text-success fw-bold">•</span> <?= __('chat_status_active', 'Sedia Membantu') ?></div>
            </div>
          </div>
          <span class="badge bg-success rounded-pill px-3 py-1"><?= __('status_active', 'Aktif') ?></span>
        </div>

        <div class="chat-body-scroll flex-grow-1 p-3" id="patientChatBox">
          <!-- Messages loaded dynamically -->
        </div>

        <div class="p-3 chat-footer-area border-top">
          
          <!-- Staged Photo Preview Bar -->
          <div id="patientPhotoPreviewContainer" class="p-2 mb-2 rounded-3 border d-none align-items-center justify-content-between" style="background:rgba(8,115,131,0.08);border-color:rgba(8,115,131,0.2)!important;">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
              <img id="patientPhotoPreviewImg" src="" class="rounded-2 shadow-sm" style="width:48px;height:48px;object-fit:cover;" alt="Preview">
              <div class="min-w-0">
                <div class="small fw-semibold text-truncate chat-text-title" id="patientPhotoPreviewName">photo.jpg</div>
                <div class="small text-muted" id="patientPhotoPreviewSize" style="font-size:0.75rem;">Sedia dihantar • Klik butang hantar</div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px;height:28px;" title="<?= __('btn_remove_photo', 'Batal Foto') ?>" onclick="clearPatientSelectedPhoto()">
              <span class="material-symbols-outlined" style="font-size:16px;">close</span>
            </button>
          </div>

          <!-- Staged Voice Recording Container -->
          <div id="patientVoicePreviewContainer" class="p-2 mb-2 rounded-3 border d-none align-items-center justify-content-between" style="background:rgba(220,53,69,0.08);border-color:rgba(220,53,69,0.25)!important;">
            <div class="d-flex align-items-center gap-2 flex-grow-1 me-2 overflow-hidden">
              <div id="patientRecordingIndicator" class="d-flex align-items-center gap-2">
                <span class="recording-pulse-dot"></span>
                <span class="small fw-bold text-danger" id="patientRecordingTimer">0:00</span>
                <span class="small text-muted d-none d-sm-inline">Merakam Suara / Recording...</span>
              </div>
              <div id="patientAudioPlaybackWrapper" class="d-none flex-grow-1 d-flex align-items-center gap-2">
                <audio id="patientVoiceAudioPreview" controls class="w-100" style="height:34px;"></audio>
              </div>
            </div>
            <div class="d-flex align-items-center gap-1">
              <button type="button" id="btnPatientStopRecord" class="btn btn-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" onclick="stopPatientVoiceRecording()">
                <span class="material-symbols-outlined" style="font-size:16px;">stop</span> Selesai
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px;height:28px;" title="Batal / Discard" onclick="cancelPatientVoiceRecording()">
                <span class="material-symbols-outlined" style="font-size:16px;">close</span>
              </button>
            </div>
          </div>

          <form onsubmit="sendPatientMsg(event)" class="d-flex align-items-center gap-2">
            
            <!-- Photo Upload Trigger Button -->
            <button type="button" class="btn btn-ghost-secondary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;" title="<?= __('btn_upload_photo', 'Muat Naik Foto') ?>" onclick="document.getElementById('patientPhotoInput').click()">
              <span class="material-symbols-outlined text-primary" style="font-size:24px;">add_a_photo</span>
            </button>
            <input type="file" id="patientPhotoInput" accept="image/png, image/jpeg, image/jpg, image/webp" style="display:none;" onchange="handlePatientPhotoSelect(this)">

            <div class="position-relative flex-grow-1">
              <input type="text" id="patientInput" class="form-control chat-input-pill pe-5" placeholder="<?= __('ph_type_message', 'Tulis soalan kesihatan anda di sini...') ?>" autocomplete="off">
              <span id="patientMicRecordBtn" class="material-symbols-outlined position-absolute end-0 top-50 translate-middle-y me-3 text-muted" style="font-size:22px;cursor:pointer;transition:color .2s;" title="Rakam Mesej Suara / Record Voice" onclick="togglePatientVoiceRecording()">mic</span>
            </div>

            <button type="submit" class="btn chat-send-btn" title="<?= __('btn_send', 'Hantar') ?>">
              <span class="material-symbols-outlined" style="font-size:20px;">send</span>
            </button>
          </form>
        </div>
      </div>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>

<!-- Dedicated Standalone Fullscreen Lightbox Overlay with Zoom & Pan -->
<div id="chatLightboxOverlay" class="chat-lightbox-overlay" onclick="handleOverlayClick(event)">
  <!-- Floating Top Bar with Controls -->
  <div class="chat-lightbox-toolbar">
    <div class="chat-lightbox-btn-group">
      <button type="button" class="chat-lightbox-btn" title="Zum Keluar (-)" onclick="zoomImage(-0.25)">
        <span class="material-symbols-outlined" style="font-size:20px;">zoom_out</span>
      </button>
      <span class="px-2 py-1 small text-white fw-bold d-flex align-items-center" id="zoomLevelText" style="min-width:55px;justify-content:center;">100%</span>
      <button type="button" class="chat-lightbox-btn" title="Zum Masuk (+)" onclick="zoomImage(0.25)">
        <span class="material-symbols-outlined" style="font-size:20px;">zoom_in</span>
      </button>
      <button type="button" class="chat-lightbox-btn" title="Set Semula Zum (100%)" onclick="resetZoomImage()">
        <span class="material-symbols-outlined" style="font-size:20px;">restart_alt</span>
      </button>
    </div>
    <button type="button" class="chat-lightbox-btn-close" title="Tutup (Esc)" onclick="closeLightbox()">
      <span class="material-symbols-outlined" style="font-size:24px;">close</span>
    </button>
  </div>

  <!-- Big Image Container -->
  <div id="zoomViewport" class="chat-lightbox-viewport" onclick="event.stopPropagation()">
    <img src="" id="lightboxImg" class="chat-lightbox-img" alt="Foto Klinikal" ondblclick="toggleDoubleZoom(event)">
  </div>

  <div class="text-white-50 small mt-3 d-none d-md-block" style="text-shadow:0 1px 4px rgba(0,0,0,0.8);pointer-events:none;">
    <span class="material-symbols-outlined align-middle" style="font-size:16px;">touch_app</span> Klik dua kali / Skrol tetikus untuk zum • Seret untuk gerakkan • Esc untuk tutup
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
<script>
var CONV_ID = <?= (int)$convId ?>;
var API_URL = '/sedap2.0/pages/shared/actions/chat_api.php';
var patientStagedPhoto = null;

var currentZoom = 1.0;
var panX = 0, panY = 0;
var isDragging = false, startDragX = 0, startDragY = 0;

// Dynamic Realistic Waveform Heights Profile (Dense & Thin)
var WAVEFORM_PROFILE = [
  25, 40, 70, 95, 60, 35, 80, 100, 75, 45, 85, 95, 65, 30, 60, 85, 100, 80, 50, 70, 90, 65, 40, 60, 85, 95, 70, 45, 30, 55, 75, 90, 60, 35, 20
];

function generateWaveformBarsHtml() {
  let barsHtml = '';
  WAVEFORM_PROFILE.forEach(height => {
    barsHtml += `<div class="voice-bar" style="height: ${height}%;"></div>`;
  });
  return barsHtml;
}

function formatMessageContent(content) {
  if (!content) return '';
  let safe = escapeHtml(content).replace(/\n/g, '<br>');
  safe = safe.replace(/\[img\](.*?)\[\/img\]/gi, function(match, url) {
    return `<div class="chat-img-wrapper"><img src="${url}" class="img-fluid chat-img-thumb" onclick="openLightbox('${url}')" alt="Photo Attachment"></div>`;
  });
  safe = safe.replace(/\[audio\](.*?)\[\/audio\]/gi, function(match, url) {
    const bars = generateWaveformBarsHtml();
    return `<div class="sedap-voice-player" data-audio-src="${url}"><div class="voice-player-main-row"><button type="button" class="btn-voice-play-toggle" onclick="sedapToggleVoicePlay(this)" title="Play / Pause"><span class="material-symbols-outlined voice-play-icon">play_arrow</span></button><div class="voice-waveform-container" onclick="sedapSeekVoice(event, this)"><div class="voice-waveform-bars">${bars}</div><div class="voice-waveform-dot" style="left:0%;"></div></div><button type="button" class="btn-voice-speed" onclick="sedapToggleVoiceSpeed(this)" title="Playback Speed">1×</button></div><div class="voice-time-row"><span class="voice-time-current">0:00</span><span class="voice-time-duration">--:--</span></div><audio class="d-none voice-hidden-audio" preload="metadata" src="${url}" ontimeupdate="sedapOnVoiceTimeUpdate(this)" onloadedmetadata="sedapOnVoiceLoaded(this)" onended="sedapOnVoiceEnded(this)"></audio></div>`;
  });
  return safe;
}

let currentlyPlayingAudio = null;

function sedapToggleVoicePlay(btn) {
  const player = btn.closest('.sedap-voice-player');
  if (!player) return;
  const audio = player.querySelector('.voice-hidden-audio');
  const icon = btn.querySelector('.voice-play-icon');
  if (!audio) return;

  if (audio.paused) {
    if (currentlyPlayingAudio && currentlyPlayingAudio !== audio) {
      currentlyPlayingAudio.pause();
      const prevPlayer = currentlyPlayingAudio.closest('.sedap-voice-player');
      if (prevPlayer) {
        const prevIcon = prevPlayer.querySelector('.voice-play-icon');
        if (prevIcon) prevIcon.textContent = 'play_arrow';
      }
    }
    audio.play();
    currentlyPlayingAudio = audio;
    if (icon) icon.textContent = 'pause';
  } else {
    audio.pause();
    if (icon) icon.textContent = 'play_arrow';
  }
}

function sedapOnVoiceLoaded(audio) {
  const player = audio.closest('.sedap-voice-player');
  if (!player) return;
  const durationEl = player.querySelector('.voice-time-duration');
  if (durationEl && !isNaN(audio.duration) && isFinite(audio.duration)) {
    const mins = Math.floor(audio.duration / 60);
    const secs = Math.floor(audio.duration % 60);
    durationEl.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
  }
}

function sedapOnVoiceTimeUpdate(audio) {
  const player = audio.closest('.sedap-voice-player');
  if (!player) return;
  const currentEl = player.querySelector('.voice-time-current');
  const durationEl = player.querySelector('.voice-time-duration');
  const dot = player.querySelector('.voice-waveform-dot');
  const bars = player.querySelectorAll('.voice-bar');

  if (currentEl) {
    const mins = Math.floor(audio.currentTime / 60);
    const secs = Math.floor(audio.currentTime % 60);
    currentEl.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
  }

  if (durationEl && (durationEl.textContent === '--:--' || durationEl.textContent === '0:00') && !isNaN(audio.duration) && isFinite(audio.duration)) {
    const dmins = Math.floor(audio.duration / 60);
    const dsecs = Math.floor(audio.duration % 60);
    durationEl.textContent = `${dmins}:${dsecs < 10 ? '0' : ''}${dsecs}`;
  }

  const progress = audio.duration ? (audio.currentTime / audio.duration) : 0;
  const percent = Math.min(Math.max(progress * 100, 0), 100);

  if (dot) {
    dot.style.left = `${percent}%`;
  }

  const activeCount = Math.floor(progress * bars.length);
  bars.forEach((bar, idx) => {
    if (idx <= activeCount) {
      bar.classList.add('active');
    } else {
      bar.classList.remove('active');
    }
  });
}

function sedapOnVoiceEnded(audio) {
  const player = audio.closest('.sedap-voice-player');
  if (!player) return;
  const icon = player.querySelector('.voice-play-icon');
  const dot = player.querySelector('.voice-waveform-dot');
  const bars = player.querySelectorAll('.voice-bar');
  const currentEl = player.querySelector('.voice-time-current');

  if (icon) icon.textContent = 'play_arrow';
  if (dot) dot.style.left = '0%';
  bars.forEach(bar => bar.classList.remove('active'));
  if (currentEl) currentEl.textContent = '0:00';
  audio.currentTime = 0;
}

function sedapSeekVoice(e, container) {
  const player = container.closest('.sedap-voice-player');
  if (!player) return;
  const audio = player.querySelector('.voice-hidden-audio');
  if (!audio || !audio.duration) return;

  const rect = container.getBoundingClientRect();
  const clickX = e.clientX - rect.left;
  const ratio = Math.min(Math.max(clickX / rect.width, 0), 1);
  audio.currentTime = ratio * audio.duration;
  sedapOnVoiceTimeUpdate(audio);
}

function sedapToggleVoiceSpeed(btn) {
  const player = btn.closest('.sedap-voice-player');
  if (!player) return;
  const audio = player.querySelector('.voice-hidden-audio');
  if (!audio) return;

  const speeds = [1.0, 1.5, 2.0];
  const labels = ['1×', '1.5×', '2×'];
  let curIdx = speeds.indexOf(audio.playbackRate);
  if (curIdx === -1) curIdx = 0;
  const nextIdx = (curIdx + 1) % speeds.length;

  audio.playbackRate = speeds[nextIdx];
  btn.textContent = labels[nextIdx];
}

function openLightbox(url) {
  const img = document.getElementById('lightboxImg');
  if (img) img.src = url;

  resetZoomImage();

  const overlay = document.getElementById('chatLightboxOverlay');
  if (overlay) {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    setupZoomListeners();
  }
}

function closeLightbox() {
  const overlay = document.getElementById('chatLightboxOverlay');
  if (overlay) {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
  resetZoomImage();
}

function handleOverlayClick(e) {
  if (e.target.id === 'chatLightboxOverlay') {
    closeLightbox();
  }
}

window.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeLightbox();
  }
});

function applyZoomTransform() {
  const img = document.getElementById('lightboxImg');
  const textEl = document.getElementById('zoomLevelText');
  const viewport = document.getElementById('zoomViewport');

  if (img) {
    img.style.transform = `scale(${currentZoom}) translate(${panX}px, ${panY}px)`;
  }
  if (textEl) {
    textEl.textContent = Math.round(currentZoom * 100) + '%';
  }
  if (viewport) {
    viewport.style.cursor = currentZoom > 1 ? (isDragging ? 'grabbing' : 'grab') : 'default';
  }
}

function zoomImage(delta) {
  currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 4.0);
  if (currentZoom <= 1.0) {
    panX = 0;
    panY = 0;
  }
  applyZoomTransform();
}

function resetZoomImage() {
  currentZoom = 1.0;
  panX = 0;
  panY = 0;
  applyZoomTransform();
}

function toggleDoubleZoom(e) {
  if (e) e.preventDefault();
  if (currentZoom > 1.2) {
    resetZoomImage();
  } else {
    currentZoom = 2.2;
    applyZoomTransform();
  }
}

let zoomListenersAttached = false;
function setupZoomListeners() {
  if (zoomListenersAttached) return;
  zoomListenersAttached = true;

  const viewport = document.getElementById('zoomViewport');
  if (!viewport) return;

  viewport.addEventListener('wheel', (e) => {
    e.preventDefault();
    if (e.deltaY < 0) {
      zoomImage(0.2);
    } else {
      zoomImage(-0.2);
    }
  }, { passive: false });

  viewport.addEventListener('mousedown', (e) => {
    if (currentZoom <= 1) return;
    isDragging = true;
    startDragX = e.clientX - panX * currentZoom;
    startDragY = e.clientY - panY * currentZoom;
    applyZoomTransform();
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDragging || currentZoom <= 1) return;
    panX = (e.clientX - startDragX) / currentZoom;
    panY = (e.clientY - startDragY) / currentZoom;
    applyZoomTransform();
  });

  window.addEventListener('mouseup', () => {
    if (isDragging) {
      isDragging = false;
      applyZoomTransform();
    }
  });

  viewport.addEventListener('touchstart', (e) => {
    if (currentZoom <= 1 || e.touches.length !== 1) return;
    isDragging = true;
    startDragX = e.touches[0].clientX - panX * currentZoom;
    startDragY = e.touches[0].clientY - panY * currentZoom;
  });

  viewport.addEventListener('touchmove', (e) => {
    if (!isDragging || currentZoom <= 1 || e.touches.length !== 1) return;
    panX = (e.touches[0].clientX - startDragX) / currentZoom;
    panY = (e.touches[0].clientY - startDragY) / currentZoom;
    applyZoomTransform();
  });

  viewport.addEventListener('touchend', () => {
    isDragging = false;
  });
}

function handlePatientPhotoSelect(input) {
  if (!input.files || input.files.length === 0) return;
  const file = input.files[0];

  patientStagedPhoto = file;
  const container = document.getElementById('patientPhotoPreviewContainer');
  const img = document.getElementById('patientPhotoPreviewImg');
  const nameEl = document.getElementById('patientPhotoPreviewName');
  const sizeEl = document.getElementById('patientPhotoPreviewSize');

  const sizeKb = Math.round(file.size / 1024);
  nameEl.textContent = file.name;
  sizeEl.textContent = `${sizeKb} KB • Sedia dihantar (Klik butang hantar)`;

  const reader = new FileReader();
  reader.onload = function(e) {
    img.src = e.target.result;
    container.classList.remove('d-none');
    container.classList.add('d-flex');
  };
  reader.readAsDataURL(file);
}

function clearPatientSelectedPhoto() {
  patientStagedPhoto = null;
  const fileInput = document.getElementById('patientPhotoInput');
  if (fileInput) fileInput.value = '';
  const container = document.getElementById('patientPhotoPreviewContainer');
  if (container) {
    container.classList.remove('d-flex');
    container.classList.add('d-none');
  }
}

let lastPatientMsgCount = 0;
let lastRenderedPatientMessagesJson = '';

async function loadPatientMessages() {
  try {
    const res = await fetch(`${API_URL}?action=get_messages&conversation_id=${CONV_ID}`);
    const data = await res.json();
    if (!data.ok || !data.messages) return;

    if (lastPatientMsgCount > 0 && data.messages.length > lastPatientMsgCount) {
      const newMsgs = data.messages.slice(lastPatientMsgCount);
      const hasIncoming = newMsgs.some(m => m.sender_id != <?= $userId ?> && !m.is_deleted);
      if (hasIncoming && typeof sedapPlayNotificationSound === 'function') {
        sedapPlayNotificationSound();
      }
    }
    lastPatientMsgCount = data.messages.length;

    // Prevent audio playback from being restarted/destroyed during polling
    if (currentlyPlayingAudio && !currentlyPlayingAudio.paused) {
      return;
    }

    const currentJson = JSON.stringify(data.messages);
    if (currentJson === lastRenderedPatientMessagesJson) {
      return; // Keep existing DOM intact
    }
    lastRenderedPatientMessagesJson = currentJson;

    const box = document.getElementById('patientChatBox');
    const isAtBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 60;

    let html = '';
    data.messages.forEach(m => {
      const isMe = (m.sender_id == <?= $userId ?>);
      const isDeleted = m.is_deleted;
      const isVoice = !isDeleted && m.content && m.content.includes('[audio]');
      const voiceBubbleClass = isVoice ? 'msg-bubble-voice' : '';
      const formattedContent = isDeleted 
        ? `<div class="msg-deleted-bubble d-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:16px;">block</span> ${escapeHtml(m.content)}</div>`
        : formatMessageContent(m.content);

      const rawCopy = escapeAttr(m.raw_content ? m.raw_content.replace(/\[img\](.*?)\[\/img\]/gi, '$1').replace(/\[audio\](.*?)\[\/audio\]/gi, '$1') : m.content);

      const actionMenu = !isDeleted ? `
        <div class="dropdown">
          <button class="btn btn-ghost-secondary btn-sm msg-action-btn" type="button" data-coreui-toggle="dropdown" aria-expanded="false" title="Pilihan">
            <span class="material-symbols-outlined" style="font-size:16px;">more_vert</span>
          </button>
          <ul class="dropdown-menu shadow-sm ${isMe ? 'dropdown-menu-end' : ''}" style="border-radius:0.75rem;font-size:0.85rem;">
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="copyMessageText('${rawCopy}')">
                <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span> <?= __('btn_copy', 'Salin') ?>
              </a>
            </li>
            ${m.can_delete ? `
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="deletePatientMessage(${m.id})">
                <span class="material-symbols-outlined" style="font-size:16px;">delete</span> <?= __('btn_delete', 'Padam Mesej') ?>
              </a>
            </li>` : ''}
          </ul>
        </div>
      ` : '';

      if (isMe) {
        html += `
          <div class="d-flex flex-column align-items-end mb-3 msg-wrapper">
            <div class="d-flex align-items-end gap-2 justify-content-end w-100">
              ${actionMenu}
              ${isDeleted ? formattedContent : `<div class="msg-sent-bubble ${voiceBubbleClass}">${formattedContent}</div>`}
            </div>
            <div class="text-muted small mt-1 me-2" style="font-size:0.72rem;">${m.time}</div>
          </div>
        `;
      } else {
        html += `
          <div class="d-flex flex-column align-items-start mb-3 msg-wrapper">
            <div class="d-flex align-items-start gap-2 w-100">
              <div class="chat-avatar bg-primary text-white" style="width:32px;height:32px;font-size:0.75rem;">
                <span class="material-symbols-outlined" style="font-size:16px;">medical_services</span>
              </div>
              ${isDeleted ? formattedContent : `<div class="msg-received-bubble ${voiceBubbleClass}">${formattedContent}</div>`}
              ${actionMenu}
            </div>
            <div class="text-muted small mt-1 ms-5" style="font-size:0.72rem;">${m.time}</div>
          </div>
        `;
      }
    });

    if (data.messages.length === 0) {
      html = `
        <div class="text-center text-muted p-5 small">
          <span class="material-symbols-outlined d-block mb-2" style="font-size:36px;opacity:.4;">chat</span>
          Selamat sejahtera. Sila kemukakan sebarang pertanyaan klinikal atau muat naik foto keadaan untuk semakan doktor.
        </div>
      `;
    }

    box.innerHTML = html;
    if (isAtBottom) {
      box.scrollTop = box.scrollHeight;
    }
  } catch (err) {
    console.error("Patient chat load error:", err);
  }
}

async function sendPatientMsg(e) {
  if (e) e.preventDefault();
  
  // If recording is in progress or a voice note is ready, send the voice note!
  if ((patientMediaRecorder && patientMediaRecorder.state === 'recording') || patientRecordedVoiceBlob) {
    await sendPatientVoiceRecording();
    return;
  }

  const inp = document.getElementById('patientInput');
  const txt = inp ? inp.value.trim() : '';

  if (!patientStagedPhoto && !txt) return;

  if (inp) inp.value = '';

  try {
    const formData = new FormData();
    formData.append('conversation_id', CONV_ID);

    if (patientStagedPhoto) {
      formData.append('action', 'upload_photo');
      formData.append('photo', patientStagedPhoto);
      if (txt) formData.append('caption', txt);
      clearPatientSelectedPhoto();
    } else {
      formData.append('action', 'send_message');
      formData.append('content', txt);
    }

    const res = await fetch(API_URL, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) {
      await loadPatientMessages();
    } else {
      alert(data.error || 'Gagal menghantar mesej.');
    }
  } catch (err) {
    console.error("Send message error:", err);
  }
}

// Voice Message Recording Engine for Patient
let patientMediaRecorder = null;
let patientAudioChunks = [];
let patientVoiceRecordingStream = null;
let patientRecordingTimerInterval = null;
let patientRecordingSeconds = 0;
let patientRecordedVoiceBlob = null;

async function togglePatientVoiceRecording() {
  if (patientMediaRecorder && patientMediaRecorder.state === 'recording') {
    stopPatientVoiceRecording();
  } else {
    startPatientVoiceRecording();
  }
}

async function startPatientVoiceRecording() {
  try {
    patientVoiceRecordingStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    
    let mimeType = 'audio/webm';
    if (typeof MediaRecorder.isTypeSupported === 'function') {
      if (MediaRecorder.isTypeSupported('audio/webm')) mimeType = 'audio/webm';
      else if (MediaRecorder.isTypeSupported('audio/mp4')) mimeType = 'audio/mp4';
      else if (MediaRecorder.isTypeSupported('audio/ogg')) mimeType = 'audio/ogg';
      else mimeType = '';
    }

    patientMediaRecorder = mimeType ? new MediaRecorder(patientVoiceRecordingStream, { mimeType }) : new MediaRecorder(patientVoiceRecordingStream);
    patientAudioChunks = [];
    patientRecordedVoiceBlob = null;

    patientMediaRecorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) {
        patientAudioChunks.push(e.data);
      }
    };

    patientMediaRecorder.start(250);

    // UI Updates
    patientRecordingSeconds = 0;
    const timerEl = document.getElementById('patientRecordingTimer');
    if (timerEl) timerEl.textContent = '0:00';

    const container = document.getElementById('patientVoicePreviewContainer');
    const recIndicator = document.getElementById('patientRecordingIndicator');
    const playbackWrapper = document.getElementById('patientAudioPlaybackWrapper');
    const btnStop = document.getElementById('btnPatientStopRecord');
    const btnSend = document.getElementById('btnPatientSendVoice');
    const micBtn = document.getElementById('patientMicRecordBtn');

    if (container) {
      container.classList.remove('d-none');
      container.classList.add('d-flex');
    }
    if (recIndicator) recIndicator.classList.remove('d-none');
    if (playbackWrapper) {
      playbackWrapper.classList.remove('d-flex');
      playbackWrapper.classList.add('d-none');
    }
    if (btnStop) btnStop.classList.remove('d-none');
    if (btnSend) {
      btnSend.classList.remove('d-flex');
      btnSend.classList.add('d-none');
    }
    if (micBtn) {
      micBtn.style.color = '#dc3545';
      micBtn.classList.add('text-danger');
    }

    clearInterval(patientRecordingTimerInterval);
    patientRecordingTimerInterval = setInterval(() => {
      patientRecordingSeconds++;
      const mins = Math.floor(patientRecordingSeconds / 60);
      const secs = patientRecordingSeconds % 60;
      if (timerEl) timerEl.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }, 1000);

  } catch (err) {
    console.error("Patient mic access error:", err);
    alert("Tidak dapat mengakses mikrofon. Sila benarkan akses mikrofon pada pelayar web anda.");
  }
}

function stopPatientVoiceRecording() {
  return new Promise((resolve) => {
    clearInterval(patientRecordingTimerInterval);
    if (!patientMediaRecorder || patientMediaRecorder.state !== 'recording') {
      resolve(patientRecordedVoiceBlob);
      return;
    }

    patientMediaRecorder.onstop = () => {
      patientRecordedVoiceBlob = new Blob(patientAudioChunks, { type: patientMediaRecorder.mimeType || 'audio/webm' });
      const audioUrl = URL.createObjectURL(patientRecordedVoiceBlob);
      
      const previewAudio = document.getElementById('patientVoiceAudioPreview');
      if (previewAudio) {
        previewAudio.src = audioUrl;
      }

      const recIndicator = document.getElementById('patientRecordingIndicator');
      const playbackWrapper = document.getElementById('patientAudioPlaybackWrapper');
      const btnStop = document.getElementById('btnPatientStopRecord');
      const btnSend = document.getElementById('btnPatientSendVoice');

      if (recIndicator) recIndicator.classList.add('d-none');
      if (playbackWrapper) {
        playbackWrapper.classList.remove('d-none');
        playbackWrapper.classList.add('d-flex');
      }
      if (btnStop) btnStop.classList.add('d-none');
      if (btnSend) {
        btnSend.classList.remove('d-none');
        btnSend.classList.add('d-flex');
      }

      if (patientVoiceRecordingStream) {
        patientVoiceRecordingStream.getTracks().forEach(track => track.stop());
      }
      resolve(patientRecordedVoiceBlob);
    };

    patientMediaRecorder.stop();

    const micBtn = document.getElementById('patientMicRecordBtn');
    if (micBtn) {
      micBtn.style.color = '';
      micBtn.classList.remove('text-danger');
    }
  });
}

function cancelPatientVoiceRecording() {
  clearInterval(patientRecordingTimerInterval);
  if (patientMediaRecorder && patientMediaRecorder.state === 'recording') {
    patientMediaRecorder.stop();
  }
  if (patientVoiceRecordingStream) {
    patientVoiceRecordingStream.getTracks().forEach(track => track.stop());
  }
  patientAudioChunks = [];
  patientRecordedVoiceBlob = null;

  const container = document.getElementById('patientVoicePreviewContainer');
  if (container) {
    container.classList.remove('d-flex');
    container.classList.add('d-none');
  }
  const micBtn = document.getElementById('patientMicRecordBtn');
  if (micBtn) {
    micBtn.style.color = '';
    micBtn.classList.remove('text-danger');
  }
  const previewAudio = document.getElementById('patientVoiceAudioPreview');
  if (previewAudio) previewAudio.src = '';
}

async function sendPatientVoiceRecording() {
  let blob = patientRecordedVoiceBlob;
  if (patientMediaRecorder && patientMediaRecorder.state === 'recording') {
    blob = await stopPatientVoiceRecording();
  }

  if (!blob || !CONV_ID) return;

  const btnSend = document.getElementById('btnPatientSendVoice');
  if (btnSend) {
    btnSend.disabled = true;
    btnSend.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Hantar...';
  }

  try {
    const formData = new FormData();
    formData.append('action', 'upload_voice');
    formData.append('conversation_id', CONV_ID);
    formData.append('voice_note', blob, 'voice.webm');

    const res = await fetch(API_URL, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) {
      cancelPatientVoiceRecording();
      await loadPatientMessages();
    } else {
      alert(data.error || 'Gagal menghantar mesej suara.');
    }
  } catch (err) {
    console.error("Patient voice send error:", err);
    alert('Ralat rangkaian semasa menghantar mesej suara.');
  } finally {
    if (btnSend) {
      btnSend.disabled = false;
      btnSend.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">send</span> Hantar';
    }
  }
}

async function deletePatientMessage(msgId) {
  if (!confirm('Adakah anda pasti mahu memadamkan mesej ini? Ia akan digantikan dengan "Mesej dipadam".')) return;

  try {
    const formData = new FormData();
    formData.append('action', 'delete_message');
    formData.append('message_id', msgId);

    const res = await fetch(API_URL, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) {
      await loadPatientMessages();
    } else {
      alert(data.error || 'Gagal memadam mesej.');
    }
  } catch (err) {
    console.error("Delete error:", err);
  }
}

function copyMessageText(text) {
  if (!text) return;
  const txt = document.createElement('textarea');
  txt.innerHTML = text;
  const decoded = txt.value;

  if (navigator.clipboard) {
    navigator.clipboard.writeText(decoded).then(() => {
      showToast('Teks disalin ke papan keratan!');
    });
  } else {
    txt.select();
    document.execCommand('copy');
    showToast('Teks disalin ke papan keratan!');
  }
}

function showToast(msg) {
  const existing = document.getElementById('patientChatToast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'patientChatToast';
  toast.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-2 bg-dark text-white rounded-pill shadow-lg small';
  toast.style.zIndex = '9999';
  toast.style.animation = 'fadeIn .2s ease';
  toast.textContent = msg;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity .3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 2000);
}

function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function initPatientLiveChat() {
  if (!document.getElementById('patientChatBox')) return;
  loadPatientMessages();
  if (window.sedapPatientChatPollTimer) clearInterval(window.sedapPatientChatPollTimer);
  window.sedapPatientChatPollTimer = setInterval(() => {
    if (!document.getElementById('patientChatBox')) {
      clearInterval(window.sedapPatientChatPollTimer);
      return;
    }
    loadPatientMessages();
  }, 3500);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPatientLiveChat);
} else {
  initPatientLiveChat();
}
window.addEventListener('sedap:page-loaded', initPatientLiveChat);
</script>
</body>
</html>
