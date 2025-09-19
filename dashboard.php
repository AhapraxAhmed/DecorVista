<?php
require_once 'config/config.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard - DecorVista';

// Get user statistics
$user_id = $_SESSION['user_id'];

// Use raw mysqli connection
$conn = $db->getConnection();

// ---------- Recent Orders ----------
$recent_orders_query = "SELECT order_id, total_price, status AS order_status, order_date, quantity AS item_count
                        FROM orders
                        WHERE user_id = ?
                        ORDER BY order_date DESC
                        LIMIT 5";

$stmt = $conn->prepare($recent_orders_query);
if (!$stmt) {
    die("Prepare failed for recent orders: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------- Cart Count ----------
$cart_count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($cart_count_query);
if (!$stmt) {
    die("Prepare failed for cart count: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// ---------- Favorites Count ----------
$favorites_count_query = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
$stmt = $conn->prepare($favorites_count_query);
if (!$stmt) {
    die("Prepare failed for favorites count: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// ---------- Upcoming Consultations ----------
$consultations_query = "SELECT c.consultation_id, CONCAT(c.scheduled_date, ' ', c.scheduled_time) AS scheduled_datetime, c.status,
                              ud.first_name, ud.last_name
                       FROM consultations c
                       JOIN interior_designers id ON c.designer_id = id.designer_id
                       JOIN users u ON id.user_id = u.user_id
                       JOIN user_details ud ON u.user_id = ud.user_id
                       WHERE c.user_id = ? AND CONCAT(c.scheduled_date, ' ', c.scheduled_time) > NOW()
                       ORDER BY scheduled_datetime ASC
                       LIMIT 3";

$stmt = $conn->prepare($consultations_query);
if (!$stmt) {
    die("Prepare failed for consultations: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'includes/header.php';
?>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<!-- Locomotive Scroll CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.css">
<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="min-h-screen bg-white text-black" data-scroll-container>
    <div data-scroll-section>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Welcome Section -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 transform transition-all duration-500 hover:shadow-2xl hover:shadow-black/20" data-scroll data-scroll-speed="1">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h1 class="font-heading text-3xl font-bold text-black mb-2">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['firstname'] ?? $_SESSION['username']); ?>!
                        </h1>
                        <p class="text-gray-600">Manage your interior design journey from your personal dashboard.</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="products.php" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-medium transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="relative bg-gray-100 rounded-xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.1">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-3xl font-bold text-black mb-2 relative"><?php echo $cart_count; ?></div>
                    <div class="text-gray-600">Items in Cart</div>
                    <a href="/cart.php" class="text-sm text-black hover:text-gray-800 mt-2 inline-block">
                        View Cart <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="relative bg-gray-100 rounded-xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.2">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-3xl font-bold text-black mb-2 relative"><?php echo count($recent_orders); ?></div>
                    <div class="text-gray-600">Total Orders</div>
                    <a href="/orders.php" class="text-sm text-black hover:text-gray-800 mt-2 inline-block">
                        View Orders <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="relative bg-gray-100 rounded-xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.3">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-3xl font-bold text-black mb-2 relative"><?php echo $favorites_count; ?></div>
                    <div class="text-gray-600">Saved Favorites</div>
                    <a href="/favorites.php" class="text-sm text-black hover:text-gray-800 mt-2 inline-block">
                        View Favorites <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="relative bg-gray-100 rounded-xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.4">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-3xl font-bold text-black mb-2 relative"><?php echo count($upcoming_consultations); ?></div>
                    <div class="text-gray-600">Upcoming Consultations</div>
                    <a href="/consultations.php" class="text-sm text-black hover:text-gray-800 mt-2 inline-block">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Orders -->
                <div class="bg-gray-100 rounded-xl p-6" data-scroll data-scroll-speed="3">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-heading text-xl font-semibold text-black">Recent Orders</h2>
                        <a href="/orders.php" class="text-black hover:text-gray-800 text-sm">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600 mb-4">No orders yet</p>
                            <a href="products.php" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-medium transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="relative bg-gray-200 rounded-lg p-4 flex justify-between items-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div>
                                        <div class="font-medium text-black">Order #<?php echo $order['order_id']; ?></div>
                                        <div class="text-sm text-gray-600">
                                            <?php echo $order['item_count']; ?> items • <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-black"><?php echo formatPrice($order['total_price']); ?></div>
                                        <div class="text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs <?php 
                                                echo $order['order_status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                                     ($order['order_status'] === 'processing' ? 'bg-blue-100 text-blue-800' :
                                                      ($order['order_status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'));
                                            ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Consultations -->
                <div class="bg-gray-100 rounded-xl p-6" data-scroll data-scroll-speed="3">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="font-heading text-xl font-semibold text-black">Upcoming Consultations</h2>
                        <a href="designers.php" class="text-black hover:text-gray-800 text-sm">
                            Book New <i class="fas fa-plus ml-1"></i>
                        </a>
                    </div>
                    <?php if (empty($upcoming_consultations)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600 mb-4">No upcoming consultations</p>
                            <a href="designers.php" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-medium transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                Book Consultation
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_consultations as $consultation): ?>
                                <div class="relative bg-gray-200 rounded-lg p-4 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-medium text-black">
                                                <?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($consultation['scheduled_datetime'])); ?>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs bg-gray-300 text-black">
                                            <?php echo ucfirst($consultation['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8">
                <h2 class="font-heading text-xl font-semibold text-black mb-6">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="products.php" class="relative bg-gray-200 rounded-xl p-6 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-shopping-bag text-2xl text-black mb-3 group-hover:scale-110 transition-transform"></i>
                        <div class="font-medium text-black">Browse Products</div>
                    </a>
                    <a href="gallery.php" class="relative bg-gray-200 rounded-xl p-6 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-images text-2xl text-black mb-3 group-hover:scale-110 transition-transform"></i>
                        <div class="font-medium text-black">View Gallery</div>
                    </a>
                    <a href="designers.php" class="relative bg-gray-200 rounded-xl p-6 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-user-tie text-2xl text-black mb-3 group-hover:scale-110 transition-transform"></i>
                        <div class="font-medium text-black">Find Designers</div>
                    </a>
                    <a href="profile.php" class="relative bg-gray-200 rounded-xl p-6 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-user-cog text-2xl text-black mb-3 group-hover:scale-110 transition-transform"></i>
                        <div class="font-medium text-black">Edit Profile</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
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
        scroll.update();

        // Initialize Vanilla Tilt for 3D effect
        VanillaTilt.init(document.querySelectorAll('.tilt-card'), {
            max: 10,
            speed: 400,
            glare: true,
            'max-glare': 0.2
        });
    });
</script>

<style>
    /* Prevent overflow clipping */
    [data-scroll-container] {
        overflow: visible !important;
        min-height: 100vh;
    }

    /* Smooth fade-in animation */
    [data-scroll] {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    [data-scroll].is-inview {
        opacity: 1;
        transform: translateY(0);
    }
</style>