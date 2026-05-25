-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 11:11 PM
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
-- Database: `shopeasy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
-- CREATE DATABASE USER FOR LECTURER TESTING
CREATE USER IF NOT EXISTS 'shopeasy_app'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON shopeasy_db.* TO 'shopeasy_app'@'localhost';
FLUSH PRIVILEGES;

-- Ensure the database exists and use it
CREATE DATABASE IF NOT EXISTS shopeasy_db;
USE shopeasy_db;

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action_type`, `action_description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LIMITED_USER_TEST', 'Limited database user insert permission tested successfully', '127.0.0.1', '2026-05-18 15:31:01'),
(2, 1, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 1', '::1', '2026-05-21 20:28:49'),
(3, 1, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 2', '::1', '2026-05-21 20:29:40'),
(4, 1, 'ACCOUNT_LOCKED', 'Account locked after 3 failed login attempts', '::1', '2026-05-21 20:30:48'),
(5, 1, 'LOCKED_LOGIN_ATTEMPT', 'Locked account attempted to log in', '::1', '2026-05-21 20:31:04'),
(6, 2, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 1', '::1', '2026-05-21 20:32:03'),
(7, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-21 20:37:01'),
(8, 3, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 1', '::1', '2026-05-24 16:09:29'),
(9, 3, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 2', '::1', '2026-05-24 16:09:33'),
(10, 1, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 1', '::1', '2026-05-24 16:11:55'),
(11, 3, 'ACCOUNT_LOCKED', 'Account locked after 3 failed login attempts', '::1', '2026-05-24 16:11:56'),
(12, 1, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 2', '::1', '2026-05-24 16:12:18'),
(13, 3, 'LOCKED_LOGIN_ATTEMPT', 'Locked account attempted to log in', '::1', '2026-05-24 16:13:26'),
(14, 11, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:13:59'),
(15, 11, 'ORDER_PLACED', 'Customer placed order ID 5 with total amount RM 210.90', '::1', '2026-05-24 16:14:39'),
(16, 1, 'ACCOUNT_LOCKED', 'Account locked after 3 failed login attempts', '::1', '2026-05-24 16:15:10'),
(17, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:17:41'),
(18, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:17:56'),
(19, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:18:01'),
(20, 11, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:18:30'),
(21, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:18:37'),
(22, 1, 'ACCOUNT_LOCKED', 'Account locked after 3 failed login attempts', '::1', '2026-05-24 16:19:11'),
(23, 12, 'LOGIN_FAILED', 'Failed login attempt. Attempt count: 1', '::1', '2026-05-24 16:19:15'),
(24, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:19:23'),
(25, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:26:13'),
(26, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:41:54'),
(27, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:42:21'),
(28, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:42:33'),
(29, 12, 'DRIVER_ASSIGNMENT', 'Admin assigned driver ID 13 to order ID 1', '::1', '2026-05-24 16:42:41'),
(30, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 16:42:46'),
(31, 13, 'DELIVERY_STATUS_UPDATE', 'Driver started delivery ID 3', '::1', '2026-05-24 16:42:47'),
(32, 13, 'DELIVERY_UPDATE_FAILED', 'Driver attempted to update delivery ID 3 but it was not assigned or already updated', '::1', '2026-05-24 16:42:48'),
(33, 13, 'DELIVERY_UPDATE_FAILED', 'Driver attempted to update delivery ID 3 but it was not assigned or already updated', '::1', '2026-05-24 16:42:49'),
(34, 13, 'DELIVERY_UPDATE_FAILED', 'Driver attempted to update delivery ID 3 but it was not assigned or already updated', '::1', '2026-05-24 16:42:50'),
(35, 13, 'DELIVERY_UPDATE_FAILED', 'Driver attempted to update delivery ID 3 but it was not assigned or already updated', '::1', '2026-05-24 16:42:50'),
(36, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:18:53'),
(37, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:23:05'),
(38, 11, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:25:24'),
(39, 11, 'ORDER_PLACED', 'Customer placed order ID 6 with total amount RM 210.90', '::1', '2026-05-24 18:25:53'),
(40, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:26:04'),
(41, 12, 'DRIVER_ASSIGNMENT', 'Admin assigned driver ID 13 to order ID 5', '::1', '2026-05-24 18:26:12'),
(42, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:26:35'),
(43, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:29:12'),
(44, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:34:39'),
(45, 11, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:35:34'),
(46, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:53:44'),
(47, 12, 'DRIVER_ASSIGNMENT', 'Admin assigned driver ID 13 to order ID 6', '::1', '2026-05-24 18:53:48'),
(48, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 18:53:55'),
(49, 13, 'ROUTE_STARTED', 'Driver started route for Delivery #5', '::1', '2026-05-24 18:54:00'),
(50, 13, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 19:10:10'),
(51, 11, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 19:18:34'),
(52, 11, 'ORDER_PLACED', 'Customer placed order ID 7 with total amount RM 210.90', '::1', '2026-05-24 19:21:53'),
(53, 11, 'LOGOUT_SUCCESS', 'User logged out and terminated session securely', '::1', '2026-05-24 19:22:07'),
(54, 12, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-05-24 19:22:11'),
(55, 12, 'LOGOUT_SUCCESS', 'User logged out and terminated session securely', '::1', '2026-05-24 19:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `delivery_status` enum('assigned','out_for_delivery','delivered') DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `order_id`, `driver_id`, `location_id`, `delivery_status`, `assigned_at`, `delivered_at`) VALUES
(3, 1, 13, 1, 'delivered', '2026-05-24 16:42:41', NULL),
(4, 5, 13, 2, 'delivered', '2026-05-24 18:26:12', NULL),
(5, 6, 13, 2, 'assigned', '2026-05-24 18:53:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `product_id` int(11) NOT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`product_id`, `quantity_available`, `last_updated`) VALUES
(1, 50, '2026-05-07 15:44:52'),
(2, 22, '2026-05-24 19:21:53'),
(3, 37, '2026-05-24 19:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`location_id`, `user_id`, `address_line`, `city`, `postcode`, `is_default`) VALUES
(1, 2, '123 Jalan Bukit', 'Kuala Lumpur', '50000', 1),
(2, 11, 'Jalan Ampang', 'Kuala Lumpur', '50000', 1),
(3, 12, 'Not Specified Yet', 'Kuala Lumpur', '50000', 1),
(4, 13, 'Not Specified Yet', 'Kuala Lumpur', '50000', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `location_id`, `total_amount`, `order_status`, `ordered_at`, `updated_at`) VALUES
(1, 2, 1, 259.89, 'delivered', '2026-05-07 15:52:33', '2026-05-24 18:23:45'),
(5, 11, 2, 210.90, 'delivered', '2026-05-24 16:14:39', '2026-05-24 18:33:48'),
(6, 11, 2, 210.90, 'shipped', '2026-05-24 18:25:53', '2026-05-24 18:53:48'),
(7, 11, 2, 210.90, 'pending', '2026-05-24 19:21:53', '2026-05-24 19:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 49.99),
(2, 1, 2, 1, 120.00),
(3, 1, 3, 1, 90.90),
(6, 5, 3, 1, 90.90),
(7, 5, 2, 1, 120.00),
(8, 6, 3, 1, 90.90),
(9, 6, 2, 1, 120.00),
(10, 7, 3, 1, 90.90),
(11, 7, 2, 1, 120.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `payment_status`, `transaction_reference`, `paid_at`) VALUES
(1, 1, 'Online Banking', 'paid', 'TXN-2026-0001', '2026-05-07 15:56:24'),
(2, 5, 'Debit Card', 'paid', 'TXN-2026-5160', '2026-05-24 16:14:39'),
(3, 6, 'Debit Card', 'paid', 'TXN-2026-1102', '2026-05-24 18:25:53'),
(4, 7, 'Debit Card', 'paid', 'TXN-2026-7404', '2026-05-24 19:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `description`, `price`, `image_url`, `is_active`, `created_at`) VALUES
(1, 'Wireless Mouse', 'RGB wireless gaming mouse', 49.99, 'mouse.jpg', 1, '2026-05-07 15:44:17'),
(2, 'NC Headset', 'Noise cancelling Bluetooth headset', 120.00, 'mouse.jpg', 1, '2026-05-07 15:44:17'),
(3, 'Wireless Keyboard', 'Blue switch mechanical keyboard', 90.90, 'keyboard.jpg', 1, '2026-05-07 15:44:17');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'customer'),
(3, 'driver');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `account_status` enum('active','suspended','locked') NOT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `account_status`, `failed_login_attempts`, `last_login`, `password_updated_at`, `created_at`, `phone_number`, `reset_token`, `token_expires_at`) VALUES
(1, 'Admin', 'admin@shopeasy.com', '$2y$10$wJFxmEDv0jkiq2cg1TEUIO4G5QIw0zLppYEnRI1Af3SeWoz4pQTW6', '0123456789', 'active', 0, NULL, NULL, '2026-05-07 15:34:48', NULL, NULL, NULL),
(2, 'Habeba Nader', 'habeba@hotmail.com', '$2y$10$wJFxmEDv0jkiq2cg1TEUIO4G5QIw0zLppYEnRI1Af3SeWoz4pQTW6', '0112233445', 'active', 0, NULL, NULL, '2026-05-07 15:38:55', NULL, NULL, NULL),
(3, 'Ali', 'driver@shopeasy.com', '$2y$10$wJFxmEDv0jkiq2cg1TEUIO4G5QIw0zLppYEnRI1Af3SeWoz4pQTW6', '0198877665', 'active', 0, NULL, NULL, '2026-05-07 15:39:42', NULL, NULL, NULL),
(11, 'Fiqa Fiqa', 'fiqa@gmail.com', '$2y$10$Lwg7rd8rvvF5DxVY71wzZecfpHkgGIArBgHjQCNSHNQdmc2RZP2nu', '0108645509', 'active', 0, '2026-05-24 19:18:34', NULL, '2026-05-24 16:13:11', NULL, NULL, NULL),
(12, 'Admin', 'admin2@shopeasy.com', '$2y$10$2BFld/xs7fyNEEy0mmM9OeNULu2JqT2XbP4dwGJfptALMCrpR6p9S', '0112233445', 'active', 0, '2026-05-24 19:22:11', NULL, '2026-05-24 16:16:18', NULL, NULL, NULL),
(13, 'Zakwan', 'driver2@shopeasy.com', '$2y$10$Gs7iTp6kY6drW1la9bXTE.tnfs3KSB5qokf6Lq3SO2cqkjjAVny3S', '0123456788', 'active', 0, '2026-05-24 19:10:10', NULL, '2026-05-24 16:41:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
CREATE TABLE `user_role` (
  `user_role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`user_role_id`, `user_id`, `role_id`) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 11, 2),
(5, 12, 1),
(6, 13, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_auditlog_user` (`user_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `fk_delivery_driver` (`driver_id`),
  ADD KEY `fk_delivery_location` (`location_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `fk_location_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_location` (`location_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`user_role_id`),
  ADD KEY `fk_userrole_user` (`user_id`),
  ADD KEY `fk_userrole_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_role`
--
ALTER TABLE `user_role`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_auditlog_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_driver` FOREIGN KEY (`driver_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_delivery_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_delivery_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `location`
--
ALTER TABLE `location`
  ADD CONSTRAINT `fk_location_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `fk_userrole_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_userrole_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

