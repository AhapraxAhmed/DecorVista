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

// Check if consultation exists and belongs to the designer
$check_query = "SELECT c.*, id.user_id as designer_user_id 
                FROM consultations c
                JOIN interior_designers id ON c.designer_id = id.designer_id
                WHERE c.consultation_id = ? AND id.user_id = ?";
$stmt = $db->prepare($check_query);
$stmt->bind_param("ii", $consultation_id, $user_id);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if (!$consultation) {
    echo json_encode(['success' => false, 'message' => 'Consultation not found or access denied']);
    exit;
}

// Check if consultation can be confirmed (not already confirmed or cancelled)
if ($consultation['status'] === 'confirmed') {
    echo json_encode(['success' => false, 'message' => 'Consultation is already confirmed']);
    exit;
}

if ($consultation['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Consultation is already cancelled']);
    exit;
}

// Update consultation status
$update_query = "UPDATE consultations SET status = 'confirmed', updated_at = NOW() WHERE consultation_id = ?";
$stmt = $db->prepare($update_query);
$stmt->bind_param("i", $consultation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Consultation confirmed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to confirm consultation']);
}
?>