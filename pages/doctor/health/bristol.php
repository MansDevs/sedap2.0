<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$types_ms = [
  ['type'=>1, 'title'=>'Tipe 1: Berketul Keras', 'desc'=>'Ketulan keras berasingan seperti kacang, sukar dikeluarkan (Sembelit teruk)'],
  ['type'=>2, 'title'=>'Tipe 2: Berbentuk Sosej Berketul', 'desc'=>'Berbentuk sosej tetapi berketul-ketul kasar (Sembelit ringan)'],
  ['type'=>3, 'title'=>'Tipe 3: Sosej Beretak', 'desc'=>'Seperti sosej dengan retakan pada permukaannya (Normal)'],
  ['type'=>4, 'title'=>'Tipe 4: Sosej Licin & Lembut', 'desc'=>'Bentuk sosej atau ular, licin dan mudah dikeluarkan (Normal / Ideal)'],
  ['type'=>5, 'title'=>'Tipe 5: Gumpalan Lembut', 'desc'=>'Gumpalan lembut dengan tepi yang jelas, mudah keluar (Kurang serat)'],
  ['type'=>6, 'title'=>'Tipe 6: Lembik / Berserabut', 'desc'=>'Kepingan gebu dengan tepi bergerigi, najis lembik (Cirit-birit ringan)'],
  ['type'=>7, 'title'=>'Tipe 7: Cair Sepenuhnya', 'desc'=>'Cair tanpa ketulan pejal, keluar secara mendadak (Cirit-birit teruk / Bahaya)']
];

$types_en = [
  ['type'=>1, 'title'=>'Type 1: Separate Hard Lumps', 'desc'=>'Nut-like hard lumps, difficult to pass (Severe constipation)'],
  ['type'=>2, 'title'=>'Type 2: Sausage-shaped Lumpy', 'desc'=>'Sausage-shaped but distinctly lumpy (Mild constipation)'],
  ['type'=>3, 'title'=>'Type 3: Sausage with Cracks', 'desc'=>'Like a sausage with cracks on the surface (Normal)'],
  ['type'=>4, 'title'=>'Type 4: Smooth & Soft Sausage', 'desc'=>'Like a snake or sausage, smooth and easy to pass (Ideal / Normal)'],
  ['type'=>5, 'title'=>'Type 5: Soft Blobs', 'desc'=>'Soft blobs with clear-cut edges, passed easily (Lacking fiber)'],
  ['type'=>6, 'title'=>'Type 6: Fluffy Pieces / Mushy', 'desc'=>'Fluffy pieces with ragged edges, a mushy stool (Mild diarrhea)'],
  ['type'=>7, 'title'=>'Type 7: Entirely Liquid', 'desc'=>'Watery, no solid pieces, entirely liquid (Severe diarrhea / Danger)']
];

$types = ($_SESSION['lang'] ?? 'ms') === 'en' ? $types_en : $types_ms;
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_bristol_title', 'Skala Bristol') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">bar_chart</span><?= __('page_bristol_title', 'Carta Skala Najis Bristol') ?></h1>
        <p class="page-subtitle"><?= __('page_bristol_sub', 'Rujukan klinikal untuk mengklasifikasikan bentuk dan konsistensi najis pesakit') ?></p>
      </div>

      <div class="row g-3">
        <?php foreach ($types as $t): 
          $isDanger = $t['type'] >= 6;
          $isGood = in_array($t['type'], [3, 4]);
        ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-<?= $isDanger ? 'danger' : ($isGood ? 'success' : 'warning') ?>">
              <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="badge bg-<?= $isDanger ? 'danger' : ($isGood ? 'success' : 'warning') ?> fs-6"><?= ($_SESSION['lang'] ?? 'ms') === 'en' ? 'Type ' . $t['type'] : 'Tipe ' . $t['type'] ?></span>
                <span class="small fw-semibold"><?= $isDanger ? (__('lbl_attention', 'Perhatian')) : ($isGood ? (__('lbl_ideal', 'Ideal')) : (__('lbl_moderate', 'Sederhana'))) ?></span>
              </div>
              <div class="card-body">
                <h6 class="fw-bold mb-2"><?= htmlspecialchars($t['title']) ?></h6>
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
