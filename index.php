<?php
require_once 'config/config.php';

$pageTitle = 'DecorVista - Transform Your Space';

// Get featured products
// Featured Products
$featured_products_query = "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 8";
$products_result = $db->query($featured_products_query);
if (!$products_result) {
    die("Products query failed: " . $db->getConnection()->error);
}
$featured_products = $products_result->fetch_all(MYSQLI_ASSOC);

// Featured Interior Designers
$designers_query = "SELECT designer_id, first_name, last_name, specialization, years_experience, portfolio_url, bio, hourly_rate, rating, total_reviews
                    FROM interior_designers
                    WHERE is_verified = 1
                    ORDER BY created_at DESC
                    LIMIT 6";
$designers_result = $db->query($designers_query);
if (!$designers_result) {
    die("Designers query failed: " . $db->getConnection()->error);
}
$featured_designers = $designers_result->fetch_all(MYSQLI_ASSOC);


include 'includes/header.php';
?>

<!-- Add data-scroll-container for Locomotive Scroll -->
<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300">
    <!-- Hero Section -->
    <section class="relative py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <h1 class="text-5xl md:text-7xl font-bold text-black mb-6">
                Transform Your <span class="text-gray-700">Space</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Discover stunning interior designs, shop premium products, and connect with professional designers to create your dream home.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="./products.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-shopping-bag mr-2"></i>Shop Products
                </a>
                <a href="./designers.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-paint-brush mr-2"></i>Find Designers
                </a>
                <a href="./gallery.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                    <i class="fas fa-images mr-2"></i>Browse Gallery
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto">
            <h2 class="text-4xl font-bold text-black text-center mb-12">Why Choose DecorVista?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-palette text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Expert Designers</h3>
                    <p class="text-gray-600">Connect with professional interior designers who understand your vision and bring it to life.</p>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Premium Products</h3>
                    <p class="text-gray-600">Shop from a curated collection of high-quality furniture and decor items from top brands.</p>
                </div>
                <div class="bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lightbulb text-2xl text-gray-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-4">Endless Inspiration</h3>
                    <p class="text-gray-600">Browse thousands of design ideas and save your favorites to create your perfect space.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<section class="py-20 px-4" data-scroll-section>
    <div class="container mx-auto">
        <div class="flex justify-between items-center mb-12">
            <h2 class="text-4xl font-bold text-black">Featured Products</h2>
            <a href="products.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors duration-300">View All Products</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured_products as $product): ?>
                <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                    <div class="w-24 h-24 rounded-full overflow-hidden mx-auto mb-4 bg-gray-200">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-700 text-2xl">
                                <i class="fas fa-box"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-2">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="text-gray-800 font-bold mb-4">$<?php echo htmlspecialchars($product['price']); ?></p>
                    <a href="product.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        View Product
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="py-20 px-4" data-scroll-section>
    <div class="container mx-auto text-center">
        <p class="text-gray-600 text-lg">No featured products available at the moment. Please check back later.</p>
    </div>
</section>
<?php endif; ?>


    <!-- Featured Designers -->
    <?php if (!empty($featured_designers)): ?>
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-4xl font-bold text-black">Featured Designers</h2>
                <a href="designers.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors duration-300">View All Designers</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($featured_designers, 0, 3) as $designer): ?>
                    <div class="bg-white/80 backdrop-blur-lg p-6 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
                        <div class="w-24 h-24 rounded-full overflow-hidden mx-auto mb-4 bg-gray-200">
                            <?php if (!empty($designer['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($designer['profile_image']); ?>" alt="Designer" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-700 text-2xl">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-xl font-bold text-black mb-2">
                            <?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($designer['specialization'] ?? 'Interior Designer'); ?></p>
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($designer['years_of_experience'] ?? 0); ?> years experience</p>
                        <a href="designer-profile.php?id=<?php echo htmlspecialchars($designer['designer_id'] ?? ''); ?>" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                            View Profile
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <p class="text-gray-600 text-lg">No featured designers available at the moment.</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="container mx-auto text-center">
            <div class="bg-white/80 backdrop-blur-lg p-12 rounded-xl shadow-lg">
                <h2 class="text-4xl font-bold text-black mb-6">Ready to Transform Your Space?</h2>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Join thousands of homeowners who have created their dream spaces with DecorVista.
                </p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-user-plus mr-2"></i>Get Started Today
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="bg-gray-800 text-white text-lg px-8 py-4 rounded-lg hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
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