/**
 * SeDaP Doctor Clinical Live Chat Engine — Real-time DB Integration with Photo Support
 */

var API_URL = '/sedap2.0/pages/shared/actions/chat_api.php';
var queueData = window.sedapInitialQueue || [];
var activeQueueItem = window.sedapFirstPatient || null;
var currentConvId = window.sedapFirstPatient ? window.sedapFirstPatient.conversation_id : null;
var pollTimer = null;

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
  // Replace [img]url[/img] with interactive thumbnail
  safe = safe.replace(/\[img\](.*?)\[\/img\]/gi, function(match, url) {
    return `<div class="chat-img-wrapper"><img src="${url}" class="img-fluid chat-img-thumb" onclick="openLightbox('${url}')" alt="Photo Attachment"></div>`;
  });
  // Replace [audio]url[/audio] with custom WhatsApp-style voice player
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

let currentZoom = 1.0;
let panX = 0, panY = 0;
let isDragging = false, startDragX = 0, startDragY = 0;

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

  // Mouse wheel zoom
  viewport.addEventListener('wheel', (e) => {
    e.preventDefault();
    if (e.deltaY < 0) {
      zoomImage(0.2);
    } else {
      zoomImage(-0.2);
    }
  }, { passive: false });

  // Mouse Drag / Pan
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

  // Touch support for mobile / tablets
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

let previousTotalUnread = -1;
let lastMessageCount = 0;

// 1. Fetch & Render Real-Time Triage Queue from MySQL (sedap.users.role = 'user')
async function loadQueue(keepActive = true) {
  try {
    const res = await fetch(`${API_URL}?action=get_queue`);
    const data = await res.json();
    if (!data.ok || !data.queue) return;

    // Check for incoming unread messages across queue to play notification chime
    let currentTotalUnread = 0;
    data.queue.forEach(q => {
      if (activeQueueItem && q.key === activeQueueItem.key) return;
      currentTotalUnread += (q.unread || 0);
    });

    if (previousTotalUnread !== -1 && currentTotalUnread > previousTotalUnread) {
      if (typeof sedapPlayNotificationSound === 'function') {
        sedapPlayNotificationSound();
      }
    }
    previousTotalUnread = currentTotalUnread;

    queueData = data.queue;

    if (!activeQueueItem && queueData.length > 0) {
      selectPatient(queueData[0].key);
    } else if (keepActive && activeQueueItem) {
      const activeMatch = queueData.find(q => q.key === activeQueueItem.key);
      if (activeMatch) activeMatch.unread = 0;
      renderQueueList();
      // Refresh current conversation messages quietly
      loadActiveMessages(true);
    } else {
      renderQueueList();
    }
  } catch (err) {
    console.error("Queue load error:", err);
  }
}

function renderQueueList() {
  const container = document.getElementById('queueListContainer');
  if (!container) return;

  const filterOnlyPriority = window.filterOnlyPriority || false;

  let html = '';
  queueData.forEach(item => {
    if (filterOnlyPriority && !item.priority) return;

    const isActive = activeQueueItem && activeQueueItem.key === item.key;
    const priorityClass = item.priority ? 'priority-alert' : '';
    const activeClass = isActive ? 'active' : '';
    const isRoomAlert = item.priority;

    html += `
      <div class="queue-item ${activeClass} ${priorityClass} d-flex align-items-center gap-3" 
           data-key="${item.key}" onclick="selectPatient('${item.key}')">
        <div class="chat-avatar ${item.avatar_class}">
          ${isRoomAlert ? '<span class="material-symbols-outlined" style="font-size:22px;">warning</span>' : item.initials}
          ${!isRoomAlert ? '<span class="online-dot"></span>' : ''}
        </div>
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="fw-bold ${isRoomAlert ? 'text-danger' : 'chat-queue-title'} text-truncate" style="font-size:0.95rem;">${escapeHtml(item.name)}</span>
            <span class="small fw-semibold ${isRoomAlert ? 'text-danger' : (isActive ? 'text-primary' : 'text-muted')}" style="font-size:0.78rem;">${item.time}</span>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small text-truncate queue-snippet" style="max-width:180px;">${escapeHtml(item.snippet)}</span>
            ${item.unread > 0 ? `<span class="badge rounded-pill bg-primary unread-badge px-2 py-1" style="font-size:0.72rem;">${item.unread}</span>` : ''}
          </div>
        </div>
      </div>
    `;
  });

  if (!html) {
    html = `<div class="text-center text-muted p-4 small">No active patients in queue.</div>`;
  }

  container.innerHTML = html;
}

