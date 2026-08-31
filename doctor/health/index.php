<?php
$doctorBase = '../';
$activeNav = 'health';
$pageTitle = 'Health Module';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/includes/health_functions.php';

$patients = getAllPatients($pdo);
?>

<?php if (empty($patients)): ?>

    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] sm:rounded-[32px] p-8 sm:p-12 text-center max-w-xl mx-auto shadow-sm">
        <div class="w-14 h-14 mx-auto mb-5 bg-primary/10 rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-[28px] text-primary">favorite</span>
        </div>
        <h2 class="font-headline text-xl font-bold text-on-surface mb-2">No patients yet</h2>
        <p class="text-on-surface-variant text-sm">
            The health module tracks entries per patient, so register a patient first before logging Bristol scale, water intake, mood, or medicine.
        </p>
    </div>

<?php else: ?>

    <!-- Patient picker -->
    <div class="mb-6 max-w-md">
        <label for="patientSelect" class="block text-sm font-semibold text-on-surface-variant mb-2">Patient</label>
        <select id="patientSelect" class="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/40 rounded-2xl text-on-surface focus:border-2 focus:border-primary focus:ring-0 outline-none transition-all">
            <option value="">Select a patient…</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?php echo (int) $p['id']; ?>">
                    <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo htmlspecialchars($p['registration_number']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="healthContent" class="hidden">

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-1">
            <button type="button" data-tab="bristol" class="tab-btn shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold border transition-colors">
                <span class="material-symbols-outlined text-[18px]">emoji_nature</span> Bristol Scale
            </button>
            <button type="button" data-tab="water" class="tab-btn shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold border transition-colors">
                <span class="material-symbols-outlined text-[18px]">water_drop</span> Water Tracker
            </button>
            <button type="button" data-tab="mood" class="tab-btn shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold border transition-colors">
                <span class="material-symbols-outlined text-[18px]">mood</span> Mood Journal
            </button>
            <button type="button" data-tab="medicine" class="tab-btn shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold border transition-colors">
                <span class="material-symbols-outlined text-[18px]">medication</span> Medicine
            </button>
        </div>

        <!-- ===================== BRISTOL TAB ===================== -->
        <div id="tab-bristol" class="tab-panel grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6">
            <form id="bristolForm" class="bg-surface-container-low border border-[#e7d8c1] rounded-[24px] p-5 space-y-4 h-fit">
                <h3 class="font-headline font-bold text-on-surface">Log an entry</h3>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Bristol type</label>
                    <select name="scale_type" required class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                        <option value="1">1 — Separate hard lumps (severe constipation)</option>
                        <option value="2">2 — Lumpy sausage (mild constipation)</option>
                        <option value="3">3 — Sausage with cracks (normal)</option>
                        <option value="4" selected>4 — Smooth sausage (ideal)</option>
                        <option value="5">5 — Soft blobs (lacking fiber)</option>
                        <option value="6">6 — Mushy, ragged edges (mild diarrhea)</option>
                        <option value="7">7 — Watery, no solid pieces (severe diarrhea)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Notes (optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-2.5 rounded-full transition-colors">Log entry</button>
            </form>
            <div>
                <h3 class="font-headline font-bold text-on-surface mb-3">Recent entries</h3>
                <div id="bristolList" class="space-y-2"></div>
            </div>
        </div>

        <!-- ===================== WATER TAB ===================== -->
        <div id="tab-water" class="tab-panel grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6 hidden">
            <form id="waterForm" class="bg-surface-container-low border border-[#e7d8c1] rounded-[24px] p-5 space-y-4 h-fit">
                <h3 class="font-headline font-bold text-on-surface">Log water intake</h3>
                <div id="waterTodayTotal" class="bg-primary/10 text-primary rounded-2xl px-4 py-3 text-center font-headline font-bold text-lg">0 ml today</div>
                <div class="flex gap-2">
                    <button type="button" data-ml="250" class="water-quick flex-1 border border-outline-variant/40 rounded-xl py-2 text-sm font-semibold hover:bg-surface-container transition-colors">+250ml</button>
                    <button type="button" data-ml="500" class="water-quick flex-1 border border-outline-variant/40 rounded-xl py-2 text-sm font-semibold hover:bg-surface-container transition-colors">+500ml</button>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Custom amount (ml)</label>
                    <input type="number" name="amount_ml" min="1" max="5000" placeholder="e.g. 350" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-2.5 rounded-full transition-colors">Log entry</button>
            </form>
            <div>
                <h3 class="font-headline font-bold text-on-surface mb-3">Recent entries</h3>
                <div id="waterList" class="space-y-2"></div>
            </div>
        </div>

        <!-- ===================== MOOD TAB ===================== -->
        <div id="tab-mood" class="tab-panel grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6 hidden">
            <form id="moodForm" class="bg-surface-container-low border border-[#e7d8c1] rounded-[24px] p-5 space-y-4 h-fit">
                <h3 class="font-headline font-bold text-on-surface">Log a mood</h3>
                <input type="hidden" name="mood" id="moodValue" value="">
                <div class="grid grid-cols-5 gap-2">
                    <button type="button" data-mood="very_sad" class="mood-btn flex flex-col items-center gap-1 p-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined">sentiment_very_dissatisfied</span>
                        <span class="text-[10px] font-medium">Very Sad</span>
                    </button>
                    <button type="button" data-mood="sad" class="mood-btn flex flex-col items-center gap-1 p-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined">sentiment_dissatisfied</span>
                        <span class="text-[10px] font-medium">Sad</span>
                    </button>
                    <button type="button" data-mood="neutral" class="mood-btn flex flex-col items-center gap-1 p-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined">sentiment_neutral</span>
                        <span class="text-[10px] font-medium">Neutral</span>
                    </button>
                    <button type="button" data-mood="happy" class="mood-btn flex flex-col items-center gap-1 p-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined">sentiment_satisfied</span>
                        <span class="text-[10px] font-medium">Happy</span>
                    </button>
                    <button type="button" data-mood="very_happy" class="mood-btn flex flex-col items-center gap-1 p-2 rounded-xl border border-outline-variant/40 hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined">sentiment_very_satisfied</span>
                        <span class="text-[10px] font-medium">Very Happy</span>
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Note (optional)</label>
                    <textarea name="note" rows="2" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-2.5 rounded-full transition-colors">Log entry</button>
            </form>
            <div>
                <h3 class="font-headline font-bold text-on-surface mb-3">Recent entries</h3>
                <div id="moodList" class="space-y-2"></div>
            </div>
        </div>

        <!-- ===================== MEDICINE TAB ===================== -->
        <div id="tab-medicine" class="tab-panel grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-6 hidden">
            <form id="medicineForm" class="bg-surface-container-low border border-[#e7d8c1] rounded-[24px] p-5 space-y-3 h-fit">
                <h3 class="font-headline font-bold text-on-surface">Add a medicine</h3>
                <input type="text" name="medicine_name" required placeholder="Medicine name" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                <input type="text" name="dosage" placeholder="Dosage e.g. 500mg" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                <input type="text" name="frequency" placeholder="Frequency e.g. 2x daily" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-semibold text-on-surface-variant mb-1">Start date</label>
                        <input type="date" name="start_date" class="w-full px-2 py-2 bg-white/60 border border-outline-variant/40 rounded-xl text-xs outline-none focus:border-2 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-on-surface-variant mb-1">End date</label>
                        <input type="date" name="end_date" class="w-full px-2 py-2 bg-white/60 border border-outline-variant/40 rounded-xl text-xs outline-none focus:border-2 focus:border-primary">
                    </div>
                </div>
                <textarea name="notes" rows="2" placeholder="Notes (optional)" class="w-full px-3 py-2.5 bg-white/60 border border-outline-variant/40 rounded-xl text-sm outline-none focus:border-2 focus:border-primary resize-none"></textarea>
                <button type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary font-semibold py-2.5 rounded-full transition-colors">Add medicine</button>
            </form>
            <div>
                <h3 class="font-headline font-bold text-on-surface mb-3">Medicines & reminders</h3>
                <div id="medicineList" class="space-y-3"></div>
            </div>
        </div>

    </div>

<?php endif; ?>

<style>
    .tab-btn { background-color: #fff2e0; color: #3f494a; border-color: #e7d8c1; }
    .tab-btn.active { background-color: #005359; color: #ffffff; border-color: #005359; }
    .mood-btn.active { background-color: #005359; color: #ffffff; border-color: #005359; }
</style>

<script>
(function () {
    const patients = <?php echo json_encode($patients); ?>;
    if (!patients.length) return;

    const patientSelect = document.getElementById('patientSelect');
    const healthContent = document.getElementById('healthContent');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    let activeTab = 'bristol';

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function formatDateTime(s) {
        const d = new Date(s.replace(' ', 'T'));
        return d.toLocaleString([], { day: '2-digit', month: 'short', hour: 'numeric', minute: '2-digit' });
    }

    function currentPatientId() {
        return parseInt(patientSelect.value, 10) || 0;
    }

    // ---------- Tabs ----------
    function setActiveTab(tab) {
        activeTab = tab;
        tabButtons.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        tabPanels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.id !== 'tab-' + tab);
        });
        loadTab(tab);
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { setActiveTab(btn.dataset.tab); });
    });

    patientSelect.addEventListener('change', function () {
        const pid = currentPatientId();
        if (!pid) {
            healthContent.classList.add('hidden');
            return;
        }
        healthContent.classList.remove('hidden');
        setActiveTab(activeTab);
    });

    // ---------- Load / render per tab ----------
    function loadTab(tab) {
        const pid = currentPatientId();
        if (!pid) return;

        fetch('actions/fetch_logs.php?patient_id=' + pid + '&type=' + tab)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) return;
                if (tab === 'bristol') renderBristol(data.logs);
                if (tab === 'water') renderWater(data.logs, data.total_today_ml);
                if (tab === 'mood') renderMood(data.logs);
                if (tab === 'medicine') renderMedicines(data.medicines);
            });
    }

    function emptyState(text) {
        return '<p class="text-sm text-on-surface-variant text-center py-8">' + escapeHtml(text) + '</p>';
    }

    const bristolLabels = {
        1: 'Type 1 — Separate hard lumps',
        2: 'Type 2 — Lumpy sausage',
        3: 'Type 3 — Sausage with cracks',
        4: 'Type 4 — Smooth sausage',
        5: 'Type 5 — Soft blobs',
        6: 'Type 6 — Mushy, ragged edges',
        7: 'Type 7 — Watery, no solid pieces',
    };

    function renderBristol(logs) {
        const list = document.getElementById('bristolList');
        if (!logs.length) { list.innerHTML = emptyState('No entries logged yet.'); return; }
        list.innerHTML = logs.map(function (log) {
            return '<div class="bg-surface-container-low border border-[#e7d8c1] rounded-2xl p-4">' +
                '<div class="flex justify-between items-start gap-2">' +
                '<span class="font-semibold text-sm text-on-surface">' + escapeHtml(bristolLabels[log.scale_type] || ('Type ' + log.scale_type)) + '</span>' +
                '<span class="text-xs text-on-surface-variant shrink-0">' + formatDateTime(log.logged_at) + '</span>' +
                '</div>' +
                (log.notes ? '<p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(log.notes) + '</p>' : '') +
                '</div>';
        }).join('');
    }

    function renderWater(logs, totalToday) {
        document.getElementById('waterTodayTotal').textContent = totalToday + ' ml today';
        const list = document.getElementById('waterList');
        if (!logs.length) { list.innerHTML = emptyState('No entries logged yet.'); return; }
        list.innerHTML = logs.map(function (log) {
            return '<div class="bg-surface-container-low border border-[#e7d8c1] rounded-2xl p-4 flex justify-between items-center">' +
                '<span class="font-semibold text-sm text-on-surface flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-primary">water_drop</span>' + log.amount_ml + ' ml</span>' +
                '<span class="text-xs text-on-surface-variant">' + formatDateTime(log.logged_at) + '</span>' +
                '</div>';
        }).join('');
    }

    const moodMeta = {
        very_sad: { icon: 'sentiment_very_dissatisfied', label: 'Very Sad' },
        sad: { icon: 'sentiment_dissatisfied', label: 'Sad' },
        neutral: { icon: 'sentiment_neutral', label: 'Neutral' },
        happy: { icon: 'sentiment_satisfied', label: 'Happy' },
        very_happy: { icon: 'sentiment_very_satisfied', label: 'Very Happy' },
    };

    function renderMood(logs) {
        const list = document.getElementById('moodList');
        if (!logs.length) { list.innerHTML = emptyState('No entries logged yet.'); return; }
        list.innerHTML = logs.map(function (log) {
            const meta = moodMeta[log.mood] || { icon: 'sentiment_neutral', label: log.mood };
            return '<div class="bg-surface-container-low border border-[#e7d8c1] rounded-2xl p-4">' +
                '<div class="flex justify-between items-start gap-2">' +
                '<span class="font-semibold text-sm text-on-surface flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-primary">' + meta.icon + '</span>' + meta.label + '</span>' +
                '<span class="text-xs text-on-surface-variant shrink-0">' + formatDateTime(log.logged_at) + '</span>' +
                '</div>' +
                (log.note ? '<p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(log.note) + '</p>' : '') +
                '</div>';
        }).join('');
    }

    const dayLabels = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };

    function renderMedicines(medicines) {
        const list = document.getElementById('medicineList');
        if (!medicines.length) { list.innerHTML = emptyState('No medicines added yet.'); return; }

        list.innerHTML = medicines.map(function (med) {
            const reminderRows = (med.reminders || []).map(function (r) {
                const days = String(r.days_of_week).split(',').map(function (d) { return dayLabels[d] || d; }).join(', ');
                return '<div class="flex items-center gap-2 text-xs text-on-surface-variant"><span class="material-symbols-outlined text-[14px]">alarm</span>' +
                    r.reminder_time.slice(0, 5) + ' · ' + days + '</div>';
            }).join('');

            return '<div class="bg-surface-container-low border border-[#e7d8c1] rounded-2xl p-4" data-medicine-id="' + med.id + '">' +
                '<p class="font-semibold text-sm text-on-surface">' + escapeHtml(med.medicine_name) + '</p>' +
                '<p class="text-xs text-on-surface-variant mt-0.5">' +
                    [med.dosage, med.frequency].filter(Boolean).map(escapeHtml).join(' · ') +
                '</p>' +
                (med.notes ? '<p class="text-xs text-on-surface-variant mt-1">' + escapeHtml(med.notes) + '</p>' : '') +
                '<div class="mt-2 space-y-1">' + reminderRows + '</div>' +
                '<button type="button" class="add-reminder-btn text-xs font-semibold text-primary mt-2 flex items-center gap-1">' +
                    '<span class="material-symbols-outlined text-[14px]">add_alarm</span> Add reminder</button>' +
                '<div class="reminder-form hidden mt-3 pt-3 border-t border-outline-variant/20 space-y-2">' +
                    '<input type="time" class="reminder-time w-full px-2 py-1.5 bg-white/60 border border-outline-variant/40 rounded-lg text-xs outline-none focus:border-2 focus:border-primary">' +
                    '<div class="flex flex-wrap gap-1">' +
                        Object.keys(dayLabels).map(function (d) {
                            return '<label class="text-[10px] flex items-center gap-1 border border-outline-variant/40 rounded-lg px-2 py-1 cursor-pointer">' +
                                '<input type="checkbox" class="reminder-day" value="' + d + '"> ' + dayLabels[d] + '</label>';
                        }).join('') +
                    '</div>' +
                    '<button type="button" class="save-reminder-btn w-full bg-primary text-on-primary text-xs font-semibold py-2 rounded-full">Save reminder</button>' +
                '</div>' +
            '</div>';
        }).join('');

        list.querySelectorAll('.add-reminder-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.nextElementSibling.classList.toggle('hidden');
            });
        });

        list.querySelectorAll('.save-reminder-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const card = btn.closest('[data-medicine-id]');
                const medicineId = card.dataset.medicineId;
                const time = card.querySelector('.reminder-time').value;
                const days = Array.from(card.querySelectorAll('.reminder-day:checked')).map(function (cb) { return cb.value; });

                if (!time || !days.length) {
                    alert('Pick a time and at least one day.');
                    return;
                }

                const body = new URLSearchParams();
                body.append('patient_id', currentPatientId());
                body.append('medicine_id', medicineId);
                body.append('reminder_time', time);
                days.forEach(function (d) { body.append('days[]', d); });

                fetch('actions/add_reminder.php', { method: 'POST', body: body })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            loadTab('medicine');
                        } else {
                            alert(data.error || 'Could not save reminder.');
                        }
                    });
            });
        });
    }

    // ---------- Form submissions ----------
    function submitForm(form, type, extraFields, onDone) {
        const pid = currentPatientId();
        if (!pid) return;

        const body = new URLSearchParams(new FormData(form));
        body.set('patient_id', pid);
        body.set('type', type);
        if (extraFields) {
            Object.keys(extraFields).forEach(function (k) { body.set(k, extraFields[k]); });
        }

        fetch('actions/add_log.php', { method: 'POST', body: body })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    if (onDone) onDone();
                    loadTab(type);
                } else {
                    alert(data.error || 'Could not save entry.');
                }
            });
    }

    document.getElementById('bristolForm').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(e.target, 'bristol', null, function () { e.target.reset(); });
    });

    const waterForm = document.getElementById('waterForm');
    waterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(e.target, 'water', null, function () { e.target.reset(); });
    });
    document.querySelectorAll('.water-quick').forEach(function (btn) {
        btn.addEventListener('click', function () {
            submitForm(waterForm, 'water', { amount_ml: btn.dataset.ml });
        });
    });

    const moodForm = document.getElementById('moodForm');
    document.querySelectorAll('.mood-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mood-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('moodValue').value = btn.dataset.mood;
        });
    });
    moodForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!document.getElementById('moodValue').value) {
            alert('Pick a mood first.');
            return;
        }
        submitForm(e.target, 'mood', null, function () {
            e.target.reset();
            document.querySelectorAll('.mood-btn').forEach(function (b) { b.classList.remove('active'); });
        });
    });

    document.getElementById('medicineForm').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(e.target, 'medicine', null, function () { e.target.reset(); });
    });

    // Default active tab styling on load
    setActiveTab('bristol');
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
