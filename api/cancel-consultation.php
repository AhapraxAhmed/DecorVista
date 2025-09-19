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
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$consultation_id = $_POST['consultation_id'] ?? '';

if (empty($consultation_id)) {
    echo json_encode(['success' => false, 'message' => 'Consultation ID is required']);
    exit;
}

// Check if consultation exists and belongs to user or designer
$check_query = "SELECT c.*, id.user_id as designer_user_id 
                FROM consultations c
                LEFT JOIN interior_designers id ON c.designer_id = id.designer_id
                WHERE c.consultation_id = ? AND (c.user_id = ? OR id.user_id = ?)";
$stmt = $db->prepare($check_query);
$stmt->bind_param("iii", $consultation_id, $user_id, $user_id);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if (!$consultation) {
    echo json_encode(['success' => false, 'message' => 'Consultation not found or access denied']);
    exit;
}

// Check if consultation can be cancelled (not already completed or cancelled)
if ($consultation['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Consultation is already cancelled']);
    exit;
}

if ($consultation['status'] === 'completed') {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel completed consultation']);
    exit;
}

// Update consultation status
$update_query = "UPDATE consultations SET status = 'cancelled', updated_at = NOW() WHERE consultation_id = ?";
$stmt = $db->prepare($update_query);
$stmt->bind_param("i", $consultation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Consultation cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel consultation']);
}
?>