// 2. Select Patient and Load Messages from Database
async function selectPatient(key) {
  const item = queueData.find(q => q.key === key);
  if (!item) return;

  activeQueueItem = item;
  currentConvId = item.conversation_id;

  // Clear unread badge
  item.unread = 0;
  renderQueueList();

  // Update Header
  const avatarEl = document.getElementById('headerAvatar');
  avatarEl.textContent = item.priority ? '!' : item.initials;
  avatarEl.className = `chat-avatar ${item.avatar_class}`;
  if (!item.priority) {
    avatarEl.innerHTML = `${item.initials}<span class="online-dot"></span>`;
  } else {
    avatarEl.innerHTML = `<span class="material-symbols-outlined" style="font-size:22px;">warning</span>`;
  }

  document.getElementById('headerName').textContent = item.name;
  document.getElementById('headerSub').innerHTML = `<span class="text-success fw-bold">•</span> Active Session • ID: ${item.patient_id}`;

  await loadActiveMessages(false);
}

let lastRenderedMessagesJson = '';

// 3. Fetch Messages from Database for Active Conversation
async function loadActiveMessages(isBackgroundPoll = false) {
  if (!currentConvId) return;

  try {
    const res = await fetch(`${API_URL}?action=get_messages&conversation_id=${currentConvId}`);
    const data = await res.json();
    if (!data.ok || !data.messages) return;

    const container = document.getElementById('chatMessages');
    const prevScrollHeight = container.scrollHeight;
    const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 80;

    if (isBackgroundPoll && lastMessageCount > 0 && data.messages.length > lastMessageCount) {
      const newMsgs = data.messages.slice(lastMessageCount);
      const hasIncoming = newMsgs.some(m => m.sender_role === 'user' && !m.is_deleted);
      if (hasIncoming && typeof sedapPlayNotificationSound === 'function') {
        sedapPlayNotificationSound();
      }
    }
    lastMessageCount = data.messages.length;

    // Prevent audio playback from being restarted/destroyed by background poll
    if (isBackgroundPoll && currentlyPlayingAudio && !currentlyPlayingAudio.paused) {
      return;
    }

    const currentJson = JSON.stringify(data.messages);
    if (isBackgroundPoll && currentJson === lastRenderedMessagesJson) {
      return; // Nothing changed, keep existing DOM intact
    }
    lastRenderedMessagesJson = currentJson;

    let html = '';
    data.messages.forEach(m => {
      const isDoctor = (m.sender_role === 'doctor' || m.sender_role === 'admin');
      const isDeleted = m.is_deleted;
      const isVoice = !isDeleted && m.content && m.content.includes('[audio]');
      const voiceBubbleClass = isVoice ? 'msg-bubble-voice' : '';
      const formattedContent = isDeleted 
        ? `<div class="msg-deleted-bubble d-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:16px;">block</span> ${escapeHtml(m.content)}</div>`
        : formatMessageContent(m.content);

      const rawCopy = escapeAttr(m.raw_content ? m.raw_content.replace(/\[img\](.*?)\[\/img\]/gi, '$1').replace(/\[audio\](.*?)\[\/audio\]/gi, '$1') : m.content);

      const actionMenu = !isDeleted ? `
        <div class="dropdown">
          <button class="btn btn-ghost-secondary btn-sm msg-action-btn" type="button" data-coreui-toggle="dropdown" aria-expanded="false" title="Options">
            <span class="material-symbols-outlined" style="font-size:16px;">more_vert</span>
          </button>
          <ul class="dropdown-menu shadow-sm dropdown-menu-end" style="border-radius:0.75rem;font-size:0.85rem;">
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="copyMessageText('${rawCopy}')">
                <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span> Copy
              </a>
            </li>
            ${m.can_delete ? `
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" onclick="deleteMessage(${m.id})">
                <span class="material-symbols-outlined" style="font-size:16px;">delete</span> Delete Message
              </a>
            </li>` : ''}
          </ul>
        </div>
      ` : '';

      if (isDoctor) {
        html += `
          <div class="d-flex flex-column align-items-end mb-3 msg-wrapper">
            <div class="d-flex align-items-end gap-2 justify-content-end w-100">
              ${actionMenu}
              ${isDeleted ? formattedContent : `<div class="msg-sent-bubble ${voiceBubbleClass}">${formattedContent}</div>`}
              <div class="chat-avatar bg-primary text-white" style="width:34px;height:34px;font-size:0.8rem;">
                <span class="material-symbols-outlined" style="font-size:18px;">medical_services</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-1 text-muted small mt-1 me-5" style="font-size:0.75rem;">
              <span>${m.time}</span>
              ${!isDeleted ? `<span class="material-symbols-outlined text-primary" style="font-size:15px;">done_all</span>` : ''}
            </div>
          </div>
        `;
      } else {
        html += `
          <div class="d-flex flex-column align-items-start mb-3 msg-wrapper">
            <div class="d-flex align-items-start gap-2 w-100">
              <div class="chat-avatar ${activeQueueItem ? activeQueueItem.avatar_class : 'chat-avatar-em'}" style="width:34px;height:34px;font-size:0.8rem;">
                ${activeQueueItem ? (activeQueueItem.priority ? '!' : activeQueueItem.initials) : 'U'}
              </div>
              ${isDeleted ? formattedContent : `<div class="msg-received-bubble ${voiceBubbleClass}">${formattedContent}</div>`}
              ${actionMenu}
            </div>
            <div class="text-muted small mt-1 ms-5" style="font-size:0.75rem;">${m.time}</div>
          </div>
        `;
      }
    });

    if (data.messages.length === 0) {
      html = `
        <div class="text-center text-muted p-5 small">
          <span class="material-symbols-outlined d-block mb-2" style="font-size:36px;opacity:.4;">forum</span>
          Session started with ${escapeHtml(activeQueueItem.name)}. Send a message to begin consultation.
        </div>
      `;
    }

    container.innerHTML = html;

    if (!isBackgroundPoll || isAtBottom) {
      container.scrollTop = container.scrollHeight;
    }
  } catch (err) {
    console.error("Messages load error:", err);
  }
}

