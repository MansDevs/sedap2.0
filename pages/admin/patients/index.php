<?php
$adminBase = '../';
$activeNav = 'patients';
$pageTitle = 'Patient Registration';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase);

$msg = '';
$err = '';

// Handle Patient Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    // 1. Demographics & Identification
    $fullName        = trim($_POST['full_name'] ?? '');
    $dob             = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender          = $_POST['gender'] ?? 'male';
    $genderIdentity  = trim($_POST['gender_identity'] ?? '');
    $icNumber        = trim($_POST['ic_number'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $address         = trim($_POST['address'] ?? '');

    // 2. Emergency Contacts
    $emName          = trim($_POST['emergency_contact_name'] ?? '');
    $emRelationship  = trim($_POST['emergency_contact_relationship'] ?? '');
    $emPhone         = trim($_POST['emergency_contact_phone'] ?? '');
    $emAltPhone      = trim($_POST['emergency_contact_alt_phone'] ?? '');

    // 3. Insurance & Billing
    $insPayer        = trim($_POST['insurance_payer'] ?? '');
    $insPolicyId     = trim($_POST['insurance_policy_id'] ?? '');
    $insGroupNum     = trim($_POST['insurance_group_number'] ?? '');
    $insSubscriber   = trim($_POST['insurance_subscriber_details'] ?? '');
    $insCoverageType = trim($_POST['insurance_coverage_type'] ?? 'Primary');
    $billingAddress  = trim($_POST['billing_address'] ?? '');

    // 4. Initial Clinical Screening
    $reasonForVisit  = trim($_POST['clinical_reason_for_visit'] ?? '');
    $activeMeds      = trim($_POST['clinical_active_medications'] ?? '');
    $allergies       = trim($_POST['clinical_allergies'] ?? '');
    $surgicalHistory = trim($_POST['clinical_surgical_history'] ?? '');
    $familyHistory   = trim($_POST['clinical_family_history'] ?? '');

    if (empty($fullName)) {
        $err = 'Please provide the patient\'s full legal name.';
    } else {
        try {
            $maxId = (int)$pdo->query("SELECT MAX(id) FROM patients")->fetchColumn();
            $regNumber = 'PT-' . str_pad((string)($maxId + 1), 6, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO patients (
                registration_number, full_name, date_of_birth, gender, gender_identity, ic_number, phone, email, address,
                emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, emergency_contact_alt_phone,
                insurance_payer, insurance_policy_id, insurance_group_number, insurance_subscriber_details, insurance_coverage_type, billing_address,
                clinical_reason_for_visit, clinical_active_medications, clinical_allergies, clinical_surgical_history, clinical_family_history,
                registered_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, NOW()
            )");

            $stmt->execute([
                $regNumber, $fullName, $dob, $gender, $genderIdentity, $icNumber, $phone, $email, $address,
                $emName, $emRelationship, $emPhone, $emAltPhone,
                $insPayer, $insPolicyId, $insGroupNum, $insSubscriber, $insCoverageType, $billingAddress,
                $reasonForVisit, $activeMeds, $allergies, $surgicalHistory, $familyHistory,
                $currentUser['id']
            ]);

            $msg = "Patient <strong>" . htmlspecialchars($fullName) . "</strong> ($regNumber) has been registered successfully.";
        } catch (PDOException $e) {
            $err = 'Registration error: ' . $e->getMessage();
        }
    }
}

$patients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
  /* 24px rounded corners for inputs */
  .rounded-24 {
    border-radius: 24px !important;
  }
  .input-24 {
    border-radius: 24px !important;
    padding: 0.65rem 1.25rem !important;
  }
  .textarea-24 {
    border-radius: 20px !important;
    padding: 0.75rem 1.25rem !important;
  }
</style>

<div class="space-y-6 pb-12">
    <!-- Header banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-[32px] text-primary">person_add</span>
                <span>Patient Registration</span>
            </h1>
            <p class="text-on-surface-variant text-sm mt-1">Multi-category structured patient registration & clinical intake</p>
        </div>
        <button onclick="openRegistrationModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-5 py-2.5 rounded-[24px] shadow-sm transition-all duration-200 hover:shadow">
            <span class="material-symbols-outlined text-[20px]">add_circle</span>
            <span>New Patient Registration</span>
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <div><?php echo $msg; ?></div>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <div><?php echo htmlspecialchars($err); ?></div>
        </div>
    <?php endif; ?>

    <!-- Patients Directory Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] overflow-hidden shadow-sm">
        <div class="p-5 sm:p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">groups</span>
                <h2 class="font-headline font-bold text-lg text-on-surface">Registered Patients (<?php echo count($patients); ?>)</h2>
            </div>
            <div class="w-full sm:w-72">
                <input type="text" id="patientSearchInput" placeholder="Search name, IC, phone..."
                       class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm px-4 py-2 input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="patientsTable">
                <thead class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Reg</th>
                        <th class="py-3.5 px-4">Patient Name</th>
                        <th class="py-3.5 px-4">IC / ID</th>
                        <th class="py-3.5 px-4">Sex / Gender</th>
                        <th class="py-3.5 px-4">Phone</th>
                        <th class="py-3.5 px-4">Emergency Contact</th>
                        <th class="py-3.5 px-4">Insurance</th>
                        <th class="py-3.5 px-4">Date Registered</th>
                        <th class="py-3.5 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface">
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="9" class="py-10 text-center text-on-surface-variant">
                                No registered patients found. Click "New Patient Registration" above to add one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr class="hover:bg-surface-container/50 transition-colors patient-table-row">
                                <td class="py-4 px-6 font-semibold text-primary"><?php echo htmlspecialchars($p['registration_number'] ?? 'PT-' . $p['id']); ?></td>
                                <td class="py-4 px-4 font-medium">
                                    <?php echo htmlspecialchars($p['full_name']); ?>
                                    <?php if (!empty($p['clinical_reason_for_visit'])): ?>
                                        <div class="text-xs text-on-surface-variant truncate max-w-xs"><?php echo htmlspecialchars($p['clinical_reason_for_visit']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-on-surface-variant"><?php echo htmlspecialchars($p['ic_number'] ?? '—'); ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-surface-container px-2.5 py-1 rounded-full text-xs font-semibold">
                                        <?php echo ucfirst(htmlspecialchars($p['gender'] ?? '—')); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4"><?php echo htmlspecialchars($p['phone'] ?? '—'); ?></td>
                                <td class="py-4 px-4 text-xs">
                                    <?php echo !empty($p['emergency_contact_name']) ? htmlspecialchars($p['emergency_contact_name']) . ' (' . htmlspecialchars($p['emergency_contact_phone'] ?? '') . ')' : '<span class="text-on-surface-variant/60">—</span>'; ?>
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    <?php if (!empty($p['insurance_payer'])): ?>
                                        <span class="bg-primary/10 text-primary px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($p['insurance_payer']); ?></span>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant/60">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-xs text-on-surface-variant"><?php echo !empty($p['created_at']) ? date('d M Y, H:i', strtotime($p['created_at'])) : '—'; ?></td>
                                <td class="py-4 px-6 text-center">
                                    <button type="button" onclick='viewPatientDetails(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)'
                                            class="inline-flex items-center gap-1 text-primary hover:text-primary/80 font-semibold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-full transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        <span>View</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: MULTI-STEP CATEGORY PATIENT REGISTRATION -->
<!-- ============================================================= -->
<div id="registrationModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col h-[90vh] max-h-[820px] text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">person_add</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface">Patient Registration Form</h3>
                    <p class="text-xs text-on-surface-variant">Step-by-step category data collection</p>
                </div>
            </div>
            <button type="button" onclick="closeRegistrationModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Stepper Navigation -->
        <div class="px-6 pt-5 pb-3 border-b border-outline-variant/10 bg-surface-container-lowest shrink-0">
            <div class="grid grid-cols-4 gap-2 text-center relative">
                <!-- Step 1 Button -->
                <button type="button" id="stepBtn1" onclick="jumpToStep(1)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle1" class="w-10 h-10 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-sm shadow-md transition-all">1</div>
                    <span id="stepLabel1" class="text-xs font-bold text-primary mt-1.5 line-clamp-1">1. Demographics</span>
                </button>
                <!-- Step 2 Button -->
                <button type="button" id="stepBtn2" onclick="jumpToStep(2)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle2" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">2</div>
                    <span id="stepLabel2" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">2. Emergency Contacts</span>
                </button>
                <!-- Step 3 Button -->
                <button type="button" id="stepBtn3" onclick="jumpToStep(3)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle3" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">3</div>
                    <span id="stepLabel3" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">3. Insurance & Billing</span>
                </button>
                <!-- Step 4 Button -->
                <button type="button" id="stepBtn4" onclick="jumpToStep(4)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle4" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">4</div>
                    <span id="stepLabel4" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">4. Clinical Screening</span>
                </button>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
                <div id="stepProgressBar" class="bg-primary h-full transition-all duration-300" style="width: 25%;"></div>
            </div>
        </div>

        <!-- Form Body -->
        <form method="POST" id="patientMultiStepForm" class="overflow-y-auto p-6 space-y-6 flex-1">
            <input type="hidden" name="action" value="add">

            <!-- ============================================== -->
            <!-- CATEGORY 1: Demographics & Identification -->
            <!-- ============================================== -->
            <div id="categoryStep1" class="space-y-4">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">badge</span>
                        <span>Category 1: Demographics & Identification</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-1">
                        <strong>Primary Purpose:</strong> Prevents duplicate records, ensures accurate patient verification, and enables direct communication.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Full Legal Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="full_name" id="c1_full_name" required placeholder="e.g. Johnathan Alexander Doe"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">National ID / SSN / Passport <span class="text-rose-500">*</span></label>
                        <input type="text" name="ic_number" id="c1_ic" required placeholder="e.g. 920514-10-5544"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Date of Birth <span class="text-rose-500">*</span></label>
                        <input type="date" name="date_of_birth" id="c1_dob" required
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Biological Sex</label>
                        <select name="gender" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Gender Identity</label>
                        <input type="text" name="gender_identity" placeholder="Optional (e.g. Cisgender, Non-binary)"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Phone Number <span class="text-rose-500">*</span></label>
                        <input type="tel" name="phone" id="c1_phone" required placeholder="e.g. +60 12-345 6789"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Primary Email</label>
                        <input type="email" name="email" placeholder="patient@example.com"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Residential Address</label>
                        <textarea name="address" id="c1_address" rows="2" placeholder="Street address, unit/apt, city, state, postal code"
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- CATEGORY 2: Emergency Contacts -->
            <!-- ============================================== -->
            <div id="categoryStep2" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">emergency</span>
                        <span>Category 2: Emergency Contacts</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-1">
                        <strong>Primary Purpose:</strong> Crucial for urgent medical situations or clinical updates when the patient is unable to communicate.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Contact Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="emergency_contact_name" id="c2_name" placeholder="Full name of emergency contact"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Relationship to Patient</label>
                        <select name="emergency_contact_relationship" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Spouse">Spouse</option>
                            <option value="Parent">Parent</option>
                            <option value="Child">Child</option>
                            <option value="Sibling">Sibling</option>
                            <option value="Legal Guardian">Legal Guardian</option>
                            <option value="Other">Other Relative / Friend</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Primary Phone Number <span class="text-rose-500">*</span></label>
                        <input type="tel" name="emergency_contact_phone" id="c2_phone" placeholder="e.g. +60 13-987 6543"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Alternate Phone</label>
                        <input type="tel" name="emergency_contact_alt_phone" placeholder="Home / Work / Secondary Phone"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- CATEGORY 3: Insurance & Billing -->
            <!-- ============================================== -->
            <div id="categoryStep3" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                        <span>Category 3: Insurance & Billing</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-1">
                        <strong>Primary Purpose:</strong> Validates coverage active status, facilitates claim submission, and clarifies financial responsibility.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Payer / Carrier Name</label>
                        <input type="text" name="insurance_payer" placeholder="e.g. MySalam, AIA, Great Eastern, MOH, Self-Pay"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Primary vs. Secondary Coverage</label>
                        <select name="insurance_coverage_type" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Primary">Primary Coverage</option>
                            <option value="Secondary">Secondary Coverage</option>
                            <option value="Self-Pay">Self-Pay / Cash</option>
                            <option value="Government Subsidy">Government Subsidy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Policy / Member ID</label>
                        <input type="text" name="insurance_policy_id" placeholder="e.g. POL-1029384"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Group Number</label>
                        <input type="text" name="insurance_group_number" placeholder="e.g. GRP-99201"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Subscriber / Policyholder Details</label>
                        <input type="text" name="insurance_subscriber_details" placeholder="Policyholder name, employer, date of birth (if not patient)"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-on-surface">Billing Address</label>
                            <button type="button" onclick="copyAddress()" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                <span>Same as residential address</span>
                            </button>
                        </div>
                        <textarea name="billing_address" id="c3_billing_address" rows="2" placeholder="Billing address for claims and invoices"
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- CATEGORY 4: Initial Clinical Screening -->
            <!-- ============================================== -->
            <div id="categoryStep4" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">medical_services</span>
                        <span>Category 4: Initial Clinical Screening</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-1">
                        <strong>Primary Purpose:</strong> Ensures basic patient safety prior to consultation (e.g., preventing adverse drug interactions).
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Primary Reason for Visit / Chief Complaint <span class="text-rose-500">*</span></label>
                        <textarea name="clinical_reason_for_visit" id="c4_reason" rows="2" required placeholder="Primary symptoms, duration, acute complaints"
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Active Medications</label>
                            <textarea name="clinical_active_medications" rows="2" placeholder="Current prescription drugs, over-the-counter meds, supplements"
                                      class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Known Drug / Food Allergies</label>
                            <textarea name="clinical_allergies" rows="2" placeholder="e.g. Penicillin, NSAIDs, Shellfish, Peanuts, Latex, None"
                                      class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Past Surgical History</label>
                            <textarea name="clinical_surgical_history" rows="2" placeholder="Previous surgeries, hospitalizations, approximate years"
                                      class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Family Medical History</label>
                            <textarea name="clinical_family_history" rows="2" placeholder="Cardiovascular disease, diabetes, hypertension, asthma, cancer"
                                      class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal Footer Navigation -->
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex items-center justify-between shrink-0">
            <button type="button" id="prevBtn" onclick="prevStep()" class="inline-flex items-center gap-1.5 text-on-surface-variant hover:text-on-surface font-semibold text-sm px-5 py-2.5 rounded-[24px] border border-outline-variant/40 hover:bg-surface-container transition-all hidden">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Previous Category</span>
            </button>
            <button type="button" id="cancelBtn" onclick="closeRegistrationModal()" class="text-on-surface-variant hover:text-on-surface font-semibold text-sm px-5 py-2.5 rounded-[24px] hover:bg-surface-container transition-all">
                Cancel
            </button>

            <div class="flex items-center gap-3">
                <button type="button" id="nextBtn" onclick="nextStep()" class="inline-flex items-center gap-1.5 bg-primary hover:bg-primary/90 text-on-primary font-semibold text-sm px-6 py-2.5 rounded-[24px] shadow-sm transition-all hover:shadow">
                    <span>Next: Emergency Contacts</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
                <button type="submit" form="patientMultiStepForm" id="submitBtn" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-6 py-2.5 rounded-[24px] shadow-sm transition-all hover:shadow hidden">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>Submit & Register Patient</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: VIEW PATIENT DETAILS -->
<!-- ============================================================= -->
<div id="patientDetailsModal" class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-black/60 backdrop-blur-sm">
    <div class="min-h-full flex items-center justify-center p-3 sm:p-6 text-center">
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col my-auto text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <div class="px-6 py-5 bg-primary text-on-primary flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[28px]">badge</span>
                <div>
                    <h3 class="font-headline font-bold text-lg" id="detailPatientName">Patient Profile</h3>
                    <p class="text-xs text-on-primary/80" id="detailPatientReg"></p>
                </div>
            </div>
            <button type="button" onclick="closeDetailsModal()" class="text-on-primary/80 hover:text-on-primary p-1.5 rounded-full hover:bg-white/10 transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto space-y-5" id="detailModalContent"></div>
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex justify-end shrink-0">
            <button type="button" onclick="closeDetailsModal()" class="bg-surface-container text-on-surface hover:bg-surface-container-high font-semibold text-sm px-6 py-2.5 rounded-[24px] transition-colors">
                Close
            </button>
        </div>
        </div>
    </div>
</div>

<script>
let currentCategoryStep = 1;
const totalCategorySteps = 4;
const stepNames = [
    "Next: Emergency Contacts",
    "Next: Insurance & Billing",
    "Next: Clinical Screening",
    "Submit & Register Patient"
];

function updateStepUI() {
    for (let i = 1; i <= totalCategorySteps; i++) {
        const pane = document.getElementById('categoryStep' + i);
        const circle = document.getElementById('stepCircle' + i);
        const label = document.getElementById('stepLabel' + i);

        if (i === currentCategoryStep) {
            pane.classList.remove('hidden');
            circle.className = "w-10 h-10 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-sm shadow-md transition-all";
            label.className = "text-xs font-bold text-primary mt-1.5 line-clamp-1";
        } else {
            pane.classList.add('hidden');
            if (i < currentCategoryStep) {
                circle.className = "w-10 h-10 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-sm transition-all";
                circle.innerHTML = `<span class="material-symbols-outlined text-[18px]">check</span>`;
                label.className = "text-xs font-medium text-emerald-700 mt-1.5 line-clamp-1";
            } else {
                circle.className = "w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all";
                circle.innerHTML = i;
                label.className = "text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1";
            }
        }
    }

    const progressPercent = (currentCategoryStep / totalCategorySteps) * 100;
    document.getElementById('stepProgressBar').style.width = progressPercent + '%';

    const prevBtn = document.getElementById('prevBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    if (currentCategoryStep > 1) {
        prevBtn.classList.remove('hidden');
        cancelBtn.classList.add('hidden');
    } else {
        prevBtn.classList.add('hidden');
        cancelBtn.classList.remove('hidden');
    }

    if (currentCategoryStep === totalCategorySteps) {
        nextBtn.classList.add('hidden');
        submitBtn.classList.remove('hidden');
    } else {
        nextBtn.classList.remove('hidden');
        submitBtn.classList.add('hidden');
        nextBtn.querySelector('span:first-child').innerText = stepNames[currentCategoryStep - 1];
    }
}

function validateCategory(step) {
    if (step === 1) {
        const name = document.getElementById('c1_full_name').value.trim();
        const ic = document.getElementById('c1_ic').value.trim();
        const dob = document.getElementById('c1_dob').value;
        const phone = document.getElementById('c1_phone').value.trim();
        if (!name || !ic || !dob || !phone) {
            alert('Please complete the required fields in Demographics (Full Name, ID/SSN, Date of Birth, and Phone Number).');
            return false;
        }
    }
    return true;
}

function nextStep() {
    if (!validateCategory(currentCategoryStep)) return;
    if (currentCategoryStep < totalCategorySteps) {
        currentCategoryStep++;
        updateStepUI();
    }
}

function prevStep() {
    if (currentCategoryStep > 1) {
        currentCategoryStep--;
        updateStepUI();
    }
}

function jumpToStep(step) {
    if (step > currentCategoryStep && !validateCategory(currentCategoryStep)) return;
    currentCategoryStep = step;
    updateStepUI();
}

function openRegistrationModal() {
    document.getElementById('registrationModal').classList.remove('hidden');
    currentCategoryStep = 1;
    updateStepUI();
}

function closeRegistrationModal() {
    document.getElementById('registrationModal').classList.add('hidden');
}

function copyAddress() {
    const addr = document.getElementById('c1_address').value;
    document.getElementById('c3_billing_address').value = addr;
}

// Search Filter
document.getElementById('patientSearchInput')?.addEventListener('keyup', function(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('.patient-table-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
    });
});

// View Patient Profile Details
function viewPatientDetails(p) {
    document.getElementById('detailPatientName').innerText = p.full_name || 'Patient Profile';
    document.getElementById('detailPatientReg').innerText = (p.registration_number || 'PT-' + p.id) + ' • Registered: ' + (p.created_at || '—');

    const html = `
        <div class="space-y-4">
            <!-- 1. Demographics -->
            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20">
                <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                    <span>1. Demographics & Identification</span>
                </h4>
                <div class="grid grid-cols-2 gap-2 text-xs text-on-surface">
                    <div><span class="text-on-surface-variant font-medium">Full Name:</span> <strong>${p.full_name || '—'}</strong></div>
                    <div><span class="text-on-surface-variant font-medium">National ID / SSN:</span> <strong>${p.ic_number || '—'}</strong></div>
                    <div><span class="text-on-surface-variant font-medium">Date of Birth:</span> ${p.date_of_birth || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Sex / Gender:</span> ${p.gender || '—'} ${p.gender_identity ? '(' + p.gender_identity + ')' : ''}</div>
                    <div><span class="text-on-surface-variant font-medium">Phone:</span> ${p.phone || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Email:</span> ${p.email || '—'}</div>
                    <div class="col-span-2 mt-1"><span class="text-on-surface-variant font-medium">Residential Address:</span> ${p.address || '—'}</div>
                </div>
            </div>

            <!-- 2. Emergency Contacts -->
            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20">
                <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[18px]">emergency</span>
                    <span>2. Emergency Contacts</span>
                </h4>
                <div class="grid grid-cols-2 gap-2 text-xs text-on-surface">
                    <div><span class="text-on-surface-variant font-medium">Contact Name:</span> <strong>${p.emergency_contact_name || '—'}</strong></div>
                    <div><span class="text-on-surface-variant font-medium">Relationship:</span> ${p.emergency_contact_relationship || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Primary Phone:</span> ${p.emergency_contact_phone || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Alternate Phone:</span> ${p.emergency_contact_alt_phone || '—'}</div>
                </div>
            </div>

            <!-- 3. Insurance & Billing -->
            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20">
                <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>3. Insurance & Billing</span>
                </h4>
                <div class="grid grid-cols-2 gap-2 text-xs text-on-surface">
                    <div><span class="text-on-surface-variant font-medium">Payer / Carrier:</span> <strong>${p.insurance_payer || '—'}</strong></div>
                    <div><span class="text-on-surface-variant font-medium">Coverage Type:</span> ${p.insurance_coverage_type || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Policy / Member ID:</span> ${p.insurance_policy_id || '—'}</div>
                    <div><span class="text-on-surface-variant font-medium">Group Number:</span> ${p.insurance_group_number || '—'}</div>
                    <div class="col-span-2"><span class="text-on-surface-variant font-medium">Subscriber Details:</span> ${p.insurance_subscriber_details || '—'}</div>
                    <div class="col-span-2"><span class="text-on-surface-variant font-medium">Billing Address:</span> ${p.billing_address || '—'}</div>
                </div>
            </div>

            <!-- 4. Initial Clinical Screening -->
            <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/20">
                <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-[18px]">medical_services</span>
                    <span>4. Initial Clinical Screening</span>
                </h4>
                <div class="space-y-2 text-xs text-on-surface">
                    <div><span class="text-on-surface-variant font-medium">Primary Reason for Visit:</span> <strong class="text-rose-600">${p.clinical_reason_for_visit || '—'}</strong></div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div><span class="text-on-surface-variant font-medium">Active Medications:</span><br>${p.clinical_active_medications || 'None'}</div>
                        <div><span class="text-on-surface-variant font-medium">Known Allergies:</span><br>${p.clinical_allergies || 'None'}</div>
                        <div><span class="text-on-surface-variant font-medium">Past Surgical History:</span><br>${p.clinical_surgical_history || 'None'}</div>
                        <div><span class="text-on-surface-variant font-medium">Family Medical History:</span><br>${p.clinical_family_history || 'None'}</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('detailModalContent').innerHTML = html;
    document.getElementById('patientDetailsModal').classList.remove('hidden');
}

function closeDetailsModal() {
    document.getElementById('patientDetailsModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
