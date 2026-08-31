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

$posters = [];
try {
    $stmt = $pdo->query("SELECT * FROM posters WHERE status='published' ORDER BY created_at DESC");
    $posters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_posters_title', 'Galeri Poster') ?> — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">image</span><?= __('page_posters_title', 'Galeri Poster Infografik') ?></h1>
        <p class="page-subtitle"><?= __('page_posters_sub', 'Bahan pendidikan kesihatan awam dan panduan pencegahan') ?></p>
      </div>

      <?php if (empty($posters)): ?>
        <div class="card text-center p-5">
          <span class="material-symbols-outlined d-block text-muted mb-2" style="font-size:48px;opacity:.4;">image</span>
          <h5 class="fw-semibold text-muted"><?= __('no_posters_published', 'Tiada Poster Diterbitkan') ?></h5>
          <p class="text-muted small"><?= __('no_posters_desc', 'Poster infografik kesihatan akan dipaparkan di sini apabila diterbitkan.') ?></p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($posters as $p): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="card h-100 shadow-sm overflow-hidden">
                <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                  <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" class="img-fluid h-100 w-100 object-fit-cover" alt="Poster">
                  <?php else: ?>
                    <span class="material-symbols-outlined text-muted" style="font-size:64px;opacity:.3;">image</span>
                  <?php endif; ?>
                </div>
                <div class="card-body">
                  <h6 class="fw-semibold mb-1"><?= htmlspecialchars($p['title'] ?? __('ph_poster_title', 'Poster Kesihatan')) ?></h6>
                  <p class="small text-muted mb-0"><?= date('d M Y', strtotime($p['created_at'])) ?></p>
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
