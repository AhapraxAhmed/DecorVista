<?php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Use SUM(quantity) to count total items, not just rows
$stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Database error: ' . $db->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$count = $result['count'] ?? 0; // Handle NULL case
$stmt->close();

echo json_encode(['success' => true, 'count' => (int)$count]);
exit;