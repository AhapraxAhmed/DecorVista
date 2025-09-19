<?php
require_once '../config/config.php';

// Require designer login
requireLogin();
requireRole('designer');

$pageTitle = 'Your Consultations - DecorVista';
$designer_id = $_SESSION['user_id']; // Designer's user_id

// Fetch consultations for this designer
$consultations_query = "SELECT c.*, u.username, ud.first_name as client_first_name, ud.last_name as client_last_name, ud.phone
FROM consultations c
JOIN users u ON c.user_id = u.user_id
JOIN user_details ud ON u.user_id = ud.user_id
WHERE c.designer_id = ?
ORDER BY CONCAT(c.scheduled_date, ' ', c.scheduled_time) DESC";

$stmt = $db->prepare($consultations_query);
if (!$stmt) {
    die("Prepare failed: " . $db->getConnection()->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
</head>
<body>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pb-32">
    <!-- Hero Section -->
    <section class="relative py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <h1 class="text-5xl md:text-7xl font-bold text-black mb-6">
                Your <span class="text-gray-700">Consultations</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Manage your scheduled consultations, view client details, and track the progress of your design projects with ease.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="../dashboard.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="../designers.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-users mr-2"></i>Manage Profile
                </a>
                <a href="../products.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto">
            <h2 class="text-4xl font-bold text-black text-center mb-12">Manage Your Consultations</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-check text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Schedule & Track</h3>
                    <p class="text-gray-600">View upcoming and past consultations with all client details and project notes.</p>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comments text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Client Communication</h3>
                    <p class="text-gray-600">Access client requirements, notes, and contact information to provide exceptional service.</p>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-dollar-sign text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Billing & Payments</h3>
                    <p class="text-gray-600">Track consultation costs, durations, and total project expenses for accurate invoicing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Consultations List -->
    <?php if (empty($consultations)): ?>
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <div class="bg-white/80 backdrop-blur-lg p-12 rounded-xl shadow-lg">
                <i class="fas fa-calendar-times text-6xl text-gray-400 mb-6"></i>
                <h2 class="text-3xl font-bold text-black mb-4">No Consultations Found</h2>
                <p class="text-xl text-gray-600 mb-8">You don't have any consultations scheduled yet. Start connecting with clients to grow your portfolio.</p>
                <a href="../designers.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-edit mr-2"></i>Update Your Profile
                </a>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-black">Your Consultations (<?php echo count($consultations); ?>)</h2>
                <a href="../dashboard.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($consultations as $c):
                    $statusColor = match(strtolower($c['status'])) {
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-green-100 text-green-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    $clientName = trim(($c['client_first_name'] ?? $c['firstname'] ?? '') . ' ' . ($c['client_last_name'] ?? $c['lastname'] ?? ''));
                ?>
                <div class="bg-white/80 backdrop-blur-lg rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300 group">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-bold text-black"><?php echo htmlspecialchars($clientName ?: 'Unknown Client'); ?></h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                            <?php echo ucfirst(strtolower($c['status'])); ?>
                        </span>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                            <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($c['scheduled_date'])); ?> at <?php echo date('g:i A', strtotime($c['scheduled_time'])); ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-clock mr-2 text-gray-400"></i>
                            <span><strong>Duration:</strong> <?php echo intval($c['duration_hours']); ?> hours</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-dollar-sign mr-2 text-gray-400"></i>
                            <span><strong>Total Cost:</strong> $<?php echo number_format(floatval($c['total_cost']), 2); ?></span>
                        </div>
                    </div>

                    <?php if (!empty(trim($c['notes']))): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1 font-medium"><strong>Client Notes:</strong></p>
                        <div class="text-sm bg-gray-50 p-3 rounded-lg max-h-20 overflow-y-auto">
                            <?php echo nl2br(htmlspecialchars($c['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty(trim($c['client_requirements']))): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1 font-medium"><strong>Requirements:</strong></p>
                        <div class="text-sm bg-blue-50 p-3 rounded-lg max-h-20 overflow-y-auto">
                            <?php echo nl2br(htmlspecialchars($c['client_requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty(trim($c['phone']))): ?>
                    <div class="flex items-center text-gray-600 mb-4">
                        <i class="fas fa-phone mr-2 text-gray-400"></i>
                        <a href="tel:<?php echo htmlspecialchars($c['phone']); ?>" class="text-blue-600 hover:text-blue-800 font-medium underline">
                            <?php echo htmlspecialchars($c['phone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <small class="text-gray-400">
                            <i class="fas fa-clock mr-1"></i>
                            Created: <?php echo date('M d, Y g:i A', strtotime($c['created_at'])); ?>
                        </small>
                        <a href="consultation-details.php?id=<?php echo intval($c['consultation_id']); ?>" class="text-blue-600 hover:text-blue-800 font-medium text-sm group-hover:underline transition-colors duration-300 flex items-center">
                            View Details <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($consultations) > 6): ?>
            <div class="text-center mt-12">
                <button onclick="loadMoreConsultations()" class="bg-gray-800 text-white px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300 text-lg">
                    Load More Consultations
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <div class="bg-white/80 backdrop-blur-lg p-12 rounded-xl shadow-lg">
                <h2 class="text-4xl font-bold text-black mb-6">Ready to Take on More Projects?</h2>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Update your profile and showcase your portfolio to attract more clients and grow your design business.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../designer-edit.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-edit mr-2"></i>Edit Profile
                    </a>
                    <a href="../portfolio.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-images mr-2"></i>Manage Portfolio
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>


<!-- Locomotive Scroll -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('load', () => {
            if (typeof LocomotiveScroll !== 'undefined') {
                window.locomotiveScroll = new LocomotiveScroll({
                    el: document.querySelector('[data-scroll-container]'),
                    smooth: true,
                    multiplier: 1,
                    lerp: 0.1,
                    reloadOnContextChange: true
                });
                
                const resizeObserver = new ResizeObserver(() => {
                    window.locomotiveScroll.update();
            });
            resizeObserver.observe(document.querySelector('[data-scroll-container]'));
            
            const images = document.querySelectorAll('img');
            Promise.all(
                Array.from(images).map(img => img.complete ? Promise.resolve() : new Promise(resolve => {
                    img.addEventListener('load', resolve);
                    img.addEventListener('error', resolve);
                }))
            ).then(() => window.locomotiveScroll.update());
        }
    });
});

function loadMoreConsultations() {
    alert('Load more functionality would be implemented here with AJAX pagination.');
}
</script>

</body>
</html>
