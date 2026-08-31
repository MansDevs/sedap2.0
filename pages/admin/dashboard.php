<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

// Stat counts
$patientCount = 0; $triageToday = 0; $annCount = 0; $staffCount = 0;
try {
    $patientCount = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $triageToday  = (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE DATE(triaged_at) = CURDATE()")->fetchColumn();
    $annCount     = (int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn();
    $staffCount   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('doctor','volunteer') AND status='active'")->fetchColumn();
} catch (Exception $e) {}

// Recent 5 triage records
$recentTriages = [];
try {
    $recentTriages = $pdo->query(
        "SELECT tr.*, p.name AS patient_name, u.name AS staff_name 
         FROM triage_records tr 
         LEFT JOIN patients p ON tr.patient_id = p.id 
         LEFT JOIN users u ON tr.triaged_by = u.id 
         ORDER BY tr.triaged_at DESC LIMIT 5"
    )->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('admin_dash_title', 'Dashboard Pentadbir') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">admin_panel_settings</span><?= __('admin_dash_title', 'Dashboard Pentadbir') ?></h1>
          <p class="page-subtitle"><?= __('admin_dash_subtitle', 'Ringkasan operasi dan pemantauan sistem SeDaP') ?></p>
        </div>
        <div class="d-flex gap-2">
          <a href="announcements/index.php" class="btn btn-primary d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size:18px;">campaign</span><?= __('btn_add', 'Pengumuman Baru') ?>
          </a>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-teal">
            <div>
              <div class="stat-value"><?= $patientCount ?></div>
              <div class="stat-label"><?= __('admin_stat_patients', 'Jumlah Pesakit') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">people</span>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-green">
            <div>
              <div class="stat-value"><?= $triageToday ?></div>
              <div class="stat-label"><?= __('admin_stat_triage_today', 'Triaj Hari Ini') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">monitor_heart</span>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-amber">
            <div>
              <div class="stat-value"><?= $annCount ?></div>
              <div class="stat-label"><?= __('admin_stat_announcements', 'Pengumuman Aktif') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">campaign</span>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-red">
            <div>
              <div class="stat-value"><?= $staffCount ?></div>
              <div class="stat-label"><?= __('admin_stat_staff', 'Petugas & Sukarelawan') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">badge</span>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Recent Triage -->
        <div class="col-12 col-xl-8">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span class="d-flex align-items-center gap-2"><span class="material-symbols-outlined">monitor_heart</span><strong><?= __('admin_recent_triage', 'Rekod Triaj Terkini') ?></strong></span>
              <a href="triage/index.php" class="btn btn-sm btn-outline-primary"><?= __('btn_view_all', 'Lihat Semua') ?></a>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr><th><?= __('col_patient_name', 'Pesakit') ?></th><th><?= __('col_level', 'Tahap') ?></th><th><?= __('col_status', 'Status') ?></th><th><?= __('col_triaged_by', 'Ditapis Oleh') ?></th><th><?= __('col_time', 'Masa') ?></th></tr>
                  </thead>
                  <tbody>
                    <?php if (empty($recentTriages)): ?>
                      <tr><td colspan="5" class="text-center text-muted py-4"><?= __('doc_no_records', 'Tiada rekod triaj') ?></td></tr>
                    <?php else: ?>
                      <?php foreach ($recentTriages as $tr): 
                        $lv = strtolower($tr['triage_level'] ?? 'green');
                        $bcMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $bc = $bcMap[$lv] ?? 'badge-triage-green';
                        $llMap = [
                            'red' => __('triage_red', 'Merah'),
                            'yellow' => __('triage_yellow', 'Kuning'),
                            'green' => __('triage_green', 'Hijau')
                        ];
                        $ll = $llMap[$lv] ?? __('triage_green', 'Hijau');
                      ?>
                      <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($tr['patient_name'] ?? 'Pesakit #' . $tr['id']) ?></td>
                        <td><span class="badge <?= $bc ?>"><?= $ll ?></span></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($tr['status'] ?? 'waiting') ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars($tr['staff_name'] ?? 'Sistem') ?></td>
                        <td class="small text-muted"><?= $tr['triaged_at'] ? date('d/m H:i', strtotime($tr['triaged_at'])) : '—' ?></td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="col-12 col-xl-4">
          <div class="card h-100">
            <div class="card-header"><span class="material-symbols-outlined">bolt</span><strong><?= __('btn_quick_actions', 'Tindakan Pantas') ?></strong></div>
            <div class="card-body d-flex flex-column gap-2">
              <a href="triage/add.php" class="btn btn-primary d-flex align-items-center gap-2"><span class="material-symbols-outlined">add_circle</span><?= __('admin_btn_new_triage', 'Daftar Triaj Baru') ?></a>
              <a href="patients/index.php" class="btn btn-outline-primary d-flex align-items-center gap-2"><span class="material-symbols-outlined">person_add</span><?= __('admin_btn_patients', 'Pengurusan Pesakit') ?></a>
              <a href="personnel/index.php" class="btn btn-outline-secondary d-flex align-items-center gap-2"><span class="material-symbols-outlined">badge</span><?= __('admin_btn_staff', 'Pengurusan Kakitangan') ?></a>
              <a href="posters/index.php" class="btn btn-outline-secondary d-flex align-items-center gap-2"><span class="material-symbols-outlined">image</span><?= __('admin_btn_posters', 'Galeri Poster') ?></a>
              <a href="settings.php" class="btn btn-outline-secondary d-flex align-items-center gap-2"><span class="material-symbols-outlined">settings</span><?= __('admin_btn_settings', 'Tetapan Sistem') ?></a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
