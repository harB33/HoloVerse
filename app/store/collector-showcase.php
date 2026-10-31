<?php

function store_collector_favorite_ids(DatabaseConnection $conn, int $userId): array
{
    if ($userId < 1) {
        return [];
    }

    $statement = $conn->prepare(
        'SELECT product_id FROM collector_favorites_tbl WHERE user_id = ?'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $favoriteIds = array_map(
        'intval',
        $statement->get_result()->fetch_all(PDO::FETCH_COLUMN)
    );
    $statement->close();

    return $favoriteIds;
}

function store_collector_favorite_product_count(DatabaseConnection $conn, int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $statement = $conn->prepare(
        "SELECT COUNT(DISTINCT p.product_id) AS product_count
         FROM collector_favorites_tbl f
         INNER JOIN product_tbl p ON p.product_id = f.product_id
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE f.user_id = ? AND p.status = 'active'"
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $productCount = (int) $statement->get_result()->fetch_assoc()['product_count'];
    $statement->close();

    return $productCount;
}

function store_collector_favorite_products(DatabaseConnection $conn, int $userId, int $limit, int $offset): array
{
    if ($userId < 1 || $limit < 1 || $offset < 0) {
        return [];
    }

    $availableStock = store_available_stock_sql();
    $statement = $conn->prepare(
        "SELECT DISTINCT ON (p.product_id)
                p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                c.category_id, c.category_name, v.variant_id, v.variant_name, v.price, v.currency,
                {$availableStock} AS stock_quantity, i.image_url, i.alt_text,
                (SELECT t.talent_name
                 FROM product_talents_tbl pt
                 INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
                 WHERE pt.product_id = p.product_id
                 ORDER BY t.talent_id
                 LIMIT 1) AS talent_name
         FROM collector_favorites_tbl f
         INNER JOIN product_tbl p ON p.product_id = f.product_id
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
         WHERE f.user_id = ? AND p.status = 'active'
         ORDER BY p.product_id, ({$availableStock} > 0) DESC, v.variant_id, i.image_id
         LIMIT ? OFFSET ?"
    );
    $statement->bind_param('iii', $userId, $limit, $offset);
    $statement->execute();
    $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    return $products;
}

function store_collector_purchase_rows(DatabaseConnection $conn, int $userId): array
{
    $statement = $conn->prepare(
        "SELECT oi.product_name_snapshot, oi.variant_name_snapshot, oi.quantity,
                o.order_number, o.created_at, o.status, v.product_id,
                i.image_url, i.alt_text
         FROM order_tbl o
         INNER JOIN order_items_tbl oi ON oi.order_id = o.order_id
         LEFT JOIN product_variants_tbl v ON v.variant_id = oi.variant_id
         LEFT JOIN LATERAL (
             SELECT image_url, alt_text
             FROM product_image_tbl
             WHERE product_id = v.product_id
             ORDER BY sort_order, image_id
             LIMIT 1
         ) i ON TRUE
         WHERE o.user_id = ? AND o.status NOT IN ('cancelled', 'refunded')
         ORDER BY o.created_at DESC, oi.order_item_id DESC"
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $purchases = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    return $purchases;
}
