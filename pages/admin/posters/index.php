<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $title = trim($_POST['title'] ?? '');
    $img   = trim($_POST['image_url'] ?? '');
    if ($title) {
        $pdo->prepare("INSERT INTO posters (title, image_url, status, created_at) VALUES (?, ?, 'published', NOW())")->execute([$title, $img]);
        $msg = 'Poster berjaya ditambah.';
    }
}
$posters = $pdo->query("SELECT * FROM posters ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengurusan Poster — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">image</span>Pengurusan Galeri Poster</h1>
          <p class="page-subtitle">Muat naik dan urus poster infografik kesihatan awam</p>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">add_photo_alternate</span><strong>Tambah Poster Baharu</strong></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-3"><label class="form-label fw-semibold small">Tajuk Poster</label><input type="text" name="title" class="form-control" placeholder="Contoh: Langkah Mencuci Tangan" required></div>
                <div class="mb-3"><label class="form-label fw-semibold small">Pautan Imej / URL</label><input type="url" name="image_url" class="form-control" placeholder="https://..."></div>
                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;">upload</span>Terbitkan Poster</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">collections</span><strong>Poster Diterbitkan (<?= count($posters) ?>)</strong></div>
            <div class="card-body">
              <?php if (empty($posters)): ?>
                <p class="text-muted text-center py-4">Tiada poster dimuat naik.</p>
              <?php else: ?>
                <div class="row g-3">
                  <?php foreach ($posters as $p): ?>
                    <div class="col-sm-6 col-md-4">
                      <div class="card h-100 shadow-sm overflow-hidden">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height:140px;">
                          <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" class="img-fluid h-100 w-100 object-fit-cover">
                          <?php else: ?>
                            <span class="material-symbols-outlined text-muted" style="font-size:48px;opacity:.3;">image</span>
                          <?php endif; ?>
                        </div>
                        <div class="card-body p-2 text-center">
                          <span class="small fw-semibold d-block"><?= htmlspecialchars($p['title']) ?></span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
