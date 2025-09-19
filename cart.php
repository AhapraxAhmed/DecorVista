<?php
require_once 'config/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Shopping Cart - DecorVista';
$user_id = $_SESSION['user_id'];

// Initialize messages
$error = null;
$success = null;

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        switch ($_POST['action']) {
            case 'update':
                $cart_id = (int)($_POST['cart_id'] ?? 0);
                $quantity = (int)($_POST['quantity'] ?? 1);
                
                if ($cart_id > 0 && $quantity > 0) {
                    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
                        if ($stmt->execute()) {
                            $success = 'Cart updated successfully.';
                        } else {
                            $error = 'Failed to update cart: ' . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = 'Query preparation failed: ' . $db->error;
                    }
                } else {
                    $error = 'Invalid cart ID or quantity.';
                }
                break;
                
            case 'remove':
                $cart_id = (int)($_POST['cart_id'] ?? 0);
                
                if ($cart_id > 0) {
                    $stmt = $db->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $cart_id, $user_id);
                        if ($stmt->execute()) {
                            $success = 'Item removed from cart.';
                        } else {
                            $error = 'Failed to remove item: ' . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = 'Query preparation failed: ' . $db->error;
                    }
                } else {
                    $error = 'Invalid cart ID.';
                }
                break;
                
            case 'clear':
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $success = 'Cart cleared successfully.';
                    } else {
                        $error = 'Failed to clear cart: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Query preparation failed: ' . $db->error;
                }
                break;
        }
    }
}

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
    die("Query preparation failed: " . $db->error);
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

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Shopping Cart</h1>
                <p class="text-gray-600">
                    <?php echo $total_items; ?> item<?php echo $total_items !== 1 ? 's' : ''; ?> in your cart
                </p>
            </div>
            <a href="./products.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md font-medium hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
            </a>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="bg-white/80 backdrop-blur-lg rounded-xl p-12 text-center">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
            <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-4">Your cart is empty</h2>
            <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
            <a href="/products.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <!-- Cart Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-4">
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
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 mb-1">
                                            <a href="/product-detail.php?id=<?php echo $item['product_id']; ?>" 
                                               class="hover:text-gray-700 transition-colors">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        </h3>
                                        <?php if ($item['category_name']): ?>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-lg font-semibold text-gray-800">
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quantity and Actions -->
                            <div class="flex items-center space-x-4">
                                <!-- Quantity Selector -->
                                <form method="POST" action="" class="flex items-center space-x-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    
                                    <label for="quantity_<?php echo $item['cart_id']; ?>" class="text-sm text-gray-600">Qty:</label>
                                    <select name="quantity" id="quantity_<?php echo $item['cart_id']; ?>" 
                                            onchange="this.form.submit()"
                                            class="px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-gray-500 focus:border-transparent">
                                        <?php for ($i = 1; $i <= min(10, $item['stock_quantity']); $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == $item['quantity'] ? 'selected' : ''; ?>
                                            <?php echo $i; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </form>
                                
                                <!-- Remove Button -->
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Remove this item from cart?')"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Item Total -->
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                Added <?php echo timeAgo($item['added_at']); ?>
                            </span>
                            <span class="font-semibold text-gray-900">
                                Subtotal: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Clear Cart -->
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-4">
                    <form method="POST" action="" class="text-center">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" 
                                onclick="return confirm('Are you sure you want to clear your entire cart?')"
                                class="text-red-600 hover:text-red-800 text-sm transition-colors">
                            <i class="fas fa-trash mr-2"></i>Clear Entire Cart
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 sticky top-24">
                    <h2 class="font-heading text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6">
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
                        
                        <?php if ($subtotal < 100 && $subtotal > 0): ?>
                            <div class="text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">
                                <i class="fas fa-info-circle mr-2"></i>
                                Add $<?php echo number_format(100 - $subtotal, 2); ?> more for free shipping!
                            </div>
                        <?php endif; ?>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-gray-800">$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Checkout Button -->
                    <button onclick="proceedToCheckout()" class="bg-gray-800 text-white w-full px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>Proceed to Checkout
                    </button>
                    
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

function proceedToCheckout() {
    window.location.href = './payment.php';
}

// Auto-save cart changes
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for quantity changes
    const quantitySelects = document.querySelectorAll('select[name="quantity"]');
    quantitySelects.forEach(select => {
        select.addEventListener('change', function() {
            // Show loading state
            this.disabled = true;
            const form = this.closest('form');
            
            // Submit form via AJAX to avoid page reload
            const formData = new FormData(form);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Show success notification
                showNotification('Cart updated successfully', 'success');
                // Reload page to show updated totals
                window.location.reload();
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                this.disabled = false;
                showNotification('Error updating cart', 'error');
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>