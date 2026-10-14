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
(1, 1, '1/4 Scale', 'HV-SUISEI-ANNIV-14', 224.00, 280.00, 'USD', 22),
(2, 2, '1/4 Scale', 'HV-KORONE-DOOG-14', 289.00, NULL, 'USD', 60),
(3, 3, '1/7 Scale', 'HV-GURA-APEX-17', 215.00, NULL, 'USD', 60),
(4, 4, 'Tour Edition', 'HV-CALLIOPE-SINDERELLA', 190.00, NULL, 'USD', 413),
(5, 5, 'Memorial Box', 'HV-SUISEI-MEMORIAL-05', 175.00, NULL, 'USD', 360);

INSERT INTO `product_image_tbl` (`image_id`, `product_id`, `image_url`, `alt_text`, `sort_order`) VALUES
(1, 1, 'assets/images/suisei2.jpg', 'Hoshimachi Suisei anniversary figure', 0),
(2, 2, 'assets/images/hololive.png', 'Inugami Korone limited figure', 0),
(3, 3, 'assets/images/hololive.png', 'Gawr Gura limited figure', 0),
(4, 4, 'assets/images/hololive.png', 'Mori Calliope tour souvenir jacket', 0),
(5, 5, 'assets/images/suisei.jpg', 'Hoshimachi Suisei memorial box', 0);

INSERT INTO `product_talents_tbl` (`product_id`, `talent_id`) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 1);

COMMIT;
