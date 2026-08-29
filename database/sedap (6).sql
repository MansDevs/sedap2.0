-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 28, 2026 at 06:50 PM
-- Server version: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sedap`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_revisions`
--

CREATE TABLE `announcement_revisions` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `content` text NOT NULL COMMENT 'Snapshot of content at time of edit',
  `edited_by` int(11) NOT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bristol_scale_info`
--

CREATE TABLE `bristol_scale_info` (
  `id` int(11) NOT NULL,
  `scale_type` tinyint(1) NOT NULL COMMENT '1–7 Bristol chart type',
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bristol_scale_info`
--

INSERT INTO `bristol_scale_info` (`id`, `scale_type`, `title`, `description`, `image_url`, `updated_by`, `updated_at`) VALUES
(1, 1, 'Type 1 — Separate Hard Lumps', 'Najis keras seperti kacang kering. Sangat susah untuk keluar. Tanda sembelit teruk.', NULL, NULL, '2026-08-24 07:35:16'),
(2, 2, 'Type 2 — Lumpy and Sausage-Like', 'Najis berbentuk sosej tetapi bergetar dan berketul. Tanda sembelit ringan.', NULL, NULL, '2026-08-24 07:35:16'),
(3, 3, 'Type 3 — Sausage With Cracks', 'Seperti sosej dengan retakan pada permukaan. Normal / boleh diterima.', NULL, NULL, '2026-08-24 07:35:16'),
(4, 4, 'Type 4 — Smooth & Soft Sausage', 'Seperti sosej atau ular, licin dan lembut. Paling ideal / sihat.', NULL, NULL, '2026-08-24 07:35:16'),
(5, 5, 'Type 5 — Soft Blobs with Clear Edges', 'Gumpalan lembut dengan tepi yang jelas. Mungkin tiada cukup serat.', NULL, NULL, '2026-08-24 07:35:16'),
(6, 6, 'Type 6 — Mushy Consistency', 'Najis berkecai dengan tepi bergerigi. Tanda cirit-birit ringan.', NULL, NULL, '2026-08-24 07:35:16'),
(7, 7, 'Type 7 — Entirely Liquid', 'Sepenuhnya cecair, tiada pepejal. Tanda cirit-birit teruk / dehidrasi.', NULL, NULL, '2026-08-24 07:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `bristol_scale_logs`
--

CREATE TABLE `bristol_scale_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `scale_type` tinyint(1) NOT NULL COMMENT 'Bristol stool chart type 1–7',
  `notes` varchar(255) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `type` enum('direct','group') NOT NULL DEFAULT 'direct',
  `name` varchar(255) DEFAULT NULL COMMENT 'NULL for direct chats, group name for groups',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `type`, `name`, `created_by`, `created_at`) VALUES
(1, 'direct', NULL, 3, '2026-08-21 08:51:32'),
(2, 'direct', NULL, 4, '2026-08-21 08:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `last_read_message_id` int(11) DEFAULT NULL COMMENT 'Powers the unread counter',
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sound` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL COMMENT 'NULL = still in the chat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `role`, `last_read_message_id`, `is_muted`, `notification_sound`, `joined_at`, `left_at`) VALUES
(1, 1, 3, 'member', 1, 0, 1, '2026-08-21 08:51:32', NULL),
(2, 1, 2, 'member', NULL, 0, 1, '2026-08-21 08:51:32', NULL),
(3, 2, 4, 'member', 9, 0, 1, '2026-08-21 08:52:27', NULL),
(4, 2, 3, 'member', 10, 0, 1, '2026-08-21 08:52:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `families`
--

CREATE TABLE `families` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK to users.id — patient who registered',
  `head_name` varchar(255) NOT NULL COMMENT 'Nama Ketua Keluarga',
  `head_ic` varchar(20) NOT NULL COMMENT 'No. IC / ID SeDaP',
  `head_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL COMMENT 'Alamat / No. Lot / Kod Zon',
  `total_members` tinyint(3) DEFAULT NULL,
  `water_source` enum('treated_tap','well_gravity','river_rain','bottled') DEFAULT NULL,
  `toilet_type` enum('flush_proper','pit_open','shared_community') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_health_screening`
--

CREATE TABLE `family_health_screening` (
  `id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `has_sick_members` tinyint(1) NOT NULL DEFAULT 0,
  `sick_member_names` text DEFAULT NULL,
  `shared_food` tinyint(1) NOT NULL DEFAULT 0,
  `shared_food_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `relationship` enum('spouse','child','parent','grandparent','relative_other') NOT NULL,
  `age` tinyint(3) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `vulnerable_category` set('infant_under5','elderly_60plus','pregnant','disabled','none') DEFAULT 'none',
  `chronic_diseases` set('diabetes','hypertension','kidney','gastric_bowel','none_other') DEFAULT 'none_other',
  `allergies` varchar(255) DEFAULT NULL COMMENT 'Alahan ubat / makanan, atau Tiada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq_templates`
--

CREATE TABLE `faq_templates` (
  `id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faq_templates`
--

INSERT INTO `faq_templates` (`id`, `question`, `answer`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Apakah ubat yang perlu saya ambil untuk cirit-birit?', 'Sila minum garam rehidrasi oral (ORS) dan hubungi doktor anda untuk penilaian lanjut.', 1, 1, '2026-08-24 07:35:16', '2026-08-24 07:35:16'),
(2, 'Berapa banyak air yang perlu saya minum sehari?', 'Sasaran asas ialah berat badan (kg) × 35 ml. Contoh: 60 kg → 2,100 ml sehari.', 1, 1, '2026-08-24 07:35:16', '2026-08-24 07:35:16'),
(3, 'Bilakah saya perlu pergi ke hospital?', 'Pergi hospital segera jika ada darah dalam najis/muntah, sakit perut teruk, atau sesak nafas.', 1, 1, '2026-08-24 07:35:16', '2026-08-24 07:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `health_module_content`
--

CREATE TABLE `health_module_content` (
  `id` int(11) NOT NULL,
  `module` enum('water_tracker','bristol_info','mood_guide','medicine_guide') NOT NULL,
  `section` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `purpose` enum('ors_probiotic','painkiller_fever','antibiotic','chronic_disease','supplement','other') DEFAULT 'other',
  `form` enum('tablet','capsule','liquid','powder') DEFAULT 'tablet',
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL COMMENT 'e.g. "2x daily"',
  `food_instruction` enum('before_meal','after_meal','with_meal') DEFAULT 'after_meal',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `stock_count` int(11) DEFAULT NULL COMMENT 'Number of pills/packets remaining',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_reminders`
--

CREATE TABLE `medicine_reminders` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `reminder_time` time NOT NULL,
  `days_of_week` varchar(20) DEFAULT NULL COMMENT 'CSV of 1-7, e.g. "1,2,3,4,5"',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_reminder_logs`
--

CREATE TABLE `medicine_reminder_logs` (
  `id` int(11) NOT NULL,
  `reminder_id` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `status` enum('pending','taken','missed','skipped') NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `reply_to_id` int(11) DEFAULT NULL COMMENT 'Self-reference for replies/quotes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete — UI shows "message deleted"'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `reply_to_id`, `created_at`, `edited_at`, `deleted_at`) VALUES
(1, 1, 3, 'hi bro', NULL, '2026-08-21 08:51:36', NULL, NULL),
(2, 2, 4, 'hello', NULL, '2026-08-21 08:52:30', NULL, NULL),
(3, 2, 3, 'Hallo', NULL, '2026-08-21 08:54:25', NULL, NULL),
(4, 2, 3, 'Hee', NULL, '2026-08-21 08:54:29', NULL, NULL),
(5, 2, 3, 'Semua oke keep tuuuuu', NULL, '2026-08-21 08:54:48', NULL, NULL),
(6, 2, 3, 'Ll', NULL, '2026-08-21 08:57:29', NULL, NULL),
(7, 2, 4, 'lol', NULL, '2026-08-21 08:57:33', NULL, NULL),
(8, 2, 4, 'ingat kelakar la tu', NULL, '2026-08-21 08:57:38', NULL, NULL),
(9, 2, 4, 'hahah', NULL, '2026-08-21 08:57:47', NULL, NULL),
(10, 2, 3, 'hello', NULL, '2026-08-23 14:00:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `message_status`
--

CREATE TABLE `message_status` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_status`
--

INSERT INTO `message_status` (`id`, `message_id`, `user_id`, `status`, `updated_at`) VALUES
(1, 1, 2, 'sent', '2026-08-21 08:51:36'),
(2, 2, 3, 'read', '2026-08-21 08:52:44'),
(3, 3, 4, 'read', '2026-08-21 08:54:35'),
(4, 4, 4, 'read', '2026-08-21 08:54:35'),
(5, 5, 4, 'read', '2026-08-21 08:54:55'),
(6, 6, 4, 'read', '2026-08-21 08:57:29'),
(7, 7, 3, 'read', '2026-08-21 08:57:33'),
(8, 8, 3, 'read', '2026-08-21 08:57:39'),
(9, 9, 3, 'read', '2026-08-21 08:57:48'),
(10, 10, 4, 'sent', '2026-08-23 14:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `mood_journal_entries`
--

CREATE TABLE `mood_journal_entries` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `mood` enum('very_sad','sad','neutral','happy','very_happy') NOT NULL,
  `mood_score` tinyint(1) DEFAULT NULL COMMENT '1=very_sad to 5=very_happy',
  `specific_emotions` text DEFAULT NULL COMMENT 'JSON array of selected emotion tags',
  `energy_level` tinyint(1) DEFAULT NULL COMMENT '1=Exhausted 2=Tired 3=Moderate 4=Full',
  `sleep_quality` tinyint(1) DEFAULT NULL COMMENT '1=<5h 2=Insomnia 3=Woke often 4=Restful 7h+',
  `focus_level` tinyint(1) DEFAULT NULL COMMENT '1=Brain fog 2=Moderate 3=Sharp',
  `stress_level` tinyint(1) DEFAULT NULL COMMENT '1=Low(0-25%) 2=Moderate 3=High 4=Critical(76-100%)',
  `stress_triggers` text DEFAULT NULL COMMENT 'JSON array of trigger tags',
  `gut_brain_symptoms` text DEFAULT NULL COMMENT 'JSON array of gut symptom tags',
  `appetite_state` tinyint(1) DEFAULT NULL COMMENT '1=Normal 2=Overeating 3=No appetite 4=Nausea',
  `social_interaction` tinyint(1) DEFAULT NULL COMMENT '1=Isolated 2=Conflict 3=Neutral 4=Supported',
  `coping_activities` text DEFAULT NULL COMMENT 'JSON array of coping strategy tags',
  `gratitude_note` text DEFAULT NULL COMMENT 'One thing I am grateful for',
  `personal_note` text DEFAULT NULL COMMENT 'Free journal / personal reflection',
  `note` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL COMMENT 'e.g. PT-000001',
  `full_name` varchar(255) NOT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('registered','in_triage','admitted','discharged','referred') NOT NULL DEFAULT 'registered',
  `registered_by` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'FK to users.id for patient login account',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_family_members`
--

CREATE TABLE `patient_family_members` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_emergency_contact` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `type` enum('doctor','volunteer') NOT NULL COMMENT 'doctor = medical staff / MA / nurse',
  `full_name` varchar(255) NOT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `availability_date` date DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','active','inactive') NOT NULL DEFAULT 'pending',
  `registered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `type`, `full_name`, `ic_number`, `gender`, `date_of_birth`, `phone`, `email`, `address`, `department`, `skills`, `availability_date`, `emergency_contact_name`, `emergency_contact_phone`, `user_id`, `status`, `registered_by`, `created_at`, `updated_at`) VALUES
(4, 'doctor', 'MOHAMAD NUR IMANUDIN BIN JALALLUDDIN', '0102380123', 'male', '2026-08-12', '0109163624', 'nuriman3300@gmail.com', 'sd', 'sds', 'dsd', '2026-08-12', '0109163624', '0109163624', NULL, 'pending', 3, '2026-08-23 12:39:37', '2026-08-23 12:39:37');

-- --------------------------------------------------------

--
-- Table structure for table `posters`
--

CREATE TABLE `posters` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `canvas_data` longtext DEFAULT NULL COMMENT 'JSON design state from Fabric.js editor',
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `screening_answers`
--

CREATE TABLE `screening_answers` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `screening_forms`
--

CREATE TABLE `screening_forms` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `screening_questions`
--

CREATE TABLE `screening_questions` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `question_text` varchar(500) NOT NULL,
  `question_type` enum('text','number','single_choice','multiple_choice','scale','yes_no') NOT NULL DEFAULT 'text',
  `options` text DEFAULT NULL COMMENT 'JSON array of choices',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `order_index` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `screening_responses`
--

CREATE TABLE `screening_responses` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `respondent_name` varchar(255) DEFAULT NULL,
  `respondent_phone` varchar(20) DEFAULT NULL,
  `triage_result` enum('red','yellow','green') DEFAULT NULL COMMENT 'Auto-determined triage code',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `triage_records`
--

CREATE TABLE `triage_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `zone_code` varchar(50) DEFAULT NULL COMMENT 'Kod Kawasan / Rujukan',
  `occupation` varchar(100) DEFAULT NULL COMMENT 'Pekerjaan pesakit',
  `education_level` enum('none','primary','secondary','tertiary') DEFAULT NULL,
  `triage_level` enum('red','yellow','green','black') NOT NULL COMMENT 'Triage color code',
  `chief_complaint` varchar(255) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Body temperature in °C',
  `spo2` int(11) DEFAULT NULL,
  `consciousness_level` varchar(50) DEFAULT NULL COMMENT 'AVPU scale',
  `glucose_level` decimal(5,2) DEFAULT NULL COMMENT 'Paras Glukosa Darah mmol/L',
  `lipid_profile` decimal(5,2) DEFAULT NULL COMMENT 'Profil Lipid mmol/L',
  `symptoms` text DEFAULT NULL COMMENT 'JSON array: cirit-birit, muntah, demam, sakit perut, pening',
  `medical_history` text DEFAULT NULL COMMENT 'Penyakit sedia ada, alahan ubat',
  `interview_notes` text DEFAULT NULL COMMENT 'Catatan temu bual sukarelawan',
  `status` enum('waiting','in_treatment','referred','discharged') NOT NULL DEFAULT 'waiting',
  `triaged_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `triaged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash via password_hash()',
  `role` enum('admin','doctor','volunteer','user') NOT NULL DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL COMMENT 'General phone number',
  `contact_number` varchar(20) DEFAULT NULL COMMENT 'Additional contact',
  `date_of_birth` date DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `dark_mode` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `water_target_ml` int(11) DEFAULT 2100 COMMENT 'Daily water intake target in ml',
  `weight_kg` decimal(5,2) DEFAULT NULL COMMENT 'Body weight for water calculation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `phone`, `contact_number`, `date_of_birth`, `avatar_url`, `dark_mode`, `status`, `water_target_ml`, `weight_kg`, `created_at`, `updated_at`) VALUES
(2, 'Ian', 'ian', 'nuriman3300@gmail.com', '$2y$10$QuLOTldeuX1RrpNThdAB8exVulI6RUJzWdCmKJuiphHiza83BBw6G', 'doctor', NULL, NULL, NULL, NULL, 0, 'active', 2100, NULL, '2026-08-19 16:25:43', '2026-08-24 07:35:17'),
(3, 'Ahmad', 'ahmad', 'ahmad@gmail.com', '$2y$10$i.oixkpKjgycNd3qhmun5OEPr/QxE.AY53xNshFp64peWTMiuxby.', 'doctor', NULL, NULL, NULL, NULL, 1, 'active', 2100, NULL, '2026-08-19 16:34:16', '2026-08-28 07:46:27'),
(4, 'Adam', 'adam', 'adam@gmail.com', '$2y$10$SrASugqGVN/nJkBONfyVh.0xDU3tT0mVlZ3dZVh/um8yJ4N/qrSI6', 'doctor', NULL, NULL, NULL, NULL, 0, 'active', 2100, NULL, '2026-08-21 08:52:13', '2026-08-24 07:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `water_intake_logs`
--

CREATE TABLE `water_intake_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount_ml` int(11) NOT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `status_published_idx` (`status`,`published_at`);

--
-- Indexes for table `announcement_revisions`
--
ALTER TABLE `announcement_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `edited_by` (`edited_by`);

--
-- Indexes for table `bristol_scale_info`
--
ALTER TABLE `bristol_scale_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scale_type` (`scale_type`);

--
-- Indexes for table `bristol_scale_logs`
--
ALTER TABLE `bristol_scale_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_logged_idx` (`patient_id`,`logged_at`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conversation_user_unique` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_read_message_id` (`last_read_message_id`);

--
-- Indexes for table `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `family_health_screening`
--
ALTER TABLE `family_health_screening`
  ADD PRIMARY KEY (`id`),
  ADD KEY `family_id` (`family_id`);

--
-- Indexes for table `family_members`
--
ALTER TABLE `family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `family_id` (`family_id`);

--
-- Indexes for table `faq_templates`
--
ALTER TABLE `faq_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `health_module_content`
--
ALTER TABLE `health_module_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicine_reminders`
--
ALTER TABLE `medicine_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `medicine_reminder_logs`
--
ALTER TABLE `medicine_reminder_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reminder_scheduled_idx` (`reminder_id`,`scheduled_for`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `reply_to_id` (`reply_to_id`),
  ADD KEY `conversation_created_idx` (`conversation_id`,`created_at`);

--
-- Indexes for table `message_status`
--
ALTER TABLE `message_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_user_unique` (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mood_journal_entries`
--
ALTER TABLE `mood_journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_logged_idx` (`patient_id`,`logged_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `registered_by` (`registered_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `patient_family_members`
--
ALTER TABLE `patient_family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `registered_by` (`registered_by`),
  ADD KEY `type_status_idx` (`type`,`status`);

--
-- Indexes for table `posters`
--
ALTER TABLE `posters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `screening_answers`
--
ALTER TABLE `screening_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `screening_forms`
--
ALTER TABLE `screening_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `screening_questions`
--
ALTER TABLE `screening_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `screening_responses`
--
ALTER TABLE `screening_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `triage_records`
--
ALTER TABLE `triage_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `triaged_by` (`triaged_by`),
  ADD KEY `live_view_idx` (`status`,`triage_level`,`triaged_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `water_intake_logs`
--
ALTER TABLE `water_intake_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_logged_idx` (`patient_id`,`logged_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_revisions`
--
ALTER TABLE `announcement_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bristol_scale_info`
--
ALTER TABLE `bristol_scale_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bristol_scale_logs`
--
ALTER TABLE `bristol_scale_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `families`
--
ALTER TABLE `families`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_health_screening`
--
ALTER TABLE `family_health_screening`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq_templates`
--
ALTER TABLE `faq_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `health_module_content`
--
ALTER TABLE `health_module_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_reminders`
--
ALTER TABLE `medicine_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_reminder_logs`
--
ALTER TABLE `medicine_reminder_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `message_status`
--
ALTER TABLE `message_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `mood_journal_entries`
--
ALTER TABLE `mood_journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_family_members`
--
ALTER TABLE `patient_family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posters`
--
ALTER TABLE `posters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `screening_answers`
--
ALTER TABLE `screening_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `screening_forms`
--
ALTER TABLE `screening_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `screening_questions`
--
ALTER TABLE `screening_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `screening_responses`
--
ALTER TABLE `screening_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `triage_records`
--
ALTER TABLE `triage_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `water_intake_logs`
--
ALTER TABLE `water_intake_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ann_updated` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcement_revisions`
--
ALTER TABLE `announcement_revisions`
  ADD CONSTRAINT `fk_ann_rev_ann` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ann_rev_user` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `bristol_scale_logs`
--
ALTER TABLE `bristol_scale_logs`
  ADD CONSTRAINT `fk_bsl_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `fk_cp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `families`
--
ALTER TABLE `families`
  ADD CONSTRAINT `fk_families_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_health_screening`
--
ALTER TABLE `family_health_screening`
  ADD CONSTRAINT `fk_fhs_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_members`
--
ALTER TABLE `family_members`
  ADD CONSTRAINT `fk_fm_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_med_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
