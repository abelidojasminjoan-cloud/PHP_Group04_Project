-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 30, 2026 at 08:50 PM
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
-- Database: `purevia_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_lockout_logs`
--

CREATE TABLE `account_lockout_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `source_attempt_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name_snapshot` varchar(210) NOT NULL,
  `email_snapshot` varchar(255) NOT NULL,
  `failed_attempts` smallint(5) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser` varchar(255) NOT NULL,
  `locked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `ip_address`, `browser`, `created_at`) VALUES
(1, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 12:23:44'),
(2, 1, 'CREATE', 'Users', '2', 'Created pending staff account for jasminabelido475@gmail.com', '::1', 'Chrome 156 · Windows', '2026-10-30 12:27:56'),
(3, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 12:29:48'),
(4, 2, 'LOGIN', 'Auth', '2', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 12:32:39'),
(5, 2, 'LOGOUT', 'Auth', '2', 'Signed out from the staff session', '::1', 'Chrome 156 · Windows', '2026-10-30 12:32:45'),
(6, 2, 'LOGIN', 'Auth', '2', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 12:33:10'),
(7, 2, 'LOGOUT', 'Auth', '2', 'Signed out from the staff session', '::1', 'Chrome 156 · Windows', '2026-10-30 12:38:46'),
(8, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 12:48:56'),
(9, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 13:01:54'),
(10, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 13:02:30'),
(11, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 13:30:32'),
(12, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 14:26:52'),
(13, 1, 'CREATE', 'Categories', '1', 'Added category \"Cleansers\".', '::1', NULL, '2026-10-30 14:29:14'),
(14, 1, 'CREATE', 'Categories', '2', 'Added category \"Toners\".', '::1', NULL, '2026-10-30 14:29:21'),
(15, 1, 'CREATE', 'Categories', '3', 'Added category \"Serums\".', '::1', NULL, '2026-10-30 14:29:26'),
(16, 1, 'CREATE', 'Categories', '4', 'Added category \"Moisturizers\".', '::1', NULL, '2026-10-30 14:29:33'),
(17, 1, 'CREATE', 'Categories', '5', 'Added category \"Sunscreen\".', '::1', NULL, '2026-10-30 14:29:40'),
(18, 1, 'CREATE', 'Ingredients', '1', 'Added ingredient \"Niacinamide\".', '::1', NULL, '2026-10-30 14:30:01'),
(19, 1, 'CREATE', 'Ingredients', '2', 'Added ingredient \"Hyaluronic Acid\".', '::1', NULL, '2026-10-30 14:30:12'),
(20, 1, 'CREATE', 'Ingredients', '3', 'Added ingredient \"Retinol\".', '::1', NULL, '2026-10-30 14:30:21'),
(21, 1, 'CREATE', 'Ingredients', '4', 'Added ingredient \"Vitamin C\".', '::1', NULL, '2026-10-30 14:30:30'),
(22, 1, 'CREATE', 'Ingredients', '5', 'Added ingredient \"Salicylic Acid\".', '::1', NULL, '2026-10-30 14:30:39'),
(23, 1, 'CREATE', 'Ingredients', '6', 'Added ingredient \"Glycolic Acid\".', '::1', NULL, '2026-10-30 14:30:48'),
(24, 1, 'CREATE', 'Ingredients', '7', 'Added ingredient \"Ceramides\".', '::1', NULL, '2026-10-30 14:30:56'),
(25, 1, 'CREATE', 'Ingredients', '8', 'Added ingredient \"Centella Asiatica\".', '::1', NULL, '2026-10-30 14:31:04'),
(26, 1, 'CREATE', 'Ingredients', '9', 'Added ingredient \"Peptides\".', '::1', NULL, '2026-10-30 14:31:15'),
(27, 1, 'CREATE', 'Skin Concerns', '1', 'Added concern \"Acne\".', '::1', NULL, '2026-10-30 14:31:26'),
(28, 1, 'CREATE', 'Skin Concerns', '2', 'Added concern \"Aging\".', '::1', NULL, '2026-10-30 14:31:31'),
(29, 1, 'CREATE', 'Skin Concerns', '3', 'Added concern \"Brightening\".', '::1', NULL, '2026-10-30 14:31:37'),
(30, 1, 'CREATE', 'Skin Concerns', '4', 'Added concern \"Dark Spots\".', '::1', NULL, '2026-10-30 14:31:43'),
(31, 1, 'CREATE', 'Skin Concerns', '5', 'Added concern \"Dryness\".', '::1', NULL, '2026-10-30 14:31:50'),
(32, 1, 'CREATE', 'Skin Concerns', '6', 'Added concern \"Enlarged Pores\".', '::1', NULL, '2026-10-30 14:31:55'),
(33, 1, 'CREATE', 'Skin Concerns', '7', 'Added concern \"Hyperpigmentation\".', '::1', NULL, '2026-10-30 14:32:01'),
(34, 1, 'CREATE', 'Skin Concerns', '8', 'Added concern \"Redness\".', '::1', NULL, '2026-10-30 14:32:07'),
(35, 1, 'CREATE', 'Skin Types', '1', 'Create Skin Types record.', '::1', NULL, '2026-10-30 14:32:33'),
(36, 1, 'CREATE', 'Skin Types', '2', 'Create Skin Types record.', '::1', NULL, '2026-10-30 14:32:38'),
(37, 1, 'CREATE', 'Skin Types', '3', 'Create Skin Types record.', '::1', NULL, '2026-10-30 14:32:44'),
(38, 1, 'CREATE', 'Skin Types', '4', 'Create Skin Types record.', '::1', NULL, '2026-10-30 14:32:51'),
(39, 1, 'CREATE', 'Skin Types', '5', 'Create Skin Types record.', '::1', NULL, '2026-10-30 14:32:59'),
(40, 1, 'CREATE', 'Suitability Tags', '1', 'Create Suitability Tags record.', '::1', NULL, '2026-10-30 14:33:12'),
(41, 1, 'CREATE', 'Suitability Tags', '2', 'Create Suitability Tags record.', '::1', NULL, '2026-10-30 14:33:20'),
(42, 1, 'CREATE', 'Suitability Tags', '3', 'Create Suitability Tags record.', '::1', NULL, '2026-10-30 14:33:30'),
(43, 1, 'CREATE', 'Suitability Tags', '4', 'Create Suitability Tags record.', '::1', NULL, '2026-10-30 14:33:35'),
(44, 1, 'CREATE', 'Suitability Tags', '5', 'Create Suitability Tags record.', '::1', NULL, '2026-10-30 14:33:41'),
(45, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 14:34:42'),
(46, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 15:26:10'),
(47, 1, 'CREATE', 'Products', '1', 'Created product \"PureVia Aloe Vera Soothe Moisturizer\" (PRD-000001).', '::1', NULL, '2026-10-30 15:38:02'),
(48, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 15:39:36'),
(49, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 16:13:13'),
(50, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 16:16:11'),
(51, 2, 'LOGIN', 'Auth', '2', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 16:16:53'),
(52, 2, 'LOGOUT', 'Auth', '2', 'Signed out from the staff session', '::1', 'Chrome 156 · Windows', '2026-10-30 16:17:26'),
(53, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 16:17:55'),
(54, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 16:19:05'),
(55, 2, 'LOGIN', 'Auth', '2', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 16:19:25'),
(56, 2, 'LOGOUT', 'Auth', '2', 'Signed out from the staff session', '::1', 'Chrome 156 · Windows', '2026-10-30 16:20:08'),
(57, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 16:20:25'),
(58, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 17:00:56'),
(59, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 17:16:35'),
(60, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 17:18:15'),
(61, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 17:22:19'),
(62, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 17:22:29'),
(63, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 17:30:25'),
(64, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 17:33:35'),
(65, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 17:51:04'),
(66, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 18:14:10'),
(67, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-30 18:34:55'),
(68, 3, 'LOGIN', 'Auth', '3', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 18:36:26'),
(69, 3, 'LOGOUT', 'Auth', '3', 'Signed out from the customer session', '::1', 'Chrome 156 · Windows', '2026-10-30 18:39:56'),
(70, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-30 18:46:07'),
(71, 1, 'CREATE', 'Products', '2', 'Created product \"PureVia Chamomile Comfort Serum\" (PRD-000002).', '::1', NULL, '2026-10-30 18:52:06'),
(72, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Edge 156 · Windows', '2026-10-30 19:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cart_id` int(10) UNSIGNED NOT NULL,
  `product_variant_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cleansers', 'Facial cleansers and micellar waters', 'active', '2026-10-30 14:29:14', '2026-10-30 14:29:14'),
(2, 'Toners', 'Balancing and hydrating toners', 'active', '2026-10-30 14:29:20', '2026-10-30 14:29:20'),
(3, 'Serums', 'Targeted treatment serums', 'active', '2026-10-30 14:29:26', '2026-10-30 14:29:26'),
(4, 'Moisturizers', 'Day and night creams', 'active', '2026-10-30 14:29:33', '2026-10-30 14:29:33'),
(5, 'Sunscreen', 'SPF protection products', 'active', '2026-10-30 14:29:40', '2026-10-30 14:29:40');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_tokens`
--

