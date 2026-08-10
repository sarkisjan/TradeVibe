-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 10, 2026 at 06:16 PM
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
-- Database: `products`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(5) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `shipping_address` varchar(255) NOT NULL,
  `shipping_name` varchar(255) NOT NULL,
  `shipping_phone` varchar(50) NOT NULL,
  `payment_method` varchar(100) NOT NULL DEFAULT 'Cash on Delivery',
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `currency`, `shipping_address`, `shipping_name`, `shipping_phone`, `payment_method`, `status`, `order_date`) VALUES
(1, 3, 80.00, 'USD', 'Jane Sandanski No. 22, Bitola', '', '', 'Cash on Delivery', 'Pending', '2026-08-05 19:24:43'),
(2, 3, 362.40, 'USD', 'Jane Sandanski No. 22, Bitola', '', '', 'Cash on Delivery', 'Pending', '2026-08-05 19:39:44'),
(3, 3, 2909.35, 'USD', 'Jane Sandanski No. 22, Bitola', '', '', 'Cash on Delivery', 'Pending', '2026-08-07 11:44:49'),
(4, 3, 3188.00, 'USD', 'Jane Sandanski No. 22, Bitola', '', '', 'Cash on Delivery', 'Pending', '2026-08-07 11:45:01'),
(5, 3, 1674.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 12:02:26'),
(6, 3, 1674.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 12:02:27'),
(7, 3, 1674.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 12:02:29'),
(8, 3, 1674.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 12:02:34'),
(9, 3, 1674.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 12:06:06'),
(10, 3, 3738.35, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-08 13:04:00'),
(11, 7, 46.40, 'USD', 'Makedonija 50', 'Elena Stojanova', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-09 13:14:16'),
(12, 7, 46.40, 'USD', 'Makedonija 50', 'Elena Stojanova', '+38975555666', 'Cash on Delivery', 'Pending', '2026-08-09 13:17:38'),
(13, 3, 46.40, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Shipped', '2026-08-10 12:18:09'),
(14, 3, 616.00, 'USD', 'Jane Sandanski No. 22, Bitola', 'Ivan Jovanov', '+38975555666', 'Cash on Delivery', 'Shipped', '2026-08-10 12:45:41');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(5) NOT NULL,
  `selected_size` varchar(50) NOT NULL DEFAULT 'Standard',
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `selected_size`, `price_at_purchase`) VALUES
(1, 1, 14, 1, 'Standard', 80.00),
(2, 2, 14, 1, 'Standard', 80.00),
(3, 2, 18, 2, 'Standard', 118.00),
(4, 2, 19, 1, 'Standard', 46.40),
(5, 3, 21, 1, 'Standard', 1594.00),
(6, 3, 22, 1, 'Standard', 1315.35),
(7, 4, 21, 2, 'Standard', 1594.00),
(8, 9, 14, 1, '41', 80.00),
(9, 9, 21, 1, 'Standard', 1594.00),
(10, 10, 20, 1, 'Standard', 829.00),
(11, 10, 21, 1, 'Standard', 1594.00),
(12, 10, 22, 1, 'Standard', 1315.35),
(13, 11, 19, 1, '42', 46.40),
(14, 12, 19, 1, '42', 46.40),
(15, 13, 19, 1, '42', 46.40),
(16, 14, 23, 1, '', 616.00);

-- --------------------------------------------------------

--
-- Table structure for table `producttable`
--

CREATE TABLE `producttable` (
  `id` int(10) NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT 1,
  `sku` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` int(3) DEFAULT 0,
  `image` varchar(255) DEFAULT 'default-product.png',
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(50) NOT NULL,
  `furniture_room` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `size_attr` varchar(20) DEFAULT NULL,
  `gender` enum('Unisex','Men','Women','Kids') DEFAULT 'Unisex',
  `size` int(10) DEFAULT NULL,
  `weight` int(10) DEFAULT NULL,
  `height` int(6) DEFAULT NULL,
  `width` int(6) DEFAULT NULL,
  `length` int(6) DEFAULT NULL,
  `productType` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `producttable`
--

INSERT INTO `producttable` (`id`, `admin_id`, `sku`, `name`, `description`, `price`, `discount`, `image`, `category`, `subcategory`, `furniture_room`, `brand`, `color`, `size_attr`, `gender`, `size`, `weight`, `height`, `width`, `length`, `productType`) VALUES
(14, 2, '1250', 'ADIDAS TERREX TRACEFINDER 2', '', 80.00, 0, '1785954777_6a7381d9496d7.webp,1785954777_6a7381d949c81.webp,1785954777_6a7381d94a196.webp,1785954777_6a7381d94a5b7.webp', 'Sports & Recreation', 'Shoes', NULL, 'Adidas', 'Black', NULL, 'Men', NULL, NULL, NULL, NULL, NULL, 'Shoes'),
(18, 2, '1212', 'NIKE M AIR MAX ALPHA TRAINER 6 ', '', 118.00, 0, '1785955322_6a7383fa2744a.webp,1785955322_6a7383fa27988.webp,1785955322_6a7383fa27e4b.webp,1785955322_6a7383fa281c0.webp', 'Sports & Recreation', 'Shoes', NULL, 'Nike', 'White', NULL, 'Men', NULL, NULL, NULL, NULL, NULL, 'Shoes'),
(19, 2, '1242', 'HUMMEL Патики HML NANO 25 ', '', 116.00, 60, '1785955505_6a7384b16d60a.webp,1785955505_6a7384b16d9a0.webp,1785955505_6a7384b16de24.webp', 'Sports & Recreation', 'Shoes', NULL, 'Hummel', 'Blue', NULL, 'Men', NULL, NULL, NULL, NULL, NULL, 'Shoes'),
(20, 2, '2001', '2-seat sofa, Tibbleby beige/gray', 'Cuddle up in the soft comfort of KIVIK sofa. The generous size, low armrests and pocket springs with foam that adapts to the body invites you and your guests to many hours of socialising and relaxation.', 829.00, 0, '1786012491_6a74634b0df4d.webp,1786012491_6a74634b0e19a.webp,1786012491_6a74634b12bc8.webp,1786012491_6a74634b12e3c.webp', 'Home & Garden', 'Furniture', 'Living room', 'Ikea', 'Brown', NULL, 'Unisex', NULL, NULL, 83, 190, 95, 'Furniture'),
(21, 2, '2002', 'STOCKHOLM 20253-seat sofa, Alhamn beige 3-seat sof', 'Long-lasting quality, tactile materials and comfort with geometric shapes and well-balanced proportions – this sofa has it all! Sit back and enjoy a sofa that will be the natural centerpiece of any room.', 1596.00, 0, '1786013188_6a74660446308.webp,1786013188_6a746604466ca.webp,1786013188_6a74660446ad8.webp', 'Home & Garden', 'Furniture', 'Bedroom', 'Ikea', 'Brown', NULL, 'Unisex', NULL, NULL, 70, 243, 99, 'Furniture'),
(22, 2, '2003', 'TOSSBERG table with 4 chairs, oak veneer brown sta', 'A durable dining set that makes it easy to have big dinners. The solid wood holds up well over time and will endure all the family meals and activities around the table.', 1667.00, 21, '1786013547_6a74676b16fcc.webp,1786013547_6a74676b17322.webp,1786013547_6a74676b17698.webp,1786013547_6a74676b17a55.webp,1786013547_6a74676b17eb4.webp', 'Home & Garden', 'Furniture', 'Dining room', 'Ikea', 'Brown', NULL, 'Unisex', NULL, NULL, 67, 145, 145, 'Furniture'),
(23, 2, '2004', 'KNOXHULT kitchen white Kitchen', 'Just right if you want a small kitchen with a freestanding cooktop and all necessary basic storage. As fast and easy to buy as to start using, since we’ve combined the modules into a ready-made solution.', 616.00, 0, '1786014142_6a7469be99099.webp,1786014142_6a7469be993e5.webp,1786014142_6a7469be99694.webp', 'Home & Garden', 'Furniture', 'Kitchen', 'Ikea', 'White', NULL, 'Unisex', NULL, NULL, 223, 236, 60, 'Furniture'),
(25, 2, '2/3001', 'Myprotein Impact Whey Protein Powder', '', 60.00, 0, '1786370759_6a79dac7aa983.jpg', 'Sports & Recreation', 'Supplements', NULL, 'MyProtein', NULL, NULL, 'Unisex', NULL, 900, NULL, NULL, NULL, 'Supplements');

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(10) NOT NULL,
  `size_name` varchar(10) NOT NULL,
  `quantity` int(5) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_stock`
--

INSERT INTO `product_stock` (`id`, `product_id`, `size_name`, `quantity`) VALUES
(1, 14, '39', 25),
(2, 14, '41', 20),
(3, 14, '42', 17),
(4, 14, '44', 9),
(5, 14, '45', 8),
(6, 14, '46', 10),
(7, 18, '39', 12),
(8, 18, '41', 11),
(9, 18, '42', 7),
(10, 18, '43', 11),
(11, 18, '45', 11),
(12, 18, '46', 8),
(13, 19, '39', 7),
(14, 19, '42', 1),
(15, 19, '45', 3),
(16, 20, 'Standard', 3),
(17, 21, 'Standard', 2),
(18, 22, 'Standard', 3),
(19, 23, 'Standard', 6),
(20, 25, 'Standard', 6);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `role` enum('user','admin','root') NOT NULL DEFAULT 'user',
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `phone`, `email`, `password`, `address`, `role`, `verification_token`, `is_verified`) VALUES
(1, 'superroot', 'Blagoja', 'Sarkisjan', '+38970111222', 'root@eshop.com', '$2y$10$UlKXR6iVsuiBiidfLtwIsOKPwRB0JGAjS2k3NjYsV2UYV6LBAHNSq', 'E-Shop Head Office, Skopje', 'root', NULL, 1),
(2, 'admin1', 'Marko', 'Petrov', '+38971333445', 'admin@eshop.com', '$2y$10$p/YZYPlnV0RfIVTXoVFtN.WY5ZVDmbkA2NXdmpAMDk.bE2JsBrFuu', 'Partizanski Odredi No. 50, Skopje', 'admin', NULL, 1),
(3, 'kupuvac1', 'Ivan', 'Jovanov', '+38975555666', 'user@eshop.com', '$2y$10$nJQFl2V3CcLAwSctRmPVmO9/h.u.JMrzrT7I81qFRJ2Xve88bnMuu', 'Jane Sandanski No. 22, Bitola', 'user', NULL, 1),
(6, 'admin2', 'Marko', 'Petrov', '', 'admin2@eshop.com', '$2y$10$Y2YR3s5vLByzWPHlgYs12uhCI41XaKlUeb4MsogL73eio.AgjM9Ne', 'Makedonija 50', 'admin', 'e25113badab942cf001e86e93b0e7e21931cb5c25256e0f9d7823832bd77e693', 1),
(7, 'user2', 'Elena', 'Stojanova', '', 'user2@eshop.com', '$2y$10$r8oSC5rU8Mch6GSefC25OOke1unTQaW3k5hgTsI62TCMktkA1h9PG', 'Makedonija 50', 'user', 'e046edba81c3b136b95867eb30b542a0da401f4099fab7383536bcf46f5eb7ae', 1),
(9, 'Blagoja', 'Blagoja', 'Sarkisjan', '', 'baze_sarkisjan@yahoo.com', '$2y$10$rI/XZBknhf10GSdyYLTYZuDCRRTaOn/xTdG7hdG2r5X1r8IO9KcHi', 'Mihail i Eftihij', 'admin', '44d2a9983882336c748eb5f4ca5ac437987ce86eb15119a316f4a148c0a51909', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `producttable`
--
ALTER TABLE `producttable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku_unique` (`sku`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `producttable`
--
ALTER TABLE `producttable`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `producttable` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `producttable` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `producttable`
--
ALTER TABLE `producttable`
  ADD CONSTRAINT `producttable_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `producttable` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
