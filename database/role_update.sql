-- =========================================================
-- SeDaP — Role update for Doctor / Medical Assistant / Nurse
-- Import AFTER sedap_admin_schema.sql
-- =========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

-- Extend the role enum on `users` to include the new roles.
-- (MySQL/MariaDB requires re-declaring the full enum list on MODIFY.)
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','staff','volunteer','doctor','nurse','medical_assistant') NOT NULL DEFAULT 'staff';

-- --------------------------------------------------------

--
-- Table `triage_counter_logs`
-- Backs the "Triage Patient Counter" — a fast tally tool, separate from
-- the full triage_records list. Each tap inserts one row; `change` is
-- +1 for a normal tap, -1 to undo a mis-tap. Sum by triage_level for totals.
--

CREATE TABLE `triage_counter_logs` (
  `id` INT(11) NOT NULL,
  `triage_level` ENUM('red','yellow','green','black') NOT NULL,
  `change_value` TINYINT(4) NOT NULL DEFAULT 1 COMMENT '+1 to tally, -1 to undo a mis-tap',
  `recorded_by` INT(11) NOT NULL,
  `recorded_at` TIMESTAMP NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `triage_counter_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `level_totals_idx` (`triage_level`) COMMENT 'Speeds up SUM(change_value) per color';

ALTER TABLE `triage_counter_logs`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `triage_counter_logs`
  ADD CONSTRAINT `fk_counter_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;
