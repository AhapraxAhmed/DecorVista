<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage favorites']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$user_id = $_SESSION['user_id'];
$gallery_id = (int)($_POST['gallery_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);

if ($gallery_id <= 0 && $product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Initialize statement variable
$stmt = null;

    // Check if the item exists
    if ($gallery_id > 0) {
        $stmt = $db->prepare("SELECT gallery_id FROM gallery WHERE gallery_id = ? AND is_active = 1");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $gallery_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Gallery image not found']);
            exit();
        }
        $item_type = 'gallery';
        $item_id = $gallery_id;
    } elseif ($product_id > 0) {
        $stmt = $db->prepare("SELECT product_id FROM products WHERE product_id = ? AND is_active = 1");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        $item_type = 'product';
        $item_id = $product_id;
    }

    // Close the previous statement
    if ($stmt) {
        $stmt->close();
    }

    // Check if already in favorites
    $stmt = $db->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND " . ($item_type === 'gallery' ? 'gallery_id = ?' : 'product_id = ?'));
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    $stmt->bind_param("ii", $user_id, $item_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Remove from favorites
        $stmt = $db->prepare("DELETE FROM favorites WHERE favorite_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $existing['favorite_id']);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete favorite");
        }
        $stmt->close();
        echo json_encode(['success' => true, 'added' => false, 'message' => 'Removed from favorites']);
    } else {
        // Add to favorites
        $stmt = $db->prepare("INSERT INTO favorites (user_id, " . ($item_type === 'gallery' ? 'gallery_id' : 'product_id') . ") VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("ii", $user_id, $item_id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to insert favorite");
        }
        $stmt->close();
        echo json_encode(['success' => true, 'added' => true, 'message' => 'Added to favorites']);
    }

?>