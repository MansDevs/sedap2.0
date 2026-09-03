-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 01, 2026 at 01:17 PM
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
(2, 'direct', NULL, 4, '2026-08-21 08:52:27'),
(3, 'direct', NULL, 6, '2026-08-29 04:56:49'),
(4, 'direct', NULL, 7, '2026-08-29 04:56:49'),
(5, 'direct', NULL, 8, '2026-08-29 04:56:49'),
(6, 'direct', NULL, 9, '2026-08-29 04:56:49'),
(7, 'direct', NULL, 5, '2026-08-29 04:57:48'),
(8, 'direct', NULL, 10, '2026-09-01 13:06:16');

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
(4, 2, 3, 'member', 10, 0, 1, '2026-08-21 08:52:27', NULL),
(5, 3, 6, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(6, 3, 2, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(7, 4, 7, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(8, 4, 2, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(9, 5, 8, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(10, 5, 2, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(11, 6, 9, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(12, 6, 2, 'member', NULL, 0, 1, '2026-08-29 04:56:49', NULL),
(13, 7, 5, 'member', 64, 0, 1, '2026-08-29 04:57:48', NULL),
(14, 7, 3, 'member', 64, 0, 1, '2026-08-29 04:57:48', NULL),
(15, 3, 3, 'member', 12, 0, 1, '2026-08-29 05:04:43', NULL),
(16, 4, 3, 'member', 0, 0, 1, '2026-08-29 05:04:43', NULL),
(17, 5, 3, 'member', 0, 0, 1, '2026-08-29 05:04:43', NULL),
(18, 6, 3, 'member', 0, 0, 1, '2026-08-29 05:04:43', NULL),
(19, 8, 10, 'member', 0, 0, 1, '2026-09-01 13:06:16', NULL),
(20, 8, 2, 'member', 0, 0, 1, '2026-09-01 13:06:16', NULL);

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
(10, 2, 3, 'hello', NULL, '2026-08-23 14:00:15', NULL, NULL),
(11, 3, 2, 'I understand, Elias. Let\'s get a better look at it. Are you experiencing any numbness or tingling in your toes?', NULL, '2026-08-29 04:52:49', NULL, NULL),
(12, 3, 6, 'No numbness, just a throbbing pain. The skin looks a bit more red than before too.', NULL, '2026-08-29 04:56:49', NULL, NULL),
(13, 7, 5, 'hi', NULL, '2026-08-29 04:58:04', NULL, NULL),
(14, 7, 5, 'Bilakah saya perlu pergi ke hospital?', NULL, '2026-08-29 04:58:41', NULL, NULL),
(15, 7, 3, 'Pergi hospital segera jika ada darah dalam najis/muntah, sakit perut teruk, atau sesak nafas.', NULL, '2026-08-29 04:58:56', NULL, NULL),
(16, 7, 5, 'baiklah', NULL, '2026-08-29 05:05:02', NULL, NULL),
(17, 7, 5, 'naise', NULL, '2026-08-29 05:05:13', NULL, NULL),
(18, 7, 5, 'nice', NULL, '2026-08-29 05:05:20', NULL, NULL),
(19, 7, 5, 'hello', NULL, '2026-08-29 05:11:09', NULL, NULL),
(20, 7, 5, 'hello', NULL, '2026-08-29 05:11:15', NULL, NULL),
(21, 7, 3, 'Here is the community medical guide on ORS rehydration and prevention steps: https://sedap.moh.gov.my/guides/ors-hydration', NULL, '2026-08-29 05:11:30', NULL, NULL),
(22, 7, 3, 'Please take a clear, well-lit photo of the affected area and send it over so I can assess the inflammation.', NULL, '2026-08-29 05:11:38', NULL, '2026-08-29 05:22:27'),
(23, 7, 5, '[img]/sedap/sedap2.0/uploads/chat/chat_20260829_071343_6a926a87aeaff.png[/img]', NULL, '2026-08-29 05:13:46', NULL, NULL),
(24, 7, 3, '[img]/sedap/sedap2.0/uploads/chat/chat_20260829_071523_6a926aebbdace.jpg[/img]', NULL, '2026-08-29 05:15:26', NULL, NULL),
(25, 7, 3, '[img]/sedap/sedap2.0/uploads/chat/chat_20260829_071806_6a926b8ec422f.jpg[/img]', NULL, '2026-08-29 05:18:09', NULL, '2026-08-29 05:22:19'),
(26, 7, 5, 'hi', NULL, '2026-08-29 05:42:16', NULL, NULL),
(27, 7, 5, 'hi', NULL, '2026-08-29 05:42:28', NULL, NULL),
(28, 7, 5, 'hi', NULL, '2026-08-29 05:44:58', NULL, NULL),
(29, 7, 5, 'hello', NULL, '2026-08-29 05:45:02', NULL, NULL),
(30, 7, 5, 'hi', NULL, '2026-08-29 05:45:08', NULL, NULL),
(31, 7, 5, 'hi', NULL, '2026-08-29 05:49:42', NULL, NULL),
(32, 7, 5, 'hi', NULL, '2026-08-29 05:49:49', NULL, NULL),
(33, 7, 5, 'hi', NULL, '2026-08-29 05:53:46', NULL, NULL),
(34, 7, 5, 'hi', NULL, '2026-08-29 05:53:51', NULL, NULL),
(35, 7, 5, 'hi', NULL, '2026-08-29 05:53:56', NULL, NULL),
(36, 7, 5, 'hi', NULL, '2026-08-29 05:54:21', NULL, NULL),
(37, 7, 5, 'hi', NULL, '2026-08-29 05:58:22', NULL, NULL),
(38, 7, 5, 'hi', NULL, '2026-08-29 06:01:56', NULL, NULL),
(39, 7, 5, 'hello', NULL, '2026-08-29 06:02:00', NULL, NULL),
(40, 7, 3, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_081204_6a927834856bb.webm[/audio]', NULL, '2026-08-29 06:12:04', NULL, NULL),
(41, 7, 3, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_082705_6a927bb917675.webm[/audio]', NULL, '2026-08-29 06:27:05', NULL, '2026-08-29 15:15:01'),
(42, 7, 3, 'ss', NULL, '2026-08-29 06:27:14', NULL, NULL),
(43, 7, 5, 's', NULL, '2026-08-29 11:53:48', NULL, NULL),
(44, 7, 5, 's', NULL, '2026-08-29 11:53:54', NULL, NULL),
(45, 7, 5, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_135609_6a92c8d95ea0a.webm[/audio]', NULL, '2026-08-29 11:56:10', NULL, NULL),
(46, 7, 5, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_140013_6a92c9cd2c585.webm[/audio]', NULL, '2026-08-29 12:00:13', NULL, NULL),
(47, 7, 3, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_140024_6a92c9d8745fc.webm[/audio]', NULL, '2026-08-29 12:00:25', NULL, NULL),
(48, 7, 5, 'hi', NULL, '2026-08-29 12:05:38', NULL, NULL),
(49, 7, 5, 'hi', NULL, '2026-08-29 12:05:45', NULL, NULL),
(50, 7, 5, 'hi', NULL, '2026-08-29 12:06:03', NULL, NULL),
(51, 7, 5, 'hi', NULL, '2026-08-29 12:13:38', NULL, NULL),
(52, 7, 5, 'hi', NULL, '2026-08-29 12:18:13', NULL, NULL),
(53, 7, 5, 'tes\'', NULL, '2026-08-29 12:18:19', NULL, NULL),
(54, 7, 5, 'hi', NULL, '2026-08-29 12:20:38', NULL, NULL),
(55, 7, 5, 'great', NULL, '2026-08-29 12:23:58', NULL, NULL),
(56, 7, 3, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_142405_6a92cf65eb30f.webm[/audio]', NULL, '2026-08-29 12:24:06', NULL, NULL),
(57, 7, 5, 'hi', NULL, '2026-08-29 12:33:37', NULL, NULL),
(58, 7, 5, 'hello', NULL, '2026-08-29 12:33:46', NULL, NULL),
(59, 7, 5, 'lo', NULL, '2026-08-29 12:33:53', NULL, NULL),
(60, 7, 5, 'ello', NULL, '2026-08-29 12:34:01', NULL, NULL),
(61, 7, 5, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_143411_6a92d1c33173e.webm[/audio]', NULL, '2026-08-29 12:34:11', NULL, NULL),
(62, 7, 5, '[audio]/sedap/sedap2.0/uploads/chat_audio/voice_20260829_143425_6a92d1d1756e7.webm[/audio]', NULL, '2026-08-29 12:34:26', NULL, NULL),
(63, 7, 5, 'hello', NULL, '2026-08-29 14:05:20', NULL, NULL),
(64, 7, 5, 'hello', NULL, '2026-08-29 14:28:34', NULL, NULL);

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
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `triage_id` varchar(20) NOT NULL UNIQUE COMMENT 'Formatted Triage ID (e.g. TI-001)',
  `patient_id` int(11) DEFAULT NULL COMMENT 'Optional Foreign Key to patients.id',
  `full_name` varchar(150) NOT NULL COMMENT 'Full Name of Patient',
  `ic_number` varchar(12) NOT NULL COMMENT 'National Identity Card (IC) No. (12 Digits without hyphen)',
  `phone_number` varchar(20) DEFAULT NULL COMMENT 'Patient Phone Number',
  `age` int(11) DEFAULT NULL COMMENT 'Patient Age in Years',
  `gender` enum('Male','Female') DEFAULT 'Male' COMMENT 'Patient Gender',
  `occupation` varchar(100) DEFAULT NULL COMMENT 'Patient Occupation',
  `education_level` enum('No Formal Education','Primary School','Secondary School','Diploma / Degree') DEFAULT 'Secondary School' COMMENT 'Education Level',
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Body Temperature (°C)',
  `blood_pressure` varchar(20) DEFAULT NULL COMMENT 'Blood Pressure (BP)',
  `glucose_level` decimal(5,2) DEFAULT NULL COMMENT 'Blood Glucose Level (mmol/L)',
  `lipid_profile` decimal(5,2) DEFAULT NULL COMMENT 'Lipid Profile Reading (mmol/L)',
  `symptoms` varchar(500) NOT NULL COMMENT 'Main / Acute Symptoms checklist as JSON or text',
  `medical_history` text DEFAULT NULL COMMENT 'Pre-existing Medical Conditions (Diabetes, Hypertension, Gastritis, Drug Allergies)',
  `interview_notes` text DEFAULT NULL COMMENT 'Volunteer Interview Notes (cause, symptom onset time, complaints)',
  `triage_level` enum('green','yellow','red') NOT NULL DEFAULT 'green' COMMENT 'Triage Urgency Category (Single Selection)',
  `chief_complaint` varchar(255) DEFAULT NULL,
  `pulse_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `spo2` int(11) DEFAULT NULL,
  `consciousness_level` varchar(50) DEFAULT NULL,
  `status` enum('waiting','in_treatment','referred','discharged') NOT NULL DEFAULT 'waiting',
  `triaged_by` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `triaged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  INDEX `idx_triage_custom_id` (`triage_id`),
  INDEX `idx_triage_ic` (`ic_number`),
  INDEX `idx_triage_patient` (`patient_id`),
  INDEX `idx_triage_level` (`triage_level`),
  INDEX `idx_triage_date` (`triaged_at`)
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lang` varchar(10) NOT NULL DEFAULT 'ms',
  `sound_notification` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `phone`, `contact_number`, `date_of_birth`, `avatar_url`, `dark_mode`, `status`, `water_target_ml`, `weight_kg`, `created_at`, `updated_at`, `lang`, `sound_notification`) VALUES
(2, 'Ian', 'ian', 'nuriman3300@gmail.com', '$2y$10$QuLOTldeuX1RrpNThdAB8exVulI6RUJzWdCmKJuiphHiza83BBw6G', 'doctor', NULL, NULL, NULL, NULL, 0, 'active', 2100, NULL, '2026-08-19 16:25:43', '2026-08-24 07:35:17', 'ms', 1),
(3, 'Ahmad', 'ahmad', 'ahmad@gmail.com', '$2y$10$i.oixkpKjgycNd3qhmun5OEPr/QxE.AY53xNshFp64peWTMiuxby.', 'admin', NULL, NULL, NULL, NULL, 1, 'active', 2100, NULL, '2026-08-19 16:34:16', '2026-09-01 13:14:31', 'ms', 1),
(4, 'Adam', 'adam', 'adam@gmail.com', '$2y$10$SrASugqGVN/nJkBONfyVh.0xDU3tT0mVlZ3dZVh/um8yJ4N/qrSI6', 'doctor', NULL, NULL, NULL, NULL, 0, 'active', 2100, NULL, '2026-08-21 08:52:13', '2026-09-01 12:14:42', 'ms', 1),
(5, 'syakirah Binti Rahim', 'syakirah', 'syakirah@gmail.com', '$2y$10$yJmh1e5cdyGlj6M.sSzbcukIRziDdPRu5dfeBOEFAEi/Z7J4EwbVK', 'user', '0109162343', NULL, NULL, NULL, 1, 'active', 2100, NULL, '2026-08-29 04:34:23', '2026-08-29 04:34:58', 'ms', 1),
(6, 'Elias Morgan', 'elias', 'elias.morgan@example.com', '$2y$10$1WVod2jellLCm3WVXjK4guZj45960Bb0Hfxpnhscw1iHkD9EjOHci', 'user', NULL, '012-3456789', NULL, NULL, 0, 'active', 2100, NULL, '2026-08-29 04:56:49', '2026-08-29 04:56:49', 'ms', 1),
(7, 'Sarah Reynolds', 'sarah_r', 'sarah.reynolds@example.com', '$2y$10$iMvjfBBivy3PXNdSVOvnU.l0UtvCtgpU9kArNEYCQZAphSLevJXV.', 'user', NULL, '013-9876543', NULL, NULL, 0, 'active', 2100, NULL, '2026-08-29 04:56:49', '2026-08-29 04:56:49', 'ms', 1),
(8, 'James Davies', 'james_d', 'james.davies@example.com', '$2y$10$6bK2Nqb8eY4HCq4YA9huU.RUZVeWHlJ0U8wlHpwCxYc8VBINhZFTe', 'user', NULL, '014-5551234', NULL, NULL, 0, 'active', 2100, NULL, '2026-08-29 04:56:49', '2026-08-29 04:56:49', 'ms', 1),
(9, 'Priority: Room 4 (Observation)', 'room4', 'room4@sedap.local', '$2y$10$X1XtPJEux38/gMr4EvcbMumzlMYVlFns8a7Gja6lDLjT1JP5e6Nz6', 'user', NULL, '019-9990004', NULL, NULL, 0, 'active', 2100, NULL, '2026-08-29 04:56:49', '2026-08-29 04:56:49', 'ms', 1),
(10, 'rahim bin ahmad', 'rahim', 'rahim@gmail.com', '$2y$10$6kzu3YMhkohLgAUIhGv3POFZg2R0MhED2q1U5/Ikur8RawTztFVqO', 'user', NULL, NULL, NULL, NULL, 0, 'active', 2100, NULL, '2026-09-01 12:56:10', '2026-09-01 13:08:44', 'ms', 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
