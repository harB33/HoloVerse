<?php

function store_flash_sale_table_exists(DatabaseConnection $conn): bool
{
    $statement = $conn->prepare(
        "SELECT EXISTS (
             SELECT 1
         FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'flash_sale_tbl'
         ) AS table_exists"
    );
    $statement->execute();
    $tableExists = $statement->get_result()->fetch_assoc()['table_exists'];
    $exists = in_array($tableExists, [true, 't', 1, '1'], true);
    $statement->close();

    return $exists;
}

function store_flash_sale_rows(DatabaseConnection $conn, string $state, DateTimeImmutable $now): array
{
    $nowValue = $now->format('Y-m-d H:i:s');
    $availableStock = store_available_stock_sql();

    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder,
                   c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text,
                   fs.flash_sale_id, fs.starts_at, fs.ends_at
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            LEFT JOIN flash_sale_tbl fs ON fs.variant_id = v.variant_id AND fs.status = 'scheduled'
            WHERE p.status = 'active'
              AND v.compare_at_price IS NOT NULL
              AND v.compare_at_price > v.price";

    if ($state === 'live') {
        $sql .= " AND {$availableStock} > 0
                  AND (
                      (fs.flash_sale_id IS NULL AND NOT EXISTS (
                          SELECT 1 FROM flash_sale_tbl scheduled
                          WHERE scheduled.variant_id = v.variant_id AND scheduled.status = 'scheduled'
                      ))
                      OR (fs.starts_at <= ? AND fs.ends_at > ?)
                  )
                  ORDER BY fs.ends_at IS NULL, fs.ends_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $nowValue);
    } elseif ($state === 'starting') {
        $endOfWindow = $now->modify('+1 hour')->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at > ? AND fs.starts_at <= ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $endOfWindow);
    } elseif ($state === 'tomorrow') {
        $tomorrow = $now->modify('tomorrow')->setTime(0, 0)->format('Y-m-d H:i:s');
        $dayAfter = $now->modify('+2 days')->setTime(0, 0)->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at >= ? AND fs.starts_at < ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $tomorrow, $dayAfter);
    } elseif ($state === 'archive') {
        $sql .= ' AND fs.ends_at <= ? ORDER BY fs.ends_at DESC, p.product_id DESC';
        $statement = $conn->prepare($sql);
        $statement->bind_param('s', $nowValue);
    } else {
        throw new InvalidArgumentException('Choose a valid flash sale state.');
    }

    $statement->execute();
    $rows = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    return $rows;
}

