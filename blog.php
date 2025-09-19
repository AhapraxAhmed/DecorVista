<?php
require_once 'config/config.php';

$pageTitle = 'Blog - DecorVista';

// ----------- GET CATEGORIES -----------
$categories = [];
$categories_query = "SELECT DISTINCT category FROM blog_posts ORDER BY category";
$categories_result = $db->getConnection()->query($categories_query);

if ($categories_result) {
    $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Categories query failed: " . $db->getConnection()->error);
}

// ----------- GET FILTERS -----------
$category_filter = $_GET['category'] ?? '';
$search_filter = $_GET['search'] ?? '';

// ----------- BUILD QUERY -----------
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($category_filter)) {
    $where_conditions[] = "b.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search_filter)) {
    $where_conditions[] = "(b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);
$blog_query = "SELECT b.* FROM blog_posts b WHERE $where_clause ORDER BY b.created_at DESC";

// ----------- PREPARE AND EXECUTE -----------
$blog_posts = [];
$stmt = $db->getConnection()->prepare($blog_query);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $blog_posts = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Blog posts get_result failed: " . $db->getConnection()->error);
    }

    $stmt->close();
} else {
    error_log("Blog posts prepare failed: " . $db->getConnection()->error);
}

// ----------- INCLUDE HEADER -----------
include 'includes/header.php';
?>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pb-20">
    <!-- Header Section -->
    <section class="py-12 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-black mb-4">Design Insights & Inspiration</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
                Explore our latest blog posts for tips, trends, and ideas to transform your space.
            </p>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl p-6 mb-8 shadow-lg">
                <form method="GET" action="" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:space-x-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-search mr-2 text-gray-700"></i>Search Posts
                        </label>
                        <input type="text" id="search" name="search" 
                               class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                               placeholder="Search by title or content..."
                               value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="lg:w-48">
                        <label for="category" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-tags mr-2 text-gray-700"></i>Category
                        </label>
                        <select id="category" name="category" 
                                class="flex h-10 w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                                        <?php echo $category_filter === $category['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-md font-medium hover:bg-gray-900 transition-colors duration-300">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
                
                <!-- Active Filters -->
                <?php if (!empty($search_filter) || !empty($category_filter)): ?>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-600">Active filters:</span>
                            
                            <?php if (!empty($search_filter)): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Search: "<?php echo htmlspecialchars($search_filter); ?>"
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($category_filter)): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Category: <?php echo htmlspecialchars($category_filter); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <a href="/blog.php" class="text-sm text-gray-700 hover:text-gray-900 transition-colors duration-300">
                                <i class="fas fa-times-circle mr-1"></i>Clear all filters
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Blog Posts -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <?php if (!empty($blog_posts)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    <?php foreach ($blog_posts as $index => $post): ?>
                        <div class="relative group bg-white/80 backdrop-blur-lg rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1" style="animation-delay: <?php echo ($index % 6) * 0.1; ?>s">
                            <!-- Image with Overlay -->
                            <?php if (!empty($post['image_url'])): ?>
                                <div class="relative h-44 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                                        <h3 class="text-white font-semibold text-lg line-clamp-2">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="h-64 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-500"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Card Content -->
                            <div class="p-6 flex flex-col justify-between h-56">
                                <div>
                                    <h3 class="font-heading text-lg font-semibold text-black mb-2 line-clamp-2">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </h3>
                                    <?php if (!empty($post['category'])): ?>
                                        <span class="text-xs text-gray-600 font-medium uppercase tracking-wide mb-2 inline-block">
                                            <?php echo htmlspecialchars($post['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                        <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 120)); ?>...
                                    </p>
                                </div>

                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-calendar-alt text-gray-700"></i>
                                        <span><?php echo htmlspecialchars(date('M d, Y', strtotime($post['created_at']))); ?></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user text-gray-700"></i>
                                        <span><?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Read More Button -->
                            <a href="blog_post.php?id=<?php echo $post['id']; ?>" 
                               class="absolute bottom-0 left-0 right-0 bg-gray-800 text-white text-center py-3 font-medium transition-all duration-300 group-hover:bg-gray-900 hover:no-underline">
                                <i class="fas fa-arrow-right mr-1"></i>Read More
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Results Summary -->
                <div class="text-center mb-8">
                    <p class="text-gray-600">
                        Showing <?php echo count($blog_posts); ?> blog posts
                        <?php if (!empty($category_filter)): ?>
                            in "<?php echo htmlspecialchars($category_filter); ?>" category
                        <?php endif; ?>
                        <?php if (!empty($search_filter)): ?>
                            for "<?php echo htmlspecialchars($search_filter); ?>"
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-white/80 backdrop-blur-lg rounded-xl p-12 text-center shadow-lg">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-newspaper text-3xl text-gray-500"></i>
                    </div>
                    <h3 class="font-heading text-xl font-semibold text-black mb-2">No Blog Posts Found</h3>
                    <p class="text-gray-600 mb-6">Try adjusting your search criteria or check back later for new posts.</p>
                    <a href="/blog.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Browse All Posts
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</main>

<!-- Critical CSS for Locomotive Scroll -->
<style>
    html, body { height: 100%; margin: 0; overflow: hidden; }
    [data-scroll-container] { min-height: 100vh; will-change: transform; backface-visibility: hidden; position: relative; }
    [data-scroll-section] { position: relative; z-index: 1; }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-4 {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<!-- Locomotive Scroll -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('load', () => {
        try {
            window.locomotiveScroll = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                multiplier: 1,
                lerp: 0.1,
                reloadOnContextChange: true
            });

            const resizeObserver = new ResizeObserver(() => {
                try {
                    window.locomotiveScroll.update();
                } catch (error) {
                    console.error('Error updating Locomotive Scroll:', error);
                }
            });
            resizeObserver.observe(document.querySelector('[data-scroll-container]'));

            const images = document.querySelectorAll('img');
            Promise.all(Array.from(images).map(img => {
                if (img.complete) return Promise.resolve();
                return new Promise(resolve => {
                    img.addEventListener('load', resolve);
                    img.addEventListener('error', () => {
                        console.warn(`Image failed to load: ${img.src}`);
                        resolve();
                    });
                });
            })).then(() => {
                try {
                    window.locomotiveScroll.update();
                } catch (error) {
                    console.error('Error updating Locomotive Scroll after images load:', error);
                }
            }).catch(error => {
                console.error('Error processing images:', error);
            });
        } catch (error) {
            console.error('Error initializing Locomotive Scroll:', error);
        }
    });
});
</script>