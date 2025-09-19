<?php
require_once 'config/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Order Confirmed - DecorVista';
$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

// Validate order
$order_query = "SELECT o.order_id, o.product_id, o.quantity, o.total_price, o.status, o.order_date,
                       p.name, p.image, c.name as category_name
                FROM orders o
                JOIN products p ON o.product_id = p.product_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE o.order_id = ? AND o.user_id = ?";

$stmt = $db->prepare($order_query);
if (!$stmt) {
    die("Query preparation failed: " . $db->error);
}
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: /cart.php');
    exit;
}

// Calculate totals for display
$subtotal = $order['total_price'];
$total_items = $order['quantity'];
$tax_rate = 0.08; // 8% tax
$tax_amount = $subtotal * $tax_rate;
$shipping = $subtotal > 100 ? 0 : 15; // Free shipping over $100
$total = $subtotal + $tax_amount + $shipping;

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 mb-8 text-center">
        <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
        <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
        <p class="text-gray-600 mb-4">
            Thank you for your purchase! Your order #<?php echo $order_id; ?> has been placed successfully.
        </p>
        <p class="text-gray-600">
            Order placed on <?php echo date('F j, Y, g:i A', strtotime($order['order_date'])); ?>
        </p>
    </div>
    
    <!-- Order Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Items -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="font-heading text-xl font-semibold text-gray-900 mb-4">Order Item</h2>
            <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6">
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-6">
                    <!-- Product Image -->
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                            <?php if ($order['image']): ?>
                                <img src="<?php echo htmlspecialchars($order['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['name']); ?>"
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
                            <?php echo htmlspecialchars($order['name']); ?>
                        </h3>
                        <?php if ($order['category_name']): ?>
                            <p class="text-sm text-gray-600 mb-2">
                                <?php echo htmlspecialchars($order['category_name']); ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-lg font-semibold text-gray-800">
                            $<?php echo number_format($order['total_price'] / $order['quantity'], 2); ?> x <?php echo $order['quantity']; ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            Subtotal: $<?php echo number_format($order['total_price'], 2); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 sticky top-24">
                <h2 class="font-heading text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
                
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal (<?php echo $total_items; ?> item<?php echo $total_items > 1 ? 's' : ''; ?>)</span>
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
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold text-gray-900">Total</span>
                            <span class="text-lg font-bold text-gray-800">$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="space-y-4">
                    <a href="./products.php" class="bg-gray-800 text-white w-full px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors block text-center">
                        <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                    </a>
                    <a href="./profile.php" class="bg-gray-200 text-gray-700 w-full px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors block text-center">
                        <i class="fas fa-user mr-2"></i>View Order History
                    </a>
                </div>
            </div>
        </div>
    </div>
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