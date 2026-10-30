-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 29, 2026 at 09:13 PM
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

--
-- Dumping data for table `account_lockout_logs`
--

INSERT INTO `account_lockout_logs` (`id`, `user_id`, `source_attempt_id`, `name_snapshot`, `email_snapshot`, `failed_attempts`, `ip_address`, `browser`, `locked_at`) VALUES
(1, NULL, 45, 'Unknown account', 'th3sis2026@gmail.com', NULL, '::1', 'Chrome', '2026-10-20 06:41:14'),
(2, NULL, 49, 'Unknown account', 'th3sis2026@gmail.com', NULL, '::1', 'Chrome', '2026-10-20 06:46:08'),
(3, 25, 58, 'Bryan Tambe', 'th3sis2026@gmail.com', NULL, '::1', 'Chrome', '2026-10-20 17:29:16'),
(4, 26, 64, 'Celine Rabino', 'jasjoan16@gmail.com', NULL, '::1', 'Chrome', '2026-10-20 17:32:49');

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
(1, 1, 'DELETE', 'Ingredients', '1', 'Deleted ingredient \"Niacinamide\".', '::1', NULL, '2026-10-20 08:32:20'),
(2, 1, 'CREATE', 'Ingredients', '2', 'Added ingredient \"Niacinamide\".', '::1', NULL, '2026-10-20 08:32:29'),
(3, 1, 'update_session_policy', 'security', '1', 'Administrator updated session timeout and login lockout settings.', '::1', NULL, '2026-10-20 09:30:28'),
(4, 1, 'update_session_policy', 'security', '1', 'Administrator updated session timeout and login lockout settings.', '::1', NULL, '2026-10-20 09:34:20'),
(5, 1, 'update_session_policy', 'security', '1', 'Administrator updated session timeout and login lockout settings.', '::1', NULL, '2026-10-20 09:34:35'),
(6, 1, 'update_session_policy', 'security', '1', 'Administrator updated session timeout and login lockout settings.', '::1', NULL, '2026-10-20 09:57:42'),
(7, 1, 'update_session_policy', 'security', '1', 'Administrator updated session timeout and login lockout settings.', '::1', NULL, '2026-10-20 10:10:51'),
(8, 1, 'unlock_account', 'security', '25', 'Administrator manually unlocked a user account.', '::1', NULL, '2026-10-20 10:10:55'),
(9, 1, 'UPDATE', 'Product Suitability', '2', 'Update Product Suitability record.', '::1', NULL, '2026-10-20 19:58:30'),
(10, 1, 'UPDATE', 'Product Suitability', '2', 'Update Product Suitability record.', '::1', NULL, '2026-10-20 19:58:42'),
(11, 1, 'UPDATE', 'Product Suitability', '2', 'Update Product Suitability record.', '::1', NULL, '2026-10-20 20:00:42'),
(12, 1, 'UPDATE', 'Product Suitability', '2', 'Update Product Suitability record.', '::1', NULL, '2026-10-20 20:00:48'),
(13, 1, 'UPDATE', 'orders', '7', 'Updated order ORD-2026-713D5DD08B: Pending to Confirmed; payment Pending to Pending.', '::1', NULL, '2026-10-24 06:53:20'),
(14, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-28 18:55:24'),
(15, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-28 18:56:00'),
(16, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-28 18:56:16'),
(17, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-28 18:56:35'),
(18, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 14:26:59'),
(19, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 15:32:31'),
(20, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-29 16:07:15'),
(21, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 16:43:32'),
(22, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-29 16:43:49'),
(23, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 16:44:00'),
(24, 1, 'UPDATE', 'orders', '7', 'Updated order ORD-2026-713D5DD08B: Confirmed to Completed; payment Pending to Pending.', '::1', NULL, '2026-10-29 16:44:16'),
(25, 1, 'UPDATE', 'orders', '7', 'Updated order ORD-2026-713D5DD08B: Completed to Completed; payment Pending to Verified.', '::1', NULL, '2026-10-29 16:44:34'),
(26, 1, 'LOGOUT', 'Auth', '1', 'Signed out from the admin session', '::1', 'Chrome 156 · Windows', '2026-10-29 16:44:43'),
(27, 25, 'LOGIN', 'Auth', '25', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 16:45:11'),
(28, 25, 'LOGOUT', 'Auth', '25', 'Signed out from the customer session', '::1', 'Chrome 156 · Windows', '2026-10-29 16:53:49'),
(29, 1, 'LOGIN', 'Auth', '1', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 16:58:11'),
(30, 3, 'LOGIN', 'Auth', '3', 'Successful login', '::1', 'Chrome 156 · Windows', '2026-10-29 18:09:04');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`) VALUES
(1, 3, '2026-10-13 13:09:11'),
(4, 25, '2026-10-24 02:13:33');

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

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_variant_id`, `quantity`) VALUES
(10, 1, 2, 1),
(13, 4, 1, 1),
(14, 1, 1, 1);

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
(1, 'Cleansers', 'Facial cleansing products for removing dirt, oil, and impurities.', 'active', '2026-09-28 16:13:57', '2026-09-28 16:13:57'),
(2, 'Serums', 'Concentrated skincare products designed for specific skin concerns.', 'active', '2026-09-28 16:13:57', '2026-09-28 16:13:57'),
(3, 'Moisturizers', 'Products that help maintain skin hydration and support the skin barrier.', 'active', '2026-09-28 16:13:57', '2026-09-28 16:13:57'),
(4, 'Sunscreens', 'Daily sun protection products.', 'active', '2026-09-28 16:13:57', '2026-09-28 16:13:57'),
(6, 'Masks', 'Supplementary skincare treatments for targeted concerns.', 'active', '2026-09-28 16:13:57', '2026-09-28 16:13:57'),
(7, 'Toners', 'Lightweight products used after cleansing.', 'active', '2026-10-17 07:59:28', '2026-10-17 07:59:28');

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
(9, 25, '2c7608709ba8f03b495f00e04a3574e5e231213466cffea133c52dc759648af5', '2026-10-20 07:28:02', '2026-10-20 06:58:14', '2026-10-19 22:58:02'),
(10, 26, '3b88c77e3eb44c7583ab11d19c9dce5d1332624c19412b97e14230f34b1375c4', '2026-10-20 18:02:09', '2026-10-20 17:32:29', '2026-10-20 09:32:09');

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
(2, 'Niacinamide', 'asdadad', 'Serums, Toners', 'active', '2026-10-20 08:32:29', '2026-10-20 08:32:29');

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
(16, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 17:04:19'),
(17, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 19:46:07'),
(18, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 20:05:23'),
(19, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 20:13:35'),
(20, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 20:30:42'),
(21, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 21:23:50'),
(22, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 22:04:09'),
(23, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 22:11:40'),
(24, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 22:21:51'),
(25, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 22:24:38'),
(26, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid password. Attempt 1 of 3.', '2026-10-16 22:47:27'),
(27, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-16 22:47:32'),
(28, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 00:35:08'),
(29, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 00:38:00'),
(30, NULL, 'hdmmariadiannecuvin@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:06:56'),
(31, NULL, 'hdmmariadiannecuvin@gmail.com', '::1', 'Chrome', 0, 'Invalid password. Attempt 1 of 3.', '2026-10-17 01:13:13'),
(32, NULL, 'hdmmariadiannecuvin@gmail.com', '::1', 'Chrome', 0, 'Invalid password. Attempt 2 of 3.', '2026-10-17 01:14:02'),
(33, NULL, 'hdmmariadiannecuvin@gmail.com', '::1', 'Chrome', 0, 'Account locked after 3 failed login attempts.', '2026-10-17 01:14:05'),
(34, NULL, 'hdmmariadiannecuvin@gmail.com', '::1', 'Chrome', 0, 'Login blocked: account locked.', '2026-10-17 01:14:49'),
(35, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:16:55'),
(36, 2, 'mariadianne@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:17:41'),
(37, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:19:17'),
(38, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:21:32'),
(39, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 01:22:50'),
(40, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 06:27:11'),
(41, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-17 07:43:52'),
(42, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-19 20:45:09'),
(43, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-19 22:41:09'),
(44, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-19 22:41:12'),
(45, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Account locked.', '2026-10-19 22:41:14'),
(46, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-19 22:45:53'),
(47, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-19 22:46:04'),
(48, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-19 22:46:06'),
(49, NULL, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Account locked.', '2026-10-19 22:46:08'),
(50, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-19 22:46:20'),
(51, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-19 22:58:29'),
(52, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-19 22:58:35'),
(53, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-19 22:59:07'),
(54, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 08:29:34'),
(55, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:18:09'),
(56, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:29:13'),
(57, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:29:15'),
(58, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 0, 'Account locked.', '2026-10-20 09:29:16'),
(59, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:30:02'),
(60, 26, 'jasjoan16@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:32:40'),
(61, 26, 'jasjoan16@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:32:43'),
(62, 26, 'jasjoan16@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:32:45'),
(63, 26, 'jasjoan16@gmail.com', '::1', 'Chrome', 0, 'Invalid email or password.', '2026-10-20 09:32:47'),
(64, 26, 'jasjoan16@gmail.com', '::1', 'Chrome', 0, 'Account locked.', '2026-10-20 09:32:49'),
(65, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:34:03'),
(66, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:34:52'),
(67, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:51:42'),
(68, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:54:21'),
(69, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:54:36'),
(70, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:57:34'),
(71, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 09:57:56'),
(72, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 10:06:57'),
(73, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 10:10:43'),
(74, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 10:12:27'),
(75, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 15:30:51'),
(76, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 17:45:48'),
(77, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 18:17:55'),
(78, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 18:21:06'),
(79, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 18:44:37'),
(80, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 18:46:14'),
(81, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 19:44:39'),
(82, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 20:41:09'),
(83, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 20:58:48'),
(84, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-23 17:34:08'),
(85, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-23 18:14:25'),
(86, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-23 18:34:19'),
(87, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-23 19:38:16'),
(88, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-23 20:48:34'),
(89, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 01:23:56'),
(90, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 01:54:59'),
(91, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 02:02:41'),
(92, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 02:11:46'),
(93, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:42:08'),
(94, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:46:52'),
(95, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:47:28'),
(96, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:53:04'),
(97, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:54:02'),
(98, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 06:54:49'),
(99, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 15:38:02'),
(100, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 15:38:33'),
(101, 2, 'mariadianne@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 15:39:41'),
(102, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 15:54:10'),
(103, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 16:29:09'),
(104, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 16:34:05'),
(105, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 16:40:33'),
(106, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-24 16:44:53'),
(107, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-26 19:07:08'),
(108, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-26 19:07:26'),
(109, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 15:18:49'),
(110, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 15:40:50'),
(111, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 17:06:29'),
(112, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 17:35:05'),
(113, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 18:08:05'),
(114, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 18:26:43'),
(115, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 18:55:24'),
(116, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-28 18:56:16'),
(117, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 14:26:59'),
(118, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 15:32:31'),
(119, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 16:43:32'),
(120, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 16:44:00'),
(121, 25, 'th3sis2026@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 16:45:11'),
(122, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 16:58:11'),
(123, 3, 'amimacadato@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-29 18:09:04');

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

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `first_name`, `last_name`, `email`, `phone`, `address_line1`, `address_line2`, `barangay`, `city`, `province`, `postal_code`, `country`, `subtotal`, `shipping_fee`, `total_amount`, `payment_status`, `order_status`, `created_at`, `updated_at`, `cancellation_reason`, `cancellation_details`, `cancelled_at`) VALUES
(3, 3, 'PV-20261014-38687E9C37', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 267.00, 8.00, 275.00, 'pending', 'cancelled', '2026-10-14 10:44:25', '2026-10-16 11:34:47', NULL, NULL, NULL),
(4, 3, 'ORD-2026-7AD308C96D', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 160.00, 8.00, 168.00, 'pending', 'pending', '2026-10-14 12:47:33', '2026-10-14 12:47:33', NULL, NULL, NULL),
(5, 3, 'ORD-2026-3AB6BCF589', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 40.00, 8.00, 48.00, 'pending', 'pending', '2026-10-17 06:33:50', '2026-10-17 06:33:50', NULL, NULL, NULL),
(6, 25, 'ORD-2026-026ADE1FBB', 'Samantha', 'Siao', 'th3sis2026@gmail.com', '09936008933', 'Nagpayong Pinagbuhatan', '', 'Pinagbuhatan', 'Metro Manila', 'Metro Manila', '1602', 'Philippines', 20.00, 8.00, 28.00, 'pending', 'pending', '2026-10-24 06:49:28', '2026-10-24 06:49:28', NULL, NULL, NULL),
(7, 25, 'ORD-2026-713D5DD08B', 'Samantha', 'Siao', 'th3sis2026@gmail.com', '09936008933', 'Nagpayong pinagbuhatan', '', 'Pinagbuhatan', 'Metro Manila', 'Metro Manila', '1602', 'Philippines', 89.00, 8.00, 97.00, 'paid', 'completed', '2026-10-24 06:51:24', '2026-10-29 16:44:34', NULL, NULL, NULL),
(8, 3, 'ORD-2026-512646A98E', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 20.00, 8.00, 28.00, 'pending', 'pending', '2026-10-29 18:35:37', '2026-10-29 18:35:37', NULL, NULL, NULL);

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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(3, 3, 1, 'PureVia Matcha Clarify Moisturizer (Travel size · 50g)', 3, 89.00, 267.00),
(4, 4, 2, 'SDADAD (REG 50g)', 8, 20.00, 160.00),
(5, 5, 2, 'SDADAD (REG 50g)', 2, 20.00, 40.00),
(6, 6, 2, 'SDADAD (REG 50g)', 1, 20.00, 20.00),
(7, 7, 1, 'PureVia Matcha Clarify Moisturizer (Travel size 50g)', 1, 89.00, 89.00),
(8, 8, 2, 'SDADAD (REG 50g)', 1, 20.00, 20.00);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(22, 25, '$2y$10$vX.A4drCfJzXtiTjL0e7QOZewBtSsBJHGu4Upp.nGOQVn0jK9dEVy', 'email_verification', '2026-10-20 07:07:50', '2026-10-20 06:58:02', '2026-10-19 22:57:50', 0),
(23, 26, '$2y$10$.UMnLKBrmkjKHt822nsK5uPYabZ6baR7S/PtRJHq4qUjnnMdv0L42', 'email_verification', '2026-10-20 17:41:48', '2026-10-20 17:32:09', '2026-10-20 09:31:48', 0),
(24, 25, '$2y$10$KDpKjDxrIsJlfkMgNmLQhuVcaygxIu7g/7qkiM8rq4R5awbdG7Y1W', 'password_reset', '2026-10-29 22:28:14', '2026-10-29 22:20:04', '2026-10-29 14:18:14', 0),
(25, 26, '$2y$10$apgqCiPcLWuMfc2asRjEtelyBA4.OeBipb4n8tL5p2OLsHvr5B1uq', 'password_reset', '2026-10-29 22:29:22', NULL, '2026-10-29 14:19:22', 0),
(26, 25, '$2y$10$moXDhMJ9cev3.5Pfqdhcvuz0P8kyzOn/v06FHqAOxJBvZDevVKc02', 'password_reset', '2026-10-29 22:30:04', '2026-10-29 22:20:19', '2026-10-29 14:20:04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_limits`
--

CREATE TABLE `password_reset_limits` (
  `key_hash` char(64) NOT NULL,
  `requests` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_limits`
--

INSERT INTO `password_reset_limits` (`key_hash`, `requests`, `expires_at`) VALUES
('30e4e76f43ffc8fbf29aaf1d21a5086b68cd9ffd926b7aab7c252f8570cf9a3a', 4, '2026-10-30 00:18:14'),
('3afc4824677ba52abf4e2c6eeacb3bbdc23e641126d3f0838aa30508dfefdaf6', 1, '2026-10-30 00:19:22'),
('4ab429d7556584bc87470614c9ce0954dea1be12132292a47625cfacdaf8a7ce', 2, '2026-10-30 00:18:14'),
('890a4ccc8f54a3f15397c70cc15a6bf651f41fa2282ab649e0c4bd88da4fd760', 1, '2026-10-30 00:19:53');

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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `payment_status`, `transaction_reference`, `gateway_reference`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(3, 3, 'cod', 275.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-14 10:44:25', '2026-10-14 10:44:25'),
(4, 4, 'cod', 168.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-14 12:47:33', '2026-10-14 12:47:33'),
(5, 5, 'cod', 48.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-17 06:33:50', '2026-10-17 06:33:50'),
(6, 6, 'cod', 28.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-24 06:49:28', '2026-10-24 06:49:28'),
(7, 7, 'cod', 97.00, 'paid', NULL, NULL, 1, '2026-10-30 00:44:34', '2026-10-24 06:51:24', '2026-10-29 16:44:34'),
(8, 8, 'online', 28.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-29 18:35:37', '2026-10-29 18:35:37');

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

--
-- Dumping data for table `paypal_checkouts`
--

INSERT INTO `paypal_checkouts` (`order_id`, `request_key`, `environment`, `paypal_order_id`, `approval_url`, `capture_id`, `items_snapshot`, `state`, `created_at`) VALUES
(8, 'f391b928c25913117a5d046840182bba69d563a8', 'sandbox', '08T10443GS867452A', 'https://www.sandbox.paypal.com/checkoutnow?token=08T10443GS867452A', NULL, '{\"payment_preference\":\"paypal\",\"items\":[{\"cart_item_id\":10,\"product_variant_id\":2,\"product_id\":2,\"quantity\":1}]}', 'pending', '2026-10-29 18:35:37');

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
(1, 3, 'PureVia Matcha Clarify Moisturizer', 'MST-001', 'Refresh and balance your skin with PureVia Matcha Clarify Moisturizer, featuring Matcha Extract, a plant-based ingredient rich in antioxidants known for its skin-conditioning properties. Designed for everyday skincare, this lightweight moisturizer helps control excess oil, minimize the appearance of pores, and provide essential hydration without leaving a greasy feeling, promoting a clearer, smoother, and healthier-looking complexion.', NULL, 89.00, 6, NULL, 'active', '2026-10-09 19:01:23', '2026-10-24 06:51:24'),
(2, 1, 'SDADAD', 'DMO-001', 'ASDADA', NULL, 20.00, 0, 'uploads/products/3715746d17844d01a7629c2a9bad4f1a.jpg', 'active', '2026-10-09 21:11:47', '2026-10-29 18:35:37');

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
(3, 2, 'DADAD', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_concerns`
--

CREATE TABLE `product_concerns` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `concern_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 2, 'uploads/products/3715746d17844d01a7629c2a9bad4f1a.jpg', 0);

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
(2, 2);

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
(2, 4);

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
(2, 0, 'none', 'both', '', 0, 0, 0, 4, '2026-10-20 20:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_suitability_tags`
--

CREATE TABLE `product_suitability_tags` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `suitability_tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, '50g', 'Travel size', 89.00, 6, '2026-10-09 19:01:23', '2026-10-24 06:51:24', NULL, 15, NULL),
(2, 2, '50g', 'REG', 20.00, 0, '2026-10-09 21:11:47', '2026-10-29 18:35:37', NULL, 15, NULL);

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
(1, 'Admin'),
(3, 'Customer'),
(2, 'Staff');

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

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`id`, `max_login_attempts`, `lockout_minutes`, `session_timeout_minutes`, `password_min_length`, `require_uppercase`, `require_lowercase`, `require_number`, `require_special_character`, `password_expiry_days`, `require_otp`, `updated_at`, `min_uppercase`, `min_lowercase`, `min_number`, `min_special_character`) VALUES
(1, 3, 15, 30, 12, 1, 1, 1, 1, 0, 1, '2026-10-20 10:10:51', 1, 1, 1, 1);

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

--
-- Dumping data for table `skin_assessments`
--

INSERT INTO `skin_assessments` (`id`, `user_id`, `submission_key`, `assessment_method`, `origin`, `skin_type_id`, `skin_type_label`, `clarity`, `profile_snapshot`, `usefulness`, `created_at`) VALUES
(1, 3, 'd4c89227e5011d02e6d542d25cc091a3ee05a45bc9a6122e46739c34be48989a', 'guided', 'quiz', 4, 'Combination', 'clear', '{\"assessment_method\":\"guided\",\"skin_type\":\"combination\",\"skin_type_id\":4,\"skin_type_label\":\"Combination\",\"assessment\":{\"status\":\"suggested\",\"reason\":null,\"skin_type\":\"combination\",\"scores\":{\"combination\":4,\"normal\":2,\"oily\":1,\"dry\":0},\"answered\":7,\"margin\":2,\"followups_answered\":0,\"needs_followup\":false,\"rule_version\":\"equal-observations-v2\",\"validation_status\":\"Research-informed custom rules; not clinically validated\",\"explanation\":\"Each answered observation contributes one vote. A result requires at least five observations, a majority and a lead of at least two votes. Conflicting answers may remain undetermined. Scores are not probabilities.\"},\"answers\":{\"q2\":\"B\",\"q3\":\"C\",\"q4\":\"D\",\"q5\":\"D\",\"q6\":\"B\",\"q7\":\"D\",\"q8\":\"D\",\"q9\":\"A\",\"q10\":\"A\",\"q11\":\"D\",\"f1\":\"\",\"f2\":\"\",\"obs_breakouts\":\"B\",\"obs_marks\":\"C\",\"obs_redness\":\"B\",\"obs_lines\":\"A\"},\"sensitivity\":{\"level\":\"low\",\"score\":0,\"answered\":2,\"frequent_reaction\":false,\"validation_status\":\"Self-reported reaction flag; not an allergy test or validated sensitivity scale\"},\"concerns\":[],\"main_concern_id\":null,\"ingredient_preference\":\"none\",\"avoided_ingredients\":[],\"avoidance_reason\":\"\",\"routine_preference\":\"basic\",\"questionnaire_version\":\"25fd9647986f6a6921c397df03b378f818e3696fa26a0117a4d9e3e3ff5a88ff\",\"rule_version\":\"purevia-observations-2026-10-27\",\"questionnaire_snapshot\":{\"q2\":[\"How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?\",[\"Tight or uncomfortable\",\"Comfortable and balanced\",\"Oily or shiny across most of my face\",\"Tight on my cheeks but oily around my nose or forehead\",\"Not sure\"]],\"q3\":[\"How does your skin usually look by midday?\",[\"Mostly dry or matte, sometimes flaky\",\"Neither especially dry nor oily\",\"Shiny across most of my face\",\"Shiny mainly on my forehead, nose, and chin\",\"Not sure\"]],\"q4\":[\"How would you describe your cheeks most days?\",[\"Dry, tight, or rough\",\"Comfortable and balanced\",\"Oily or shiny\",\"Usually normal or dry while my T-zone is oily\",\"Not sure\"]],\"q5\":[\"How does your forehead, nose, and chin (T-zone) usually behave?\",[\"Often dry or tight\",\"Usually balanced\",\"Very oily, along with my cheeks\",\"Oily while my cheeks are noticeably less oily\",\"Not sure\"]],\"q6\":[\"How frequently do you notice dry patches or flaking?\",[\"Frequently across several facial areas\",\"Rarely or never\",\"Rarely, because my face is usually oily\",\"Sometimes, especially on my cheeks\",\"Not sure\"]],\"q7\":[\"Which description best matches your skin throughout a typical day?\",[\"Often feels dry and needs moisturizing\",\"Usually feels comfortable and balanced\",\"Produces noticeable oil over most of my face\",\"Has both oily and dry or normal areas\",\"Changes significantly or I\'m not sure\"]],\"q8\":[\"When you gently blot your face with clean tissue around midday, where do you usually notice oil?\",[\"Little or no oil anywhere\",\"A small amount distributed evenly\",\"Noticeable oil from both my T-zone and cheeks\",\"Oil mainly from my T-zone\",\"I haven\'t checked \\/ Not sure\"]],\"q9\":[\"How often does your skin sting, burn, or become irritated after applying skincare products?\",[\"Never or rarely\",\"Occasionally\",\"Frequently\",\"Not sure\"]],\"q10\":[\"How often do you experience redness, itching, or discomfort after using new products?\",[\"Never or rarely\",\"Sometimes\",\"Frequently\",\"Not sure\"]],\"q11\":[\"How would you describe your skin\'s response to fragrances or strong skincare formulas?\",[\"Usually no noticeable reaction\",\"Occasional discomfort\",\"Frequent irritation or discomfort\",\"I haven\'t used these \\/ Not sure\"]],\"f1\":[\"Which areas of your face are consistently oily?\",[\"None \\u2014 my skin is mostly dry\",\"No specific area \\u2014 my skin feels balanced\",\"Most of my face, including cheeks\",\"Mainly my forehead, nose, and chin\"]],\"f2\":[\"Compared with your forehead and nose, how do your cheeks usually feel?\",[\"Both areas are dry\",\"Both areas are balanced\",\"Both areas are oily\",\"My cheeks are noticeably less oily\"]],\"obs_breakouts\":[\"Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_marks\":[\"Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_redness\":[\"Over the past four weeks, have you noticed recurring facial redness, itching or burning?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_lines\":[\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",[\"Yes\",\"No\",\"Not sure\"]]},\"question_evidence\":[{\"id\":\"1\",\"question_key\":\"q2\",\"prompt\":\"How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?\",\"options_json\":\"[\\\"Tight or uncomfortable\\\",\\\"Comfortable and balanced\\\",\\\"Oily or shiny across most of my face\\\",\\\"Tight on my cheeks but oily around my nose or forehead\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"2\",\"question_key\":\"q3\",\"prompt\":\"How does your skin usually look by midday?\",\"options_json\":\"[\\\"Mostly dry or matte, sometimes flaky\\\",\\\"Neither especially dry nor oily\\\",\\\"Shiny across most of my face\\\",\\\"Shiny mainly on my forehead, nose, and chin\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"3\",\"question_key\":\"q4\",\"prompt\":\"How would you describe your cheeks most days?\",\"options_json\":\"[\\\"Dry, tight, or rough\\\",\\\"Comfortable and balanced\\\",\\\"Oily or shiny\\\",\\\"Usually normal or dry while my T-zone is oily\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"4\",\"question_key\":\"q5\",\"prompt\":\"How does your forehead, nose, and chin (T-zone) usually behave?\",\"options_json\":\"[\\\"Often dry or tight\\\",\\\"Usually balanced\\\",\\\"Very oily, along with my cheeks\\\",\\\"Oily while my cheeks are noticeably less oily\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"5\",\"question_key\":\"q6\",\"prompt\":\"How frequently do you notice dry patches or flaking?\",\"options_json\":\"[\\\"Frequently across several facial areas\\\",\\\"Rarely or never\\\",\\\"Rarely, because my face is usually oily\\\",\\\"Sometimes, especially on my cheeks\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"6\",\"question_key\":\"q7\",\"prompt\":\"Which description best matches your skin throughout a typical day?\",\"options_json\":\"[\\\"Often feels dry and needs moisturizing\\\",\\\"Usually feels comfortable and balanced\\\",\\\"Produces noticeable oil over most of my face\\\",\\\"Has both oily and dry or normal areas\\\",\\\"Changes significantly or I\'m not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"7\",\"question_key\":\"q8\",\"prompt\":\"When you gently blot your face with clean tissue around midday, where do you usually notice oil?\",\"options_json\":\"[\\\"Little or no oil anywhere\\\",\\\"A small amount distributed evenly\\\",\\\"Noticeable oil from both my T-zone and cheeks\\\",\\\"Oil mainly from my T-zone\\\",\\\"I haven\'t checked \\\\\\/ Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"8\",\"question_key\":\"q9\",\"prompt\":\"How often does your skin sting, burn, or become irritated after applying skincare products?\",\"options_json\":\"[\\\"Never or rarely\\\",\\\"Occasionally\\\",\\\"Frequently\\\",\\\"Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"9\",\"question_key\":\"q10\",\"prompt\":\"How often do you experience redness, itching, or discomfort after using new products?\",\"options_json\":\"[\\\"Never or rarely\\\",\\\"Sometimes\\\",\\\"Frequently\\\",\\\"Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"10\",\"question_key\":\"q11\",\"prompt\":\"How would you describe your skin\'s response to fragrances or strong skincare formulas?\",\"options_json\":\"[\\\"Usually no noticeable reaction\\\",\\\"Occasional discomfort\\\",\\\"Frequent irritation or discomfort\\\",\\\"I haven\'t used these \\\\\\/ Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"11\",\"question_key\":\"f1\",\"prompt\":\"Which areas of your face are consistently oily?\",\"options_json\":\"[\\\"None \\\\u2014 my skin is mostly dry\\\",\\\"No specific area \\\\u2014 my skin feels balanced\\\",\\\"Most of my face, including cheeks\\\",\\\"Mainly my forehead, nose, and chin\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"12\",\"question_key\":\"f2\",\"prompt\":\"Compared with your forehead and nose, how do your cheeks usually feel?\",\"options_json\":\"[\\\"Both areas are dry\\\",\\\"Both areas are balanced\\\",\\\"Both areas are oily\\\",\\\"My cheeks are noticeably less oily\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"25\",\"question_key\":\"obs_breakouts\",\"prompt\":\"Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"baumann\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"9\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"26\",\"question_key\":\"obs_marks\",\"prompt\":\"Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"classification\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"20\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"27\",\"question_key\":\"obs_redness\",\"prompt\":\"Over the past four weeks, have you noticed recurring facial redness, itching or burning?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"baumann\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"22\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"28\",\"question_key\":\"obs_lines\",\"prompt\":\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"classification\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"16\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"}],\"observed_concerns\":[{\"concern_id\":16,\"question\":\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",\"source\":\"classification\"}]}', NULL, '2026-10-28 15:26:30');

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
(9, 'Acne', 'Treatment and care for acne concerns', 'active', '2026-10-17 08:27:00'),
(10, 'Excess Oil', 'Excess oil or shine', 'active', '2026-10-20 17:55:00'),
(16, 'Fine Lines / Aging', 'Fine lines', 'active', '2026-10-20 17:55:00'),
(18, 'Dryness / Dehydration', 'Dryness or dehydration', 'active', '2026-10-20 19:26:07'),
(19, 'Dullness', 'Dull-looking skin', 'active', '2026-10-20 19:26:07'),
(20, 'Dark Spots / Uneven Tone', 'Dark spots or uneven-looking tone', 'active', '2026-10-20 19:26:07'),
(21, 'Large / Visible Pores', 'Visible pores', 'active', '2026-10-20 19:26:07'),
(22, 'Redness / Irritation', 'Redness or irritation', 'active', '2026-10-20 19:26:07'),
(23, 'Uneven Texture', 'Uneven-looking texture', 'active', '2026-10-20 19:26:07');

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

--
-- Dumping data for table `skin_profiles`
--

INSERT INTO `skin_profiles` (`id`, `user_id`, `skin_type_id`, `sensitivity_level`, `skin_type_source`, `created_at`, `updated_at`, `assessment_method`, `sensitivity_score`, `main_concern_id`, `ingredient_preference`, `avoidance_reason`, `routine_preference`, `assessment_answers`) VALUES
(1, 3, 4, 'low', 'finder', '2026-10-20 18:20:24', '2026-10-28 15:26:30', 'guided', 0.0000, NULL, 'none', NULL, 'basic', '{\"assessment\":{\"status\":\"suggested\",\"reason\":null,\"skin_type\":\"combination\",\"scores\":{\"combination\":4,\"normal\":2,\"oily\":1,\"dry\":0},\"answered\":7,\"margin\":2,\"followups_answered\":0,\"needs_followup\":false,\"rule_version\":\"equal-observations-v2\",\"validation_status\":\"Research-informed custom rules; not clinically validated\",\"explanation\":\"Each answered observation contributes one vote. A result requires at least five observations, a majority and a lead of at least two votes. Conflicting answers may remain undetermined. Scores are not probabilities.\"},\"answers\":{\"q2\":\"B\",\"q3\":\"C\",\"q4\":\"D\",\"q5\":\"D\",\"q6\":\"B\",\"q7\":\"D\",\"q8\":\"D\",\"q9\":\"A\",\"q10\":\"A\",\"q11\":\"D\",\"f1\":\"\",\"f2\":\"\",\"obs_breakouts\":\"B\",\"obs_marks\":\"C\",\"obs_redness\":\"B\",\"obs_lines\":\"A\"},\"questionnaire_version\":\"25fd9647986f6a6921c397df03b378f818e3696fa26a0117a4d9e3e3ff5a88ff\",\"rule_version\":\"purevia-observations-2026-10-27\",\"questionnaire_snapshot\":{\"q2\":[\"How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?\",[\"Tight or uncomfortable\",\"Comfortable and balanced\",\"Oily or shiny across most of my face\",\"Tight on my cheeks but oily around my nose or forehead\",\"Not sure\"]],\"q3\":[\"How does your skin usually look by midday?\",[\"Mostly dry or matte, sometimes flaky\",\"Neither especially dry nor oily\",\"Shiny across most of my face\",\"Shiny mainly on my forehead, nose, and chin\",\"Not sure\"]],\"q4\":[\"How would you describe your cheeks most days?\",[\"Dry, tight, or rough\",\"Comfortable and balanced\",\"Oily or shiny\",\"Usually normal or dry while my T-zone is oily\",\"Not sure\"]],\"q5\":[\"How does your forehead, nose, and chin (T-zone) usually behave?\",[\"Often dry or tight\",\"Usually balanced\",\"Very oily, along with my cheeks\",\"Oily while my cheeks are noticeably less oily\",\"Not sure\"]],\"q6\":[\"How frequently do you notice dry patches or flaking?\",[\"Frequently across several facial areas\",\"Rarely or never\",\"Rarely, because my face is usually oily\",\"Sometimes, especially on my cheeks\",\"Not sure\"]],\"q7\":[\"Which description best matches your skin throughout a typical day?\",[\"Often feels dry and needs moisturizing\",\"Usually feels comfortable and balanced\",\"Produces noticeable oil over most of my face\",\"Has both oily and dry or normal areas\",\"Changes significantly or I\'m not sure\"]],\"q8\":[\"When you gently blot your face with clean tissue around midday, where do you usually notice oil?\",[\"Little or no oil anywhere\",\"A small amount distributed evenly\",\"Noticeable oil from both my T-zone and cheeks\",\"Oil mainly from my T-zone\",\"I haven\'t checked \\/ Not sure\"]],\"q9\":[\"How often does your skin sting, burn, or become irritated after applying skincare products?\",[\"Never or rarely\",\"Occasionally\",\"Frequently\",\"Not sure\"]],\"q10\":[\"How often do you experience redness, itching, or discomfort after using new products?\",[\"Never or rarely\",\"Sometimes\",\"Frequently\",\"Not sure\"]],\"q11\":[\"How would you describe your skin\'s response to fragrances or strong skincare formulas?\",[\"Usually no noticeable reaction\",\"Occasional discomfort\",\"Frequent irritation or discomfort\",\"I haven\'t used these \\/ Not sure\"]],\"f1\":[\"Which areas of your face are consistently oily?\",[\"None \\u2014 my skin is mostly dry\",\"No specific area \\u2014 my skin feels balanced\",\"Most of my face, including cheeks\",\"Mainly my forehead, nose, and chin\"]],\"f2\":[\"Compared with your forehead and nose, how do your cheeks usually feel?\",[\"Both areas are dry\",\"Both areas are balanced\",\"Both areas are oily\",\"My cheeks are noticeably less oily\"]],\"obs_breakouts\":[\"Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_marks\":[\"Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_redness\":[\"Over the past four weeks, have you noticed recurring facial redness, itching or burning?\",[\"Yes\",\"No\",\"Not sure\"]],\"obs_lines\":[\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",[\"Yes\",\"No\",\"Not sure\"]]},\"question_evidence\":[{\"id\":\"1\",\"question_key\":\"q2\",\"prompt\":\"How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?\",\"options_json\":\"[\\\"Tight or uncomfortable\\\",\\\"Comfortable and balanced\\\",\\\"Oily or shiny across most of my face\\\",\\\"Tight on my cheeks but oily around my nose or forehead\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"2\",\"question_key\":\"q3\",\"prompt\":\"How does your skin usually look by midday?\",\"options_json\":\"[\\\"Mostly dry or matte, sometimes flaky\\\",\\\"Neither especially dry nor oily\\\",\\\"Shiny across most of my face\\\",\\\"Shiny mainly on my forehead, nose, and chin\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"3\",\"question_key\":\"q4\",\"prompt\":\"How would you describe your cheeks most days?\",\"options_json\":\"[\\\"Dry, tight, or rough\\\",\\\"Comfortable and balanced\\\",\\\"Oily or shiny\\\",\\\"Usually normal or dry while my T-zone is oily\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"4\",\"question_key\":\"q5\",\"prompt\":\"How does your forehead, nose, and chin (T-zone) usually behave?\",\"options_json\":\"[\\\"Often dry or tight\\\",\\\"Usually balanced\\\",\\\"Very oily, along with my cheeks\\\",\\\"Oily while my cheeks are noticeably less oily\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"5\",\"question_key\":\"q6\",\"prompt\":\"How frequently do you notice dry patches or flaking?\",\"options_json\":\"[\\\"Frequently across several facial areas\\\",\\\"Rarely or never\\\",\\\"Rarely, because my face is usually oily\\\",\\\"Sometimes, especially on my cheeks\\\",\\\"Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"6\",\"question_key\":\"q7\",\"prompt\":\"Which description best matches your skin throughout a typical day?\",\"options_json\":\"[\\\"Often feels dry and needs moisturizing\\\",\\\"Usually feels comfortable and balanced\\\",\\\"Produces noticeable oil over most of my face\\\",\\\"Has both oily and dry or normal areas\\\",\\\"Changes significantly or I\'m not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"7\",\"question_key\":\"q8\",\"prompt\":\"When you gently blot your face with clean tissue around midday, where do you usually notice oil?\",\"options_json\":\"[\\\"Little or no oil anywhere\\\",\\\"A small amount distributed evenly\\\",\\\"Noticeable oil from both my T-zone and cheeks\\\",\\\"Oil mainly from my T-zone\\\",\\\"I haven\'t checked \\\\\\/ Not sure\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"8\",\"question_key\":\"q9\",\"prompt\":\"How often does your skin sting, burn, or become irritated after applying skincare products?\",\"options_json\":\"[\\\"Never or rarely\\\",\\\"Occasionally\\\",\\\"Frequently\\\",\\\"Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"9\",\"question_key\":\"q10\",\"prompt\":\"How often do you experience redness, itching, or discomfort after using new products?\",\"options_json\":\"[\\\"Never or rarely\\\",\\\"Sometimes\\\",\\\"Frequently\\\",\\\"Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"10\",\"question_key\":\"q11\",\"prompt\":\"How would you describe your skin\'s response to fragrances or strong skincare formulas?\",\"options_json\":\"[\\\"Usually no noticeable reaction\\\",\\\"Occasional discomfort\\\",\\\"Frequent irritation or discomfort\\\",\\\"I haven\'t used these \\\\\\/ Not sure\\\"]\",\"source_key\":\"bstq2025\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"11\",\"question_key\":\"f1\",\"prompt\":\"Which areas of your face are consistently oily?\",\"options_json\":\"[\\\"None \\\\u2014 my skin is mostly dry\\\",\\\"No specific area \\\\u2014 my skin feels balanced\\\",\\\"Most of my face, including cheeks\\\",\\\"Mainly my forehead, nose, and chin\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"12\",\"question_key\":\"f2\",\"prompt\":\"Compared with your forehead and nose, how do your cheeks usually feel?\",\"options_json\":\"[\\\"Both areas are dry\\\",\\\"Both areas are balanced\\\",\\\"Both areas are oily\\\",\\\"My cheeks are noticeably less oily\\\"]\",\"source_key\":\"sos2014\",\"rationale\":\"Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.\",\"concern_id\":null,\"is_core\":\"1\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:09:00\",\"updated_at\":\"2026-10-27 14:09:00\"},{\"id\":\"25\",\"question_key\":\"obs_breakouts\",\"prompt\":\"Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"baumann\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"9\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"26\",\"question_key\":\"obs_marks\",\"prompt\":\"Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"classification\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"20\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"27\",\"question_key\":\"obs_redness\",\"prompt\":\"Over the past four weeks, have you noticed recurring facial redness, itching or burning?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"baumann\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"22\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"},{\"id\":\"28\",\"question_key\":\"obs_lines\",\"prompt\":\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",\"options_json\":\"[\\\"Yes\\\",\\\"No\\\",\\\"Not sure\\\"]\",\"source_key\":\"classification\",\"rationale\":\"Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.\",\"concern_id\":\"16\",\"is_core\":\"0\",\"status\":\"active\",\"revision\":\"1\",\"created_at\":\"2026-10-27 14:12:05\",\"updated_at\":\"2026-10-27 14:12:05\"}],\"observed_concerns\":[{\"concern_id\":16,\"question\":\"Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?\",\"source\":\"classification\"}]}');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skin_questions`
--

INSERT INTO `skin_questions` (`id`, `question_key`, `prompt`, `options_json`, `source_key`, `rationale`, `concern_id`, `is_core`, `status`, `revision`, `created_at`, `updated_at`) VALUES
(1, 'q2', 'How does your face usually feel about 30 minutes after washing with a gentle cleanser, before applying skincare?', '[\"Tight or uncomfortable\",\"Comfortable and balanced\",\"Oily or shiny across most of my face\",\"Tight on my cheeks but oily around my nose or forehead\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(2, 'q3', 'How does your skin usually look by midday?', '[\"Mostly dry or matte, sometimes flaky\",\"Neither especially dry nor oily\",\"Shiny across most of my face\",\"Shiny mainly on my forehead, nose, and chin\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(3, 'q4', 'How would you describe your cheeks most days?', '[\"Dry, tight, or rough\",\"Comfortable and balanced\",\"Oily or shiny\",\"Usually normal or dry while my T-zone is oily\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(4, 'q5', 'How does your forehead, nose, and chin (T-zone) usually behave?', '[\"Often dry or tight\",\"Usually balanced\",\"Very oily, along with my cheeks\",\"Oily while my cheeks are noticeably less oily\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(5, 'q6', 'How frequently do you notice dry patches or flaking?', '[\"Frequently across several facial areas\",\"Rarely or never\",\"Rarely, because my face is usually oily\",\"Sometimes, especially on my cheeks\",\"Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(6, 'q7', 'Which description best matches your skin throughout a typical day?', '[\"Often feels dry and needs moisturizing\",\"Usually feels comfortable and balanced\",\"Produces noticeable oil over most of my face\",\"Has both oily and dry or normal areas\",\"Changes significantly or I\'m not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(7, 'q8', 'When you gently blot your face with clean tissue around midday, where do you usually notice oil?', '[\"Little or no oil anywhere\",\"A small amount distributed evenly\",\"Noticeable oil from both my T-zone and cheeks\",\"Oil mainly from my T-zone\",\"I haven\'t checked \\/ Not sure\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(8, 'q9', 'How often does your skin sting, burn, or become irritated after applying skincare products?', '[\"Never or rarely\",\"Occasionally\",\"Frequently\",\"Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(9, 'q10', 'How often do you experience redness, itching, or discomfort after using new products?', '[\"Never or rarely\",\"Sometimes\",\"Frequently\",\"Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(10, 'q11', 'How would you describe your skin\'s response to fragrances or strong skincare formulas?', '[\"Usually no noticeable reaction\",\"Occasional discomfort\",\"Frequent irritation or discomfort\",\"I haven\'t used these \\/ Not sure\"]', 'bstq2025', 'Adapted observation for PureVia; not an original validated instrument item. Preserve answer meanings and order when editing.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(11, 'f1', 'Which areas of your face are consistently oily?', '[\"None \\u2014 my skin is mostly dry\",\"No specific area \\u2014 my skin feels balanced\",\"Most of my face, including cheeks\",\"Mainly my forehead, nose, and chin\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(12, 'f2', 'Compared with your forehead and nose, how do your cheeks usually feel?', '[\"Both areas are dry\",\"Both areas are balanced\",\"Both areas are oily\",\"My cheeks are noticeably less oily\"]', 'sos2014', 'Adapted observation for PureVia; not an original validated instrument item. Clarifies regional oil distribution.', NULL, 1, 'active', 1, '2026-10-27 06:09:00', '2026-10-27 06:09:00'),
(25, 'obs_breakouts', 'Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?', '[\"Yes\",\"No\",\"Not sure\"]', 'baumann', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', 9, 0, 'active', 1, '2026-10-27 06:12:05', '2026-10-27 06:12:05'),
(26, 'obs_marks', 'Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?', '[\"Yes\",\"No\",\"Not sure\"]', 'classification', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', 20, 0, 'active', 1, '2026-10-27 06:12:05', '2026-10-27 06:12:05'),
(27, 'obs_redness', 'Over the past four weeks, have you noticed recurring facial redness, itching or burning?', '[\"Yes\",\"No\",\"Not sure\"]', 'baumann', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', 22, 0, 'active', 1, '2026-10-27 06:12:05', '2026-10-27 06:12:05'),
(28, 'obs_lines', 'Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?', '[\"Yes\",\"No\",\"Not sure\"]', 'classification', 'Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.', 16, 0, 'active', 1, '2026-10-27 06:12:05', '2026-10-27 06:12:05');

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
(1, 'Normal', 'Generally balanced skin with minimal dryness or excess oil.', 'active', '2026-09-25 13:49:35', 'normal'),
(2, 'Dry', 'Skin that may feel tight, rough, or lacking in moisture.', 'active', '2026-09-25 13:49:35', 'dry'),
(3, 'Oily', 'Skin that commonly produces excess oil or shine.', 'active', '2026-09-25 13:49:35', 'oily'),
(4, 'Combination', 'Skin with both oily and dry or normal areas.', 'active', '2026-09-25 13:49:35', 'combination'),
(5, 'Sensitive', 'Skin that may be more reactive to certain products or ingredients.', 'active', '2026-09-25 13:49:35', NULL);

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
(1, 'Daily Use', 'Appropriate for consistent everyday routines.', 'active', '2026-10-20 20:28:44', '2026-10-20 20:28:44'),
(2, 'Beginner Friendly', 'Suitable for customers new to active skincare.', 'active', '2026-10-20 20:28:44', '2026-10-20 20:28:44'),
(3, 'Sensitive Skin Friendly', 'Formulated with reactive skin in mind.', 'active', '2026-10-20 20:28:44', '2026-10-20 20:28:44'),
(4, 'Pregnancy Friendly', 'Free from ingredients commonly avoided during pregnancy.', 'active', '2026-10-20 20:28:44', '2026-10-20 20:28:44'),
(5, 'Fragrance Free', 'Made without added fragrance.', 'active', '2026-10-20 20:28:44', '2026-10-20 20:28:44');

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
(1, 1, 'Jasmin', 'Abelido', 'jasminabelido475@gmail.com', NULL, '$2y$10$97y2xEWqat8CIC9GQlrqMeHNN5YlwE5Zu3EHQbm2xJo74QtjnxtTm', '2026-10-20 16:29:34', 'active', NULL, 0, NULL, '2026-10-30 00:58:11', '2026-09-30 16:47:11', '2026-10-29 16:58:11', NULL, NULL, NULL, NULL, NULL, -1, 0),
(2, 2, 'Maria', 'Cuvin', 'mariadianne@gmail.com', NULL, '$2y$10$uRY2Mwthinkf99lNs7japeLF9II31WH.znD5KSVEZttSzphAXW8NC', '2026-10-17 09:17:41', 'active', NULL, 0, NULL, '2026-10-24 23:39:41', '2026-09-30 16:58:48', '2026-10-24 15:39:41', NULL, NULL, NULL, NULL, NULL, -1, 0),
(3, 3, 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '', '$2y$10$ZlGVOXu9aR/zHIux/zLsCeD15TVKrIVG1tR8nQm0QsjW2MZ3SpP/.', '2026-10-17 14:27:11', 'active', NULL, 0, NULL, '2026-10-30 02:09:04', '2026-09-30 16:59:12', '2026-10-29 18:09:04', NULL, NULL, NULL, NULL, NULL, -1, 0),
(25, 3, 'Bryan', 'Tambe', 'th3sis2026@gmail.com', NULL, '$2y$10$b.w..3hbJ6m/uLZG72zswudrbd6fcF788OzhzOCZsiLEXutGUnn62', '2026-10-20 06:58:35', 'active', '2026-10-20 06:58:02', 0, NULL, '2026-10-30 00:45:11', '2026-10-19 22:57:50', '2026-10-29 16:45:11', NULL, NULL, NULL, 's2IKBD3eS/Lj/7EMhzvj0Y5Ff5WDrHj7NR3z+nO1iW941z3IWu5WCOjhjt2zYCch7XZZtxjydsv1F91U', '2026-10-24 02:14:24', 59759308, 0),
(26, 3, 'Celine', 'Rabino', 'jasjoan16@gmail.com', NULL, '$2y$10$ECmLI/FL12AN18iLVZnhv.7CwDKcRvCnINS3yBrnHOrWcQsZY.r.O', NULL, 'locked', '2026-10-20 17:32:09', 5, NULL, NULL, '2026-10-20 09:31:48', '2026-10-20 09:32:49', NULL, NULL, NULL, NULL, NULL, -1, 0);

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

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_label`, `recipient_first_name`, `recipient_last_name`, `phone`, `address_line1`, `address_line2`, `barangay`, `city`, `province`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(3, 3, 'Home', 'Ahmhida', 'Macadato', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 1, '2026-10-14 10:14:33', '2026-10-14 10:14:33');

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
-- Dumping data for table `user_totp_recovery_codes`
--

INSERT INTO `user_totp_recovery_codes` (`id`, `user_id`, `code_hash`, `used_at`, `created_at`) VALUES
(1, 25, '$2y$10$fHSdM5G1XzqxRLFHvHXOOe/gC5KWNY4D9Weum6mWRCziTkZ72RN3O', '2026-10-25 00:29:09', '2026-10-23 18:14:24'),
(2, 25, '$2y$10$jp7nfN3Z..7.oXvjeM7Z9OBGIhetzpl2dC3ol.pY/dA2yoxIHAKTi', NULL, '2026-10-23 18:14:24'),
(3, 25, '$2y$10$y/VRmIorjUr1UO37SH5QWO2Ew2gaOirDwC2VuQYg7xaQc2/g.FKYu', NULL, '2026-10-23 18:14:24'),
(4, 25, '$2y$10$RHRKOPv71UnTibMratpUUuSQIy7xeLzrBU8El9cNqhsfWpRXAdgL.', NULL, '2026-10-23 18:14:25'),
(5, 25, '$2y$10$aGywTitq7Pzt/uXCFgLfIeWYGKCHJ0vQIct4VsTt7c/UPyC3H1pz6', NULL, '2026-10-23 18:14:25'),
(6, 25, '$2y$10$4mDwo3R2fOQLTo9txsDN4uwIG6zzPrPnuJgWMhrASUKujRr584VCa', NULL, '2026-10-23 18:14:25'),
(7, 25, '$2y$10$GhEbEKm0DLf7YkZy5Ac3l.OfFrn3c7GEjvMyZhT2BqTeVsBc6WIJS', NULL, '2026-10-23 18:14:25'),
(8, 25, '$2y$10$hOwEe1NhAyi0VRcyLzIZMONFfrQULz4TJo3/gv1ujRidhQ9/V8rXG', NULL, '2026-10-23 18:14:25');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_adjustments`
--
ALTER TABLE `inventory_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_benefits`
--
ALTER TABLE `product_benefits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skin_assessments`
--
ALTER TABLE `skin_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `skin_questions`
--
ALTER TABLE `skin_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_totp_recovery_codes`
--
ALTER TABLE `user_totp_recovery_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
