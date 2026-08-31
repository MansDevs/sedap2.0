<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$types = [
  ['type'=>1, 'title'=>'Tipe 1: Berketul Keras', 'desc'=>'Ketulan keras berasingan seperti kacang (Sembelit teruk)'],
  ['type'=>2, 'title'=>'Tipe 2: Berbentuk Sosej Berketul', 'desc'=>'Berbentuk sosej tetapi berketul-ketul kasar (Sembelit)'],
  ['type'=>3, 'title'=>'Tipe 3: Sosej Beretak', 'desc'=>'Bentuk sosej dengan retakan di permukaan (Normal)'],
  ['type'=>4, 'title'=>'Tipe 4: Sosej Licin & Lembut', 'desc'=>'Bentuk sosej atau ular, licin dan mudah keluar (Paling Ideal)'],
  ['type'=>5, 'title'=>'Tipe 5: Gumpalan Lembut', 'desc'=>'Gumpalan lembut dengan tepi jelas (Kurang serat)'],
  ['type'=>6, 'title'=>'Tipe 6: Lembik / Berserabut', 'desc'=>'Kepingan gebu, najis lembik (Cirit-birit ringan)'],
  ['type'=>7, 'title'=>'Tipe 7: Cair Sepenuhnya', 'desc'=>'Cair tanpa ketulan pejal (Cirit-birit teruk — jumpa doktor)']
];
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Skala Najis Bristol — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">bar_chart</span>Panduan Skala Najis Bristol</h1>
        <p class="page-subtitle">Kenal pasti tahap kesihatan pencernaan anda berdasarkan bentuk najis</p>
      </div>

      <div class="row g-3">
        <?php foreach ($types as $t): 
          $isAlert = $t['type'] >= 6;
          $isGood = in_array($t['type'], [3,4]);
        ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-<?= $isAlert ? 'danger' : ($isGood ? 'success' : 'warning') ?>">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span class="badge bg-<?= $isAlert ? 'danger' : ($isGood ? 'success' : 'warning') ?>">Tipe <?= $t['type'] ?></span>
                <span class="small fw-semibold"><?= $isAlert ? 'Bahaya / Cirit' : ($isGood ? 'Sihat' : 'Sederhana') ?></span>
              </div>
              <div class="card-body">
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($t['title']) ?></h6>
                <p class="small text-muted mb-0"><?= htmlspecialchars($t['desc']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
  <?php include '../../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
