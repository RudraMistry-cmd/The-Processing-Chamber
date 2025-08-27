-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2025 at 08:52 AM
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
-- Database: `processing_chamber`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `parent_id`, `created_at`) VALUES
(1, 'Processors', 'CPU processors from Intel and AMD', NULL, '2025-08-24 12:05:05'),
(2, 'Graphics Cards', 'GPU cards from NVIDIA and AMD', NULL, '2025-08-24 12:05:05'),
(3, 'Memory', 'RAM memory modules', NULL, '2025-08-24 12:05:05'),
(4, 'Storage', 'SSD and HDD storage devices', NULL, '2025-08-24 12:05:05'),
(5, 'Motherboards', 'Computer motherboards', NULL, '2025-08-24 12:05:05'),
(6, 'Cooling', 'CPU coolers and case fans', NULL, '2025-08-24 12:05:05'),
(7, 'Power Supplies', 'PSU power supply units', NULL, '2025-08-24 12:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` enum('cod','card','upi','netbanking') DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `payment_method`, `payment_status`, `shipping_address`, `created_at`, `updated_at`) VALUES
(1, 7, 38999.00, 'pending', 'cod', 'pending', 'knb', '2025-08-25 17:41:29', '2025-08-25 17:41:29'),
(2, 7, 150498.00, 'pending', 'cod', 'pending', 'cfdgdrtgtdrx', '2025-08-26 04:30:06', '2025-08-26 04:30:06'),
(3, 7, 72500.00, 'pending', 'cod', 'pending', 'fvdf', '2025-08-26 05:47:07', '2025-08-26 05:47:07'),
(4, 9, 194995.00, 'pending', 'upi', 'pending', 'dfvfvdfb', '2025-08-27 05:08:57', '2025-08-27 05:08:57'),
(5, 9, 38999.00, 'pending', 'cod', 'pending', 'erhetrgbd', '2025-08-27 05:59:45', '2025-08-27 05:59:45'),
(6, 9, 18999.00, 'pending', 'netbanking', 'pending', 'gncnbcbnc', '2025-08-27 06:02:40', '2025-08-27 06:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 38999.00),
(2, 2, 1, 2, 38999.00),
(3, 2, 2, 1, 72500.00),
(4, 3, 2, 1, 72500.00),
(5, 4, 1, 5, 38999.00),
(6, 5, 1, 1, 38999.00),
(7, 6, 5, 1, 18999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `specifications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image`, `category_id`, `featured`, `specifications`, `created_at`, `updated_at`) VALUES
(1, 'Intel Core i7-13700K', '16-core 24-thread desktop processor', 38999.00, 0, 'i7-13700k.jpg', 1, 1, '{\"cores\":16,\"threads\":24,\"base_clock\":\"3.4GHz\",\"boost_clock\":\"5.4GHz\",\"cache\":\"30MB\"}', '2025-08-24 12:05:05', '2025-08-27 06:32:15'),
(2, 'NVIDIA RTX 4070 Ti', '12GB GDDR6X graphics card', 72500.00, 6, '4070ti.jpg', 2, 1, '{\"vram\":\"12GB\",\"type\":\"GDDR6X\",\"cuda_cores\":7680,\"boost_clock\":\"2.61GHz\"}', '2025-08-24 12:05:05', '2025-08-26 05:47:07'),
(3, 'Corsair Vengeance 32GB DDR5', '32GB DDR5 RAM kit', 12800.00, 20, NULL, 3, 1, '{\"capacity\":\"32GB\",\"speed\":\"5600MHz\",\"latency\":\"CL36\",\"type\":\"DDR5\"}', '2025-08-24 12:05:05', '2025-08-24 12:05:05'),
(5, 'ASUS ROG Strix B760-F', 'LGA 1700 motherboard', 18999.00, 9, NULL, 5, 0, '{\"socket\":\"LGA1700\",\"chipset\":\"B760\",\"form_factor\":\"ATX\",\"memory_slots\":4}', '2025-08-24 12:05:05', '2025-08-27 06:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `remember_token`, `reset_token`, `reset_expiry`, `created_at`, `updated_at`) VALUES
(7, 'rm@g.com', 'rm@g.com', '$2y$10$BiT7uIytoTyERZx0E3.lJeyWVPsNwPx6UZOaszAHpcGTmG8NUeAyW', 'admin', NULL, NULL, NULL, '2025-08-25 17:39:51', '2025-08-27 06:26:44'),
(8, 'Rudu', 'rudu@gmail.com', '1234567890', 'admin', NULL, NULL, NULL, '2025-08-26 05:39:58', '2025-08-26 05:39:58'),
(9, 'Preet Prajapati', 'preet@gmail.com', '$2y$10$4C6TTF90LPK6dRdkK/Kzpe.Cef2moUY42EkFV5tfTjucPBk7zKX1u', 'customer', 'd6beea8c23c8aaa0642aced85b04870e3af75895960203d454064abb2829a108', NULL, NULL, '2025-08-27 04:56:48', '2025-08-27 05:48:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
