<?php
require_once 'config/config.php';

requireLogin();

$pageTitle = 'My Reviews - DecorVista';
$user_id = $_SESSION['user_id'];

// Get user's reviews
$reviews_query = "SELECT r.*, 
                         p.name as product_name, p.image_url as product_image,
                         CONCAT(ud.firstname, ' ', ud.lastname) as designer_name
                  FROM reviews r
                  LEFT JOIN products p ON r.product_id = p.product_id
                  LEFT JOIN interior_designers id ON r.designer_id = id.designer_id
                  LEFT JOIN user_details ud ON id.user_id = ud.user_id
                  WHERE r.user_id = ?
                  ORDER BY r.created_at DESC";

$stmt = $db->prepare($reviews_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-black">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="glass-card p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">My Reviews</h1>
                    <p class="text-purple-200">View and manage your product and designer reviews</p>
                </div>
                <a href="dashboard.php" class="glass-button">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="space-y-6">
            <?php foreach ($user_reviews as $review): ?>
                <div class="glass-card p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <?php if ($review['product_name']): ?>
                            <!-- Product Review -->
                            <div class="w-full md:w-32 h-32 rounded-lg overflow-hidden bg-gray-800 flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($review['product_image'] ?: '/placeholder.svg?height=128&width=128'); ?>" 
                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                     class="w-full h-full object-cover">
                            </div>
                        <?php else: ?>
                            <!-- Designer Review -->
                            <div class="w-full md:w-32 h-32 rounded-lg overflow-hidden bg-purple-600 flex-shrink-0 flex items-center justify-center">
                                <i class="fas fa-paint-brush text-white text-3xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-1">
                            <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white mb-2">
                                        <?php echo htmlspecialchars($review['product_name'] ?: $review['designer_name']); ?>
                                    </h3>
                                    <div class="flex items-center gap-4 mb-2">
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-gray-400 text-sm">
                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                        </span>
                                    </div>
                                    <span class="px-3 py-1 text-xs rounded-full <?php echo $review['is_approved'] ? 'bg-green-600/30 text-green-300' : 'bg-yellow-600/30 text-yellow-300'; ?>">
                                        <?php echo $review['is_approved'] ? 'Published' : 'Pending Approval'; ?>
                                    </span>
                                </div>
                                <span class="px-3 py-1 text-xs rounded-full bg-purple-600/30 text-purple-300">
                                    <?php echo $review['product_name'] ? 'Product Review' : 'Designer Review'; ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-300 leading-relaxed">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($user_reviews)): ?>
            <div class="text-center py-12">
                <i class="fas fa-star text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl text-gray-400 mb-2">No reviews yet</h3>
                <p class="text-gray-500 mb-6">Start reviewing products and designers to share your experience</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="products.php" class="glass-button">
                        <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                    </a>
                    <a href="designers.php" class="glass-button">
                        <i class="fas fa-paint-brush mr-2"></i>Find Designers
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
