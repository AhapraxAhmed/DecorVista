<?php
require_once 'config/config.php';

// Require login
requireLogin();

$pageTitle = 'My Profile - DecorVista';

// Get user info
$user_id = $_SESSION['user_id'];

$user_query = "SELECT u.user_id, u.username, u.email, ud.first_name, ud.last_name, ud.phone, ud.profile_image
               FROM users u
               JOIN user_details ud ON u.user_id = ud.user_id
               WHERE u.user_id = ?";
$stmt = $db->prepare($user_query);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent orders
$recent_orders_query = "SELECT o.order_id, o.total_price, o.status, o.order_date
                        FROM orders o
                        WHERE o.user_id = ?
                        ORDER BY o.order_date DESC
                        LIMIT 5";
$stmt = $db->prepare($recent_orders_query);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get favorites count and recent favorites
$favorites_query = "SELECT COUNT(*) AS count FROM favorites WHERE user_id = ?";
$stmt = $db->prepare($favorites_query);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get recent favorites for preview
$recent_favorites_query = "SELECT p.product_id, p.name, p.image, p.price
                           FROM favorites f
                           JOIN products p ON f.product_id = p.product_id
                           WHERE f.user_id = ?
                           ORDER BY f.created_at DESC
                           LIMIT 4";
$stmt = $db->prepare($recent_favorites_query);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get upcoming consultations
$consultations_query = "SELECT c.consultation_id, CONCAT(c.scheduled_date, ' ', c.scheduled_time) AS scheduled_datetime, c.status,
                               ud.first_name AS designer_first, ud.last_name AS designer_last, ud.profile_image AS designer_image
                        FROM consultations c
                        JOIN interior_designers id ON c.designer_id = id.designer_id
                        JOIN users u2 ON id.user_id = u2.user_id
                        JOIN user_details ud ON u2.user_id = ud.user_id
                        WHERE c.user_id = ? AND CONCAT(c.scheduled_date, ' ', c.scheduled_time) > NOW()
                        ORDER BY scheduled_datetime ASC
                        LIMIT 3";
$stmt = $db->prepare($consultations_query);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle profile update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);

    $update_query = "UPDATE user_details SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
    $stmt = $db->prepare($update_query);
    if (!$stmt) {
        $error = "Prepare failed: " . $db->error;
    } else {
        $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            // Refresh user data
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['phone'] = $phone;
        } else {
            $error = "Update failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pb-20">
    <!-- Header Section -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-black mb-4">My Profile</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">Manage your account, view orders, favorites, and consultations.</p>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: User Info and Edit -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Profile Card -->
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-black mb-6 text-center">Account Information</h2>
                    
                    <?php if (isset($success)): ?>
                        <p class="text-green-500 mb-4 text-center"><?php echo $success; ?></p>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 mb-4 text-center"><?php echo $error; ?></p>
                    <?php endif; ?>
                    
                    <!-- Edit Form -->
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="update_profile" value="1">
                        <div>
                            <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-2">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-gray-500 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-colors bg-white">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-2">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                   required
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-gray-500 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-colors bg-white">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-gray-500 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-colors bg-white">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-900 transition-colors shadow-md focus:ring-2 focus:ring-gray-500 focus:outline-none">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-600">Username: <span class="font-medium text-black"><?php echo htmlspecialchars($user['username']); ?></span></p>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Orders, Favorites, Consultations -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Recent Orders -->
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-black">Recent Orders</h2>
                        <a href="/orders.php" class="text-gray-600 hover:text-black text-sm">View All</a>
                    </div>
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-gray-600 text-center">No recent orders. Start shopping!</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['order_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatPrice($order['total_price']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $order['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Favorites Preview -->
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-black">Favorites (<?php echo $favorites_count; ?>)</h2>
                        <a href="/favorites.php" class="text-gray-600 hover:text-black text-sm">View All</a>
                    </div>
                    <?php if (empty($recent_favorites)): ?>
                        <p class="text-gray-600 text-center">No favorites yet. Start adding some!</p>
                    <?php else: ?>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <?php foreach ($recent_favorites as $favorite): ?>
                                <div class="bg-gray-100 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                    <img src="<?php echo htmlspecialchars($favorite['image']); ?>" alt="<?php echo htmlspecialchars($favorite['name']); ?>" class="w-full h-32 object-cover">
                                    <div class="p-2 text-center">
                                        <p class="text-sm font-medium text-black line-clamp-1"><?php echo htmlspecialchars($favorite['name']); ?></p>
                                        <p class="text-xs text-gray-600"><?php echo formatPrice($favorite['price']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming Consultations -->
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-black">Upcoming Consultations</h2>
                        <a href="/consultations.php" class="text-gray-600 hover:text-black text-sm">View All</a>
                    </div>
                    <?php if (empty($upcoming_consultations)): ?>
                        <p class="text-gray-600 text-center">No upcoming consultations. Book one today!</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_consultations as $consult): ?>
                                <div class="flex items-center space-x-4 border-b border-gray-200 pb-4 last:border-b-0 last:pb-0 hover:bg-gray-50 rounded-lg p-2 transition-colors">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex-shrink-0">
                                        <?php if (!empty($consult['designer_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($consult['designer_image']); ?>" alt="Designer" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-2xl text-gray-500 flex items-center justify-center h-full"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-black truncate"><?php echo htmlspecialchars($consult['designer_first'] . ' ' . $consult['designer_last']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($consult['scheduled_datetime'])); ?></p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 flex-shrink-0"><?php echo ucfirst($consult['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Critical CSS for Locomotive Scroll -->
<style>
    html, body { height: 100%; margin: 0; overflow: hidden; }
    [data-scroll-container] { min-height: 100vh; will-change: transform; backface-visibility: hidden; position: relative; }
    [data-scroll-section] { position: relative; z-index: 1; }
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<!-- Locomotive Scroll -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('load', () => {
        window.locomotiveScroll = new LocomotiveScroll({
            el: document.querySelector('[data-scroll-container]'),
            smooth: true,
            multiplier: 1,
            lerp: 0.1,
            reloadOnContextChange: true
        });

        const resizeObserver = new ResizeObserver(() => { window.locomotiveScroll.update(); });
        resizeObserver.observe(document.querySelector('[data-scroll-container]'));

        const images = document.querySelectorAll('img');
        Promise.all(Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => { img.addEventListener('load', resolve); img.addEventListener('error', resolve); });
        })).then(() => window.locomotiveScroll.update());
    });
});
</script>