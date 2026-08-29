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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status  = $_POST['status'] ?? 'published';
        if ($title) {
            $pdo->prepare("INSERT INTO announcements (title, content, status, created_at) VALUES (?, ?, ?, NOW())")->execute([$title, $content, $status]);
            $msg = 'Pengumuman berjaya dicipta.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
            $msg = 'Pengumuman berjaya dipadam.';
        }
    }
}
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengurusan Pengumuman — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">campaign</span>Pengurusan Pengumuman</h1>
        <p class="page-subtitle">Cipta, kemas kini, dan terbitkan pengumuman rasmi komuniti</p>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <!-- Form -->
        <div class="col-lg-5">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">add_circle</span><strong>Cipta Pengumuman Baharu</strong></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                  <label class="form-label fw-semibold small">Tajuk Pengumuman</label>
                  <input type="text" name="title" class="form-control" placeholder="Contoh: Peringatan Pengambilan Air Bersih" required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold small">Kandungan</label>
                  <textarea name="content" class="form-control" rows="5" placeholder="Tulis isi pengumuman..." required></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold small">Status</label>
                  <select name="status" class="form-select">
                    <option value="published">Diterbitkan (Published)</option>
                    <option value="draft">Draf (Draft)</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                  <span class="material-symbols-outlined" style="font-size:18px;">save</span>Terbitkan Pengumuman
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- List -->
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">list</span><strong>Senarai Pengumuman (<?= count($announcements) ?>)</strong></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr><th>Tajuk</th><th><?= __('col_status', 'Status') ?></th><th>Tarikh</th><th>Tindakan</th></tr>
                  </thead>
                  <tbody>
                    <?php if (empty($announcements)): ?>
                      <tr><td colspan="4" class="text-center text-muted py-4">Tiada pengumuman dijumpai</td></tr>
                    <?php else: ?>
                      <?php foreach ($announcements as $a): ?>
                        <tr>
                          <td class="fw-semibold"><?= htmlspecialchars($a['title']) ?></td>
                          <td><span class="badge bg-<?= $a['status'] === 'published' ? 'success' : 'secondary' ?>"><?= ucfirst($a['status']) ?></span></td>
                          <td class="small text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                          <td>
                            <form method="POST" onsubmit="return confirm('Padam pengumuman ini?')" style="display:inline;">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="id" value="<?= $a['id'] ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Padam">
                                <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
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
