<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Dr. Sarah');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

// Fetch any active FAQ templates from DB
$faqTemplates = [];
try {
    $stmt = $pdo->query("SELECT * FROM faq_templates WHERE is_active=1 ORDER BY id ASC");
    $faqTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (empty($faqTemplates)) {
    $faqTemplates = [
        ['question' => 'ORS Rehydration Protocol', 'answer' => 'Please dissolve 1 packet of Oral Rehydration Salts (ORS) in 250ml of clean boiled water. Drink 1 cup after every loose bowel movement.'],
        ['question' => 'Fever Management Guide', 'answer' => 'Take Paracetamol 500mg (1-2 tablets) every 6 hours if temperature exceeds 38.0°C. Continue cool water sponging.'],
        ['question' => 'Emergency Danger Signs Warning', 'answer' => 'If you experience severe rigid abdominal pain, bloody vomit/stool, or extreme dizziness upon standing, proceed immediately to the Emergency Triage Counter.'],
        ['question' => 'Clean Water Sanitization', 'answer' => 'Ensure all drinking and food preparation water is boiled vigorously for at least 3 minutes or treated with chlorine sanitization tablets.']
    ];
}

// Fetch initial real patient queue from DB
$initialQueue = [];
$firstPatient = null;
try {
    $currentUserId = (int)$_SESSION['user_id'];
    $stmt = $pdo->query("SELECT u.id, u.name, u.email, u.contact_number, u.created_at 
                         FROM users u 
                         WHERE u.role IN ('user', 'patient') AND u.status = 'active' 
                         ORDER BY u.id ASC");
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbUsers as $u) {
        $uid = (int)$u['id'];
        
        $convStmt = $pdo->prepare("SELECT c.id FROM conversations c 
                                  JOIN conversation_participants cp ON c.id=cp.conversation_id 
                                  WHERE cp.user_id=? LIMIT 1");
        $convStmt->execute([$uid]);
        $convId = $convStmt->fetchColumn();

        if (!$convId) {
            $pdo->prepare("INSERT INTO conversations (type, created_by, created_at) VALUES ('direct', ?, NOW())")->execute([$uid]);
            $convId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $uid]);
            $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $currentUserId]);
        } else {
            $chkPart = $pdo->prepare("SELECT id FROM conversation_participants WHERE conversation_id=? AND user_id=?");
            $chkPart->execute([$convId, $currentUserId]);
            if (!$chkPart->fetchColumn()) {
                $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, last_read_message_id) VALUES (?, ?, 0)")->execute([$convId, $currentUserId]);
            }
        }

        $lastMsgStmt = $pdo->prepare("SELECT id, content, DATE_FORMAT(created_at, '%h:%i %p') as time, sender_id, deleted_at 
                                      FROM messages 
                                      WHERE conversation_id = ? 
                                      ORDER BY created_at DESC LIMIT 1");
        $lastMsgStmt->execute([$convId]);
        $lastMsg = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);

        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages 
                                      WHERE conversation_id = ? 
                                        AND sender_id != ? 
                                        AND id > IFNULL((SELECT last_read_message_id FROM conversation_participants WHERE conversation_id=? AND user_id=?), 0)
                                        AND deleted_at IS NULL");
        $unreadStmt->execute([$convId, $currentUserId, $convId, $currentUserId]);
        $unreadCount = (int)$unreadStmt->fetchColumn();

        $words = explode(' ', trim($u['name']));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1));
                if (mb_strlen($initials) >= 2) break;
            }
        }
        $initials = $initials ?: 'U';

        $snippet = 'Sedia untuk perbualan / Session active.';
        if ($lastMsg) {
            if (!empty($lastMsg['deleted_at'])) {
                $snippet = ' Mesej dipadam';
            } elseif (stripos($lastMsg['content'], '[audio]') !== false) {
                $snippet = ' [Mesej Suara / Voice Note]';
            } elseif (stripos($lastMsg['content'], '[img]') !== false) {
                $snippet = ' [Foto / Photo Attachment]';
            } else {
                $snippet = $lastMsg['content'];
            }
        }

        $avatarClass = ($uid % 3 === 1) ? 'chat-avatar-sr' : (($uid % 3 === 2) ? 'chat-avatar-jd' : 'chat-avatar-em');

        $item = [
            'key' => 'user_' . $uid,
            'user_id' => $uid,
            'conversation_id' => (int)$convId,
            'name' => $u['name'],
            'initials' => $initials,
            'avatar_class' => $avatarClass,
            'time' => $lastMsg ? $lastMsg['time'] : date('h:i A', strtotime($u['created_at'])),
            'snippet' => $snippet,
            'unread' => $unreadCount,
            'patient_id' => '#' . str_pad($uid + 84900, 5, '0', STR_PAD_LEFT)
        ];

        if (!$firstPatient) {
            $firstPatient = $item;
        }

        $initialQueue[] = $item;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_livechat_title', 'Medical Live Chat') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link rel="stylesheet" href="css/livechat.css?v=<?= time() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-3 px-md-4 py-3 py-md-4">

      <!-- Chat Dual-Panel Container -->
      <div class="row g-4">
        
        <!-- Left Panel: Triage Queue (35% on XL) -->
        <div class="col-12 col-lg-5 col-xl-4">
          <div class="card chat-app-card h-100 p-3">
            
            <!-- Queue Header -->
            <div class="d-flex align-items-center justify-content-between mb-3 px-2 pt-1">
              <h5 class="fw-bold mb-0 chat-text-title" style="font-size:1.15rem;"><?= __('triage_queue_title', 'Triage Queue') ?></h5>
              <button class="btn btn-ghost-secondary btn-sm p-1 rounded-circle" onclick="filterQueue()" title="Filter Queue">
                <span class="material-symbols-outlined" style="font-size:22px;">tune</span>
              </button>
            </div>

            <!-- Queue Items List -->
            <div class="queue-list d-flex flex-column" id="queueListContainer" style="max-height:600px;overflow-y:auto;">
              <?php if (empty($initialQueue)): ?>
                <div class="text-center text-muted p-4 small">Tiada pesakit aktif dalam pangkalan data.</div>
              <?php else: ?>
                <?php foreach ($initialQueue as $idx => $item): ?>
                  <div class="queue-item <?= $idx === 0 ? 'active' : '' ?> d-flex align-items-center gap-3" data-key="<?= htmlspecialchars($item['key']) ?>" onclick="selectPatient('<?= htmlspecialchars($item['key']) ?>')">
                    <div class="chat-avatar <?= $item['avatar_class'] ?>">
                      <?= htmlspecialchars($item['initials']) ?>
                      <span class="online-dot"></span>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold chat-queue-title text-truncate" style="font-size:0.95rem;"><?= htmlspecialchars($item['name']) ?></span>
                        <span class="small <?= $idx === 0 ? 'text-primary fw-semibold' : 'text-muted' ?>" style="font-size:0.78rem;"><?= htmlspecialchars($item['time']) ?></span>
                      </div>
                      <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small text-truncate queue-snippet" style="max-width:170px;"><?= htmlspecialchars($item['snippet']) ?></span>
                        <?php if (!empty($item['unread'])): ?>
                          <span class="badge rounded-pill bg-primary unread-badge px-2 py-1" style="font-size:0.72rem;"><?= $item['unread'] ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          </div>
        </div>

        <!-- Right Panel: Active Chat Thread (65% on XL) -->
        <div class="col-12 col-lg-7 col-xl-8">
          <div class="card chat-app-card h-100 d-flex flex-column">
            
            <!-- Active Chat Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 px-md-4 border-bottom" style="border-color:rgba(255,255,255,0.08)!important;">
              <div class="d-flex align-items-center gap-3">
                <div class="chat-avatar <?= $firstPatient ? $firstPatient['avatar_class'] : 'chat-avatar-em' ?>" id="headerAvatar" style="width:48px;height:48px;font-size:1.05rem;">
                  <?= $firstPatient ? htmlspecialchars($firstPatient['initials']) : 'EM' ?>
                  <span class="online-dot"></span>
                </div>
                <div>
                  <h5 class="fw-bold mb-0 chat-text-title" id="headerName" style="font-size:1.2rem;"><?= $firstPatient ? htmlspecialchars($firstPatient['name']) : 'Pilih Pesakit' ?></h5>
                  <div class="d-flex align-items-center gap-1 small text-muted" id="headerSub" style="font-size:0.82rem;">
                    <span class="text-success fw-bold">•</span> Active Session • ID: <?= $firstPatient ? htmlspecialchars($firstPatient['patient_id']) : '#00000' ?>
                  </div>
                </div>
              </div>

              <!-- Header Action Buttons -->
              <!-- <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 rounded-pill px-3 py-1 fw-semibold" onclick="openHistoryModal()">
                  <span class="material-symbols-outlined" style="font-size:18px;">history</span>
                  <?= __('btn_history', 'History') ?>
                </button>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 rounded-pill px-3 py-1 fw-semibold shadow-sm" style="background:#0066cc;border-color:#0066cc;" onclick="openVideoModal()">
                  <span class="material-symbols-outlined" style="font-size:18px;">videocam</span>
                  <?= __('btn_escalate_video', 'Escalate to Video') ?>
                </button>
              </div> -->
            </div>

            <!-- Messages Thread Box -->
            <div class="chat-body-scroll flex-grow-1" id="chatMessages">
              <!-- Messages injected dynamically via JS -->
            </div>

            <!-- Bottom Input & Quick Actions Area -->
            <div class="p-3 px-md-4 chat-footer-area border-top">
              
              <!-- Quick Action Chips -->
              <div class="d-flex align-items-center gap-2 mb-3 overflow-x-auto pb-1" style="scrollbar-width:none;">
                <button class="btn quick-chip chip-primary d-flex align-items-center gap-1" data-coreui-toggle="modal" data-coreui-target="#faqModal">
                  <span class="material-symbols-outlined" style="font-size:16px;">bolt</span>
                  <?= __('chip_faq_templates', 'FAQ Templates') ?>
                </button>
                <button class="btn quick-chip d-flex align-items-center gap-1" onclick="sendRequestPhoto()">
                  <?= __('chip_request_photo', 'Request Photo') ?>
                </button>
                <button class="btn quick-chip d-flex align-items-center gap-1" onclick="sendArticle()">
                  <?= __('chip_send_article', 'Send Article') ?>
                </button>
              </div>

              <!-- Staged Photo Preview Bar -->
              <div id="photoPreviewContainer" class="p-2 mb-2 rounded-3 border d-none align-items-center justify-content-between" style="background:rgba(8,115,131,0.08);border-color:rgba(8,115,131,0.2)!important;">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                  <img id="photoPreviewImg" src="" class="rounded-2 shadow-sm" style="width:48px;height:48px;object-fit:cover;" alt="Preview">
                  <div class="min-w-0">
                    <div class="small fw-semibold text-truncate chat-text-title" id="photoPreviewName">photo.jpg</div>
                    <div class="small text-muted" id="photoPreviewSize" style="font-size:0.75rem;">Ready to send</div>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px;height:28px;" title="<?= __('btn_remove_photo', 'Remove photo') ?>" onclick="clearSelectedPhoto()">
                  <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                </button>
              </div>

              <!-- Staged Voice Recording Container -->
              <div id="voicePreviewContainer" class="p-2 mb-2 rounded-3 border d-none align-items-center justify-content-between" style="background:rgba(220,53,69,0.08);border-color:rgba(220,53,69,0.25)!important;">
                <div class="d-flex align-items-center gap-2 flex-grow-1 me-2 overflow-hidden">
                  <div id="recordingIndicator" class="d-flex align-items-center gap-2">
                    <span class="recording-pulse-dot"></span>
                    <span class="small fw-bold text-danger" id="recordingTimer">0:00</span>
                    <span class="small text-muted d-none d-sm-inline">Merakam Suara / Recording...</span>
                  </div>
                  <div id="audioPlaybackWrapper" class="d-none flex-grow-1 d-flex align-items-center gap-2">
                    <audio id="voiceAudioPreview" controls class="w-100" style="height:34px;"></audio>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                  <button type="button" id="btnStopRecord" class="btn btn-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1" onclick="stopVoiceRecording()">
                    <span class="material-symbols-outlined" style="font-size:16px;">stop</span> Selesai
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center" style="width:28px;height:28px;" title="Batal / Discard" onclick="cancelVoiceRecording()">
                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                  </button>
                </div>
              </div>

              <!-- Interactive Input Bar -->
              <form onsubmit="handleSendMessage(event)" class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-ghost-secondary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;" title="<?= __('btn_upload_photo', 'Upload Photo / Image') ?>" onclick="document.getElementById('fileUpload').click()">
                  <span class="material-symbols-outlined text-primary" style="font-size:24px;">add_a_photo</span>
                </button>
                <input type="file" id="fileUpload" accept="image/png, image/jpeg, image/jpg, image/webp" style="display:none;" onchange="handlePhotoSelect(this)">

                <div class="position-relative flex-grow-1">
                  <input type="text" id="chatInput" class="form-control chat-input-pill pe-5" placeholder="<?= __('ph_type_message', 'Type a message...') ?>" autocomplete="off">
                  <span id="micRecordBtn" class="material-symbols-outlined position-absolute end-0 top-50 translate-middle-y me-3 text-muted" style="font-size:22px;cursor:pointer;transition:color .2s;" title="Rakam Mesej Suara / Record Voice" onclick="toggleVoiceRecording()">mic</span>
                </div>

                <button type="submit" class="btn chat-send-btn" title="<?= __('btn_send', 'Send') ?>">
                  <span class="material-symbols-outlined" style="font-size:20px;">send</span>
                </button>
              </form>

            </div>

          </div>
        </div>

      </div>

    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>

