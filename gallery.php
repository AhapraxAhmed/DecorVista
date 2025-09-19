<?php
require_once 'config/config.php';

$pageTitle = 'Inspiration Gallery - DecorVista';

// Use raw mysqli connection for consistency
$conn = $db->getConnection();

// Get categories for filter (use room_type instead of category)
$categories_query = "SELECT DISTINCT room_type FROM gallery WHERE is_active = 1 ORDER BY room_type";
$categories_result = $conn->query($categories_query);
if (!$categories_result) {
    die("Categories query failed: " . $conn->error);
}
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Build query for main gallery
$where_conditions = ["g.is_active = 1"];
$params = [];
$types = "";

if (!empty($category_filter)) {
    $where_conditions[] = "g.room_type = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search_filter)) {
    $where_conditions[] = "(g.title LIKE ? OR g.description LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

$gallery_query = "SELECT g.*, 
                        CASE WHEN f.gallery_id IS NOT NULL THEN 1 ELSE 0 END as is_favorited,
                        ud.first_name AS designer_first_name, ud.last_name AS designer_last_name
                 FROM gallery g
                 LEFT JOIN favorites f ON g.gallery_id = f.gallery_id AND f.user_id = ?
                 LEFT JOIN interior_designers id ON g.designer_id = id.designer_id
                 LEFT JOIN user_details ud ON id.user_id = ud.user_id
                 WHERE $where_clause
                 ORDER BY g.created_at DESC";

$user_id = $_SESSION['user_id'] ?? 0;
array_unshift($params, $user_id);
$types = "i" . $types;

$stmt = $conn->prepare($gallery_query);
if (!$stmt) {
    die("Gallery query preparation failed: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$gallery_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch favorited images for logged-in user
$favorites = [];
if ($user_id) {
    $favorites_query = "SELECT g.*, 
                           ud.first_name AS designer_first_name, ud.last_name AS designer_last_name
                    FROM gallery g
                    INNER JOIN favorites f ON g.gallery_id = f.gallery_id AND f.user_id = ?
                    LEFT JOIN interior_designers id ON g.designer_id = id.designer_id
                    LEFT JOIN user_details ud ON id.user_id = ud.user_id
                    WHERE g.is_active = 1
                    ORDER BY f.created_at DESC";
    $stmt = $conn->prepare($favorites_query);
    if (!$stmt) {
        die("Favorites query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

include 'includes/header.php';
?>

<!-- Add data-scroll-container for Locomotive Scroll -->
<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pt-16">
    <section class="container mx-auto px-4 py-8" data-scroll-section>
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl font-bold text-black mb-4 font-heading">
                Inspiration <span class="text-gray-700">Gallery</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Discover stunning interior designs and get inspired for your next project
            </p>
        </div>

        <!-- Favorites Section -->
        <?php if ($user_id && !empty($favorites)): ?>
            <div class="mb-12">
                <h2 class="text-2xl md:text-3xl font-bold text-black mb-6 font-heading">Your Favorites</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($favorites as $image): ?>
                        <div class="relative">
                            <button class="favorite-btn absolute top-4 right-4 w-10 h-10 rounded-full bg-black/50 flex items-center justify-center text-white hover:bg-black/70 transition-colors z-10" 
                                    data-gallery-id="<?php echo $image['gallery_id']; ?>">
                                <i class="fas fa-heart text-gray-700"></i>
                            </button>
                            <div class="bg-white/80 backdrop-blur-lg overflow-hidden group cursor-pointer hover:shadow-xl transition-shadow duration-300" data-gallery-id="<?php echo $image['gallery_id']; ?>">
                                <div class="relative">
                                    <img src=".<?php echo htmlspecialchars($image['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                                         class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                </div>
                                
                                <div class="p-4">
                                    <h3 class="font-semibold text-black mb-2"><?php echo htmlspecialchars($image['title']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($image['description']); ?></p>
                                    <p class="text-gray-600 text-sm mb-2">
                                        <span class="font-medium">Designer:</span> 
                                        <?php echo htmlspecialchars($image['designer_first_name'] . ' ' . $image['designer_last_name'] ?: 'Unknown Designer'); ?>
                                    </p>
                                    <span class="inline-block px-3 py-1 bg-gray-200 text-gray-800 text-xs rounded-full">
                                        <?php echo htmlspecialchars(ucfirst($image['room_type'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white/80 backdrop-blur-lg p-6 mb-8 rounded-xl">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" 
                           placeholder="Search designs..." 
                           class="w-full px-4 py-3 bg-white/30 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500">
                </div>
                <div class="md:w-48">
                    <select name="category" class="w-full px-4 py-3 bg-white/30 border border-gray-300 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['room_type']); ?>" 
                                    <?php echo $category_filter === $category['room_type'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($category['room_type'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-all duration-300">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </form>
        </div>

        <!-- Gallery Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($gallery_images as $image): ?>
                <div class="relative">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="favorite-btn absolute top-4 right-4 w-10 h-10 rounded-full bg-black/50 flex items-center justify-center text-white hover:bg-black/70 transition-colors z-10" 
                                data-gallery-id="<?php echo $image['gallery_id']; ?>">
                            <i class="fas fa-heart <?php echo $image['is_favorited'] ? 'text-gray-700' : 'text-gray-400'; ?>"></i>
                        </button>
                    <?php endif; ?>
                    <div class="bg-white/80 backdrop-blur-lg overflow-hidden group cursor-pointer hover:shadow-xl transition-shadow duration-300" data-gallery-id="<?php echo $image['gallery_id']; ?>">
                        <div class="relative">
                            <img src=".<?php echo htmlspecialchars($image['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['title']); ?>"
                                 class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-black mb-2"><?php echo htmlspecialchars($image['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($image['description']); ?></p>
                            <p class="text-gray-600 text-sm mb-2">
                                <span class="font-medium">Designer:</span> 
                                <?php echo htmlspecialchars($image['designer_first_name'] . ' ' . $image['designer_last_name'] ?: 'Unknown Designer'); ?>
                            </p>
                            <span class="inline-block px-3 py-1 bg-gray-200 text-gray-800 text-xs rounded-full">
                                <?php echo htmlspecialchars(ucfirst($image['room_type'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($gallery_images)): ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl text-black font-semibold mb-2 font-heading">No images found</h3>
                <p class="text-gray-600">Try adjusting your search or filter criteria</p>
            </div>
        <?php endif; ?>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</main>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white backdrop-blur-lg max-w-4xl max-h-full overflow-auto rounded-xl">
        <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h3 id="modalTitle" class="text-xl font-bold text-black font-heading"></h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-4">
            <img id="modalImage" src="/placeholder.svg" alt="" class="w-full h-auto rounded-lg mb-4">
            <p id="modalDescription" class="text-gray-600"></p>
            <p id="modalDesigner" class="text-gray-600 mt-2">
                <span class="font-medium">Designer:</span> <span id="modalDesignerName"></span>
            </p>
        </div>
    </div>
</div>

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

<!-- Toastify for notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<!-- Include Locomotive Scroll and initialization script -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    let locomotiveScroll = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Locomotive Scroll
        window.addEventListener('load', () => {
            locomotiveScroll = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                multiplier: 1,
                lerp: 0.1,
                reloadOnContextChange: true
            });

            // Update scroll on window resize or content change
            const resizeObserver = new ResizeObserver(() => {
                if (locomotiveScroll) {
                    locomotiveScroll.update();
                }
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
                if (locomotiveScroll) {
                    locomotiveScroll.update();
                }
            });

            // Add click handlers for gallery items (excluding favorite buttons)
            document.querySelectorAll('[data-gallery-id] > div').forEach(item => {
                item.addEventListener('click', () => {
                    const imageId = item.parentElement.getAttribute('data-gallery-id');
                    openImageModal(imageId);
                });
            });

            // Add click handlers for favorite buttons
            document.querySelectorAll('.favorite-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const imageId = btn.getAttribute('data-gallery-id');
                    toggleFavorite(imageId);
                });
            });
        });
    });

    function openImageModal(imageId) {
        const images = <?php echo json_encode($gallery_images); ?>;
        const image = images.find(img => img.gallery_id == imageId);
        
        if (image) {
            document.getElementById('modalTitle').textContent = image.title;
            document.getElementById('modalImage').src = '.'+image.image_url;
            document.getElementById('modalImage').alt = image.title;
            document.getElementById('modalDescription').textContent = image.description;
            document.getElementById('modalDesignerName').textContent = 
                image.designer_first_name && image.designer_last_name 
                    ? `${image.designer_first_name} ${image.designer_last_name}` 
                    : 'Unknown Designer';
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
            
            // Disable Locomotive Scroll while modal is open
            if (locomotiveScroll) {
                locomotiveScroll.stop();
            }
        }
    }

    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.getElementById('imageModal').classList.remove('flex');
        
        // Re-enable Locomotive Scroll
        if (locomotiveScroll) {
            setTimeout(() => locomotiveScroll.start(), 100);
            setTimeout(() => locomotiveScroll.update(), 200);
        }
    }

    function toggleFavorite(imageId) {
        <?php if (!isLoggedIn()): ?>
            window.location.href = './login.php';
            return;
        <?php endif; ?>
        
        const formData = new FormData();
        formData.append('gallery_id', imageId);
        formData.append('csrf_token', '<?php echo htmlspecialchars(generateCSRFToken()); ?>');
        
        fetch('./api/toggle-favorite.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const button = document.querySelector(`.favorite-btn[data-gallery-id="${imageId}"]`);
                const icon = button.querySelector('i');
                if (data.added) {
                    icon.classList.remove('text-gray-400');
                    icon.classList.add('text-gray-700');
                    showNotification('Added to favorites!', 'success');
                } else {
                    icon.classList.remove('text-gray-700');
                    icon.classList.add('text-gray-400');
                    showNotification('Removed from favorites', 'info');
                }
                // Refresh page to update favorites section
                setTimeout(() => {
                    window.location.reload();
                }, 1000); // Delay to allow notification to be seen
            } else {
                showNotification(data.message || 'Failed to update favorite', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating favorite', 'error');
        });
    }

    function showNotification(message, type = 'info') {
        Toastify({
            text: message,
            duration: 3000,
            gravity: 'top',
            position: 'right',
            backgroundColor: type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6',
            stopOnFocus: true
        }).showToast();

        if (locomotiveScroll) {
            setTimeout(() => locomotiveScroll.update(), 100);
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
</script>

</body>
</html>