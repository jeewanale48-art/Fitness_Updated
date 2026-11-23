-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2026 at 08:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fittrack2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `related_type` varchar(40) DEFAULT NULL,
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `title`, `message`, `related_type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 'New coaching request', 'Sunny Shah requested a coaching session.', 'booking', 1, 0, '2026-11-21 19:51:07'),
(2, 'New subscription request', 'Sunny Shah submitted a 3 Months plan request.', 'subscription', 1, 0, '2026-11-21 19:54:39'),
(3, 'New subscription request', 'ashik submitted a 1 Month plan request.', 'subscription', 2, 0, '2026-11-22 17:21:36'),
(4, 'New gym membership request', 'rose applied for the 1-month package.', 'gym_membership', 4, 0, '2026-11-22 18:05:34'),
(5, 'New subscription request', 'rose submitted a 1 Month plan request.', 'subscription', 3, 0, '2026-11-22 18:25:04');

-- --------------------------------------------------------

--
-- Table structure for table `admin_suggestions`
--

CREATE TABLE `admin_suggestions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `suggestion` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `body_measurements`
--

CREATE TABLE `body_measurements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `body_fat_percent` decimal(5,2) DEFAULT NULL,
  `waist_cm` decimal(6,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `body_measurements`
--

INSERT INTO `body_measurements` (`id`, `user_id`, `recorded_at`, `weight_kg`, `body_fat_percent`, `waist_cm`, `notes`, `created_at`) VALUES
(1, 4, '2026-11-22 00:00:00', 102.00, 40.00, 40.00, NULL, '2026-11-22 18:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

CREATE TABLE `coaches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `specialization` varchar(180) NOT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` tinyint(3) UNSIGNED DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `name`, `email`, `specialization`, `bio`, `experience_years`, `fee`, `photo`, `is_active`, `created_at`) VALUES
(1, 'Ashik Sir', 'ashik@gmail.com', 'Chest Expert', NULL, 3, 1500.00, NULL, 1, '2026-11-21 19:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `coach_availability`
--

CREATE TABLE `coach_availability` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coach_id` bigint(20) UNSIGNED NOT NULL,
  `weekday` tinyint(3) UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_availability`
--

INSERT INTO `coach_availability` (`id`, `coach_id`, `weekday`, `start_time`, `end_time`) VALUES
(1, 1, 7, '06:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `coach_bookings`
--

CREATE TABLE `coach_bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `coach_id` bigint(20) UNSIGNED NOT NULL,
  `session_start` datetime NOT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `user_message` text DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coach_bookings`
--

INSERT INTO `coach_bookings` (`id`, `user_id`, `coach_id`, `session_start`, `duration_minutes`, `status`, `user_message`, `admin_message`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 1, 1, '2026-11-22 16:00:00', 60, 'completed', NULL, NULL, '2026-11-21 19:51:07', '2026-11-22 01:36:55', 2);

-- --------------------------------------------------------

--
-- Table structure for table `diet_plans`
--

CREATE TABLE `diet_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `fitness_level` enum('beginner','intermediate','advanced','expert') NOT NULL,
  `access_type` enum('free','premium') NOT NULL DEFAULT 'free',
  `dietary_preference` varchar(40) DEFAULT NULL,
  `guidance` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `title`, `description`, `fitness_level`, `access_type`, `dietary_preference`, `guidance`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'oats milk', NULL, 'beginner', 'free', 'no_preference', NULL, 0, 2, '2026-11-22 17:22:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed','not_configured') NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `user_id`, `recipient`, `subject`, `status`, `error_message`, `created_at`) VALUES
