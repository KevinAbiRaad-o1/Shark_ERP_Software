-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 07, 2025 at 11:29 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erp-database`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`, `description`) VALUES
(9, 'one', NULL),
(10, 'two', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE IF NOT EXISTS `department` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(25) NOT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_name` (`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`) VALUES
(8, '1'),
(9, '2');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
CREATE TABLE IF NOT EXISTS `employee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_id` int NOT NULL,
  `first_name` varchar(25) NOT NULL,
  `last_name` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address_line` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `department_id` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `person_id` (`person_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employee_department` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `person_id`, `first_name`, `last_name`, `email`, `phone`, `address_line`, `city`, `hire_date`, `department_id`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Sarah', 'Johnson', 'hr@company.com', '+9611234567', 'zaytouna', 'jbeil', '2025-05-06', NULL, 1, '2025-05-06 15:11:04', '2025-05-07 13:35:04', NULL),
(3, 3, 'Ali', 'Hassan', 'warehouse@company.com', '+9613456789', NULL, NULL, '2025-05-06', 8, 1, '2025-05-06 15:11:05', '2025-05-07 03:28:43', NULL),
(4, 4, 'Layla', 'Khalil', 'logistics@company.com', '+9614567890', NULL, NULL, '2025-05-06', 8, 1, '2025-05-06 15:11:05', '2025-05-07 03:49:06', NULL),
(5, 5, 'Karim', 'Al-Fayed', 'owner@company.com', '+9615678901', 'naher brahim rue 42', 'jbeil', '2025-05-06', NULL, 1, '2025-05-06 15:11:05', '2025-05-07 13:37:30', NULL),
(7, 0, 'Karim', 'Johnson', 'kevinabiraad@gmail.com', '+9611234567', '11', '11', '1111-11-11', NULL, 1, '2025-05-07 03:54:25', '2025-05-07 03:54:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_role_role_type_enum`
--

DROP TABLE IF EXISTS `employee_role_role_type_enum`;
CREATE TABLE IF NOT EXISTS `employee_role_role_type_enum` (
  `value` enum('owner','hr','accounting','warehouse','logistics') NOT NULL,
  PRIMARY KEY (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_role_role_type_enum`
--

INSERT INTO `employee_role_role_type_enum` (`value`) VALUES
('owner'),
('hr'),
('accounting'),
('warehouse'),
('logistics');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `location_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_id`, `location_id`, `quantity`, `last_updated`) VALUES
(1, 1, 6, 100, '2025-05-06 20:37:45'),
(2, 3, 6, 50, '2025-05-06 20:40:06'),
(3, 4, 6, 50, '2025-05-06 21:01:52'),
(4, 5, 6, 40, '2025-05-06 21:14:44'),
(5, 6, 6, 1, '2025-05-06 22:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
CREATE TABLE IF NOT EXISTS `item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sku` varchar(25) NOT NULL,
  `category_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `weight` decimal(10,2) DEFAULT NULL,
  `weight_unit` varchar(5) DEFAULT 'kg',
  `dimensions` varchar(20) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `min_stock_level` int DEFAULT NULL,
  `max_stock_level` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`id`, `sku`, `category_id`, `name`, `description`, `weight`, `weight_unit`, `dimensions`, `color`, `min_stock_level`, `max_stock_level`, `created_at`, `updated_at`, `is_active`) VALUES
(4, '#k3124215', 10, 'phone', 'apple', 13.00, 'kg', '100x100', 'gold', 100, 200, '2025-05-06 21:01:52', '2025-05-07 04:01:54', 1),
(5, '#k312421', 9, 'bush', 'a green bush', 1.00, 'kg', '8x5', 'green', 80, 400, '2025-05-06 21:14:44', '2025-05-07 04:02:01', 1),
(6, '#k3124215q', 9, 'q', '', 0.06, 'kg', '1', 'gold', 10, 1, '2025-05-06 22:02:40', '2025-05-06 22:02:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `item_deletion_log`
--

DROP TABLE IF EXISTS `item_deletion_log`;
CREATE TABLE IF NOT EXISTS `item_deletion_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `action` enum('approved','rejected') NOT NULL,
  `user_id` int NOT NULL,
  `reason` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `item_deletion_log`
--

INSERT INTO `item_deletion_log` (`id`, `item_id`, `action`, `user_id`, `reason`, `created_at`) VALUES
(1, 1, 'approved', 4, 'Bulk action', '2025-05-06 21:11:06'),
(2, 3, 'approved', 4, 'Bulk action', '2025-05-06 21:40:03'),
(3, 4, 'rejected', 4, 'cant', '2025-05-07 04:01:54'),
(4, 4, 'rejected', 4, 'cant', '2025-05-07 04:01:55'),
(5, 4, 'rejected', 4, 'cant', '2025-05-07 04:01:56'),
(6, 5, 'rejected', 4, 'cant', '2025-05-07 04:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
CREATE TABLE IF NOT EXISTS `location` (
  `id` int NOT NULL AUTO_INCREMENT,
  `warehouse_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`id`, `warehouse_id`, `name`) VALUES
(7, 1, 'second'),
(6, 1, 'first');

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
CREATE TABLE IF NOT EXISTS `login_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `login_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics_po_approval`
--

DROP TABLE IF EXISTS `logistics_po_approval`;
CREATE TABLE IF NOT EXISTS `logistics_po_approval` (
  `id` int NOT NULL AUTO_INCREMENT,
  `warehouse_request_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `payment_terms` varchar(20) NOT NULL,
  `shipping_method` varchar(20) NOT NULL,
  `approved_by` int NOT NULL,
  `approved_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `warehouse_request_id` (`warehouse_request_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logistics_po_approval`
--

INSERT INTO `logistics_po_approval` (`id`, `warehouse_request_id`, `supplier_id`, `payment_terms`, `shipping_method`, `approved_by`, `approved_at`) VALUES
(7, 3, 5, '', '', 4, '2025-05-06 21:18:22'),
(6, 2, 5, '', '', 4, '2025-05-06 21:04:31'),
(5, 1, 5, '', '', 4, '2025-05-06 20:46:04');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_item`
--

DROP TABLE IF EXISTS `purchase_order_item`;
CREATE TABLE IF NOT EXISTS `purchase_order_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `received_quantity` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_order_item`
--

INSERT INTO `purchase_order_item` (`id`, `po_id`, `item_id`, `quantity`, `received_quantity`) VALUES
(11, 1, 3, 150, 0),
(12, 2, 4, 50, 0),
(13, 3, 5, 40, 0),
(14, 4, 5, 40, 0),
(15, 5, 5, 40, 0),
(16, 6, 6, 9, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_status_enum`
--

DROP TABLE IF EXISTS `purchase_order_status_enum`;
CREATE TABLE IF NOT EXISTS `purchase_order_status_enum` (
  `value` enum('draft','approved','sent','received','cancelled') NOT NULL,
  PRIMARY KEY (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_order_status_enum`
--

INSERT INTO `purchase_order_status_enum` (`value`) VALUES
('draft'),
('approved'),
('sent'),
('received'),
('cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_status_history`
--

DROP TABLE IF EXISTS `purchase_order_status_history`;
CREATE TABLE IF NOT EXISTS `purchase_order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `status` enum('draft','approved','sent','received','cancelled') NOT NULL,
  `changed_by` int NOT NULL,
  `changed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `role_type` enum('owner','hr','accounting','warehouse','logistics') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `employee_id`, `role_type`) VALUES
(1, 1, 'hr'),
(7, 7, 'hr'),
(3, 3, 'warehouse'),
(4, 4, 'logistics'),
(5, 5, 'hr');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
CREATE TABLE IF NOT EXISTS `supplier` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(100) NOT NULL,
  `address_line2` varchar(100) DEFAULT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL DEFAULT 'Lebanon',
  `tax_id` varchar(50) DEFAULT NULL,
  `payment_terms` enum('NET30','NET60','Upon Delivery','Advance Payment') DEFAULT 'NET30',
  `lead_time_days` int DEFAULT '7',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id`, `supplier_code`, `name`, `contact_person`, `email`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `tax_id`, `payment_terms`, `lead_time_days`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'SUP001', 'Supplier One', 'John Doe', 'john@supplier.com', '1234567890', '123 Main St', '', 'New York', 'NY', '10001', 'USA', 'TAX123', '', 7, 1, '2025-05-06 20:45:46', '2025-05-07 13:49:48'),
(5, 'SUP002', 'Supplier Two', 'Jane Smith', 'jane.different@supplier.com', '0987654321', '456 Industrial Ave', 'Building 2', 'Los Angeles', 'CA', '90001', 'USA', 'TAX456', '', 10, 1, '2025-05-06 20:45:46', '2025-05-07 13:49:48'),
(8, 'SUP003', 'Supplier three', 'Jane', 'jane@supplier.com', '098765432', '', NULL, '', '', '', 'Lebanon', NULL, 'NET30', 7, 1, '2025-05-06 22:15:36', '2025-05-07 13:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_items`
--

DROP TABLE IF EXISTS `supplier_items`;
CREATE TABLE IF NOT EXISTS `supplier_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `item_id` int NOT NULL,
  `supplier_sku` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_item` (`supplier_id`,`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `person_id` int NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `person_id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 1, 'hruser', 'sarah', '2025-05-06 15:11:04', '2025-05-07 13:35:04'),
(6, 6, 'kevin', '123456789', '2025-05-07 03:52:49', '2025-05-07 03:52:49'),
(3, 3, 'warehouse', '123', '2025-05-06 15:11:05', '2025-05-06 15:11:05'),
(4, 4, 'logistics', '123', '2025-05-06 15:11:05', '2025-05-06 15:11:05'),
(5, 5, 'owner', 'faayad', '2025-05-06 15:11:05', '2025-05-07 13:37:30'),
(7, 7, '111', '11111111', '2025-05-07 03:54:25', '2025-05-07 03:54:25');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_item_request`
--

DROP TABLE IF EXISTS `warehouse_item_request`;
CREATE TABLE IF NOT EXISTS `warehouse_item_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('draft','approved','sent','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text,
  `created_by` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_terms` varchar(20) DEFAULT 'NET30',
  `shipping_method` varchar(20) DEFAULT 'Ground',
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `fk_po_supplier` (`supplier_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `warehouse_item_request`
--

INSERT INTO `warehouse_item_request` (`id`, `po_number`, `order_date`, `expected_delivery_date`, `status`, `notes`, `created_by`, `supplier_id`, `approved_by`, `approved_at`, `created_at`, `updated_at`, `payment_terms`, `shipping_method`, `is_deleted`) VALUES
(1, 'PO-20250506-174126', '2025-05-06 20:41:26', '2026-05-13', 'approved', 'urgent', 3, 5, 4, '2025-05-06 20:46:04', '2025-05-06 20:41:26', '2025-05-07 04:02:26', 'NET30', 'Ground', 1),
(2, 'PO-20250506-180415', '2025-05-06 21:04:15', '2025-05-13', 'approved', 'needed', 3, 5, 4, '2025-05-06 21:04:31', '2025-05-06 21:04:15', '2025-05-06 21:57:29', 'NET30', 'Ground', 1),
(3, 'PO-20250506-181546', '2025-05-06 21:15:46', '2025-05-13', 'cancelled', 'needed', 3, 5, 4, '2025-05-06 21:18:22', '2025-05-06 21:15:46', '2025-05-06 21:18:32', 'NET30', 'Ground', 1),
(4, 'PO-20250506-182213', '2025-05-06 21:22:13', '2025-05-13', 'cancelled', '', 3, NULL, 4, '2025-05-06 21:22:51', '2025-05-06 21:22:13', '2025-05-06 21:55:35', NULL, NULL, 1),
(5, 'PO-20250506-183055', '2025-05-06 21:30:55', '2025-05-13', 'cancelled', '', 3, NULL, 4, '2025-05-06 21:55:06', '2025-05-06 21:30:55', '2025-05-06 21:55:33', NULL, NULL, 1),
(6, 'PO-20250506-190303', '2025-05-06 22:03:03', '2025-05-13', 'cancelled', '', 3, NULL, 4, '2025-05-07 04:02:10', '2025-05-06 22:03:03', '2025-05-07 04:02:27', NULL, NULL, 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
