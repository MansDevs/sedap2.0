-- ============================================================================
-- SeDaP 2.0 — Clinical Triage & Urgency Classification Schema
-- ============================================================================
-- Section 1: Personal Information & Background (Demographics)
-- Section 2.1: Vital Signs Screening & Basic Laboratory Tests
-- Section 2.2: Medical History & Interview Findings
-- Section 2.3: Urgency Rating & System (Triage Category / Color Code)
-- ============================================================================

CREATE TABLE IF NOT EXISTS triage_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    triage_id VARCHAR(20) NOT NULL UNIQUE COMMENT 'Formatted Triage ID (e.g., TI-001, TI-002)',
    patient_id INT DEFAULT NULL COMMENT 'Optional Foreign Key to patients.id',

    -- SECTION 1: Personal Information & Background (Demographics)
    full_name VARCHAR(150) NOT NULL COMMENT 'Full Name of Patient',
    ic_number VARCHAR(12) NOT NULL COMMENT 'National Identity Card (IC) No. (12 Digits without hyphen)',
    phone_number VARCHAR(20) DEFAULT NULL COMMENT 'Patient Phone Number',
    age INT DEFAULT NULL COMMENT 'Patient Age in Years',
    gender ENUM('Male', 'Female') DEFAULT 'Male' COMMENT 'Patient Gender',
    occupation VARCHAR(100) DEFAULT NULL COMMENT 'Patient Occupation',
    education_level ENUM(
        'No Formal Education',
        'Primary School',
        'Secondary School',
        'Diploma / Degree'
    ) DEFAULT 'Secondary School' COMMENT 'Education Level',

    -- SECTION 2.1: Vital Signs Screening & Basic Laboratory Tests
    temperature DECIMAL(4,1) DEFAULT NULL COMMENT 'Body Temperature (°C)',
    blood_pressure VARCHAR(20) DEFAULT NULL COMMENT 'Blood Pressure (BP) e.g. 120/80 mmHg',
    glucose_level DECIMAL(5,2) DEFAULT NULL COMMENT 'Blood Glucose Level (mmol/L)',
    lipid_profile DECIMAL(5,2) DEFAULT NULL COMMENT 'Lipid Profile Reading (mmol/L)',
    
    -- Main / Acute Symptoms (Required: Checklist of Diarrhea, Vomiting / Nausea, Fever, Stomach Ache, Dizziness)
    symptoms VARCHAR(500) NOT NULL COMMENT 'Main / Acute Symptoms checklist as JSON or comma-separated text',

    -- SECTION 2.2: Medical History & Interview Findings
    medical_history TEXT DEFAULT NULL COMMENT 'Pre-existing Medical Conditions (Diabetes, Hypertension, Gastritis, Drug Allergies)',
    interview_notes TEXT DEFAULT NULL COMMENT 'Volunteer Interview Notes (cause, symptom onset time, additional complaints)',

    -- SECTION 2.3: Urgency Rating & System (Triage Category / Color Code)
    triage_level ENUM(
        'green',     -- Green: Non-urgent / stable / general screening
        'yellow',    -- Yellow: Requires treatment / moderate symptoms
        'red'        -- Red: Emergency / severe dehydration
    ) NOT NULL DEFAULT 'green' COMMENT 'Triage Urgency Category (Single Selection)',

    chief_complaint VARCHAR(255) DEFAULT NULL COMMENT 'Primary complaint summary',
    pulse_rate INT DEFAULT NULL COMMENT 'Heart Rate (bpm)',
    respiratory_rate INT DEFAULT NULL COMMENT 'Respiratory Rate (breaths/min)',
    spo2 INT DEFAULT NULL COMMENT 'Oxygen Saturation (%)',
    consciousness_level VARCHAR(50) DEFAULT NULL COMMENT 'AVPU scale',

    status ENUM('waiting', 'in_treatment', 'referred', 'discharged') NOT NULL DEFAULT 'waiting',
    triaged_by INT NOT NULL DEFAULT 1 COMMENT 'User ID of staff/volunteer who conducted the triage',
    notes TEXT DEFAULT NULL COMMENT 'Additional medical observations',

    -- Registration Date & Time Stamp (Automatically recorded)
    triaged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Performance Indexes
    INDEX idx_triage_custom_id (triage_id),
    INDEX idx_triage_ic (ic_number),
    INDEX idx_triage_patient (patient_id),
    INDEX idx_triage_level (triage_level),
    INDEX idx_triage_date (triaged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
