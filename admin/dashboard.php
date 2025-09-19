<?php
require_once '../config/config.php';

// Require admin login
requireLogin();
requireRole('admin');

$pageTitle = 'Admin Dashboard - DecorVista';

// Get statistics
$stats = [];

// Users count
$users_query = "SELECT COUNT(*) AS total_users FROM users WHERE is_active = 1";
$result = $db->query($users_query);
if ($result && $result instanceof mysqli_result) {
    $stats['total_users'] = $result->fetch_assoc()['total_users'];
} else {
    $stats['total_users'] = 0;
    error_log("SQL error [Users]: " . $db->getConnection()->error);
}

// Products count
$products_query = "SELECT COUNT(*) AS total_products FROM products WHERE is_active = 1";
$result = $db->query($products_query);
if ($result && $result instanceof mysqli_result) {
    $stats['total_products'] = $result->fetch_assoc()['total_products'];
} else {
    $stats['total_products'] = 0;
    error_log("SQL error [Products]: " . $db->getConnection()->error);
}

// Designers count
$designers_query = "SELECT COUNT(*) AS total_designers FROM users WHERE role = 'designer' AND is_active = 1";
$result = $db->query($designers_query);
if ($result && $result instanceof mysqli_result) {
    $stats['total_designers'] = $result->fetch_assoc()['total_designers'];
} else {
    $stats['total_designers'] = 0;
    error_log("SQL error [Designers]: " . $db->getConnection()->error);
}

// Consultations count
$consultations_query = "SELECT COUNT(*) AS total_consultations FROM consultations";
$result = $db->query($consultations_query);
if ($result && $result instanceof mysqli_result) {
    $stats['total_consultations'] = $result->fetch_assoc()['total_consultations'];
} else {
    $stats['total_consultations'] = 0;
    error_log("SQL error [Consultations]: " . $db->getConnection()->error);
}

// Recent Users
$recent_users_query = "SELECT u.username, ud.firstname, ud.lastname, u.created_at 
                       FROM users u 
                       JOIN user_details ud ON u.user_id = ud.user_id 
                       WHERE u.is_active = 1 
                       ORDER BY u.created_at DESC LIMIT 5";

$result = $db->query($recent_users_query);
if ($result && $result instanceof mysqli_result) {
    $recent_users = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $recent_users = [];
    error_log("SQL error [Recent Users]: " . $db->getConnection()->error);
}

// Recent Consultations
$recent_consultations_query = "
    SELECT c.*, 
           ud1.firstname AS user_firstname, 
           ud1.lastname AS user_lastname,
           ud2.firstname AS designer_firstname, 
           ud2.lastname AS designer_lastname
    FROM consultations c
    JOIN users u1 ON c.user_id = u1.user_id
    JOIN user_details ud1 ON u1.user_id = ud1.user_id
    JOIN designers d ON c.designer_id = d.designer_id
    JOIN user_details ud2 ON d.user_id = ud2.user_id
    ORDER BY c.created_at DESC 
    LIMIT 5
";

$result = $db->query($recent_consultations_query);
if ($result && $result instanceof mysqli_result) {
    $recent_consultations = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $recent_consultations = [];
    error_log("SQL error [Recent Consultations]: " . $db->getConnection()->error);
}

include '../includes/header.php';
?>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<!-- Locomotive Scroll CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.css">
<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="min-h-screen bg-white text-black" data-scroll-container>
    <div data-scroll-section>
        <div class="container mx-auto px-4 py-12">
            <!-- Header -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 transform transition-all duration-500 hover:shadow-2xl hover:shadow-black/20" data-scroll data-scroll-speed="1">
                <h1 class="text-4xl font-extrabold tracking-tight text-black mb-3">Admin Dashboard</h1>
                <p class="text-gray-600">Elevate your DecorVista management experience</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="relative bg-gray-100 rounded-2xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.1">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-4xl font-bold text-black mb-2 relative"><?php echo $stats['total_users']; ?></div>
                    <div class="text-gray-600">Total Users</div>
                </div>
                <div class="relative bg-gray-100 rounded-2xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.2">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-4xl font-bold text-black mb-2 relative"><?php echo $stats['total_products']; ?></div>
                    <div class="text-gray-600">Products</div>
                </div>
                <div class="relative bg-gray-100 rounded-2xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.3">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-4xl font-bold text-black mb-2 relative"><?php echo $stats['total_designers']; ?></div>
                    <div class="text-gray-600">Designers</div>
                </div>
                <div class="relative bg-gray-100 rounded-2xl p-6 text-center overflow-hidden transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" data-scroll data-scroll-speed="2" data-scroll-delay="0.4">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="text-4xl font-bold text-black mb-2 relative"><?php echo $stats['total_consultations']; ?></div>
                    <div class="text-gray-600">Consultations</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-12" data-scroll data-scroll-speed="1.5">
                <h2 class="text-2xl font-bold text-black mb-6">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="users.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-users text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Manage Users</div>
                    </a>
                    <a href="products.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-box text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Manage Products</div>
                    </a>
                    <a href="designers.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-paint-brush text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Manage Designers</div>
                    </a>
                    <a href="consultations.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-calendar text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">View Consultations</div>
                    </a>
                    <a href="gallery.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-images text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Manage Gallery</div>
                    </a>
                    <a href="reviews.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-star text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Manage Reviews</div>
                    </a>
                    <a href="orders.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-shopping-cart text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">View Orders</div>
                    </a>
                    <a href="settings.php" class="relative bg-gray-200 rounded-xl p-4 text-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-cog text-2xl mb-2 text-black"></i>
                        <div class="text-black font-medium">Settings</div>
                    </a>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Users -->
                <div class="bg-gray-100 rounded-2xl p-8" data-scroll data-scroll-speed="3">
                    <h2 class="text-2xl font-bold text-black mb-6">Recent Users</h2>
                    <?php if (empty($recent_users)): ?>
                        <p class="text-gray-600">No recent users</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="relative bg-gray-200 rounded-lg p-4 flex justify-between items-center transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div>
                                        <h3 class="font-semibold text-black">
                                            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-gray-600 text-sm">
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Consultations -->
                <div class="bg-gray-100 rounded-2xl p-8" data-scroll data-scroll-speed="3">
                    <h2 class="text-2xl font-bold text-black mb-6">Recent Consultations</h2>
                    <?php if (empty($recent_consultations)): ?>
                        <p class="text-gray-600">No recent consultations</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_consultations as $consultation): ?>
                                <div class="relative bg-gray-200 rounded-lg p-4 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group tilt-card">
                                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-semibold text-black">
                                                <?php echo htmlspecialchars($consultation['user_firstname'] . ' ' . $consultation['user_lastname']); ?>
                                            </h3>
                                            <p class="text-gray-600 text-sm">
                                                with <?php echo htmlspecialchars($consultation['designer_firstname'] . ' ' . $consultation['designer_lastname']); ?>
                                            </p>
                                        </div>
                                        <span class="px-3 py-1 text-xs rounded-full bg-gray-300 text-black">
                                            <?php echo ucfirst($consultation['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm">
                                        <?php echo date('M j, Y g:i A', strtotime($consultation['scheduled_datetime'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
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