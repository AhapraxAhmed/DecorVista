<?php
require_once '../config/config.php';

// Require designer login and role
requireLogin();
requireRole('designer');

$user_id = $_SESSION['user_id'];

// Check if the request is a POST and required fields are set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultation_id'], $_POST['action'])) {
    $consultation_id = filter_input(INPUT_POST, 'consultation_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    // Validate inputs
    if (!$consultation_id || !in_array($action, ['confirm', 'cancel'])) {
        $_SESSION['error'] = 'Invalid request.';
        header('Location: dashboard.php');
        exit;
    }

    // Get designer_id for the logged-in user
    $designer_query = "SELECT designer_id FROM interior_designers WHERE user_id = ?";
    $stmt = $db->prepare($designer_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $designer = $stmt->get_result()->fetch_assoc();
    $designer_id = $designer['designer_id'];

    // Verify that the consultation belongs to this designer
    $verify_query = "SELECT consultation_id FROM consultations WHERE consultation_id = ? AND designer_id = ?";
    $stmt = $db->prepare($verify_query);
    $stmt->bind_param("ii", $consultation_id, $designer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'You do not have permission to modify this consultation.';
        header('Location: dashboard.php');
        exit;
    }

    // Update consultation status based on action
    $new_status = ($action === 'confirm') ? 'confirmed' : 'cancelled';
    $update_query = "UPDATE consultations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE consultation_id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->bind_param("si", $new_status, $consultation_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Consultation $new_status successfully.";
    } else {
        $_SESSION['error'] = 'Failed to update consultation status.';
    }

    // Redirect back to the dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Invalid request
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: dashboard.php');
    exit;
}
?>