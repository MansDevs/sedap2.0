<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Sukarelawan');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT = $_ROOT ?? sedap_root();

// Stats
$triageCount = 0;
$annCount    = 0;
try { $triageCount = $pdo->query("SELECT COUNT(*) FROM triage_records WHERE DATE(triaged_at)=CURDATE()")->fetchColumn(); } catch(Exception $e) {}
try { $annCount = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn(); } catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Sukarelawan — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="page-title">
            <span class="material-symbols-outlined" style="color:var(--cui-primary);">volunteer_activism</span>
            Dashboard Sukarelawan
          </h1>
          <p class="page-subtitle">Selamat datang, <?= $userName ?>. Terima kasih atas sumbangan anda.</p>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
          <div class="stat-card stat-teal">
            <div>
              <div class="stat-value"><?= $triageCount ?></div>
              <div class="stat-label">Triaj Hari Ini</div>
            </div>
            <span class="material-symbols-outlined stat-icon">monitor_heart</span>
          </div>
        </div>
        <div class="col-sm-6 col-xl-4">
          <div class="stat-card stat-green">
            <div>
              <div class="stat-value"><?= $annCount ?></div>
              <div class="stat-label">Pengumuman Aktif</div>
            </div>
            <span class="material-symbols-outlined stat-icon">campaign</span>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">task_alt</span><strong>Tugasan Semasa</strong></div>
            <div class="card-body">
              <div class="alert alert-info d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                Tiada tugasan ditetapkan buat masa ini.
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">campaign</span><strong>Pengumuman Terkini</strong></div>
            <div class="card-body">
              <?php
              try {
                $anns = $pdo->query("SELECT title, created_at FROM announcements WHERE status='published' ORDER BY created_at DESC LIMIT 5")->fetchAll();
                if ($anns): foreach ($anns as $a): ?>
                  <div class="d-flex align-items-center gap-2 mb-2 small">
                    <span class="material-symbols-outlined text-primary" style="font-size:16px;">fiber_manual_record</span>
                    <span><?= htmlspecialchars($a['title']) ?></span>
                    <span class="ms-auto text-muted"><?= date('d/m', strtotime($a['created_at'])) ?></span>
                  </div>
                <?php endforeach; else: ?>
                  <p class="text-muted small">Tiada pengumuman.</p>
                <?php endif;
              } catch(Exception $e) { echo '<p class="text-muted small">Tiada pengumuman.</p>'; } ?>
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