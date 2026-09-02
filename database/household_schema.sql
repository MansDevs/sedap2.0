-- Household & Family Registration Schema for SeDaP 2.0

CREATE TABLE IF NOT EXISTS Household (
    household_id INT AUTO_INCREMENT PRIMARY KEY,
    street_address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100),
    state VARCHAR(100),
    house_type VARCHAR(50),      -- Apartment, Landed, Rental, Owned, PPS, etc.
    total_residents INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS HeadOfHousehold (
    ic_number VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20),
    email VARCHAR(100),
    household_id INT NOT NULL,
    FOREIGN KEY (household_id) REFERENCES Household(household_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS Member (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(30) NOT NULL,
    date_of_birth DATE,
    gender VARCHAR(10),
    relationship_to_head VARCHAR(50),
    marital_status VARCHAR(20),
    citizenship_status VARCHAR(30),
    education_level VARCHAR(30),
    employment_status VARCHAR(30),
    chronic_condition VARCHAR(255),
    healthcare_coverage VARCHAR(100),
    vulnerable_dependent VARCHAR(100),
    household_id INT NOT NULL,
    FOREIGN KEY (household_id) REFERENCES Household(household_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS HouseholdFinance (
    finance_id INT AUTO_INCREMENT PRIMARY KEY,
    household_id INT NOT NULL,
    gross_household_income DECIMAL(10,2) DEFAULT 0.00,
    rent_mortgage DECIMAL(10,2) DEFAULT 0.00,
    utilities DECIMAL(10,2) DEFAULT 0.00,
    education_fees DECIMAL(10,2) DEFAULT 0.00,
    medical_costs DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (household_id) REFERENCES Household(household_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