// 4. Send Message and Persist to MySQL
let stagedPhotoFile = null;

function handlePhotoSelect(input) {
  if (!input.files || input.files.length === 0) return;
  const file = input.files[0];

  stagedPhotoFile = file;
  const container = document.getElementById('photoPreviewContainer');
  const img = document.getElementById('photoPreviewImg');
  const nameEl = document.getElementById('photoPreviewName');
  const sizeEl = document.getElementById('photoPreviewSize');

  const sizeKb = Math.round(file.size / 1024);
  nameEl.textContent = file.name;
  sizeEl.textContent = `${sizeKb} KB • Click send button to send`;

  const reader = new FileReader();
  reader.onload = function(e) {
    img.src = e.target.result;
    container.classList.remove('d-none');
    container.classList.add('d-flex');
  };
  reader.readAsDataURL(file);
}

function clearSelectedPhoto() {
  stagedPhotoFile = null;
  const fileInput = document.getElementById('fileUpload');
  if (fileInput) fileInput.value = '';
  const container = document.getElementById('photoPreviewContainer');
  if (container) {
    container.classList.remove('d-flex');
    container.classList.add('d-none');
  }
}

// 4. Send Message (Text, Staged Photo, or Voice Note) and Persist to MySQL
async function handleSendMessage(e) {
  if (e) e.preventDefault();
  
  // If recording is in progress or a voice note is staged, send the voice note!
  if ((mediaRecorder && mediaRecorder.state === 'recording') || recordedVoiceBlob) {
    await sendVoiceRecording();
    return;
  }

  const input = document.getElementById('chatInput');
  const text = input ? input.value.trim() : '';

  if (!stagedPhotoFile && !text) return;
  if (!currentConvId) return;

  if (input) input.value = '';

  try {
    const formData = new FormData();
    formData.append('conversation_id', currentConvId);

    if (stagedPhotoFile) {
      formData.append('action', 'upload_photo');
      formData.append('photo', stagedPhotoFile);
      if (text) formData.append('caption', text);
      clearSelectedPhoto();
    } else {
      formData.append('action', 'send_message');
      formData.append('content', text);
    }

    const res = await fetch(API_URL, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) {
      await loadActiveMessages(false);
      loadQueue(true);
    } else {
      alert(data.error || 'Failed to send message.');
    }
  } catch (err) {
    console.error("Send message error:", err);
  }
}

