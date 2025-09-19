<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? null;
$designer_id = $_POST['designer_id'] ?? null;
$rating = $_POST['rating'] ?? '';
$comment = $_POST['comment'] ?? '';

// Validate input
if (empty($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid rating (1-5 stars)']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a comment']);
    exit;
}

if (empty($product_id) && empty($designer_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid review target']);
    exit;
}

// Check if user has already reviewed this item
$check_query = "SELECT review_id FROM reviews WHERE user_id = ? AND ";
$params = [$user_id];
$types = "i";

if ($product_id) {
    $check_query .= "product_id = ?";
    $params[] = $product_id;
    $types .= "i";
} else {
    $check_query .= "designer_id = ?";
    $params[] = $designer_id;
    $types .= "i";
}

$stmt = $db->prepare($check_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this item']);
    exit;
}

// Insert review
$insert_query = "INSERT INTO reviews (user_id, product_id, designer_id, rating, comment, is_approved, created_at) 
                 VALUES (?, ?, ?, ?, ?, 0, NOW())";
$stmt = $db->prepare($insert_query);
$stmt->bind_param("iiiis", $user_id, $product_id, $designer_id, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully! It will be visible after admin approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review. Please try again.']);
}
?>
