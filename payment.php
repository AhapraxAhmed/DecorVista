<?php
require_once 'config/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: ./login.php');
    exit;
}

$pageTitle = 'Checkout - DecorVista';
$user_id = $_SESSION['user_id'];

// Initialize checkout token to prevent duplicate submissions
if (!isset($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(16));
}
$checkout_token = $_SESSION['checkout_token'];

// Initialize messages
$error = null;
$success = null;

// Get cart items
$cart_query = "SELECT c.cart_id, c.quantity, c.added_at,
                      p.product_id, p.name, p.price, p.image, p.stock_quantity,
                      c2.name as category_name
               FROM cart c
               JOIN products p ON c.product_id = p.product_id
               LEFT JOIN categories c2 ON p.category_id = c2.category_id
               WHERE c.user_id = ? AND p.is_active = 1
               ORDER BY c.added_at DESC";

$stmt = $db->prepare($cart_query);
if (!$stmt) {
    die("Query preparation failed: " . $db->getConnection()->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$tax_rate = 0.08; // 8% tax
$tax_amount = $subtotal * $tax_rate;
$shipping = $subtotal > 100 ? 0 : 15; // Free shipping over $100
$total = $subtotal + $tax_amount + $shipping;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($_POST['checkout_token'] !== $checkout_token) {
        $error = 'Invalid checkout attempt. Please try again.';
    } elseif (empty($cart_items)) {
        $error = 'Your cart is empty.';
    } else {
        // Validate stock availability
        foreach ($cart_items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $error = "Insufficient stock for {$item['name']}.";
                break;
            }
        }

        if (!$error) {
            // Simulate payment processing
            $payment_success = true; // Replace with actual payment gateway logic (e.g., Stripe)

            if ($payment_success) {
                // Start transaction using the mysqli connection
                $mysqli = $db->getConnection();
                try {
                    if (!$mysqli->begin_transaction()) {
                        throw new Exception('Failed to start transaction: ' . $mysqli->error);
                    }

                    // Create an order for each cart item
                    $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, status, order_date, updated_at) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())");
                    if (!$stmt) {
                        throw new Exception('Query preparation failed: ' . $mysqli->error);
                    }

                    $last_order_id = 0;
                    foreach ($cart_items as $item) {
                        $item_total = $item['price'] * $item['quantity'];
                        $stmt->bind_param("iisd", $user_id, $item['product_id'], $item['quantity'], $item_total);
                        if (!$stmt->execute()) {
                            throw new Exception('Order insertion failed: ' . $stmt->error);
                        }
                        $last_order_id = $mysqli->insert_id; // Track the last order ID
                    }
                    $stmt->close();

                    // Update product stock
                    $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
                    if (!$stmt) {
                        throw new Exception('Query preparation failed: ' . $mysqli->error);
                    }
                    foreach ($cart_items as $item) {
                        $stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                        if (!$stmt->execute()) {
                            throw new Exception('Stock update failed: ' . $stmt->error);
                        }
                    }
                    $stmt->close();

                    // Clear cart
                    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception('Query preparation failed: ' . $mysqli->error);
                    }
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception('Cart clearing failed: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Commit transaction
                    if (!$mysqli->commit()) {
                        throw new Exception('Transaction commit failed: ' . $mysqli->error);
                    }

                    // Clear checkout token
                    unset($_SESSION['checkout_token']);

                    // Redirect to order confirmation with the last order ID
                    header("Location: ./order-confirmed.php?order_id=$last_order_id");
                    exit;
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $error = 'Failed to process order: ' . $e->getMessage();
                }
            } else {
                $error = 'Payment failed. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Checkout</h1>
                <p class="text-gray-600">Review your order and complete payment</p>
            </div>
            <a href="/cart.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md font-medium hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Cart
            </a>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <script>
            showNotification("<?php echo htmlspecialchars($error); ?>", "error");
        </script>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="bg-white/80 backdrop-blur-lg rounded-xl p-12 text-center">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
            <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-4">Your cart is empty</h2>
            <p class="text-gray-600 mb-8">Add items to your cart before checking out.</p>
            <a href="/products.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <!-- Checkout Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Items -->
            <div class="lg:col-span-2 space-y-4">
                <h2 class="font-heading text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
                <?php foreach ($cart_items as $item): ?>
                    <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6">
                        <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-6">
                            <!-- Product Image -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                            <i class="fas fa-image text-xl text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 mb-1">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <?php if ($item['category_name']): ?>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="text-lg font-semibold text-gray-800">
                                    $<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Subtotal: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Payment Form -->
            <div class="lg:col-span-1">
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 sticky top-24">
                    <h2 class="font-heading text-xl font-semibold text-gray-900 mb-6">Payment Details</h2>
                    
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="process_payment">
                        <input type="hidden" name="checkout_token" value="<?php echo $checkout_token; ?>">
                        
                        <!-- Simulated Payment Fields -->
                        <div>
                            <label for="card_number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
                            <input type="text" id="card_number" name="card_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                                   placeholder="1234 5678 9012 3456" required>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="expiry" class="block text-sm font-medium text-gray-700 mb-2">Expiry</label>
                                <input type="text" id="expiry" name="expiry" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                                       placeholder="MM/YY" required>
                            </div>
                            <div>
                                <label for="cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                <input type="text" id="cvv" name="cvv" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                                       placeholder="123" required>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <h3 class="font-semibold text-gray-900 mb-4">Order Summary</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal (<?php echo $total_items; ?> items)</span>
                                    <span class="font-medium">$<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax (8%)</span>
                                    <span class="font-medium">$<?php echo number_format($tax_amount, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-medium">
                                        <?php if ($shipping > 0): ?>
                                            $<?php echo number_format($shipping, 2); ?>
                                        <?php else: ?>
                                            <span class="text-green-600">Free</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="border-t border-gray-200 pt-2 mt-2">
                                    <div class="flex justify-between">
                                        <span class="text-lg font-semibold text-gray-900">Total</span>
                                        <span class="text-lg font-bold text-gray-800">$<?php echo number_format($total, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="bg-gray-800 text-white w-full px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors">
                            <i class="fas fa-credit-card mr-2"></i>Pay Now
                        </button>
                    </form>
                    
                    <div class="text-center text-sm text-gray-600 mt-4">
                        <i class="fas fa-lock mr-1"></i>Secure checkout with SSL encryption
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600 mb-3">We accept:</p>
                        <div class="flex justify-center space-x-2">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fab fa-cc-amex text-2xl text-blue-500"></i>
                            <i class="fab fa-cc-paypal text-2xl text-blue-700"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Toastify.js -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />

<script>
function showNotification(message, type) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: type === 'success' ? "#10B981" : type === 'error' ? "#EF4444" : "#3B82F6",
        stopOnFocus: true
    }).showToast();
}
</script>

<?php include 'includes/footer.php'; ?>