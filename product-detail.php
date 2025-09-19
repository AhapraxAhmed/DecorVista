<?php
require_once 'config/config.php';

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    error_log("Invalid product ID: " . ($_GET['id'] ?? 'not set'));
    header('Location: ./products.php');
    exit();
}

// Get product details
$product_query = "SELECT p.*, c.name AS categoryname 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.product_id = ? AND p.is_active = 1";
$stmt = $db->prepare($product_query);
if ($stmt === false) {
    error_log("Prepare failed for product query: " . $db->getError());
    die("Prepare failed: " . $db->getError());
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    error_log("No product found for ID: $product_id");
    header('Location: ./products.php');
    exit();
}

$pageTitle = htmlspecialchars($product['name']) . ' - DecorVista';

// Skip product_images query (table doesn't exist in SQL dump)
$product_images = [];

// Get related products
$related_query = "SELECT p.*, c.name AS categoryname 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.category_id = ? AND p.product_id != ? AND p.is_active = 1 
                  ORDER BY RAND() 
                  LIMIT 4";
$stmt = $db->prepare($related_query);
if ($stmt === false) {
    error_log("Prepare failed for related products: " . $db->getError());
    $related_products = [];
} else {
    $stmt->bind_param("ii", $product['category_id'], $product_id);
    $stmt->execute();
    $related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get product reviews
$reviews_query = "SELECT r.*, ud.first_name, ud.last_name 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.user_id 
                  JOIN user_details ud ON u.user_id = ud.user_id 
                  WHERE r.product_id = ? AND r.is_approved = 1 
                  ORDER BY r.created_at DESC 
                  LIMIT 5";
$stmt = $db->prepare($reviews_query);
if ($stmt === false) {
    error_log("Prepare failed for reviews query: " . $db->getError() . " | Query: $reviews_query | Product ID: $product_id");
    $reviews = [];
} else {
    $stmt->bind_param("i", $product_id); // Changed "s" to "i"
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $avg_rating = array_sum(array_column($reviews, 'rating')) / $total_reviews;
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
            <?php if ($product['categoryname']): ?>
                <li><a href="/products.php?category=<?php echo $product['category_id']; ?>" class="hover:text-purple-600"><?php echo htmlspecialchars($product['categoryname']); ?></a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
            <?php endif; ?>
            <li class="text-gray-900"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Product Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
        <!-- Product Images -->
        <div class="space-y-4">
            <!-- Main Image -->
            <div class="glass rounded-2xl overflow-hidden aspect-square">
                <?php if (!empty($product_images)): ?>
                    <img id="main-image" 
                         src="<?php echo htmlspecialchars($product_images[0]['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-full object-cover">
                <?php elseif ($product['image']): ?>
                    <img id="main-image" 
                         src="./Uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-100 to-purple-200">
                        <i class="fas fa-image text-6xl text-purple-400"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Thumbnail Images -->
            <?php if (count($product_images) > 1): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($product_images as $index => $image): ?>
                        <button onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>')"
                                class="glass rounded-lg overflow-hidden aspect-square hover:ring-2 hover:ring-purple-500 transition-all duration-300 <?php echo $index === 0 ? 'ring-2 ring-purple-500' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['alt_text'] ?? $product['name']); ?>"
                                 class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Information -->
        <div class="space-y-6">
            <!-- Product Title and Rating -->
            <div>
                <?php if ($product['categoryname']): ?>
                    <div class="text-sm text-purple-600 font-medium uppercase tracking-wide mb-2">
                        <?php echo htmlspecialchars($product['categoryname']); ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <?php if ($product['brand']): ?>
                    <p class="text-lg text-gray-600 mb-3">
                        by <span class="font-medium"><?php echo htmlspecialchars($product['brand']); ?></span>
                    </p>
                <?php endif; ?>
                
                <!-- Rating -->
                <?php if ($total_reviews > 0): ?>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm text-gray-600">
                            <?php echo number_format($avg_rating, 1); ?> (<?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?>)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Price -->
            <div class="glass rounded-xl p-6">
                <div class="text-3xl font-bold text-purple-600 mb-2">
                    <?php echo formatPrice($product['price']); ?>
                </div>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <p class="text-sm text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>In Stock (<?php echo $product['stock_quantity']; ?> available)
                    </p>
                <?php else: ?>
                    <p class="text-sm text-red-600">
                        <i class="fas fa-times-circle mr-1"></i>Out of Stock
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Product Details -->
            <div class="glass rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Product Details</h3>
                <div class="space-y-3">
                    <?php if ($product['dimensions']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Dimensions:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($product['dimensions']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['materials']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Materials:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($product['materials']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add to Cart Section -->
            <div class="glass rounded-xl p-6">
                <?php if (isLoggedIn()): ?>
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                                <select id="quantity" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <?php for ($i = 1; $i <= min(10, $product['stock_quantity']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button onclick="addToCartWithQuantity(<?php echo $product['product_id']; ?>)" 
                                        class="btn-primary flex-1 glass-hover">
                                    <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                                </button>
                                
                                <button onclick="toggleFavorite(<?php echo $product['product_id']; ?>)" 
                                        class="btn-secondary px-4 glass-hover">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-red-600 mb-4">This product is currently out of stock.</p>
                            <button class="btn-secondary" disabled>
                                <i class="fas fa-bell mr-2"></i>Notify When Available
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-gray-600 mb-4">Please login to purchase this product.</p>
                        <a href="/login.php" class="btn-primary">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login to Buy
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Product Description -->
    <?php if ($product['description']): ?>
        <div class="glass rounded-xl p-8 mb-12">
            <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-6">Description</h2>
            <div class="prose prose-lg max-w-none text-gray-700">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Reviews Section -->
    <div class="glass rounded-xl p-8 mb-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-heading text-2xl font-semibold text-gray-900">Customer Reviews</h2>
            <?php if (isLoggedIn()): ?>
                <button onclick="openReviewModal()" class="btn-secondary">
                    <i class="fas fa-star mr-2"></i>Write Review
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="text-center py-8">
                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">No reviews yet. Be the first to review this product!</p>
                <?php if (isLoggedIn()): ?>
                    <button onclick="openReviewModal()" class="btn-primary">Write First Review</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($reviews as $review): ?>
                    <div class="border-b border-gray-200 pb-6 last:border-b-0">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </div>
                                <div class="flex items-center space-x-2 mt-1">
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-sm"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?php echo timeAgo($review['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($review['comment']): ?>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="mb-12">
            <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-8">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($related_products as $related): ?>
                    <div class="glass rounded-xl overflow-hidden glass-hover group">
                        <div class="aspect-square bg-gray-100 relative overflow-hidden">
                            <?php if ($related['image']): ?>
                                <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-100 to-purple-200">
                                    <i class="fas fa-image text-3xl text-purple-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="/product-detail.php?id=<?php echo $related['product_id']; ?>" 
                                   class="hover:text-purple-600 transition-colors">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="flex justify-between items-center">
                                <div class="text-lg font-bold text-purple-600">
                                    <?php echo formatPrice($related['price']); ?>
                                </div>
                                
                                <?php if (isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $related['product_id']; ?>)" 
                                            class="btn-secondary text-sm">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function changeMainImage(imageUrl) {
    document.getElementById('main-image').src = imageUrl;
    
    // Update thumbnail selection
    document.querySelectorAll('.grid button').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-purple-500');
    });
    event.target.closest('button').classList.add('ring-2', 'ring-purple-500');
}

function addToCartWithQuantity(productId) {
    const quantity = document.getElementById('quantity').value;
    addToCart(productId, quantity);
}

function toggleFavorite(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('/api/toggle-favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const button = event.target.closest('button');
            const icon = button.querySelector('i');
            if (data.added) {
                icon.classList.remove('text-gray-400');
                icon.classList.add('text-red-500');
                showNotification('Added to favorites!', 'success');
            } else {
                icon.classList.remove('text-red-500');
                icon.classList.add('text-gray-400');
                showNotification('Removed from favorites', 'info');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function openReviewModal() {
    window.location.href = './write-review.php?product_id=<?php echo $product_id; ?>';
}
</script>

<?php include 'includes/footer.php'; ?>