// 5. Voice Message Recording Engine
let mediaRecorder = null;
let audioChunks = [];
let voiceRecordingStream = null;
let recordingTimerInterval = null;
let recordingSeconds = 0;
let recordedVoiceBlob = null;

async function toggleVoiceRecording() {
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    stopVoiceRecording();
  } else {
    startVoiceRecording();
  }
}

async function startVoiceRecording() {
  if (!currentConvId) {
    alert("Sila pilih perbualan pesakit terlebih dahulu / Please select a patient conversation first.");
    return;
  }

  try {
    voiceRecordingStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    
    let mimeType = 'audio/webm';
    if (typeof MediaRecorder.isTypeSupported === 'function') {
      if (MediaRecorder.isTypeSupported('audio/webm')) mimeType = 'audio/webm';
      else if (MediaRecorder.isTypeSupported('audio/mp4')) mimeType = 'audio/mp4';
      else if (MediaRecorder.isTypeSupported('audio/ogg')) mimeType = 'audio/ogg';
      else mimeType = '';
    }

    mediaRecorder = mimeType ? new MediaRecorder(voiceRecordingStream, { mimeType }) : new MediaRecorder(voiceRecordingStream);
    audioChunks = [];
    recordedVoiceBlob = null;

    mediaRecorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) {
        audioChunks.push(e.data);
      }
    };

    mediaRecorder.start(250);

    // UI Updates
    recordingSeconds = 0;
    const timerEl = document.getElementById('recordingTimer');
    if (timerEl) timerEl.textContent = '0:00';

    const container = document.getElementById('voicePreviewContainer');
    const recIndicator = document.getElementById('recordingIndicator');
    const playbackWrapper = document.getElementById('audioPlaybackWrapper');
    const btnStop = document.getElementById('btnStopRecord');
    const btnSend = document.getElementById('btnSendVoice');
    const micBtn = document.getElementById('micRecordBtn');

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

    clearInterval(recordingTimerInterval);
    recordingTimerInterval = setInterval(() => {
      recordingSeconds++;
      const mins = Math.floor(recordingSeconds / 60);
      const secs = recordingSeconds % 60;
      if (timerEl) timerEl.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }, 1000);

  } catch (err) {
    console.error("Microphone access error:", err);
    alert("Tidak dapat mengakses mikrofon. Sila benarkan akses mikrofon pada pelayar web anda / Cannot access microphone. Please enable microphone permissions.");
  }
}

function stopVoiceRecording() {
  return new Promise((resolve) => {
    clearInterval(recordingTimerInterval);
    if (!mediaRecorder || mediaRecorder.state !== 'recording') {
      resolve(recordedVoiceBlob);
      return;
    }

    mediaRecorder.onstop = () => {
      recordedVoiceBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
      const audioUrl = URL.createObjectURL(recordedVoiceBlob);
      
      const previewAudio = document.getElementById('voiceAudioPreview');
      if (previewAudio) {
        previewAudio.src = audioUrl;
      }

      const recIndicator = document.getElementById('recordingIndicator');
      const playbackWrapper = document.getElementById('audioPlaybackWrapper');
      const btnStop = document.getElementById('btnStopRecord');
      const btnSend = document.getElementById('btnSendVoice');

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

      if (voiceRecordingStream) {
        voiceRecordingStream.getTracks().forEach(track => track.stop());
      }
      resolve(recordedVoiceBlob);
    };

    mediaRecorder.stop();

    const micBtn = document.getElementById('micRecordBtn');
    if (micBtn) {
      micBtn.style.color = '';
      micBtn.classList.remove('text-danger');
    }
  });
}

function cancelVoiceRecording() {
  clearInterval(recordingTimerInterval);
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
  }
  if (voiceRecordingStream) {
    voiceRecordingStream.getTracks().forEach(track => track.stop());
  }
  audioChunks = [];
  recordedVoiceBlob = null;

  const container = document.getElementById('voicePreviewContainer');
  if (container) {
    container.classList.remove('d-flex');
    container.classList.add('d-none');
  }
  const micBtn = document.getElementById('micRecordBtn');
  if (micBtn) {
    micBtn.style.color = '';
    micBtn.classList.remove('text-danger');
  }
  const previewAudio = document.getElementById('voiceAudioPreview');
  if (previewAudio) previewAudio.src = '';
}

