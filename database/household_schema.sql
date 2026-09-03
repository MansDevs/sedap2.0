-- Household & Family Registration Schema for SeDaP 2.0
-- REVIEWED & CORRECTED VERSION

CREATE TABLE IF NOT EXISTS Household (
    household_id INT AUTO_INCREMENT PRIMARY KEY,
    street_address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100),
    state VARCHAR(100),
    house_type VARCHAR(50),      -- Apartment, Landed, Rental, Owned, PPS, etc.
    total_residents INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS HeadOfHousehold (
    ic_number VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(100),
    household_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES Household(household_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS Member (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(30) NOT NULL UNIQUE,
    date_of_birth DATE,
    age INT,
    gender ENUM('Male', 'Female') NOT NULL,
    relationship_to_head ENUM('Head', 'Spouse', 'Child', 'Mother', 'Father', 'Grandfather', 'Grandmother', 'Relative', 'Others') NOT NULL,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single',
    citizenship_status VARCHAR(30),
    education_level ENUM('No Formal Education', 'Primary', 'Secondary', 'Tertiary', 'Post-Graduate'),
    employment_status ENUM('Employed', 'Self-Employed', 'Unemployed', 'Student', 'Retired', 'Homemaker', 'Informal'),
    
    -- Vulnerable/High-Risk Groups (can select multiple)
    vulnerable_infant_under5 BOOLEAN DEFAULT FALSE,
    vulnerable_senior_60plus BOOLEAN DEFAULT FALSE,
    vulnerable_pregnant_mother BOOLEAN DEFAULT FALSE,
    vulnerable_disability_oku BOOLEAN DEFAULT FALSE,
    vulnerable_bedridden BOOLEAN DEFAULT FALSE,
    
    -- Chronic Conditions (can select multiple)
    chronic_diabetes BOOLEAN DEFAULT FALSE,
    chronic_hypertension BOOLEAN DEFAULT FALSE,
    chronic_kidney_disease BOOLEAN DEFAULT FALSE,
    chronic_gastric_intestinal BOOLEAN DEFAULT FALSE,
    chronic_other VARCHAR(255),
    
    -- Allergies
    drug_allergies VARCHAR(255),
    food_allergies VARCHAR(255),
    
    -- Health Screening (Past 3 days)
    has_diarrhea BOOLEAN DEFAULT FALSE,
    has_vomiting BOOLEAN DEFAULT FALSE,
    has_fever BOOLEAN DEFAULT FALSE,
    is_affected_member BOOLEAN DEFAULT FALSE,
    symptom_onset_date DATE,
    
    -- Food Exposure & Meal History
    shared_outside_food ENUM('Yes', 'No', 'Not Applicable') DEFAULT 'Not Applicable',
    outside_food_notes VARCHAR(500),
    shared_feast_meal BOOLEAN DEFAULT FALSE,
    shared_same_meal_before_symptom BOOLEAN DEFAULT FALSE,
    meal_type VARCHAR(255),
    
    -- Healthcare & Household
    healthcare_coverage VARCHAR(100),
    household_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better query performance
    INDEX idx_household (household_id),
    INDEX idx_national_id (national_id),
    INDEX idx_symptom_date (symptom_onset_date),
    
    FOREIGN KEY (household_id) REFERENCES Household(household_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;