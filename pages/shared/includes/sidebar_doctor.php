<?php
$_cuiActive = basename($_SERVER['PHP_SELF'], '.php');

// Calculate initial unread chat count (number of unique patients) for instant server-side badge rendering
$_chatUnread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../config/db.php';
        $_chatStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.sender_id)
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ?
            WHERE u.role = 'user'
              AND m.id > IFNULL(cp.last_read_message_id, 0)
              AND m.deleted_at IS NULL
        ");
        $_chatStmt->execute([(int)$_SESSION['user_id']]);
        $_chatUnread = (int)$_chatStmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<div class="sidebar sidebar-fixed sidebar-dark" id="sidebar">
  <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3">
    <div class="sidebar-brand-full d-flex align-items-center gap-2">
      <span class="material-symbols-outlined" style="font-size:28px;color:#fff;">medical_services</span>
      <span class="fw-bold fs-5 text-white">SeDaP</span>
    </div>
    <div class="sidebar-brand-narrow">
      <span class="material-symbols-outlined" style="font-size:28px;color:#fff;">medical_services</span>
    </div>
  </div>
  <ul class="sidebar-nav">
    <li class="nav-title">Portal Doktor</li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/cdashboard.php">
        <span class="material-symbols-outlined nav-icon">dashboard</span>Dashboard
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/triage_list.php">
        <span class="material-symbols-outlined nav-icon">format_list_bulleted</span>Senarai Triaj
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/triage_counter.php">
        <span class="material-symbols-outlined nav-icon">add_circle</span>Kaunter Triaj
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/announcements.php">
        <span class="material-symbols-outlined nav-icon">campaign</span>Pengumuman
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/posters.php">
        <span class="material-symbols-outlined nav-icon">image</span>Poster
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap2.0/pages/doctor/patientfamily.php">
        <span class="material-symbols-outlined nav-icon">groups</span>Pesakit &amp; Keluarga
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center justify-content-between" href="/sedap2.0/pages/doctor/livechat.php">
        <div class="d-flex align-items-center">
          <span class="material-symbols-outlined nav-icon">chat</span>Live Chat (BETA)
        </div>
        <span class="badge rounded-pill bg-danger sidebar-chat-badge <?= $_chatUnread > 0 ? '' : 'd-none' ?>" style="font-size:0.75rem;">
          <?= $_chatUnread > 99 ? '99+' : $_chatUnread ?>
        </span>
      </a>
    </li>
    <li class="nav-title">Modul Kesihatan</li>
    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <span class="material-symbols-outlined nav-icon">favorite</span>Modul Kesihatan
      </a>
      <ul class="nav-group-items">
        <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/doctor/health/water.php"><span class="nav-icon material-symbols-outlined">water_drop</span>Air</a></li>
        <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/doctor/health/bristol.php"><span class="nav-icon material-symbols-outlined">bar_chart</span>Bristol</a></li>
        <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/doctor/health/mood.php"><span class="nav-icon material-symbols-outlined">sentiment_satisfied</span>Mood</a></li>
        <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/doctor/health/medicine.php"><span class="nav-icon material-symbols-outlined">medication</span>Ubat</a></li>
      </ul>
    </li>
    <li class="nav-title">Akaun</li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/doctor/settings.php"><span class="material-symbols-outlined nav-icon">settings</span>Tetapan</a></li>
    <li class="nav-item"><a class="nav-link text-danger" href="/sedap2.0/pages/auth/logout.php"><span class="material-symbols-outlined nav-icon text-danger">logout</span>Log Keluar</a></li>
  </ul>
  
</div>
