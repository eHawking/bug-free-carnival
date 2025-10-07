<?php
// Simple Order Create API (COD)
// Accepts JSON POST { sku, price, contact{email,phone}, name, address{line1,city,zip}, product{name,img}, notes }
// Security: allows same-origin requests; optionally require API_SECRET via 'secret' field.

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Read JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // Support form-encoded fallback
    $data = $_POST ?: [];
}

// Basic origin/secret check
$secret = $data['secret'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$refHost = $referer ? parse_url($referer, PHP_URL_HOST) : '';
$origHost = $origin ? parse_url($origin, PHP_URL_HOST) : '';
$allowSameOrigin = ($refHost && $refHost === $host) || ($origHost && $origHost === $host);
$allowBySecret = defined('API_SECRET') && API_SECRET && hash_equals(API_SECRET, (string)$secret);
if (!$allowSameOrigin && !$allowBySecret) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// Extract fields
$sku = substr(trim((string)($data['sku'] ?? '')), 0, 20);
$price = (float)($data['price'] ?? 0);
$contact = $data['contact'] ?? [];
$email = substr(trim((string)($contact['email'] ?? '')), 0, 190);
$phone = substr(trim((string)($contact['phone'] ?? '')), 0, 50);
$name = substr(trim((string)($data['name'] ?? '')), 0, 190);
$addr = $data['address'] ?? [];
$line1 = substr(trim((string)($addr['line1'] ?? '')), 0, 255);
$city  = substr(trim((string)($addr['city'] ?? '')), 0, 120);
$zip   = substr(trim((string)($addr['zip'] ?? '')), 0, 30);
$product = $data['product'] ?? [];
$productName = substr(trim((string)($product['name'] ?? '')), 0, 190);
$notes = isset($data['notes']) ? (string)$data['notes'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

if ($sku === '' || $price <= 0 || $email === '' || $phone === '' || $name === '' || $line1 === '' || $city === '' || $zip === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

$orderNumber = 'HONR-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$now = date('Y-m-d H:i:s');

try {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO orders
        (order_number, created_at, updated_at, name, email, phone, address_line1, city, zip, sku, product_name, price, payment_method, status, notes, raw_json, ip, user_agent)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $rawJson = $raw ?: json_encode($data);
    $stmt->execute([
        $orderNumber, $now, $now,
        $name, $email, $phone,
        $line1, $city, $zip,
        $sku, $productName, $price,
        'COD', 'pending', $notes,
        $rawJson, $ip, $ua
    ]);
    $id = (int)$pdo->lastInsertId();
    echo json_encode(['ok' => true, 'order_id' => $id, 'order_number' => $orderNumber]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
