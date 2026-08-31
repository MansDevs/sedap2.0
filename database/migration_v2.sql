-- ============================================================
-- SeDaP Database Migration v2
-- Aligns schema with full PRD requirements
-- Run this AFTER importing the base schema (sedap (3).sql)
-- ============================================================

-- ============================================================
-- 1. UPDATE users table
--    - Remove 'staff' role, add 'user' (patient) role
--    - Add username, date_of_birth, contact_number columns
-- ============================================================

-- Add new columns to users
ALTER TABLE `users`
  ADD COLUMN `username` varchar(100) NULL UNIQUE AFTER `name`,
  ADD COLUMN `date_of_birth` date NULL AFTER `phone`,
  ADD COLUMN `contact_number` varchar(20) NULL AFTER `date_of_birth`;

-- Update role enum to replace 'staff' with 'user'
-- Step 1: convert existing staff rows to 'doctor' (closest match per PRD decision)
UPDATE `users` SET `role` = 'doctor' WHERE `role` = 'staff';

-- Step 2: alter enum to new set
ALTER TABLE `users`
  MODIFY COLUMN `role` enum('admin','doctor','volunteer','user') NOT NULL DEFAULT 'user';

-- Populate usernames for existing users (fallback to email prefix)
UPDATE `users` SET `username` = SUBSTRING_INDEX(`email`, '@', 1) WHERE `username` IS NULL;

-- ============================================================
-- 2. UPDATE triage_records — add full PRD fields
-- ============================================================
ALTER TABLE `triage_records`
  ADD COLUMN `zone_code`        varchar(50)  NULL COMMENT 'Kod Kawasan / Rujukan' AFTER `patient_id`,
  ADD COLUMN `occupation`       varchar(100) NULL COMMENT 'Pekerjaan pesakit' AFTER `zone_code`,
  ADD COLUMN `education_level`  enum('none','primary','secondary','tertiary') NULL AFTER `occupation`,
  ADD COLUMN `glucose_level`    decimal(5,2) NULL COMMENT 'Paras Glukosa Darah mmol/L' AFTER `temperature`,
  ADD COLUMN `lipid_profile`    decimal(5,2) NULL COMMENT 'Profil Lipid mmol/L' AFTER `glucose_level`,
  ADD COLUMN `symptoms`         text NULL COMMENT 'JSON array: cirit-birit, muntah, demam, sakit perut, pening' AFTER `lipid_profile`,
  ADD COLUMN `medical_history`  text NULL COMMENT 'Penyakit sedia ada, alahan ubat' AFTER `symptoms`,
  ADD COLUMN `interview_notes`  text NULL COMMENT 'Catatan temu bual sukarelawan' AFTER `medical_history`;

-- ============================================================
-- 3. ADD patients.user_id — link patient login account
-- ============================================================
ALTER TABLE `patients`
  ADD COLUMN `user_id` int(11) NULL UNIQUE COMMENT 'FK to users.id for patient login' AFTER `registered_by`;

