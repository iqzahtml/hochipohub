<?php
/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - AJAX LOAD PRODUCTS
|--------------------------------------------------------------------------
| File: ajax/load_products.php
| Supports: category_id, vendor_id, keyword, sort, limit, offset
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/database/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function lpJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function lpImageUrl($image): string
{
    $image = trim((string) $image);
    if ($image === '') return '';
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) return $image;
    if (str_starts_with($image, 'uploads/')) return BASE_URL . ltrim($image, '/');
    return BASE_URL . 'uploads/products/' . rawurlencode(basename($image));
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    lpJson(['success' => false, 'message' => 'Invalid request method.', 'products' => []], 405);
}

$db = getDB();
if (!($db instanceof PDO)) {
    lpJson(['success' => false, 'message' => 'Database connection is not available.', 'products' => []], 500);
}

$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
if ($categoryId === false || $categoryId === null || $categoryId <= 0) $categoryId = null;

$vendorId = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT);
if ($vendorId === false || $vendorId === null || $vendorId <= 0) $vendorId = null;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'latest'));
$allowedSorts = ['latest', 'price_low', 'price_high', 'name', 'oldest'];
if (!in_array($sort, $allowedSorts, true)) $sort = 'latest';

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
if ($limit === false || $limit === null || $limit < 1) $limit = 100;
$limit = max(1, min($limit, 100));

$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT);
if ($offset === false || $offset === null || $offset < 0) $offset = 0;

$orderBy = match ($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.product_name ASC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC',
};

try {
    $where = "
        WHERE p.stock_quantity > 0
        AND LOWER(p.status) IN ('available', 'active')
        AND LOWER(v.approval_status) = 'approved'
        AND LOWER(u.status) = 'active'
    ";
    $params = [];

    if ($categoryId !== null) {
        $where .= " AND p.category_id = ? ";
        $params[] = $categoryId;
    }
    if ($vendorId !== null) {
        $where .= " AND p.vendor_id = ? ";
        $params[] = $vendorId;
    }
    if ($keyword !== '') {
        $search = '%' . $keyword . '%';
        $where .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR v.business_name LIKE ? OR c.category_name LIKE ?) ";
        array_push($params, $search, $search, $search, $search);
    }

    $sql = "
        SELECT
            p.product_id, p.product_name, p.description, p.price,
            p.stock_quantity, p.image, p.status, p.vendor_id,
            p.category_id, p.created_at,
            v.business_name, v.business_logo,
            c.category_name
        FROM products p
        INNER JOIN vendors v ON p.vendor_id = v.vendor_id
        INNER JOIN users u ON v.user_id = u.user_id
        INNER JOIN categories c ON p.category_id = c.category_id
        {$where}
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "
        SELECT COUNT(*)
        FROM products p
        INNER JOIN vendors v ON p.vendor_id = v.vendor_id
        INNER JOIN users u ON v.user_id = u.user_id
        INNER JOIN categories c ON p.category_id = c.category_id
        {$where}
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $formattedProducts = [];
    foreach ($products as $product) {
        $price = (float) $product['price'];
        $formattedProducts[] = [
            'product_id' => (int) $product['product_id'],
            'product_name' => (string) $product['product_name'],
            'description' => (string) ($product['description'] ?? ''),
            'price' => $price,
            'formatted_price' => number_format($price, 2),
            'stock_quantity' => (int) $product['stock_quantity'],
            'image' => (string) ($product['image'] ?? ''),
            'image_url' => lpImageUrl($product['image'] ?? ''),
            'status' => (string) $product['status'],
            'vendor_id' => (int) $product['vendor_id'],
            'business_name' => (string) $product['business_name'],
            'business_logo' => (string) ($product['business_logo'] ?? ''),
            'category_id' => (int) $product['category_id'],
            'category_name' => (string) $product['category_name'],
            'created_at' => (string) $product['created_at'],
            'product_url' => BASE_URL . 'product_details.php?id=' . (int) $product['product_id'],
        ];
    }

    $nextOffset = $offset + $limit;
    lpJson([
        'success' => true,
        'message' => 'Products loaded successfully.',
        'products' => $formattedProducts,
        'count' => count($formattedProducts),
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'next_offset' => $nextOffset < $total ? $nextOffset : null,
        'has_more' => $nextOffset < $total,
        'filters' => [
            'keyword' => $keyword,
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'sort' => $sort,
        ],
    ]);
} catch (Throwable $e) {
    $message = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'Unable to load products.';
    lpJson(['success' => false, 'message' => $message, 'products' => []], 500);
}
