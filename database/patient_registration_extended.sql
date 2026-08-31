-- Migration script to add extended patient registration categories and fields
-- Categories: Demographics & Identification, Emergency Contacts, Insurance & Billing, Initial Clinical Screening

ALTER TABLE `patients`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `gender_identity` VARCHAR(100) NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `emergency_contact_relationship` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `emergency_contact_alt_phone` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `insurance_payer` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `insurance_policy_id` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `insurance_group_number` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `insurance_subscriber_details` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `insurance_coverage_type` VARCHAR(50) NULL DEFAULT 'Primary',
  ADD COLUMN IF NOT EXISTS `billing_address` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_reason_for_visit` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_active_medications` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_allergies` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_surgical_history` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_family_history` TEXT NULL;
