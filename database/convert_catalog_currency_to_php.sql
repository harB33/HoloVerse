-- Conversion rates dated 2026-10-16: 1 USD = 62.968 PHP; 1 JPY = 0.39848 PHP.
-- Monetary values are rounded to two decimal places. Running this repeatedly is safe.
ALTER TABLE `product_variants_tbl`
    MODIFY `currency` char(3) NOT NULL DEFAULT 'PHP';

START TRANSACTION;

UPDATE `product_variants_tbl`
SET `price` = ROUND(`price` * CASE `currency`
        WHEN 'USD' THEN 62.968
        WHEN 'JPY' THEN 0.39848
    END, 2),
    `compare_at_price` = CASE
        WHEN `compare_at_price` IS NULL THEN NULL
        ELSE ROUND(`compare_at_price` * CASE `currency`
            WHEN 'USD' THEN 62.968
            WHEN 'JPY' THEN 0.39848
        END, 2)
    END,
    `currency` = 'PHP'
WHERE `currency` IN ('USD', 'JPY');

UPDATE `order_items_tbl` item
INNER JOIN `order_tbl` orders ON orders.`order_id` = item.`order_id`
SET item.`unit_price` = ROUND(item.`unit_price` * CASE orders.`currency`
        WHEN 'USD' THEN 62.968
        WHEN 'JPY' THEN 0.39848
    END, 2)
WHERE orders.`currency` IN ('USD', 'JPY');

UPDATE `order_tbl`
SET `shipping_fee` = ROUND(`shipping_fee` * CASE `currency`
        WHEN 'USD' THEN 62.968
        WHEN 'JPY' THEN 0.39848
    END, 2),
    `tax` = ROUND(`tax` * CASE `currency`
        WHEN 'USD' THEN 62.968
        WHEN 'JPY' THEN 0.39848
    END, 2),
    `currency` = 'PHP'
WHERE `currency` IN ('USD', 'JPY');

COMMIT;
