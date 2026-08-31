<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$patients = [];
$families = [];
try {
    $patients = $pdo->query("SELECT * FROM patients ORDER BY full_name LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    $families = $pdo->query("SELECT * FROM families ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_family_title', 'Pesakit & Maklumat Keluarga') ?> — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">groups</span><?= __('page_family_title', 'Pesakit & Maklumat Keluarga') ?></h1>
        <p class="page-subtitle"><?= __('page_family_sub', 'Senarai lengkap pesakit berdaftar dan isi rumah komuniti') ?></p>
      </div>

      <div class="card mb-4">
        <div class="card-header p-0">
          <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="patientFamilyTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-semibold" id="patients-tab" data-coreui-toggle="tab" data-coreui-target="#patients-pane" type="button" role="tab"><?= __('tab_registered_patients', 'Pesakit Berdaftar') ?> (<?= count($patients) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-semibold" id="families-tab" data-coreui-toggle="tab" data-coreui-target="#families-pane" type="button" role="tab"><?= __('tab_family_info', 'Maklumat Keluarga') ?> (<?= count($families) ?>)</button>
            </li>
          </ul>
        </div>
        <div class="card-body tab-content" id="patientFamilyTabContent">
          <!-- Tab 1: Pesakit -->
          <div class="tab-pane fade show active" id="patients-pane" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>#</th><th><?= __('col_name', 'Nama') ?></th><th><?= __('col_ic', 'No. IC') ?></th><th><?= __('col_gender', 'Jantina') ?></th><th><?= __('col_phone', 'Telefon') ?></th><th><?= __('col_date_reg', 'Tarikh Daftar') ?></th></tr>
                </thead>
                <tbody>
                  <?php if (empty($patients)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= __('no_patients_found', 'Tiada rekod pesakit') ?></td></tr>
                  <?php else: ?>
                    <?php foreach ($patients as $idx => $p): ?>
                      <tr>
                        <td><?= $idx + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($p['full_name'] ?? '—') ?></td>
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

          <!-- Tab 2: Keluarga -->
          <div class="tab-pane fade" id="families-pane" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>#</th><th><?= __('col_head_family', 'Ketua Keluarga') ?></th><th><?= __('col_phone', 'No. Telefon') ?></th><th><?= __('col_address_zone', 'Alamat / Zon') ?></th><th><?= __('col_total_members', 'Jumlah Ahli') ?></th><th><?= __('col_date_reg', 'Tarikh Daftar') ?></th></tr>
                </thead>
                <tbody>
                  <?php if (empty($families)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= __('no_families_found', 'Tiada rekod keluarga') ?></td></tr>
                  <?php else: ?>
                    <?php foreach ($families as $idx => $f): ?>
                      <tr>
                        <td><?= $idx + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($f['head_name'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($f['phone'] ?? '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($f['address'] ?? '—') ?></td>
                        <td><span class="badge bg-primary"><?= (int)($f['total_members'] ?? 1) ?> <?= __('unit_people', 'Orang') ?></span></td>
                        <td class="small text-muted"><?= !empty($f['created_at']) ? date('d/m/Y', strtotime($f['created_at'])) : '—' ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
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
