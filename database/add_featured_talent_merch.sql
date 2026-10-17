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
