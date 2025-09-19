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
    echo json_encode(['success' => false, 'message' => 'Please log in to book a consultation']);
    exit;
}

$user_id = $_SESSION['user_id'];
$designer_id = $_POST['designer_id'] ?? '';
$consultation_date = $_POST['consultation_date'] ?? '';
$consultation_time = $_POST['consultation_time'] ?? '';
$notes = $_POST['notes'] ?? '';

// Validate input
if (empty($designer_id) || empty($consultation_date) || empty($consultation_time)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

// Combine date and time
$scheduled_datetime = $consultation_date . ' ' . $consultation_time;

// Validate datetime format
$datetime = DateTime::createFromFormat('Y-m-d H:i', $scheduled_datetime);
if (!$datetime || $datetime->format('Y-m-d H:i') !== $scheduled_datetime) {
    echo json_encode(['success' => false, 'message' => 'Invalid date or time format']);
    exit;
}

// Check if the datetime is in the future
if ($datetime <= new DateTime()) {
    echo json_encode(['success' => false, 'message' => 'Please select a future date and time']);
    exit;
}

// Check if designer exists
$designer_check = "SELECT designer_id FROM interior_designers WHERE designer_id = ?";
$stmt = $db->prepare($designer_check);
$stmt->bind_param("i", $designer_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Designer not found']);
    exit;
}

// Check for existing consultation at the same time
$conflict_check = "SELECT consultation_id FROM consultations 
                   WHERE designer_id = ? AND scheduled_datetime = ? AND status != 'cancelled'";
$stmt = $db->prepare($conflict_check);
$stmt->bind_param("is", $designer_id, $scheduled_datetime);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
    exit;
}

// Insert consultation
$insert_query = "INSERT INTO consultations (user_id, designer_id, scheduled_datetime, status, notes, created_at) 
                 VALUES (?, ?, ?, 'scheduled', ?, NOW())";
$stmt = $db->prepare($insert_query);
$stmt->bind_param("iiss", $user_id, $designer_id, $scheduled_datetime, $notes);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Consultation booked successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to book consultation. Please try again.']);
}
?>