INSERT INTO `email_verification_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '3547a93e6eeb1cc163a7c5dde10983e50b2d0235d217feb5a68039616cb0e5b6', '2026-10-30 20:47:39', '2026-10-30 20:17:57', '2026-10-30 12:17:39'),
(2, 2, '7bbb4c313215229db943e6aafc0ce13dea3f48faf7203ff92ff6d102bc79df80', '2026-10-30 21:02:06', '2026-10-30 20:32:19', '2026-10-30 12:32:06'),
(3, 3, 'eaad3491c1aada7a960af385b5790c4b1304e03adb1f885e4275785e917ade90', '2026-10-31 03:06:07', '2026-10-31 02:36:20', '2026-10-30 18:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `ingredient_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `common_in` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `ingredient_name`, `description`, `common_in`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Niacinamide', 'Vitamin B3 that minimizes pores and evens skin tone', 'Serums, Toners', 'active', '2026-10-30 14:30:01', '2026-10-30 14:30:01'),
(2, 'Hyaluronic Acid', 'Powerful humectant that attracts moisture', 'Serums, Moisturizers', 'active', '2026-10-30 14:30:12', '2026-10-30 14:30:12'),
(3, 'Retinol', 'Vitamin A derivative for anti-aging', 'Serums, Night Creams', 'active', '2026-10-30 14:30:21', '2026-10-30 14:30:21'),
(4, 'Vitamin C', 'Antioxidant brightener', 'Serums, Toners', 'active', '2026-10-30 14:30:29', '2026-10-30 14:30:29'),
(5, 'Salicylic Acid', 'BHA that exfoliates inside pores', 'Cleansers, Toners', 'active', '2026-10-30 14:30:39', '2026-10-30 14:30:39'),
(6, 'Glycolic Acid', 'AHA that resurfaces skin texture', 'Toners, Exfoliants', 'active', '2026-10-30 14:30:48', '2026-10-30 14:30:48'),
(7, 'Ceramides', 'Lipids that strengthen skin barrier', 'Moisturizers, Cleansers', 'active', '2026-10-30 14:30:56', '2026-10-30 14:30:56'),
(8, 'Centella Asiatica', 'Calming botanical for sensitive skin', 'Toners, Creams', 'active', '2026-10-30 14:31:04', '2026-10-30 14:31:04'),
(9, 'Peptides', 'Amino acid chains that signal collagen production', 'Serums, Eye Creams', 'active', '2026-10-30 14:31:15', '2026-10-30 14:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_adjustments`
--

CREATE TABLE `inventory_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `adjustment_type` enum('add','remove') NOT NULL,
  `reason` varchar(40) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `stock_before` int(10) UNSIGNED NOT NULL,
  `stock_after` int(10) UNSIGNED NOT NULL,
  `threshold_before` int(10) UNSIGNED NOT NULL,
  `threshold_after` int(10) UNSIGNED NOT NULL,
  `notes` text NOT NULL,
  `request_key` char(64) NOT NULL,
  `request_fingerprint` char(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `email_attempted` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 0,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `email_attempted`, `ip_address`, `browser`, `successful`, `failure_reason`, `attempted_at`) VALUES
(1, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 12:23:44'),
(2, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 0, 'Staff email verification required.', '2026-10-30 12:31:57'),
(3, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 12:32:39'),
(4, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 12:33:10'),
(5, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 12:48:56'),
(6, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 13:02:30'),
(7, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 14:26:52'),
(8, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 14:34:42'),
(9, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 15:26:10'),
(10, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 16:13:13'),
(11, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-30 16:16:19'),
(12, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 16:16:53'),
(13, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 16:17:55'),
(14, 2, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 16:19:25'),
(15, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 16:20:25'),
(16, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 17:00:56'),
(17, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 17:18:15'),
(18, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 17:22:29'),
(19, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 17:33:35'),
(20, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 18:14:10'),
(21, 3, 'jasjoan16@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 18:36:26'),
(22, 1, 'purevia10@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-30 18:46:07'),
(23, 1, 'purevia10@gmail.com', '::1', 'Edge', 1, NULL, '2026-10-30 19:48:02');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Philippines',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','confirmed','preparing','shipping','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancellation_reason` varchar(100) DEFAULT NULL,
  `cancellation_details` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `order_return_items`
--

CREATE TABLE `order_return_items` (
  `return_request_id` int(10) UNSIGNED NOT NULL,
  `order_item_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_return_requests`
--

CREATE TABLE `order_return_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(100) NOT NULL,
  `resolution` enum('refund','replacement') NOT NULL,
  `details` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_method` enum('courier','dropoff') NOT NULL DEFAULT 'courier',
  `evidence_json` text DEFAULT NULL,
  `internal_note` text DEFAULT NULL,
  `resolution_reference` varchar(200) DEFAULT NULL,
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `restocked_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` enum('login','email_verification','password_reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attempt_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `user_id`, `otp_hash`, `purpose`, `expires_at`, `used_at`, `created_at`, `attempt_count`) VALUES
(1, 1, '$2y$10$xtiZHn9miPDtcU4C0iClXuszSesrCIJ3FVLs8BW5HvQsmpu.Ysi02', 'email_verification', '2026-10-30 20:27:23', '2026-10-30 20:17:39', '2026-10-30 12:17:23', 0),
(2, 2, '$2y$10$AJLZbxECZ97oJKM0Dwm.Ru/JWEMDQeY8.KGnWiioy9d3.EcLa9YiK', 'email_verification', '2026-10-30 20:41:52', '2026-10-30 20:32:06', '2026-10-30 12:31:52', 0),
(3, 3, '$2y$10$fd8dBIP4m4T2Yj0S050FeuEGnwPymdzdYFtJlvol4e41E.QPQcX.y', 'email_verification', '2026-10-31 02:45:40', '2026-10-31 02:36:07', '2026-10-30 18:35:40', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_limits`
--

CREATE TABLE `password_reset_limits` (
  `key_hash` char(64) NOT NULL,
  `requests` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `payment_method` enum('cod','online') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paypal_checkouts`
--

CREATE TABLE `paypal_checkouts` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `request_key` char(40) NOT NULL,
  `environment` enum('sandbox','live') NOT NULL,
  `paypal_order_id` varchar(32) DEFAULT NULL,
  `approval_url` varchar(512) DEFAULT NULL,
  `capture_id` varchar(64) DEFAULT NULL,
  `items_snapshot` longtext NOT NULL,
  `state` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `usage_instructions` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `product_name`, `product_code`, `description`, `usage_instructions`, `price`, `stock_quantity`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'PureVia Aloe Vera Soothe Moisturizer', 'PRD-000001', 'Hydrate and soothe your skin with PureVia Aloe Vera Soothe Moisturizer, featuring Aloe Vera Extract, a plant-based ingredient known for its moisturizing and calming properties. Designed for everyday skincare, this lightweight moisturizer helps replenish moisture, soothe dryness, and support the skin\'s natural protective barrier, leaving your skin feeling soft, smooth, and refreshed.', NULL, 79.00, 110, 'uploads/products/783c90dff38a6acb9fbf7966fc9a4bca.png', 'active', '2026-10-30 15:38:02', '2026-10-30 15:38:02'),
(2, 3, 'PureVia Chamomile Comfort Serum', 'PRD-000002', 'Inspired by the delicate chamomile flower, this lightweight facial serum features Chamomile Extract alongside moisture-loving ingredients designed to comfort dry-feeling skin, replenish hydration, and promote a naturally soft and radiant complexion.\r\nWhether your skin feels tight after cleansing, looks stressed from everyday environmental exposure, or needs a little extra care to maintain its natural glow, PureVia Chamomile Comfort brings a gentle, nature-inspired touch to your skincare routine. Its blend of hydrating and skin-conditioning ingredients focuses on supporting skin comfort and helping reduce the appearance of redness.', NULL, 79.00, 60, 'uploads/products/ab8d112102d233c7c55540c97b8075a4.png', 'active', '2026-10-30 18:52:06', '2026-10-30 18:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_benefits`
--

CREATE TABLE `product_benefits` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `benefit_text` varchar(500) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_benefits`
--

INSERT INTO `product_benefits` (`id`, `product_id`, `benefit_text`, `sort_order`) VALUES
(1, 1, 'Provides long-lasting hydration and moisture', 0),
(2, 1, 'Helps soothe dry and irritated skin', 1),
(3, 1, 'Supports and strengthens the skin\'s moisture barrier', 2),
(4, 1, 'Helps reduce dryness and tightness', 3),
(5, 1, 'Promotes softer, smoother, and healthier-looking skin', 4),
(6, 1, 'Contains Aloe Vera Extract', 5),
(7, 2, 'Helps soothe dry and irritated-feeling skin', 0),
(8, 2, 'Helps reduce the appearance of redness', 1),
(9, 2, 'Provides hydration and moisture', 2),
(10, 2, 'Supports the skin\'s natural moisture barrier', 3),
(11, 2, 'Helps maintain softness and smoothness', 4),
(12, 2, 'Promotes a refreshed and healthier-looking complexion', 5),
(13, 2, 'Features Chamomile Extract', 6);

-- --------------------------------------------------------

--
-- Table structure for table `product_concerns`
--

CREATE TABLE `product_concerns` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `concern_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_concerns`
--

INSERT INTO `product_concerns` (`product_id`, `concern_id`) VALUES
(1, 5),
(1, 8),
(2, 5),
(2, 8);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(2048) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`) VALUES
(1, 1, 'uploads/products/783c90dff38a6acb9fbf7966fc9a4bca.png', 0),
(2, 1, 'uploads/products/19eb6f082a60e9fc35bfe32fcf559db3.png', 1),
(3, 1, 'uploads/products/ba7ec42f82372b6f0efd82f5f03b86db.png', 2),
(4, 2, 'uploads/products/ab8d112102d233c7c55540c97b8075a4.png', 0),
(5, 2, 'uploads/products/e7e761f91a7b23a38c92aec73020bd60.png', 1),
(6, 2, 'uploads/products/62cd2aba3bd31c2852355b11b77215a7.png', 2),
(7, 2, 'uploads/products/d151cc5e39bd2d8e72ed845cce8e3fb4.png', 3);

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`product_id`, `ingredient_id`) VALUES
(1, 1),
(2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `review_text` text NOT NULL,
  `skin_type` varchar(50) DEFAULT NULL,
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `product_skin_types`
--

CREATE TABLE `product_skin_types` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `skin_type_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_skin_types`
--

INSERT INTO `product_skin_types` (`product_id`, `skin_type_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `product_suitability`
--

CREATE TABLE `product_suitability` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `ingredients_complete` tinyint(1) NOT NULL DEFAULT 0,
  `routine_role` enum('none','cleanser','moisturizer','sunscreen','treatment') NOT NULL DEFAULT 'none',
  `usage_period` enum('am','pm','both') NOT NULL DEFAULT 'both',
  `routine_instructions` text DEFAULT NULL,
  `gentle` tinyint(1) NOT NULL DEFAULT 0,
  `targeted_approved` tinyint(1) NOT NULL DEFAULT 0,
  `sunscreen_verified` tinyint(1) NOT NULL DEFAULT 0,
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_suitability`
--

INSERT INTO `product_suitability` (`product_id`, `ingredients_complete`, `routine_role`, `usage_period`, `routine_instructions`, `gentle`, `targeted_approved`, `sunscreen_verified`, `revision`, `updated_at`) VALUES
(1, 0, 'moisturizer', 'pm', 'Cleanse your face thoroughly.\r\nApply an appropriate amount of moisturizer to your face and neck.\r\nGently massage into the skin using upward circular motions.\r\nAllow the product to absorb completely.\r\nUse morning and evening as part of your daily skincare routine.', 0, 0, 0, 1, '2026-10-30 15:38:02'),
(2, 0, 'treatment', 'both', '', 0, 0, 0, 1, '2026-10-30 18:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_suitability_tags`
--

CREATE TABLE `product_suitability_tags` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `suitability_tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_suitability_tags`
--

INSERT INTO `product_suitability_tags` (`product_id`, `suitability_tag_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `size_capacity` varchar(100) NOT NULL,
  `variant_label` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `variant_sku` varchar(100) DEFAULT NULL,
  `low_stock_threshold` int(10) UNSIGNED NOT NULL DEFAULT 15,
  `last_restocked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size_capacity`, `variant_label`, `price`, `stock_quantity`, `created_at`, `updated_at`, `variant_sku`, `low_stock_threshold`, `last_restocked_at`) VALUES
(1, 1, '50g', 'Mini', 79.00, 30, '2026-10-30 15:38:02', '2026-10-30 15:38:02', NULL, 15, NULL),
(2, 1, '100g', 'Regular', 129.00, 30, '2026-10-30 15:38:02', '2026-10-30 15:38:02', NULL, 15, NULL),
(3, 1, '150g', NULL, 179.00, 25, '2026-10-30 15:38:02', '2026-10-30 15:38:02', NULL, 15, NULL),
(4, 1, '500g', NULL, 399.00, 25, '2026-10-30 15:38:02', '2026-10-30 15:38:02', NULL, 15, NULL),
(5, 2, '30ml', NULL, 79.00, 20, '2026-10-30 18:52:06', '2026-10-30 18:52:06', NULL, 15, NULL),
(6, 2, '50ml', NULL, 119.00, 20, '2026-10-30 18:52:06', '2026-10-30 18:52:06', NULL, 15, NULL),
(7, 2, '100ml', NULL, 199.00, 20, '2026-10-30 18:52:06', '2026-10-30 18:52:06', NULL, 15, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `review_images`
--

CREATE TABLE `review_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `review_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `review_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `vote` enum('up','down') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(3, 'customer'),
(2, 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `max_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `lockout_minutes` int(10) UNSIGNED NOT NULL DEFAULT 15,
  `session_timeout_minutes` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `password_min_length` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `require_uppercase` tinyint(1) NOT NULL DEFAULT 1,
  `require_lowercase` tinyint(1) NOT NULL DEFAULT 1,
  `require_number` tinyint(1) NOT NULL DEFAULT 1,
  `require_special_character` tinyint(1) NOT NULL DEFAULT 1,
  `password_expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `require_otp` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_uppercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_lowercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_special_character` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_assessments`
--

CREATE TABLE `skin_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `submission_key` char(64) NOT NULL,
  `assessment_method` enum('manual','guided') NOT NULL,
  `origin` enum('quiz','profile_edit') NOT NULL DEFAULT 'quiz',
  `skin_type_id` int(10) UNSIGNED DEFAULT NULL,
  `skin_type_label` varchar(100) DEFAULT NULL,
  `clarity` enum('clear','ambiguous','insufficient') NOT NULL,
  `profile_snapshot` mediumtext NOT NULL,
  `usefulness` tinyint(3) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_concerns`
--

CREATE TABLE `skin_concerns` (
  `id` int(10) UNSIGNED NOT NULL,
  `concern_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skin_concerns`
--

INSERT INTO `skin_concerns` (`id`, `concern_name`, `description`, `status`, `created_at`) VALUES
(1, 'Acne', 'Treatment and care for acne concerns', 'active', '2026-10-30 14:31:26'),
(2, 'Aging', 'Treatment and care for aging concerns', 'active', '2026-10-30 14:31:31'),
(3, 'Brightening', 'Treatment and care for brightening concerns', 'active', '2026-10-30 14:31:37'),
(4, 'Dark Spots', 'Treatment and care for dark spots concerns', 'active', '2026-10-30 14:31:43'),
(5, 'Dryness', 'Treatment and care for dryness concerns', 'active', '2026-10-30 14:31:50'),
(6, 'Enlarged Pores', 'Treatment and care for enlarged pores concerns', 'active', '2026-10-30 14:31:55'),
(7, 'Hyperpigmentation', 'Treatment and care for hyperpigmentation concerns', 'active', '2026-10-30 14:32:01'),
(8, 'Redness', 'Treatment and care for redness concerns', 'active', '2026-10-30 14:32:07');

-- --------------------------------------------------------

--
-- Table structure for table `skin_profiles`
--

CREATE TABLE `skin_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `skin_type_id` int(10) UNSIGNED DEFAULT NULL,
  `sensitivity_level` enum('low','moderate','high') DEFAULT NULL,
  `skin_type_source` enum('manual','finder') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assessment_method` enum('manual','guided') DEFAULT NULL,
  `sensitivity_score` decimal(5,4) DEFAULT NULL,
  `main_concern_id` int(10) UNSIGNED DEFAULT NULL,
  `ingredient_preference` enum('selected','none','unsure') NOT NULL DEFAULT 'none',
  `avoidance_reason` varchar(40) DEFAULT NULL,
  `routine_preference` enum('basic','targeted','unsure') NOT NULL DEFAULT 'basic',
  `assessment_answers` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_profile_concerns`
--

CREATE TABLE `skin_profile_concerns` (
  `skin_profile_id` int(10) UNSIGNED NOT NULL,
  `concern_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skin_questions`
--

CREATE TABLE `skin_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_key` varchar(40) NOT NULL,
  `prompt` varchar(1000) NOT NULL,
  `options_json` text NOT NULL,
  `source_key` varchar(40) NOT NULL,
  `rationale` varchar(2000) NOT NULL,
  `concern_id` int(10) UNSIGNED DEFAULT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `question_kind` enum('skin_type','sensitivity','clarification','observation') DEFAULT NULL,
  `answer_scores_json` text DEFAULT NULL,
  `concern_ids_json` text DEFAULT NULL,
  `skin_type_ids_json` text DEFAULT NULL,
  `concern_answer` varchar(1) NOT NULL DEFAULT 'A',
  `source_title` varchar(1000) DEFAULT NULL,
  `source_author` varchar(500) DEFAULT NULL,
  `source_url` varchar(2048) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skin_questions`
--

INSERT INTO `skin_questions` (`id`, `question_key`, `prompt`, `options_json`, `source_key`, `rationale`, `concern_id`, `is_core`, `status`, `revision`, `created_at`, `updated_at`, `question_kind`, `answer_scores_json`, `concern_ids_json`, `skin_type_ids_json`, `concern_answer`, `source_title`, `source_author`, `source_url`) VALUES
(1, 'q2', 'How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?', '[\"Tight or uncomfortable\",\"Comfortable and balanced\",\"Oily or shiny across most of my face\",\"Tight on my cheeks but oily around my nose or forehead\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(2, 'q3', 'How does your skin usually look by midday?', '[\"Mostly dry or matte, sometimes flaky\",\"Neither especially dry nor oily\",\"Shiny across most of my face\",\"Shiny mainly on my forehead, nose, and chin\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(3, 'q4', 'How would you describe your cheeks most days?', '[\"Dry, tight, or rough\",\"Comfortable and balanced\",\"Oily or shiny\",\"Usually normal or dry while my T-zone is oily\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(4, 'q5', 'How does your forehead, nose, and chin (T-zone) usually behave?', '[\"Often dry or tight\",\"Usually balanced\",\"Very oily, along with my cheeks\",\"Oily while my cheeks are noticeably less oily\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(5, 'q6', 'How frequently do you notice dry patches or flaking?', '[\"Frequently across several facial areas\",\"Rarely or never\",\"Rarely, because my face is usually oily\",\"Sometimes, especially on my cheeks\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(6, 'q7', 'Which description best matches your skin throughout a typical day?', '[\"Often feels dry and needs moisturizing\",\"Usually feels comfortable and balanced\",\"Produces noticeable oil over most of my face\",\"Has both oily and dry or normal areas\",\"Changes significantly or I\'m not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(7, 'q8', 'When you gently blot your face with clean tissue around midday, where do you usually notice oil?', '[\"Little or no oil anywhere\",\"A small amount distributed evenly\",\"Noticeable oil from both my T-zone and cheeks\",\"Oil mainly from my T-zone\",\"I haven\'t checked \\/ Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'skin_type', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1},\"E\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(8, 'q9', 'How often does your skin sting, burn, or become irritated after applying skincare products?', '[\"Never or rarely\",\"Occasionally\",\"Frequently\",\"Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'sensitivity', '{\"A\":{\"sensitivity\":0},\"B\":{\"sensitivity\":1},\"C\":{\"sensitivity\":2},\"D\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(9, 'q10', 'How often do you experience redness, itching, or discomfort after using new products?', '[\"Never or rarely\",\"Sometimes\",\"Frequently\",\"Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'sensitivity', '{\"A\":{\"sensitivity\":0},\"B\":{\"sensitivity\":1},\"C\":{\"sensitivity\":2},\"D\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(10, 'q11', 'How would you describe your skin\'s response to fragrances or strong skincare formulas?', '[\"Usually no noticeable reaction\",\"Occasional discomfort\",\"Frequent irritation or discomfort\",\"I haven\'t used these \\/ Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'sensitivity', '{\"A\":{\"sensitivity\":0},\"B\":{\"sensitivity\":1},\"C\":{\"sensitivity\":2},\"D\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(11, 'f1', 'Which areas of your face are consistently oily?', '[\"None \\u2014 my skin is mostly dry\",\"No specific area \\u2014 my skin feels balanced\",\"Most of my face, including cheeks\",\"Mainly my forehead, nose, and chin\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'clarification', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1}}', '[]', '[]', 'A', NULL, NULL, NULL),
(12, 'f2', 'Compared with your forehead and nose, how do your cheeks usually feel?', '[\"Both areas are dry\",\"Both areas are balanced\",\"Both areas are oily\",\"My cheeks are noticeably less oily\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.', NULL, 1, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'clarification', '{\"A\":{\"dry\":1},\"B\":{\"normal\":1},\"C\":{\"oily\":1},\"D\":{\"combination\":1}}', '[]', '[]', 'A', NULL, NULL, NULL),
(13, 'obs_breakouts', 'Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?', '[\"Yes\",\"No\",\"Not sure\"]', 'baumann', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', NULL, 0, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'observation', '{\"A\":[],\"B\":[],\"C\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(14, 'obs_marks', 'Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?', '[\"Yes\",\"No\",\"Not sure\"]', 'classification', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', NULL, 0, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'observation', '{\"A\":[],\"B\":[],\"C\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(15, 'obs_redness', 'Over the past four weeks, have you noticed recurring facial redness, itching or burning?', '[\"Yes\",\"No\",\"Not sure\"]', 'baumann', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', NULL, 0, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'observation', '{\"A\":[],\"B\":[],\"C\":[]}', '[]', '[]', 'A', NULL, NULL, NULL),
(16, 'obs_lines', 'Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?', '[\"Yes\",\"No\",\"Not sure\"]', 'classification', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', NULL, 0, 'active', 1, '2026-10-30 13:12:59', '2026-10-30 18:08:22', 'observation', '{\"A\":[],\"B\":[],\"C\":[]}', '[]', '[]', 'A', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `skin_types`
--

CREATE TABLE `skin_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `skin_type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assessment_key` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skin_types`
--

INSERT INTO `skin_types` (`id`, `skin_type_name`, `description`, `status`, `created_at`, `assessment_key`) VALUES
(1, 'Oily', 'Skin with excess sebum, shine, and congestion.', 'active', '2026-10-30 14:32:33', 'oily'),
(2, 'Dry', 'Skin that needs richer moisture and barrier support.', 'active', '2026-10-30 14:32:38', 'dry'),
(3, 'Combination', 'Oily in some areas and dry or balanced in others.', 'active', '2026-10-30 14:32:44', 'combination'),
(4, 'Normal', 'Generally balanced skin with minimal sensitivity.', 'active', '2026-10-30 14:32:51', 'normal'),
(5, 'Sensitive', 'Skin prone to redness, discomfort, or reactivity.', 'active', '2026-10-30 14:32:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suitability_tags`
--

CREATE TABLE `suitability_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suitability_tags`
--

INSERT INTO `suitability_tags` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Daily Use', 'Appropriate for consistent everyday routines.', 'active', '2026-10-30 14:33:12', '2026-10-30 14:33:12'),
(2, 'Beginner Friendly', 'Suitable for customers new to active skincare.', 'active', '2026-10-30 14:33:20', '2026-10-30 14:33:20'),
(3, 'Sensitive Skin Friendly', 'Formulated with reactive skin in mind.', 'active', '2026-10-30 14:33:30', '2026-10-30 14:33:30'),
(4, 'Pregnancy Friendly', 'Free from ingredients commonly avoided during pregnancy.', 'active', '2026-10-30 14:33:35', '2026-10-30 14:33:35'),
(5, 'Fragrance Free', 'Made without added fragrance.', 'active', '2026-10-30 14:33:41', '2026-10-30 14:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `status` enum('pending','active','inactive','locked') NOT NULL DEFAULT 'pending',
  `email_verified_at` datetime DEFAULT NULL,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gender` varchar(30) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `totp_secret` text DEFAULT NULL,
  `totp_enabled_at` datetime DEFAULT NULL,
  `totp_last_step` bigint(20) NOT NULL DEFAULT -1,
  `session_version` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `password_changed_at`, `status`, `email_verified_at`, `failed_login_attempts`, `locked_until`, `last_login_at`, `created_at`, `updated_at`, `gender`, `birth_date`, `profile_photo`, `totp_secret`, `totp_enabled_at`, `totp_last_step`, `session_version`) VALUES
(1, 1, 'Admin', 'Owner', 'purevia10@gmail.com', NULL, '$2y$10$sja/kKpUPRXR9H1LoL7iZeRrnVTzq2VrKy652VXqYlh/UQR54zeLq', NULL, 'active', '2026-10-30 20:17:39', 0, NULL, '2026-10-31 03:48:02', '2026-10-30 12:17:23', '2026-10-30 19:48:02', NULL, NULL, NULL, NULL, NULL, -1, 0),
(2, 2, 'Jasminabelido475', '', 'jasminabelido475@gmail.com', NULL, '$2y$10$U6wNSwRBkWTDTFYnh0m1HOm2rh3orVGrri0hnFHgm0hjE5rvNcNza', NULL, 'active', '2026-10-30 20:32:06', 0, NULL, '2026-10-31 00:19:25', '2026-10-30 12:27:56', '2026-10-30 16:19:25', NULL, NULL, NULL, NULL, NULL, -1, 0),
(3, 3, 'Ami', 'Macadato', 'jasjoan16@gmail.com', NULL, '$2y$10$6/q4pch1FQ0PQjlLD31sZ.UviXCyJwFMoqhcYXAwnmEBZH1f9pdk2', NULL, 'active', '2026-10-31 02:36:07', 0, NULL, '2026-10-31 02:36:26', '2026-10-30 18:35:40', '2026-10-30 18:36:26', NULL, NULL, NULL, NULL, NULL, -1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `address_label` varchar(50) NOT NULL DEFAULT 'Home',
  `recipient_first_name` varchar(100) NOT NULL,
  `recipient_last_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Philippines',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_avoided_ingredients`
--

CREATE TABLE `user_avoided_ingredients` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `ingredient_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_totp_recovery_codes`
--

CREATE TABLE `user_totp_recovery_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_lockout_logs`
--
ALTER TABLE `account_lockout_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source_attempt_id` (`source_attempt_id`),
  ADD KEY `idx_lockout_time` (`locked_at`,`id`),
  ADD KEY `idx_lockout_user` (`user_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_module` (`module`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `uq_carts_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_variant` (`cart_id`,`product_variant_id`),
  ADD UNIQUE KEY `uq_cart_variant` (`cart_id`,`product_variant_id`),
  ADD KEY `product_variant_id` (`product_variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `idx_email_verification_user` (`user_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ingredient_name` (`ingredient_name`);

--
-- Indexes for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_key` (`request_key`),
  ADD KEY `idx_adjustment_variant` (`variant_id`,`created_at`),
  ADD KEY `fk_inventory_adjustment_product` (`product_id`),
  ADD KEY `fk_inventory_adjustment_actor` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempt_user` (`user_id`),
  ADD KEY `idx_login_attempt_email` (`email_attempted`),
  ADD KEY `idx_login_attempt_date` (`attempted_at`),
  ADD KEY `idx_login_attempt_successful` (`successful`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `order_return_items`
--
ALTER TABLE `order_return_items`
  ADD PRIMARY KEY (`return_request_id`,`order_item_id`),
  ADD KEY `fk_return_order_item` (`order_item_id`);

--
-- Indexes for table `order_return_requests`
--
ALTER TABLE `order_return_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_return_order` (`order_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_user` (`user_id`),
  ADD KEY `idx_otp_expires` (`expires_at`);

--
-- Indexes for table `password_reset_limits`
--
ALTER TABLE `password_reset_limits`
  ADD PRIMARY KEY (`key_hash`),
  ADD KEY `idx_reset_limit_expiry` (`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `idx_password_reset_user` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payments_verified_by` (`verified_by`),
  ADD KEY `idx_payments_order` (`order_id`),
  ADD KEY `idx_payments_status` (`payment_status`);

--
-- Indexes for table `paypal_checkouts`
--
ALTER TABLE `paypal_checkouts`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `request_key` (`request_key`),
  ADD UNIQUE KEY `paypal_order_id` (`paypal_order_id`),
  ADD UNIQUE KEY `capture_id` (`capture_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_name` (`product_name`);

--
-- Indexes for table `product_benefits`
--
ALTER TABLE `product_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_benefits_product` (`product_id`);

--
-- Indexes for table `product_concerns`
--
ALTER TABLE `product_concerns`
  ADD PRIMARY KEY (`product_id`,`concern_id`),
  ADD KEY `fk_product_concerns_concern` (`concern_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_images_product` (`product_id`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`product_id`,`ingredient_id`),
  ADD KEY `fk_product_ingredients_ingredient` (`ingredient_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_user` (`user_id`),
  ADD KEY `idx_review_product_status` (`product_id`,`status`);

--
-- Indexes for table `product_skin_types`
--
ALTER TABLE `product_skin_types`
  ADD PRIMARY KEY (`product_id`,`skin_type_id`),
  ADD KEY `fk_product_skin_type` (`skin_type_id`);

--
-- Indexes for table `product_suitability`
--
ALTER TABLE `product_suitability`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_suitability_tags`
--
ALTER TABLE `product_suitability_tags`
  ADD PRIMARY KEY (`product_id`,`suitability_tag_id`),
  ADD KEY `fk_product_suitability_tag_label` (`suitability_tag_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variant_sku` (`variant_sku`),
  ADD KEY `idx_product_variants_product` (`product_id`);

--
-- Indexes for table `review_images`
--
ALTER TABLE `review_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_image` (`review_id`);

--
-- Indexes for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review_vote` (`review_id`,`user_id`),
  ADD KEY `fk_vote_user` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skin_assessments`
--
ALTER TABLE `skin_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `submission_key` (`submission_key`),
  ADD KEY `idx_assessments_date` (`origin`,`created_at`),
  ADD KEY `idx_assessments_user` (`user_id`),
  ADD KEY `fk_assessment_type` (`skin_type_id`);

--
-- Indexes for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `concern_name` (`concern_name`);

--
-- Indexes for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_skin_profile_type` (`skin_type_id`);

--
-- Indexes for table `skin_profile_concerns`
--
ALTER TABLE `skin_profile_concerns`
  ADD PRIMARY KEY (`skin_profile_id`,`concern_id`),
  ADD KEY `fk_profile_concerns_concern` (`concern_id`);

--
-- Indexes for table `skin_questions`
--
ALTER TABLE `skin_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `question_key` (`question_key`);

--
-- Indexes for table `skin_types`
--
ALTER TABLE `skin_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skin_type_name` (`skin_type_name`),
  ADD UNIQUE KEY `uq_skin_assessment_key` (`assessment_key`);

--
-- Indexes for table `suitability_tags`
--
ALTER TABLE `suitability_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_status` (`status`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_addresses_user` (`user_id`);

--
-- Indexes for table `user_avoided_ingredients`
--
ALTER TABLE `user_avoided_ingredients`
  ADD PRIMARY KEY (`user_id`,`ingredient_id`),
  ADD KEY `fk_avoided_ingredient` (`ingredient_id`);

--
-- Indexes for table `user_totp_recovery_codes`
--
ALTER TABLE `user_totp_recovery_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_totp_recovery_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_lockout_logs`
--
ALTER TABLE `account_lockout_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_return_requests`
--
ALTER TABLE `order_return_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_benefits`
--
ALTER TABLE `product_benefits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `review_images`
--
ALTER TABLE `review_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `security_settings`
--
ALTER TABLE `security_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_assessments`
--
ALTER TABLE `skin_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_questions`
--
ALTER TABLE `skin_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `skin_types`
--
ALTER TABLE `skin_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suitability_tags`
--
ALTER TABLE `suitability_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_totp_recovery_codes`
--
ALTER TABLE `user_totp_recovery_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_lockout_logs`
--
ALTER TABLE `account_lockout_logs`
  ADD CONSTRAINT `fk_account_lockout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `fk_email_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  ADD CONSTRAINT `fk_inventory_adjustment_actor` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_adjustment_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_adjustment_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `fk_login_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_return_items`
--
ALTER TABLE `order_return_items`
  ADD CONSTRAINT `fk_return_item_request` FOREIGN KEY (`return_request_id`) REFERENCES `order_return_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_return_requests`
--
ALTER TABLE `order_return_requests`
  ADD CONSTRAINT `fk_return_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paypal_checkouts`
--
ALTER TABLE `paypal_checkouts`
  ADD CONSTRAINT `fk_paypal_checkout_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_benefits`
--
ALTER TABLE `product_benefits`
  ADD CONSTRAINT `fk_benefits_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_concerns`
--
ALTER TABLE `product_concerns`
  ADD CONSTRAINT `fk_product_concerns_concern` FOREIGN KEY (`concern_id`) REFERENCES `skin_concerns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_concerns_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `fk_product_ingredients_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_ingredients_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_skin_types`
--
ALTER TABLE `product_skin_types`
  ADD CONSTRAINT `fk_product_skin_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_skin_type` FOREIGN KEY (`skin_type_id`) REFERENCES `skin_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_suitability`
--
ALTER TABLE `product_suitability`
  ADD CONSTRAINT `fk_suitability_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_suitability_tags`
--
ALTER TABLE `product_suitability_tags`
  ADD CONSTRAINT `fk_product_suitability_tag_label` FOREIGN KEY (`suitability_tag_id`) REFERENCES `suitability_tags` (`id`),
  ADD CONSTRAINT `fk_product_suitability_tag_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_images`
--
ALTER TABLE `review_images`
  ADD CONSTRAINT `fk_review_image` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD CONSTRAINT `fk_vote_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skin_assessments`
--
ALTER TABLE `skin_assessments`
  ADD CONSTRAINT `fk_assessment_type` FOREIGN KEY (`skin_type_id`) REFERENCES `skin_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assessment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  ADD CONSTRAINT `fk_skin_profiles_type` FOREIGN KEY (`skin_type_id`) REFERENCES `skin_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_skin_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skin_profile_concerns`
--
ALTER TABLE `skin_profile_concerns`
  ADD CONSTRAINT `fk_profile_concerns_concern` FOREIGN KEY (`concern_id`) REFERENCES `skin_concerns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_profile_concerns_profile` FOREIGN KEY (`skin_profile_id`) REFERENCES `skin_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_avoided_ingredients`
--
ALTER TABLE `user_avoided_ingredients`
  ADD CONSTRAINT `fk_avoided_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avoided_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_totp_recovery_codes`
--
ALTER TABLE `user_totp_recovery_codes`
  ADD CONSTRAINT `fk_totp_recovery_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
