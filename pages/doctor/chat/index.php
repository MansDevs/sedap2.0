<?php
$doctorBase = '../';
$activeNav = 'chat';
$pageTitle = 'Live Chat';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$userId = (int)$currentUser['id'];
$userName = htmlspecialchars($currentUser['name']);
$userRole = htmlspecialchars($currentUser['role']);
$_ROOT = sedap_root();
?>

<!-- WhatsApp Style Fullscreen Clinical Live Chat Styles (Blue Theme) -->
<style>
  /* Remove outer padding and enable edge-to-edge container */
  html, body {
    height: 100%;
    overflow: hidden;
  }

  main:has(.whatsapp-chat-wrapper),
  .flex-1.flex.flex-col.overflow-hidden > main {
    padding: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
    min-height: 0 !important;
  }

  .whatsapp-chat-wrapper {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: row;
    overflow: hidden;
    background-color: #ffffff;
  }

  /* Left Queue Sidebar */
  .wa-sidebar {
    width: 380px;
    height: 100%;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e2e8f0;
    background-color: #ffffff;
    flex-shrink: 0;
  }

  .wa-sidebar-header {
    height: 60px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #f0f2f5;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
  }

  .wa-search-bar {
    padding: 8px 14px;
    background-color: #ffffff;
    border-bottom: 1px solid #f0f2f5;
    flex-shrink: 0;
  }

  .wa-filter-chips {
    padding: 6px 14px 10px 14px;
    display: flex;
    gap: 8px;
    background-color: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
    flex-shrink: 0;
  }

  .wa-filter-btn {
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 500;
    background-color: #f0f2f5;
    color: #54656f;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  .wa-filter-btn.active {
    background-color: #0058bd;
    color: #ffffff;
    font-weight: 600;
  }

  /* Queue Item Rows (Flat WhatsApp list) */
  .wa-queue-list {
    flex: 1;
    overflow-y: auto;
    background-color: #ffffff;
  }

  .wa-patient-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.15s ease;
  }
  .wa-patient-row:hover {
    background-color: #f5f6f6;
  }
  .wa-patient-row.active {
    background-color: #eff6ff;
  }

  .wa-patient-row.priority {
    border-left: 4px solid #ef4444;
  }

  /* Right Active Chat Panel */
  .wa-chat-panel {
    flex: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    background-color: #f8fafc;
    background-image: radial-gradient(rgba(0, 88, 189, 0.03) 1px, transparent 0);
    background-size: 16px 16px;
    position: relative;
    overflow: hidden;
  }

  .wa-chat-header {
    height: 60px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #f0f2f5;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
  }

  .wa-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  /* WhatsApp Message Bubbles */
  .wa-bubble {
    position: relative;
    max-width: 65%;
    min-width: 80px;
    padding: 6px 10px 8px 10px;
    border-radius: 8px;
    box-shadow: 0 1px 0.5px rgba(11, 20, 26, 0.13);
    font-size: 13.5px;
    line-height: 1.45;
    word-break: break-word;
  }

  .wa-bubble-incoming {
    align-self: flex-start;
    background-color: #ffffff;
    color: #111b21;
    border-top-left-radius: 0;
  }

  .wa-bubble-outgoing {
    align-self: flex-end;
    background-color: #dbeafe;
    color: #0f172a;
    border-top-right-radius: 0;
  }

  .wa-bubble-meta {
    float: right;
    margin-left: 8px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 10.5px;
    color: #64748b;
    user-select: none;
  }

  /* Quick Actions Bar */
  .wa-quick-bar {
    padding: 6px 16px;
    background-color: #f0f2f5;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    flex-shrink: 0;
  }

  .wa-quick-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    background-color: #ffffff;
    border: 1px solid #d1d7db;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
    color: #54656f;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
  }
  .wa-quick-chip:hover {
    background-color: #e2e8f0;
    color: #0058bd;
  }

  /* Chat Input Footer */
  .wa-chat-footer {
    padding: 8px 16px;
    background-color: #f0f2f5;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .wa-input-box {
    flex: 1;
    background-color: #ffffff;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 14px;
    color: #111b21;
    border: 1px solid transparent;
    outline: none;
  }
  .wa-input-box:focus {
    border-color: transparent;
  }

  .wa-icon-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #54656f;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.15s ease;
    border: none;
    background: transparent;
    flex-shrink: 0;
  }
  .wa-icon-btn:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: #0058bd;
  }

  /* Audio Waveform Bars */
  .waveform-bar {
    width: 2.5px;
    background-color: #94a3b8;
    border-radius: 2px;
    display: inline-block;
    transition: height 0.15s ease, background-color 0.15s ease;
  }
  .waveform-bar.active {
    background-color: #0058bd;
  }

  /* Custom Lightbox Fullscreen Modal */
  .chat-lightbox {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 999999 !important;
    background-color: rgba(11, 20, 26, 0.95) !important;
    backdrop-filter: blur(12px) !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .chat-lightbox.hidden {
    display: none !important;
  }

  /* Custom Scrollbar */
  .custom-scrollbar::-webkit-scrollbar { width: 6px; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c7cd; border-radius: 4px; }

  /* ========================================================= */
  /* WHATSAPP DARK THEME OVERRIDES (BLUE ACCENTS)              */
  /* ========================================================= */
  html.dark .whatsapp-chat-wrapper {
    background-color: #0b0f19;
  }
  html.dark .wa-sidebar {
    background-color: #0f172a;
    border-color: #1e293b;
  }
  html.dark .wa-sidebar-header,
  html.dark .wa-chat-header,
  html.dark .wa-quick-bar,
  html.dark .wa-chat-footer {
    background-color: #0f172a;
    border-color: #1e293b;
  }
  html.dark .wa-search-bar,
  html.dark .wa-filter-chips {
    background-color: #0f172a;
    border-color: #1e293b;
  }
  html.dark .wa-filter-btn {
    background-color: #1e293b;
    color: #94a3b8;
  }
  html.dark .wa-filter-btn.active {
    background-color: #0058bd;
    color: #ffffff;
    font-weight: 600;
  }
  html.dark .wa-queue-list {
    background-color: #0f172a;
  }
  html.dark .wa-patient-row {
    border-color: #1e293b;
  }
  html.dark .wa-patient-row:hover {
    background-color: #162334;
  }
  html.dark .wa-patient-row.active {
    background-color: #1a2c44;
  }

  html.dark .wa-chat-panel {
    background-color: #0b0f19;
    background-image: none;
  }

  html.dark .wa-bubble-incoming {
    background-color: #162334;
    color: #f8fafc;
  }
  html.dark .wa-bubble-outgoing {
    background-color: #173d63;
    color: #f8fafc;
  }
  html.dark .wa-bubble-meta {
    color: #94a3b8;
  }

  html.dark .wa-quick-chip {
    background-color: #162334;
    border-color: #1e293b;
    color: #94a3b8;
  }
  html.dark .wa-quick-chip:hover {
    background-color: #1e2d44;
    color: #38bdf8;
  }

  html.dark .wa-input-box {
    background-color: #131d2e;
    color: #f8fafc;
    border-color: #1e293b;
  }
  html.dark .wa-input-box::placeholder {
    color: #64748b;
  }

  html.dark .wa-icon-btn {
    color: #94a3b8;
  }
  html.dark .wa-icon-btn:hover {
    background-color: rgba(255, 255, 255, 0.08);
    color: #38bdf8;
  }

  html.dark .waveform-bar.active {
    background-color: #38bdf8;
  }

  html.dark #faqModal > div {
    background-color: #0f172a !important;
    border-color: #1e293b !important;
  }
  html.dark #faqModal .wa-modal-header {
    background-color: #0f172a !important;
    border-color: #1e293b !important;
  }
  html.dark #faqListContainer {
    background-color: #0b0f19 !important;
  }
  html.dark #faqListContainer > div {
    background-color: #131d2e !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
  }
  html.dark #faqListContainer > div:hover {
    background-color: #18263c !important;
    border-color: #0058bd !important;
  }
