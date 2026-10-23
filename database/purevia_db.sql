-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 20, 2026 at 10:31 AM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 3, '2026-10-13 13:09:11');

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
(9, 25, '2c7608709ba8f03b495f00e04a3574e5e231213466cffea133c52dc759648af5', '2026-10-20 07:28:02', '2026-10-20 06:58:14', '2026-10-19 22:58:02');

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
(1, 'Niacinamide', 'Vitamin B3 that minimizes pores and evens skin tone', 'Serums, Toners', 'active', '2026-10-17 08:15:20', '2026-10-17 08:15:20');

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
(54, 1, 'jasminabelido475@gmail.com', '::1', 'Chrome', 1, NULL, '2026-10-20 08:29:34');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `first_name`, `last_name`, `email`, `phone`, `address_line1`, `address_line2`, `barangay`, `city`, `province`, `postal_code`, `country`, `subtotal`, `shipping_fee`, `total_amount`, `payment_status`, `order_status`, `created_at`, `updated_at`) VALUES
(3, 3, 'PV-20261014-38687E9C37', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 267.00, 8.00, 275.00, 'pending', 'cancelled', '2026-10-14 10:44:25', '2026-10-16 11:34:47'),
(4, 3, 'ORD-2026-7AD308C96D', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 160.00, 8.00, 168.00, 'pending', 'pending', '2026-10-14 12:47:33', '2026-10-14 12:47:33'),
(5, 3, 'ORD-2026-3AB6BCF589', 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '09936008933', 'Blk 3 Mangga St. Nagpayong Pinagbuhatan', '', 'Ermita', 'Manila', 'Metro Manila', '1602', 'Philippines', 40.00, 8.00, 48.00, 'pending', 'pending', '2026-10-17 06:33:50', '2026-10-17 06:33:50');

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
(5, 5, 2, 'SDADAD (REG 50g)', 2, 20.00, 40.00);

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
(22, 25, '$2y$10$vX.A4drCfJzXtiTjL0e7QOZewBtSsBJHGu4Upp.nGOQVn0jK9dEVy', 'email_verification', '2026-10-20 07:07:50', '2026-10-20 06:58:02', '2026-10-19 22:57:50', 0);

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
(5, 5, 'cod', 48.00, 'pending', NULL, NULL, NULL, NULL, '2026-10-17 06:33:50', '2026-10-17 06:33:50');

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
(1, 3, 'PureVia Matcha Clarify Moisturizer', 'MST-001', 'Refresh and balance your skin with PureVia Matcha Clarify Moisturizer, featuring Matcha Extract, a plant-based ingredient rich in antioxidants known for its skin-conditioning properties. Designed for everyday skincare, this lightweight moisturizer helps control excess oil, minimize the appearance of pores, and provide essential hydration without leaving a greasy feeling, promoting a clearer, smoother, and healthier-looking complexion.', NULL, 89.00, 7, NULL, 'active', '2026-10-09 19:01:23', '2026-10-14 10:44:25'),
(2, 1, 'SDADAD', 'DMO-001', 'ASDADA', NULL, 20.00, 2, 'uploads/products/3715746d17844d01a7629c2a9bad4f1a.jpg', 'active', '2026-10-09 21:11:47', '2026-10-17 06:33:50');

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
(1, 1, '50g', 'Travel size', 89.00, 7, '2026-10-09 19:01:23', '2026-10-14 10:44:25', NULL, 15, NULL),
(2, 2, '50g', 'REG', 20.00, 2, '2026-10-09 21:11:47', '2026-10-17 06:33:50', NULL, 15, NULL);

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
  `min_uppercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_lowercase` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `min_special_character` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `password_expiry_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `require_otp` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(9, 'Acne', 'Treatment and care for acne concerns', 'active', '2026-10-17 08:27:00');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `skin_types`
--

CREATE TABLE `skin_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `skin_type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skin_types`
--

INSERT INTO `skin_types` (`id`, `skin_type_name`, `description`, `status`, `created_at`) VALUES
(1, 'Normal', 'Generally balanced skin with minimal dryness or excess oil.', 'active', '2026-09-25 13:49:35'),
(2, 'Dry', 'Skin that may feel tight, rough, or lacking in moisture.', 'active', '2026-09-25 13:49:35'),
(3, 'Oily', 'Skin that commonly produces excess oil or shine.', 'active', '2026-09-25 13:49:35'),
(4, 'Combination', 'Skin with both oily and dry or normal areas.', 'active', '2026-09-25 13:49:35'),
(5, 'Sensitive', 'Skin that may be more reactive to certain products or ingredients.', 'active', '2026-09-25 13:49:35');

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
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `status`, `email_verified_at`, `failed_login_attempts`, `locked_until`, `last_login_at`, `created_at`, `updated_at`, `gender`, `birth_date`, `profile_photo`) VALUES
(1, 1, 'Jasmin', 'Abelido', 'jasminabelido475@gmail.com', NULL, '$2y$10$97y2xEWqat8CIC9GQlrqMeHNN5YlwE5Zu3EHQbm2xJo74QtjnxtTm', 'active', NULL, 0, NULL, '2026-10-20 16:29:34', '2026-09-30 16:47:11', '2026-10-20 08:29:34', NULL, NULL, NULL),
(2, 2, 'Maria', 'Cuvin', 'mariadianne@gmail.com', NULL, '$2y$10$uRY2Mwthinkf99lNs7japeLF9II31WH.znD5KSVEZttSzphAXW8NC', 'active', NULL, 0, NULL, '2026-10-17 09:17:41', '2026-09-30 16:58:48', '2026-10-17 01:17:41', NULL, NULL, NULL),
(3, 3, 'Ahmhida', 'Macadato', 'amimacadato@gmail.com', '', '$2y$10$ZlGVOXu9aR/zHIux/zLsCeD15TVKrIVG1tR8nQm0QsjW2MZ3SpP/.', 'active', NULL, 0, NULL, '2026-10-17 14:27:11', '2026-09-30 16:59:12', '2026-10-17 06:27:11', NULL, NULL, NULL),
(25, 3, 'Bryan', 'Tambe', 'th3sis2026@gmail.com', NULL, '$2y$10$b.w..3hbJ6m/uLZG72zswudrbd6fcF788OzhzOCZsiLEXutGUnn62', 'active', '2026-10-20 06:58:02', 0, NULL, '2026-10-20 06:58:35', '2026-10-19 22:57:50', '2026-10-19 22:58:35', NULL, NULL, NULL);

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

--
-- Indexes for dumped tables
--

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
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_user` (`user_id`),
  ADD KEY `idx_otp_expires` (`expires_at`);

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
-- Indexes for table `skin_types`
--
ALTER TABLE `skin_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skin_type_name` (`skin_type_name`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `skin_concerns`
--
ALTER TABLE `skin_concerns`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `skin_profiles`
--
ALTER TABLE `skin_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skin_types`
--
ALTER TABLE `skin_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