ALTER TABLE `patients`
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 4. EXPAND mood_journal_entries — full PRD 6-section journal
-- ============================================================
-- Keep original table structure but add extended fields
ALTER TABLE `mood_journal_entries`
  ADD COLUMN `mood_score`            tinyint(1) NULL COMMENT '1=very sad, 5=very happy' AFTER `mood`,
  ADD COLUMN `specific_emotions`     text NULL COMMENT 'JSON array of specific emotions selected' AFTER `mood_score`,
  ADD COLUMN `energy_level`          tinyint(1) NULL COMMENT '1-4 scale' AFTER `specific_emotions`,
  ADD COLUMN `sleep_quality`         tinyint(1) NULL COMMENT '1-4 scale' AFTER `energy_level`,
  ADD COLUMN `focus_level`           tinyint(1) NULL COMMENT '1-3 scale' AFTER `sleep_quality`,
  ADD COLUMN `stress_level`          tinyint(1) NULL COMMENT '1-4 scale (25%/50%/75%/100%)' AFTER `focus_level`,
  ADD COLUMN `stress_triggers`       text NULL COMMENT 'JSON array of triggers' AFTER `stress_level`,
  ADD COLUMN `gut_brain_symptoms`    text NULL COMMENT 'JSON array of gut symptoms' AFTER `stress_triggers`,
  ADD COLUMN `appetite_state`        tinyint(1) NULL COMMENT '1=normal, 2=overeating, 3=no appetite, 4=nausea' AFTER `gut_brain_symptoms`,
  ADD COLUMN `social_interaction`    tinyint(1) NULL COMMENT '1-4 scale' AFTER `appetite_state`,
  ADD COLUMN `coping_activities`     text NULL COMMENT 'JSON array of coping strategies' AFTER `social_interaction`,
  ADD COLUMN `gratitude_note`        text NULL COMMENT 'Reflection: gratitude' AFTER `coping_activities`,
  ADD COLUMN `personal_note`         text NULL COMMENT 'Reflection: personal journal entry' AFTER `gratitude_note`;

-- ============================================================
-- 5. ADD water target to users (daily target from BMI calc)
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN `water_target_ml` int(11) NULL DEFAULT 2100 COMMENT 'Daily water intake target in ml' AFTER `contact_number`,
  ADD COLUMN `weight_kg`       decimal(5,2) NULL COMMENT 'Body weight for water calculation' AFTER `water_target_ml`;

