<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Reviews - Admin';

// Handle review actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? '';
    
    if ($action === 'approve' && $review_id) {
        $approve_query = "UPDATE reviews SET is_approved = 1 WHERE review_id = ?";
        $stmt = $db->prepare($approve_query);
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            header('Location: reviews.php?msg=Review approved successfully');
            exit;
        } else {
            $error = "Failed to approve review: " . $db->error;
        }
    } elseif ($action === 'reject' && $review_id) {
        $reject_query = "DELETE FROM reviews WHERE review_id = ?";
        $stmt = $db->prepare($reject_query);
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            header('Location: reviews.php?msg=Review rejected successfully');
            exit;
        } else {
            $error = "Failed to reject review: " . $db->error;
        }
    }
}

// Get reviews
$status_filter = $_GET['status'] ?? 'pending';
$type_filter = $_GET['type'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($status_filter === 'pending') {
    $where_conditions[] = "r.is_approved = 0";
} elseif ($status_filter === 'approved') {
    $where_conditions[] = "r.is_approved = 1";
}

if ($type_filter === 'product') {
    $where_conditions[] = "r.product_id IS NOT NULL";
} elseif ($type_filter === 'designer') {
    $where_conditions[] = "r.designer_id IS NOT NULL";
}

$where_clause = implode(" AND ", $where_conditions);

$reviews_query = "SELECT r.*, 
                         u.username AS reviewer_username,
                         p.name AS product_name,
                         CONCAT(d.first_name, ' ', d.last_name) AS designer_name
                  FROM reviews r
                  LEFT JOIN users u ON r.user_id = u.user_id
                  LEFT JOIN products p ON r.product_id = p.product_id
                  LEFT JOIN interior_designers d ON r.designer_id = d.designer_id
                  WHERE $where_clause
                  ORDER BY r.created_at DESC";

$stmt = $db->prepare($reviews_query);
if (!$stmt) {
    $error = "SQL Error in reviews query: " . $db->error;
} else {
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include '../includes/header.php';
?>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<!-- Locomotive Scroll CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.css">
<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="bg-white text-black" data-scroll-container>
    <div data-scroll-section class="pb-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 mb-24">
            <!-- Error/Success Message -->
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-8 shadow-md" data-scroll data-scroll-speed="1">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (isset($_GET['msg'])): ?>
                <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-8 shadow-md" data-scroll data-scroll-speed="1">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg transform transition-all duration-500 hover:shadow-2xl hover:shadow-black/20" data-scroll data-scroll-speed="1">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-4xl font-extrabold text-black mb-2">Manage Reviews</h1>
                        <p class="text-gray-600 text-lg">Moderate and approve user reviews for products and designers</p>
                    </div>
                    <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-lg transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="md:w-48">
                        <select name="status" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Reviews</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved Reviews</option>
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                        </select>
                    </div>
                    <div class="md:w-48">
                        <select name="type" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="">All Types</option>
                            <option value="product" <?php echo $type_filter === 'product' ? 'selected' : ''; ?>>Product Reviews</option>
                            <option value="designer" <?php echo $type_filter === 'designer' ? 'selected' : ''; ?>>Designer Reviews</option>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
            </div>

            <!-- Reviews List -->
            <div class="space-y-8" data-scroll data-scroll-speed="2">
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Reviews Found</h3>
                        <p class="text-gray-500 text-base">No reviews match your current filter criteria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="relative bg-gray-100 rounded-xl p-8 shadow-lg transform transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-black/20 group tilt-card">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                                <div class="flex-1">
                                    <div class="flex items-center gap-6 mb-4">
                                        <div>
                                            <h3 class="font-semibold text-black text-lg">
                                                <?php echo htmlspecialchars($review['reviewer_username'] ?? 'Unknown User'); ?>
                                            </h3>
                                            <p class="text-gray-600 text-base">
                                                <?php echo isset($review['created_at']) ? date('M j, Y g:i A', strtotime($review['created_at'])) : 'N/A'; ?>
                                            </p>
                                        </div>
                                        <div class="flex text-yellow-400 text-xl">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= ($review['rating'] ?? 0) ? '' : '-o'; ?> mr-1"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo ($review['is_approved'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ($review['is_approved'] ?? 0) ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </div>
                                    <div class="mb-4">
                                        <p class="text-gray-600 font-semibold text-base mb-2">
                                            Review for: 
                                            <?php if ($review['product_name']): ?>
                                                <span class="text-black"><?php echo htmlspecialchars($review['product_name']); ?> (Product)</span>
                                            <?php else: ?>
                                                <span class="text-black"><?php echo htmlspecialchars($review['designer_name'] ?? 'Unknown Designer'); ?> (Designer)</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-gray-600 text-base line-clamp-4"><?php echo htmlspecialchars($review['comment'] ?? 'No comment provided.'); ?></p>
                                    </div>
                                </div>
                                <?php if (!($review['is_approved'] ?? 0)): ?>
                                    <div class="flex gap-3">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id'] ?? ''; ?>">
                                            <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                <i class="fas fa-check mr-2"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id'] ?? ''; ?>">
                                            <button type="submit" class="relative bg-gray-300 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" 
                                                    onclick="return confirm('Are you sure you want to reject this review?')">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                <i class="fas fa-times mr-2"></i>Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Locomotive Scroll JS -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.js"></script>
<!-- Vanilla Tilt for 3D tilt effect -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.2/vanilla-tilt.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Locomotive Scroll
        const scroll = new LocomotiveScroll({
            el: document.querySelector('[data-scroll-container]'),
            smooth: true,
            lerp: 0.08,
            smartphone: { smooth: true },
            tablet: { smooth: true }
        });

        // Update scroll after load to ensure content visibility
        setTimeout(() => {
            scroll.update();
            // Force another update after a longer delay for dynamic content
            setTimeout(() => scroll.update(), 500);
        }, 200);

        // Initialize Vanilla Tilt for review cards
        VanillaTilt.init(document.querySelectorAll('.tilt-card'), {
            max: 10,
            speed: 400,
            glare: true,
            'max-glare': 0.2
        });
    });
</script>

<style>
    [data-scroll-container] {
        width: 100%;
        overflow: visible;
    }

    [data-scroll] {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.8s ease-out, transform 0.8s ease-out;
    }

    [data-scroll].is-inview {
        opacity: 1;
        transform: translateY(0);
    }

    .line-clamp-4 {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ensure footer is not overlapped */
    footer {
        position: relative;
        z-index: 10;
    }

    /* Override any conflicting glass styles */
    .glass-card, .glass-button {
        background: transparent !important;
        border: none !important;
    }
</style>

<?php include '../includes/footer.php'; ?>