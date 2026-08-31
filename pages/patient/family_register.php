<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php'); exit;
}
$userId    = $_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $head_name = trim($_POST['head_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $members   = (int)($_POST['total_members'] ?? 1);

    if ($head_name) {
        $pdo->prepare("INSERT INTO families (user_id, head_name, phone, address, total_members, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([$userId, $head_name, $phone, $address, $members]);
        $msg = 'Maklumat keluarga berjaya didaftarkan.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pendaftaran Keluarga — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">family_restroom</span>Pendaftaran Isi Rumah</h1>
        <p class="page-subtitle">Daftarkan maklumat ahli keluarga anda untuk bantuan kecemasan</p>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-semibold">Nama Ketua Keluarga *</label><input type="text" name="head_name" class="form-control" required value="<?= $userName ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-semibold">No. Telefon</label><input type="tel" name="phone" class="form-control"></div>
              <div class="col-md-8"><label class="form-label small fw-semibold">Alamat Rumah / Zon Komuniti</label><input type="text" name="address" class="form-control" placeholder="Contoh: No. 12, Lorong 4, Zon B"></div>
              <div class="col-md-4"><label class="form-label small fw-semibold">Jumlah Ahli Keluarga</label><input type="number" name="total_members" class="form-control" value="1" min="1"></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3 d-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;">save</span>Daftar Keluarga</button>
          </form>
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
