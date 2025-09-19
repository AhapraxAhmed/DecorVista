<?php
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit();
}

// Get product_id from URL
$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    error_log("Invalid product ID: " . ($_GET['product_id'] ?? 'not set'));
    header('Location: /products.php');
    exit();
}

// Fetch product details to validate and display product name
$product_query = "SELECT name FROM products WHERE product_id = ? AND is_active = 1";
$stmt = $db->prepare($product_query);
if ($stmt === false) {
    error_log("Prepare failed for product query: " . $db->getError());
    die("Error preparing product query. Check error logs for details.");
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    error_log("No product found for ID: $product_id");
    header('Location: /products.php');
    exit();
}

$pageTitle = "Write Review for " . htmlspecialchars($product['name']) . " - DecorVista";

// Handle form submission
$errors = [];
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generateCSRFToken()) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        // Validate inputs
        if ($rating < 1 || $rating > 5) {
            $errors[] = "Please select a rating between 1 and 5.";
        }
        if (strlen($comment) > 1000) {
            $errors[] = "Comment cannot exceed 1000 characters.";
        }

        if (empty($errors)) {
            // Insert review into database
            $review_query = "INSERT INTO reviews (user_id, product_id, rating, comment, is_approved, created_at) 
                             VALUES (?, ?, ?, ?, 0, NOW())";
            $stmt = $db->prepare($review_query);
            if ($stmt === false) {
                error_log("Prepare failed for review insert: " . $db->getError());
                $errors[] = "Failed to submit review. Please try again.";
            } else {
                $user_id = $_SESSION['user_id']; // Assumes user_id is stored in session
                $stmt->bind_param("iiss", $user_id, $product_id, $rating, $comment);
                if ($stmt->execute()) {
                    $success_message = "Your review has been submitted and is pending approval.";
                    header("Location: ./product-detail.php?id=$product_id&success=" . urlencode($success_message));
                    exit();
                } else {
                    error_log("Review insert failed: " . $stmt->error);
                    $errors[] = "Failed to submit review. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 text-sm text-gray-600">
            <li><a href="./index.php" class="hover:text-purple-600">Home</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="./products.php" class="hover:text-purple-600">Products</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="./product-detail.php?id=<?php echo $product_id; ?>" class="hover:text-purple-600"><?php echo htmlspecialchars($product['name']); ?></a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li class="text-gray-900">Write Review</li>
        </ol>
    </nav>

    <!-- Review Form -->
    <div class="glass rounded-xl p-8 max-w-lg mx-auto">
        <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-6">Write a Review for <?php echo htmlspecialchars($product['name']); ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="./write-review.php?product_id=<?php echo $product_id; ?>" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">

            <!-- Rating -->
            <div>
                <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select id="rating" name="rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                    <option value="">Select a rating</option>
                    <option value="1">1 Star</option>
                    <option value="2">2 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="5">5 Stars</option>
                </select>
            </div>

            <!-- Comment -->
            <div>
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                <textarea id="comment" name="comment" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Share your thoughts about the product..."></textarea>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" class="btn-primary w-full glass-hover">
                    <i class="fas fa-star mr-2"></i>Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>