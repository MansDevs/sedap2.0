<?php
$adminBase = '../';
$activeNav = 'personnel';
$pageTitle = 'Staff & Volunteers';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase); // admin only
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/personnel_functions.php';

$personnel = getAllPersonnel($pdo);
$successMessage = isset($_GET['success']) ? 'Registered successfully.' : null;
$errorMessage = isset($_GET['error']) ? $_GET['error'] : null;
?>

<!-- Toggle button -->
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="text-on-surface-variant text-sm"><?php echo count($personnel); ?> total registered</p>
    <button type="button" id="toggleFormBtn" class="bg-primary hover:bg-primary-container text-on-primary font-semibold px-5 py-2.5 rounded-full transition-colors flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">person_add</span>
        Register staff / volunteer
    </button>
</div>

<!-- ===================== REGISTRATION FORM ===================== -->
<div id="registerForm" class="hidden bg-surface-container-low border border-outline-variant/30 rounded-[24px] p-5 sm:p-6 mb-8">
    <h3 class="font-headline font-bold text-lg text-on-surface mb-4">Register a new person</h3>
    <form id="personnelForm" action="actions/add_personnel.php" method="POST" class="space-y-4" novalidate>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Type</label>
            <div class="flex gap-3">
                <label class="flex items-center gap-2 px-4 py-2.5 border border-outline-variant/40 rounded-xl cursor-pointer flex-1 justify-center text-sm font-medium has-[:checked]:bg-primary has-[:checked]:text-on-primary has-[:checked]:border-primary transition-colors">
                    <input type="radio" name="type" value="staff" required class="accent-primary"> Staff
                </label>
                <label class="flex items-center gap-2 px-4 py-2.5 border border-outline-variant/40 rounded-xl cursor-pointer flex-1 justify-center text-sm font-medium has-[:checked]:bg-primary has-[:checked]:text-on-primary has-[:checked]:border-primary transition-colors">
                    <input type="radio" name="type" value="volunteer" required class="accent-primary"> Volunteer
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Full name *</label>
                <input type="text" name="full_name" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">IC / Passport number</label>
                <input type="text" name="ic_number" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Gender</label>
                <select name="gender" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                    <option value="" disabled selected>—</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Date of birth</label>
                <input type="date" name="date_of_birth" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Phone</label>
                <input type="text" name="phone" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Address</label>
            <textarea name="address" rows="2" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Department / assigned team</label>
                <input type="text" name="department" required placeholder="e.g. Logistics, Medical Bay" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Availability date</label>
                <input type="date" name="availability_date" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Skills</label>
            <textarea name="skills" rows="2" required placeholder="e.g. First aid certified, fluent in Mandarin" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Emergency contact name</label>
                <input type="text" name="emergency_contact_name" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Emergency contact phone</label>
                <input type="text" name="emergency_contact_phone" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
            <select name="status" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                <option value="pending" selected>Pending</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary font-semibold px-6 py-2.5 rounded-full transition-colors">Register</button>
            <button type="button" id="cancelFormBtn" class="text-on-surface-variant font-semibold px-6 py-2.5 rounded-full hover:bg-surface-container transition-colors">Cancel</button>
        </div>
    </form>
</div>

<?php if (empty($personnel)): ?>

    <div class="bg-surface-container-low border border-outline-variant/30 rounded-[28px] p-10 text-center">
        <div class="w-14 h-14 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-[28px] text-primary">groups</span>
        </div>
        <h2 class="font-headline text-lg font-bold text-on-surface mb-1">No one registered yet</h2>
        <p class="text-on-surface-variant text-sm">Use the button above to register your first staff member or volunteer.</p>
    </div>