(1, 1, 'sunnyshah35350@gmail.com', 'FitTrack coaching booking update', 'not_configured', NULL, '2026-11-21 19:51:35'),
(2, 1, 'sunnyshah35350@gmail.com', 'FitTrack coaching booking update', 'not_configured', NULL, '2026-11-21 19:51:55'),
(3, 1, 'sunnyshah35350@gmail.com', 'Your FitTrack subscription is approved', 'not_configured', NULL, '2026-11-21 19:54:49'),
(4, 3, 'jeewanale48@gmail.com', 'Your FitTrack subscription is approved', 'not_configured', NULL, '2026-11-22 17:21:54'),
(5, 2, 'rishishahm1887@gmail.com', 'New Premium subscription request - rose', 'not_configured', 'Email is disabled in config/mail.php.', '2026-11-22 18:25:04'),
(6, 4, 'ca7681980@gmail.com', 'Your FitTrack subscription is approved', 'not_configured', 'Email is disabled in config/mail.php.', '2026-11-22 18:25:44');

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `muscle_group` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `category`, `muscle_group`, `instructions`, `created_at`) VALUES
(1, 'Push-up', 'Strength', 'Chest', 'Keep the body straight and lower with control.', '2026-11-21 19:23:16'),
(2, 'Bodyweight squat', 'Strength', 'Legs', 'Keep the feet stable and lower to a comfortable depth.', '2026-11-21 19:23:16'),
(3, 'Plank', 'Strength', 'Core', 'Keep the body aligned and brace the core.', '2026-11-21 19:23:16'),
(4, 'Walking', 'Cardio', 'Full body', 'Walk at a comfortable, steady pace.', '2026-11-21 19:23:16'),
(5, 'Running', 'Cardio', 'Full body', 'Choose a pace appropriate to your fitness level.', '2026-11-21 19:23:16'),
(6, 'Cycling', 'Cardio', 'Legs', 'Maintain a comfortable cadence and safe posture.', '2026-11-21 19:23:16'),
(7, 'Hamstring stretch', 'Flexibility', 'Hamstrings', 'Stretch gently without bouncing.', '2026-11-21 19:23:16');

-- --------------------------------------------------------

--
-- Table structure for table `fitness_goals`
--

CREATE TABLE `fitness_goals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `goal_type` enum('target_weight','workout_frequency','workout_minutes','other') NOT NULL DEFAULT 'other',
  `starting_value` decimal(10,2) DEFAULT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `start_date` date NOT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fitness_goals`
--

