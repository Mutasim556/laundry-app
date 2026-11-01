-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 01, 2026 at 08:28 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laundry_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `avatar`, `email_verified_at`, `password`, `is_blocked`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@laundry.com', 'avatars/admins/Xpya59trIkzzSm2agYN5t6NzhwcornAhz7eMNYIJ.jpg', '2026-11-01 07:49:18', '$2y$12$uT5mGIeMuB6igM7WuesFAepoZzbOtYvkjOFlIPRlxVnBTMHMMI8DO', 0, NULL, '2026-11-01 05:46:45', '2026-11-01 07:49:18'),
(2, 'Test', 'test@laundry.com', 'avatars/admins/TK5BwW6qL5Ebu8VLPxPxVuV4EHtKo8baZpRWqxew.png', NULL, '$2y$12$XlXhqOrZ9cHzmEM53hdw7.P9.m4A5wkqxrW/UkbhkNFcT3GOvPAoW', 0, NULL, '2026-11-01 13:59:25', '2026-11-01 14:07:07'),
(3, 'Alta Kerluke', 'hmarks@example.net', NULL, '2026-11-01 14:10:26', '$2y$12$Qc8swoY07Scf1XQz6k3EseJCekPn9vfwkI2yGiAOH4CYdjukCwH8O', 0, 'w1eGtHAlYp', '2026-11-01 14:10:26', '2026-11-01 14:10:26');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:5:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"d\";s:10:\"group_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:37:{i:0;a:5:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"view-admins\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:17:\"Admins Management\";s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"create-admins\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:17:\"Admins Management\";}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:11:\"edit-admins\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:17:\"Admins Management\";}i:3;a:5:{s:1:\"a\";i:4;s:1:\"b\";s:13:\"delete-admins\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:17:\"Admins Management\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:10:\"view-roles\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:19:\"Roles & Permissions\";}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"create-roles\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:19:\"Roles & Permissions\";}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:10:\"edit-roles\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:19:\"Roles & Permissions\";}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"delete-roles\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:19:\"Roles & Permissions\";}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:11:\"view-orders\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:6:\"Orders\";}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:13:\"create-orders\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:6:\"Orders\";}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:11:\"edit-orders\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:6:\"Orders\";}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:13:\"delete-orders\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:6:\"Orders\";}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:19:\"update-order-status\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:6:\"Orders\";}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:13:\"view-services\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Services & Pricing\";}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"create-services\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Services & Pricing\";}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:13:\"edit-services\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Services & Pricing\";}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:15:\"delete-services\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Services & Pricing\";}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:14:\"view-customers\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:9:\"Customers\";}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:14:\"edit-customers\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:9:\"Customers\";}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:16:\"delete-customers\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:9:\"Customers\";}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:12:\"view-reports\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Reports & Settings\";}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"export-reports\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Reports & Settings\";}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:15:\"manage-settings\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:18:\"Reports & Settings\";}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:12:\"create-order\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:16:\"Orders & Booking\";}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:18:\"view-order-history\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:16:\"Orders & Booking\";}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:12:\"cancel-order\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:16:\"Orders & Booking\";}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:11:\"track-order\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:16:\"Orders & Booking\";}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:16:\"manage-addresses\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:19:\"Profile & Addresses\";}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:14:\"update-profile\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:19:\"Profile & Addresses\";}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:11:\"view-wallet\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:17:\"Wallet & Payments\";}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:9:\"add-funds\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:17:\"Wallet & Payments\";}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:17:\"view-transactions\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:17:\"Wallet & Payments\";}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:13:\"submit-review\";s:1:\"c\";s:3:\"web\";s:1:\"d\";s:18:\"Reviews & Feedback\";}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:10:\"view-users\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:16:\"Users Management\";}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:12:\"create-users\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:16:\"Users Management\";}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:10:\"edit-users\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:16:\"Users Management\";}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:12:\"delete-users\";s:1:\"c\";s:5:\"admin\";s:1:\"d\";s:16:\"Users Management\";}}s:5:\"roles\";a:1:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:5:\"admin\";}}}', 1793650662);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_11_01_110238_create_permission_tables', 2),
(5, '2026_11_01_114330_create_admins_table', 3),
(6, '2026_11_01_132801_add_avatar_to_admins_table', 4),
(7, '2026_11_01_152101_add_avatar_to_users_table', 5),
(8, '2026_11_01_195707_add_status_to_admins_and_users_tables', 6);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(4, 'App\\Models\\User', 1),
(1, 'Modules\\Admin\\Models\\Admin', 1),
(2, 'Modules\\Admin\\Models\\Admin', 2),
(1, 'Modules\\Admin\\Models\\Admin', 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `group_name`, `created_at`, `updated_at`) VALUES
(1, 'view-admins', 'admin', 'Admins Management', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(2, 'create-admins', 'admin', 'Admins Management', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(3, 'edit-admins', 'admin', 'Admins Management', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(4, 'delete-admins', 'admin', 'Admins Management', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(5, 'view-roles', 'admin', 'Roles & Permissions', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(6, 'create-roles', 'admin', 'Roles & Permissions', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(7, 'edit-roles', 'admin', 'Roles & Permissions', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(8, 'delete-roles', 'admin', 'Roles & Permissions', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(9, 'view-orders', 'admin', 'Orders', '2026-11-01 07:54:57', '2026-11-01 08:15:13'),
(10, 'create-orders', 'admin', 'Orders', '2026-11-01 07:54:57', '2026-11-01 08:15:14'),
(11, 'edit-orders', 'admin', 'Orders', '2026-11-01 07:54:57', '2026-11-01 08:15:14'),
(12, 'delete-orders', 'admin', 'Orders', '2026-11-01 07:54:57', '2026-11-01 08:15:14'),
(13, 'update-order-status', 'admin', 'Orders', '2026-11-01 07:54:57', '2026-11-01 08:15:14'),
(14, 'view-services', 'admin', 'Services & Pricing', '2026-11-01 07:54:57', '2026-11-01 08:15:14'),
(15, 'create-services', 'admin', 'Services & Pricing', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(16, 'edit-services', 'admin', 'Services & Pricing', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(17, 'delete-services', 'admin', 'Services & Pricing', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(18, 'view-customers', 'admin', 'Customers', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(19, 'edit-customers', 'admin', 'Customers', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(20, 'delete-customers', 'admin', 'Customers', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(21, 'view-reports', 'admin', 'Reports & Settings', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(22, 'export-reports', 'admin', 'Reports & Settings', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(23, 'manage-settings', 'admin', 'Reports & Settings', '2026-11-01 07:54:58', '2026-11-01 08:15:14'),
(24, 'create-order', 'web', 'Orders & Booking', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(25, 'view-order-history', 'web', 'Orders & Booking', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(26, 'cancel-order', 'web', 'Orders & Booking', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(27, 'track-order', 'web', 'Orders & Booking', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(28, 'manage-addresses', 'web', 'Profile & Addresses', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(29, 'update-profile', 'web', 'Profile & Addresses', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(30, 'view-wallet', 'web', 'Wallet & Payments', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(31, 'add-funds', 'web', 'Wallet & Payments', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(32, 'view-transactions', 'web', 'Wallet & Payments', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(33, 'submit-review', 'web', 'Reviews & Feedback', '2026-11-01 13:53:20', '2026-11-01 13:53:20'),
(34, 'view-users', 'admin', 'Users Management', '2026-11-01 14:08:53', '2026-11-01 14:08:53'),
(35, 'create-users', 'admin', 'Users Management', '2026-11-01 14:08:53', '2026-11-01 14:08:53'),
(36, 'edit-users', 'admin', 'Users Management', '2026-11-01 14:08:53', '2026-11-01 14:08:53'),
(37, 'delete-users', 'admin', 'Users Management', '2026-11-01 14:08:53', '2026-11-01 14:08:53');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'admin', '2026-11-01 07:49:18', '2026-11-01 07:49:18'),
(2, 'admin', 'admin', '2026-11-01 08:12:32', '2026-11-01 08:12:32'),
(3, 'editor', 'admin', '2026-11-01 08:22:09', '2026-11-01 08:24:54'),
(4, 'user', 'web', '2026-11-01 11:09:45', '2026-11-01 11:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 2),
(4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('b8Qc63808xlXKwTICvwYO1FAlbtfWi4WFFTNVUg8', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJZSGpqTWdDUW5tSmN3VUd2b0sxYXk5WU9MeDR4U1dnUkd2RGRCSXg3IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbGF1bmRyeS1hcHAudGVzdFwvYWRtaW5cL3JvbGVzIiwicm91dGUiOiJhZG1pbi5yb2xlcy5pbmRleCJ9LCJsb2dpbl9hZG1pbl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX2FkbWluIjoiZGNmOTc4ODllZDRjNTIyZmUwOWEyYjlmNWFhODhlMTBlZDI1MGNiZjczMWZlMWNjNGQ4ZmYzMTg4YzRlMGU5ZCJ9', 1793564262),
('RfZoITgbPZcigweQgbDZyQ4tEyUHUG6GE2BRT5wq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJSRUxLSUg1WXdicWJYaFh5WGw4VWJiaGM1bDE5aXNoREdGeEZxa05lIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xhdW5kcnktYXBwLnRlc3QiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1793562177),
('vq8A8b7uwT6BouZXy0tk0g9hMMKarPBhAAUUkq5L', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiI4Z3RIaFcwdGcwc21BUnNzN2tmVk5OS05abjN3MFhaUFViMkRmVGc5IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xhdW5kcnktYXBwLnRlc3RcL2FkbWluXC9hZG1pbnMiLCJyb3V0ZSI6ImFkbWluLmFkbWlucy5pbmRleCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX2FkbWluXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsInBhc3N3b3JkX2hhc2hfYWRtaW4iOiI2NTc5ZTI1OGZmZTcyNzYxZjg3MTQ3YTZlODNmNjdjZjlhZGUzZmFiODdiYWUwMmEwMWE5OTkwMjE4MzI5MTg1In0=', 1793564350),
('ZyR3fOPn63uP4OnlZnGSk2al79Rblw4tN1cOY9x5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ2STlzUGE3SEpObExoN0ljWVF5c1pqMFE1Q29IRUQ3eVdkRkZSNm5JIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvbGF1bmRyeS1hcHAudGVzdFwvYWRtaW5cL2FkbWlucyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbGF1bmRyeS1hcHAudGVzdFwvYWRtaW5cL2FkbWlucyIsInJvdXRlIjoiYWRtaW4uYWRtaW5zLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1793562176);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar`, `email_verified_at`, `password`, `is_blocked`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Test', 'mutasimstore@gmail.com', 'avatars/users/sMTB3gGyHqmIp6qoUt386T1t1gS5ZXZYOoBIHf1S.jpg', NULL, '$2y$12$aeq9xRaxS06F5aAiKUecEuQjQBXhqYTGsFknpLQwa3VVIb6QEDge6', 0, NULL, '2026-11-01 11:09:08', '2026-11-01 14:06:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
