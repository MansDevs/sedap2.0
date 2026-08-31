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

$families = [];
try {
    $families = $pdo->query("SELECT * FROM families ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_family_title', 'Maklumat Keluarga') ?> — SeDaP</title>
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
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">family_restroom</span><?= __('page_family_title', 'Maklumat Keluarga Komuniti') ?></h1>
        <p class="page-subtitle">Senarai isi rumah dan pemetaan risiko kesihatan keluarga</p>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Ketua Keluarga</th><th>No. Telefon</th><th>Alamat / Zon</th><th>Jumlah Ahli</th><th>Tarikh Daftar</th></tr>
              </thead>
              <tbody>
                <?php if (empty($families)): ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">Tiada rekod keluarga dijumpai</td></tr>
                <?php else: ?>
                  <?php foreach ($families as $idx => $f): ?>
                    <tr>
                      <td><?= $idx + 1 ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($f['head_name'] ?? '—') ?></td>
                      <td class="small"><?= htmlspecialchars($f['phone'] ?? '—') ?></td>
                      <td class="small text-muted"><?= htmlspecialchars($f['address'] ?? '—') ?></td>
                      <td><span class="badge bg-primary"><?= (int)($f['total_members'] ?? 1) ?> Orang</span></td>
                      <td class="small text-muted"><?= !empty($f['created_at']) ? date('d/m/Y', strtotime($f['created_at'])) : '—' ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
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
