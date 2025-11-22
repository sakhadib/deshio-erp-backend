-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 03:37 PM
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
-- Database: `deshio`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('asset','liability','equity','income','expense') NOT NULL,
  `sub_type` enum('current_asset','fixed_asset','other_asset','current_liability','long_term_liability','owner_equity','retained_earnings','sales_revenue','other_income','cost_of_goods_sold','operating_expenses','other_expenses') DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `level` int(11) NOT NULL DEFAULT 1,
  `path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_code`, `name`, `description`, `type`, `sub_type`, `parent_id`, `is_active`, `level`, `path`, `created_at`, `updated_at`) VALUES
(1, '1000', 'Current Assets', NULL, 'asset', 'current_asset', NULL, 1, 1, '1', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(2, '1001', 'Cash and Cash Equivalents', NULL, 'asset', 'current_asset', 1, 1, 2, '1/2', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(3, '1002', 'Accounts Receivable', NULL, 'asset', 'current_asset', 1, 1, 2, '1/3', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(4, '1003', 'Inventory', NULL, 'asset', 'current_asset', 1, 1, 2, '1/4', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(5, '1100', 'Fixed Assets', NULL, 'asset', 'fixed_asset', NULL, 1, 1, '5', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(6, '1101', 'Property, Plant and Equipment', NULL, 'asset', 'fixed_asset', 5, 1, 2, '5/6', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(7, '1102', 'Accumulated Depreciation', NULL, 'asset', 'fixed_asset', 5, 1, 2, '5/7', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(8, '2000', 'Current Liabilities', NULL, 'liability', 'current_liability', NULL, 1, 1, '8', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(9, '2001', 'Accounts Payable', NULL, 'liability', 'current_liability', 8, 1, 2, '8/9', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(10, '3000', 'Owner Equity', NULL, 'equity', 'owner_equity', NULL, 1, 1, '10', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(11, '3001', 'Retained Earnings', NULL, 'equity', 'retained_earnings', 10, 1, 2, '10/11', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(12, '4000', 'Revenue', NULL, 'income', 'sales_revenue', NULL, 1, 1, '12', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(13, '4001', 'Sales Revenue', NULL, 'income', 'sales_revenue', 12, 1, 2, '12/13', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(14, '4002', 'Service Revenue', NULL, 'income', 'other_income', 12, 1, 2, '12/14', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(15, '5000', 'Expenses', NULL, 'expense', 'operating_expenses', NULL, 1, 1, '15', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(16, '5001', 'Operating Expenses', NULL, 'expense', 'operating_expenses', 15, 1, 2, '15/16', '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(17, '5002', 'Cost of Goods Sold', NULL, 'expense', 'cost_of_goods_sold', 15, 1, 2, '15/17', '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','saved') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_denominations`
--

CREATE TABLE `cash_denominations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_split_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('received','change') NOT NULL DEFAULT 'received',
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `denomination_value` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cash_type` enum('note','coin') NOT NULL DEFAULT 'note',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0,
  `path` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `level`, `path`, `title`, `description`, `image`, `color`, `icon`, `slug`, `order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 0, NULL, 'Sharee', NULL, 'categories/1763646039_sharee.webp', NULL, NULL, 'sharee', 0, 1, '2025-11-20 07:40:39', '2025-11-20 07:40:39', NULL),
(2, NULL, 0, NULL, 'Shotoronji', 'Shtotoronji', 'categories/1763646074_shotoronji.jpg', NULL, NULL, 'shotoronji', 0, 1, '2025-11-20 07:41:14', '2025-11-20 07:41:14', NULL),
(3, 1, 1, '1', 'Jamdani', 'Jamdani', 'categories/1763646103_jamdani.webp', NULL, NULL, 'jamdani', 0, 1, '2025-11-20 07:41:43', '2025-11-20 07:41:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('season','occasion','category','campaign') NOT NULL DEFAULT 'season',
  `season` varchar(255) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `launch_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collection_product`
--

CREATE TABLE `collection_product` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `collection_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_type` enum('counter','social_commerce','ecommerce') NOT NULL DEFAULT 'counter',
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `social_profiles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_profiles`)),
  `customer_code` varchar(255) NOT NULL,
  `total_purchases` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `last_purchase_at` timestamp NULL DEFAULT NULL,
  `first_purchase_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_type`, `name`, `phone`, `email`, `password`, `email_verified_at`, `remember_token`, `address`, `city`, `state`, `postal_code`, `country`, `date_of_birth`, `gender`, `preferences`, `social_profiles`, `customer_code`, `total_purchases`, `total_orders`, `last_purchase_at`, `first_purchase_at`, `status`, `notes`, `created_by`, `assigned_employee_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'counter', 'Rumy Parvez', '01818316964', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-3B60FB', 1000.00, 1, '2025-11-20 08:24:05', '2025-11-20 08:24:05', 'active', NULL, 1, NULL, '2025-11-20 08:24:03', '2025-11-20 08:24:05', NULL),
(2, 'social_commerce', 'Nishat Tasneem', '01818316965', 'tasneem.nishat.4953@gmail.com', NULL, NULL, NULL, 'Dhunot, Bogura, Rajshahi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-1CE8C6', 1000.00, 1, '2025-11-20 08:41:33', '2025-11-20 08:41:33', 'active', NULL, 1, NULL, '2025-11-20 08:41:31', '2025-11-20 08:41:33', NULL),
(3, 'counter', 'Walk-in Customer', 'WALK-IN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-F035D7', 3600.00, 3, '2025-11-20 09:50:54', '2025-11-20 08:47:26', 'active', NULL, 1, NULL, '2025-11-20 08:47:24', '2025-11-20 09:50:54', NULL),
(4, 'counter', 'Sneha', '01818313467', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-7732BD', 300.00, 1, '2025-11-20 08:55:38', '2025-11-20 08:55:38', 'active', NULL, 1, NULL, '2025-11-20 08:55:37', '2025-11-20 08:55:38', NULL),
(5, 'counter', 'Ariyaan', '01818316764', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-238019', 3000.00, 1, '2025-11-20 09:23:36', '2025-11-20 09:23:36', 'active', NULL, 1, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:36', NULL),
(6, 'counter', 'Rodoshi', '09818316964', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-BAE693', 11000.00, 3, '2025-11-22 00:17:13', '2025-11-20 09:58:43', 'active', NULL, 1, NULL, '2025-11-20 09:58:42', '2025-11-22 00:17:13', NULL),
(7, 'counter', 'Rodoshi', '01818318964', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-94437A', 3000.00, 1, '2025-11-22 01:18:53', '2025-11-22 01:18:53', 'active', NULL, 1, NULL, '2025-11-22 01:18:51', '2025-11-22 01:18:53', NULL),
(8, 'counter', 'Nilu', '01818566964', NULL, NULL, NULL, NULL, 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CUST-2025-855CB8', 3000.00, 1, '2025-11-22 08:21:39', '2025-11-22 08:21:39', 'active', NULL, 1, NULL, '2025-11-22 08:21:38', '2025-11-22 08:21:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('shipping','billing','both') NOT NULL DEFAULT 'both',
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `postal_code` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Bangladesh',
  `landmark` varchar(255) DEFAULT NULL,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `defective_products`
--

CREATE TABLE `defective_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_barcode_id` bigint(20) UNSIGNED NOT NULL,
  `product_batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `defect_type` varchar(255) NOT NULL,
  `defect_description` text NOT NULL,
  `defect_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`defect_images`)),
  `severity` enum('minor','moderate','major','critical') NOT NULL DEFAULT 'moderate',
  `original_price` decimal(10,2) NOT NULL,
  `suggested_selling_price` decimal(10,2) DEFAULT NULL,
  `minimum_selling_price` decimal(10,2) DEFAULT NULL,
  `status` enum('identified','inspected','available_for_sale','sold','disposed','returned_to_vendor') NOT NULL DEFAULT 'identified',
  `identified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `inspected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `sold_by` bigint(20) UNSIGNED DEFAULT NULL,
  `identified_at` timestamp NULL DEFAULT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actual_selling_price` decimal(10,2) DEFAULT NULL,
  `sale_notes` text DEFAULT NULL,
  `disposal_notes` text DEFAULT NULL,
  `disposed_at` timestamp NULL DEFAULT NULL,
  `vendor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `returned_to_vendor_at` timestamp NULL DEFAULT NULL,
  `vendor_notes` text DEFAULT NULL,
  `source_return_id` bigint(20) UNSIGNED DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `defective_products`
--

INSERT INTO `defective_products` (`id`, `product_id`, `product_barcode_id`, `product_batch_id`, `store_id`, `defect_type`, `defect_description`, `defect_images`, `severity`, `original_price`, `suggested_selling_price`, `minimum_selling_price`, `status`, `identified_by`, `inspected_by`, `sold_by`, `identified_at`, `inspected_at`, `sold_at`, `order_id`, `actual_selling_price`, `sale_notes`, `disposal_notes`, `disposed_at`, `vendor_id`, `returned_to_vendor_at`, `vendor_notes`, `source_return_id`, `internal_notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 7, 1, 1, 'malfunction', 'Auto-marked from return #RET-20251120-0001: defective_product - notes', NULL, 'moderate', 1000.00, 750.00, 200.00, 'sold', 1, 1, 1, '2025-11-20 08:50:07', '2025-11-20 08:54:17', '2025-11-20 08:55:37', 4, 300.00, 'Sold via POS/Commerce at 2025-11-20T14:55:37.502Z', NULL, NULL, NULL, NULL, NULL, 1, 'Auto-inspected for sale preparation', NULL, '2025-11-20 08:50:07', '2025-11-20 08:55:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'email_verification' COMMENT 'email_verification, password_reset, etc.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `is_in_service` tinyint(1) NOT NULL DEFAULT 1,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `employee_code` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `avatar` varchar(255) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `email_verified_at`, `password`, `store_id`, `is_in_service`, `role_id`, `phone`, `address`, `employee_code`, `hire_date`, `department`, `salary`, `manager_id`, `is_active`, `avatar`, `last_login_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Mueed Sami', 'mueedibnesami.anoy@gmail.com', '2025-11-20 07:33:15', '$2y$10$dOshLHBIXD.zXNG9MCMDiufcEmXVy7ZmXtHdnYLFbIYlo2AAAYzPG', 1, 1, 1, '+1234567890', '123 Admin Street, Admin City, Admin Country', 'EMP-001', '2025-11-20', 'Administration', 100000.00, NULL, 1, NULL, '2025-11-22 07:11:05', NULL, '2025-11-20 07:33:15', '2025-11-22 07:11:05', NULL),
(2, 'Preownti', 'tasneem.nishat.4953@gmail.com', NULL, '$2y$10$rmpwQCzIcihaOQqL2VqpxeDDYJbTyvYg8MbQ95Xm9jII7.7Iuc/jG', 2, 1, 2, '+8801818318964', 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, '2025-11-20', 'Sales', 10000.00, NULL, 1, NULL, NULL, NULL, '2025-11-20 09:07:06', '2025-11-20 09:07:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_m_f_a_backup_codes`
--

CREATE TABLE `employee_m_f_a_backup_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_mfa_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_m_f_a_s`
--

CREATE TABLE `employee_m_f_a_s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL COMMENT 'sms, email, totp, backup_codes',
  `secret` text DEFAULT NULL COMMENT 'TOTP secret key',
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Backup recovery codes' CHECK (json_valid(`backup_codes`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional MFA settings' CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_sessions`
--

CREATE TABLE `employee_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_number` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(10,2) NOT NULL,
  `status` enum('draft','pending_approval','approved','rejected','cancelled','processing','completed') NOT NULL DEFAULT 'draft',
  `payment_status` enum('unpaid','partially_paid','paid','overpaid','refunded') NOT NULL DEFAULT 'unpaid',
  `expense_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `vendor_invoice_number` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `notes` text DEFAULT NULL,
  `expense_type` enum('vendor_payment','salary_payment','utility_bill','rent_lease','logistics','maintenance','marketing','insurance','taxes','supplies','travel','training','software','bank_charges','depreciation','miscellaneous') NOT NULL DEFAULT 'miscellaneous',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_type` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT NULL,
  `recurrence_interval` int(11) NOT NULL DEFAULT 1,
  `recurrence_end_date` date DEFAULT NULL,
  `parent_expense_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `approval_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_number`, `category_id`, `vendor_id`, `employee_id`, `store_id`, `created_by`, `approved_by`, `processed_by`, `amount`, `tax_amount`, `discount_amount`, `total_amount`, `paid_amount`, `outstanding_amount`, `status`, `payment_status`, `expense_date`, `due_date`, `approved_at`, `processed_at`, `completed_at`, `reference_number`, `vendor_invoice_number`, `description`, `notes`, `expense_type`, `is_recurring`, `recurrence_type`, `recurrence_interval`, `recurrence_end_date`, `parent_expense_id`, `attachments`, `metadata`, `approval_notes`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'EXP-20251120-BDBABA', 1, NULL, NULL, 1, 1, 1, NULL, 45000.00, 0.00, 0.00, 45000.00, 0.00, 45000.00, 'approved', 'unpaid', '2025-11-15', '2025-11-30', '2025-11-17 07:33:15', NULL, NULL, 'REF-2024-001', NULL, 'Raw Material Purchase - Q4 2024: Bulk purchase of raw materials for production', NULL, 'vendor_payment', 0, NULL, 1, NULL, NULL, NULL, '\"{\\\"vendor_name\\\":\\\"ABC Suppliers Ltd\\\",\\\"invoice_number\\\":\\\"INV-2024-001\\\",\\\"payment_terms\\\":\\\"Net 30 days\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(2, 'EXP-20251120-BDC1F8', 3, NULL, NULL, 1, 1, 1, NULL, 12500.00, 0.00, 0.00, 12500.00, 12500.00, 0.00, 'completed', 'paid', '2025-11-05', '2025-11-15', '2025-11-08 07:33:15', '2025-11-15 07:33:15', '2025-11-15 07:33:15', 'DESCO-OCT-2024', NULL, 'Electricity Bill - October 2024: Monthly electricity consumption for office and warehouse', NULL, 'utility_bill', 1, 'monthly', 1, NULL, NULL, NULL, '\"{\\\"utility_provider\\\":\\\"Dhaka Electric Supply Company\\\",\\\"account_number\\\":\\\"DESCO-123456\\\",\\\"billing_period\\\":\\\"October 1-31, 2024\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(3, 'EXP-20251120-BDC237', 6, NULL, NULL, 1, 1, NULL, NULL, 25000.00, 0.00, 0.00, 25000.00, 0.00, 25000.00, 'pending_approval', 'unpaid', '2025-11-20', '2025-11-27', NULL, NULL, NULL, 'SMAD-2024-Q4', NULL, 'Social Media Advertising Campaign: Facebook and Instagram ads for product promotion', NULL, 'marketing', 0, NULL, 1, NULL, NULL, NULL, '\"{\\\"campaign_name\\\":\\\"Winter Collection Launch\\\",\\\"platforms\\\":[\\\"Facebook\\\",\\\"Instagram\\\"],\\\"target_audience\\\":\\\"18-35 years, Dhaka region\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(4, 'EXP-20251120-BDC24C', 2, NULL, NULL, 1, 1, 1, NULL, 85000.00, 0.00, 0.00, 85000.00, 0.00, 85000.00, 'approved', 'unpaid', '2025-11-19', '2025-11-25', '2025-11-19 07:33:15', NULL, NULL, 'SAL-OCT-2024', NULL, 'Monthly Salary Payment - October 2024: Salary payment for all employees', NULL, 'salary_payment', 1, 'monthly', 1, NULL, NULL, NULL, '\"{\\\"payroll_period\\\":\\\"October 1-31, 2024\\\",\\\"number_of_employees\\\":15,\\\"payment_method\\\":\\\"Bank Transfer\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(5, 'EXP-20251120-BDC26A', 4, NULL, NULL, 1, 1, 1, NULL, 8500.00, 0.00, 0.00, 8500.00, 8500.00, 0.00, 'completed', 'paid', '2025-11-10', '2025-11-18', '2025-11-12 07:33:15', '2025-11-18 07:33:15', '2025-11-18 07:33:15', 'PATHAO-NOV-2024', NULL, 'Courier Service Charges: Shipping charges for customer orders', NULL, 'logistics', 0, NULL, 1, NULL, NULL, NULL, '\"{\\\"courier_service\\\":\\\"Pathao Courier\\\",\\\"number_of_shipments\\\":45,\\\"service_type\\\":\\\"Express Delivery\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(6, 'EXP-20251120-BDC292', 5, NULL, NULL, 1, 1, 1, NULL, 18000.00, 0.00, 0.00, 18000.00, 0.00, 18000.00, 'approved', 'unpaid', '2025-10-31', '2025-11-30', '2025-11-02 07:33:15', NULL, NULL, 'MAINT-Q4-2024', NULL, 'Equipment Maintenance: Quarterly maintenance of production equipment', NULL, 'maintenance', 1, 'quarterly', 3, NULL, NULL, NULL, '\"{\\\"maintenance_type\\\":\\\"Preventive Maintenance\\\",\\\"equipment_list\\\":[\\\"Sewing Machines\\\",\\\"Packaging Equipment\\\"],\\\"service_provider\\\":\\\"TechMaintenance Ltd\\\"}\"', NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('operational','capital','personnel','marketing','administrative','logistics','utilities','maintenance','taxes','insurance','other') NOT NULL DEFAULT 'operational',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `monthly_budget` decimal(12,2) DEFAULT NULL,
  `yearly_budget` decimal(12,2) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approval_threshold` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `code`, `description`, `type`, `parent_id`, `monthly_budget`, `yearly_budget`, `requires_approval`, `approval_threshold`, `is_active`, `sort_order`, `icon`, `color`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 'Vendor Payments', 'VENDOR_PAY', 'Payments to suppliers and vendors for goods and services', 'operational', NULL, NULL, NULL, 1, 50000.00, 1, 1, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(2, 'Employee Salaries', 'EMP_SALARY', 'Monthly salary payments to employees', 'personnel', NULL, NULL, NULL, 1, 100000.00, 1, 2, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(3, 'Utilities', 'UTILITIES', 'Electricity, water, gas, and other utility bills', 'utilities', NULL, 50000.00, 600000.00, 1, 25000.00, 1, 3, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(4, 'Logistics', 'LOGISTICS', 'Shipping, transportation, and delivery costs', 'logistics', NULL, 75000.00, 900000.00, 1, 30000.00, 1, 4, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(5, 'Maintenance', 'MAINTENANCE', 'Equipment and facility maintenance costs', 'maintenance', NULL, 25000.00, 300000.00, 1, 15000.00, 1, 5, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(6, 'Marketing', 'MARKETING', 'Advertising, promotions, and marketing expenses', 'marketing', NULL, 100000.00, 1200000.00, 1, 50000.00, 1, 6, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(7, 'Insurance', 'INSURANCE', 'Business insurance premiums', 'insurance', NULL, 30000.00, 360000.00, 1, 20000.00, 1, 7, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(8, 'Taxes', 'TAXES', 'Business taxes and related fees', 'taxes', NULL, NULL, NULL, 1, 50000.00, 1, 8, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(9, 'Supplies', 'SUPPLIES', 'Office supplies and consumables', 'administrative', NULL, 15000.00, 180000.00, 0, 5000.00, 1, 9, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(10, 'Travel', 'TRAVEL', 'Business travel and accommodation expenses', 'operational', NULL, 50000.00, 600000.00, 1, 25000.00, 1, 10, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(11, 'Training', 'TRAINING', 'Employee training and development costs', 'personnel', NULL, 30000.00, 360000.00, 1, 15000.00, 1, 11, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(12, 'Software Licenses', 'SOFTWARE', 'Software subscriptions and licensing fees', 'operational', NULL, 25000.00, 300000.00, 1, 10000.00, 1, 12, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(13, 'Bank Charges', 'BANK_CHARGES', 'Bank fees, transaction charges, and service fees', 'administrative', NULL, 5000.00, 60000.00, 0, 2000.00, 1, 13, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(14, 'Depreciation', 'DEPRECIATION', 'Asset depreciation expenses', 'other', NULL, NULL, NULL, 1, 50000.00, 1, 14, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(15, 'Miscellaneous', 'MISC', 'Other operating expenses not categorized elsewhere', 'other', NULL, 20000.00, 240000.00, 0, 10000.00, 1, 15, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(16, 'Raw Materials', 'RAW_MAT', 'Payment for raw materials and components', 'operational', 1, NULL, NULL, 1, 30000.00, 1, 1, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(17, 'Packaging', 'PACKAGING', 'Payment for packaging materials', 'operational', 1, 20000.00, 240000.00, 1, 15000.00, 1, 2, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(18, 'Services', 'SERVICES', 'Payment for professional services', 'operational', 1, 50000.00, 600000.00, 1, 25000.00, 1, 3, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(19, 'Digital Marketing', 'DIGITAL_MKT', 'Online advertising and digital marketing expenses', 'marketing', 6, 40000.00, 480000.00, 1, 20000.00, 1, 1, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(20, 'Print Media', 'PRINT_MEDIA', 'Newspaper, magazine, and print advertising', 'marketing', 6, 30000.00, 360000.00, 1, 15000.00, 1, 2, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(21, 'Events', 'EVENTS', 'Trade shows, exhibitions, and promotional events', 'marketing', 6, 30000.00, 360000.00, 1, 15000.00, 1, 3, NULL, NULL, NULL, '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `expense_payments`
--

CREATE TABLE `expense_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `expense_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `processed_by` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refund_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_receipts`
--

CREATE TABLE `expense_receipts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_extension` varchar(10) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

CREATE TABLE `fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL COMMENT 'text|number|date|file|select|textarea|boolean|email|url',
  `description` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `default_value` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'For select/radio fields: {"option1": "value1", "option2": "value2"}' CHECK (json_valid(`options`)),
  `validation_rules` varchar(255) DEFAULT NULL COMMENT 'Laravel validation rules like "required|email|max:255"',
  `placeholder` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `title`, `type`, `description`, `is_required`, `default_value`, `options`, `validation_rules`, `placeholder`, `order`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Color', 'text', NULL, 0, NULL, NULL, NULL, NULL, 1, 1, '2025-11-20 07:42:36', '2025-11-20 07:42:36', NULL),
(2, 'Size', 'text', NULL, 0, NULL, NULL, NULL, NULL, 2, 1, '2025-11-20 07:42:46', '2025-11-20 07:42:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_rebalancings`
--

CREATE TABLE `inventory_rebalancings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `source_batch_id` bigint(20) UNSIGNED NOT NULL,
  `source_store_id` bigint(20) UNSIGNED NOT NULL,
  `destination_store_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','approved','in_transit','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `reason` text DEFAULT NULL,
  `estimated_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `requested_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `dispatch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_inventories`
--

CREATE TABLE `master_inventories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `damaged_quantity` int(11) NOT NULL DEFAULT 0,
  `minimum_stock_level` int(11) NOT NULL DEFAULT 0,
  `maximum_stock_level` int(11) DEFAULT NULL,
  `reorder_point` int(11) NOT NULL DEFAULT 0,
  `average_cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `average_sell_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_status` enum('out_of_stock','low_stock','normal','overstocked') NOT NULL DEFAULT 'normal',
  `store_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_breakdown`)),
  `batch_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`batch_breakdown`)),
  `last_updated_at` timestamp NULL DEFAULT NULL,
  `last_counted_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(2, '2025_10_19_124619_create_categories_table', 1),
(3, '2025_10_19_125208_create_vendors_table', 1),
(4, '2025_10_19_125918_create_stores_table', 1),
(5, '2025_10_19_130850_create_fields_table', 1),
(6, '2025_10_19_131437_create_permissions_table', 1),
(7, '2025_10_19_132019_create_roles_table', 1),
(8, '2025_10_19_132209_create_role_permissions_table', 1),
(9, '2025_10_19_132919_create_employees_table', 1),
(10, '2025_10_20_040643_create_employee_sessions_table', 1),
(11, '2025_10_20_041030_create_email_verification_tokens_table', 1),
(12, '2025_10_20_041333_create_password_reset_tokens_table', 1),
(13, '2025_10_20_042224_create_employee_m_f_a_s_table', 1),
(14, '2025_10_20_042431_create_employee_m_f_a_backup_codes_table', 1),
(15, '2025_10_20_042846_create_notes_table', 1),
(16, '2025_10_20_043231_create_products_table', 1),
(17, '2025_10_20_043526_create_product_fields_table', 1),
(18, '2025_10_20_044733_create_product_images_table', 1),
(19, '2025_10_20_045402_create_product_barcodes_table', 1),
(20, '2025_10_20_050100_create_product_price_overrides_table', 1),
(21, '2025_10_20_052015_create_product_batches_table', 1),
(22, '2025_10_20_052915_create_product_dispatches_table', 1),
(23, '2025_10_20_053455_create_product_dispatch_items_table', 1),
(24, '2025_10_20_054000_create_product_dispatch_item_barcodes_table', 1),
(25, '2025_10_20_054347_create_product_movements_table', 1),
(26, '2025_10_20_054814_create_master_inventories_table', 1),
(27, '2025_10_20_054921_create_inventory_rebalancings_table', 1),
(28, '2025_10_20_055444_create_customers_table', 1),
(29, '2025_10_20_055729_create_orders_table', 1),
(30, '2025_10_20_055756_create_order_items_table', 1),
(31, '2025_10_20_060140_add_authentication_fields_to_customers_table', 1),
(32, '2025_10_20_060433_create_shipments_table', 1),
(33, '2025_10_20_063135_create_product_returns_table', 1),
(34, '2025_10_20_063137_create_refunds_table', 1),
(35, '2025_10_22_042539_create_payment_methods_table', 1),
(36, '2025_10_22_042546_create_order_payments_table', 1),
(37, '2025_10_22_043530_create_services_table', 1),
(38, '2025_10_22_043544_create_service_fields_table', 1),
(39, '2025_10_22_044739_create_service_orders_table', 1),
(40, '2025_10_22_044748_create_service_order_items_table', 1),
(41, '2025_10_22_044757_create_service_order_payments_table', 1),
(42, '2025_10_22_045534_create_expense_categories_table', 1),
(43, '2025_10_22_045541_create_expenses_table', 1),
(44, '2025_10_22_045547_create_expense_payments_table', 1),
(45, '2025_10_22_050345_create_transactions_table', 1),
(46, '2025_10_22_050608_create_accounts_table', 1),
(47, '2025_10_29_055717_update_orders_table_for_fragmented_payments', 1),
(48, '2025_10_29_055744_update_order_payments_table_for_fragmented_payments', 1),
(49, '2025_10_29_055803_update_service_order_payments_table_for_fragmented_payments', 1),
(50, '2025_10_29_055828_update_service_orders_table_for_fragmented_payments', 1),
(51, '2025_11_04_000001_create_payment_splits_table', 1),
(52, '2025_11_04_000002_create_cash_denominations_table', 1),
(53, '2025_11_04_100001_create_purchase_orders_table', 1),
(54, '2025_11_04_100002_create_purchase_order_items_table', 1),
(55, '2025_11_04_100003_create_vendor_payments_table', 1),
(56, '2025_11_04_100004_create_vendor_payment_items_table', 1),
(57, '2025_11_04_121517_add_pathao_delivery_fields_to_product_dispatches_table', 1),
(58, '2025_11_07_052324_add_parent_id_to_categories_table', 1),
(59, '2025_11_07_053437_create_defective_products_table', 1),
(60, '2025_11_07_053702_add_is_defective_to_product_barcodes_table', 1),
(61, '2025_11_07_095148_add_image_to_categories_table', 1),
(62, '2025_11_08_051045_remove_unique_constraint_from_products_sku', 1),
(63, '2025_11_09_042046_add_dispatch_id_to_inventory_rebalancings_table', 1),
(64, '2025_11_09_074605_add_batch_id_to_product_barcodes_table', 1),
(65, '2025_11_09_075143_add_product_barcode_id_to_order_items_table', 1),
(66, '2025_11_10_080000_add_barcode_location_tracking', 1),
(67, '2025_11_12_130229_add_soft_deletes_to_multiple_tables', 1),
(68, '2025_11_12_133214_create_promotions_table', 1),
(69, '2025_11_12_133215_create_promotion_usages_table', 1),
(70, '2025_11_12_133543_create_variant_options_table', 1),
(71, '2025_11_12_133544_create_product_variants_table', 1),
(72, '2025_11_12_134709_create_collections_table', 1),
(73, '2025_11_12_151238_add_deleted_at_to_order_payments_table', 1),
(74, '2025_11_13_070231_fix_purchase_orders_and_vendors_issues', 1),
(75, '2025_11_15_082515_make_payment_method_id_nullable_in_order_payments_table', 1),
(76, '2025_11_16_064953_make_store_id_nullable_in_expenses_table', 1),
(77, '2025_11_16_085054_create_expense_receipts_table', 1),
(78, '2025_11_16_171017_make_reference_id_nullable_in_transactions_table', 1),
(79, '2025_11_16_174247_add_defective_movement_type_to_product_movements_table', 1),
(80, '2025_11_16_174431_make_to_store_id_nullable_in_product_movements_table', 1),
(81, '2025_11_16_175130_make_product_movements_fields_nullable', 1),
(82, '2025_11_17_112727_make_reference_id_nullable_in_transactions_table_v2', 1),
(83, '2025_11_18_000001_add_soft_deletes_to_refunds_table', 1),
(84, '2025_11_18_144426_create_carts_table', 1),
(85, '2025_11_18_144735_create_wishlists_table', 1),
(86, '2025_11_18_150000_create_customer_addresses_table', 1),
(87, '2025_11_18_151925_add_missing_barcode_statuses_to_product_barcodes', 1),
(88, '2025_11_19_181830_add_source_return_id_to_defective_products_table', 1),
(89, '2025_11_19_183033_add_received_at_store_id_to_product_returns_table', 1),
(90, '2025_11_22_000000_add_cogs_to_order_items_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general' COMMENT 'general, hr, performance, disciplinary, medical',
  `is_private` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Only visible to HR/managers',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data like attachments, tags' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `order_type` enum('counter','social_commerce','ecommerce') NOT NULL DEFAULT 'counter',
  `status` enum('pending','confirmed','processing','ready_for_pickup','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','partially_paid','paid','failed','refunded','overdue') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','bank_transfer','digital_wallet','cod') DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_installment_payment` tinyint(1) NOT NULL DEFAULT 0,
  `total_installments` int(11) DEFAULT NULL,
  `paid_installments` int(11) NOT NULL DEFAULT 0,
  `installment_amount` decimal(10,2) DEFAULT NULL,
  `next_payment_due` date DEFAULT NULL,
  `allow_partial_payments` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_payment_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_address`)),
  `billing_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_address`)),
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `shipped_by` bigint(20) UNSIGNED DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier_name` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `payment_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_schedule`)),
  `payment_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `store_id`, `order_type`, `status`, `payment_status`, `payment_method`, `subtotal`, `tax_amount`, `discount_amount`, `shipping_amount`, `total_amount`, `paid_amount`, `outstanding_amount`, `is_installment_payment`, `total_installments`, `paid_installments`, `installment_amount`, `next_payment_due`, `allow_partial_payments`, `minimum_payment_amount`, `notes`, `shipping_address`, `billing_address`, `order_date`, `confirmed_at`, `shipped_at`, `delivered_at`, `cancelled_at`, `created_by`, `processed_by`, `shipped_by`, `tracking_number`, `carrier_name`, `metadata`, `payment_schedule`, `payment_history`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'ORD-20251120-FB8120', 1, 1, 'counter', 'confirmed', 'paid', NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 1050.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-20 14:24:05', '2025-11-20 08:24:05', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 08:24:03', '2025-11-20 08:24:05', NULL),
(2, 'ORD-20251120-AD6A06', 2, 2, 'social_commerce', 'confirmed', 'paid', NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 1050.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'Social Commerce. ID: Oishee. Domestic delivery.', NULL, NULL, '2025-11-20 14:41:33', '2025-11-20 08:41:33', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 08:41:31', '2025-11-20 08:41:33', NULL),
(3, 'ORD-20251120-A6218C', 3, 1, 'counter', 'confirmed', 'paid', NULL, 1000.00, 0.00, 0.00, 0.00, 1000.00, 1050.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%', NULL, NULL, '2025-11-20 14:47:26', '2025-11-20 08:47:26', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 08:47:24', '2025-11-20 08:47:26', NULL),
(4, 'ORD-20251120-C5DA66', 4, 1, 'counter', 'confirmed', 'paid', NULL, 300.00, 0.00, 0.00, 0.00, 300.00, 315.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-20 14:55:38', '2025-11-20 08:55:38', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 08:55:37', '2025-11-20 08:55:38', NULL),
(5, 'ORD-20251120-28B0EB', 5, 1, 'counter', 'confirmed', 'paid', NULL, 3000.00, 0.00, 0.00, 0.00, 3000.00, 3150.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-20 15:23:36', '2025-11-20 09:23:36', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:36', NULL),
(7, 'ORD-20251120-BA38E6', 3, 2, 'counter', 'confirmed', 'paid', NULL, 1300.00, 0.00, 0.00, 0.00, 1300.00, 1365.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%', NULL, NULL, '2025-11-20 15:48:50', '2025-11-20 09:48:50', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50', NULL),
(8, 'ORD-20251120-4AE399', 3, 2, 'counter', 'confirmed', 'pending', NULL, 1300.00, 0.00, 0.00, 0.00, 1300.00, 0.00, 1300.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'Exchange from order #ORD-20251120-BA38E6 | Return: #RET-20251120-0003', NULL, NULL, '2025-11-20 15:50:54', '2025-11-20 09:50:54', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 09:50:53', '2025-11-20 09:50:54', NULL),
(9, 'ORD-20251120-77C380', 6, 1, 'counter', 'confirmed', 'paid', NULL, 3000.00, 0.00, 0.00, 0.00, 3000.00, 3150.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-20 15:58:43', '2025-11-20 09:58:43', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:43', NULL),
(10, 'ORD-20251120-961538', 6, 1, 'counter', 'confirmed', 'pending', NULL, 5000.00, 0.00, 0.00, 0.00, 5000.00, 0.00, 5000.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'Exchange from order #ORD-20251120-77C380 | Return: #RET-20251120-0004', NULL, NULL, '2025-11-20 16:00:54', '2025-11-20 10:00:54', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 10:00:53', '2025-11-20 10:00:54', NULL),
(11, 'ORD-20251122-3812E5', 6, 1, 'counter', 'confirmed', 'paid', NULL, 3000.00, 0.00, 0.00, 0.00, 3000.00, 3150.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-22 06:17:13', '2025-11-22 00:17:13', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22 00:17:11', '2025-11-22 00:17:13', NULL),
(12, 'ORD-20251122-88DC6D', 7, 1, 'counter', 'confirmed', 'paid', NULL, 3000.00, 0.00, 0.00, 0.00, 3000.00, 3150.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-22 07:18:53', '2025-11-22 01:18:53', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22 01:18:51', '2025-11-22 01:18:53', NULL),
(13, 'ORD-20251122-B66072', 8, 1, 'counter', 'confirmed', 'paid', NULL, 3000.00, 0.00, 0.00, 0.00, 3000.00, 3150.00, 0.00, 0, NULL, 0, NULL, NULL, 1, NULL, 'VAT: 5%, Address: International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', NULL, NULL, '2025-11-22 14:21:39', '2025-11-22 08:21:39', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22 08:21:38', '2025-11-22 08:21:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_barcode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `cogs` decimal(10,2) DEFAULT NULL COMMENT 'Cost of goods sold for this item at time of sale',
  `product_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`product_options`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_batch_id`, `product_barcode_id`, `product_name`, `product_sku`, `quantity`, `unit_price`, `discount_amount`, `tax_amount`, `total_amount`, `cogs`, `product_options`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 6, 'Shotoronji - Red', 'p02', 1, 1000.00, 0.00, 0.00, 1000.00, NULL, NULL, NULL, '2025-11-20 08:24:03', '2025-11-20 08:24:03'),
(2, 2, 3, 2, NULL, 'Shotoronji - Red', 'p02', 1, 1000.00, 0.00, 0.00, 1000.00, NULL, NULL, NULL, '2025-11-20 08:41:31', '2025-11-20 08:41:31'),
(3, 3, 3, 1, 10, 'Shotoronji - Red', 'p02', 1, 1000.00, 0.00, 0.00, 1000.00, NULL, NULL, NULL, '2025-11-20 08:47:24', '2025-11-20 08:47:24'),
(4, 4, 3, 1, NULL, 'Shotoronji - Red', 'p02', 1, 300.00, 0.00, 0.00, 300.00, NULL, NULL, NULL, '2025-11-20 08:55:37', '2025-11-20 08:55:37'),
(5, 5, 1, 3, 11, '60 count - White', 'p01', 1, 3000.00, 0.00, 0.00, 3000.00, NULL, NULL, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:35'),
(6, 7, 2, 4, 21, 'Shotoronji - Black', 'p02', 1, 1300.00, 0.00, 0.00, 1300.00, NULL, NULL, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50'),
(7, 8, 2, 4, 26, 'Shotoronji - Black', 'p02', 1, 1300.00, 0.00, 0.00, 1300.00, NULL, NULL, NULL, '2025-11-20 09:50:53', '2025-11-20 09:50:53'),
(8, 9, 1, 3, 18, '60 count - White', 'p01', 1, 3000.00, 0.00, 0.00, 3000.00, NULL, NULL, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:42'),
(9, 10, 3, 5, 37, 'Shotoronji - Red', 'p02', 1, 5000.00, 0.00, 0.00, 5000.00, NULL, NULL, NULL, '2025-11-20 10:00:53', '2025-11-20 10:00:53'),
(10, 11, 1, 3, 12, '60 count - White', 'p01', 1, 3000.00, 0.00, 0.00, 3000.00, 2000.00, NULL, NULL, '2025-11-22 00:17:11', '2025-11-22 00:17:11'),
(11, 12, 1, 3, 17, '60 count - White', 'p01', 1, 3000.00, 0.00, 0.00, 3000.00, 2000.00, NULL, NULL, '2025-11-22 01:18:51', '2025-11-22 01:18:51'),
(12, 13, 1, 3, 19, '60 count - White', 'p01', 1, 3000.00, 0.00, 0.00, 3000.00, 2000.00, NULL, NULL, '2025-11-22 08:21:38', '2025-11-22 08:21:38');

-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `is_partial_payment` tinyint(1) NOT NULL DEFAULT 0,
  `installment_number` int(11) DEFAULT NULL,
  `payment_type` enum('full','installment','partial','final','advance') NOT NULL DEFAULT 'full',
  `payment_due_date` date DEFAULT NULL,
  `payment_received_date` date DEFAULT NULL,
  `order_balance_before` decimal(10,2) DEFAULT NULL,
  `order_balance_after` decimal(10,2) DEFAULT NULL,
  `expected_installment_amount` decimal(10,2) DEFAULT NULL,
  `installment_notes` text DEFAULT NULL,
  `is_late_payment` tinyint(1) NOT NULL DEFAULT 0,
  `days_late` int(11) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refund_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_payments`
--

INSERT INTO `order_payments` (`id`, `payment_number`, `order_id`, `payment_method_id`, `customer_id`, `store_id`, `processed_by`, `amount`, `fee_amount`, `net_amount`, `is_partial_payment`, `installment_number`, `payment_type`, `payment_due_date`, `payment_received_date`, `order_balance_before`, `order_balance_after`, `expected_installment_amount`, `installment_notes`, `is_late_payment`, `days_late`, `status`, `transaction_reference`, `external_reference`, `processed_at`, `completed_at`, `failed_at`, `payment_data`, `metadata`, `notes`, `failure_reason`, `status_history`, `refunded_amount`, `refund_history`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PAY-20251120-5AA5AD8B', 1, NULL, 1, 1, 1, 1050.00, 2.50, 1047.50, 0, NULL, 'full', NULL, NULL, 1000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 08:24:04', '2025-11-20 08:24:04', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:24:04.670338Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:24:04.674607Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 08:24:04', '2025-11-20 08:24:04', NULL),
(2, 'PAY-20251120-5C3AF395', 2, 1, 2, 2, 1, 1050.00, 0.00, 1050.00, 0, NULL, 'full', NULL, NULL, 1000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 08:41:32', '2025-11-20 08:41:32', NULL, '[]', NULL, 'Social Commerce payment via Cash', NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:41:32.300866Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:41:32.302780Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 08:41:32', '2025-11-20 08:41:32', NULL),
(3, 'PAY-20251120-C4DB24DB', 3, NULL, 3, 1, 1, 1050.00, 2.50, 1047.50, 0, NULL, 'full', NULL, NULL, 1000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 08:47:25', '2025-11-20 08:47:25', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:47:25.459294Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:47:25.463796Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 08:47:25', '2025-11-20 08:47:25', NULL),
(4, 'PAY-20251120-A3CBF2BB', 4, NULL, 4, 1, 1, 315.00, 2.15, 312.85, 0, NULL, 'full', NULL, NULL, 300.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 08:55:38', '2025-11-20 08:55:38', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:55:38.317493Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:55:38.321675Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 08:55:38', '2025-11-20 08:55:38', NULL),
(5, 'PAY-20251120-9D6ED87A', 5, NULL, 5, 1, 1, 3150.00, 3.50, 3146.50, 0, NULL, 'full', NULL, NULL, 3000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:35', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:23:35.680465Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:23:35.681717Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:35', NULL),
(6, 'PAY-20251120-99DFC324', 7, NULL, 3, 2, 1, 1365.00, 2.65, 1362.35, 0, NULL, 'full', NULL, NULL, 1300.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:48:50.564440Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:48:50.567242Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50', NULL),
(7, 'PAY-20251120-93696F52', 8, 1, 3, 2, 1, 1300.00, 0.00, 1300.00, 0, NULL, 'full', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, '[]', NULL, NULL, NULL, NULL, 0.00, NULL, '2025-11-20 09:50:53', '2025-11-20 09:50:53', NULL),
(8, 'PAY-20251120-876E65AB', 9, NULL, 6, 1, 1, 3150.00, 3.50, 3146.50, 0, NULL, 'full', NULL, NULL, 3000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:42', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:58:42.612741Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:58:42.614845Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:42', NULL),
(9, 'PAY-20251120-A8EF7ECA', 10, 1, 6, 1, 1, 5000.00, 0.00, 5000.00, 0, NULL, 'full', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, '[]', NULL, NULL, NULL, NULL, 0.00, NULL, '2025-11-20 10:00:53', '2025-11-20 10:00:53', NULL),
(10, 'PAY-20251122-5AA9538D', 11, 1, 6, 1, 1, 3150.00, 0.00, 3150.00, 0, NULL, 'full', NULL, NULL, 3000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-22 00:17:12', '2025-11-22 00:17:12', NULL, '[]', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-22T06:17:12.218091Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-22T06:17:12.221205Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-22 00:17:12', '2025-11-22 00:17:12', NULL),
(11, 'PAY-20251122-0238AAB1', 12, NULL, 7, 1, 1, 3150.00, 3.50, 3146.50, 0, NULL, 'full', NULL, NULL, 3000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-22 01:18:52', '2025-11-22 01:18:52', NULL, NULL, NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-22T07:18:52.825844Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-22T07:18:52.836137Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-22 01:18:52', '2025-11-22 01:18:52', NULL),
(12, 'PAY-20251122-01FFB393', 13, 1, 8, 1, 1, 3150.00, 0.00, 3150.00, 0, NULL, 'full', NULL, NULL, 3000.00, 0.00, NULL, NULL, 0, NULL, 'completed', NULL, NULL, '2025-11-22 08:21:39', '2025-11-22 08:21:39', NULL, '[]', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-22T14:21:39.410652Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-22T14:21:39.412309Z\",\"changed_by\":null,\"notes\":null}]', 0.00, NULL, '2025-11-22 08:21:39', '2025-11-22 08:21:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('cash','card','bank_transfer','online_banking','mobile_banking','digital_wallet','other') NOT NULL,
  `allowed_customer_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`allowed_customer_types`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `requires_reference` tinyint(1) NOT NULL DEFAULT 0,
  `supports_partial` tinyint(1) NOT NULL DEFAULT 1,
  `min_amount` decimal(10,2) DEFAULT NULL,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `processor_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`processor_config`)),
  `icon` varchar(255) DEFAULT NULL,
  `fixed_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `percentage_fee` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `code`, `name`, `description`, `type`, `allowed_customer_types`, `is_active`, `requires_reference`, `supports_partial`, `min_amount`, `max_amount`, `processor`, `processor_config`, `icon`, `fixed_fee`, `percentage_fee`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'cash', 'Cash', 'Cash payment at counter', 'cash', '[\"counter\",\"social_commerce\",\"ecommerce\"]', 1, 0, 1, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(2, 'card', 'Card Payment', 'Credit/Debit card payment', 'card', '[\"counter\",\"social_commerce\",\"ecommerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.00, 1.50, 2, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(3, 'online_pay', 'Online Pay', 'Online payment gateway', 'online_banking', '[\"counter\",\"social_commerce\",\"ecommerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 5.00, 0.50, 3, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(4, 'bank_transfer', 'Bank Transfer', 'Direct bank transfer', 'bank_transfer', '[\"ecommerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 4, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(5, 'online_banking', 'Online Banking', 'Online banking payment', 'online_banking', '[\"social_commerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 5.00, 0.00, 5, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(6, 'mobile_banking', 'Mobile Banking', 'Mobile banking payment (bKash, Nagad, etc.)', 'mobile_banking', '[\"counter\",\"social_commerce\",\"ecommerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 2.00, 1.00, 6, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(7, 'digital_wallet', 'Digital Wallet', 'Digital wallet payment', 'digital_wallet', '[\"counter\",\"social_commerce\",\"ecommerce\"]', 1, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.00, 0.00, 7, '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `payment_splits`
--

CREATE TABLE `payment_splits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_payment_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `split_sequence` int(11) NOT NULL DEFAULT 1,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refund_history`)),
  `notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_splits`
--

INSERT INTO `payment_splits` (`id`, `order_payment_id`, `payment_method_id`, `store_id`, `amount`, `fee_amount`, `net_amount`, `split_sequence`, `transaction_reference`, `external_reference`, `payment_data`, `status`, `processed_at`, `completed_at`, `failed_at`, `refunded_amount`, `refund_history`, `notes`, `failure_reason`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1000.00, 0.00, 1000.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:24:04', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:24:04', '2025-11-20 08:24:04'),
(2, 1, 6, 1, 50.00, 2.50, 47.50, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:24:04', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:24:04', '2025-11-20 08:24:04'),
(3, 3, 1, 1, 1000.00, 0.00, 1000.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:47:25', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:47:25', '2025-11-20 08:47:25'),
(4, 3, 6, 1, 50.00, 2.50, 47.50, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:47:25', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:47:25', '2025-11-20 08:47:25'),
(5, 4, 1, 1, 300.00, 0.00, 300.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:55:38', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:55:38', '2025-11-20 08:55:38'),
(6, 4, 6, 1, 15.00, 2.15, 12.85, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 08:55:38', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 08:55:38', '2025-11-20 08:55:38'),
(7, 5, 1, 1, 3000.00, 0.00, 3000.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:23:35', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:35'),
(8, 5, 6, 1, 150.00, 3.50, 146.50, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:23:35', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:23:35', '2025-11-20 09:23:35'),
(9, 6, 1, 2, 1300.00, 0.00, 1300.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:48:50', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50'),
(10, 6, 6, 2, 65.00, 2.65, 62.35, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:48:50', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:48:50', '2025-11-20 09:48:50'),
(11, 8, 1, 1, 3000.00, 0.00, 3000.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:58:42', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:42'),
(12, 8, 6, 1, 150.00, 3.50, 146.50, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-20 09:58:42', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-20 09:58:42', '2025-11-20 09:58:42'),
(13, 11, 1, 1, 3000.00, 0.00, 3000.00, 1, NULL, NULL, '[]', 'completed', NULL, '2025-11-22 01:18:52', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-22 01:18:52', '2025-11-22 01:18:52'),
(14, 11, 6, 1, 150.00, 3.50, 146.50, 2, NULL, NULL, '[]', 'completed', NULL, '2025-11-22 01:18:52', NULL, 0.00, NULL, NULL, NULL, NULL, '2025-11-22 01:18:52', '2025-11-22 01:18:52');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL COMMENT 'Module name like products, orders, users',
  `guard_name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `title`, `slug`, `description`, `module`, `guard_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'View Dashboard', 'dashboard.view', 'Access to main dashboard', 'dashboard', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(2, 'View Analytics', 'dashboard.analytics', 'Access to dashboard analytics and charts', 'dashboard', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(3, 'View Employees', 'employees.view', 'View employee list and details', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(4, 'Create Employees', 'employees.create', 'Create new employees', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(5, 'Edit Employees', 'employees.edit', 'Edit employee information', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(6, 'Delete Employees', 'employees.delete', 'Delete employees', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(7, 'Manage Employee Roles', 'employees.manage_roles', 'Assign and remove employee roles', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(8, 'View Employee Sessions', 'employees.view_sessions', 'View employee login sessions', 'employees', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(9, 'View Roles', 'roles.view', 'View roles and their permissions', 'roles', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(10, 'Create Roles', 'roles.create', 'Create new roles', 'roles', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(11, 'Edit Roles', 'roles.edit', 'Edit role permissions and details', 'roles', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(12, 'Delete Roles', 'roles.delete', 'Delete roles', 'roles', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(13, 'Manage Permissions', 'permissions.manage', 'Create, edit, and delete permissions', 'permissions', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(14, 'View Products', 'products.view', 'View product catalog', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(15, 'Create Products', 'products.create', 'Create new products', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(16, 'Edit Products', 'products.edit', 'Edit product information', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(17, 'Delete Products', 'products.delete', 'Delete products', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(18, 'Import Products', 'products.import', 'Import products from CSV/Excel', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(19, 'Export Products', 'products.export', 'Export products to CSV/Excel', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(20, 'Manage Product Images', 'products.manage_images', 'Upload and manage product images', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(21, 'Manage Product Barcodes', 'products.manage_barcodes', 'Generate and manage product barcodes', 'products', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(22, 'View Product Batches', 'product_batches.view', 'View product batch information', 'product_batches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(23, 'Create Product Batches', 'product_batches.create', 'Create new product batches', 'product_batches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(24, 'Edit Product Batches', 'product_batches.edit', 'Edit product batch information', 'product_batches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(25, 'Delete Product Batches', 'product_batches.delete', 'Delete product batches', 'product_batches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(26, 'View Categories', 'categories.view', 'View product categories', 'categories', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(27, 'Create Categories', 'categories.create', 'Create new product categories', 'categories', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(28, 'Edit Categories', 'categories.edit', 'Edit product categories', 'categories', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(29, 'Delete Categories', 'categories.delete', 'Delete product categories', 'categories', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(30, 'View Vendors', 'vendors.view', 'View vendor information', 'vendors', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(31, 'Create Vendors', 'vendors.create', 'Create new vendors', 'vendors', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(32, 'Edit Vendors', 'vendors.edit', 'Edit vendor information', 'vendors', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(33, 'Delete Vendors', 'vendors.delete', 'Delete vendors', 'vendors', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(34, 'View Inventory', 'inventory.view', 'View inventory levels and details', 'inventory', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(35, 'Adjust Inventory', 'inventory.adjust', 'Manually adjust inventory levels', 'inventory', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(36, 'View Inventory Movements', 'inventory.view_movements', 'View inventory movement history', 'inventory', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(37, 'View Product Dispatches', 'product_dispatches.view', 'View product dispatch records', 'product_dispatches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(38, 'Create Product Dispatches', 'product_dispatches.create', 'Create new product dispatches', 'product_dispatches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(39, 'Edit Product Dispatches', 'product_dispatches.edit', 'Edit product dispatch information', 'product_dispatches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(40, 'Delete Product Dispatches', 'product_dispatches.delete', 'Delete product dispatches', 'product_dispatches', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(41, 'View Inventory Rebalancing', 'inventory_rebalancing.view', 'View inventory rebalancing records', 'inventory_rebalancing', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(42, 'Create Inventory Rebalancing', 'inventory_rebalancing.create', 'Create inventory rebalancing entries', 'inventory_rebalancing', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(43, 'Approve Inventory Rebalancing', 'inventory_rebalancing.approve', 'Approve inventory rebalancing requests', 'inventory_rebalancing', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(44, 'View Customers', 'customers.view', 'View customer list and details', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(45, 'Create Customers', 'customers.create', 'Create new customers', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(46, 'Edit Customers', 'customers.edit', 'Edit customer information', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(47, 'Delete Customers', 'customers.delete', 'Delete customers', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(48, 'Import Customers', 'customers.import', 'Import customers from CSV/Excel', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(49, 'Export Customers', 'customers.export', 'Export customers to CSV/Excel', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(50, 'View Customer Purchase History', 'customers.view_purchase_history', 'View customer purchase history and analytics', 'customers', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(51, 'View Orders', 'orders.view', 'View order list and details', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(52, 'Create Orders', 'orders.create', 'Create new orders', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(53, 'Edit Orders', 'orders.edit', 'Edit order information', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(54, 'Delete Orders', 'orders.delete', 'Delete orders', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(55, 'Process Orders', 'orders.process', 'Process and fulfill orders', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(56, 'Cancel Orders', 'orders.cancel', 'Cancel orders', 'orders', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(57, 'View Order Payments', 'order_payments.view', 'View order payment records', 'order_payments', 'web', 1, '2025-11-20 07:33:14', '2025-11-20 07:33:14'),
(58, 'Process Order Payments', 'order_payments.process', 'Process and record order payments', 'order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(59, 'Refund Order Payments', 'order_payments.refund', 'Process refunds for order payments', 'order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(60, 'Manage Installment Payments', 'order_payments.manage_installments', 'Setup and manage installment payment plans', 'order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(61, 'View Shipments', 'shipments.view', 'View shipment records', 'shipments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(62, 'Create Shipments', 'shipments.create', 'Create new shipments', 'shipments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(63, 'Edit Shipments', 'shipments.edit', 'Edit shipment information', 'shipments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(64, 'Delete Shipments', 'shipments.delete', 'Delete shipments', 'shipments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(65, 'Update Shipment Status', 'shipments.update_status', 'Update shipment delivery status', 'shipments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(66, 'View Product Returns', 'product_returns.view', 'View product return requests', 'product_returns', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(67, 'Process Product Returns', 'product_returns.process', 'Process and approve product returns', 'product_returns', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(68, 'View Refunds', 'refunds.view', 'View refund records', 'refunds', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(69, 'Process Refunds', 'refunds.process', 'Process and approve refunds', 'refunds', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(70, 'View Services', 'services.view', 'View service catalog', 'services', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(71, 'Create Services', 'services.create', 'Create new services', 'services', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(72, 'Edit Services', 'services.edit', 'Edit service information', 'services', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(73, 'Delete Services', 'services.delete', 'Delete services', 'services', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(74, 'View Service Orders', 'service_orders.view', 'View service order list and details', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(75, 'Create Service Orders', 'service_orders.create', 'Create new service orders', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(76, 'Edit Service Orders', 'service_orders.edit', 'Edit service order information', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(77, 'Delete Service Orders', 'service_orders.delete', 'Delete service orders', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(78, 'Process Service Orders', 'service_orders.process', 'Process and fulfill service orders', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(79, 'Cancel Service Orders', 'service_orders.cancel', 'Cancel service orders', 'service_orders', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(80, 'View Service Order Payments', 'service_order_payments.view', 'View service order payment records', 'service_order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(81, 'Process Service Order Payments', 'service_order_payments.process', 'Process and record service order payments', 'service_order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(82, 'Refund Service Order Payments', 'service_order_payments.refund', 'Process refunds for service order payments', 'service_order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(83, 'Manage Service Installment Payments', 'service_order_payments.manage_installments', 'Setup and manage service installment payment plans', 'service_order_payments', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(84, 'View Payment Methods', 'payment_methods.view', 'View available payment methods', 'payment_methods', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(85, 'Create Payment Methods', 'payment_methods.create', 'Create new payment methods', 'payment_methods', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(86, 'Edit Payment Methods', 'payment_methods.edit', 'Edit payment method settings', 'payment_methods', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(87, 'Delete Payment Methods', 'payment_methods.delete', 'Delete payment methods', 'payment_methods', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(88, 'View Expenses', 'expenses.view', 'View expense records', 'expenses', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(89, 'Create Expenses', 'expenses.create', 'Create new expense records', 'expenses', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(90, 'Edit Expenses', 'expenses.edit', 'Edit expense information', 'expenses', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(91, 'Delete Expenses', 'expenses.delete', 'Delete expense records', 'expenses', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(92, 'Approve Expenses', 'expenses.approve', 'Approve expense requests', 'expenses', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(93, 'View Expense Categories', 'expense_categories.view', 'View expense categories', 'expense_categories', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(94, 'Create Expense Categories', 'expense_categories.create', 'Create new expense categories', 'expense_categories', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(95, 'Edit Expense Categories', 'expense_categories.edit', 'Edit expense categories', 'expense_categories', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(96, 'Delete Expense Categories', 'expense_categories.delete', 'Delete expense categories', 'expense_categories', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(97, 'View Transactions', 'transactions.view', 'View financial transactions', 'transactions', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(98, 'View Accounts', 'accounts.view', 'View account balances and details', 'accounts', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(99, 'Manage Accounts', 'accounts.manage', 'Create and manage financial accounts', 'accounts', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(100, 'View Financial Reports', 'financial_reports.view', 'Access financial reports and statements', 'financial_reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(101, 'View Stores', 'stores.view', 'View store information', 'stores', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(102, 'Create Stores', 'stores.create', 'Create new stores', 'stores', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(103, 'Edit Stores', 'stores.edit', 'Edit store information', 'stores', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(104, 'Delete Stores', 'stores.delete', 'Delete stores', 'stores', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(105, 'Manage Store Settings', 'stores.manage_settings', 'Configure store-specific settings', 'stores', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(106, 'View System Settings', 'system.settings.view', 'View system configuration settings', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(107, 'Edit System Settings', 'system.settings.edit', 'Modify system configuration settings', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(108, 'View Dynamic Fields', 'fields.view', 'View dynamic field configurations', 'fields', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(109, 'Create Dynamic Fields', 'fields.create', 'Create new dynamic fields', 'fields', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(110, 'Edit Dynamic Fields', 'fields.edit', 'Edit dynamic field configurations', 'fields', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(111, 'Delete Dynamic Fields', 'fields.delete', 'Delete dynamic fields', 'fields', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(112, 'View Notes', 'notes.view', 'View system notes and comments', 'notes', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(113, 'Create Notes', 'notes.create', 'Create new notes and comments', 'notes', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(114, 'Edit Notes', 'notes.edit', 'Edit existing notes', 'notes', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(115, 'Delete Notes', 'notes.delete', 'Delete notes and comments', 'notes', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(116, 'Run Database Migrations', 'system.migrations.run', 'Execute database migrations', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(117, 'Run Database Seeders', 'system.seeders.run', 'Execute database seeders', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(118, 'Clear System Cache', 'system.cache.clear', 'Clear application cache', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(119, 'View System Logs', 'system.logs.view', 'Access system log files', 'system', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(120, 'View Sales Reports', 'reports.sales.view', 'Access sales and revenue reports', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(121, 'View Inventory Reports', 'reports.inventory.view', 'Access inventory and stock reports', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(122, 'View Customer Reports', 'reports.customers.view', 'Access customer analytics and reports', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(123, 'View Financial Reports', 'reports.financial.view', 'Access financial statements and reports', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(124, 'View Performance Reports', 'reports.performance.view', 'Access system and user performance reports', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(125, 'Export Reports', 'reports.export', 'Export reports to various formats', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(126, 'Schedule Reports', 'reports.schedule', 'Schedule automated report generation', 'reports', 'web', 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `vendor_id`, `sku`, `name`, `description`, `is_archived`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'p01', '60 count - White', 'jamdanis', 0, NULL, '2025-11-20 07:50:01', '2025-11-20 07:50:01'),
(2, 2, 1, 'p02', 'Shotoronji - Black', 'Shotoronjis', 0, NULL, '2025-11-20 08:00:00', '2025-11-20 08:00:00'),
(3, 2, 1, 'p02', 'Shotoronji - Red', 'Shotoronjis', 0, NULL, '2025-11-20 08:00:02', '2025-11-20 08:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_barcodes`
--

CREATE TABLE `product_barcodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `current_store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `current_status` enum('available','in_warehouse','in_shop','on_display','in_transit','in_shipment','sold','with_customer','in_return','defective','repair','vendor_return','disposed') DEFAULT 'available' COMMENT 'Current state of this physical unit',
  `location_updated_at` timestamp NULL DEFAULT NULL COMMENT 'When location/status was last updated',
  `location_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional location info like shelf, bin, display section' CHECK (json_valid(`location_metadata`)),
  `barcode` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'CODE128' COMMENT 'Barcode type: CODE128, EAN13, etc.',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_defective` tinyint(1) NOT NULL DEFAULT 0,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_barcodes`
--

INSERT INTO `product_barcodes` (`id`, `product_id`, `batch_id`, `current_store_id`, `current_status`, `location_updated_at`, `location_metadata`, `barcode`, `type`, `is_primary`, `is_active`, `is_defective`, `generated_at`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 2, 'available', '2025-11-20 08:19:38', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-7247F1\",\"source_store_id\":1,\"source_batch_id\":1,\"destination_batch_id\":2,\"transfer_date\":\"2025-11-20T14:19:38.597777Z\",\"delivered_at\":\"2025-11-20T14:19:38.597885Z\"}', '823758388022', 'CODE128', 1, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:19:38'),
(2, 3, 2, 2, 'available', '2025-11-20 08:19:38', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-7247F1\",\"source_store_id\":1,\"source_batch_id\":1,\"destination_batch_id\":2,\"transfer_date\":\"2025-11-20T14:19:38.605811Z\",\"delivered_at\":\"2025-11-20T14:19:38.606006Z\"}', '187697284366', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:19:38'),
(3, 3, 2, 2, 'available', '2025-11-20 08:19:38', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-7247F1\",\"source_store_id\":1,\"source_batch_id\":1,\"destination_batch_id\":2,\"transfer_date\":\"2025-11-20T14:19:38.610440Z\",\"delivered_at\":\"2025-11-20T14:19:38.610597Z\"}', '188425871018', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:19:38'),
(4, 3, 2, 2, 'available', '2025-11-20 08:19:38', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-7247F1\",\"source_store_id\":1,\"source_batch_id\":1,\"destination_batch_id\":2,\"transfer_date\":\"2025-11-20T14:19:38.615502Z\",\"delivered_at\":\"2025-11-20T14:19:38.615601Z\"}', '199652485702', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:19:38'),
(5, 3, 2, 2, 'available', '2025-11-20 08:19:38', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-7247F1\",\"source_store_id\":1,\"source_batch_id\":1,\"destination_batch_id\":2,\"transfer_date\":\"2025-11-20T14:19:38.619159Z\",\"delivered_at\":\"2025-11-20T14:19:38.619224Z\"}', '065906326214', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:19:38'),
(6, 3, 1, 1, 'sold', '2025-11-20 08:24:05', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-FB8120\",\"sale_date\":\"2025-11-20T14:24:05.383362Z\",\"sold_by\":1}', '714923669048', 'CODE128', 0, 0, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:24:05'),
(7, 3, 1, 1, 'in_warehouse', '2025-11-20 08:00:58', NULL, '098814711963', 'CODE128', 0, 0, 1, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:50:07'),
(8, 3, 1, 1, 'in_warehouse', '2025-11-20 08:00:58', NULL, '632247567221', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:00:58'),
(9, 3, 1, 1, 'in_warehouse', '2025-11-20 08:00:58', NULL, '829631842678', 'CODE128', 0, 1, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:00:58'),
(10, 3, 1, 1, 'sold', '2025-11-20 08:47:26', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-A6218C\",\"sale_date\":\"2025-11-20T14:47:26.219998Z\",\"sold_by\":1}', '397530449450', 'CODE128', 0, 0, 0, '2025-11-20 08:00:58', '2025-11-20 08:00:58', '2025-11-20 08:47:26'),
(11, 1, 3, 1, 'sold', '2025-11-20 09:23:36', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-28B0EB\",\"sale_date\":\"2025-11-20T15:23:36.075532Z\",\"sold_by\":1}', '695545734101', 'CODE128', 1, 0, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:23:36'),
(12, 1, 3, 1, 'sold', '2025-11-22 00:17:13', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251122-3812E5\",\"sale_date\":\"2025-11-22T06:17:13.113153Z\",\"sold_by\":1}', '405546631090', 'CODE128', 0, 0, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-22 00:17:13'),
(13, 1, 3, 1, 'in_warehouse', '2025-11-20 09:19:25', NULL, '636823734848', 'CODE128', 0, 1, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:19:25'),
(14, 1, 3, 1, 'in_warehouse', '2025-11-20 09:19:25', NULL, '775561774204', 'CODE128', 0, 1, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:19:25'),
(15, 1, 3, 1, 'in_warehouse', '2025-11-20 09:19:25', NULL, '092434737082', 'CODE128', 0, 1, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:19:25'),
(16, 1, 3, 1, 'in_warehouse', '2025-11-20 09:19:25', NULL, '346964646795', 'CODE128', 0, 1, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:19:25'),
(17, 1, 3, 1, 'sold', '2025-11-22 01:18:53', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251122-88DC6D\",\"sale_date\":\"2025-11-22T07:18:53.642920Z\",\"sold_by\":1}', '301853774510', 'CODE128', 0, 0, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-22 01:18:53'),
(18, 1, 3, 1, 'sold', '2025-11-20 09:58:43', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-77C380\",\"sale_date\":\"2025-11-20T15:58:43.108260Z\",\"sold_by\":1}', '022383783949', 'CODE128', 0, 0, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:58:43'),
(19, 1, 3, 1, 'sold', '2025-11-22 08:21:39', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251122-B66072\",\"sale_date\":\"2025-11-22T14:21:39.824838Z\",\"sold_by\":1}', '247505873807', 'CODE128', 0, 0, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-22 08:21:39'),
(20, 1, 3, 1, 'in_warehouse', '2025-11-20 09:19:25', NULL, '472029851342', 'CODE128', 0, 1, 0, '2025-11-20 09:19:25', '2025-11-20 09:19:25', '2025-11-20 09:19:25'),
(21, 2, 4, 2, 'sold', '2025-11-20 09:48:50', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-BA38E6\",\"sale_date\":\"2025-11-20T15:48:50.800972Z\",\"sold_by\":1}', '442691993174', 'CODE128', 1, 0, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:48:50'),
(22, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '965679062801', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(23, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '018417686315', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(24, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '824456084890', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(25, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '351048385622', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(26, 2, 4, 2, 'sold', '2025-11-20 09:50:54', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-4AE399\",\"sale_date\":\"2025-11-20T15:50:54.146425Z\",\"sold_by\":1}', '386169992523', 'CODE128', 0, 0, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:50:54'),
(27, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '975858176544', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(28, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '549066646022', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(29, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '074260249693', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(30, 2, 4, 2, 'in_shop', '2025-11-20 09:25:56', NULL, '359769292405', 'CODE128', 0, 1, 0, '2025-11-20 09:25:56', '2025-11-20 09:25:56', '2025-11-20 09:25:56'),
(31, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '097838256515', 'CODE128', 1, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(32, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '165608026214', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(33, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '553144508613', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(34, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '620184378128', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(35, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '342803407938', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(36, 3, 5, 1, 'in_warehouse', '2025-11-20 09:44:51', NULL, '766313918395', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 09:44:51'),
(37, 3, 5, 1, 'sold', '2025-11-20 10:00:54', '{\"sold_via\":\"order\",\"order_number\":\"ORD-20251120-961538\",\"sale_date\":\"2025-11-20T16:00:54.152852Z\",\"sold_by\":1}', '537408698789', 'CODE128', 0, 0, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 10:00:54'),
(38, 3, 6, 2, 'available', '2025-11-20 13:32:11', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-6E330D\",\"source_store_id\":1,\"source_batch_id\":5,\"destination_batch_id\":6,\"transfer_date\":\"2025-11-20T19:32:11.824651Z\",\"delivered_at\":\"2025-11-20T19:32:11.824837Z\"}', '586340806248', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 13:32:11'),
(39, 3, 6, 2, 'available', '2025-11-20 13:32:11', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-6E330D\",\"source_store_id\":1,\"source_batch_id\":5,\"destination_batch_id\":6,\"transfer_date\":\"2025-11-20T19:32:11.832015Z\",\"delivered_at\":\"2025-11-20T19:32:11.832193Z\"}', '392557891524', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 13:32:11'),
(40, 3, 6, 2, 'available', '2025-11-20 13:32:11', '{\"transferred_via\":\"dispatch\",\"dispatch_number\":\"DSP-20251120-6E330D\",\"source_store_id\":1,\"source_batch_id\":5,\"destination_batch_id\":6,\"transfer_date\":\"2025-11-20T19:32:11.808890Z\",\"delivered_at\":\"2025-11-20T19:32:11.809096Z\"}', '374826156045', 'CODE128', 0, 1, 0, '2025-11-20 09:44:51', '2025-11-20 09:44:51', '2025-11-20 13:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `sell_price` decimal(10,2) NOT NULL,
  `availability` tinyint(1) NOT NULL DEFAULT 1,
  `manufactured_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `barcode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `product_id`, `batch_number`, `quantity`, `cost_price`, `sell_price`, `availability`, `manufactured_date`, `expiry_date`, `store_id`, `barcode_id`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 3, 'BATCH-20251120-ABC0DC', 2, 700.00, 1000.00, 1, NULL, NULL, 1, 1, '[2025-11-20 14:24:05] Sold 1 unit (Barcode: 714923669048) via Order #ORD-20251120-FB8120\n[2025-11-20 14:47:26] Sold 1 unit (Barcode: 397530449450) via Order #ORD-20251120-A6218C\n[2025-11-20 14:55:38] Sold 1 unit(s) (No barcode tracking) via Order #ORD-20251120-C5DA66', 1, '2025-11-20 08:00:58', '2025-11-20 08:55:38'),
(2, 3, 'BATCH-20251120-ABC0DC-DST-DSP-20251120-7247F1', 4, 700.00, 1000.00, 1, NULL, NULL, 2, 1, 'Received via dispatch DSP-20251120-7247F1\n[2025-11-20 14:41:33] Sold 1 unit(s) (No barcode tracking) via Order #ORD-20251120-AD6A06', 1, '2025-11-20 08:19:38', '2025-11-20 08:41:33'),
(3, 1, 'BATCH-20251120-78A800', 7, 2000.00, 3000.00, 1, NULL, NULL, 1, 11, '[2025-11-20 15:23:36] Sold 1 unit (Barcode: 695545734101) via Order #ORD-20251120-28B0EB\n[2025-11-20 15:58:43] Sold 1 unit (Barcode: 022383783949) via Order #ORD-20251120-77C380\n[2025-11-22 06:17:13] Sold 1 unit (Barcode: 405546631090) via Order #ORD-20251122-3812E5\n[2025-11-22 07:18:53] Sold 1 unit (Barcode: 301853774510) via Order #ORD-20251122-88DC6D\n[2025-11-22 14:21:39] Sold 1 unit (Barcode: 247505873807) via Order #ORD-20251122-B66072', 1, '2025-11-20 09:19:25', '2025-11-22 08:21:39'),
(4, 2, 'BATCH-20251120-432170', 9, 1000.00, 1300.00, 1, NULL, NULL, 2, 21, '[2025-11-20 15:48:50] Sold 1 unit (Barcode: 442691993174) via Order #ORD-20251120-BA38E6\n[2025-11-20 15:50:54] Sold 1 unit (Barcode: 386169992523) via Order #ORD-20251120-4AE399', 1, '2025-11-20 09:25:56', '2025-11-20 09:50:54'),
(5, 3, 'BATCH-20251120-D91415', 6, 4000.00, 5000.00, 1, NULL, NULL, 1, 31, '[2025-11-20 16:00:54] Sold 1 unit (Barcode: 537408698789) via Order #ORD-20251120-961538', 1, '2025-11-20 09:44:51', '2025-11-20 13:32:11'),
(6, 3, 'BATCH-20251120-D91415-DST-DSP-20251120-6E330D', 3, 4000.00, 5000.00, 1, NULL, NULL, 2, 31, 'Received via dispatch DSP-20251120-6E330D', 1, '2025-11-20 13:32:11', '2025-11-20 13:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_dispatches`
--

CREATE TABLE `product_dispatches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_store_id` bigint(20) UNSIGNED NOT NULL,
  `destination_store_id` bigint(20) UNSIGNED NOT NULL,
  `dispatch_number` varchar(255) NOT NULL,
  `status` enum('pending','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `for_pathao_delivery` tinyint(1) NOT NULL DEFAULT 0,
  `dispatch_date` datetime NOT NULL,
  `expected_delivery_date` datetime DEFAULT NULL,
  `actual_delivery_date` datetime DEFAULT NULL,
  `carrier_name` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_delivery_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customer_delivery_info`)),
  `shipment_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_dispatches`
--

INSERT INTO `product_dispatches` (`id`, `source_store_id`, `destination_store_id`, `dispatch_number`, `status`, `for_pathao_delivery`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `carrier_name`, `tracking_number`, `total_cost`, `total_value`, `total_items`, `notes`, `metadata`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`, `customer_id`, `order_id`, `customer_delivery_info`, `shipment_id`) VALUES
(1, 1, 2, 'DSP-20251120-7247F1', 'delivered', 0, '2025-11-20 14:03:26', '2025-11-22 00:00:00', '2025-11-20 14:19:38', NULL, NULL, 3500.00, 5000.00, 1, NULL, NULL, 1, 1, '2025-11-20 08:03:33', '2025-11-20 08:03:26', '2025-11-20 08:19:38', NULL, NULL, NULL, NULL),
(2, 1, 2, 'DSP-20251120-6E330D', 'delivered', 0, '2025-11-20 19:25:24', '2025-11-23 00:00:00', '2025-11-20 19:32:11', NULL, NULL, 12000.00, 15000.00, 1, NULL, NULL, 1, 1, '2025-11-20 13:25:47', '2025-11-20 13:25:24', '2025-11-20 13:32:11', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_dispatch_items`
--

CREATE TABLE `product_dispatch_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_dispatch_id` bigint(20) UNSIGNED NOT NULL,
  `product_batch_id` bigint(20) UNSIGNED NOT NULL,
  `product_barcode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `status` enum('pending','dispatched','received','damaged','missing') NOT NULL DEFAULT 'pending',
  `received_quantity` int(11) DEFAULT NULL,
  `damaged_quantity` int(11) DEFAULT NULL,
  `missing_quantity` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_dispatch_items`
--

INSERT INTO `product_dispatch_items` (`id`, `product_dispatch_id`, `product_batch_id`, `product_barcode_id`, `quantity`, `unit_cost`, `unit_price`, `total_cost`, `total_value`, `status`, `received_quantity`, `damaged_quantity`, `missing_quantity`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, NULL, 5, 700.00, 1000.00, 3500.00, 5000.00, 'received', 5, 0, 0, NULL, '2025-11-20 08:03:27', '2025-11-20 08:19:38'),
(2, 2, 6, NULL, 3, 4000.00, 5000.00, 12000.00, 15000.00, 'received', 3, 0, 0, NULL, '2025-11-20 13:25:24', '2025-11-20 13:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_dispatch_item_barcodes`
--

CREATE TABLE `product_dispatch_item_barcodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_dispatch_item_id` bigint(20) UNSIGNED NOT NULL,
  `product_barcode_id` bigint(20) UNSIGNED NOT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scanned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_dispatch_item_barcodes`
--

INSERT INTO `product_dispatch_item_barcodes` (`id`, `product_dispatch_item_id`, `product_barcode_id`, `scanned_at`, `scanned_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-11-20 08:14:51', 1, '2025-11-20 08:14:51', '2025-11-20 08:14:51'),
(2, 1, 2, '2025-11-20 08:18:44', 1, '2025-11-20 08:18:44', '2025-11-20 08:18:44'),
(3, 1, 3, '2025-11-20 08:18:59', 1, '2025-11-20 08:18:59', '2025-11-20 08:18:59'),
(4, 1, 4, '2025-11-20 08:19:08', 1, '2025-11-20 08:19:08', '2025-11-20 08:19:08'),
(5, 1, 5, '2025-11-20 08:19:21', 1, '2025-11-20 08:19:21', '2025-11-20 08:19:21'),
(6, 2, 40, '2025-11-20 13:30:17', 1, '2025-11-20 13:30:17', '2025-11-20 13:30:17'),
(7, 2, 38, '2025-11-20 13:31:38', 1, '2025-11-20 13:31:38', '2025-11-20 13:31:38'),
(8, 2, 39, '2025-11-20 13:31:56', 1, '2025-11-20 13:31:56', '2025-11-20 13:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `product_fields`
--

CREATE TABLE `product_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL,
  `value` text DEFAULT NULL COMMENT 'The value for this field on this product',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_fields`
--

INSERT INTO `product_fields` (`id`, `product_id`, `field_id`, `value`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'White', '2025-11-20 07:50:01', '2025-11-20 07:50:01'),
(2, 2, 1, 'Black', '2025-11-20 08:00:00', '2025-11-20 08:00:00'),
(3, 3, 1, 'Red', '2025-11-20 08:00:02', '2025-11-20 08:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'products/1/1763646602_WeJ6FZCyjd.jpg', '60 count - White', 1, 0, 1, '2025-11-20 07:50:02', '2025-11-20 07:50:02'),
(2, 2, 'products/2/1763647201_JMgs6qhckC.jpg', 'Shotoronji - Black', 1, 0, 1, '2025-11-20 08:00:01', '2025-11-20 08:00:01'),
(3, 3, 'products/3/1763647203_dwSqr6hhqd.jpg', 'Shotoronji - Red', 1, 0, 1, '2025-11-20 08:00:03', '2025-11-20 08:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `product_movements`
--

CREATE TABLE `product_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_batch_id` bigint(20) UNSIGNED NOT NULL,
  `product_barcode_id` bigint(20) UNSIGNED NOT NULL,
  `from_store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_dispatch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `movement_type` enum('dispatch','transfer','return','adjustment','defective') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `movement_date` datetime NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL COMMENT 'Type: order, dispatch, return, shipment, adjustment',
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID of the referenced record',
  `status_before` enum('available','in_warehouse','in_shop','on_display','in_transit','in_shipment','sold','with_customer','in_return','defective','repair','vendor_return','disposed') DEFAULT NULL COMMENT 'Status before movement',
  `status_after` enum('available','in_warehouse','in_shop','on_display','in_transit','in_shipment','sold','with_customer','in_return','defective','repair','vendor_return','disposed') DEFAULT NULL COMMENT 'Status after movement',
  `notes` text DEFAULT NULL,
  `performed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_movements`
--

INSERT INTO `product_movements` (`id`, `product_batch_id`, `product_barcode_id`, `from_store_id`, `to_store_id`, `product_dispatch_id`, `movement_type`, `quantity`, `unit_cost`, `unit_price`, `total_cost`, `total_value`, `movement_date`, `reference_number`, `reference_type`, `reference_id`, `status_before`, `status_after`, `notes`, `performed_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 2, 1, 'transfer', 1, 700.00, 1000.00, 700.00, 1000.00, '2025-11-20 14:19:38', 'DSP-20251120-7247F1', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 823758388022', 1, '2025-11-20 08:19:38', '2025-11-20 08:19:38'),
(2, 2, 2, 1, 2, 1, 'transfer', 1, 700.00, 1000.00, 700.00, 1000.00, '2025-11-20 14:19:38', 'DSP-20251120-7247F1', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 187697284366', 1, '2025-11-20 08:19:38', '2025-11-20 08:19:38'),
(3, 2, 3, 1, 2, 1, 'transfer', 1, 700.00, 1000.00, 700.00, 1000.00, '2025-11-20 14:19:38', 'DSP-20251120-7247F1', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 188425871018', 1, '2025-11-20 08:19:38', '2025-11-20 08:19:38'),
(4, 2, 4, 1, 2, 1, 'transfer', 1, 700.00, 1000.00, 700.00, 1000.00, '2025-11-20 14:19:38', 'DSP-20251120-7247F1', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 199652485702', 1, '2025-11-20 08:19:38', '2025-11-20 08:19:38'),
(5, 2, 5, 1, 2, 1, 'transfer', 1, 700.00, 1000.00, 700.00, 1000.00, '2025-11-20 14:19:38', 'DSP-20251120-7247F1', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 065906326214', 1, '2025-11-20 08:19:38', '2025-11-20 08:19:38'),
(6, 1, 1, NULL, 1, NULL, 'return', 1, 1000.00, NULL, 1000.00, 0.00, '2025-11-20 14:50:07', NULL, 'return', 1, NULL, NULL, 'Product return: RET-20251120-0001 (Quality approved)', 1, '2025-11-20 08:50:07', '2025-11-20 08:50:07'),
(7, 1, 7, NULL, 1, NULL, 'defective', -1, 1000.00, NULL, 1000.00, 0.00, '2025-11-20 14:50:07', NULL, 'defective_product', 1, NULL, NULL, 'Marked as defective: malfunction', 1, '2025-11-20 08:50:07', '2025-11-20 08:50:07'),
(8, 3, 11, NULL, 1, NULL, 'return', 1, 3000.00, NULL, 3000.00, 0.00, '2025-11-20 15:43:46', NULL, 'return', 2, NULL, NULL, 'Product return: RET-20251120-0002 (Quality approved)', 1, '2025-11-20 09:43:46', '2025-11-20 09:43:46'),
(9, 4, 21, NULL, 2, NULL, 'return', 1, 1300.00, NULL, 1300.00, 0.00, '2025-11-20 15:50:50', NULL, 'return', 3, NULL, NULL, 'Product return: RET-20251120-0003 (Quality approved)', 1, '2025-11-20 09:50:50', '2025-11-20 09:50:50'),
(10, 3, 11, NULL, 1, NULL, 'return', 1, 3000.00, NULL, 3000.00, 0.00, '2025-11-20 16:00:51', NULL, 'return', 4, NULL, NULL, 'Product return: RET-20251120-0004 (Quality approved)', 1, '2025-11-20 10:00:51', '2025-11-20 10:00:51'),
(11, 6, 40, 1, 2, 2, 'transfer', 1, 4000.00, 5000.00, 4000.00, 5000.00, '2025-11-20 19:32:11', 'DSP-20251120-6E330D', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 374826156045', 1, '2025-11-20 13:32:11', '2025-11-20 13:32:11'),
(12, 6, 38, 1, 2, 2, 'transfer', 1, 4000.00, 5000.00, 4000.00, 5000.00, '2025-11-20 19:32:11', 'DSP-20251120-6E330D', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 586340806248', 1, '2025-11-20 13:32:11', '2025-11-20 13:32:11'),
(13, 6, 39, 1, 2, 2, 'transfer', 1, 4000.00, 5000.00, 4000.00, 5000.00, '2025-11-20 19:32:11', 'DSP-20251120-6E330D', NULL, NULL, NULL, NULL, 'Individual barcode transfer delivered: 392557891524', 1, '2025-11-20 13:32:11', '2025-11-20 13:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_price_overrides`
--

CREATE TABLE `product_price_overrides` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) NOT NULL COMMENT 'discount, promotion, price_change, clearance, etc.',
  `description` text DEFAULT NULL,
  `store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_returns`
--

CREATE TABLE `product_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `return_number` varchar(255) NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `received_at_store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `return_reason` enum('defective_product','wrong_item','not_as_described','customer_dissatisfaction','size_issue','color_issue','quality_issue','late_delivery','changed_mind','duplicate_order','other') NOT NULL,
  `return_reason_details` text DEFAULT NULL,
  `return_type` enum('customer_return','store_return','warehouse_return') NOT NULL DEFAULT 'customer_return',
  `status` enum('pending','approved','rejected','processing','completed','refunded') NOT NULL DEFAULT 'pending',
  `total_return_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_refund_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`return_items`)),
  `return_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `received_date` timestamp NULL DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL,
  `rejected_date` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `quality_check_passed` tinyint(1) DEFAULT NULL,
  `quality_check_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `tracking_number` varchar(255) DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_returns`
--

INSERT INTO `product_returns` (`id`, `return_number`, `order_id`, `customer_id`, `store_id`, `received_at_store_id`, `return_reason`, `return_reason_details`, `return_type`, `status`, `total_return_value`, `total_refund_amount`, `processing_fee`, `return_items`, `return_date`, `received_date`, `approved_date`, `processed_date`, `rejected_date`, `created_by`, `approved_by`, `processed_by`, `rejected_by`, `quality_check_passed`, `quality_check_notes`, `customer_notes`, `internal_notes`, `rejection_reason`, `attachments`, `tracking_number`, `status_history`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'RET-20251120-0001', 3, 3, 1, 1, 'defective_product', NULL, 'customer_return', 'refunded', 1000.00, 1000.00, 0.00, '[{\"order_item_id\":3,\"product_id\":3,\"product_batch_id\":1,\"product_name\":\"Shotoronji - Red\",\"quantity\":1,\"unit_price\":\"1000.00\",\"total_price\":1000,\"reason\":null}]', '2025-11-20 14:50:10', '2025-11-20 08:50:05', '2025-11-20 08:50:06', '2025-11-20 08:50:07', NULL, NULL, 1, 1, NULL, 1, 'Auto-approved via POS', 'notes', 'Approved via POS system', NULL, '[]', NULL, '[{\"status\":\"approved\",\"changed_at\":\"2025-11-20T14:50:06.317494Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:50:07.083660Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:50:07.943840Z\",\"changed_by\":null,\"notes\":null},{\"status\":\"refunded\",\"changed_at\":\"2025-11-20T14:50:10.229678Z\",\"changed_by\":null,\"notes\":null}]', '2025-11-20 08:50:04', '2025-11-20 08:50:10', NULL),
(2, 'RET-20251120-0002', 5, 5, 1, 1, 'other', NULL, 'customer_return', 'refunded', 3000.00, 3000.00, 0.00, '[{\"order_item_id\":5,\"product_id\":1,\"product_batch_id\":3,\"product_name\":\"60 count - White\",\"quantity\":1,\"unit_price\":\"3000.00\",\"total_price\":3000,\"reason\":null}]', '2025-11-20 15:43:47', '2025-11-20 09:43:45', '2025-11-20 09:43:45', '2025-11-20 09:43:46', NULL, NULL, 1, 1, NULL, 1, 'Exchange - Auto-approved via POS', 'Exchange transaction - Original Order: ORD-20251120-28B0EB', 'Exchange - Auto-approved via POS', NULL, '[]', NULL, '[{\"status\":\"approved\",\"changed_at\":\"2025-11-20T15:43:45.839691Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:43:46.179751Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:43:46.500313Z\",\"changed_by\":null,\"notes\":null},{\"status\":\"refunded\",\"changed_at\":\"2025-11-20T15:43:47.542775Z\",\"changed_by\":null,\"notes\":null}]', '2025-11-20 09:43:45', '2025-11-20 09:43:47', NULL),
(3, 'RET-20251120-0003', 7, 3, 2, 2, 'other', NULL, 'customer_return', 'refunded', 1300.00, 1300.00, 0.00, '[{\"order_item_id\":6,\"product_id\":2,\"product_batch_id\":4,\"product_name\":\"Shotoronji - Black\",\"quantity\":1,\"unit_price\":\"1300.00\",\"total_price\":1300,\"reason\":null}]', '2025-11-20 15:50:53', '2025-11-20 09:50:49', '2025-11-20 09:50:50', '2025-11-20 09:50:50', NULL, NULL, 1, 1, NULL, 1, 'Exchange - Auto-approved via POS', 'Exchange transaction - Original Order: ORD-20251120-BA38E6', 'Exchange - Auto-approved via POS', NULL, '[]', NULL, '[{\"status\":\"approved\",\"changed_at\":\"2025-11-20T15:50:50.296630Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:50:50.854499Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:50:51.364964Z\",\"changed_by\":null,\"notes\":null},{\"status\":\"refunded\",\"changed_at\":\"2025-11-20T15:50:53.018520Z\",\"changed_by\":null,\"notes\":null}]', '2025-11-20 09:50:49', '2025-11-20 09:50:53', NULL),
(4, 'RET-20251120-0004', 9, 6, 1, 1, 'other', NULL, 'customer_return', 'refunded', 3000.00, 3000.00, 0.00, '[{\"order_item_id\":8,\"product_id\":1,\"product_batch_id\":3,\"product_name\":\"60 count - White\",\"quantity\":1,\"unit_price\":\"3000.00\",\"total_price\":3000,\"reason\":null}]', '2025-11-20 16:00:53', '2025-11-20 10:00:50', '2025-11-20 10:00:50', '2025-11-20 10:00:51', NULL, NULL, 1, 1, NULL, 1, 'Exchange - Auto-approved via POS', 'Exchange transaction - Original Order: ORD-20251120-77C380', 'Exchange - Auto-approved via POS', NULL, '[]', NULL, '[{\"status\":\"approved\",\"changed_at\":\"2025-11-20T16:00:50.546016Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"processing\",\"changed_at\":\"2025-11-20T16:00:51.069768Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T16:00:51.585025Z\",\"changed_by\":null,\"notes\":null},{\"status\":\"refunded\",\"changed_at\":\"2025-11-20T16:00:53.157582Z\",\"changed_by\":null,\"notes\":null}]', '2025-11-20 10:00:49', '2025-11-20 10:00:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`attributes`)),
  `price_adjustment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_point` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('percentage','fixed','buy_x_get_y','free_shipping') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `buy_quantity` int(11) DEFAULT NULL,
  `get_quantity` int(11) DEFAULT NULL,
  `minimum_purchase` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `applicable_customers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_customers`)),
  `usage_limit` int(11) DEFAULT NULL,
  `usage_per_customer` int(11) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_usages`
--

CREATE TABLE `promotion_usages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(255) NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','sent_to_vendor','partially_received','received','cancelled','returned') NOT NULL DEFAULT 'draft',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partially_paid','paid','overdue') NOT NULL DEFAULT 'unpaid',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_due_date` date DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(255) DEFAULT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL DEFAULT 0,
  `quantity_pending` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `unit_sell_price` decimal(10,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `manufactured_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `receive_status` enum('pending','partially_received','fully_received','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `refund_number` varchar(255) NOT NULL,
  `return_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `refund_type` enum('full','percentage','partial_amount') NOT NULL DEFAULT 'full',
  `refund_percentage` decimal(5,2) DEFAULT NULL,
  `original_amount` decimal(10,2) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_method` enum('cash','bank_transfer','card_refund','store_credit','gift_card','digital_wallet','check','other') NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `refund_method_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refund_method_details`)),
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `bank_reference` varchar(255) DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `store_credit_expires_at` timestamp NULL DEFAULT NULL,
  `store_credit_code` varchar(255) DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`id`, `refund_number`, `return_id`, `order_id`, `customer_id`, `refund_type`, `refund_percentage`, `original_amount`, `refund_amount`, `processing_fee`, `refund_method`, `payment_reference`, `refund_method_details`, `status`, `processed_at`, `completed_at`, `failed_at`, `processed_by`, `approved_by`, `transaction_reference`, `bank_reference`, `gateway_reference`, `customer_notes`, `internal_notes`, `failure_reason`, `store_credit_expires_at`, `store_credit_code`, `status_history`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'REF-20251120-0001', 1, 3, 3, 'full', NULL, 1000.00, 1000.00, 0.00, 'cash', NULL, '{\"cash\":800,\"card\":200,\"bkash\":0,\"nagad\":0}', 'completed', '2025-11-20 08:50:09', '2025-11-20 08:50:10', NULL, 1, NULL, 'POS-REFUND-1763650209486', NULL, NULL, NULL, 'Refund processed via POS', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T14:50:09.432587Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T14:50:10.194707Z\",\"changed_by\":null,\"notes\":null}]', NULL, '2025-11-20 08:50:08', '2025-11-20 08:50:10'),
(2, 'REF-20251120-0002', 2, 5, 5, 'full', NULL, 3000.00, 3000.00, 0.00, 'cash', NULL, NULL, 'completed', '2025-11-20 09:43:47', '2025-11-20 09:43:47', NULL, 1, NULL, 'EXCHANGE-REFUND-1763653427206', NULL, NULL, NULL, 'Full refund for exchange - Original Order: ORD-20251120-28B0EB', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:43:47.139430Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:43:47.516848Z\",\"changed_by\":null,\"notes\":null}]', NULL, '2025-11-20 09:43:46', '2025-11-20 09:43:47'),
(3, 'REF-20251120-0003', 3, 7, 3, 'full', NULL, 1300.00, 1300.00, 0.00, 'cash', NULL, NULL, 'completed', '2025-11-20 09:50:52', '2025-11-20 09:50:52', NULL, 1, NULL, 'EXCHANGE-REFUND-1763653852483', NULL, NULL, NULL, 'Full refund for exchange - Original Order: ORD-20251120-BA38E6', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T15:50:52.438292Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T15:50:52.996802Z\",\"changed_by\":null,\"notes\":null}]', NULL, '2025-11-20 09:50:51', '2025-11-20 09:50:52'),
(4, 'REF-20251120-0004', 4, 9, 6, 'full', NULL, 3000.00, 3000.00, 0.00, 'cash', NULL, NULL, 'completed', '2025-11-20 10:00:52', '2025-11-20 10:00:53', NULL, 1, NULL, 'EXCHANGE-REFUND-1763654452668', NULL, NULL, NULL, 'Full refund for exchange - Original Order: ORD-20251120-77C380', NULL, NULL, NULL, '[{\"status\":\"processing\",\"changed_at\":\"2025-11-20T16:00:52.629576Z\",\"changed_by\":1,\"notes\":null},{\"status\":\"completed\",\"changed_at\":\"2025-11-20T16:00:53.131461Z\",\"changed_by\":null,\"notes\":null}]', NULL, '2025-11-20 10:00:52', '2025-11-20 10:00:53');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `guard_name` varchar(255) DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT 0 COMMENT 'Role hierarchy level',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Default role for new users',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `title`, `slug`, `description`, `guard_name`, `level`, `is_active`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'super-admin', 'Full system access with all permissions', 'web', 100, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(2, 'Admin', 'admin', 'Administrative access to most system functions', 'web', 90, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(3, 'Manager', 'manager', 'Management level access for overseeing operations', 'web', 80, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(4, 'Sales Representative', 'sales-rep', 'Access for sales and customer management', 'web', 60, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(5, 'Accountant', 'accountant', 'Financial and accounting access', 'web', 70, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(6, 'Warehouse Staff', 'warehouse-staff', 'Inventory and warehouse management access', 'web', 50, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(7, 'Customer Service', 'customer-service', 'Customer support and order management access', 'web', 40, 1, 0, '2025-11-20 07:33:15', '2025-11-20 07:33:15'),
(8, 'Viewer', 'viewer', 'Read-only access to system data', 'web', 10, 1, 1, '2025-11-20 07:33:15', '2025-11-20 07:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, 99, NULL, NULL),
(2, 1, 98, NULL, NULL),
(3, 1, 27, NULL, NULL),
(4, 1, 29, NULL, NULL),
(5, 1, 28, NULL, NULL),
(6, 1, 26, NULL, NULL),
(7, 1, 45, NULL, NULL),
(8, 1, 47, NULL, NULL),
(9, 1, 46, NULL, NULL),
(10, 1, 49, NULL, NULL),
(11, 1, 48, NULL, NULL),
(12, 1, 44, NULL, NULL),
(13, 1, 50, NULL, NULL),
(14, 1, 2, NULL, NULL),
(15, 1, 1, NULL, NULL),
(16, 1, 4, NULL, NULL),
(17, 1, 6, NULL, NULL),
(18, 1, 5, NULL, NULL),
(19, 1, 7, NULL, NULL),
(20, 1, 3, NULL, NULL),
(21, 1, 8, NULL, NULL),
(22, 1, 94, NULL, NULL),
(23, 1, 96, NULL, NULL),
(24, 1, 95, NULL, NULL),
(25, 1, 93, NULL, NULL),
(26, 1, 92, NULL, NULL),
(27, 1, 89, NULL, NULL),
(28, 1, 91, NULL, NULL),
(29, 1, 90, NULL, NULL),
(30, 1, 88, NULL, NULL),
(31, 1, 109, NULL, NULL),
(32, 1, 111, NULL, NULL),
(33, 1, 110, NULL, NULL),
(34, 1, 108, NULL, NULL),
(35, 1, 100, NULL, NULL),
(36, 1, 43, NULL, NULL),
(37, 1, 42, NULL, NULL),
(38, 1, 41, NULL, NULL),
(39, 1, 35, NULL, NULL),
(40, 1, 34, NULL, NULL),
(41, 1, 36, NULL, NULL),
(42, 1, 113, NULL, NULL),
(43, 1, 115, NULL, NULL),
(44, 1, 114, NULL, NULL),
(45, 1, 112, NULL, NULL),
(46, 1, 60, NULL, NULL),
(47, 1, 58, NULL, NULL),
(48, 1, 59, NULL, NULL),
(49, 1, 57, NULL, NULL),
(50, 1, 56, NULL, NULL),
(51, 1, 52, NULL, NULL),
(52, 1, 54, NULL, NULL),
(53, 1, 53, NULL, NULL),
(54, 1, 55, NULL, NULL),
(55, 1, 51, NULL, NULL),
(56, 1, 85, NULL, NULL),
(57, 1, 87, NULL, NULL),
(58, 1, 86, NULL, NULL),
(59, 1, 84, NULL, NULL),
(60, 1, 13, NULL, NULL),
(61, 1, 23, NULL, NULL),
(62, 1, 25, NULL, NULL),
(63, 1, 24, NULL, NULL),
(64, 1, 22, NULL, NULL),
(65, 1, 38, NULL, NULL),
(66, 1, 40, NULL, NULL),
(67, 1, 39, NULL, NULL),
(68, 1, 37, NULL, NULL),
(69, 1, 67, NULL, NULL),
(70, 1, 66, NULL, NULL),
(71, 1, 15, NULL, NULL),
(72, 1, 17, NULL, NULL),
(73, 1, 16, NULL, NULL),
(74, 1, 19, NULL, NULL),
(75, 1, 18, NULL, NULL),
(76, 1, 21, NULL, NULL),
(77, 1, 20, NULL, NULL),
(78, 1, 14, NULL, NULL),
(79, 1, 69, NULL, NULL),
(80, 1, 68, NULL, NULL),
(81, 1, 122, NULL, NULL),
(82, 1, 125, NULL, NULL),
(83, 1, 123, NULL, NULL),
(84, 1, 121, NULL, NULL),
(85, 1, 124, NULL, NULL),
(86, 1, 120, NULL, NULL),
(87, 1, 126, NULL, NULL),
(88, 1, 10, NULL, NULL),
(89, 1, 12, NULL, NULL),
(90, 1, 11, NULL, NULL),
(91, 1, 9, NULL, NULL),
(92, 1, 83, NULL, NULL),
(93, 1, 81, NULL, NULL),
(94, 1, 82, NULL, NULL),
(95, 1, 80, NULL, NULL),
(96, 1, 79, NULL, NULL),
(97, 1, 75, NULL, NULL),
(98, 1, 77, NULL, NULL),
(99, 1, 76, NULL, NULL),
(100, 1, 78, NULL, NULL),
(101, 1, 74, NULL, NULL),
(102, 1, 71, NULL, NULL),
(103, 1, 73, NULL, NULL),
(104, 1, 72, NULL, NULL),
(105, 1, 70, NULL, NULL),
(106, 1, 62, NULL, NULL),
(107, 1, 64, NULL, NULL),
(108, 1, 63, NULL, NULL),
(109, 1, 65, NULL, NULL),
(110, 1, 61, NULL, NULL),
(111, 1, 102, NULL, NULL),
(112, 1, 104, NULL, NULL),
(113, 1, 103, NULL, NULL),
(114, 1, 105, NULL, NULL),
(115, 1, 101, NULL, NULL),
(116, 1, 118, NULL, NULL),
(117, 1, 119, NULL, NULL),
(118, 1, 116, NULL, NULL),
(119, 1, 117, NULL, NULL),
(120, 1, 107, NULL, NULL),
(121, 1, 106, NULL, NULL),
(122, 1, 97, NULL, NULL),
(123, 1, 31, NULL, NULL),
(124, 1, 33, NULL, NULL),
(125, 1, 32, NULL, NULL),
(126, 1, 30, NULL, NULL),
(127, 2, 99, NULL, NULL),
(128, 2, 98, NULL, NULL),
(129, 2, 27, NULL, NULL),
(130, 2, 29, NULL, NULL),
(131, 2, 28, NULL, NULL),
(132, 2, 26, NULL, NULL),
(133, 2, 45, NULL, NULL),
(134, 2, 47, NULL, NULL),
(135, 2, 46, NULL, NULL),
(136, 2, 49, NULL, NULL),
(137, 2, 48, NULL, NULL),
(138, 2, 44, NULL, NULL),
(139, 2, 50, NULL, NULL),
(140, 2, 2, NULL, NULL),
(141, 2, 1, NULL, NULL),
(142, 2, 4, NULL, NULL),
(143, 2, 5, NULL, NULL),
(144, 2, 7, NULL, NULL),
(145, 2, 3, NULL, NULL),
(146, 2, 8, NULL, NULL),
(147, 2, 94, NULL, NULL),
(148, 2, 96, NULL, NULL),
(149, 2, 95, NULL, NULL),
(150, 2, 93, NULL, NULL),
(151, 2, 92, NULL, NULL),
(152, 2, 89, NULL, NULL),
(153, 2, 91, NULL, NULL),
(154, 2, 90, NULL, NULL),
(155, 2, 88, NULL, NULL),
(156, 2, 109, NULL, NULL),
(157, 2, 111, NULL, NULL),
(158, 2, 110, NULL, NULL),
(159, 2, 108, NULL, NULL),
(160, 2, 100, NULL, NULL),
(161, 2, 43, NULL, NULL),
(162, 2, 42, NULL, NULL),
(163, 2, 41, NULL, NULL),
(164, 2, 35, NULL, NULL),
(165, 2, 34, NULL, NULL),
(166, 2, 36, NULL, NULL),
(167, 2, 113, NULL, NULL),
(168, 2, 115, NULL, NULL),
(169, 2, 114, NULL, NULL),
(170, 2, 112, NULL, NULL),
(171, 2, 60, NULL, NULL),
(172, 2, 58, NULL, NULL),
(173, 2, 59, NULL, NULL),
(174, 2, 57, NULL, NULL),
(175, 2, 56, NULL, NULL),
(176, 2, 52, NULL, NULL),
(177, 2, 54, NULL, NULL),
(178, 2, 53, NULL, NULL),
(179, 2, 55, NULL, NULL),
(180, 2, 51, NULL, NULL),
(181, 2, 85, NULL, NULL),
(182, 2, 87, NULL, NULL),
(183, 2, 86, NULL, NULL),
(184, 2, 84, NULL, NULL),
(185, 2, 23, NULL, NULL),
(186, 2, 25, NULL, NULL),
(187, 2, 24, NULL, NULL),
(188, 2, 22, NULL, NULL),
(189, 2, 38, NULL, NULL),
(190, 2, 40, NULL, NULL),
(191, 2, 39, NULL, NULL),
(192, 2, 37, NULL, NULL),
(193, 2, 67, NULL, NULL),
(194, 2, 66, NULL, NULL),
(195, 2, 15, NULL, NULL),
(196, 2, 17, NULL, NULL),
(197, 2, 16, NULL, NULL),
(198, 2, 19, NULL, NULL),
(199, 2, 18, NULL, NULL),
(200, 2, 21, NULL, NULL),
(201, 2, 20, NULL, NULL),
(202, 2, 14, NULL, NULL),
(203, 2, 69, NULL, NULL),
(204, 2, 68, NULL, NULL),
(205, 2, 122, NULL, NULL),
(206, 2, 125, NULL, NULL),
(207, 2, 123, NULL, NULL),
(208, 2, 121, NULL, NULL),
(209, 2, 124, NULL, NULL),
(210, 2, 120, NULL, NULL),
(211, 2, 126, NULL, NULL),
(212, 2, 9, NULL, NULL),
(213, 2, 83, NULL, NULL),
(214, 2, 81, NULL, NULL),
(215, 2, 82, NULL, NULL),
(216, 2, 80, NULL, NULL),
(217, 2, 79, NULL, NULL),
(218, 2, 75, NULL, NULL),
(219, 2, 77, NULL, NULL),
(220, 2, 76, NULL, NULL),
(221, 2, 78, NULL, NULL),
(222, 2, 74, NULL, NULL),
(223, 2, 71, NULL, NULL),
(224, 2, 73, NULL, NULL),
(225, 2, 72, NULL, NULL),
(226, 2, 70, NULL, NULL),
(227, 2, 62, NULL, NULL),
(228, 2, 64, NULL, NULL),
(229, 2, 63, NULL, NULL),
(230, 2, 65, NULL, NULL),
(231, 2, 61, NULL, NULL),
(232, 2, 102, NULL, NULL),
(233, 2, 104, NULL, NULL),
(234, 2, 103, NULL, NULL),
(235, 2, 105, NULL, NULL),
(236, 2, 101, NULL, NULL),
(237, 2, 118, NULL, NULL),
(238, 2, 119, NULL, NULL),
(239, 2, 107, NULL, NULL),
(240, 2, 106, NULL, NULL),
(241, 2, 97, NULL, NULL),
(242, 2, 31, NULL, NULL),
(243, 2, 33, NULL, NULL),
(244, 2, 32, NULL, NULL),
(245, 2, 30, NULL, NULL),
(246, 3, 98, NULL, NULL),
(247, 3, 27, NULL, NULL),
(248, 3, 28, NULL, NULL),
(249, 3, 26, NULL, NULL),
(250, 3, 45, NULL, NULL),
(251, 3, 46, NULL, NULL),
(252, 3, 49, NULL, NULL),
(253, 3, 48, NULL, NULL),
(254, 3, 44, NULL, NULL),
(255, 3, 50, NULL, NULL),
(256, 3, 2, NULL, NULL),
(257, 3, 1, NULL, NULL),
(258, 3, 3, NULL, NULL),
(259, 3, 8, NULL, NULL),
(260, 3, 93, NULL, NULL),
(261, 3, 92, NULL, NULL),
(262, 3, 89, NULL, NULL),
(263, 3, 90, NULL, NULL),
(264, 3, 88, NULL, NULL),
(265, 3, 100, NULL, NULL),
(266, 3, 43, NULL, NULL),
(267, 3, 42, NULL, NULL),
(268, 3, 41, NULL, NULL),
(269, 3, 35, NULL, NULL),
(270, 3, 34, NULL, NULL),
(271, 3, 36, NULL, NULL),
(272, 3, 113, NULL, NULL),
(273, 3, 114, NULL, NULL),
(274, 3, 112, NULL, NULL),
(275, 3, 60, NULL, NULL),
(276, 3, 58, NULL, NULL),
(277, 3, 59, NULL, NULL),
(278, 3, 57, NULL, NULL),
(279, 3, 56, NULL, NULL),
(280, 3, 52, NULL, NULL),
(281, 3, 53, NULL, NULL),
(282, 3, 55, NULL, NULL),
(283, 3, 51, NULL, NULL),
(284, 3, 84, NULL, NULL),
(285, 3, 23, NULL, NULL),
(286, 3, 24, NULL, NULL),
(287, 3, 22, NULL, NULL),
(288, 3, 38, NULL, NULL),
(289, 3, 39, NULL, NULL),
(290, 3, 37, NULL, NULL),
(291, 3, 67, NULL, NULL),
(292, 3, 66, NULL, NULL),
(293, 3, 15, NULL, NULL),
(294, 3, 16, NULL, NULL),
(295, 3, 19, NULL, NULL),
(296, 3, 18, NULL, NULL),
(297, 3, 14, NULL, NULL),
(298, 3, 69, NULL, NULL),
(299, 3, 68, NULL, NULL),
(300, 3, 122, NULL, NULL),
(301, 3, 125, NULL, NULL),
(302, 3, 123, NULL, NULL),
(303, 3, 121, NULL, NULL),
(304, 3, 124, NULL, NULL),
(305, 3, 120, NULL, NULL),
(306, 3, 83, NULL, NULL),
(307, 3, 81, NULL, NULL),
(308, 3, 82, NULL, NULL),
(309, 3, 80, NULL, NULL),
(310, 3, 79, NULL, NULL),
(311, 3, 75, NULL, NULL),
(312, 3, 76, NULL, NULL),
(313, 3, 78, NULL, NULL),
(314, 3, 74, NULL, NULL),
(315, 3, 71, NULL, NULL),
(316, 3, 72, NULL, NULL),
(317, 3, 70, NULL, NULL),
(318, 3, 62, NULL, NULL),
(319, 3, 63, NULL, NULL),
(320, 3, 65, NULL, NULL),
(321, 3, 61, NULL, NULL),
(322, 3, 103, NULL, NULL),
(323, 3, 105, NULL, NULL),
(324, 3, 101, NULL, NULL),
(325, 3, 97, NULL, NULL),
(326, 3, 31, NULL, NULL),
(327, 3, 32, NULL, NULL),
(328, 3, 30, NULL, NULL),
(329, 4, 45, NULL, NULL),
(330, 4, 46, NULL, NULL),
(331, 4, 44, NULL, NULL),
(332, 4, 50, NULL, NULL),
(333, 4, 1, NULL, NULL),
(334, 4, 113, NULL, NULL),
(335, 4, 112, NULL, NULL),
(336, 4, 58, NULL, NULL),
(337, 4, 57, NULL, NULL),
(338, 4, 52, NULL, NULL),
(339, 4, 53, NULL, NULL),
(340, 4, 51, NULL, NULL),
(341, 4, 66, NULL, NULL),
(342, 4, 122, NULL, NULL),
(343, 4, 120, NULL, NULL),
(344, 4, 81, NULL, NULL),
(345, 4, 80, NULL, NULL),
(346, 4, 75, NULL, NULL),
(347, 4, 76, NULL, NULL),
(348, 4, 74, NULL, NULL),
(349, 4, 70, NULL, NULL),
(350, 4, 65, NULL, NULL),
(351, 4, 61, NULL, NULL),
(352, 5, 99, NULL, NULL),
(353, 5, 98, NULL, NULL),
(354, 5, 1, NULL, NULL),
(355, 5, 94, NULL, NULL),
(356, 5, 96, NULL, NULL),
(357, 5, 95, NULL, NULL),
(358, 5, 93, NULL, NULL),
(359, 5, 92, NULL, NULL),
(360, 5, 89, NULL, NULL),
(361, 5, 91, NULL, NULL),
(362, 5, 90, NULL, NULL),
(363, 5, 88, NULL, NULL),
(364, 5, 100, NULL, NULL),
(365, 5, 113, NULL, NULL),
(366, 5, 114, NULL, NULL),
(367, 5, 112, NULL, NULL),
(368, 5, 58, NULL, NULL),
(369, 5, 59, NULL, NULL),
(370, 5, 57, NULL, NULL),
(371, 5, 85, NULL, NULL),
(372, 5, 86, NULL, NULL),
(373, 5, 84, NULL, NULL),
(374, 5, 125, NULL, NULL),
(375, 5, 123, NULL, NULL),
(376, 5, 120, NULL, NULL),
(377, 5, 81, NULL, NULL),
(378, 5, 82, NULL, NULL),
(379, 5, 80, NULL, NULL),
(380, 5, 97, NULL, NULL),
(381, 6, 1, NULL, NULL),
(382, 6, 42, NULL, NULL),
(383, 6, 41, NULL, NULL),
(384, 6, 35, NULL, NULL),
(385, 6, 34, NULL, NULL),
(386, 6, 36, NULL, NULL),
(387, 6, 113, NULL, NULL),
(388, 6, 112, NULL, NULL),
(389, 6, 51, NULL, NULL),
(390, 6, 22, NULL, NULL),
(391, 6, 38, NULL, NULL),
(392, 6, 39, NULL, NULL),
(393, 6, 37, NULL, NULL),
(394, 6, 67, NULL, NULL),
(395, 6, 66, NULL, NULL),
(396, 6, 14, NULL, NULL),
(397, 6, 121, NULL, NULL),
(398, 6, 62, NULL, NULL),
(399, 6, 63, NULL, NULL),
(400, 6, 65, NULL, NULL),
(401, 6, 61, NULL, NULL),
(402, 7, 46, NULL, NULL),
(403, 7, 44, NULL, NULL),
(404, 7, 50, NULL, NULL),
(405, 7, 1, NULL, NULL),
(406, 7, 113, NULL, NULL),
(407, 7, 114, NULL, NULL),
(408, 7, 112, NULL, NULL),
(409, 7, 57, NULL, NULL),
(410, 7, 53, NULL, NULL),
(411, 7, 55, NULL, NULL),
(412, 7, 51, NULL, NULL),
(413, 7, 67, NULL, NULL),
(414, 7, 66, NULL, NULL),
(415, 7, 69, NULL, NULL),
(416, 7, 68, NULL, NULL),
(417, 7, 122, NULL, NULL),
(418, 7, 120, NULL, NULL),
(419, 7, 80, NULL, NULL),
(420, 7, 76, NULL, NULL),
(421, 7, 78, NULL, NULL),
(422, 7, 74, NULL, NULL),
(423, 7, 70, NULL, NULL),
(424, 7, 65, NULL, NULL),
(425, 7, 61, NULL, NULL),
(426, 8, 98, NULL, NULL),
(427, 8, 26, NULL, NULL),
(428, 8, 44, NULL, NULL),
(429, 8, 50, NULL, NULL),
(430, 8, 1, NULL, NULL),
(431, 8, 3, NULL, NULL),
(432, 8, 93, NULL, NULL),
(433, 8, 88, NULL, NULL),
(434, 8, 108, NULL, NULL),
(435, 8, 41, NULL, NULL),
(436, 8, 34, NULL, NULL),
(437, 8, 36, NULL, NULL),
(438, 8, 112, NULL, NULL),
(439, 8, 57, NULL, NULL),
(440, 8, 51, NULL, NULL),
(441, 8, 84, NULL, NULL),
(442, 8, 22, NULL, NULL),
(443, 8, 37, NULL, NULL),
(444, 8, 66, NULL, NULL),
(445, 8, 14, NULL, NULL),
(446, 8, 68, NULL, NULL),
(447, 8, 122, NULL, NULL),
(448, 8, 123, NULL, NULL),
(449, 8, 121, NULL, NULL),
(450, 8, 124, NULL, NULL),
(451, 8, 120, NULL, NULL),
(452, 8, 9, NULL, NULL),
(453, 8, 80, NULL, NULL),
(454, 8, 74, NULL, NULL),
(455, 8, 70, NULL, NULL),
(456, 8, 61, NULL, NULL),
(457, 8, 101, NULL, NULL),
(458, 8, 97, NULL, NULL),
(459, 8, 30, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_price` decimal(10,2) DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `pricing_type` varchar(255) NOT NULL DEFAULT 'fixed',
  `estimated_duration` int(11) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `min_quantity` int(11) NOT NULL DEFAULT 1,
  `max_quantity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `icon` varchar(255) DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `instructions` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_fields`
--

CREATE TABLE `service_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL,
  `value` text DEFAULT NULL,
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value_json`)),
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_orders`
--

CREATE TABLE `service_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_order_number` varchar(255) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','partially_paid','paid','refunded','partially_refunded','overdue') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_installment_payment` tinyint(1) NOT NULL DEFAULT 0,
  `total_installments` int(11) DEFAULT NULL,
  `paid_installments` int(11) NOT NULL DEFAULT 0,
  `installment_amount` decimal(10,2) DEFAULT NULL,
  `next_payment_due` date DEFAULT NULL,
  `allow_partial_payments` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_payment_amount` decimal(10,2) DEFAULT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `scheduled_time` timestamp NULL DEFAULT NULL,
  `estimated_completion` timestamp NULL DEFAULT NULL,
  `actual_completion` timestamp NULL DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(255) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `payment_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_schedule`)),
  `payment_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_history`)),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_order_items`
--

CREATE TABLE `service_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_order_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `service_field_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_code` varchar(255) NOT NULL,
  `service_description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_options`)),
  `field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_values`)),
  `customizations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customizations`)),
  `status` enum('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `scheduled_time` timestamp NULL DEFAULT NULL,
  `estimated_duration` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_order_payments`
--

CREATE TABLE `service_order_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `service_order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `is_partial_payment` tinyint(1) NOT NULL DEFAULT 0,
  `installment_number` int(11) DEFAULT NULL,
  `payment_type` enum('full','installment','partial','final','advance') NOT NULL DEFAULT 'full',
  `payment_due_date` date DEFAULT NULL,
  `payment_received_date` date DEFAULT NULL,
  `order_balance_before` decimal(10,2) DEFAULT NULL,
  `order_balance_after` decimal(10,2) DEFAULT NULL,
  `expected_installment_amount` decimal(10,2) DEFAULT NULL,
  `installment_notes` text DEFAULT NULL,
  `is_late_payment` tinyint(1) NOT NULL DEFAULT 0,
  `days_late` int(11) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refund_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `shipment_number` varchar(255) NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` bigint(20) UNSIGNED NOT NULL,
  `pathao_consignment_id` varchar(255) DEFAULT NULL,
  `pathao_tracking_number` varchar(255) DEFAULT NULL,
  `pathao_status` varchar(255) DEFAULT NULL,
  `pathao_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pathao_response`)),
  `status` enum('pending','pickup_requested','picked_up','in_transit','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_type` enum('home_delivery','store_pickup','express') NOT NULL DEFAULT 'home_delivery',
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cod_amount` decimal(10,2) DEFAULT NULL,
  `package_weight` decimal(8,2) DEFAULT NULL,
  `package_dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`package_dimensions`)),
  `special_instructions` text DEFAULT NULL,
  `pickup_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pickup_address`)),
  `delivery_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`delivery_address`)),
  `package_barcodes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`package_barcodes`)),
  `pickup_requested_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `estimated_delivery_date` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `delivered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `recipient_phone` varchar(255) DEFAULT NULL,
  `recipient_signature` varchar(255) DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `pathao_key` varchar(100) DEFAULT NULL,
  `is_warehouse` tinyint(1) NOT NULL DEFAULT 0,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `store_code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL COMMENT 'Warehouse capacity in sq ft or units',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `name`, `address`, `pathao_key`, `is_warehouse`, `is_online`, `phone`, `email`, `contact_person`, `store_code`, `description`, `latitude`, `longitude`, `capacity`, `is_active`, `opening_hours`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Main Store', '123 Main Street, City, Country', NULL, 1, 1, '+1234567890', 'main@deshioerp.com', 'Store Manager', 'MAIN', 'Main headquarters store', NULL, NULL, NULL, 1, '\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"16:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"10:00\\\",\\\"close\\\":\\\"14:00\\\"}}\"', '2025-11-20 07:33:15', '2025-11-20 07:33:15', NULL),
(2, 'Uttara', 'Uttara,Dhaka', 'UTT123', 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-11-20 07:39:47', '2025-11-20 07:39:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_number` varchar(255) NOT NULL,
  `transaction_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('debit','credit') NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `store_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_number`, `transaction_date`, `amount`, `type`, `account_id`, `reference_type`, `reference_id`, `description`, `store_id`, `created_by`, `metadata`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TXN-20251120-UKSN4DKN', '2025-11-20', 1050.00, 'debit', 2, 'App\\Models\\OrderPayment', 1, 'Order Payment - PAY-20251120-5AA5AD8B', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-FB8120\",\"customer_name\":\"Rumy Parvez\"}', 'completed', '2025-11-20 08:24:04', '2025-11-20 08:24:04'),
(2, 'TXN-20251120-RI0DBACY', '2025-11-20', 1050.00, 'credit', 12, 'App\\Models\\OrderPayment', 1, 'Order Payment - PAY-20251120-5AA5AD8B', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-FB8120\",\"customer_name\":\"Rumy Parvez\"}', 'pending', '2025-11-20 08:24:04', '2025-11-20 08:24:04'),
(3, 'TXN-20251120-Q5BOHWVM', '2025-11-20', 1050.00, 'debit', 2, 'App\\Models\\OrderPayment', 2, 'Order Payment - PAY-20251120-5C3AF395', 2, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-AD6A06\",\"customer_name\":\"Nishat Tasneem\"}', 'completed', '2025-11-20 08:41:32', '2025-11-20 08:41:32'),
(4, 'TXN-20251120-LDV2DECS', '2025-11-20', 1050.00, 'credit', 12, 'App\\Models\\OrderPayment', 2, 'Order Payment - PAY-20251120-5C3AF395', 2, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-AD6A06\",\"customer_name\":\"Nishat Tasneem\"}', 'pending', '2025-11-20 08:41:32', '2025-11-20 08:41:32'),
(5, 'TXN-20251120-UCVFCEWW', '2025-11-20', 1050.00, 'debit', 2, 'App\\Models\\OrderPayment', 3, 'Order Payment - PAY-20251120-C4DB24DB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-A6218C\",\"customer_name\":\"Walk-in Customer\"}', 'completed', '2025-11-20 08:47:25', '2025-11-20 08:47:25'),
(6, 'TXN-20251120-1AETZLLM', '2025-11-20', 1050.00, 'credit', 12, 'App\\Models\\OrderPayment', 3, 'Order Payment - PAY-20251120-C4DB24DB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-A6218C\",\"customer_name\":\"Walk-in Customer\"}', 'pending', '2025-11-20 08:47:25', '2025-11-20 08:47:25'),
(7, 'TXN-20251120-YULENEPJ', '2025-11-20', 1000.00, 'credit', 2, 'App\\Models\\Refund', 1, 'Refund - REF-20251120-0001', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-A6218C\",\"customer_name\":\"Walk-in Customer\",\"refund_type\":\"full\"}', 'completed', '2025-11-20 08:50:08', '2025-11-20 08:50:10'),
(8, 'TXN-20251120-K3XPEFM8', '2025-11-20', 1000.00, 'debit', 12, 'App\\Models\\Refund', 1, 'Refund - REF-20251120-0001', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-A6218C\",\"customer_name\":\"Walk-in Customer\",\"refund_type\":\"full\"}', 'pending', '2025-11-20 08:50:08', '2025-11-20 08:50:08'),
(9, 'POS-REFUND-1763650209486-CASH', '2025-11-20', 1000.00, 'credit', 2, 'refund', 1, 'Cash refund for return: RET-20251120-0001', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":1,\"return_id\":1}', 'completed', '2025-11-20 08:50:10', '2025-11-20 08:50:10'),
(10, 'POS-REFUND-1763650209486-REV', '2025-11-20', 1000.00, 'debit', 12, 'refund', 1, 'Revenue reversal for return: RET-20251120-0001', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":1,\"return_id\":1}', 'completed', '2025-11-20 08:50:10', '2025-11-20 08:50:10'),
(11, 'TXN-20251120-EYEWDKKV', '2025-11-20', 315.00, 'debit', 2, 'App\\Models\\OrderPayment', 4, 'Order Payment - PAY-20251120-A3CBF2BB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-C5DA66\",\"customer_name\":\"Sneha\"}', 'completed', '2025-11-20 08:55:38', '2025-11-20 08:55:38'),
(12, 'TXN-20251120-BK9YBKMU', '2025-11-20', 315.00, 'credit', 12, 'App\\Models\\OrderPayment', 4, 'Order Payment - PAY-20251120-A3CBF2BB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-C5DA66\",\"customer_name\":\"Sneha\"}', 'pending', '2025-11-20 08:55:38', '2025-11-20 08:55:38'),
(13, 'TXN-20251120-JKPR6LXM', '2025-11-20', 3150.00, 'debit', 2, 'App\\Models\\OrderPayment', 5, 'Order Payment - PAY-20251120-9D6ED87A', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-28B0EB\",\"customer_name\":\"Ariyaan\"}', 'completed', '2025-11-20 09:23:35', '2025-11-20 09:23:35'),
(14, 'TXN-20251120-QUBMEEWQ', '2025-11-20', 3150.00, 'credit', 12, 'App\\Models\\OrderPayment', 5, 'Order Payment - PAY-20251120-9D6ED87A', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-28B0EB\",\"customer_name\":\"Ariyaan\"}', 'pending', '2025-11-20 09:23:35', '2025-11-20 09:23:35'),
(15, 'TXN-20251120-NAKIZMMA', '2025-11-20', 3000.00, 'credit', 2, 'App\\Models\\Refund', 2, 'Refund - REF-20251120-0002', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-28B0EB\",\"customer_name\":\"Ariyaan\",\"refund_type\":\"full\"}', 'completed', '2025-11-20 09:43:46', '2025-11-20 09:43:47'),
(16, 'TXN-20251120-UGDHLNDH', '2025-11-20', 3000.00, 'debit', 12, 'App\\Models\\Refund', 2, 'Refund - REF-20251120-0002', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-28B0EB\",\"customer_name\":\"Ariyaan\",\"refund_type\":\"full\"}', 'pending', '2025-11-20 09:43:46', '2025-11-20 09:43:46'),
(17, 'EXCHANGE-REFUND-1763653427206-CASH', '2025-11-20', 3000.00, 'credit', 2, 'refund', 2, 'Cash refund for return: RET-20251120-0002', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":2,\"return_id\":2}', 'completed', '2025-11-20 09:43:47', '2025-11-20 09:43:47'),
(18, 'EXCHANGE-REFUND-1763653427206-REV', '2025-11-20', 3000.00, 'debit', 12, 'refund', 2, 'Revenue reversal for return: RET-20251120-0002', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":2,\"return_id\":2}', 'completed', '2025-11-20 09:43:47', '2025-11-20 09:43:47'),
(19, 'TXN-20251120-YFJOKAXA', '2025-11-20', 1365.00, 'debit', 2, 'App\\Models\\OrderPayment', 6, 'Order Payment - PAY-20251120-99DFC324', 2, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-BA38E6\",\"customer_name\":\"Walk-in Customer\"}', 'completed', '2025-11-20 09:48:50', '2025-11-20 09:48:50'),
(20, 'TXN-20251120-DOVKUV1L', '2025-11-20', 1365.00, 'credit', 12, 'App\\Models\\OrderPayment', 6, 'Order Payment - PAY-20251120-99DFC324', 2, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-BA38E6\",\"customer_name\":\"Walk-in Customer\"}', 'pending', '2025-11-20 09:48:50', '2025-11-20 09:48:50'),
(21, 'TXN-20251120-ICS9JC4C', '2025-11-20', 1300.00, 'credit', 2, 'App\\Models\\Refund', 3, 'Refund - REF-20251120-0003', 2, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-BA38E6\",\"customer_name\":\"Walk-in Customer\",\"refund_type\":\"full\"}', 'completed', '2025-11-20 09:50:51', '2025-11-20 09:50:53'),
(22, 'TXN-20251120-OVPK5PO7', '2025-11-20', 1300.00, 'debit', 12, 'App\\Models\\Refund', 3, 'Refund - REF-20251120-0003', 2, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-BA38E6\",\"customer_name\":\"Walk-in Customer\",\"refund_type\":\"full\"}', 'pending', '2025-11-20 09:50:51', '2025-11-20 09:50:51'),
(23, 'EXCHANGE-REFUND-1763653852483-CASH', '2025-11-20', 1300.00, 'credit', 2, 'refund', 3, 'Cash refund for return: RET-20251120-0003', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":3,\"return_id\":3}', 'completed', '2025-11-20 09:50:53', '2025-11-20 09:50:53'),
(24, 'EXCHANGE-REFUND-1763653852483-REV', '2025-11-20', 1300.00, 'debit', 12, 'refund', 3, 'Revenue reversal for return: RET-20251120-0003', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":3,\"return_id\":3}', 'completed', '2025-11-20 09:50:53', '2025-11-20 09:50:53'),
(25, 'TXN-20251120-BUECIABH', '2025-11-20', 1300.00, 'debit', 2, 'App\\Models\\OrderPayment', 7, 'Order Payment - PAY-20251120-93696F52', 2, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-4AE399\",\"customer_name\":\"Walk-in Customer\"}', 'pending', '2025-11-20 09:50:53', '2025-11-20 09:50:53'),
(26, 'TXN-20251120-XVBMAVRD', '2025-11-20', 1300.00, 'credit', 12, 'App\\Models\\OrderPayment', 7, 'Order Payment - PAY-20251120-93696F52', 2, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-4AE399\",\"customer_name\":\"Walk-in Customer\"}', 'pending', '2025-11-20 09:50:53', '2025-11-20 09:50:53'),
(27, 'TXN-20251120-C13FK4LX', '2025-11-20', 3150.00, 'debit', 2, 'App\\Models\\OrderPayment', 8, 'Order Payment - PAY-20251120-876E65AB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-77C380\",\"customer_name\":\"Rodoshi\"}', 'completed', '2025-11-20 09:58:42', '2025-11-20 09:58:42'),
(28, 'TXN-20251120-EGXOYLVL', '2025-11-20', 3150.00, 'credit', 12, 'App\\Models\\OrderPayment', 8, 'Order Payment - PAY-20251120-876E65AB', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251120-77C380\",\"customer_name\":\"Rodoshi\"}', 'pending', '2025-11-20 09:58:42', '2025-11-20 09:58:42'),
(29, 'TXN-20251120-ACTSOKXW', '2025-11-20', 3000.00, 'credit', 2, 'App\\Models\\Refund', 4, 'Refund - REF-20251120-0004', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-77C380\",\"customer_name\":\"Rodoshi\",\"refund_type\":\"full\"}', 'completed', '2025-11-20 10:00:52', '2025-11-20 10:00:53'),
(30, 'TXN-20251120-VGM80KJT', '2025-11-20', 3000.00, 'debit', 12, 'App\\Models\\Refund', 4, 'Refund - REF-20251120-0004', 1, NULL, '{\"refund_method\":\"cash\",\"order_number\":\"ORD-20251120-77C380\",\"customer_name\":\"Rodoshi\",\"refund_type\":\"full\"}', 'pending', '2025-11-20 10:00:52', '2025-11-20 10:00:52'),
(31, 'EXCHANGE-REFUND-1763654452668-CASH', '2025-11-20', 3000.00, 'credit', 2, 'refund', 4, 'Cash refund for return: RET-20251120-0004', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":4,\"return_id\":4}', 'completed', '2025-11-20 10:00:53', '2025-11-20 10:00:53'),
(32, 'EXCHANGE-REFUND-1763654452668-REV', '2025-11-20', 3000.00, 'debit', 12, 'refund', 4, 'Revenue reversal for return: RET-20251120-0004', 1, 1, '{\"refund_method\":\"cash\",\"refund_id\":4,\"return_id\":4}', 'completed', '2025-11-20 10:00:53', '2025-11-20 10:00:53'),
(33, 'TXN-20251120-UKAMLRWV', '2025-11-20', 5000.00, 'debit', 2, 'App\\Models\\OrderPayment', 9, 'Order Payment - PAY-20251120-A8EF7ECA', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-961538\",\"customer_name\":\"Rodoshi\"}', 'pending', '2025-11-20 10:00:53', '2025-11-20 10:00:53'),
(34, 'TXN-20251120-8JQCQSGQ', '2025-11-20', 5000.00, 'credit', 12, 'App\\Models\\OrderPayment', 9, 'Order Payment - PAY-20251120-A8EF7ECA', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251120-961538\",\"customer_name\":\"Rodoshi\"}', 'pending', '2025-11-20 10:00:53', '2025-11-20 10:00:53'),
(35, 'TXN-20251122-LC5IFXMO', '2025-11-22', 3150.00, 'debit', 2, 'App\\Models\\OrderPayment', 10, 'Order Payment - PAY-20251122-5AA9538D', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251122-3812E5\",\"customer_name\":\"Rodoshi\"}', 'completed', '2025-11-22 00:17:12', '2025-11-22 00:17:12'),
(36, 'TXN-20251122-8JJWHQC9', '2025-11-22', 3150.00, 'credit', 12, 'App\\Models\\OrderPayment', 10, 'Order Payment - PAY-20251122-5AA9538D', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251122-3812E5\",\"customer_name\":\"Rodoshi\"}', 'pending', '2025-11-22 00:17:12', '2025-11-22 00:17:12'),
(37, 'TXN-20251122-3TM3UI4L', '2025-11-22', 3150.00, 'debit', 2, 'App\\Models\\OrderPayment', 11, 'Order Payment - PAY-20251122-0238AAB1', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251122-88DC6D\",\"customer_name\":\"Rodoshi\"}', 'completed', '2025-11-22 01:18:52', '2025-11-22 01:18:52'),
(38, 'TXN-20251122-WHPI8DQZ', '2025-11-22', 3150.00, 'credit', 12, 'App\\Models\\OrderPayment', 11, 'Order Payment - PAY-20251122-0238AAB1', 1, 1, '{\"payment_method\":\"Unknown\",\"order_number\":\"ORD-20251122-88DC6D\",\"customer_name\":\"Rodoshi\"}', 'pending', '2025-11-22 01:18:52', '2025-11-22 01:18:52'),
(39, 'TXN-20251122-D0DFT7QO', '2025-11-22', 3150.00, 'debit', 2, 'App\\Models\\OrderPayment', 12, 'Order Payment - PAY-20251122-01FFB393', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251122-B66072\",\"customer_name\":\"Nilu\"}', 'completed', '2025-11-22 08:21:39', '2025-11-22 08:21:39'),
(40, 'TXN-20251122-PVHV9ZKZ', '2025-11-22', 3150.00, 'credit', 12, 'App\\Models\\OrderPayment', 12, 'Order Payment - PAY-20251122-01FFB393', 1, 1, '{\"payment_method\":\"Cash\",\"order_number\":\"ORD-20251122-B66072\",\"customer_name\":\"Nilu\"}', 'pending', '2025-11-22 08:21:39', '2025-11-22 08:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `variant_options`
--

CREATE TABLE `variant_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'text',
  `display_value` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'manufacturer/distributor',
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `address`, `phone`, `type`, `email`, `contact_person`, `website`, `credit_limit`, `payment_terms`, `is_active`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Auto', 'International Affairs Division ,Paramanu Bhaban ,Bangladesh Atomic Energy Commission,', '+8801818316964', 'manufacturer', 'tasneem.nishat@gmail.com', 'Adrita', NULL, 10000.00, '30', 1, NULL, '2025-11-20 07:44:25', '2025-11-20 07:44:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor_payments`
--

CREATE TABLE `vendor_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` bigint(20) UNSIGNED NOT NULL,
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unallocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_type` enum('purchase_order','advance','refund','adjustment') NOT NULL DEFAULT 'purchase_order',
  `transaction_id` varchar(255) DEFAULT NULL,
  `cheque_number` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_payment_items`
--

CREATE TABLE `vendor_payment_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vendor_payment_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `po_total_at_payment` decimal(15,2) NOT NULL,
  `po_outstanding_before` decimal(15,2) NOT NULL,
  `po_outstanding_after` decimal(15,2) NOT NULL,
  `allocation_type` enum('full','partial','over') NOT NULL DEFAULT 'partial',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `wishlist_name` varchar(100) NOT NULL DEFAULT 'default',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accounts_account_code_unique` (`account_code`),
  ADD KEY `accounts_type_index` (`type`),
  ADD KEY `accounts_sub_type_index` (`sub_type`),
  ADD KEY `accounts_parent_id_index` (`parent_id`),
  ADD KEY `accounts_is_active_index` (`is_active`),
  ADD KEY `accounts_level_index` (`level`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_product_status` (`customer_id`,`product_id`,`status`),
  ADD KEY `carts_product_id_foreign` (`product_id`),
  ADD KEY `carts_customer_id_status_index` (`customer_id`,`status`),
  ADD KEY `carts_customer_id_product_id_status_index` (`customer_id`,`product_id`,`status`);

--
-- Indexes for table `cash_denominations`
--
ALTER TABLE `cash_denominations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_denominations_recorded_by_foreign` (`recorded_by`),
  ADD KEY `cash_denominations_payment_split_id_index` (`payment_split_id`),
  ADD KEY `cash_denominations_order_payment_id_index` (`order_payment_id`),
  ADD KEY `cash_denominations_store_id_index` (`store_id`),
  ADD KEY `cash_denominations_type_index` (`type`),
  ADD KEY `cash_denominations_denomination_value_index` (`denomination_value`),
  ADD KEY `cash_denominations_created_at_index` (`created_at`),
  ADD KEY `cash_denominations_payment_split_id_order_payment_id_index` (`payment_split_id`,`order_payment_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `collections_slug_unique` (`slug`),
  ADD KEY `collections_created_by_foreign` (`created_by`),
  ADD KEY `collections_slug_index` (`slug`),
  ADD KEY `collections_status_index` (`status`),
  ADD KEY `collections_type_index` (`type`);

--
-- Indexes for table `collection_product`
--
ALTER TABLE `collection_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `collection_product_collection_id_product_id_unique` (`collection_id`,`product_id`),
  ADD KEY `collection_product_collection_id_index` (`collection_id`),
  ADD KEY `collection_product_product_id_index` (`product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_customer_code_unique` (`customer_code`),
  ADD KEY `customers_created_by_foreign` (`created_by`),
  ADD KEY `customers_customer_type_status_index` (`customer_type`,`status`),
  ADD KEY `customers_phone_index` (`phone`),
  ADD KEY `customers_email_index` (`email`),
  ADD KEY `customers_assigned_employee_id_index` (`assigned_employee_id`),
  ADD KEY `customers_total_purchases_index` (`total_purchases`),
  ADD KEY `customers_last_purchase_at_index` (`last_purchase_at`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_addresses_customer_id_type_index` (`customer_id`,`type`),
  ADD KEY `customer_addresses_customer_id_is_default_shipping_index` (`customer_id`,`is_default_shipping`),
  ADD KEY `customer_addresses_customer_id_is_default_billing_index` (`customer_id`,`is_default_billing`),
  ADD KEY `customer_addresses_city_state_index` (`city`,`state`);

--
-- Indexes for table `defective_products`
--
ALTER TABLE `defective_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `defective_products_product_id_foreign` (`product_id`),
  ADD KEY `defective_products_product_batch_id_foreign` (`product_batch_id`),
  ADD KEY `defective_products_identified_by_foreign` (`identified_by`),
  ADD KEY `defective_products_inspected_by_foreign` (`inspected_by`),
  ADD KEY `defective_products_sold_by_foreign` (`sold_by`),
  ADD KEY `defective_products_order_id_foreign` (`order_id`),
  ADD KEY `defective_products_vendor_id_foreign` (`vendor_id`),
  ADD KEY `defective_products_product_barcode_id_index` (`product_barcode_id`),
  ADD KEY `defective_products_status_index` (`status`),
  ADD KEY `defective_products_defect_type_index` (`defect_type`),
  ADD KEY `defective_products_store_id_status_index` (`store_id`,`status`),
  ADD KEY `defective_products_source_return_id_foreign` (`source_return_id`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_verification_tokens_token_unique` (`token`),
  ADD KEY `email_verification_tokens_employee_id_index` (`employee_id`),
  ADD KEY `email_verification_tokens_email_index` (`email`),
  ADD KEY `email_verification_tokens_token_index` (`token`),
  ADD KEY `email_verification_tokens_expires_at_index` (`expires_at`),
  ADD KEY `email_verification_tokens_type_index` (`type`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_email_unique` (`email`),
  ADD UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  ADD KEY `employees_email_index` (`email`),
  ADD KEY `employees_store_id_index` (`store_id`),
  ADD KEY `employees_role_id_index` (`role_id`),
  ADD KEY `employees_is_in_service_index` (`is_in_service`),
  ADD KEY `employees_is_active_index` (`is_active`),
  ADD KEY `employees_manager_id_index` (`manager_id`);

--
-- Indexes for table `employee_m_f_a_backup_codes`
--
ALTER TABLE `employee_m_f_a_backup_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_m_f_a_backup_codes_employee_mfa_id_index` (`employee_mfa_id`),
  ADD KEY `employee_m_f_a_backup_codes_code_index` (`code`),
  ADD KEY `employee_m_f_a_backup_codes_is_used_index` (`is_used`);

--
-- Indexes for table `employee_m_f_a_s`
--
ALTER TABLE `employee_m_f_a_s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_m_f_a_s_employee_id_type_unique` (`employee_id`,`type`),
  ADD KEY `employee_m_f_a_s_employee_id_index` (`employee_id`),
  ADD KEY `employee_m_f_a_s_type_index` (`type`),
  ADD KEY `employee_m_f_a_s_is_enabled_index` (`is_enabled`);

--
-- Indexes for table `employee_sessions`
--
ALTER TABLE `employee_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_sessions_token_unique` (`token`),
  ADD KEY `employee_sessions_employee_id_index` (`employee_id`),
  ADD KEY `employee_sessions_token_index` (`token`),
  ADD KEY `employee_sessions_revoked_at_index` (`revoked_at`),
  ADD KEY `employee_sessions_expires_at_index` (`expires_at`),
  ADD KEY `employee_sessions_last_activity_at_index` (`last_activity_at`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenses_expense_number_unique` (`expense_number`),
  ADD KEY `expenses_created_by_foreign` (`created_by`),
  ADD KEY `expenses_approved_by_foreign` (`approved_by`),
  ADD KEY `expenses_processed_by_foreign` (`processed_by`),
  ADD KEY `expenses_parent_expense_id_foreign` (`parent_expense_id`),
  ADD KEY `expenses_expense_number_index` (`expense_number`),
  ADD KEY `expenses_category_id_index` (`category_id`),
  ADD KEY `expenses_vendor_id_index` (`vendor_id`),
  ADD KEY `expenses_employee_id_index` (`employee_id`),
  ADD KEY `expenses_store_id_index` (`store_id`),
  ADD KEY `expenses_status_index` (`status`),
  ADD KEY `expenses_payment_status_index` (`payment_status`),
  ADD KEY `expenses_expense_date_index` (`expense_date`),
  ADD KEY `expenses_due_date_index` (`due_date`),
  ADD KEY `expenses_expense_type_index` (`expense_type`),
  ADD KEY `expenses_is_recurring_index` (`is_recurring`),
  ADD KEY `expenses_created_at_index` (`created_at`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_categories_code_unique` (`code`),
  ADD KEY `expense_categories_code_index` (`code`),
  ADD KEY `expense_categories_type_index` (`type`),
  ADD KEY `expense_categories_parent_id_index` (`parent_id`),
  ADD KEY `expense_categories_is_active_index` (`is_active`),
  ADD KEY `expense_categories_requires_approval_index` (`requires_approval`),
  ADD KEY `expense_categories_sort_order_index` (`sort_order`);

--
-- Indexes for table `expense_payments`
--
ALTER TABLE `expense_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_payments_payment_number_unique` (`payment_number`),
  ADD KEY `expense_payments_processed_by_foreign` (`processed_by`),
  ADD KEY `expense_payments_expense_id_index` (`expense_id`),
  ADD KEY `expense_payments_payment_method_id_index` (`payment_method_id`),
  ADD KEY `expense_payments_store_id_index` (`store_id`),
  ADD KEY `expense_payments_status_index` (`status`),
  ADD KEY `expense_payments_processed_at_index` (`processed_at`),
  ADD KEY `expense_payments_completed_at_index` (`completed_at`),
  ADD KEY `expense_payments_payment_number_index` (`payment_number`);

--
-- Indexes for table `expense_receipts`
--
ALTER TABLE `expense_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_receipts_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `expense_receipts_expense_id_is_primary_index` (`expense_id`,`is_primary`);

--
-- Indexes for table `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fields_type_index` (`type`),
  ADD KEY `fields_is_required_index` (`is_required`),
  ADD KEY `fields_is_active_index` (`is_active`),
  ADD KEY `fields_order_index` (`order`);

--
-- Indexes for table `inventory_rebalancings`
--
ALTER TABLE `inventory_rebalancings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_rebalancings_source_batch_id_foreign` (`source_batch_id`),
  ADD KEY `inventory_rebalancings_requested_by_foreign` (`requested_by`),
  ADD KEY `inventory_rebalancings_approved_by_foreign` (`approved_by`),
  ADD KEY `inventory_rebalancings_completed_by_foreign` (`completed_by`),
  ADD KEY `inventory_rebalancings_product_id_status_index` (`product_id`,`status`),
  ADD KEY `inventory_rebalancings_source_store_id_status_index` (`source_store_id`,`status`),
  ADD KEY `inventory_rebalancings_destination_store_id_status_index` (`destination_store_id`,`status`),
  ADD KEY `inventory_rebalancings_priority_status_index` (`priority`,`status`),
  ADD KEY `inventory_rebalancings_requested_at_index` (`requested_at`),
  ADD KEY `inventory_rebalancings_dispatch_id_foreign` (`dispatch_id`);

--
-- Indexes for table `master_inventories`
--
ALTER TABLE `master_inventories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `master_inventories_product_id_unique` (`product_id`),
  ADD KEY `master_inventories_stock_status_index` (`stock_status`),
  ADD KEY `master_inventories_total_quantity_index` (`total_quantity`),
  ADD KEY `master_inventories_last_updated_at_index` (`last_updated_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notes_employee_id_index` (`employee_id`),
  ADD KEY `notes_created_by_index` (`created_by`),
  ADD KEY `notes_type_index` (`type`),
  ADD KEY `notes_is_private_index` (`is_private`),
  ADD KEY `notes_is_active_index` (`is_active`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_created_by_foreign` (`created_by`),
  ADD KEY `orders_processed_by_foreign` (`processed_by`),
  ADD KEY `orders_shipped_by_foreign` (`shipped_by`),
  ADD KEY `orders_customer_id_status_index` (`customer_id`,`status`),
  ADD KEY `orders_store_id_status_index` (`store_id`,`status`),
  ADD KEY `orders_order_type_status_index` (`order_type`,`status`),
  ADD KEY `orders_order_date_index` (`order_date`),
  ADD KEY `orders_total_amount_index` (`total_amount`),
  ADD KEY `orders_payment_status_index` (`payment_status`),
  ADD KEY `orders_payment_status_next_payment_due_index` (`payment_status`,`next_payment_due`),
  ADD KEY `orders_is_installment_payment_paid_installments_index` (`is_installment_payment`,`paid_installments`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_index` (`order_id`),
  ADD KEY `order_items_product_id_index` (`product_id`),
  ADD KEY `order_items_product_batch_id_index` (`product_batch_id`),
  ADD KEY `order_items_product_barcode_id_index` (`product_barcode_id`);

--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_payments_payment_number_unique` (`payment_number`),
  ADD KEY `order_payments_processed_by_foreign` (`processed_by`),
  ADD KEY `order_payments_order_id_index` (`order_id`),
  ADD KEY `order_payments_payment_method_id_index` (`payment_method_id`),
  ADD KEY `order_payments_customer_id_index` (`customer_id`),
  ADD KEY `order_payments_store_id_index` (`store_id`),
  ADD KEY `order_payments_status_index` (`status`),
  ADD KEY `order_payments_processed_at_index` (`processed_at`),
  ADD KEY `order_payments_completed_at_index` (`completed_at`),
  ADD KEY `order_payments_payment_number_index` (`payment_number`),
  ADD KEY `op_partial_installment_idx` (`is_partial_payment`,`installment_number`),
  ADD KEY `op_type_due_date_idx` (`payment_type`,`payment_due_date`),
  ADD KEY `op_late_payment_idx` (`is_late_payment`,`days_late`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `password_reset_tokens_token_unique` (`token`),
  ADD KEY `password_reset_tokens_employee_id_index` (`employee_id`),
  ADD KEY `password_reset_tokens_email_index` (`email`),
  ADD KEY `password_reset_tokens_token_index` (`token`),
  ADD KEY `password_reset_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_methods_code_unique` (`code`),
  ADD KEY `payment_methods_code_index` (`code`),
  ADD KEY `payment_methods_type_index` (`type`),
  ADD KEY `payment_methods_is_active_index` (`is_active`),
  ADD KEY `payment_methods_sort_order_index` (`sort_order`);

--
-- Indexes for table `payment_splits`
--
ALTER TABLE `payment_splits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_splits_order_payment_id_split_sequence_index` (`order_payment_id`,`split_sequence`),
  ADD KEY `payment_splits_payment_method_id_index` (`payment_method_id`),
  ADD KEY `payment_splits_store_id_index` (`store_id`),
  ADD KEY `payment_splits_status_index` (`status`),
  ADD KEY `payment_splits_completed_at_index` (`completed_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`),
  ADD KEY `permissions_slug_index` (`slug`),
  ADD KEY `permissions_module_index` (`module`),
  ADD KEY `permissions_guard_name_index` (`guard_name`),
  ADD KEY `permissions_is_active_index` (`is_active`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_category_id_index` (`category_id`),
  ADD KEY `products_vendor_id_index` (`vendor_id`),
  ADD KEY `products_sku_index` (`sku`),
  ADD KEY `products_is_archived_index` (`is_archived`);

--
-- Indexes for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_barcodes_barcode_unique` (`barcode`),
  ADD KEY `product_barcodes_product_id_index` (`product_id`),
  ADD KEY `product_barcodes_barcode_index` (`barcode`),
  ADD KEY `product_barcodes_type_index` (`type`),
  ADD KEY `product_barcodes_is_primary_index` (`is_primary`),
  ADD KEY `product_barcodes_is_active_index` (`is_active`),
  ADD KEY `product_barcodes_batch_id_index` (`batch_id`),
  ADD KEY `product_barcodes_current_store_id_index` (`current_store_id`),
  ADD KEY `product_barcodes_current_status_index` (`current_status`),
  ADD KEY `product_barcodes_current_store_id_current_status_index` (`current_store_id`,`current_status`),
  ADD KEY `product_barcodes_location_updated_at_index` (`location_updated_at`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_batches_batch_number_unique` (`batch_number`),
  ADD KEY `product_batches_product_id_index` (`product_id`),
  ADD KEY `product_batches_batch_number_index` (`batch_number`),
  ADD KEY `product_batches_store_id_index` (`store_id`),
  ADD KEY `product_batches_barcode_id_index` (`barcode_id`),
  ADD KEY `product_batches_expiry_date_index` (`expiry_date`),
  ADD KEY `product_batches_availability_index` (`availability`),
  ADD KEY `product_batches_is_active_index` (`is_active`);

--
-- Indexes for table `product_dispatches`
--
ALTER TABLE `product_dispatches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_dispatches_dispatch_number_unique` (`dispatch_number`),
  ADD KEY `product_dispatches_created_by_foreign` (`created_by`),
  ADD KEY `product_dispatches_approved_by_foreign` (`approved_by`),
  ADD KEY `product_dispatches_source_store_id_status_index` (`source_store_id`,`status`),
  ADD KEY `product_dispatches_destination_store_id_status_index` (`destination_store_id`,`status`),
  ADD KEY `product_dispatches_dispatch_date_index` (`dispatch_date`),
  ADD KEY `product_dispatches_expected_delivery_date_index` (`expected_delivery_date`),
  ADD KEY `product_dispatches_customer_id_foreign` (`customer_id`),
  ADD KEY `product_dispatches_order_id_foreign` (`order_id`),
  ADD KEY `product_dispatches_shipment_id_foreign` (`shipment_id`),
  ADD KEY `product_dispatches_for_pathao_delivery_index` (`for_pathao_delivery`),
  ADD KEY `idx_pathao_pending_shipment` (`for_pathao_delivery`,`status`,`shipment_id`);

--
-- Indexes for table `product_dispatch_items`
--
ALTER TABLE `product_dispatch_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_dispatch_items_product_dispatch_id_status_index` (`product_dispatch_id`,`status`),
  ADD KEY `product_dispatch_items_product_batch_id_index` (`product_batch_id`),
  ADD KEY `product_dispatch_items_product_barcode_id_index` (`product_barcode_id`);

--
-- Indexes for table `product_dispatch_item_barcodes`
--
ALTER TABLE `product_dispatch_item_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dispatch_item_barcode_unique` (`product_dispatch_item_id`,`product_barcode_id`),
  ADD KEY `product_dispatch_item_barcodes_product_barcode_id_foreign` (`product_barcode_id`),
  ADD KEY `product_dispatch_item_barcodes_scanned_by_foreign` (`scanned_by`);

--
-- Indexes for table `product_fields`
--
ALTER TABLE `product_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_fields_product_id_field_id_unique` (`product_id`,`field_id`),
  ADD KEY `product_fields_product_id_index` (`product_id`),
  ADD KEY `product_fields_field_id_index` (`field_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_index` (`product_id`),
  ADD KEY `product_images_is_primary_index` (`is_primary`),
  ADD KEY `product_images_is_active_index` (`is_active`),
  ADD KEY `product_images_sort_order_index` (`sort_order`);

--
-- Indexes for table `product_movements`
--
ALTER TABLE `product_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_movements_product_dispatch_id_foreign` (`product_dispatch_id`),
  ADD KEY `product_movements_performed_by_foreign` (`performed_by`),
  ADD KEY `product_movements_product_batch_id_movement_date_index` (`product_batch_id`,`movement_date`),
  ADD KEY `product_movements_product_barcode_id_movement_date_index` (`product_barcode_id`,`movement_date`),
  ADD KEY `product_movements_from_store_id_movement_date_index` (`from_store_id`,`movement_date`),
  ADD KEY `product_movements_to_store_id_movement_date_index` (`to_store_id`,`movement_date`),
  ADD KEY `product_movements_movement_type_movement_date_index` (`movement_type`,`movement_date`),
  ADD KEY `product_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `product_movements_status_before_index` (`status_before`),
  ADD KEY `product_movements_status_after_index` (`status_after`);

--
-- Indexes for table `product_price_overrides`
--
ALTER TABLE `product_price_overrides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_price_overrides_created_by_foreign` (`created_by`),
  ADD KEY `product_price_overrides_approved_by_foreign` (`approved_by`),
  ADD KEY `product_price_overrides_product_id_index` (`product_id`),
  ADD KEY `product_price_overrides_store_id_index` (`store_id`),
  ADD KEY `product_price_overrides_reason_index` (`reason`),
  ADD KEY `product_price_overrides_starts_at_index` (`starts_at`),
  ADD KEY `product_price_overrides_ends_at_index` (`ends_at`),
  ADD KEY `product_price_overrides_is_active_index` (`is_active`);

--
-- Indexes for table `product_returns`
--
ALTER TABLE `product_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_returns_return_number_unique` (`return_number`),
  ADD KEY `product_returns_created_by_foreign` (`created_by`),
  ADD KEY `product_returns_approved_by_foreign` (`approved_by`),
  ADD KEY `product_returns_processed_by_foreign` (`processed_by`),
  ADD KEY `product_returns_rejected_by_foreign` (`rejected_by`),
  ADD KEY `product_returns_order_id_index` (`order_id`),
  ADD KEY `product_returns_customer_id_index` (`customer_id`),
  ADD KEY `product_returns_store_id_index` (`store_id`),
  ADD KEY `product_returns_status_index` (`status`),
  ADD KEY `product_returns_return_date_index` (`return_date`),
  ADD KEY `product_returns_approved_date_index` (`approved_date`),
  ADD KEY `product_returns_return_reason_index` (`return_reason`),
  ADD KEY `product_returns_return_type_index` (`return_type`),
  ADD KEY `product_returns_received_at_store_id_foreign` (`received_at_store_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variants_sku_unique` (`sku`),
  ADD UNIQUE KEY `product_variants_barcode_unique` (`barcode`),
  ADD KEY `product_variants_product_id_index` (`product_id`),
  ADD KEY `product_variants_sku_index` (`sku`),
  ADD KEY `product_variants_barcode_index` (`barcode`),
  ADD KEY `product_variants_is_active_index` (`is_active`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotions_code_unique` (`code`),
  ADD KEY `promotions_created_by_foreign` (`created_by`),
  ADD KEY `promotions_code_index` (`code`),
  ADD KEY `promotions_start_date_index` (`start_date`),
  ADD KEY `promotions_end_date_index` (`end_date`),
  ADD KEY `promotions_is_active_index` (`is_active`);

--
-- Indexes for table `promotion_usages`
--
ALTER TABLE `promotion_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_usages_order_id_foreign` (`order_id`),
  ADD KEY `promotion_usages_promotion_id_index` (`promotion_id`),
  ADD KEY `promotion_usages_customer_id_index` (`customer_id`),
  ADD KEY `promotion_usages_used_at_index` (`used_at`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  ADD KEY `purchase_orders_approved_by_foreign` (`approved_by`),
  ADD KEY `purchase_orders_received_by_foreign` (`received_by`),
  ADD KEY `purchase_orders_po_number_index` (`po_number`),
  ADD KEY `purchase_orders_vendor_id_index` (`vendor_id`),
  ADD KEY `purchase_orders_store_id_index` (`store_id`),
  ADD KEY `purchase_orders_status_index` (`status`),
  ADD KEY `purchase_orders_payment_status_index` (`payment_status`),
  ADD KEY `purchase_orders_order_date_index` (`order_date`),
  ADD KEY `purchase_orders_expected_delivery_date_index` (`expected_delivery_date`),
  ADD KEY `purchase_orders_payment_due_date_index` (`payment_due_date`),
  ADD KEY `purchase_orders_created_by_index` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_purchase_order_id_index` (`purchase_order_id`),
  ADD KEY `purchase_order_items_product_id_index` (`product_id`),
  ADD KEY `purchase_order_items_product_batch_id_index` (`product_batch_id`),
  ADD KEY `purchase_order_items_batch_number_index` (`batch_number`),
  ADD KEY `purchase_order_items_receive_status_index` (`receive_status`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `refunds_refund_number_unique` (`refund_number`),
  ADD KEY `refunds_processed_by_foreign` (`processed_by`),
  ADD KEY `refunds_approved_by_foreign` (`approved_by`),
  ADD KEY `refunds_return_id_index` (`return_id`),
  ADD KEY `refunds_order_id_index` (`order_id`),
  ADD KEY `refunds_customer_id_index` (`customer_id`),
  ADD KEY `refunds_status_index` (`status`),
  ADD KEY `refunds_refund_method_index` (`refund_method`),
  ADD KEY `refunds_refund_type_index` (`refund_type`),
  ADD KEY `refunds_processed_at_index` (`processed_at`),
  ADD KEY `refunds_completed_at_index` (`completed_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`),
  ADD KEY `roles_slug_index` (`slug`),
  ADD KEY `roles_guard_name_index` (`guard_name`),
  ADD KEY `roles_level_index` (`level`),
  ADD KEY `roles_is_active_index` (`is_active`),
  ADD KEY `roles_is_default_index` (`is_default`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  ADD KEY `role_permissions_role_id_index` (`role_id`),
  ADD KEY `role_permissions_permission_id_index` (`permission_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `services_service_code_unique` (`service_code`),
  ADD KEY `services_service_code_index` (`service_code`),
  ADD KEY `services_category_index` (`category`),
  ADD KEY `services_is_active_index` (`is_active`),
  ADD KEY `services_is_featured_index` (`is_featured`),
  ADD KEY `services_sort_order_index` (`sort_order`);

--
-- Indexes for table `service_fields`
--
ALTER TABLE `service_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_fields_service_id_field_id_unique` (`service_id`,`field_id`),
  ADD KEY `service_fields_service_id_index` (`service_id`),
  ADD KEY `service_fields_field_id_index` (`field_id`);

--
-- Indexes for table `service_orders`
--
ALTER TABLE `service_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_orders_service_order_number_unique` (`service_order_number`),
  ADD KEY `service_orders_created_by_foreign` (`created_by`),
  ADD KEY `service_orders_customer_id_index` (`customer_id`),
  ADD KEY `service_orders_store_id_index` (`store_id`),
  ADD KEY `service_orders_assigned_to_index` (`assigned_to`),
  ADD KEY `service_orders_status_index` (`status`),
  ADD KEY `service_orders_payment_status_index` (`payment_status`),
  ADD KEY `service_orders_scheduled_date_index` (`scheduled_date`),
  ADD KEY `service_orders_service_order_number_index` (`service_order_number`),
  ADD KEY `service_orders_created_at_index` (`created_at`),
  ADD KEY `service_orders_payment_status_next_payment_due_index` (`payment_status`,`next_payment_due`),
  ADD KEY `service_orders_is_installment_payment_paid_installments_index` (`is_installment_payment`,`paid_installments`);

--
-- Indexes for table `service_order_items`
--
ALTER TABLE `service_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_order_items_service_order_id_index` (`service_order_id`),
  ADD KEY `service_order_items_service_id_index` (`service_id`),
  ADD KEY `service_order_items_service_field_id_index` (`service_field_id`),
  ADD KEY `service_order_items_status_index` (`status`),
  ADD KEY `service_order_items_scheduled_date_index` (`scheduled_date`);

--
-- Indexes for table `service_order_payments`
--
ALTER TABLE `service_order_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_order_payments_payment_number_unique` (`payment_number`),
  ADD KEY `service_order_payments_processed_by_foreign` (`processed_by`),
  ADD KEY `service_order_payments_service_order_id_index` (`service_order_id`),
  ADD KEY `service_order_payments_payment_method_id_index` (`payment_method_id`),
  ADD KEY `service_order_payments_customer_id_index` (`customer_id`),
  ADD KEY `service_order_payments_store_id_index` (`store_id`),
  ADD KEY `service_order_payments_status_index` (`status`),
  ADD KEY `service_order_payments_processed_at_index` (`processed_at`),
  ADD KEY `service_order_payments_completed_at_index` (`completed_at`),
  ADD KEY `service_order_payments_payment_number_index` (`payment_number`),
  ADD KEY `sop_partial_installment_idx` (`is_partial_payment`,`installment_number`),
  ADD KEY `sop_type_due_date_idx` (`payment_type`,`payment_due_date`),
  ADD KEY `sop_late_payment_idx` (`is_late_payment`,`days_late`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipments_shipment_number_unique` (`shipment_number`),
  ADD KEY `shipments_created_by_foreign` (`created_by`),
  ADD KEY `shipments_processed_by_foreign` (`processed_by`),
  ADD KEY `shipments_delivered_by_foreign` (`delivered_by`),
  ADD KEY `shipments_order_id_index` (`order_id`),
  ADD KEY `shipments_customer_id_index` (`customer_id`),
  ADD KEY `shipments_store_id_index` (`store_id`),
  ADD KEY `shipments_status_index` (`status`),
  ADD KEY `shipments_pathao_status_index` (`pathao_status`),
  ADD KEY `shipments_delivery_type_index` (`delivery_type`),
  ADD KEY `shipments_estimated_delivery_date_index` (`estimated_delivery_date`),
  ADD KEY `shipments_created_at_index` (`created_at`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stores_store_code_unique` (`store_code`),
  ADD KEY `stores_is_warehouse_index` (`is_warehouse`),
  ADD KEY `stores_is_online_index` (`is_online`),
  ADD KEY `stores_is_active_index` (`is_active`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_transaction_number_unique` (`transaction_number`),
  ADD KEY `transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `transactions_account_id_index` (`account_id`),
  ADD KEY `transactions_store_id_index` (`store_id`),
  ADD KEY `transactions_transaction_date_index` (`transaction_date`),
  ADD KEY `transactions_type_index` (`type`),
  ADD KEY `transactions_status_index` (`status`),
  ADD KEY `transactions_created_by_foreign` (`created_by`);

--
-- Indexes for table `variant_options`
--
ALTER TABLE `variant_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_options_name_value_index` (`name`,`value`),
  ADD KEY `variant_options_is_active_index` (`is_active`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendors_name_index` (`name`),
  ADD KEY `vendors_email_index` (`email`),
  ADD KEY `vendors_is_active_index` (`is_active`);

--
-- Indexes for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_payments_payment_number_unique` (`payment_number`),
  ADD KEY `vendor_payments_vendor_id_index` (`vendor_id`),
  ADD KEY `vendor_payments_payment_method_id_index` (`payment_method_id`),
  ADD KEY `vendor_payments_account_id_index` (`account_id`),
  ADD KEY `vendor_payments_employee_id_index` (`employee_id`),
  ADD KEY `vendor_payments_payment_date_index` (`payment_date`),
  ADD KEY `vendor_payments_status_index` (`status`),
  ADD KEY `vendor_payments_payment_type_index` (`payment_type`),
  ADD KEY `vendor_payments_payment_number_index` (`payment_number`);

--
-- Indexes for table `vendor_payment_items`
--
ALTER TABLE `vendor_payment_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_payment_items_vendor_payment_id_purchase_order_id_unique` (`vendor_payment_id`,`purchase_order_id`),
  ADD KEY `vendor_payment_items_vendor_payment_id_index` (`vendor_payment_id`),
  ADD KEY `vendor_payment_items_purchase_order_id_index` (`purchase_order_id`),
  ADD KEY `vendor_payment_items_allocation_type_index` (`allocation_type`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_product_wishlist` (`customer_id`,`product_id`,`wishlist_name`),
  ADD KEY `wishlists_product_id_foreign` (`product_id`),
  ADD KEY `wishlists_customer_id_wishlist_name_index` (`customer_id`,`wishlist_name`),
  ADD KEY `wishlists_customer_id_product_id_index` (`customer_id`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_denominations`
--
ALTER TABLE `cash_denominations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collection_product`
--
ALTER TABLE `collection_product`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `defective_products`
--
ALTER TABLE `defective_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_m_f_a_backup_codes`
--
ALTER TABLE `employee_m_f_a_backup_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_m_f_a_s`
--
ALTER TABLE `employee_m_f_a_s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_sessions`
--
ALTER TABLE `employee_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `expense_payments`
--
ALTER TABLE `expense_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_receipts`
--
ALTER TABLE `expense_receipts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fields`
--
ALTER TABLE `fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_rebalancings`
--
ALTER TABLE `inventory_rebalancings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_inventories`
--
ALTER TABLE `master_inventories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_splits`
--
ALTER TABLE `payment_splits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_dispatches`
--
ALTER TABLE `product_dispatches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_dispatch_items`
--
ALTER TABLE `product_dispatch_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_dispatch_item_barcodes`
--
ALTER TABLE `product_dispatch_item_barcodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_fields`
--
ALTER TABLE `product_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_movements`
--
ALTER TABLE `product_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_price_overrides`
--
ALTER TABLE `product_price_overrides`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_returns`
--
ALTER TABLE `product_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_usages`
--
ALTER TABLE `promotion_usages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=460;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_fields`
--
ALTER TABLE `service_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_orders`
--
ALTER TABLE `service_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_order_items`
--
ALTER TABLE `service_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_order_payments`
--
ALTER TABLE `service_order_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `variant_options`
--
ALTER TABLE `variant_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_payment_items`
--
ALTER TABLE `vendor_payment_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cash_denominations`
--
ALTER TABLE `cash_denominations`
  ADD CONSTRAINT `cash_denominations_order_payment_id_foreign` FOREIGN KEY (`order_payment_id`) REFERENCES `order_payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_denominations_payment_split_id_foreign` FOREIGN KEY (`payment_split_id`) REFERENCES `payment_splits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_denominations_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cash_denominations_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `collections`
--
ALTER TABLE `collections`
  ADD CONSTRAINT `collections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `collection_product`
--
ALTER TABLE `collection_product`
  ADD CONSTRAINT `collection_product_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `collection_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `defective_products`
--
ALTER TABLE `defective_products`
  ADD CONSTRAINT `defective_products_identified_by_foreign` FOREIGN KEY (`identified_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_inspected_by_foreign` FOREIGN KEY (`inspected_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_product_barcode_id_foreign` FOREIGN KEY (`product_barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defective_products_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defective_products_sold_by_foreign` FOREIGN KEY (`sold_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_source_return_id_foreign` FOREIGN KEY (`source_return_id`) REFERENCES `product_returns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `defective_products_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defective_products_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `email_verification_tokens_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_m_f_a_backup_codes`
--
ALTER TABLE `employee_m_f_a_backup_codes`
  ADD CONSTRAINT `employee_m_f_a_backup_codes_employee_mfa_id_foreign` FOREIGN KEY (`employee_mfa_id`) REFERENCES `employee_m_f_a_s` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_m_f_a_s`
--
ALTER TABLE `employee_m_f_a_s`
  ADD CONSTRAINT `employee_m_f_a_s_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_sessions`
--
ALTER TABLE `employee_sessions`
  ADD CONSTRAINT `employee_sessions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_parent_expense_id_foreign` FOREIGN KEY (`parent_expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD CONSTRAINT `expense_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_payments`
--
ALTER TABLE `expense_payments`
  ADD CONSTRAINT `expense_payments_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `expense_payments_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_payments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_receipts`
--
ALTER TABLE `expense_receipts`
  ADD CONSTRAINT `expense_receipts_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_receipts_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_rebalancings`
--
ALTER TABLE `inventory_rebalancings`
  ADD CONSTRAINT `inventory_rebalancings_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_rebalancings_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_rebalancings_destination_store_id_foreign` FOREIGN KEY (`destination_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_rebalancings_dispatch_id_foreign` FOREIGN KEY (`dispatch_id`) REFERENCES `product_dispatches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_rebalancings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_rebalancings_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_rebalancings_source_batch_id_foreign` FOREIGN KEY (`source_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_rebalancings_source_store_id_foreign` FOREIGN KEY (`source_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `master_inventories`
--
ALTER TABLE `master_inventories`
  ADD CONSTRAINT `master_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_shipped_by_foreign` FOREIGN KEY (`shipped_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_barcode_id_foreign` FOREIGN KEY (`product_barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD CONSTRAINT `order_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `order_payments_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_payments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_splits`
--
ALTER TABLE `payment_splits`
  ADD CONSTRAINT `payment_splits_order_payment_id_foreign` FOREIGN KEY (`order_payment_id`) REFERENCES `order_payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_splits_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `payment_splits_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD CONSTRAINT `product_barcodes_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_barcodes_current_store_id_foreign` FOREIGN KEY (`current_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_barcodes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_barcode_id_foreign` FOREIGN KEY (`barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_batches_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_dispatches`
--
ALTER TABLE `product_dispatches`
  ADD CONSTRAINT `product_dispatches_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_dispatches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_dispatches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_dispatches_destination_store_id_foreign` FOREIGN KEY (`destination_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_dispatches_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_dispatches_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_dispatches_source_store_id_foreign` FOREIGN KEY (`source_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_dispatch_items`
--
ALTER TABLE `product_dispatch_items`
  ADD CONSTRAINT `product_dispatch_items_product_barcode_id_foreign` FOREIGN KEY (`product_barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_dispatch_items_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_dispatch_items_product_dispatch_id_foreign` FOREIGN KEY (`product_dispatch_id`) REFERENCES `product_dispatches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_dispatch_item_barcodes`
--
ALTER TABLE `product_dispatch_item_barcodes`
  ADD CONSTRAINT `product_dispatch_item_barcodes_product_barcode_id_foreign` FOREIGN KEY (`product_barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_dispatch_item_barcodes_product_dispatch_item_id_foreign` FOREIGN KEY (`product_dispatch_item_id`) REFERENCES `product_dispatch_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_dispatch_item_barcodes_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_fields`
--
ALTER TABLE `product_fields`
  ADD CONSTRAINT `product_fields_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_fields_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_movements`
--
ALTER TABLE `product_movements`
  ADD CONSTRAINT `product_movements_from_store_id_foreign` FOREIGN KEY (`from_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_movements_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_movements_product_barcode_id_foreign` FOREIGN KEY (`product_barcode_id`) REFERENCES `product_barcodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_movements_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_movements_product_dispatch_id_foreign` FOREIGN KEY (`product_dispatch_id`) REFERENCES `product_dispatches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_movements_to_store_id_foreign` FOREIGN KEY (`to_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_price_overrides`
--
ALTER TABLE `product_price_overrides`
  ADD CONSTRAINT `product_price_overrides_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_price_overrides_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_price_overrides_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_price_overrides_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_returns`
--
ALTER TABLE `product_returns`
  ADD CONSTRAINT `product_returns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_returns_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_returns_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_returns_received_at_store_id_foreign` FOREIGN KEY (`received_at_store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_returns_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_returns_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_usages`
--
ALTER TABLE `promotion_usages`
  ADD CONSTRAINT `promotion_usages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `promotion_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `promotion_usages_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `purchase_orders_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  ADD CONSTRAINT `purchase_orders_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_product_batch_id_foreign` FOREIGN KEY (`product_batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `refunds_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `refunds_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `product_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_fields`
--
ALTER TABLE `service_fields`
  ADD CONSTRAINT `service_fields_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_fields_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_orders`
--
ALTER TABLE `service_orders`
  ADD CONSTRAINT `service_orders_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_order_items`
--
ALTER TABLE `service_order_items`
  ADD CONSTRAINT `service_order_items_service_field_id_foreign` FOREIGN KEY (`service_field_id`) REFERENCES `service_fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_order_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `service_order_items_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_order_payments`
--
ALTER TABLE `service_order_payments`
  ADD CONSTRAINT `service_order_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_order_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `service_order_payments_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_order_payments_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_order_payments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipments_delivered_by_foreign` FOREIGN KEY (`delivered_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipments_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  ADD CONSTRAINT `vendor_payments_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_payments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `vendor_payments_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `vendor_payment_items`
--
ALTER TABLE `vendor_payment_items`
  ADD CONSTRAINT `vendor_payment_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `vendor_payment_items_vendor_payment_id_foreign` FOREIGN KEY (`vendor_payment_id`) REFERENCES `vendor_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
