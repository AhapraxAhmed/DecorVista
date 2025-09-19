<?php
require_once '../config/config.php';

// Require designer login
requireLogin();
requireRole('designer');

$pageTitle = 'Designer Dashboard - DecorVista';
$user_id = $_SESSION['user_id'];

// Get designer info
$designer_query = "SELECT d.*, ud.first_name, ud.last_name, ud.profile_image
                   FROM interior_designers d
                   JOIN user_details ud ON d.user_id = ud.user_id
                   WHERE d.user_id = ?";
$stmt = $db->prepare($designer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$designer = $stmt->get_result()->fetch_assoc();

// Get upcoming consultations
$upcoming_query = "SELECT c.*, ud.first_name, ud.last_name, ud.phone
                   FROM consultations c
                   JOIN users u ON c.user_id = u.user_id
                   JOIN user_details ud ON u.user_id = ud.user_id
                   WHERE c.designer_id = ? 
                     AND CONCAT(c.scheduled_date, ' ', c.scheduled_time) > NOW()
                     AND c.status != 'cancelled'
                   ORDER BY CONCAT(c.scheduled_date, ' ', c.scheduled_time) ASC
                   LIMIT 5";
$stmt = $db->prepare($upcoming_query);
$stmt->bind_param("i", $designer['designer_id']);
$stmt->execute();
$upcoming_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent reviews
$reviews_query = "SELECT r.*, ud.first_name, ud.last_name
                  FROM reviews r
                  JOIN users u ON r.user_id = u.user_id
                  JOIN user_details ud ON u.user_id = ud.user_id
                  WHERE r.designer_id = ? AND r.is_approved = 1
                  ORDER BY r.created_at DESC
                  LIMIT 3";
$stmt = $db->prepare($reviews_query);
$stmt->bind_param("i", $designer['designer_id']);
$stmt->execute();
$recent_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_query = "SELECT 
                    COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_consultations,
                    COUNT(CASE WHEN c.status = 'scheduled' THEN 1 END) as scheduled_consultations,
                    COUNT(CASE WHEN CONCAT(c.scheduled_date, ' ', c.scheduled_time) > NOW() THEN 1 END) as upcoming_consultations,
                    SUM(CASE WHEN c.status = 'completed' THEN c.total_cost ELSE 0 END) as total_earnings
                FROM consultations c
                WHERE c.designer_id = ?";
$stmt = $db->prepare($stats_query);
$stmt->bind_param("i", $designer['designer_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<!-- Add data-scroll-container for Locomotive Scroll -->
<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300">
    <!-- Header Section -->
    <section class="relative py-16 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                        <?php if ($designer['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($designer['profile_image']); ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-gray-700 text-2xl"></i>
                        <?php endif; ?>
                    </div>
                    <div class="text-center md:text-left">
                        <h1 class="text-3xl font-bold text-black mb-2">
                            Welcome, <?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
                        </h1>
                        <p class="text-gray-600">Interior Designer Dashboard</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 px-4" data-scroll-section>
        <div class="container mx-auto">
            <h2 class="text-4xl font-bold text-black text-center mb-12">Your Performance Overview</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl font-bold text-purple-600 mb-2"><?php echo $stats['completed_consultations'] ?? 0; ?></div>
                    <div class="text-gray-600">Completed Consultations</div>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo $stats['scheduled_consultations'] ?? 0; ?></div>
                    <div class="text-gray-600">Scheduled Consultations</div>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl font-bold text-green-600 mb-2"><?php echo $stats['upcoming_consultations'] ?? 0; ?></div>
                    <div class="text-gray-600">Upcoming Consultations</div>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl font-bold text-yellow-600 mb-2">$<?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></div>
                    <div class="text-gray-600">Total Earnings</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Consultations and Reviews Section -->
    <section class="py-16 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Upcoming Consultations -->
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <h2 class="text-2xl font-bold text-black mb-6">Upcoming Consultations</h2>
                    <?php if (empty($upcoming_consultations)): ?>
                        <p class="text-gray-600">No upcoming consultations at the moment.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_consultations as $consultation): ?>
                                <div class="bg-gray-100/50 rounded-lg p-4 hover:bg-gray-200/50 transition-colors duration-300">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-black">
                                            <?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?>
                                        </h3>
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($consultation['scheduled_date'] . ' ' . $consultation['scheduled_time'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($consultation['notes'] ?? 'No notes provided'); ?></p>
                                    <p class="text-gray-500 text-sm">Contact: <?php echo htmlspecialchars($consultation['phone']); ?></p>
                                    <p class="text-gray-500 text-sm">Status: 
                                        <span class="font-semibold <?php echo $consultation['status'] === 'pending' ? 'text-yellow-600' : ($consultation['status'] === 'confirmed' ? 'text-green-600' : 'text-blue-600'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($consultation['status'])); ?>
                                        </span>
                                    </p>
                                    <?php if ($consultation['status'] === 'pending'): ?>
                                        <div class="flex gap-4 mt-4">
                                            <form action="update_consultation.php" method="POST">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['consultation_id']; ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-300">
                                                    Confirm
                                                </button>
                                            </form>
                                            <form action="update_consultation.php" method="POST">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['consultation_id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors duration-300">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300"> <h2 class="text-2xl font-bold text-black mb-6">Recent Reviews</h2> <?php if (empty($recent_reviews)): ?> <p class="text-gray-600">No reviews yet. Keep up the great work!</p> <?php else: ?> <div class="space-y-4"> <?php foreach ($recent_reviews as $review): ?> <div class="bg-gray-100/50 rounded-lg p-4 hover:bg-gray-200/50 transition-colors duration-300"> <div class="flex justify-between items-start mb-2"> <h3 class="font-semibold text-black"> <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?> </h3> <div class="flex text-yellow-500"> <?php for ($i = 1; $i <= 5; $i++): ?> <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-regular'; ?>"></i> <?php endfor; ?> </div> </div> <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($review['comment']); ?></p> </div> <?php endforeach; ?> </div> <?php endif; ?> </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="py-16 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg">
                <h2 class="text-2xl font-bold text-black mb-6 text-center">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="profile.php" class="bg-gray-800 text-white text-center py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-user-edit text-2xl mb-2"></i>
                        <div>Update Profile</div>
                    </a>
                    <a href="portfolio.php" class="bg-gray-800 text-white text-center py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-images text-2xl mb-2"></i>
                        <div>Manage Portfolio</div>
                    </a>
                    <a href="consultations.php" class="bg-gray-800 text-white text-center py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-calendar text-2xl mb-2"></i>
                        <div>View All Consultations</div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</main>

<!-- Critical CSS for Locomotive Scroll -->
<style>
    html, body {
        height: 100%;
        margin: 0;
        overflow: hidden;
    }
    [data-scroll-container] {
        min-height: 100vh;
        will-change: transform;
        backface-visibility: hidden;
        position: relative;
    }
    [data-scroll-section] {
        position: relative;
        z-index: 1;
    }
</style>

<!-- Include Locomotive Scroll and initialization script -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Wait for images and dynamic content to load
        window.addEventListener('load', () => {
            window.locomotiveScroll = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                multiplier: 1,
                lerp: 0.1,
                reloadOnContextChange: true
            });

            // Update scroll on window resize or content change
            const resizeObserver = new ResizeObserver(() => {
                window.locomotiveScroll.update();
            });
            resizeObserver.observe(document.querySelector('[data-scroll-container]'));

            // Ensure images are loaded before updating scroll
            const images = document.querySelectorAll('img');
            Promise.all(
                Array.from(images).map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(resolve => {
                        img.addEventListener('load', resolve);
                        img.addEventListener('error', resolve);
                    });
                })
            ).then(() => {
                window.locomotiveScroll.update();
            });
        });
    });
</script>

</body>
</html>