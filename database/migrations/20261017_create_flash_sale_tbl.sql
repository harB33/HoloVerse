CREATE TABLE IF NOT EXISTS `flash_sale_tbl` (
  `flash_sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `variant_id` int(11) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`flash_sale_id`),
  KEY `idx_flash_sale_variant_window` (`variant_id`, `starts_at`, `ends_at`),
  KEY `idx_flash_sale_status_window` (`status`, `starts_at`, `ends_at`),
  CONSTRAINT `fk_flash_sale_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants_tbl` (`variant_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_flash_sale_creator` FOREIGN KEY (`created_by`) REFERENCES `user_tbl` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
