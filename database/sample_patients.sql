-- =========================================================
-- OPTIONAL — sample patients for testing the Health Module
-- before Patient Registration (admin/patients) is built for real.
-- Safe to skip; safe to delete these rows once real patients exist.
-- =========================================================

INSERT INTO `patients` (`registration_number`, `full_name`, `ic_number`, `gender`, `phone`, `status`)
VALUES
  ('PT-000001', 'Ahmad bin Ismail', '900101-13-5555', 'male', '0123456789', 'registered'),
  ('PT-000002', 'Siti Aminah binti Yusof', '950202-14-6666', 'female', '0129876543', 'registered');
