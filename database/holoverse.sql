-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2026 at 12:08 PM
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
-- Database: `holoverse`
--

-- --------------------------------------------------------

--
-- Table structure for table `address_tbl`
--

CREATE TABLE `address_tbl` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country_code` char(2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items_tbl`
--

CREATE TABLE `cart_items_tbl` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_tbl`
--

CREATE TABLE `cart_tbl` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_token` varchar(128) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category_tbl`
--

CREATE TABLE `category_tbl` (
  `category_id` int(10) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_address_tbl`
--

CREATE TABLE `order_address_tbl` (
  `order_id` int(11) NOT NULL,
  `address_type` varchar(10) NOT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country_code` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items_tbl`
--

CREATE TABLE `order_items_tbl` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name_snapshot` varchar(255) NOT NULL,
  `variant_name_snapshot` varchar(100) NOT NULL,
  `sku_snapshot` varchar(50) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tbl`
--

CREATE TABLE `order_tbl` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_image_tbl`
--

CREATE TABLE `product_image_tbl` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_talents_tbl`
--

CREATE TABLE `product_talents_tbl` (
  `product_id` int(11) NOT NULL,
  `talent_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_tbl`
--

CREATE TABLE `product_tbl` (
  `product_id` int(11) NOT NULL,
  `category_id` int(10) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_preorder` tinyint(1) NOT NULL DEFAULT 0,
  `preorder_closes_at` datetime DEFAULT NULL,
  `expected_ship_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants_tbl`
--

CREATE TABLE `product_variants_tbl` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL DEFAULT 'Default',
  `sku` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `stock_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `talent_group_members_tbl`
--

CREATE TABLE `talent_group_members_tbl` (
  `talent_id` int(10) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `talent_group_tbl`
--

CREATE TABLE `talent_group_tbl` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `talent_tbl`
--

CREATE TABLE `talent_tbl` (
  `talent_id` int(10) NOT NULL,
  `talent_name` varchar(255) NOT NULL,
  `talent_slug` varchar(50) NOT NULL,
  `talent_img` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tbl`
--

CREATE TABLE `user_tbl` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address_tbl`
--
ALTER TABLE `address_tbl`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `fk_addresses_user` (`user_id`);

--
-- Indexes for table `cart_items_tbl`
--
ALTER TABLE `cart_items_tbl`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uq_cart_variant` (`cart_id`,`variant_id`),
  ADD KEY `fk_cart_items_variant` (`variant_id`);

--
-- Indexes for table `cart_tbl`
--
ALTER TABLE `cart_tbl`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `fk_carts_user` (`user_id`);

--
-- Indexes for table `category_tbl`
--
ALTER TABLE `category_tbl`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `order_address_tbl`
--
ALTER TABLE `order_address_tbl`
  ADD PRIMARY KEY (`order_id`,`address_type`);

--
-- Indexes for table `order_items_tbl`
--
ALTER TABLE `order_items_tbl`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_variant` (`variant_id`);

--
-- Indexes for table `order_tbl`
--
ALTER TABLE `order_tbl`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indexes for table `product_image_tbl`
--
ALTER TABLE `product_image_tbl`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_product_images_product` (`product_id`);

--
-- Indexes for table `product_talents_tbl`
--
ALTER TABLE `product_talents_tbl`
  ADD PRIMARY KEY (`product_id`,`talent_id`),
  ADD KEY `fk_product_talents_talent` (`talent_id`);

--
-- Indexes for table `product_tbl`
--
ALTER TABLE `product_tbl`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `product_variants_tbl`
--
ALTER TABLE `product_variants_tbl`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `uq_product_variant_name` (`product_id`,`variant_name`);

--
-- Indexes for table `talent_group_members_tbl`
--
ALTER TABLE `talent_group_members_tbl`
  ADD PRIMARY KEY (`talent_id`,`group_id`),
  ADD KEY `fk_tgm_group` (`group_id`);

--
-- Indexes for table `talent_group_tbl`
--
ALTER TABLE `talent_group_tbl`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `talent_tbl`
--
ALTER TABLE `talent_tbl`
  ADD PRIMARY KEY (`talent_id`);

--
-- Indexes for table `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address_tbl`
--
ALTER TABLE `address_tbl`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items_tbl`
--
ALTER TABLE `cart_items_tbl`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_tbl`
--
ALTER TABLE `cart_tbl`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category_tbl`
--
ALTER TABLE `category_tbl`
  MODIFY `category_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items_tbl`
--
ALTER TABLE `order_items_tbl`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_tbl`
--
ALTER TABLE `order_tbl`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_image_tbl`
--
ALTER TABLE `product_image_tbl`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_tbl`
--
ALTER TABLE `product_tbl`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants_tbl`
--
ALTER TABLE `product_variants_tbl`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `talent_group_tbl`
--
ALTER TABLE `talent_group_tbl`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `talent_tbl`
--
ALTER TABLE `talent_tbl`
  MODIFY `talent_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address_tbl`
--
ALTER TABLE `address_tbl`
  ADD CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `user_tbl` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items_tbl`
--
ALTER TABLE `cart_items_tbl`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart_tbl` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_tbl` (`variant_id`);

--
-- Constraints for table `cart_tbl`
--
ALTER TABLE `cart_tbl`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `user_tbl` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_address_tbl`
--
ALTER TABLE `order_address_tbl`
  ADD CONSTRAINT `fk_order_addresses_order` FOREIGN KEY (`order_id`) REFERENCES `order_tbl` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items_tbl`
--
ALTER TABLE `order_items_tbl`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `order_tbl` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_tbl` (`variant_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_tbl`
--
ALTER TABLE `order_tbl`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `user_tbl` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `product_image_tbl`
--
ALTER TABLE `product_image_tbl`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `product_tbl` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_talents_tbl`
--
ALTER TABLE `product_talents_tbl`
  ADD CONSTRAINT `fk_product_talents_product` FOREIGN KEY (`product_id`) REFERENCES `product_tbl` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_talents_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_tbl` (`talent_id`);

--
-- Constraints for table `product_tbl`
--
ALTER TABLE `product_tbl`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `category_tbl` (`category_id`);

--
-- Constraints for table `product_variants_tbl`
--
ALTER TABLE `product_variants_tbl`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `product_tbl` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `talent_group_members_tbl`
--
ALTER TABLE `talent_group_members_tbl`
  ADD CONSTRAINT `fk_tgm_group` FOREIGN KEY (`group_id`) REFERENCES `talent_group_tbl` (`group_id`),
  ADD CONSTRAINT `fk_tgm_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_tbl` (`talent_id`);

COMMIT;


-- ============================================================================
-- Starter catalog data
-- ============================================================================

ALTER TABLE `user_tbl` MODIFY `password_hash` varchar(255) NOT NULL;

START TRANSACTION;

INSERT INTO `category_tbl` (`category_id`, `category_name`) VALUES
(1, 'Limited Drops'),
(2, 'Flash Sale'),
(3, 'Pre-Order');

INSERT INTO `talent_group_tbl` (`group_id`, `group_name`, `group_slug`) VALUES
(1, 'Generation 0', 'gen-0'),
(2, 'Gamers', 'gamers'),
(3, 'Myth', 'myth'),
(4, 'Promise', 'promise'),
(5, 'Generation 3', 'gen-3');

INSERT INTO `talent_tbl` (`talent_id`, `talent_name`, `talent_slug`, `talent_img`) VALUES
(1, 'Hoshimachi Suisei', 'hoshimachi-suisei', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png'),
(2, 'Inugami Korone', 'inugami-korone', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_list_thumb.png'),
(3, 'Gawr Gura', 'gawr-gura', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Gawr-Gura_list_thumb.png'),
(4, 'Mori Calliope', 'mori-calliope', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Mori-Calliope_list_thumb.png'),
(5, 'Usada Pekora', 'usada-pekora', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Usada-Pekora_list_thumb.png');

INSERT INTO `talent_group_members_tbl` (`talent_id`, `group_id`) VALUES
(1, 1), (2, 2), (3, 3), (4, 3), (5, 5);

INSERT INTO `product_tbl`
  (`product_id`, `category_id`, `product_name`, `description`, `status`, `is_preorder`, `expected_ship_date`) VALUES
(1, 2, 'Hoshimachi Suisei 1/4 Scale Anniversary Edition', 'A limited collector release inspired by Suisei’s signature stage look, with a detailed display base and individually numbered authenticity card.', 'active', 1, DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
(2, 1, 'Inugami Korone 1/4 Scale “Doog Champion” Figure', 'Batch ETA: November 2026. Includes signed cover card.', 'active', 1, '2026-11-01'),
(3, 3, 'Gawr Gura 1/7 Scale “Apex Predator” Figure', 'Sculpted by Design COCO. Removable hoodie feature.', 'active', 1, DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
(4, 1, 'Mori Calliope “SINDERELLA” Tour Souvenir Sukajan', 'Reversible high-thread embroidery. Limited run of 750.', 'active', 0, NULL),
(5, 3, 'Hoshimachi Suisei 5th Anniversary Memorial Box', 'Complete vinyl set, bespoke star pin, and replica beret.', 'active', 1, DATE_ADD(CURDATE(), INTERVAL 5 MONTH));

INSERT INTO `product_variants_tbl`
  (`variant_id`, `product_id`, `variant_name`, `sku`, `price`, `compare_at_price`, `currency`, `stock_quantity`) VALUES
(1, 1, '1/4 Scale', 'HV-SUISEI-ANNIV-14', 14104.83, 17631.04, 'PHP', 22),
(2, 2, '1/4 Scale', 'HV-KORONE-DOOG-14', 18197.75, NULL, 'PHP', 60),
(3, 3, '1/7 Scale', 'HV-GURA-APEX-17', 13538.12, NULL, 'PHP', 60),
(4, 4, 'Tour Edition', 'HV-CALLIOPE-SINDERELLA', 11963.92, NULL, 'PHP', 413),
(5, 5, 'Memorial Box', 'HV-SUISEI-MEMORIAL-05', 11019.40, NULL, 'PHP', 360);

INSERT INTO `product_image_tbl` (`image_id`, `product_id`, `image_url`, `alt_text`, `sort_order`) VALUES
(1, 1, 'assets/images/suisei2.jpg', 'Hoshimachi Suisei anniversary figure', 0),
(2, 2, 'assets/images/hololive.png', 'Inugami Korone limited figure', 0),
(3, 3, 'assets/images/hololive.png', 'Gawr Gura limited figure', 0),
(4, 4, 'assets/images/hololive.png', 'Mori Calliope tour souvenir jacket', 0),
(5, 5, 'assets/images/suisei.jpg', 'Hoshimachi Suisei memorial box', 0);

INSERT INTO `product_talents_tbl` (`product_id`, `talent_id`) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 1);

COMMIT;


-- ============================================================================
-- Full talent directory
-- ============================================================================

START TRANSACTION;

INSERT INTO `talent_group_tbl` (`group_name`, `group_slug`)
SELECT source.`group_name`, source.`group_slug`
FROM (
    SELECT 'Generation 0' AS `group_name`, 'gen-0' AS `group_slug`
    UNION ALL SELECT 'Generation 1', 'gen-1'
    UNION ALL SELECT 'Gamers', 'gamers'
    UNION ALL SELECT 'Generation 2', 'gen-2'
    UNION ALL SELECT 'Generation 3', 'gen-3'
    UNION ALL SELECT 'Generation 4', 'gen-4'
    UNION ALL SELECT 'Generation 5', 'gen-5'
    UNION ALL SELECT 'HoloX', 'holox'
    UNION ALL SELECT 'ReGLOSS', 'regloss'
    UNION ALL SELECT 'FLOW GLOW', 'flow-glow'
    UNION ALL SELECT 'Myth', 'myth'
    UNION ALL SELECT 'Promise', 'promise'
    UNION ALL SELECT 'Advent', 'advent'
    UNION ALL SELECT 'Justice', 'justice'
    UNION ALL SELECT 'Indonesia Gen 1', 'id-gen-1'
    UNION ALL SELECT 'Indonesia Gen 2', 'id-gen-2'
    UNION ALL SELECT 'Indonesia Gen 3', 'id-gen-3'
    UNION ALL SELECT 'Asobi Mawaritai', 'asobi-mawaritai'
) AS source
WHERE NOT EXISTS (
    SELECT 1 FROM `talent_group_tbl` existing
    WHERE existing.`group_slug` = source.`group_slug`
);

INSERT INTO `talent_tbl` (`talent_name`, `talent_slug`, `talent_img`)
SELECT source.`talent_name`, source.`talent_slug`, source.`talent_img`
FROM (
    SELECT 'Tokino Sora' AS `talent_name`, 'tokino-sora' AS `talent_slug`, 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokino-Sora_list_thumb.png' AS `talent_img`
    UNION ALL SELECT 'Roboco-san', 'roboco-san', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Robocosan_list_thumb.png'
    UNION ALL SELECT 'AZKi', 'azki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/AZKi_list_thumb.png'
    UNION ALL SELECT 'Sakura Miko', 'sakura-miko', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Sakura-Miko_list_thumb.png'
    UNION ALL SELECT 'Hoshimachi Suisei', 'hoshimachi-suisei', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png'
    UNION ALL SELECT 'Aki Rosenthal', 'aki-rosenthal', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Aki-Rosenthal_list_thumb.png'
    UNION ALL SELECT 'Akai Haato', 'akai-haato', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Akai-Haato_list_thumb.png'
    UNION ALL SELECT 'Shirakami Fubuki', 'shirakami-fubuki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirakami-Fubuki_list_thumb.png'
    UNION ALL SELECT 'Natsuiro Matsuri', 'natsuiro-matsuri', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Natsuiro-Matsuri_list_thumb.png'
    UNION ALL SELECT 'Ookami Mio', 'ookami-mio', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Ookami-Mio_thumb.png'
    UNION ALL SELECT 'Nekomata Okayu', 'nekomata-okayu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nekomata-Okayu_list_thumb.png'
    UNION ALL SELECT 'Inugami Korone', 'inugami-korone', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_list_thumb.png'
    UNION ALL SELECT 'Nakiri Ayame', 'nakiri-ayame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nakiri-Ayame_list_thumb.png'
    UNION ALL SELECT 'Yuzuki Choco', 'yuzuki-choco', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yuzuki-Choco_list_thumb.png'
    UNION ALL SELECT 'Oozora Subaru', 'oozora-subaru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Oozora-Subaru_list_thumb.png'
    UNION ALL SELECT 'Gawr Gura', 'gawr-gura', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Gawr-Gura_list_thumb.png'
    UNION ALL SELECT 'Usada Pekora', 'usada-pekora', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Usada-Pekora_list_thumb.png'
    UNION ALL SELECT 'Shiranui Flare', 'shiranui-flare', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shiranui-Flare_list_thumb.png'
    UNION ALL SELECT 'Shirogane Noel', 'shirogane-noel', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirogane-Noel_list_thumb.png'
    UNION ALL SELECT 'Houshou Marine', 'houshou-marine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Houshou-Marine_list_thumb.png'
    UNION ALL SELECT 'Tsunomaki Watame', 'tsunomaki-watame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png'
    UNION ALL SELECT 'Tokoyami Towa', 'tokoyami-towa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokoyami-Towa_list_thumb.png'
    UNION ALL SELECT 'Himemori Luna', 'himemori-luna', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Himemori-Luna_list_thumb.png'
    UNION ALL SELECT 'Yukihana Lamy', 'yukihana-lamy', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yukihana-Lamy_list_thumb.png'
    UNION ALL SELECT 'Momosuzu Nene', 'momosuzu-nene', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_talent_list_thumb.png'
    UNION ALL SELECT 'Shishiro Botan', 'shishiro-botan', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Shishiro-Botan_list_thumb.png'
    UNION ALL SELECT 'Omaru Polka', 'omaru-polka', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Omaru-Polka_list_thumb.png'
    UNION ALL SELECT 'La+ Darknesss', 'la-darknesss', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/La-Darknesss_list_thumb.png'
    UNION ALL SELECT 'Takane Lui', 'takane-lui', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takane-Lui_list_thumb.png'
    UNION ALL SELECT 'Hakui Koyori', 'hakui-koyori', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakui-Koyori_list_thumb.png'
    UNION ALL SELECT 'Kazama Iroha', 'kazama-iroha', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_talent_list_thumb.png'
    UNION ALL SELECT 'Otonose Kanade', 'otonose-kanade', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Otonose-Kanade_list_thumb.png'
    UNION ALL SELECT 'Ichijou Ririka', 'ichijou-ririka', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Ichijou-Ririka_list_thumb.png'
    UNION ALL SELECT 'Juufuutei Raden', 'juufuutei-raden', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Juufuutei-Raden_list_thumb.png'
    UNION ALL SELECT 'Todoroki Hajime', 'todoroki-hajime', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Todoroki-Hajime_list_thumb.png'
    UNION ALL SELECT 'Isaki Riona', 'isaki-riona', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Isaki-Riona_list_thumb.png'
    UNION ALL SELECT 'Koganei Niko', 'koganei-niko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Koganei-Niko_list_thumb.png'
    UNION ALL SELECT 'Mizumiya Su', 'mizumiya-su', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Mizumiya-Su_list_thumb.png'
    UNION ALL SELECT 'Rindo Chihaya', 'rindo-chihaya', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Rindo-Chihaya_list_thumb.png'
    UNION ALL SELECT 'Kikirara Vivi', 'kikirara-vivi', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Kikirara-Vivi_list_thumb.png'
    UNION ALL SELECT 'Mori Calliope', 'mori-calliope', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Mori-Calliope_list_thumb.png'
    UNION ALL SELECT 'Takanashi Kiara', 'takanashi-kiara', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takanashi-Kiara_list_thumb.png'
    UNION ALL SELECT 'Ninomae Ina’nis', 'ninomae-inanis', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ninomae-Inanis_list_thumb.png'
    UNION ALL SELECT 'Watson Amelia', 'watson-amelia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Watson-Amelia_list_thumb.png'
    UNION ALL SELECT 'IRyS', 'irys', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/IRyS_list_thumb.png'
    UNION ALL SELECT 'Ouro Kronii', 'ouro-kronii', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ouro-Kronii_list_thumb.png'
    UNION ALL SELECT 'Hakos Baelz', 'hakos-baelz', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakos-Baelz_list_thumb.png'
    UNION ALL SELECT 'Shiori Novella', 'shiori-novella', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Shiori-Novella_list_thumb.png'
    UNION ALL SELECT 'Koseki Bijou', 'koseki-bijou', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Koseki-Bijou_list_thumb.png'
    UNION ALL SELECT 'Nerissa Ravencroft', 'nerissa-ravencroft', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Nerissa-Ravencroft_list_thumb.png'
    UNION ALL SELECT 'Fuwawa Abyssgard', 'fuwawa-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Fuwawa-Abyssgard_list_thumb.png'
    UNION ALL SELECT 'Mococo Abyssgard', 'mococo-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Mococo-Abyssgard_list_thumb.png'
    UNION ALL SELECT 'Elizabeth Rose Bloodflame', 'elizabeth-rose-bloodflame', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Elizabeth-Rose-Bloodflame_list_thumb.png'
    UNION ALL SELECT 'Gigi Murin', 'gigi-murin', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Gigi-Murin_list_thumb.png'
    UNION ALL SELECT 'Cecilia Immergreen', 'cecilia-immergreen', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Cecilia-Immergreen_list_thumb.png'
    UNION ALL SELECT 'Raora Panthera', 'raora-panthera', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Raora-Panthera_list_thumb.png'
    UNION ALL SELECT 'Ayunda Risu', 'ayunda-risu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ayunda-Risu_list_thumb.png'
    UNION ALL SELECT 'Moona Hoshinova', 'moona-hoshinova', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Moona-Hoshinova_list_thumb.png'
    UNION ALL SELECT 'Airani Iofifteen', 'airani-iofifteen', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Airani-Iofifteen_list_thumb.png'
    UNION ALL SELECT 'Kureiji Ollie', 'kureiji-ollie', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kureiji-Ollie_list_thumb.png'
    UNION ALL SELECT 'Anya Melfissa', 'anya-melfissa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Anya-Melfissa_list_thumb.png'
    UNION ALL SELECT 'Pavolia Reine', 'pavolia-reine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Pavolia-Reine_list_thumb.png'
    UNION ALL SELECT 'Vestia Zeta', 'vestia-zeta', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Vestia-Zeta_list_thumb.png'
    UNION ALL SELECT 'Kaela Kovalskia', 'kaela-kovalskia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kaela-Kovalskia_list_thumb.png'
    UNION ALL SELECT 'Kobo Kanaeru', 'kobo-kanaeru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kobo-Kanaeru_list_thumb.png'
    UNION ALL SELECT 'Hyakuto Kyoko', 'hyakuto-kyoko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Hyakuto-Kyoko_list_thumb.png'
    UNION ALL SELECT 'Achichi Mela', 'achichi-mela', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Achichi-Mela_list_thumb.png'
    UNION ALL SELECT 'Suzuna Tsuzuri', 'suzuna-tsuzuri', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Suzuna-Tsuzuri_list_thumb.png'
    UNION ALL SELECT 'Sorashina Sopia', 'sorashina-sopia', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Sorashina-Sopia_list_thumb.png'
) AS source
WHERE NOT EXISTS (
    SELECT 1 FROM `talent_tbl` existing
    WHERE existing.`talent_slug` = source.`talent_slug`
       OR existing.`talent_name` = source.`talent_name`
);

UPDATE `talent_tbl`
SET `talent_img` = CASE
    WHEN `talent_slug` = 'tsunomaki-watame' OR `talent_name` = 'Tsunomaki Watame'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png'
    WHEN `talent_slug` = 'momosuzu-nene' OR `talent_name` = 'Momosuzu Nene'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_talent_list_thumb.png'
    WHEN `talent_slug` = 'kazama-iroha' OR `talent_name` = 'Kazama Iroha'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_talent_list_thumb.png'
END
WHERE `talent_slug` IN ('tsunomaki-watame', 'momosuzu-nene', 'kazama-iroha')
   OR `talent_name` IN ('Tsunomaki Watame', 'Momosuzu Nene', 'Kazama Iroha');

INSERT INTO `talent_group_members_tbl` (`talent_id`, `group_id`)
SELECT talent.`talent_id`, talent_group.`group_id`
FROM (
    SELECT 'tokino-sora' AS `talent_slug`, 'gen-0' AS `group_slug`
    UNION ALL SELECT 'roboco-san', 'gen-0'
    UNION ALL SELECT 'azki', 'gen-0'
    UNION ALL SELECT 'sakura-miko', 'gen-0'
    UNION ALL SELECT 'hoshimachi-suisei', 'gen-0'
    UNION ALL SELECT 'aki-rosenthal', 'gen-1'
    UNION ALL SELECT 'akai-haato', 'gen-1'
    UNION ALL SELECT 'shirakami-fubuki', 'gen-1'
    UNION ALL SELECT 'natsuiro-matsuri', 'gen-1'
    UNION ALL SELECT 'shirakami-fubuki', 'gamers'
    UNION ALL SELECT 'ookami-mio', 'gamers'
    UNION ALL SELECT 'nekomata-okayu', 'gamers'
    UNION ALL SELECT 'inugami-korone', 'gamers'
    UNION ALL SELECT 'nakiri-ayame', 'gen-2'
    UNION ALL SELECT 'yuzuki-choco', 'gen-2'
    UNION ALL SELECT 'oozora-subaru', 'gen-2'
    UNION ALL SELECT 'usada-pekora', 'gen-3'
    UNION ALL SELECT 'shiranui-flare', 'gen-3'
    UNION ALL SELECT 'shirogane-noel', 'gen-3'
    UNION ALL SELECT 'houshou-marine', 'gen-3'
    UNION ALL SELECT 'tsunomaki-watame', 'gen-4'
    UNION ALL SELECT 'tokoyami-towa', 'gen-4'
    UNION ALL SELECT 'himemori-luna', 'gen-4'
    UNION ALL SELECT 'yukihana-lamy', 'gen-5'
    UNION ALL SELECT 'momosuzu-nene', 'gen-5'
    UNION ALL SELECT 'shishiro-botan', 'gen-5'
    UNION ALL SELECT 'omaru-polka', 'gen-5'
    UNION ALL SELECT 'la-darknesss', 'holox'
    UNION ALL SELECT 'takane-lui', 'holox'
    UNION ALL SELECT 'hakui-koyori', 'holox'
    UNION ALL SELECT 'kazama-iroha', 'holox'
    UNION ALL SELECT 'otonose-kanade', 'regloss'
    UNION ALL SELECT 'ichijou-ririka', 'regloss'
    UNION ALL SELECT 'juufuutei-raden', 'regloss'
    UNION ALL SELECT 'todoroki-hajime', 'regloss'
    UNION ALL SELECT 'isaki-riona', 'flow-glow'
    UNION ALL SELECT 'koganei-niko', 'flow-glow'
    UNION ALL SELECT 'mizumiya-su', 'flow-glow'
    UNION ALL SELECT 'rindo-chihaya', 'flow-glow'
    UNION ALL SELECT 'kikirara-vivi', 'flow-glow'
    UNION ALL SELECT 'mori-calliope', 'myth'
    UNION ALL SELECT 'takanashi-kiara', 'myth'
    UNION ALL SELECT 'ninomae-inanis', 'myth'
    UNION ALL SELECT 'watson-amelia', 'myth'
    UNION ALL SELECT 'gawr-gura', 'myth'
    UNION ALL SELECT 'irys', 'promise'
    UNION ALL SELECT 'ouro-kronii', 'promise'
    UNION ALL SELECT 'hakos-baelz', 'promise'
    UNION ALL SELECT 'shiori-novella', 'advent'
    UNION ALL SELECT 'koseki-bijou', 'advent'
    UNION ALL SELECT 'nerissa-ravencroft', 'advent'
    UNION ALL SELECT 'fuwawa-abyssgard', 'advent'
    UNION ALL SELECT 'mococo-abyssgard', 'advent'
    UNION ALL SELECT 'elizabeth-rose-bloodflame', 'justice'
    UNION ALL SELECT 'gigi-murin', 'justice'
    UNION ALL SELECT 'cecilia-immergreen', 'justice'
    UNION ALL SELECT 'raora-panthera', 'justice'
    UNION ALL SELECT 'ayunda-risu', 'id-gen-1'
    UNION ALL SELECT 'moona-hoshinova', 'id-gen-1'
    UNION ALL SELECT 'airani-iofifteen', 'id-gen-1'
    UNION ALL SELECT 'kureiji-ollie', 'id-gen-2'
    UNION ALL SELECT 'anya-melfissa', 'id-gen-2'
    UNION ALL SELECT 'pavolia-reine', 'id-gen-2'
    UNION ALL SELECT 'vestia-zeta', 'id-gen-3'
    UNION ALL SELECT 'kaela-kovalskia', 'id-gen-3'
    UNION ALL SELECT 'kobo-kanaeru', 'id-gen-3'
    UNION ALL SELECT 'hyakuto-kyoko', 'asobi-mawaritai'
    UNION ALL SELECT 'achichi-mela', 'asobi-mawaritai'
    UNION ALL SELECT 'suzuna-tsuzuri', 'asobi-mawaritai'
    UNION ALL SELECT 'sorashina-sopia', 'asobi-mawaritai'
) AS source
INNER JOIN `talent_tbl` talent ON talent.`talent_slug` = source.`talent_slug`
INNER JOIN `talent_group_tbl` talent_group ON talent_group.`group_slug` = source.`group_slug`
LEFT JOIN `talent_group_members_tbl` existing
    ON existing.`talent_id` = talent.`talent_id`
   AND existing.`group_id` = talent_group.`group_id`
WHERE existing.`talent_id` IS NULL;

COMMIT;


-- ============================================================================
-- Featured talent merchandise
-- ============================================================================

START TRANSACTION;

UPDATE `product_tbl` product
INNER JOIN `product_variants_tbl` variant ON variant.`product_id` = product.`product_id`
SET product.`product_name` = CASE variant.`sku`
        WHEN 'HV-WATAME-RESPAWN-CD' THEN 'Tsunomaki Watame "RESPAWN!!"'
        WHEN 'HV-NENE-NENENE-EP' THEN 'Momosuzu Nene 1st EP "nenene no ne!" Reissue Edition'
        WHEN 'HV-IROHA-FRIENDS-STREET' THEN 'hololive friends with u Kazama Iroha Street Outfit'
    END,
    product.`description` = CASE variant.`sku`
        WHEN 'HV-WATAME-RESPAWN-CD' THEN 'CD release featuring Tsunomaki Watame and the anime tie-in song "RESPAWN!!".'
        WHEN 'HV-NENE-NENENE-EP' THEN 'Reissue edition of Momosuzu Nene first EP, featuring five tracks.'
        WHEN 'HV-IROHA-FRIENDS-STREET' THEN 'Plush merchandise featuring Kazama Iroha in her street outfit from the hololive friends with u collection.'
    END
WHERE variant.`sku` IN ('HV-WATAME-RESPAWN-CD', 'HV-NENE-NENENE-EP', 'HV-IROHA-FRIENDS-STREET');

UPDATE `product_image_tbl` image
INNER JOIN `product_variants_tbl` variant ON variant.`product_id` = image.`product_id`
SET image.`alt_text` = CASE variant.`sku`
        WHEN 'HV-WATAME-RESPAWN-CD' THEN 'Tsunomaki Watame "RESPAWN!!" CD'
        WHEN 'HV-NENE-NENENE-EP' THEN 'Momosuzu Nene 1st EP "nenene no ne!" reissue'
        WHEN 'HV-IROHA-FRIENDS-STREET' THEN 'Kazama Iroha Street Outfit plush'
    END
WHERE variant.`sku` IN ('HV-WATAME-RESPAWN-CD', 'HV-NENE-NENENE-EP', 'HV-IROHA-FRIENDS-STREET')
  AND image.`sort_order` = 0;

INSERT INTO `product_tbl`
    (`category_id`, `product_name`, `description`, `status`, `is_preorder`, `expected_ship_date`)
SELECT category.`category_id`, source.`product_name`, source.`description`, 'active', source.`is_preorder`, source.`expected_ship_date`
FROM (
    SELECT 'Tsunomaki Watame "RESPAWN!!"' AS `product_name`,
           'CD release featuring Tsunomaki Watame and the anime tie-in song "RESPAWN!!".' AS `description`,
           'Pre-Order' AS `category_name`, 1 AS `is_preorder`, '2026-12-01' AS `expected_ship_date`
    UNION ALL SELECT 'Momosuzu Nene 1st EP "nenene no ne!" Reissue Edition',
           'Reissue edition of Momosuzu Nene first EP, featuring five tracks.',
           'Limited Drops', 0, NULL
    UNION ALL SELECT 'hololive friends with u Kazama Iroha Street Outfit',
           'Plush merchandise featuring Kazama Iroha in her street outfit from the hololive friends with u collection.',
           'Limited Drops', 0, NULL
) AS source
INNER JOIN `category_tbl` category ON category.`category_name` = source.`category_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_tbl` existing
    WHERE existing.`product_name` = source.`product_name`
);

INSERT INTO `product_variants_tbl`
    (`product_id`, `variant_name`, `sku`, `price`, `currency`, `stock_quantity`)
SELECT product.`product_id`, source.`variant_name`, source.`sku`, source.`price`, 'PHP', 10
FROM (
    SELECT 'Tsunomaki Watame "RESPAWN!!"' AS `product_name`,
           'CD Edition' AS `variant_name`, 'HV-WATAME-RESPAWN-CD' AS `sku`, 657.49 AS `price`
    UNION ALL SELECT 'Momosuzu Nene 1st EP "nenene no ne!" Reissue Edition',
           'CD Reissue', 'HV-NENE-NENENE-EP', 876.66
    UNION ALL SELECT 'hololive friends with u Kazama Iroha Street Outfit',
           'Standard Edition', 'HV-IROHA-FRIENDS-STREET', 1490.32
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_variants_tbl` existing
    WHERE existing.`sku` = source.`sku`
);

INSERT INTO `product_image_tbl` (`product_id`, `image_url`, `alt_text`, `sort_order`)
SELECT product.`product_id`, source.`image_url`, source.`alt_text`, 0
FROM (
    SELECT 'Tsunomaki Watame "RESPAWN!!"' AS `product_name`,
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/260924_Watame_tieupCDbanner_01.png?v=1789715442' AS `image_url`,
           'Tsunomaki Watame "RESPAWN!!" CD' AS `alt_text`
    UNION ALL SELECT 'Momosuzu Nene 1st EP "nenene no ne!" Reissue Edition',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/1_1b25d912-7a73-4813-872d-7d7f44523cda.png?v=1772175110',
           'Momosuzu Nene 1st EP "nenene no ne!" reissue'
    UNION ALL SELECT 'hololive friends with u Kazama Iroha Street Outfit',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/hololivefriends_vol.17_top_KazamaIrohaStreetOutfit.png?v=1731489075',
           'Kazama Iroha Street Outfit plush'
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_image_tbl` existing
    WHERE existing.`product_id` = product.`product_id` AND existing.`sort_order` = 0
);

INSERT INTO `product_talents_tbl` (`product_id`, `talent_id`)
SELECT product.`product_id`, talent.`talent_id`
FROM (
    SELECT 'Tsunomaki Watame "RESPAWN!!"' AS `product_name`, 'tsunomaki-watame' AS `talent_slug`
    UNION ALL SELECT 'Momosuzu Nene 1st EP "nenene no ne!" Reissue Edition', 'momosuzu-nene'
    UNION ALL SELECT 'hololive friends with u Kazama Iroha Street Outfit', 'kazama-iroha'
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
INNER JOIN `talent_tbl` talent ON talent.`talent_slug` = source.`talent_slug`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_talents_tbl` existing
    WHERE existing.`product_id` = product.`product_id` AND existing.`talent_id` = talent.`talent_id`
);

COMMIT;


-- ============================================================================
-- Official shop merchandise
-- ============================================================================

START TRANSACTION;

-- Product details and images are based on current official shop listings.
-- Prices are localized from JPY to PHP using the catalog's 0.3985 reference rate.
INSERT INTO `product_tbl`
    (`category_id`, `product_name`, `description`, `status`, `is_preorder`, `expected_ship_date`)
SELECT category.`category_id`, source.`product_name`, source.`description`, 'active', source.`is_preorder`, NULL
FROM (
    SELECT 'Hoshimachi Suisei Live "SuperNova: REBOOT" Blu-ray' AS `product_name`,
           'Blu-ray release of Hoshimachi Suisei Live "SuperNova: REBOOT".' AS `description`,
           'Limited Drops' AS `category_name`, 0 AS `is_preorder`
    UNION ALL SELECT 'Kureiji Ollie Birthday 2026 - Ollie''s Ramen Shop Entrance Curtain',
           'Birthday 2026 merchandise for Kureiji Ollie, featuring the Ollie''s Ramen Shop entrance curtain.',
           'Limited Drops', 0
    UNION ALL SELECT 'Otonose Kanade "At-Home Concert" Blanket',
           'Blanket merchandise from Otonose Kanade''s "At-Home Concert" collection.',
           'Limited Drops', 0
    UNION ALL SELECT 'Hakui Koyori 1st Live "Dream Spark" Acrylic Light Stick Stand',
           'Made-to-order acrylic light stick stand from Hakui Koyori''s 1st Live "Dream Spark" merchandise collection.',
           'Pre-Order', 1
    UNION ALL SELECT 'ReGLOSS AR LIVE "X-ReCHORD" Otonose Kanade Acrylic Stand',
           'Otonose Kanade acrylic stand from the ReGLOSS AR LIVE "X-ReCHORD" merchandise collection.',
           'Limited Drops', 0
) AS source
INNER JOIN `category_tbl` category ON category.`category_name` = source.`category_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_tbl` existing
    WHERE existing.`product_name` = source.`product_name`
);

INSERT INTO `product_variants_tbl`
    (`product_id`, `variant_name`, `sku`, `price`, `currency`, `stock_quantity`)
SELECT product.`product_id`, source.`variant_name`, source.`sku`, source.`price`, 'PHP', 10
FROM (
    SELECT 'Hoshimachi Suisei Live "SuperNova: REBOOT" Blu-ray' AS `product_name`,
           'Blu-ray' AS `variant_name`, 'HV-SUISEI-SUPERNOVA-REBOOT-BD' AS `sku`, 3945.15 AS `price`
    UNION ALL SELECT 'Kureiji Ollie Birthday 2026 - Ollie''s Ramen Shop Entrance Curtain',
           'Ollie''s Ramen Shop Entrance Curtain', 'HV-OLLIE-BD26-RAMEN-CURTAIN', 1753.40
    UNION ALL SELECT 'Otonose Kanade "At-Home Concert" Blanket',
           'Finest Fave Found! Kanade Extremely Exciting Blanket', 'HV-KANADE-ATHOME-BLANKET', 1753.40
    UNION ALL SELECT 'Hakui Koyori 1st Live "Dream Spark" Acrylic Light Stick Stand',
           'Acrylic Light Stick Stand', 'HV-KOYORI-DREAMSPARK-STAND', 1446.56
    UNION ALL SELECT 'ReGLOSS AR LIVE "X-ReCHORD" Otonose Kanade Acrylic Stand',
           'Acrylic Stand Otonose Kanade', 'HV-REGLOSS-XRECHORD-KANADE-STAND', 745.20
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_variants_tbl` existing
    WHERE existing.`sku` = source.`sku`
);

INSERT INTO `product_image_tbl` (`product_id`, `image_url`, `alt_text`, `sort_order`)
SELECT product.`product_id`, source.`image_url`, source.`alt_text`, 0
FROM (
    SELECT 'Hoshimachi Suisei Live "SuperNova: REBOOT" Blu-ray' AS `product_name`,
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/202600_SuperNovaREBOOT_banner_jp_1.jpg?v=1791533097' AS `image_url`,
           'Hoshimachi Suisei Live SuperNova REBOOT Blu-ray' AS `alt_text`
    UNION ALL SELECT 'Kureiji Ollie Birthday 2026 - Ollie''s Ramen Shop Entrance Curtain',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_Kureiji_20Ollie_BC_banner_JP_1005_2.png?v=1791342633',
           'Kureiji Ollie Birthday 2026 merchandise'
    UNION ALL SELECT 'Otonose Kanade "At-Home Concert" Blanket',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_OtonoseKande_ouchiLIVE_261009_ol_2.png?v=1791527958',
           'Otonose Kanade At-Home Concert merchandise'
    UNION ALL SELECT 'Hakui Koyori 1st Live "Dream Spark" Acrylic Light Stick Stand',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/261011_HakuiKoyori_1st_JP_03.png?v=1791525408',
           'Hakui Koyori Dream Spark concert merchandise'
    UNION ALL SELECT 'ReGLOSS AR LIVE "X-ReCHORD" Otonose Kanade Acrylic Stand',
           'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_ReGLOSS_AR_banner_JP_260929_ol_2.png?v=1791524436',
           'ReGLOSS X-ReCHORD concert merchandise'
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_image_tbl` existing
    WHERE existing.`product_id` = product.`product_id` AND existing.`sort_order` = 0
);

INSERT INTO `product_talents_tbl` (`product_id`, `talent_id`)
SELECT product.`product_id`, talent.`talent_id`
FROM (
    SELECT 'Hoshimachi Suisei Live "SuperNova: REBOOT" Blu-ray' AS `product_name`, 'hoshimachi-suisei' AS `talent_slug`
    UNION ALL SELECT 'Kureiji Ollie Birthday 2026 - Ollie''s Ramen Shop Entrance Curtain', 'kureiji-ollie'
    UNION ALL SELECT 'Otonose Kanade "At-Home Concert" Blanket', 'otonose-kanade'
    UNION ALL SELECT 'Hakui Koyori 1st Live "Dream Spark" Acrylic Light Stick Stand', 'hakui-koyori'
    UNION ALL SELECT 'ReGLOSS AR LIVE "X-ReCHORD" Otonose Kanade Acrylic Stand', 'otonose-kanade'
) AS source
INNER JOIN `product_tbl` product ON product.`product_name` = source.`product_name`
INNER JOIN `talent_tbl` talent ON talent.`talent_slug` = source.`talent_slug`
WHERE NOT EXISTS (
    SELECT 1 FROM `product_talents_tbl` existing
    WHERE existing.`product_id` = product.`product_id` AND existing.`talent_id` = talent.`talent_id`
);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
