<?php

function store_product_url(string $productName): string
{
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($productName));
    if (!is_string($slug) || $slug === '') {
        throw new InvalidArgumentException('A product name is required to build its URL.');
    }
    $slug = trim($slug, '-');
    if ($slug === '') {
        throw new InvalidArgumentException('A product name must contain letters or numbers to build its URL.');
    }

    return '/product/' . rawurlencode(strtolower($slug));
}

function store_category_slug(string $categoryName): string
{
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($categoryName));
    if (!is_string($slug)) {
        throw new InvalidArgumentException('Unable to create a category URL slug.');
    }

    return strtolower(trim($slug, '-'));
}

function store_product_rows(DatabaseConnection $conn, string $where = '', string $order = 'p.created_at DESC', int $limit = 0): array
{
    $availableStock = store_available_stock_sql();
    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                   c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            WHERE p.status = 'active' " . $where . "
            ORDER BY " . $order . ($limit > 0 ? ' LIMIT ' . $limit : '');
    $result = $conn->query($sql);

    return $result->fetch_all(PDO::FETCH_ASSOC);
}

function store_talent_filter_group_slugs_sql(): string
{
    return implode(
        ', ',
        array_map(static fn (string $slug): string => "'" . str_replace("'", "''", $slug) . "'", STORE_TALENT_FILTER_GROUP_SLUGS)
    );
}

function store_catalog_product_rows(DatabaseConnection $conn, int $limit = 0, int $offset = 0, string $search = '', string $groupSlug = '', int $categoryId = 0, bool $randomize = false, bool $includeImageGallery = false): array
{
    $availableStock = store_available_stock_sql();
    $eligibleGroupSlugs = store_talent_filter_group_slugs_sql();
    $imageGallerySelect = $includeImageGallery
        ? ", (SELECT json_agg(json_build_object('image_url', gallery.image_url, 'alt_text', gallery.alt_text) ORDER BY gallery.sort_order, gallery.image_id)
            FROM product_image_tbl gallery
            WHERE gallery.product_id = p.product_id) AS gallery_images"
        : '';
    $groupWhere = in_array($groupSlug, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? " AND tg.group_slug = '" . str_replace("'", "''", $groupSlug) . "'"
        : '';
    $categoryWhere = $categoryId > 0 ? ' AND c.category_id = ' . $categoryId : '';
    $searchWhere = '';
    if ($search !== '') {
        $searchWhere = " AND (
            p.product_name ILIKE ?
            OR p.description ILIKE ?
            OR c.category_name ILIKE ?
            OR v.variant_name ILIKE ?
            OR EXISTS (
                SELECT 1
                FROM product_talents_tbl search_pt
                INNER JOIN talent_tbl search_t ON search_t.talent_id = search_pt.talent_id
                WHERE search_pt.product_id = p.product_id AND search_t.talent_name ILIKE ?
            )
        )";
    }
    $sql = "SELECT catalog_products.*
            FROM (
                SELECT DISTINCT ON (p.product_id)
                       p.product_id, p.product_name, p.description, p.is_preorder,
                       c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                       v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text{$imageGallerySelect}
                FROM product_tbl p
                INNER JOIN category_tbl c ON c.category_id = p.category_id
                INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
                LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
                WHERE p.status = 'active'
                  AND EXISTS (
                      SELECT 1
                      FROM product_talents_tbl pt
                      INNER JOIN talent_group_members_tbl gm ON gm.talent_id = pt.talent_id
                      INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
                      WHERE pt.product_id = p.product_id AND tg.group_slug IN ({$eligibleGroupSlugs}){$groupWhere}
                  ){$categoryWhere}{$searchWhere}
                ORDER BY p.product_id DESC, {$availableStock} DESC, v.variant_id ASC
            ) AS catalog_products
            ORDER BY (catalog_products.stock_quantity > 0) DESC, " . ($randomize ? 'RANDOM()' : 'catalog_products.product_id DESC');
    if ($search !== '') {
        $pattern = '%' . $search . '%';
    }
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset);
    }
    if ($search !== '') {
        $statement = $conn->prepare($sql);
        $statement->bind_param('sssss', $pattern, $pattern, $pattern, $pattern, $pattern);
        $statement->execute();
        $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();

        return $products;
    }

    return $conn->query($sql)->fetch_all(PDO::FETCH_ASSOC);
}

function store_catalog_product_count(DatabaseConnection $conn, string $search = '', string $groupSlug = '', int $categoryId = 0): int
{
    $eligibleGroupSlugs = store_talent_filter_group_slugs_sql();
    $groupWhere = in_array($groupSlug, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? " AND tg.group_slug = '" . str_replace("'", "''", $groupSlug) . "'"
        : '';
    $categoryWhere = $categoryId > 0 ? ' AND c.category_id = ' . $categoryId : '';
    $searchWhere = '';
    if ($search !== '') {
        $searchWhere = " AND (
            p.product_name ILIKE ?
            OR p.description ILIKE ?
            OR c.category_name ILIKE ?
            OR v.variant_name ILIKE ?
            OR EXISTS (
                SELECT 1
                FROM product_talents_tbl search_pt
                INNER JOIN talent_tbl search_t ON search_t.talent_id = search_pt.talent_id
                WHERE search_pt.product_id = p.product_id AND search_t.talent_name ILIKE ?
            )
        )";
    }
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS product_count
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            WHERE p.status = 'active'
              AND EXISTS (
                  SELECT 1
                  FROM product_talents_tbl pt
                  INNER JOIN talent_group_members_tbl gm ON gm.talent_id = pt.talent_id
                  INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
                  WHERE pt.product_id = p.product_id AND tg.group_slug IN ({$eligibleGroupSlugs}){$groupWhere}
              ){$categoryWhere}{$searchWhere}";
    if ($search !== '') {
        $pattern = '%' . $search . '%';
        $statement = $conn->prepare($sql);
        $statement->bind_param('sssss', $pattern, $pattern, $pattern, $pattern, $pattern);
        $statement->execute();
        $result = $statement->get_result();
        $count = (int) ($result->fetch_assoc()['product_count'] ?? 0);
        $statement->close();

        return $count;
    }

    $result = $conn->query($sql);
    return (int) ($result->fetch_assoc()['product_count'] ?? 0);
}