-- ============================================================
-- 6. NEW TABLE: families (household head registration - PRD User 4d)
-- ============================================================
CREATE TABLE `families` (
  `id`               int(11) NOT NULL AUTO_INCREMENT,
  `user_id`          int(11) NOT NULL COMMENT 'The patient/user who registered this household',
  `head_name`        varchar(255) NOT NULL COMMENT 'Nama Ketua Keluarga',
  `head_ic`          varchar(20) NOT NULL COMMENT 'No. IC / ID SeDaP',
  `head_phone`       varchar(20) NULL,
  `address`          text NULL COMMENT 'Alamat / No. Lot / Kod Zon',
  `total_members`    tinyint(3) NULL,
  `water_source`     enum('treated_tap','well_gravity','river_rain','bottled') NULL,
  `toilet_type`      enum('flush_proper','pit_open','shared_community') NULL,
  `created_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`       timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. NEW TABLE: family_members (per PRD — expanded fields)
-- ============================================================
CREATE TABLE `family_members` (
  `id`               int(11) NOT NULL AUTO_INCREMENT,
  `family_id`        int(11) NOT NULL,
  `full_name`        varchar(255) NOT NULL,
  `relationship`     enum('spouse','child','parent','grandparent','relative_other') NOT NULL,
  `age`              tinyint(3) NULL,
  `gender`           enum('male','female') NULL,
  `vulnerable_category` set('infant_under5','elderly_60plus','pregnant','disabled','none') DEFAULT 'none',
  `chronic_diseases` set('diabetes','hypertension','kidney','gastric_bowel','none_other') DEFAULT 'none_other',
  `allergies`        varchar(255) NULL COMMENT 'Alahan ubat / makanan, atau Tiada',
  `created_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `family_id` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 8. NEW TABLE: family_health_screening (PRD Part 3 of family form)
-- ============================================================
CREATE TABLE `family_health_screening` (
  `id`               int(11) NOT NULL AUTO_INCREMENT,
  `family_id`        int(11) NOT NULL,
  `has_sick_members` tinyint(1) NOT NULL DEFAULT 0,
  `sick_member_names` text NULL,
  `shared_food`      tinyint(1) NOT NULL DEFAULT 0,
  `shared_food_notes` text NULL,
  `submitted_at`     timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `family_id` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 9. NEW TABLE: bristol_scale_info (admin-editable content)
-- ============================================================
CREATE TABLE `bristol_scale_info` (
  `id`           int(11) NOT NULL AUTO_INCREMENT,
  `scale_type`   tinyint(1) NOT NULL COMMENT '1-7 Bristol chart type',
  `title`        varchar(100) NOT NULL,
  `description`  text NOT NULL,
  `image_url`    varchar(255) NULL,
  `updated_by`   int(11) NULL,
  `updated_at`   timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `scale_type` (`scale_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default Bristol Scale entries
INSERT INTO `bristol_scale_info` (`scale_type`, `title`, `description`) VALUES
(1, 'Type 1 — Separate Hard Lumps', 'Najis keras seperti kacang kering. Sangat susah untuk keluar. Tanda sembelit teruk.'),
(2, 'Type 2 — Lumpy and Sausage-Like', 'Najis berbentuk sosej tetapi bergetar dan berketul. Tanda sembelit ringan.'),
(3, 'Type 3 — Sausage With Cracks', 'Seperti sosej dengan retakan pada permukaan. Normal / boleh diterima.'),
(4, 'Type 4 — Smooth & Soft Sausage', 'Seperti sosej atau ular, licin dan lembut. Paling ideal / sihat.'),
(5, 'Type 5 — Soft Blobs with Clear Edges', 'Gumpalan lembut dengan tepi yang jelas. Mungkin tiada cukup serat.'),
(6, 'Type 6 — Mushy Consistency', 'Najis berkecai dengan tepi bergerigi. Tanda cirit-birit ringan.'),
(7, 'Type 7 — Entirely Liquid', 'Sepenuhnya cecair, tiada pepejal. Tanda cirit-birit teruk / dehidrasi.');

-- ============================================================
-- 10. NEW TABLE: faq_templates (for live chat)
-- ============================================================
CREATE TABLE `faq_templates` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `question`    varchar(500) NOT NULL,
  `answer`      text NOT NULL,
  `is_active`   tinyint(1) NOT NULL DEFAULT 1,
  `created_by`  int(11) NOT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default FAQ templates
INSERT INTO `faq_templates` (`question`, `answer`, `created_by`) VALUES
('Apakah ubat yang perlu saya ambil untuk cirit-birit?', 'Sila minum garam rehidrasi oral (ORS) dan hubungi doktor anda untuk penilaian lanjut.', 1),
('Berapa banyak air yang perlu saya minum sehari?', 'Sasaran asas ialah berat badan (kg) × 35 ml. Contoh: 60 kg → 2,100 ml sehari.', 1),
('Bilakah saya perlu pergi ke hospital?', 'Pergi hospital segera jika ada darah dalam najis/muntah, sakit perut teruk, atau sesak nafas.', 1);

-- ============================================================
-- 11. NEW TABLE: health_module_content (admin editable content for health modules)
-- ============================================================
CREATE TABLE `health_module_content` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `module`      enum('water_tracker','bristol_info','mood_guide','medicine_guide') NOT NULL,
  `section`     varchar(100) NULL,
  `content`     text NOT NULL,
  `updated_by`  int(11) NULL,
  `updated_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 12. FOREIGN KEY CONSTRAINTS for new tables
-- ============================================================
ALTER TABLE `families`
  ADD CONSTRAINT `fk_families_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `family_members`
  ADD CONSTRAINT `fk_family_members_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

ALTER TABLE `family_health_screening`
  ADD CONSTRAINT `fk_fhs_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

ALTER TABLE `faq_templates`
  ADD CONSTRAINT `fk_faq_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

-- ============================================================
-- 13. UPDATE personnel table — remove staff type (now all are doctor/volunteer)
-- ============================================================
-- Rename 'staff' type to accommodate new role naming
ALTER TABLE `personnel`
  MODIFY COLUMN `type` enum('doctor','volunteer') NOT NULL;

UPDATE `personnel` SET `type` = 'doctor' WHERE `type` = 'staff';

-- ============================================================
-- END OF MIGRATION
-- ============================================================
COMMIT;