<!-- Modal 1: FAQ Templates -->
<div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:1.5rem;">
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="faqModalLabel">
          <span class="material-symbols-outlined text-primary">bolt</span>
          <?= __('modal_faq_title', 'Clinical FAQ & Rapid Response Templates') ?>
        </h5>
        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <p class="text-muted small mb-3"><?= __('modal_faq_sub', 'Select a verified medical protocol to insert into the consultation:') ?></p>
        <div class="d-flex flex-column gap-2">
          <?php foreach ($faqTemplates as $faq): ?>
            <div class="card p-3 border shadow-sm" style="border-radius:1rem;cursor:pointer;transition:all .2s;" onclick="insertTemplate(<?= htmlspecialchars(json_encode($faq['answer'])) ?>)">
              <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($faq['question']) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($faq['answer']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal 2: Patient Clinical History -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:1.5rem;">
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="historyModalLabel">
          <span class="material-symbols-outlined text-primary">history</span>
          <?= __('modal_history_title', 'Patient Triage History') ?>
        </h5>
        <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <div class="p-3 bg-light rounded-3 mb-3">
          <div class="fw-bold fs-6 chat-text-title mb-1" id="histPatientName">Elias Morgan (#84920)</div>
          <span class="badge bg-warning text-dark" id="histTriageLevel">Yellow (Semi-Critical)</span>
        </div>
        <div class="mb-2">
          <label class="fw-semibold small text-muted"><?= __('col_vitals', 'Vital Signs') ?>:</label>
          <div class="small fw-medium chat-text-title" id="histVitals">Temp: 37.4°C, BP: 128/82, Glucose: 5.8 mmol/L</div>
        </div>
        <div class="mb-2">
          <label class="fw-semibold small text-muted"><?= __('col_complaint', 'Chief Complaint') ?>:</label>
          <div class="small fw-medium chat-text-title" id="histComplaint">Painful foot swelling following flood rescue operation.</div>
        </div>
        <div class="mb-0">
          <label class="fw-semibold small text-muted"><?= __('col_time', 'Triage Time') ?>:</label>
          <div class="small text-muted" id="histDate">Today, 09:15 AM</div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-coreui-dismiss="modal"><?= __('btn_close', 'Close') ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Modal 3: Escalate to Video Call -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content text-white" style="border-radius:1.5rem;background:#0f172a;">
      <div class="modal-header border-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-white" id="videoModalLabel">
          <span class="material-symbols-outlined text-danger">videocam</span>
          <?= __('modal_video_title', 'Secure Medical Video Tele-Consultation') ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <div class="bg-black rounded-4 p-5 d-flex flex-column align-items-center justify-content-center mb-3" style="height:320px;position:relative;overflow:hidden;">
          <span class="material-symbols-outlined text-muted" style="font-size:72px;opacity:0.4;">account_circle</span>
          <div class="fs-5 fw-bold mt-2 text-white" id="videoPatientName">Elias Morgan</div>
          <div class="small text-success mt-1 d-flex align-items-center gap-1">
            <span class="badge bg-success rounded-pill p-1" style="width:8px;height:8px;"></span>
            Connected Encrypted WebRTC Link (HD 1080p)
          </div>
          <div class="position-absolute bottom-0 start-0 p-3 small text-muted">00:03:42</div>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-3">
          <button class="btn btn-dark rounded-circle p-3 shadow" title="Mute Mic"><span class="material-symbols-outlined">mic</span></button>
          <button class="btn btn-dark rounded-circle p-3 shadow" title="Camera Toggle"><span class="material-symbols-outlined">videocam</span></button>
          <button class="btn btn-danger rounded-pill px-4 py-2 d-flex align-items-center gap-1 shadow" data-coreui-dismiss="modal">
            <span class="material-symbols-outlined">call_end</span> End Call
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Dedicated Standalone Fullscreen Lightbox Overlay with Zoom & Pan -->
<div id="chatLightboxOverlay" class="chat-lightbox-overlay" onclick="handleOverlayClick(event)">
  <!-- Floating Top Bar with Controls -->
  <div class="chat-lightbox-toolbar">
    <div class="chat-lightbox-btn-group">
      <button type="button" class="chat-lightbox-btn" title="Zoom Out (-)" onclick="zoomImage(-0.25)">
        <span class="material-symbols-outlined" style="font-size:20px;">zoom_out</span>
      </button>
      <span class="px-2 py-1 small text-white fw-bold d-flex align-items-center" id="zoomLevelText" style="min-width:55px;justify-content:center;">100%</span>
      <button type="button" class="chat-lightbox-btn" title="Zoom In (+)" onclick="zoomImage(0.25)">
        <span class="material-symbols-outlined" style="font-size:20px;">zoom_in</span>
      </button>
      <button type="button" class="chat-lightbox-btn" title="Reset Zoom (100%)" onclick="resetZoomImage()">
        <span class="material-symbols-outlined" style="font-size:20px;">restart_alt</span>
      </button>
    </div>
    <button type="button" class="chat-lightbox-btn-close" title="Close (Esc)" onclick="closeLightbox()">
      <span class="material-symbols-outlined" style="font-size:24px;">close</span>
    </button>
  </div>

  <!-- Big Image Container -->
  <div id="zoomViewport" class="chat-lightbox-viewport" onclick="event.stopPropagation()">
    <img src="" id="lightboxImg" class="chat-lightbox-img" alt="Clinical Photo" ondblclick="toggleDoubleZoom(event)">
  </div>

  <div class="text-white-50 small mt-3 d-none d-md-block" style="text-shadow:0 1px 4px rgba(0,0,0,0.8);pointer-events:none;">
    <span class="material-symbols-outlined align-middle" style="font-size:16px;">touch_app</span> Double-click / Scroll wheel to zoom • Drag to pan • Esc to close
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
<script>
  window.sedapInitialQueue = <?= json_encode($initialQueue) ?>;
  window.sedapFirstPatient = <?= $firstPatient ? json_encode($firstPatient) : 'null' ?>;
</script>
<script src="js/livechat.js?v=<?= time() ?>"></script>
</body>
</html>