</style>

<div class="whatsapp-chat-wrapper">

  <!-- ========================================================= -->
  <!-- LEFT: WHATSAPP QUEUE / CHAT LIST SIDEBAR                  -->
  <!-- ========================================================= -->
  <aside class="wa-sidebar">
    
    <!-- Sidebar Header -->
    <div class="wa-sidebar-header">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[#0058bd] dark:text-[#38bdf8] text-[24px]">forum</span>
        <h2 class="font-headline font-bold text-base text-slate-800 dark:text-[#f8fafc]">Giliran Triaj</h2>
        <span class="text-[9px] font-black tracking-wider uppercase px-1.5 py-0.5 rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-[#38bdf8] border border-primary/20 ml-0.5">BETA</span>
      </div>
      <button type="button" onclick="loadChatQueue()" class="wa-icon-btn" title="Muat Semula / Refresh Queue">
        <span class="material-symbols-outlined text-[20px]">refresh</span>
      </button>
    </div>

    <!-- Search Input -->
    <div class="wa-search-bar">
      <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-3 text-slate-400 dark:text-[#64748b] text-[18px]">search</span>
        <input type="text" id="queueSearchInput" oninput="filterQueueList(this.value)" placeholder="Cari nama atau ID pesakit..."
               class="w-full bg-[#f0f2f5] dark:bg-[#131d2e] text-slate-800 dark:text-[#f8fafc] placeholder:text-slate-500 dark:placeholder:text-[#64748b] text-xs pl-9 pr-3 py-2 rounded-lg border-none focus:outline-none">
      </div>
    </div>

    <!-- Filter Tabs (All / Priority / Unread) -->
    <div class="wa-filter-chips">
      <button type="button" class="wa-filter-btn active" onclick="setQueueFilter('all', this)">Semua</button>
      <button type="button" class="wa-filter-btn" onclick="setQueueFilter('priority', this)">Kecemasan</button>
      <button type="button" class="wa-filter-btn" onclick="setQueueFilter('unread', this)">Belum Dibaca</button>
    </div>

    <!-- Queue Patient List (Flat Rows) -->
    <div class="wa-queue-list custom-scrollbar" id="queueListContainer">
      <div class="py-12 text-center text-slate-400 dark:text-[#64748b] text-xs flex flex-col items-center gap-2">
        <span class="material-symbols-outlined animate-spin text-[26px] text-[#0058bd]">sync</span>
        <span>Memuatkan senarai giliran triaj...</span>
      </div>
    </div>
  </aside>


  <!-- ========================================================= -->
  <!-- RIGHT: WHATSAPP ACTIVE CHAT ROOM CONTAINER                -->
  <!-- ========================================================= -->
  <main class="wa-chat-panel">

    <!-- Active Chat Header -->
    <header class="wa-chat-header" id="chatHeaderArea">
      <div class="flex items-center gap-3 min-w-0">
        <div class="relative shrink-0">
          <div id="activeAvatarCircle" class="w-10 h-10 rounded-full bg-primary/10 dark:bg-[#1e293b] font-bold text-[#0058bd] dark:text-[#38bdf8] text-sm flex items-center justify-center font-headline shadow-sm">
            --
          </div>
          <span class="w-2.5 h-2.5 bg-[#0058bd] dark:bg-[#38bdf8] rounded-full ring-2 ring-white dark:ring-[#0f172a] absolute bottom-0 right-0"></span>
        </div>
        <div class="min-w-0">
          <h3 class="font-headline font-semibold text-sm sm:text-base text-slate-900 dark:text-[#f8fafc] truncate" id="activePatientName">
            Pilih Pesakit dari Giliran
          </h3>
          <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-[#94a3b8]">
            <span id="activePatientStatus">Active Session</span>
            <span>•</span>
            <span id="activePatientId">ID: #---</span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <button type="button" onclick="openFaqModal()" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold wa-quick-chip">
          <span class="material-symbols-outlined text-[16px] text-amber-500">bolt</span>
          <span>Templat FAQ</span>
        </button>
      </div>
    </header>

    <!-- Message History Area -->
    <div class="wa-messages-area custom-scrollbar" id="messagesContainer">
      <div class="h-full flex flex-col items-center justify-center text-slate-400 dark:text-[#64748b] text-center p-6 space-y-3">
        <div class="w-16 h-16 rounded-full bg-slate-200 dark:bg-[#162334] flex items-center justify-center text-[#0058bd] dark:text-[#38bdf8] shadow-sm">
          <span class="material-symbols-outlined text-[32px]">chat</span>
        </div>
        <div>
          <h4 class="font-bold text-slate-700 dark:text-[#f8fafc] text-base">Sesi Perbualan WhatsApp</h4>
          <p class="text-xs text-slate-500 dark:text-[#94a3b8] mt-1 max-w-sm">Pilih pesakit dari giliran di sebelah kiri untuk memulakan perbualan.</p>
        </div>
      </div>
    </div>

    <!-- Quick Clinical Action Chips -->
    <div class="wa-quick-bar">
      <button type="button" onclick="openFaqModal()" class="wa-quick-chip">
        <span class="material-symbols-outlined text-[15px] text-amber-500">bolt</span>
        <span>Templat FAQ</span>
      </button>

      <button type="button" onclick="insertQuickMessage('Sila ambil dan hantar foto bahagian yang terjejas dengan pencahayaan yang jelas untuk penilaian lanjut.')" class="wa-quick-chip">
        <span class="material-symbols-outlined text-[15px] text-[#0058bd] dark:text-[#38bdf8]">photo_camera</span>
        <span>Minta Foto</span>
      </button>

      <button type="button" onclick="insertQuickMessage('Berikut ialah panduan rawatan kesihatan komuniti: ' + window.location.origin + '<?= $_ROOT ?>/pages/doctor/health/water.php')" class="wa-quick-chip">
        <span class="material-symbols-outlined text-[15px] text-[#0058bd] dark:text-[#38bdf8]">article</span>
        <span>Hantar Artikel</span>
      </button>
    </div>

    <!-- Stage Previews for Photos -->
    <div id="stagedPhotoBar" class="hidden px-4 py-2 bg-[#f0f2f5] dark:bg-[#131d2e] border-t border-slate-200 dark:border-[#1e293b] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img id="stagedPhotoImg" src="" class="w-10 h-10 object-cover rounded-md border border-slate-300 dark:border-slate-700" alt="Preview">
        <div>
          <div class="text-xs font-semibold text-slate-800 dark:text-[#f8fafc]" id="stagedPhotoName">photo.jpg</div>
          <div class="text-[11px] text-slate-500 dark:text-[#94a3b8]">Sedia dihantar • Klik butang Send di bawah</div>
        </div>
      </div>
      <button type="button" onclick="clearStagedPhoto()" class="wa-icon-btn text-slate-400 hover:text-red-500">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>

    <!-- Voice Recording Status Bar -->
    <div id="stagedVoiceBar" class="hidden px-4 py-2 bg-rose-50 dark:bg-rose-950/40 border-t border-rose-200 dark:border-rose-900/60 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
        <span class="text-xs font-bold text-red-600 dark:text-red-400" id="recordingDuration">0:00</span>
        <span class="text-xs text-slate-700 dark:text-slate-300">Merakam Mesej Suara...</span>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" onclick="stopRecordingVoice()" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-full flex items-center gap-1 shadow-sm">
          <span class="material-symbols-outlined text-[16px]">stop</span>
          <span>Selesai</span>
        </button>
        <button type="button" onclick="cancelRecordingVoice()" class="p-1 text-slate-400 hover:text-slate-700">
          <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
      </div>
    </div>

    <!-- Bottom Chat Input Bar -->
    <footer class="wa-chat-footer">
      <form id="chatForm" onsubmit="handleSendSubmit(event)" class="flex items-center gap-2 w-full">
        
        <!-- Camera / File Attachment Button -->
        <button type="button" onclick="document.getElementById('photoFileInput').click()" class="wa-icon-btn" title="Lampirkan Foto">
          <span class="material-symbols-outlined text-[22px]">attach_file</span>
        </button>
        <input type="file" id="photoFileInput" accept="image/*" class="hidden" onchange="handlePhotoSelected(this)">

        <!-- Text Input -->
        <input type="text" id="chatMessageInput" placeholder="Tulis mesej..." autocomplete="off" class="wa-input-box">

        <!-- Mic Recording Button -->
        <button type="button" onclick="startRecordingVoice()" id="micBtn" class="wa-icon-btn" title="Rakam Mesej Suara">
          <span class="material-symbols-outlined text-[22px]">mic</span>
        </button>

        <!-- Send Button -->
        <button type="submit" class="wa-icon-btn text-[#0058bd] hover:text-[#00479e] dark:text-[#38bdf8] dark:hover:text-[#7dd3fc]" title="Hantar Mesej">
          <span class="material-symbols-outlined text-[24px]">send</span>
        </button>
      </form>
    </footer>
  </main>
</div>

<!-- ========================================================= -->
<!-- MODAL: TEMPLAT FAQ POPUP                                  -->
<!-- ========================================================= -->
<div id="faqModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden">
  <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
    <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 wa-modal-header">
      <div class="flex items-center gap-2 font-bold">
        <span class="material-symbols-outlined text-[22px] text-amber-500">bolt</span>
        <span class="text-slate-800 dark:text-[#f8fafc]">Templat Respons Pantas (FAQ)</span>
      </div>
      <button type="button" onclick="closeFaqModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition-colors">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>
    
    <div class="p-4 overflow-y-auto space-y-3 flex-1 custom-scrollbar" id="faqListContainer">
      <div class="py-8 text-center text-slate-400 text-xs">Memuatkan templat...</div>
    </div>
  </div>
</div>

<!-- ========================================================= -->
<!-- MODAL: FULLSCREEN LIGHTBOX FOR PHOTO ATTACHMENTS         -->
<!-- ========================================================= -->
<div id="imageLightbox" class="chat-lightbox hidden">
  <div class="flex items-center gap-3 bg-slate-900/90 border border-slate-700/80 px-4 py-2 rounded-xl shadow-2xl mb-3 z-10" onclick="event.stopPropagation()">
    <button type="button" class="text-slate-300 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Zoom Out (-)" onclick="zoomLightboxImage(-0.25)">
      <span class="material-symbols-outlined text-[20px]">zoom_out</span>
    </button>
    <span class="text-xs font-bold text-white min-w-[50px] text-center" id="lightboxZoomText">100%</span>
    <button type="button" class="text-slate-300 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Zoom In (+)" onclick="zoomLightboxImage(0.25)">
      <span class="material-symbols-outlined text-[20px]">zoom_in</span>
    </button>
    <button type="button" class="text-slate-300 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Reset Zoom" onclick="resetLightboxZoom()">
      <span class="material-symbols-outlined text-[20px]">restart_alt</span>
    </button>
    <div class="w-px h-5 bg-slate-700 mx-1"></div>
    <button type="button" class="text-slate-300 hover:text-red-400 p-1.5 rounded-lg hover:bg-red-500/20 transition-colors" title="Tutup (Esc)" onclick="closeLightboxDirect()">
      <span class="material-symbols-outlined text-[22px]">close</span>
    </button>
  </div>

  <div id="lightboxViewport" class="flex-1 flex items-center justify-center overflow-hidden max-w-5xl max-h-[80vh] w-full" onclick="event.stopPropagation()">
    <img id="lightboxImg" src="" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain border border-slate-700/60 transition-transform duration-200 select-none" alt="Full photo" ondblclick="toggleDoubleZoom()">
  </div>

  <div class="text-slate-400 text-xs mt-3 select-none pointer-events-none">
    <span class="material-symbols-outlined text-[15px] align-middle">touch_app</span> Klik dua kali untuk zum • Esc untuk tutup
  </div>
</div>

<!-- ========================================================= -->
<!-- JAVASCRIPT CLINICAL LIVE CHAT LOGIC                      -->
<!-- ========================================================= -->
<script>
const API_URL = '/sedap/sedap2.0/pages/shared/actions/chat_api.php';
let currentActiveConversationId = null;
let currentActiveUserId = null;
let queueData = [];
let currentFilter = 'all';
let pollInterval = null;
let stagedPhotoFile = null;

// Audio Recording State
let mediaRecorder = null;
let audioChunks = [];
let recordingTimerInterval = null;
let recordingSeconds = 0;

// On Load
document.addEventListener('DOMContentLoaded', () => {
  loadChatQueue();
  // Polling every 3.5 seconds
  pollInterval = setInterval(() => {
    loadChatQueue(true);
    if (currentActiveConversationId) {
      loadMessages(currentActiveConversationId, true);
    }
  }, 3500);
});

// 1. Fetch & Render Triage Queue
async function loadChatQueue(isBackground = false) {
  try {
    const res = await fetch(`${API_URL}?action=get_queue`);
    const data = await res.json();
    if (data.ok && Array.isArray(data.queue)) {
      queueData = data.queue;
      renderQueueList(getFilteredQueue());
      
      // Auto-select first patient if none selected yet
      if (!currentActiveConversationId && queueData.length > 0 && !isBackground) {
        selectPatient(queueData[0]);
      }
    }
  } catch (err) {
    console.error('Queue load error:', err);
  }
}

function setQueueFilter(filterType, btn) {
  currentFilter = filterType;
  document.querySelectorAll('.wa-filter-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  renderQueueList(getFilteredQueue());
}

function getFilteredQueue() {
  let list = queueData;
  const keyword = (document.getElementById('queueSearchInput')?.value || '').toLowerCase().trim();
  if (keyword) {
    list = list.filter(p => 
      p.name.toLowerCase().includes(keyword) || 
      (p.patient_id && p.patient_id.toLowerCase().includes(keyword)) ||
      (p.snippet && p.snippet.toLowerCase().includes(keyword))
    );
  }
  if (currentFilter === 'priority') {
    list = list.filter(p => p.priority);
  } else if (currentFilter === 'unread') {
    list = list.filter(p => p.unread > 0);
  }
  return list;
}

function filterQueueList() {
  renderQueueList(getFilteredQueue());
}

function renderQueueList(items) {
  const container = document.getElementById('queueListContainer');
  if (!items || items.length === 0) {
    container.innerHTML = `<div class="py-12 text-center text-slate-400 dark:text-[#64748b] text-xs">Tiada pesakit dalam giliran buat masa ini.</div>`;
    return;
  }

  container.innerHTML = items.map(item => {
    const isActive = (item.conversation_id === currentActiveConversationId);
    const isPriority = item.priority;
    const activeClass = isActive ? 'active' : '';
    const priorityClass = isPriority ? 'priority' : '';

    return `
      <div onclick='selectPatient(${JSON.stringify(item)})'
           class="wa-patient-row ${activeClass} ${priorityClass}">
        <div class="relative shrink-0">
          <div class="w-12 h-12 rounded-full ${isPriority ? 'bg-red-600 text-white' : 'bg-primary/10 dark:bg-[#1e293b] text-[#0058bd] dark:text-[#38bdf8]'} font-bold text-sm flex items-center justify-center shadow-sm">
            ${isPriority ? '<span class="material-symbols-outlined text-[20px]">warning</span>' : item.initials}
          </div>
          <span class="w-3 h-3 ${isPriority ? 'bg-red-500' : 'bg-[#0058bd] dark:bg-[#38bdf8]'} rounded-full ring-2 ring-white dark:ring-[#0f172a] absolute bottom-0 right-0"></span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between mb-0.5">
            <h4 class="font-headline font-semibold text-sm ${isPriority ? 'text-red-600 dark:text-red-400 font-bold' : 'text-slate-900 dark:text-[#f8fafc]'} truncate">
              ${escapeHtml(item.name)}
            </h4>
            <span class="text-[11px] ${isActive ? 'text-[#0058bd] dark:text-[#38bdf8] font-semibold' : 'text-slate-400 dark:text-[#94a3b8]'} shrink-0">${item.time || ''}</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <p class="truncate text-[12px] flex-1 text-slate-500 dark:text-[#94a3b8] pr-2">${escapeHtml(item.snippet)}</p>
            ${item.unread > 0 ? `<span class="bg-[#0058bd] text-white font-bold text-[10px] min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center shrink-0 shadow-sm">${item.unread}</span>` : ''}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// 2. Select Patient & Load Messages
function selectPatient(patient) {
  currentActiveConversationId = patient.conversation_id;
  currentActiveUserId = patient.user_id;

  // Update Active Header
  document.getElementById('activePatientName').textContent = patient.name;
  document.getElementById('activeAvatarCircle').textContent = patient.initials || 'P';
  document.getElementById('activePatientId').textContent = 'ID: ' + (patient.patient_id || '#' + patient.user_id);
  document.getElementById('activePatientStatus').textContent = patient.priority ? 'Emergency Priority' : 'Active Session';

  // Highlight in queue list
  renderQueueList(getFilteredQueue());

  // Load chat messages
  loadMessages(currentActiveConversationId);
}

async function loadMessages(convId, isBackground = false) {
  const container = document.getElementById('messagesContainer');
  if (!isBackground) {
    container.innerHTML = `<div class="py-12 text-center text-slate-400 dark:text-[#64748b] text-xs flex items-center justify-center gap-2"><span class="material-symbols-outlined animate-spin text-[20px] text-[#0058bd]">sync</span> Memuatkan mesej...</div>`;
  }

  try {
    const res = await fetch(`${API_URL}?action=get_messages&conversation_id=${convId}`);
    const data = await res.json();
    if (data.ok && Array.isArray(data.messages)) {
      renderMessages(data.messages, data.current_user_id);
    }
  } catch (err) {
    console.error('Messages load error:', err);
  }
}

function renderMessages(messages, currentUserId) {
  const container = document.getElementById('messagesContainer');
  if (!messages || messages.length === 0) {
    container.innerHTML = `
      <div class="h-full flex flex-col items-center justify-center text-slate-400 dark:text-[#64748b] text-center p-6">
        <p class="text-xs">Tiada mesej lagi. Tulis mesej di bawah untuk memulakan perbualan.</p>
      </div>`;
    return;
  }

  const isAtBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 80);

  container.innerHTML = messages.map(m => {
    const isMe = (m.sender_id === currentUserId);
    const isAudio = m.content && m.content.includes('[audio]');
    const isPhoto = m.content && m.content.includes('[img]');
    const contentHtml = formatMessageContent(m.content, m.id, isMe);

    if (isMe) {
      // Outgoing (Doctor) Blue Bubble
      return `
        <div class="flex flex-col items-end group w-full">
          <div class="wa-bubble wa-bubble-outgoing">
            ${contentHtml}
            <span class="wa-bubble-meta">
              <span>${m.time}</span>
              <span class="material-symbols-outlined text-[14px] text-[#0284c7] dark:text-[#38bdf8]">done_all</span>
              ${!m.is_deleted ? `<button type="button" onclick="deleteMessage(${m.id})" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 ml-1 transition-opacity" title="Padam Mesej"><span class="material-symbols-outlined text-[13px]">delete</span></button>` : ''}
            </span>
          </div>
        </div>
      `;
    } else {
      // Incoming (Patient) Bubble
      return `
        <div class="flex flex-col items-start group w-full">
          <div class="wa-bubble wa-bubble-incoming">
            ${contentHtml}
            <span class="wa-bubble-meta">
              <span>${m.time}</span>
            </span>
          </div>
        </div>
      `;
    }
  }).join('');

  if (isAtBottom) {
    container.scrollTop = container.scrollHeight;
  }
}

// 3. Format Message Content (Text, [img], [audio], deleted)
function formatMessageContent(content, msgId, isOutgoing = false) {
  if (!content) return '';

  // Deleted message
  if (content.includes('Deleted message') || content.includes('Deleted Content') || content.includes('Deleted Voice Note')) {
    return `<span class="italic text-slate-400 dark:text-[#64748b] flex items-center gap-1 text-xs"><span class="material-symbols-outlined text-[14px]">block</span> Mesej telah dipadam</span>`;
  }

  // Audio / Voice Note [audio]URL[/audio]
  const audioMatch = content.match(/\[audio\](.*?)\[\/audio\]/i);
  if (audioMatch) {
    const audioUrl = audioMatch[1];
    return `
      <div class="voice-note-card flex items-center gap-2.5 py-1 min-w-[200px] sm:min-w-[240px]">
        <button type="button" onclick="togglePlayVoice(this, '${audioUrl}')" class="w-9 h-9 rounded-full bg-[#0058bd] hover:bg-[#00479e] text-white flex items-center justify-center shrink-0 shadow">
          <span class="material-symbols-outlined text-[20px] play-icon">play_arrow</span>
        </button>
        <div class="flex-1 flex flex-col gap-1">
          <div class="flex items-center gap-0.5 h-4">
            ${generateWaveformBars()}
          </div>
          <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-[#94a3b8]">
            <span class="audio-curr-time">0:00</span>
            <button type="button" onclick="cycleAudioSpeed(this)" class="px-1.5 py-0.2 rounded bg-black/5 dark:bg-white/10 text-slate-700 dark:text-slate-300 font-bold">1x</button>
          </div>
        </div>
      </div>
    `;
  }

  // Image Attachment [img]URL[/img]
  const imgMatch = content.match(/\[img\](.*?)\[\/img\]/i);
  if (imgMatch) {
    const imgUrl = imgMatch[1];
    const caption = content.replace(/\[img\].*?\[\/img\]/i, '').trim();
    return `
      <div class="py-0.5">
        ${caption ? `<p class="mb-1 text-xs">${escapeHtml(caption)}</p>` : ''}
        <img src="${imgUrl}" onclick="openLightbox('${imgUrl}')" class="max-w-xs max-h-64 rounded-md cursor-pointer hover:opacity-95 transition-opacity object-cover" alt="Photo">
      </div>
    `;
  }

  // Normal Text with URL auto-linking
  return escapeHtml(content).replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-[#0058bd] dark:text-[#38bdf8] underline break-all">$1</a>');
}

function generateWaveformBars() {
  const heights = [6, 12, 16, 10, 14, 18, 8, 15, 20, 12, 10, 16, 14, 6, 12, 15, 8, 14, 10];
  return heights.map(h => `<span class="waveform-bar" style="height:${h}px;"></span>`).join('');
}

// 4. Voice Note Audio Player Actions
let currentPlayingAudio = null;
let currentPlayBtn = null;

function togglePlayVoice(btn, audioUrl) {
  const icon = btn.querySelector('.play-icon');
  const card = btn.closest('.voice-note-card');
  const bars = card.querySelectorAll('.waveform-bar');
  const timeSpan = card.querySelector('.audio-curr-time');

  if (currentPlayingAudio && currentPlayingAudio.src.includes(audioUrl)) {
    if (!currentPlayingAudio.paused) {
      currentPlayingAudio.pause();
      icon.textContent = 'play_arrow';
      bars.forEach(b => b.classList.remove('active'));
      return;
    } else {
      currentPlayingAudio.play();
      icon.textContent = 'pause';
      bars.forEach(b => b.classList.add('active'));
      return;
    }
  }

  if (currentPlayingAudio) {
    currentPlayingAudio.pause();
    if (currentPlayBtn) currentPlayBtn.querySelector('.play-icon').textContent = 'play_arrow';
  }

  const audio = new Audio(audioUrl);
  currentPlayingAudio = audio;
  currentPlayBtn = btn;
  icon.textContent = 'pause';
  bars.forEach(b => b.classList.add('active'));

  audio.ontimeupdate = () => {
    const mins = Math.floor(audio.currentTime / 60);
    const secs = Math.floor(audio.currentTime % 60);
    if (timeSpan) timeSpan.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
  };

  audio.onended = () => {
    icon.textContent = 'play_arrow';
    bars.forEach(b => b.classList.remove('active'));
    if (timeSpan) timeSpan.textContent = '0:00';
  };

  audio.play().catch(e => console.error('Audio play error:', e));
}

function cycleAudioSpeed(btn) {
  if (!currentPlayingAudio) return;
  const speeds = [1, 1.5, 2];
  let curr = currentPlayingAudio.playbackRate;
  let nextIdx = (speeds.indexOf(curr) + 1) % speeds.length;
  currentPlayingAudio.playbackRate = speeds[nextIdx];
  btn.textContent = speeds[nextIdx] + 'x';
}

// 5. Send Message (Text / Staged Photo)
async function handleSendSubmit(e) {
  e.preventDefault();
  if (!currentActiveConversationId) {
    alert('Sila pilih pesakit terlebih dahulu.');
    return;
  }

  const input = document.getElementById('chatMessageInput');
  const text = input.value.trim();

  // If photo is staged, upload photo
  if (stagedPhotoFile) {
    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('conversation_id', currentActiveConversationId);
    formData.append('photo', stagedPhotoFile);
    formData.append('caption', text);

    input.value = '';
    clearStagedPhoto();

    try {
      const res = await fetch(API_URL, { method: 'POST', body: formData });
      const data = await res.json();
      if (data.ok) {
        loadMessages(currentActiveConversationId, true);
        loadChatQueue(true);
      }
    } catch (err) {
      console.error('Photo send error:', err);
    }
    return;
  }

  if (!text) return;
  input.value = '';

  const formData = new FormData();
  formData.append('action', 'send_message');
  formData.append('conversation_id', currentActiveConversationId);
  formData.append('content', text);

  try {
    const res = await fetch(API_URL, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.ok) {
      loadMessages(currentActiveConversationId, true);
      loadChatQueue(true);
    }
  } catch (err) {
    console.error('Send error:', err);
  }
}

// 6. Photo Selection & Preview
function handlePhotoSelected(input) {
  if (input.files && input.files[0]) {
    stagedPhotoFile = input.files[0];
    const reader = new FileReader();
    reader.onload = (e) => {
      document.getElementById('stagedPhotoImg').src = e.target.result;
      document.getElementById('stagedPhotoName').textContent = stagedPhotoFile.name;
      document.getElementById('stagedPhotoBar').classList.remove('hidden');
    };
    reader.readAsDataURL(stagedPhotoFile);
  }
}

function clearStagedPhoto() {
  stagedPhotoFile = null;
  document.getElementById('photoFileInput').value = '';
  document.getElementById('stagedPhotoBar').classList.add('hidden');
}

// 7. Voice Recording Implementation (MediaRecorder API)
async function startRecordingVoice() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert('Pelayar anda tidak menyokong rakaman mikrofon.');
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder = new MediaRecorder(stream);
    audioChunks = [];

    mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) audioChunks.push(e.data);
    };

    mediaRecorder.onstop = async () => {
      if (audioChunks.length === 0) return;
      const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
      await uploadVoiceBlob(audioBlob);
    };

    mediaRecorder.start();
    recordingSeconds = 0;
    document.getElementById('recordingDuration').textContent = '0:00';
    document.getElementById('stagedVoiceBar').classList.remove('hidden');

    recordingTimerInterval = setInterval(() => {
      recordingSeconds++;
      const mins = Math.floor(recordingSeconds / 60);
      const secs = recordingSeconds % 60;
      document.getElementById('recordingDuration').textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }, 1000);

  } catch (err) {
    alert('Sila benarkan akses mikrofon untuk membuat rakaman suara.');
  }
}

function stopRecordingVoice() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop();
    mediaRecorder.stream.getTracks().forEach(t => t.stop());
  }
  clearInterval(recordingTimerInterval);
  document.getElementById('stagedVoiceBar').classList.add('hidden');
}

function cancelRecordingVoice() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    audioChunks = [];
    mediaRecorder.stop();
    mediaRecorder.stream.getTracks().forEach(t => t.stop());
  }
  clearInterval(recordingTimerInterval);
  document.getElementById('stagedVoiceBar').classList.add('hidden');
}

async function uploadVoiceBlob(blob) {
  if (!currentActiveConversationId) return;

  const formData = new FormData();
  formData.append('action', 'upload_voice');
  formData.append('conversation_id', currentActiveConversationId);
  formData.append('voice_note', blob, 'voice_note.webm');

  try {
    const res = await fetch(API_URL, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.ok) {
      loadMessages(currentActiveConversationId, true);
      loadChatQueue(true);
    }
  } catch (err) {
    console.error('Voice upload error:', err);
  }
}

// 8. Quick Message Insertion & Templat FAQ Modal
function insertQuickMessage(text) {
  const input = document.getElementById('chatMessageInput');
  input.value = text;
  input.focus();
}

async function openFaqModal() {
  document.getElementById('faqModal').classList.remove('hidden');
  const container = document.getElementById('faqListContainer');
  container.innerHTML = '<div class="py-8 text-center text-slate-400 text-xs flex items-center justify-center gap-2"><span class="material-symbols-outlined animate-spin text-[18px]">sync</span> Memuatkan...</div>';

  try {
    const res = await fetch(`${API_URL}?action=get_faq_templates`);
    const data = await res.json();
    if (data.ok && Array.isArray(data.faqs) && data.faqs.length > 0) {
      container.innerHTML = data.faqs.map(f => `
        <div onclick="selectFaqTemplate('${escapeJsString(f.answer)}')" class="p-3.5 bg-white dark:bg-[#131d2e] hover:bg-slate-50 dark:hover:bg-[#18263c] border border-slate-200 dark:border-[#1e293b] rounded-xl cursor-pointer transition-colors shadow-sm">
          <div class="text-xs font-bold text-slate-800 dark:text-[#f8fafc] flex items-center gap-1.5 mb-1">
            <span class="material-symbols-outlined text-[16px] text-amber-500">bolt</span>
            <span>${escapeHtml(f.question)}</span>
          </div>
          <p class="text-[11px] text-slate-600 dark:text-[#94a3b8] line-clamp-2">${escapeHtml(f.answer)}</p>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div class="py-8 text-center text-slate-400 text-xs">Tiada templat FAQ dijumpai.</div>';
    }
  } catch (err) {
    container.innerHTML = '<div class="py-8 text-center text-red-500 text-xs">Ralat memuatkan templat.</div>';
  }
}

function selectFaqTemplate(answerText) {
  insertQuickMessage(answerText);
  closeFaqModal();
}

function closeFaqModal() {
  document.getElementById('faqModal').classList.add('hidden');
}

// 9. Fullscreen Lightbox with Drag-to-Pan & Mousewheel Zoom Controls
let lightboxCurrentZoom = 1.0;
let lightboxPanX = 0;
let lightboxPanY = 0;
let isDraggingLightbox = false;
let startDragX = 0;
let startDragY = 0;

function openLightbox(url) {
  const lb = document.getElementById('imageLightbox');
  if (lb.parentElement !== document.body) {
    document.body.appendChild(lb);
  }
  const img = document.getElementById('lightboxImg');
  img.src = url;
  lightboxCurrentZoom = 1.0;
  lightboxPanX = 0;
  lightboxPanY = 0;
  applyLightboxTransform();
  lb.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeLightbox(e) {
  if (e.target.id === 'imageLightbox') {
    closeLightboxDirect();
  }
}

function closeLightboxDirect() {
  const lb = document.getElementById('imageLightbox');
  lb.classList.add('hidden');
  document.body.style.overflow = '';
}

function zoomLightboxImage(delta) {
  lightboxCurrentZoom = Math.min(Math.max(0.5, lightboxCurrentZoom + delta), 4.0);
  if (lightboxCurrentZoom <= 1.0) {
    lightboxPanX = 0;
    lightboxPanY = 0;
  }
  applyLightboxTransform();
}

function resetLightboxZoom() {
  lightboxCurrentZoom = 1.0;
  lightboxPanX = 0;
  lightboxPanY = 0;
  applyLightboxTransform();
}

function toggleDoubleZoom(e) {
  if (lightboxCurrentZoom > 1.0) {
    resetLightboxZoom();
  } else {
    lightboxCurrentZoom = 2.0;
    applyLightboxTransform();
  }
}

function applyLightboxTransform() {
  const img = document.getElementById('lightboxImg');
  const text = document.getElementById('lightboxZoomText');
  if (img) {
    img.style.transform = `translate(${lightboxPanX}px, ${lightboxPanY}px) scale(${lightboxCurrentZoom})`;
    img.style.cursor = lightboxCurrentZoom > 1.0 ? (isDraggingLightbox ? 'grabbing' : 'grab') : 'default';
  }
  if (text) text.textContent = `${Math.round(lightboxCurrentZoom * 100)}%`;
}

// Mouse Wheel Zoom
document.addEventListener('wheel', (e) => {
  const lb = document.getElementById('imageLightbox');
  if (!lb || lb.classList.contains('hidden')) return;
  
  const viewport = document.getElementById('lightboxViewport');
  if (viewport && (viewport.contains(e.target) || e.target.id === 'lightboxImg')) {
    e.preventDefault();
    const delta = e.deltaY < 0 ? 0.2 : -0.2;
    zoomLightboxImage(delta);
  }
}, { passive: false });

// Mouse Drag-to-Pan Handlers
document.addEventListener('DOMContentLoaded', () => {
  const img = document.getElementById('lightboxImg');
  if (!img) return;

  img.addEventListener('mousedown', (e) => {
    if (lightboxCurrentZoom <= 1.0) return;
    isDraggingLightbox = true;
    startDragX = e.clientX - lightboxPanX;
    startDragY = e.clientY - lightboxPanY;
    img.style.cursor = 'grabbing';
    e.preventDefault();
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDraggingLightbox) return;
    lightboxPanX = e.clientX - startDragX;
    lightboxPanY = e.clientY - startDragY;
    applyLightboxTransform();
  });

  window.addEventListener('mouseup', () => {
    if (isDraggingLightbox) {
      isDraggingLightbox = false;
      applyLightboxTransform();
    }
  });

  // Touch Support for Mobile / Tablet Drag & Pinch
  let initialTouchDist = 0;
  let initialTouchZoom = 1.0;

  img.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1 && lightboxCurrentZoom > 1.0) {
      isDraggingLightbox = true;
      startDragX = e.touches[0].clientX - lightboxPanX;
      startDragY = e.touches[0].clientY - lightboxPanY;
    } else if (e.touches.length === 2) {
      isDraggingLightbox = false;
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      initialTouchDist = Math.hypot(dx, dy);
      initialTouchZoom = lightboxCurrentZoom;
    }
  }, { passive: true });

  window.addEventListener('touchmove', (e) => {
    if (isDraggingLightbox && e.touches.length === 1) {
      lightboxPanX = e.touches[0].clientX - startDragX;
      lightboxPanY = e.touches[0].clientY - startDragY;
      applyLightboxTransform();
    } else if (e.touches.length === 2 && initialTouchDist > 0) {
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      const currentDist = Math.hypot(dx, dy);
      const scaleFactor = currentDist / initialTouchDist;
      lightboxCurrentZoom = Math.min(Math.max(0.5, initialTouchZoom * scaleFactor), 4.0);
      applyLightboxTransform();
    }
  }, { passive: true });

  window.addEventListener('touchend', () => {
    isDraggingLightbox = false;
    initialTouchDist = 0;
  });
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeLightboxDirect();
    closeFaqModal();
  }
});

// 10. Delete Message
async function deleteMessage(msgId) {
  if (!confirm('Adakah anda pasti ingin memadam mesej ini?')) return;

  const formData = new FormData();
  formData.append('action', 'delete_message');
  formData.append('message_id', msgId);

  try {
    const res = await fetch(API_URL, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.ok) {
      loadMessages(currentActiveConversationId, true);
    }
  } catch (err) {
    console.error('Delete error:', err);
  }
}

// Utilities
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function escapeJsString(str) {
  if (!str) return '';
  return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\\n/g, '\\n').replace(/\\r/g, '');
}

function getInitials(name) {
  if (!name) return 'P';
  const parts = name.trim().split(' ');
  let initials = '';
  for (let p of parts) {
    if (p) {
      initials += p[0].toUpperCase();
      if (initials.length >= 2) break;
    }
  }
  return initials || 'P';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
