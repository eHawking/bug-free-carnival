-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 08, 2025 at 08:59 AM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `honr`
--

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `sku` varchar(10) NOT NULL,
  `title` varchar(190) NOT NULL,
  `subtitle` varchar(190) DEFAULT NULL,
  `bottles` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `old_total_price` decimal(12,2) DEFAULT NULL,
  `shipping_text` varchar(190) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `sku`, `title`, `subtitle`, `bottles`, `total_price`, `old_total_price`, `shipping_text`, `features`, `image_main`, `sort`, `updated_at`) VALUES
(1, 'EPK03', 'Best Value', '90 Days, 3 Bottles', 3, 8000.00, 12000.00, 'FREE INDIA SHIPPING', NULL, '/images/20251008085722_img-PRODx3.png', 10, '2025-10-08 08:57:41'),
(2, 'EPK02', 'Most Popular', '60 Days, 2 Bottles', 2, 3500.00, 8000.00, 'FREE INDIA SHIPPING', NULL, '/images/20251008085722_img-PRODx2.png', 20, '2025-10-08 08:57:41'),
(3, 'EPK01', 'Try One', '30 Days, 1 Bottle', 1, 2200.00, 3500.00, 'FREE INDIA SHIPPING', NULL, '/images/20251008085722_img-PRODx1.png', 30, '2025-10-08 08:57:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