<?php else: ?>

    <!-- ===================== SEARCH / FILTER / EXPORT ===================== -->
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input type="text" id="searchInput" placeholder="Search by name, IC, phone, or email…"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-full text-sm outline-none focus:border-2 focus:border-primary">
        </div>
        <select id="typeFilter" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-full text-sm outline-none focus:border-2 focus:border-primary">
            <option value="">All types</option>
            <option value="staff">Staff</option>
            <option value="volunteer">Volunteer</option>
        </select>
        <select id="statusFilter" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant/40 rounded-full text-sm outline-none focus:border-2 focus:border-primary">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
        <a id="exportBtn" href="actions/export_csv.php" class="shrink-0 flex items-center justify-center gap-2 bg-secondary/10 hover:bg-secondary/20 text-secondary font-semibold px-5 py-2.5 rounded-full transition-colors">
            <span class="material-symbols-outlined text-[18px]">download</span>
            Export CSV
        </a>
    </div>

    <p id="resultCount" class="text-xs text-on-surface-variant mb-3"></p>

    <!-- ===================== ROSTER TABLE ===================== -->
    <div class="bg-surface-container-low border border-outline-variant/30 rounded-[24px] overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant/30 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wide">
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Registered</th>
                </tr>
            </thead>
            <tbody id="rosterBody">
                <?php foreach ($personnel as $p): ?>
                    <?php
                        $searchBlob = mb_strtolower($p['full_name'] . ' ' . $p['ic_number'] . ' ' . $p['phone'] . ' ' . $p['email']);
                        $typeBadge = $p['type'] === 'staff'
                            ? 'bg-primary/10 text-primary'
                            : 'bg-secondary/10 text-secondary';
                        $statusBadge = [
                            'pending' => 'bg-surface-container text-secondary border border-secondary/30',
                            'active' => 'bg-primary/10 text-primary',
                            'inactive' => 'bg-error-container text-error',
                        ][$p['status']] ?? 'bg-surface-container text-on-surface-variant';
                    ?>
                    <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-surface-container/50 transition-colors"
                        data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                        data-type="<?php echo htmlspecialchars($p['type']); ?>"
                        data-status="<?php echo htmlspecialchars($p['status']); ?>">
                        <td class="px-4 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize <?php echo $typeBadge; ?>"><?php echo htmlspecialchars($p['type']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-on-surface"><?php echo htmlspecialchars($p['full_name']); ?></p>
                            <?php if ($p['ic_number']): ?>
                                <p class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($p['ic_number']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant">
                            <?php echo htmlspecialchars($p['phone'] ?: '—'); ?>
                            <?php if ($p['email']): ?><br><span class="text-xs"><?php echo htmlspecialchars($p['email']); ?></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant"><?php echo htmlspecialchars($p['department'] ?: '—'); ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant text-xs"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p id="noResults" class="hidden text-center text-sm text-on-surface-variant py-10">No matches for this search/filter.</p>
    </div>

<?php endif; ?>

<!-- ===================== MODAL ===================== -->
<div id="appModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-surface-container-low rounded-[28px] border border-outline-variant/30 shadow-xl max-w-sm w-full p-6 text-center">
        <div id="appModalIconWrap" class="w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center bg-primary/10">
            <span id="appModalIcon" class="material-symbols-outlined text-[28px] text-primary">check_circle</span>
        </div>
        <h3 id="appModalTitle" class="font-headline text-lg font-bold text-on-surface mb-1">Success</h3>
        <p id="appModalMessage" class="text-sm text-on-surface-variant mb-5">Done.</p>
        <button type="button" id="appModalCloseBtn" class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-2.5 rounded-full transition-colors">OK</button>
    </div>
</div>

<style>
    /* Red outline on empty required fields, only after a failed submit attempt */
    .was-validated :invalid { border-color: #ba1a1a !important; }
</style>

<script>
(function () {
    const toggleBtn = document.getElementById('toggleFormBtn');
    const cancelBtn = document.getElementById('cancelFormBtn');
    const form = document.getElementById('registerForm');
    const personnelForm = document.getElementById('personnelForm');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () { form.classList.toggle('hidden'); });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () { form.classList.add('hidden'); });
    }

    // ---------- Modal ----------
    const modal = document.getElementById('appModal');
    const modalIconWrap = document.getElementById('appModalIconWrap');
    const modalIcon = document.getElementById('appModalIcon');
    const modalTitle = document.getElementById('appModalTitle');
    const modalMessage = document.getElementById('appModalMessage');
    const modalCloseBtn = document.getElementById('appModalCloseBtn');

    function showModal(opts) {
        modalTitle.textContent = opts.title;
        modalMessage.textContent = opts.message;
        modalIcon.textContent = opts.icon || 'info';
        const tone = opts.tone === 'error' ? 'bg-error-container text-error' : 'bg-primary/10 text-primary';
        modalIconWrap.className = 'w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center ' + tone;
        modalIcon.className = 'material-symbols-outlined text-[28px] ' + (opts.tone === 'error' ? 'text-error' : 'text-primary');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    modalCloseBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) hideModal(); });

    // Show a result modal automatically if the page just reloaded from a submit
    <?php if ($successMessage): ?>
    showModal({ icon: 'check_circle', title: 'Success', message: <?php echo json_encode($successMessage); ?>, tone: 'success' });
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    showModal({ icon: 'error', title: 'Something went wrong', message: <?php echo json_encode($errorMessage); ?>, tone: 'error' });
    if (form) form.classList.remove('hidden'); // reopen the form so nothing is lost
    <?php endif; ?>

    // ---------- Require every field, with a modal instead of the native bubble ----------
    if (personnelForm) {
        personnelForm.addEventListener('submit', function (e) {
            if (!personnelForm.checkValidity()) {
                e.preventDefault();
                personnelForm.classList.add('was-validated');
                showModal({
                    icon: 'error',
                    title: 'Missing information',
                    message: 'Please fill every field before submitting.',
                    tone: 'error',
                });
            }
        });
    }

    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const exportBtn = document.getElementById('exportBtn');
    const resultCount = document.getElementById('resultCount');
    const noResults = document.getElementById('noResults');
    const rows = document.querySelectorAll('#rosterBody tr');

    // ---------- Export confirmation ----------
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            showModal({
                icon: 'download',
                title: 'Export started',
                message: 'Your CSV file is downloading now.',
                tone: 'success',
            });
        });
    }

    function applyFilters() {
        const q = (searchInput.value || '').trim().toLowerCase();
        const type = typeFilter.value;
        const status = statusFilter.value;
        let visible = 0;

        rows.forEach(function (row) {
            const matchesQ = !q || row.dataset.search.indexOf(q) !== -1;
            const matchesType = !type || row.dataset.type === type;
            const matchesStatus = !status || row.dataset.status === status;
            const show = matchesQ && matchesType && matchesStatus;
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        resultCount.textContent = visible + ' of ' + rows.length + ' shown';
        noResults.classList.toggle('hidden', visible !== 0);

        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (type) params.set('type', type);
        if (status) params.set('status', status);
        exportBtn.href = 'actions/export_csv.php' + (params.toString() ? '?' + params.toString() : '');
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
        typeFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
