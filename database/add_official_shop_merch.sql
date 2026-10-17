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
