<?php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

// Check if product exists and has sufficient stock
$stmt = $db->prepare("SELECT name, stock_quantity FROM products WHERE product_id = ? AND is_active = 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
    exit;
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found or not active']);
    exit;
}

if ($product['stock_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
    exit;
}

// Check if item already exists in cart
$stmt = $db->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_item = $result->fetch_assoc();
$stmt->close();

if ($existing_item) {
    // Update existing cart item
    $new_quantity = $existing_item['quantity'] + $quantity;
    if ($new_quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Cannot add more items than available in stock']);
        exit;
    }
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
        exit;
    }
    $stmt->bind_param("ii", $new_quantity, $existing_item['cart_id']);
} else {
    // Add new cart item
    $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
        exit;
    }
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add to cart: ' . $stmt->error]);
}
$stmt->close();
exit;