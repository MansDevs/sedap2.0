-- ============================================================================
-- SeDaP 2.0 — Patient Registration & Clinical Profile Schema
-- ============================================================================
-- File: database/patient_schema.sql
-- Description: Individual patient demographic data, emergency contacts,
--              clinical background, and family ties.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: patients
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique SeDaP ID (e.g., PT-00001)',
    full_name VARCHAR(255) NOT NULL COMMENT 'Patient Full Legal Name',
    ic_number VARCHAR(20) DEFAULT NULL UNIQUE COMMENT 'National Identity Card (IC) No. or Passport',
    date_of_birth DATE DEFAULT NULL,
    gender ENUM('male', 'female') DEFAULT NULL,
    gender_identity VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status ENUM('registered', 'in_triage', 'admitted', 'discharged', 'referred') NOT NULL DEFAULT 'registered',
    registered_by INT DEFAULT NULL COMMENT 'User ID of staff/volunteer who registered the patient',

    -- Emergency Contact Information
    emergency_contact_name VARCHAR(255) DEFAULT NULL,
    emergency_contact_relationship VARCHAR(100) DEFAULT NULL,
    emergency_contact_phone VARCHAR(50) DEFAULT NULL,
    emergency_contact_alt_phone VARCHAR(50) DEFAULT NULL,

    -- Insurance & Billing (Optional)
    insurance_payer VARCHAR(255) DEFAULT NULL,
    insurance_policy_id VARCHAR(100) DEFAULT NULL,
    insurance_group_number VARCHAR(100) DEFAULT NULL,
    insurance_subscriber_details TEXT DEFAULT NULL,
    insurance_coverage_type VARCHAR(50) DEFAULT 'Primary',
    billing_address TEXT DEFAULT NULL,

    -- Clinical Baseline & Medical History
    clinical_reason_for_visit TEXT DEFAULT NULL,
    clinical_active_medications TEXT DEFAULT NULL,
    clinical_allergies TEXT DEFAULT NULL,
    clinical_surgical_history TEXT DEFAULT NULL,
    clinical_family_history TEXT DEFAULT NULL,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_patient_status (status),
    INDEX idx_patient_registered_by (registered_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- Table: patient_family_members
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patient_family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL COMMENT 'Foreign key to patients.id',
    full_name VARCHAR(255) NOT NULL,
    relationship VARCHAR(50) DEFAULT NULL COMMENT 'e.g., Spouse, Child, Parent, Sibling, Relative',
    ic_number VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    is_emergency_contact TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Indexes & Foreign Keys
    INDEX idx_pfm_patient_id (patient_id),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
