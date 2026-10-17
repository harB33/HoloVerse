-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2026 at 02:36 PM
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

--
-- Dumping data for table `cart_items_tbl`
--

INSERT INTO `cart_items_tbl` (`cart_item_id`, `cart_id`, `variant_id`, `quantity`, `added_at`) VALUES
(9, 6, 1, 1, '2026-10-14 19:34:17');

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

--
-- Dumping data for table `cart_tbl`
--

INSERT INTO `cart_tbl` (`cart_id`, `user_id`, `session_token`, `status`, `created_at`, `updated_at`) VALUES
(6, NULL, '56ddf06829e2ba49ae77dd30b53cf4cd5d7b47658c9dcb3557238ad29a98cf8b', 'active', '2026-10-14 19:29:21', '2026-10-14 19:29:21'),
(7, 2, NULL, 'active', '2026-10-14 19:41:32', '2026-10-14 19:41:32');

-- --------------------------------------------------------

--
-- Table structure for table `category_tbl`
--

CREATE TABLE `category_tbl` (
  `category_id` int(10) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_tbl`
--

INSERT INTO `category_tbl` (`category_id`, `category_name`) VALUES
(1, 'Limited Drops'),
(2, 'Flash Sale'),
(3, 'Pre-Order');

-- --------------------------------------------------------

--
-- Table structure for table `flash_sale_tbl`
--

CREATE TABLE `flash_sale_tbl` (
  `flash_sale_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
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

--
-- Dumping data for table `product_image_tbl`
--

INSERT INTO `product_image_tbl` (`image_id`, `product_id`, `image_url`, `alt_text`, `sort_order`) VALUES
(1, 1, 'assets/images/suisei2.jpg', 'Hoshimachi Suisei anniversary figure', 0),
(2, 2, 'assets/images/hololive.png', 'Inugami Korone limited figure', 0),
(3, 3, 'assets/images/hololive.png', 'Gawr Gura limited figure', 0),
(4, 4, 'assets/images/hololive.png', 'Mori Calliope tour souvenir jacket', 0),
(5, 5, 'assets/images/suisei.jpg', 'Hoshimachi Suisei memorial box', 0),
(6, 6, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/1_1b25d912-7a73-4813-872d-7d7f44523cda.png?v=1772175110', 'Momosuzu Nene 1st EP \"nenene no ne!\" reissue', 0),
(7, 7, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/hololivefriends_vol.17_top_KazamaIrohaStreetOutfit.png?v=1731489075', 'Kazama Iroha Street Outfit plush', 0),
(8, 8, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/260924_Watame_tieupCDbanner_01.png?v=1789715442', 'Tsunomaki Watame \"RESPAWN!!\" CD', 0),
(9, 9, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_ReGLOSS_AR_banner_JP_260929_ol_2.png?v=1791524436', 'ReGLOSS X-ReCHORD concert merchandise', 0),
(10, 10, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_OtonoseKande_ouchiLIVE_261009_ol_2.png?v=1791527958', 'Otonose Kanade At-Home Concert merchandise', 0),
(11, 11, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/holo_Kureiji_20Ollie_BC_banner_JP_1005_2.png?v=1791342633', 'Kureiji Ollie Birthday 2026 merchandise', 0),
(12, 12, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/202600_SuperNovaREBOOT_banner_jp_1.jpg?v=1791533097', 'Hoshimachi Suisei Live SuperNova REBOOT Blu-ray', 0),
(13, 13, 'https://cdn.shopify.com/s/files/1/0529/2641/5045/files/261011_HakuiKoyori_1st_JP_03.png?v=1791525408', 'Hakui Koyori Dream Spark concert merchandise', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_talents_tbl`
--

CREATE TABLE `product_talents_tbl` (
  `product_id` int(11) NOT NULL,
  `talent_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_talents_tbl`
--

INSERT INTO `product_talents_tbl` (`product_id`, `talent_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 1),
(6, 26),
(7, 32),
(8, 22),
(9, 33),
(10, 33),
(11, 60),
(12, 1),
(13, 31);

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

--
-- Dumping data for table `product_tbl`
--

INSERT INTO `product_tbl` (`product_id`, `category_id`, `product_name`, `description`, `status`, `is_preorder`, `preorder_closes_at`, `expected_ship_date`, `created_at`, `updated_at`) VALUES
(1, 2, 'Hoshimachi Suisei 1/4 Scale Anniversary Edition', 'A limited collector release inspired by Suisei???s signature stage look, with a detailed display base and individually numbered authenticity card.', 'active', 1, NULL, '2027-04-14', '2026-10-14 18:21:54', '2026-10-14 18:21:54'),
(2, 1, 'Inugami Korone 1/4 Scale ???Doog Champion??? Figure', 'Batch ETA: November 2026. Includes signed cover card.', 'active', 1, NULL, '2026-11-01', '2026-10-14 18:21:54', '2026-10-14 18:21:54'),
(3, 3, 'Gawr Gura 1/7 Scale ???Apex Predator??? Figure', 'Sculpted by Design COCO. Removable hoodie feature.', 'active', 1, NULL, '2027-04-14', '2026-10-14 18:21:54', '2026-10-14 18:21:54'),
(4, 1, 'Mori Calliope ???SINDERELLA??? Tour Souvenir Sukajan', 'Reversible high-thread embroidery. Limited run of 750.', 'active', 0, NULL, NULL, '2026-10-14 18:21:54', '2026-10-14 18:21:54'),
(5, 3, 'Hoshimachi Suisei 5th Anniversary Memorial Box', 'Complete vinyl set, bespoke star pin, and replica beret.', 'active', 1, NULL, '2027-03-14', '2026-10-14 18:21:54', '2026-10-14 18:21:54'),
(6, 1, 'Momosuzu Nene 1st EP \"nenene no ne!\" Reissue Edition', 'Reissue edition of Momosuzu Nene first EP, featuring five tracks.', 'active', 0, NULL, NULL, '2026-10-17 11:56:35', '2026-10-17 11:59:07'),
(7, 1, 'hololive friends with u Kazama Iroha Street Outfit', 'Plush merchandise featuring Kazama Iroha in her street outfit from the hololive friends with u collection.', 'active', 0, NULL, NULL, '2026-10-17 11:56:35', '2026-10-17 11:56:35'),
(8, 3, 'Tsunomaki Watame \"RESPAWN!!\"', 'CD release featuring Tsunomaki Watame and the anime tie-in song \"RESPAWN!!\".', 'active', 1, NULL, '2026-12-01', '2026-10-17 11:56:35', '2026-10-17 11:59:07'),
(9, 1, 'ReGLOSS AR LIVE \"X-ReCHORD\" Otonose Kanade Acrylic Stand', 'Otonose Kanade acrylic stand from the ReGLOSS AR LIVE \"X-ReCHORD\" merchandise collection.', 'active', 0, NULL, NULL, '2026-10-17 15:58:23', '2026-10-17 15:58:23'),
(10, 1, 'Otonose Kanade \"At-Home Concert\" Blanket', 'Blanket merchandise from Otonose Kanade\'s \"At-Home Concert\" collection.', 'active', 0, NULL, NULL, '2026-10-17 15:58:23', '2026-10-17 15:58:23'),
(11, 1, 'Kureiji Ollie Birthday 2026 - Ollie\'s Ramen Shop Entrance Curtain', 'Birthday 2026 merchandise for Kureiji Ollie, featuring the Ollie\'s Ramen Shop entrance curtain.', 'active', 0, NULL, NULL, '2026-10-17 15:58:23', '2026-10-17 15:58:23'),
(12, 1, 'Hoshimachi Suisei Live \"SuperNova: REBOOT\" Blu-ray', 'Blu-ray release of Hoshimachi Suisei Live \"SuperNova: REBOOT\".', 'active', 0, NULL, NULL, '2026-10-17 15:58:23', '2026-10-17 15:58:23'),
(13, 3, 'Hakui Koyori 1st Live \"Dream Spark\" Acrylic Light Stick Stand', 'Made-to-order acrylic light stick stand from Hakui Koyori\'s 1st Live \"Dream Spark\" merchandise collection.', 'active', 1, NULL, NULL, '2026-10-17 15:58:23', '2026-10-17 15:58:23');

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

--
-- Dumping data for table `product_variants_tbl`
--

INSERT INTO `product_variants_tbl` (`variant_id`, `product_id`, `variant_name`, `sku`, `price`, `compare_at_price`, `currency`, `stock_quantity`) VALUES
(1, 1, '1/4 Scale', 'HV-SUISEI-ANNIV-14', 14104.83, 17631.04, 'PHP', 22),
(2, 2, '1/4 Scale', 'HV-KORONE-DOOG-14', 18197.75, NULL, 'PHP', 60),
(3, 3, '1/7 Scale', 'HV-GURA-APEX-17', 13538.12, NULL, 'PHP', 60),
(4, 4, 'Tour Edition', 'HV-CALLIOPE-SINDERELLA', 11963.92, NULL, 'PHP', 413),
(5, 5, 'Memorial Box', 'HV-SUISEI-MEMORIAL-05', 11019.40, NULL, 'PHP', 360),
(6, 6, 'CD Reissue', 'HV-NENE-NENENE-EP', 876.66, NULL, 'PHP', 10),
(7, 7, 'Standard Edition', 'HV-IROHA-FRIENDS-STREET', 1490.32, NULL, 'PHP', 10),
(8, 8, 'CD Edition', 'HV-WATAME-RESPAWN-CD', 657.49, NULL, 'PHP', 10),
(9, 9, 'Acrylic Stand Otonose Kanade', 'HV-REGLOSS-XRECHORD-KANADE-STAND', 745.20, NULL, 'PHP', 10),
(10, 10, 'Finest Fave Found! Kanade Extremely Exciting Blanket', 'HV-KANADE-ATHOME-BLANKET', 1753.40, NULL, 'PHP', 10),
(11, 11, 'Ollie\'s Ramen Shop Entrance Curtain', 'HV-OLLIE-BD26-RAMEN-CURTAIN', 1753.40, NULL, 'PHP', 10),
(12, 12, 'Blu-ray', 'HV-SUISEI-SUPERNOVA-REBOOT-BD', 3945.15, NULL, 'PHP', 10),
(13, 13, 'Acrylic Light Stick Stand', 'HV-KOYORI-DREAMSPARK-STAND', 1446.56, NULL, 'PHP', 10);

-- --------------------------------------------------------

--
-- Table structure for table `talent_group_members_tbl`
--

CREATE TABLE `talent_group_members_tbl` (
  `talent_id` int(10) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `talent_group_members_tbl`
--

INSERT INTO `talent_group_members_tbl` (`talent_id`, `group_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 3),
(5, 5),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 6),
(11, 6),
(12, 2),
(12, 6),
(13, 6),
(14, 2),
(15, 2),
(16, 7),
(17, 7),
(18, 7),
(19, 5),
(20, 5),
(21, 5),
(22, 8),
(23, 8),
(24, 8),
(25, 9),
(26, 9),
(27, 9),
(28, 9),
(29, 10),
(30, 10),
(31, 10),
(32, 10),
(33, 11),
(34, 11),
(35, 11),
(36, 11),
(37, 12),
(38, 12),
(39, 12),
(40, 12),
(41, 12),
(42, 3),
(43, 3),
(44, 3),
(45, 4),
(46, 4),
(47, 4),
(48, 13),
(49, 13),
(50, 13),
(51, 13),
(52, 13),
(53, 14),
(54, 14),
(55, 14),
(56, 14),
(57, 15),
(58, 15),
(59, 15),
(60, 16),
(61, 16),
(62, 16),
(63, 17),
(64, 17),
(65, 17),
(66, 18),
(67, 18),
(68, 18),
(69, 18);

-- --------------------------------------------------------

--
-- Table structure for table `talent_group_tbl`
--

CREATE TABLE `talent_group_tbl` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `talent_group_tbl`
--

INSERT INTO `talent_group_tbl` (`group_id`, `group_name`, `group_slug`) VALUES
(1, 'Generation 0', 'gen-0'),
(2, 'Gamers', 'gamers'),
(3, 'Myth', 'myth'),
(4, 'Promise', 'promise'),
(5, 'Generation 3', 'gen-3'),
(6, 'Generation 1', 'gen-1'),
(7, 'Generation 2', 'gen-2'),
(8, 'Generation 4', 'gen-4'),
(9, 'Generation 5', 'gen-5'),
(10, 'HoloX', 'holox'),
(11, 'ReGLOSS', 'regloss'),
(12, 'FLOW GLOW', 'flow-glow'),
(13, 'Advent', 'advent'),
(14, 'Justice', 'justice'),
(15, 'Indonesia Gen 1', 'id-gen-1'),
(16, 'Indonesia Gen 2', 'id-gen-2'),
(17, 'Indonesia Gen 3', 'id-gen-3'),
(18, 'Asobi Mawaritai', 'asobi-mawaritai');

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

--
-- Dumping data for table `talent_tbl`
--

INSERT INTO `talent_tbl` (`talent_id`, `talent_name`, `talent_slug`, `talent_img`) VALUES
(1, 'Hoshimachi Suisei', 'hoshimachi-suisei', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png'),
(2, 'Inugami Korone', 'inugami-korone', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_list_thumb.png'),
(3, 'Gawr Gura', 'gawr-gura', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Gawr-Gura_list_thumb.png'),
(4, 'Mori Calliope', 'mori-calliope', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Mori-Calliope_list_thumb.png'),
(5, 'Usada Pekora', 'usada-pekora', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Usada-Pekora_list_thumb.png'),
(6, 'Tokino Sora', 'tokino-sora', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokino-Sora_list_thumb.png'),
(7, 'Roboco-san', 'roboco-san', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Robocosan_list_thumb.png'),
(8, 'AZKi', 'azki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/AZKi_list_thumb.png'),
(9, 'Sakura Miko', 'sakura-miko', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Sakura-Miko_list_thumb.png'),
(10, 'Aki Rosenthal', 'aki-rosenthal', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Aki-Rosenthal_list_thumb.png'),
(11, 'Akai Haato', 'akai-haato', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Akai-Haato_list_thumb.png'),
(12, 'Shirakami Fubuki', 'shirakami-fubuki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirakami-Fubuki_list_thumb.png'),
(13, 'Natsuiro Matsuri', 'natsuiro-matsuri', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Natsuiro-Matsuri_list_thumb.png'),
(14, 'Ookami Mio', 'ookami-mio', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Ookami-Mio_thumb.png'),
(15, 'Nekomata Okayu', 'nekomata-okayu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nekomata-Okayu_list_thumb.png'),
(16, 'Nakiri Ayame', 'nakiri-ayame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nakiri-Ayame_list_thumb.png'),
(17, 'Yuzuki Choco', 'yuzuki-choco', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yuzuki-Choco_list_thumb.png'),
(18, 'Oozora Subaru', 'oozora-subaru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Oozora-Subaru_list_thumb.png'),
(19, 'Shiranui Flare', 'shiranui-flare', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shiranui-Flare_list_thumb.png'),
(20, 'Shirogane Noel', 'shirogane-noel', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirogane-Noel_list_thumb.png'),
(21, 'Houshou Marine', 'houshou-marine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Houshou-Marine_list_thumb.png'),
(22, 'Tsunomaki Watame', 'tsunomaki-watame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_list_thumb.png'),
(23, 'Tokoyami Towa', 'tokoyami-towa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokoyami-Towa_list_thumb.png'),
(24, 'Himemori Luna', 'himemori-luna', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Himemori-Luna_list_thumb.png'),
(25, 'Yukihana Lamy', 'yukihana-lamy', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yukihana-Lamy_list_thumb.png'),
(26, 'Momosuzu Nene', 'momosuzu-nene', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_list_thumb.png'),
(27, 'Shishiro Botan', 'shishiro-botan', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Shishiro-Botan_list_thumb.png'),
(28, 'Omaru Polka', 'omaru-polka', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Omaru-Polka_list_thumb.png'),
(29, 'La+ Darknesss', 'la-darknesss', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/La-Darknesss_list_thumb.png'),
(30, 'Takane Lui', 'takane-lui', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takane-Lui_list_thumb.png'),
(31, 'Hakui Koyori', 'hakui-koyori', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakui-Koyori_list_thumb.png'),
(32, 'Kazama Iroha', 'kazama-iroha', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_list_thumb.png'),
(33, 'Otonose Kanade', 'otonose-kanade', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Otonose-Kanade_list_thumb.png'),
(34, 'Ichijou Ririka', 'ichijou-ririka', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Ichijou-Ririka_list_thumb.png'),
(35, 'Juufuutei Raden', 'juufuutei-raden', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Juufuutei-Raden_list_thumb.png'),
(36, 'Todoroki Hajime', 'todoroki-hajime', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Todoroki-Hajime_list_thumb.png'),
(37, 'Isaki Riona', 'isaki-riona', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Isaki-Riona_list_thumb.png'),
(38, 'Koganei Niko', 'koganei-niko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Koganei-Niko_list_thumb.png'),
(39, 'Mizumiya Su', 'mizumiya-su', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Mizumiya-Su_list_thumb.png'),
(40, 'Rindo Chihaya', 'rindo-chihaya', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Rindo-Chihaya_list_thumb.png'),
(41, 'Kikirara Vivi', 'kikirara-vivi', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Kikirara-Vivi_list_thumb.png'),
(42, 'Takanashi Kiara', 'takanashi-kiara', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takanashi-Kiara_list_thumb.png'),
(43, 'Ninomae Ina’nis', 'ninomae-inanis', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ninomae-Inanis_list_thumb.png'),
(44, 'Watson Amelia', 'watson-amelia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Watson-Amelia_list_thumb.png'),
(45, 'IRyS', 'irys', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/IRyS_list_thumb.png'),
(46, 'Ouro Kronii', 'ouro-kronii', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ouro-Kronii_list_thumb.png'),
(47, 'Hakos Baelz', 'hakos-baelz', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakos-Baelz_list_thumb.png'),
(48, 'Shiori Novella', 'shiori-novella', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Shiori-Novella_list_thumb.png'),
(49, 'Koseki Bijou', 'koseki-bijou', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Koseki-Bijou_list_thumb.png'),
(50, 'Nerissa Ravencroft', 'nerissa-ravencroft', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Nerissa-Ravencroft_list_thumb.png'),
(51, 'Fuwawa Abyssgard', 'fuwawa-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Fuwawa-Abyssgard_list_thumb.png'),
(52, 'Mococo Abyssgard', 'mococo-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Mococo-Abyssgard_list_thumb.png'),
(53, 'Elizabeth Rose Bloodflame', 'elizabeth-rose-bloodflame', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Elizabeth-Rose-Bloodflame_list_thumb.png'),
(54, 'Gigi Murin', 'gigi-murin', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Gigi-Murin_list_thumb.png'),
(55, 'Cecilia Immergreen', 'cecilia-immergreen', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Cecilia-Immergreen_list_thumb.png'),
(56, 'Raora Panthera', 'raora-panthera', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Raora-Panthera_list_thumb.png'),
(57, 'Ayunda Risu', 'ayunda-risu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ayunda-Risu_list_thumb.png'),
(58, 'Moona Hoshinova', 'moona-hoshinova', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Moona-Hoshinova_list_thumb.png'),
(59, 'Airani Iofifteen', 'airani-iofifteen', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Airani-Iofifteen_list_thumb.png'),
(60, 'Kureiji Ollie', 'kureiji-ollie', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kureiji-Ollie_list_thumb.png'),
(61, 'Anya Melfissa', 'anya-melfissa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Anya-Melfissa_list_thumb.png'),
(62, 'Pavolia Reine', 'pavolia-reine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Pavolia-Reine_list_thumb.png'),
(63, 'Vestia Zeta', 'vestia-zeta', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Vestia-Zeta_list_thumb.png'),
(64, 'Kaela Kovalskia', 'kaela-kovalskia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kaela-Kovalskia_list_thumb.png'),
(65, 'Kobo Kanaeru', 'kobo-kanaeru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kobo-Kanaeru_list_thumb.png'),
(66, 'Hyakuto Kyoko', 'hyakuto-kyoko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Hyakuto-Kyoko_list_thumb.png'),
(67, 'Achichi Mela', 'achichi-mela', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Achichi-Mela_list_thumb.png'),
(68, 'Suzuna Tsuzuri', 'suzuna-tsuzuri', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Suzuna-Tsuzuri_list_thumb.png'),
(69, 'Sorashina Sopia', 'sorashina-sopia', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Sorashina-Sopia_list_thumb.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_tbl`
--

CREATE TABLE `user_tbl` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_role` varchar(5) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tbl`
--

INSERT INTO `user_tbl` (`user_id`, `username`, `password_hash`, `email`, `user_role`, `created_at`, `updated_at`) VALUES
(2, 'Jomari Wamil', '$2y$10$LxqIf2ROEDL.FddX3y5qVe1tRThvpS4sLYNL2M28OAodIC97VD00m', 'jomariwamil1012@gmail.com', 'admin', '2026-10-14 11:41:24', '2026-10-14 11:41:24'),
(3, 'alt_jomari2', '$2y$10$BZQZFX8idxOyz/j.UJLEduBagMhlXDsEOdQiWX0uU2w3ub1xYc4LK', 'shishironchi@gmail.com', 'user', '2026-10-17 08:42:16', '2026-10-17 08:42:16');

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
-- Indexes for table `flash_sale_tbl`
--
ALTER TABLE `flash_sale_tbl`
  ADD PRIMARY KEY (`flash_sale_id`),
  ADD KEY `idx_flash_sale_variant_window` (`variant_id`,`starts_at`,`ends_at`),
  ADD KEY `idx_flash_sale_status_window` (`status`,`starts_at`,`ends_at`),
  ADD KEY `fk_flash_sale_creator` (`created_by`);

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
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart_tbl`
--
ALTER TABLE `cart_tbl`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `category_tbl`
--
ALTER TABLE `category_tbl`
  MODIFY `category_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `flash_sale_tbl`
--
ALTER TABLE `flash_sale_tbl`
  MODIFY `flash_sale_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_tbl`
--
ALTER TABLE `product_tbl`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_variants_tbl`
--
ALTER TABLE `product_variants_tbl`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `talent_group_tbl`
--
ALTER TABLE `talent_group_tbl`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `talent_tbl`
--
ALTER TABLE `talent_tbl`
  MODIFY `talent_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `flash_sale_tbl`
--
ALTER TABLE `flash_sale_tbl`
  ADD CONSTRAINT `fk_flash_sale_creator` FOREIGN KEY (`created_by`) REFERENCES `user_tbl` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_flash_sale_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_tbl` (`variant_id`) ON DELETE CASCADE;

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
