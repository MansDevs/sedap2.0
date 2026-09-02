-- 1. Core Volunteers Table (Shared information)
CREATE TABLE IF NOT EXISTS volunteers (
    volunteer_id VARCHAR(20) PRIMARY KEY, -- e.g., 'VOL-2026-001'
    full_name VARCHAR(150) NOT NULL,
    badge_name VARCHAR(50) NOT NULL,
    id_or_passport VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(25) NOT NULL,
    email VARCHAR(100) NOT NULL,
    t_shirt_size VARCHAR(10) CHECK (t_shirt_size IN ('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL')),
    dietary_preference VARCHAR(50) DEFAULT 'None',
    has_own_transport BOOLEAN DEFAULT FALSE,
    languages_spoken TEXT NOT NULL, -- e.g., 'English, Malay, Mandarin'
    emergency_contact_name VARCHAR(150) NOT NULL,
    emergency_contact_phone VARCHAR(25) NOT NULL,
    emergency_contact_relation VARCHAR(50) NOT NULL,
    track VARCHAR(20) NOT NULL CHECK (track IN ('clinical', 'non_clinical')),
    vetting_status VARCHAR(20) DEFAULT 'pending' CHECK (vetting_status IN ('pending', 'approved', 'rejected')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Clinical Profile (1-to-1 extension for Track A)
CREATE TABLE IF NOT EXISTS clinical_profiles (
    volunteer_id VARCHAR(20) PRIMARY KEY,
    cadre VARCHAR(50) NOT NULL, -- 'Doctor', 'Nurse', 'Pharmacist', 'Medical Student', etc.
    council_reg_number VARCHAR(50), -- Council/Board Registration ID
    apc_expiry_date DATE,           -- Annual Practicing Certificate expiry
    apc_document_url VARCHAR(255),  -- Path to uploaded credential file
    specialty VARCHAR(100),         -- e.g., 'Pediatrics', 'Wound Care'
    is_life_support_certified BOOLEAN DEFAULT FALSE,
    life_support_expiry DATE,
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(volunteer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Non-Clinical Profile (1-to-1 extension for Track B)
CREATE TABLE IF NOT EXISTS non_clinical_profiles (
    volunteer_id VARCHAR(20) PRIMARY KEY,
    occupation VARCHAR(100),        -- e.g., 'IT Support', 'Accountant', 'Student'
    key_skills TEXT,                -- e.g., 'Crowd control, First aid, Photography'
    physical_limitations TEXT,      -- e.g., 'Needs seated role', 'Cannot lift heavy items'
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(volunteer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Stations / Operational Areas Lookup
CREATE TABLE IF NOT EXISTS stations (
    station_id INT AUTO_INCREMENT PRIMARY KEY,
    station_name VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'Triage', 'Pharmacy', 'Registration', 'Logistics'
    track_required VARCHAR(20) DEFAULT 'any' CHECK (track_required IN ('clinical', 'non_clinical', 'any'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Shifts Lookup
CREATE TABLE IF NOT EXISTS shifts (
    shift_id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(50) NOT NULL, -- e.g., 'Day 1 - Morning', 'Day 1 - Full Day'
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Volunteer Deployments (Roster Assignments)
CREATE TABLE IF NOT EXISTS volunteer_deployments (
    deployment_id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id VARCHAR(20) NOT NULL,
    shift_id INT NOT NULL,
    station_id INT NOT NULL,
    attendance_status VARCHAR(20) DEFAULT 'scheduled' CHECK (attendance_status IN ('scheduled', 'checked_in', 'absent', 'excused')),
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(volunteer_id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE RESTRICT,
    FOREIGN KEY (station_id) REFERENCES stations(station_id) ON DELETE RESTRICT,
    UNIQUE (volunteer_id, shift_id) -- Prevents assigning the same volunteer to overlapping shifts
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed Default Stations if empty
INSERT IGNORE INTO stations (station_id, station_name, track_required) VALUES
(1, 'Triage & Vital Signs', 'clinical'),
(2, 'Doctor Consultation', 'clinical'),
(3, 'Pharmacy & Medication Dispensing', 'clinical'),
(4, 'Minor Procedures & Wound Care', 'clinical'),
(5, 'Basic Health Screening', 'clinical'),
(6, 'Registration & Ticketing Desk', 'non_clinical'),
(7, 'Crowd Management & Queue Control', 'non_clinical'),
(8, 'Runner & Logistics Support', 'non_clinical'),
(9, 'Patient Escort & Wheelchair Assistance', 'non_clinical'),
(10, 'Children Activity Corner', 'non_clinical'),
(11, 'Food & Refreshment Station', 'non_clinical');

-- Seed Sample Default Shifts if empty
INSERT IGNORE INTO shifts (shift_id, shift_name, shift_date, start_time, end_time) VALUES
(1, 'Morning Shift (AM)', CURDATE(), '08:00:00', '13:00:00'),
(2, 'Afternoon Shift (PM)', CURDATE(), '13:00:00', '18:00:00'),
(3, 'Full Day Shift', CURDATE(), '08:00:00', '18:00:00');

-- 7. Ready-to-Export View (One Master Roster)
CREATE OR REPLACE VIEW view_master_roster_export AS
SELECT 
    v.volunteer_id,
    v.track,
    v.full_name,
    v.badge_name,
    v.phone_number,
    v.languages_spoken,
    COALESCE(c.cadre, n.occupation) AS role_or_profession,
    c.council_reg_number,
    c.apc_expiry_date,
    st.station_name AS assigned_station,
    sh.shift_name,
    sh.shift_date,
    v.t_shirt_size,
    v.dietary_preference,
    CONCAT(v.emergency_contact_name, ' (', v.emergency_contact_relation, ') - ', v.emergency_contact_phone) AS emergency_contact,
    v.vetting_status
FROM volunteers v
LEFT JOIN clinical_profiles c ON v.volunteer_id = c.volunteer_id
LEFT JOIN non_clinical_profiles n ON v.volunteer_id = n.volunteer_id
LEFT JOIN volunteer_deployments vd ON v.volunteer_id = vd.volunteer_id
LEFT JOIN stations st ON vd.station_id = st.station_id
LEFT JOIN shifts sh ON vd.shift_id = sh.shift_id;