async function sendVoiceRecording() {
  let blob = recordedVoiceBlob;
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    blob = await stopVoiceRecording();
  }

  if (!blob || !currentConvId) return;

  const btnSend = document.getElementById('btnSendVoice');
  if (btnSend) {
    btnSend.disabled = true;
    btnSend.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Hantar...';
  }

  try {
    const formData = new FormData();
    formData.append('action', 'upload_voice');
    formData.append('conversation_id', currentConvId);
    formData.append('voice_note', blob, 'voice.webm');

    const res = await fetch(API_URL, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) {
      cancelVoiceRecording();
      await loadActiveMessages(false);
      loadQueue(true);
    } else {
      alert(data.error || 'Gagal menghantar mesej suara / Failed to send voice note.');
    }
  } catch (err) {
    console.error("Voice send error:", err);
    alert('Ralat rangkaian semasa menghantar mesej suara.');
  } finally {
    if (btnSend) {
      btnSend.disabled = false;
      btnSend.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">send</span> Hantar';
    }
  }
}

// Quick action chip helpers
function insertTemplate(text) {
  const input = document.getElementById('chatInput');
  input.value = text;
  input.focus();
  const modalEl = document.getElementById('faqModal');
  if (modalEl) {
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
  }
}

function sendRequestPhoto() {
  insertTemplate("Please take a clear, well-lit photo of the affected area and send it over so I can assess the inflammation.");
}

function sendArticle() {
  insertTemplate("Here is the community medical guide on ORS rehydration and prevention steps: https://sedap.moh.gov.my/guides/ors-hydration");
}

function openHistoryModal() {
  if (!activeQueueItem) return;
  document.getElementById('histPatientName').textContent = activeQueueItem.name + ' (' + activeQueueItem.patient_id + ')';
  document.getElementById('histVitals').textContent = 'Phone: ' + activeQueueItem.phone + ', Email: ' + activeQueueItem.email;
  document.getElementById('histComplaint').textContent = 'Active Patient Session in Community Triage Database.';
  document.getElementById('histDate').textContent = activeQueueItem.time;

  const modal = new bootstrap.Modal(document.getElementById('historyModal'));
  modal.show();
}

function openVideoModal() {
  if (!activeQueueItem) return;
  document.getElementById('videoPatientName').textContent = activeQueueItem.name;
  const modal = new bootstrap.Modal(document.getElementById('videoModal'));
  modal.show();
}

function filterQueue() {
  window.filterOnlyPriority = !window.filterOnlyPriority;
  renderQueueList();
}

async function deleteMessage(msgId) {
  if (!confirm('Are you sure you want to delete this message? It will be replaced with "Deleted message".')) return;

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
      await loadActiveMessages(false);
      loadQueue(true);
    } else {
      alert(data.error || 'Failed to delete message.');
    }
  } catch (err) {
    console.error("Delete error:", err);
  }
}

function copyMessageText(text) {
  if (!text) return;
  // Decode HTML entities if any
  const txt = document.createElement('textarea');
  txt.innerHTML = text;
  const decoded = txt.value;

  if (navigator.clipboard) {
    navigator.clipboard.writeText(decoded).then(() => {
      showToast('Copied to clipboard!');
    });
  } else {
    txt.select();
    document.execCommand('copy');
    showToast('Copied to clipboard!');
  }
}

function showToast(msg) {
  const existing = document.getElementById('chatToast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'chatToast';
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

// Initialize on page load & setup real-time polling every 3.5s
function initDoctorLiveChat() {
  if (!document.getElementById('queueListContainer')) return;
  
  if (window.sedapInitialQueue && window.sedapInitialQueue.length > 0) {
    queueData = window.sedapInitialQueue;
    if (!activeQueueItem && window.sedapFirstPatient) {
      activeQueueItem = window.sedapFirstPatient;
      currentConvId = window.sedapFirstPatient.conversation_id;
    }
  }

  // Load messages immediately for current active patient
  if (currentConvId) {
    loadActiveMessages(false);
  }

  // Refresh queue from server
  loadQueue(true);

  if (window.sedapDoctorChatPollTimer) clearInterval(window.sedapDoctorChatPollTimer);
  window.sedapDoctorChatPollTimer = setInterval(() => {
    if (!document.getElementById('queueListContainer')) {
      clearInterval(window.sedapDoctorChatPollTimer);
      return;
    }
    loadQueue(true);
  }, 3500);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDoctorLiveChat);
} else {
  initDoctorLiveChat();
}
window.addEventListener('sedap:page-loaded', initDoctorLiveChat);