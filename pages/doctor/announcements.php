<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin', 'volunteer'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$announcements = [];
try {
    $stmt = $pdo->query("SELECT * FROM announcements WHERE status='published' ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_announcements_title', 'Pengumuman') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">campaign</span><?= __('page_announcements_title', 'Pengumuman Rasmi') ?></h1>
        <p class="page-subtitle"><?= __('page_announcements_sub', 'Maklumat terkini dan pekeliling kesihatan komuniti') ?></p>
      </div>

      <?php if (empty($announcements)): ?>
        <div class="card text-center p-5">
          <span class="material-symbols-outlined d-block text-muted mb-2" style="font-size:48px;opacity:.4;">campaign</span>
          <h5 class="fw-semibold text-muted"><?= __('no_announcements_published', 'Tiada Pengumuman Diterbitkan') ?></h5>
          <p class="text-muted small"><?= __('no_announcements_desc', 'Semua maklumat rasmi akan dipaparkan di sini apabila diterbitkan oleh pentadbir.') ?></p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($announcements as $a): ?>
            <div class="col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                  <span class="fw-semibold text-primary"><?= htmlspecialchars($a['title']) ?></span>
                </div>
                <div class="card-body">
                  <p class="card-text text-muted small" style="white-space:pre-line;"><?= htmlspecialchars($a['content'] ?? '') ?></p>
                </div>
                <div class="card-footer bg-transparent border-0 text-muted small d-flex align-items-center gap-1">
                  <span class="material-symbols-outlined" style="font-size:16px;">calendar_today</span>
                  <?= date('d M Y, H:i', strtotime($a['created_at'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