INSERT INTO `fitness_goals` (`id`, `user_id`, `title`, `goal_type`, `starting_value`, `target_value`, `unit`, `start_date`, `deadline`, `status`, `created_at`) VALUES
(1, 4, 'muscle gain', 'target_weight', 0.00, 100.00, 'kg', '2026-11-23', '2026-11-30', 'completed', '2026-11-22 18:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `food_logs`
--

CREATE TABLE `food_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `meal_name` varchar(150) NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL DEFAULT 'snack',
  `calories` decimal(8,2) DEFAULT NULL,
  `protein_g` decimal(7,2) DEFAULT NULL,
  `carbohydrates_g` decimal(7,2) DEFAULT NULL,
  `fat_g` decimal(7,2) DEFAULT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_memberships`
--

CREATE TABLE `gym_memberships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','rejected') NOT NULL DEFAULT 'pending',
  `status` enum('pending','active','expired','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_memberships`
--

INSERT INTO `gym_memberships` (`id`, `user_id`, `plan_id`, `start_date`, `end_date`, `amount`, `payment_status`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-11-22', '2026-12-22', 1500.00, 'paid', 'active', '2026-11-21 19:53:29'),
(2, 3, 1, '2026-11-22', '2026-12-22', 1500.00, 'paid', 'active', '2026-11-22 17:20:47'),
(3, 3, 3, '2026-11-22', '2026-11-22', 5000.00, 'rejected', 'rejected', '2026-11-22 17:39:34'),
(4, 4, 1, '2026-11-22', '2026-12-22', 1500.00, 'paid', 'active', '2026-11-22 18:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `gym_membership_plans`
--

CREATE TABLE `gym_membership_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration_months` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_membership_plans`
--

INSERT INTO `gym_membership_plans` (`id`, `name`, `duration_months`, `price`, `description`, `status`, `created_at`, `is_active`) VALUES
(1, '1-month package', 1, 1500.00, NULL, 'active', '2026-11-21 19:48:30', 1),
(2, '3-month package', 3, 2500.00, NULL, 'active', '2026-11-21 19:48:42', 1),
(3, '6-month package', 6, 5000.00, NULL, 'active', '2026-11-21 19:48:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Coaching booking approved', 'Your coaching request for 2026-11-22 16:00:00 is approved. ', 0, '2026-11-21 19:51:35'),
(2, 1, 'Coaching booking completed', 'Your coaching request for 2026-11-22 16:00:00 is completed. ', 0, '2026-11-21 19:51:55'),
(3, 1, 'Subscription approved', 'Your quarterly Premium subscription has been approved. Access starts November 21, 2026 and expires February 21, 2027.', 0, '2026-11-21 19:54:49'),
(4, 3, 'Subscription approved', 'Your monthly Premium subscription has been approved. Access starts November 22, 2026 and expires December 22, 2026.', 0, '2026-11-22 17:21:54'),
(5, 4, 'Subscription approved', 'Your monthly Premium subscription has been approved. Access starts November 22, 2026 and expires December 22, 2026.', 0, '2026-11-22 18:25:44');

-- --------------------------------------------------------

--
-- Table structure for table `plan_exercises`
--

CREATE TABLE `plan_exercises` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `exercise_id` bigint(20) UNSIGNED NOT NULL,
  `planned_sets` int(10) UNSIGNED DEFAULT NULL,
  `planned_reps` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `premium_videos`
--

CREATE TABLE `premium_videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `youtube_id` varchar(20) NOT NULL,
  `category` enum('workout','diet','general') NOT NULL DEFAULT 'workout',
  `fitness_level` enum('all','beginner','intermediate','expert') NOT NULL DEFAULT 'all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `premium_videos`
--

INSERT INTO `premium_videos` (`id`, `title`, `description`, `youtube_id`, `category`, `fitness_level`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'Chest', NULL, 'KEXZREwKipY', 'workout', 'beginner', 1, 2, '2026-11-22 18:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_requests`
--

CREATE TABLE `subscription_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan` enum('monthly','quarterly','yearly') NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_reference` varchar(120) DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_reason` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `starts_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_requests`
--

INSERT INTO `subscription_requests` (`id`, `user_id`, `plan`, `amount`, `payment_reference`, `proof_file`, `status`, `admin_reason`, `requested_at`, `reviewed_at`, `reviewed_by`, `starts_on`, `expires_on`) VALUES
(1, 1, 'quarterly', NULL, NULL, '62fe51e611262e9813269659ea92c5f53d06.jpg', 'approved', NULL, '2026-11-21 19:54:39', '2026-11-22 01:39:49', 2, '2026-11-21', '2027-02-21'),
(2, 3, 'monthly', NULL, NULL, 'b6c6770c47393a28c3159f5e07e77c1bc367.png', 'approved', NULL, '2026-11-22 17:21:36', '2026-11-22 23:06:54', 2, '2026-11-22', '2026-12-22'),
(3, 4, 'monthly', NULL, NULL, '672750a971d61d09cfe5a9720b0e105fb5d3.png', 'approved', NULL, '2026-11-22 18:25:04', '2026-11-23 00:10:44', 2, '2026-11-22', '2026-12-22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `fitness_level` enum('beginner','intermediate','advanced','expert') NOT NULL DEFAULT 'beginner',
  `fitness_goal` varchar(40) DEFAULT 'general_fitness',
  `dietary_preference` varchar(40) DEFAULT 'no_preference',
  `food_allergies` varchar(500) DEFAULT NULL,
  `available_days` text DEFAULT NULL,
  `subscription_plan` enum('monthly','quarterly','yearly') DEFAULT NULL,
  `subscription_status` enum('normal','pending','active','expired') NOT NULL DEFAULT 'normal',
  `subscription_expires_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `age`, `gender`, `height_cm`, `weight_kg`, `fitness_level`, `fitness_goal`, `dietary_preference`, `food_allergies`, `available_days`, `subscription_plan`, `subscription_status`, `subscription_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Sunny Shah', 'sunnyshah35350@gmail.com', '$2y$10$XX8dX2t4aY.elgKWmVBbkukXXHTnoaNbsHL69lwSTEwR3rf7w1xbq', 'user', 22, 'male', NULL, NULL, 'beginner', 'general_fitness', 'no_preference', NULL, '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Fri\"]', 'quarterly', 'active', '2027-02-21', '2026-11-21 19:23:44', '2026-11-21 19:54:49'),
(2, 'Rishi Shah', 'rishishahm1887@gmail.com', '$2y$10$aXClif1Gd5sytXFTGDef5e2GF/xjimhH3QuWk0VseU/27OicZZA02', 'admin', 24, 'male', NULL, NULL, 'beginner', 'general_fitness', 'no_preference', NULL, '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Fri\"]', NULL, 'normal', NULL, '2026-11-21 19:45:30', '2026-11-21 19:47:10'),
(3, 'ashik', 'jeewanale48@gmail.com', '$2y$10$5a/ty57sEDbr9qzQDBOY3OJTUJW0u90ITTfLPI2PLKzEPe2norKM6', 'user', 24, 'male', NULL, NULL, 'beginner', 'general_fitness', 'no_preference', NULL, '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Fri\"]', 'monthly', 'active', '2026-12-22', '2026-11-22 17:19:11', '2026-11-22 17:21:54'),
(4, 'rose', 'ca7681980@gmail.com', '$2y$10$oh.p7cIiMAZ4EQqY3KHBUe2d/d8qGWDPzu0PM2PhRsHUHw.ZydHne', 'user', 20, 'female', NULL, NULL, 'beginner', 'general_fitness', 'no_preference', NULL, '[\"Mon\",\"Tue\",\"Wed\",\"Thu\",\"Fri\"]', 'monthly', 'active', '2026-12-22', '2026-11-22 18:05:03', '2026-11-22 18:25:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_plan_assignments`
--

CREATE TABLE `user_plan_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `diet_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `workout_template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `water_logs`
--

CREATE TABLE `water_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `amount_ml` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

CREATE TABLE `workout_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_plans`
--

INSERT INTO `workout_plans` (`id`, `user_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 'Leg_Day', NULL, '2026-11-21 19:55:09'),
(2, 1, 'Chest Day', NULL, '2026-11-21 19:55:17'),
(3, 1, 'Squat', NULL, '2026-11-22 17:09:35'),
(4, 3, 'Squat', NULL, '2026-11-22 17:42:32');

-- --------------------------------------------------------

--
-- Table structure for table `workout_sessions`
--

CREATE TABLE `workout_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_date` datetime NOT NULL DEFAULT current_timestamp(),
  `duration_minutes` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_sessions`
--

INSERT INTO `workout_sessions` (`id`, `user_id`, `plan_id`, `session_date`, `duration_minutes`, `notes`, `created_at`) VALUES
(1, 1, 2, '2026-11-21 00:00:00', NULL, 'Workout session', '2026-11-21 19:55:43'),
(2, 1, 2, '2026-11-21 00:00:00', 30, 'Workout session', '2026-11-21 19:56:04'),
(3, 1, 3, '2026-11-24 00:00:00', 60, 'Workout session', '2026-11-22 17:10:30'),
(4, 4, NULL, '2026-11-22 00:00:00', 30, 'Workout session', '2026-11-22 18:44:47'),
(5, 4, NULL, '2026-11-22 00:00:00', NULL, 'Workout session', '2026-11-22 18:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `workout_sets`
--

CREATE TABLE `workout_sets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `exercise_id` bigint(20) UNSIGNED NOT NULL,
  `set_number` int(10) UNSIGNED NOT NULL,
  `repetitions` int(10) UNSIGNED DEFAULT NULL,
  `weight_kg` decimal(7,2) DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `distance_km` decimal(8,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_templates`
--

CREATE TABLE `workout_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `fitness_level` enum('beginner','intermediate','advanced','expert') NOT NULL,
  `access_type` enum('free','premium') NOT NULL DEFAULT 'free',
  `schedule_text` text DEFAULT NULL,
  `guidance` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_templates`
--

INSERT INTO `workout_templates` (`id`, `title`, `description`, `fitness_level`, `access_type`, `schedule_text`, `guidance`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'Chest Plan', NULL, 'beginner', 'free', NULL, NULL, 0, 2, '2026-11-21 19:53:07'),
(2, 'squat', NULL, 'beginner', 'premium', NULL, NULL, 0, 2, '2026-11-22 17:18:53'),
(3, 'Chest', NULL, 'beginner', 'free', NULL, 'Exercise	Sets	                         Reps\r\n1. Flat Barbell Bench Press	3	8–10\r\n2. Incline Dumbbell Press	3	10–12\r\n3. Decline Bench Press	3	10–12\r\n4. Dumbbell Chest Fly	3	12\r\n5. Cable Chest Fly	3	12–15\r\n6. Push-ups	2	Maximum controlled reps', 0, 2, '2026-11-22 17:27:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_suggestions`
--
ALTER TABLE `admin_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_suggestion_user` (`user_id`),
  ADD KEY `fk_suggestion_admin` (`admin_id`);

--
-- Indexes for table `body_measurements`
--
ALTER TABLE `body_measurements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_measurements_user_date` (`user_id`,`recorded_at`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_availability_coach` (`coach_id`);

--
-- Indexes for table `coach_bookings`
--
ALTER TABLE `coach_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_reviewer` (`reviewed_by`),
  ADD KEY `idx_coach_slot` (`coach_id`,`session_start`,`status`);

--
-- Indexes for table `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_diet_admin` (`created_by`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_log_user` (`user_id`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_goals_user` (`user_id`);

--
-- Indexes for table `food_logs`
--
ALTER TABLE `food_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_food_user_date` (`user_id`,`logged_at`);

--
-- Indexes for table `gym_memberships`
--
ALTER TABLE `gym_memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gym_member_plan` (`plan_id`),
  ADD KEY `idx_gym_member_status` (`user_id`,`status`,`end_date`);

--
-- Indexes for table `gym_membership_plans`
--
ALTER TABLE `gym_membership_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `plan_exercises`
--
ALTER TABLE `plan_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plan_exercises_plan` (`plan_id`),
  ADD KEY `fk_plan_exercises_exercise` (`exercise_id`);

--
-- Indexes for table `premium_videos`
--
ALTER TABLE `premium_videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_requests`
--
ALTER TABLE `subscription_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subscription_user` (`user_id`),
  ADD KEY `fk_subscription_reviewer` (`reviewed_by`),
  ADD KEY `idx_subscription_status` (`status`,`requested_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_plan_assignments`
--
ALTER TABLE `user_plan_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_assignment_user` (`user_id`),
  ADD KEY `fk_assignment_diet` (`diet_plan_id`),
  ADD KEY `fk_assignment_template` (`workout_template_id`),
  ADD KEY `fk_assignment_admin` (`assigned_by`);

--
-- Indexes for table `water_logs`
--
ALTER TABLE `water_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_water_user_date` (`user_id`,`log_date`);

--
-- Indexes for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_workout_plans_user` (`user_id`);

--
-- Indexes for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sessions_plan` (`plan_id`),
  ADD KEY `idx_sessions_user_date` (`user_id`,`session_date`);

--
-- Indexes for table `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sets_session` (`session_id`),
  ADD KEY `fk_sets_exercise` (`exercise_id`);

--
-- Indexes for table `workout_templates`
--
ALTER TABLE `workout_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_template_admin` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_suggestions`
--
ALTER TABLE `admin_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `body_measurements`
--
ALTER TABLE `body_measurements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coach_availability`
--
ALTER TABLE `coach_availability`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coach_bookings`
--
ALTER TABLE `coach_bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diet_plans`
--
ALTER TABLE `diet_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `food_logs`
--
ALTER TABLE `food_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_memberships`
--
ALTER TABLE `gym_memberships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gym_membership_plans`
--
ALTER TABLE `gym_membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plan_exercises`
--
ALTER TABLE `plan_exercises`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `premium_videos`
--
ALTER TABLE `premium_videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscription_requests`
--
ALTER TABLE `subscription_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_plan_assignments`
--
ALTER TABLE `user_plan_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `water_logs`
--
ALTER TABLE `water_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `workout_sets`
--
ALTER TABLE `workout_sets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workout_templates`
--
ALTER TABLE `workout_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_suggestions`
--
ALTER TABLE `admin_suggestions`
  ADD CONSTRAINT `fk_suggestion_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_suggestion_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `body_measurements`
--
ALTER TABLE `body_measurements`
  ADD CONSTRAINT `fk_measurements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD CONSTRAINT `fk_availability_coach` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_bookings`
--
ALTER TABLE `coach_bookings`
  ADD CONSTRAINT `fk_booking_coach` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`),
  ADD CONSTRAINT `fk_booking_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD CONSTRAINT `fk_diet_admin` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `fk_email_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  ADD CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `food_logs`
--
ALTER TABLE `food_logs`
  ADD CONSTRAINT `fk_food_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gym_memberships`
--
ALTER TABLE `gym_memberships`
  ADD CONSTRAINT `fk_gym_member_plan` FOREIGN KEY (`plan_id`) REFERENCES `gym_membership_plans` (`id`),
  ADD CONSTRAINT `fk_gym_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plan_exercises`
--
ALTER TABLE `plan_exercises`
  ADD CONSTRAINT `fk_plan_exercises_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`),
  ADD CONSTRAINT `fk_plan_exercises_plan` FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_requests`
--
ALTER TABLE `subscription_requests`
  ADD CONSTRAINT `fk_subscription_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_plan_assignments`
--
ALTER TABLE `user_plan_assignments`
  ADD CONSTRAINT `fk_assignment_admin` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assignment_diet` FOREIGN KEY (`diet_plan_id`) REFERENCES `diet_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assignment_template` FOREIGN KEY (`workout_template_id`) REFERENCES `workout_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assignment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `water_logs`
--
ALTER TABLE `water_logs`
  ADD CONSTRAINT `fk_water_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD CONSTRAINT `fk_workout_plans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD CONSTRAINT `fk_sessions_plan` FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD CONSTRAINT `fk_sets_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`),
  ADD CONSTRAINT `fk_sets_session` FOREIGN KEY (`session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_templates`
--
ALTER TABLE `workout_templates`
  ADD CONSTRAINT `fk_template_admin` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
