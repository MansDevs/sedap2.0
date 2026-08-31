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
    $name = trim($_POST['name'] ?? '');
    $ic   = trim($_POST['ic_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'male';
    if ($name) {
        $pdo->prepare("INSERT INTO patients (full_name, ic_number, phone, gender, created_at) VALUES (?,?,?,?,NOW())")->execute([$name, $ic, $phone, $gender]);
        $msg = 'Pesakit berjaya didaftarkan.';
    }
}
$patients = $pdo->query("SELECT * FROM patients ORDER BY full_name LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengurusan Pesakit — SeDaP</title>
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
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">person</span>Pengurusan Pesakit</h1>
          <p class="page-subtitle">Senarai rekod profil pesakit dan pendaftaran baharu</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-1" data-coreui-toggle="modal" data-coreui-target="#addPatientModal">
          <span class="material-symbols-outlined" style="font-size:18px;">person_add</span>Daftar Pesakit
        </button>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Nama</th><th>No. IC</th><th>Jantina</th><th>Telefon</th><th>Tarikh Daftar</th></tr>
              </thead>
              <tbody>
                <?php if (empty($patients)): ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">Tiada rekod pesakit</td></tr>
                <?php else: ?>
                  <?php foreach ($patients as $idx => $p): ?>
                    <tr>
                      <td><?= $idx + 1 ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></td>
                      <td class="small text-muted"><?= htmlspecialchars($p['ic_number'] ?? '—') ?></td>
                      <td><span class="badge bg-light text-dark"><?= ucfirst(htmlspecialchars($p['gender'] ?? '—')) ?></span></td>
                      <td class="small"><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                      <td class="small text-muted"><?= !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '—' ?></td>
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

<!-- Modal Add Patient -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header"><h5 class="modal-title fw-bold">Daftar Pesakit Baharu</h5><button type="button" class="btn-close" data-coreui-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label fw-semibold small">Nama Penuh *</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label fw-semibold small">No. IC / Kad Pengenalan</label><input type="text" name="ic_number" class="form-control"></div>
          <div class="mb-3"><label class="form-label fw-semibold small">No. Telefon</label><input type="tel" name="phone" class="form-control"></div>
          <div class="mb-3"><label class="form-label fw-semibold small">Jantina</label><select name="gender" class="form-select"><option value="male">Lelaki</option><option value="female">Perempuan</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan Pesakit</button></div>
      </form>
    </div>
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
