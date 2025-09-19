<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Gallery - Admin';

// Ensure uploads directory exists and is writable
$upload_dir = __DIR__ . '/../Uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle gallery actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_image') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $room_type = trim($_POST['room_type'] ?? '');
        $image = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image = uniqid('gallery_') . '.' . $ext;
                $destination = $upload_dir . $image;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image file. Use JPEG or PNG, max 5MB.";
            }
        }

        // Server-side validation
        if (!empty($title) && !empty($room_type)) {
            if (isset($error)) {
                // Error already set from image upload
            } else {
                $insert_query = "INSERT INTO gallery (title, description, room_type, image, is_active, created_at) 
                                VALUES (?, ?, ?, ?, 1, NOW())";
                $stmt = $db->prepare($insert_query);
                if (!$stmt) {
                    $error = "SQL prepare failed: " . $db->error;
                } else {
                    $stmt->bind_param("ssss", $title, $description, $room_type, $image);
                    if ($stmt->execute()) {
                        header('Location: gallery.php?msg=Image added successfully');
                        exit;
                    } else {
                        $error = "SQL execute failed: " . $stmt->error;
                    }
                }
            }
        } else {
            $error = "Required fields (Title, Room Type) are missing.";
        }
    } elseif ($action === 'toggle_status') {
        $gallery_id = (int)($_POST['gallery_id'] ?? 0);
        if ($gallery_id > 0) {
            $toggle_query = "UPDATE gallery SET is_active = NOT is_active WHERE gallery_id = ?";
            $stmt = $db->prepare($toggle_query);
            if (!$stmt) {
                $error = "SQL prepare failed: " . $db->error;
            } else {
                $stmt->bind_param("i", $gallery_id);
                if ($stmt->execute()) {
                    header('Location: gallery.php?msg=Image status updated');
                    exit;
                } else {
                    $error = "SQL execute failed: " . $stmt->error;
                }
            }
        } else {
            $error = "Invalid gallery ID.";
        }
    } elseif ($action === 'delete_image') {
        $gallery_id = (int)($_POST['gallery_id'] ?? 0);
        if ($gallery_id > 0) {
            // Get existing image to delete
            $query = "SELECT image FROM gallery WHERE gallery_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $gallery_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc();
            if ($image && !empty($image['image']) && file_exists($upload_dir . $image['image'])) {
                unlink($upload_dir . $image['image']);
            }

            $delete_query = "DELETE FROM gallery WHERE gallery_id = ?";
            $stmt = $db->prepare($delete_query);
            if (!$stmt) {
                $error = "SQL prepare failed: " . $db->error;
            } else {
                $stmt->bind_param("i", $gallery_id);
                if ($stmt->execute()) {
                    header('Location: gallery.php?msg=Image deleted successfully');
                    exit;
                } else {
                    $error = "SQL execute failed: " . $stmt->error;
                }
            }
        } else {
            $error = "Invalid gallery ID.";
        }
    }
}

// Get gallery images
$search = $_GET['search'] ?? '';
$room_type_filter = $_GET['room_type'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if (!empty($room_type_filter)) {
    $where_conditions[] = "room_type = ?";
    $params[] = $room_type_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

$images_query = "SELECT * FROM gallery WHERE $where_clause ORDER BY created_at DESC";
$stmt = $db->prepare($images_query);
if (!$stmt) {
    $error = "SQL Error in gallery query: " . $db->error;
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $gallery_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get categories for filter (room_type)
$categories_query = "SELECT DISTINCT room_type FROM gallery WHERE room_type IS NOT NULL AND room_type != '' ORDER BY room_type";
$categories_result = $db->query($categories_query);
$categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

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
                        <h1 class="text-4xl font-extrabold text-black mb-2">Manage Gallery</h1>
                        <p class="text-gray-600 text-lg">Add and manage inspiration gallery images</p>
                    </div>
                    <div class="flex gap-4">
                        <button onclick="openAddModal()" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <i class="fas fa-plus mr-2"></i>Add Image
                        </button>
                        <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                               placeholder="Search images by title or description..." 
                               class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                    </div>
                    <div class="md:w-48">
                        <select name="room_type" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="">All Room Types</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['room_type'] ?? ''); ?>" 
                                        <?php echo ($room_type_filter ?? '') === $category['room_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($category['room_type'] ?? 'N/A')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Gallery Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" data-scroll data-scroll-speed="2">
                <?php if (empty($gallery_images)): ?>
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Images Found</h3>
                        <p class="text-gray-500 text-base">Try adjusting your search criteria or add a new image.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($gallery_images as $image): ?>
                        <div class="relative bg-gray-100 rounded-xl overflow-hidden shadow-lg transform transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-black/20 group tilt-card">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <div class="relative">
                                <?php if (!empty($image['image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../Uploads/' . $image['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($image['title'] ?? 'Untitled'); ?>"
                                         class="w-full h-56 object-cover">
                                <?php endif; ?>
                                <div class="absolute top-3 right-3">
                                    <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo ($image['is_active'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ($image['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="font-semibold text-black text-lg mb-2"><?php echo htmlspecialchars($image['title'] ?? 'Untitled'); ?></h3>
                                <p class="text-gray-600 text-base mb-3 line-clamp-3"><?php echo htmlspecialchars($image['description'] ?? 'No description available.'); ?></p>
                                <span class="inline-block px-4 py-2 bg-gray-200 text-black text-sm font-medium rounded-full mb-4">
                                    <?php echo htmlspecialchars(ucfirst($image['room_type'] ?? 'N/A')); ?>
                                </span>
                                <div class="flex gap-3">
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="gallery_id" value="<?php echo (int)($image['gallery_id'] ?? 0); ?>">
                                        <button type="submit" class="w-full relative bg-gray-200 rounded-xl py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" 
                                                onclick="return confirm('Are you sure you want to <?php echo ($image['is_active'] ?? 0) ? 'deactivate' : 'activate'; ?> this image?')">
                                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                            <?php echo ($image['is_active'] ?? 0) ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="delete_image">
                                        <input type="hidden" name="gallery_id" value="<?php echo (int)($image['gallery_id'] ?? 0); ?>">
                                        <button type="submit" class="w-full relative bg-gray-300 rounded-xl py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" 
                                                onclick="return confirm('Are you sure you want to delete this image?')">
                                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Image Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 sm:p-6">
    <div class="bg-gray-100 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-auto shadow-lg">
        <div class="flex justify-between items-center p-6 border-b border-gray-300">
            <h3 class="text-xl font-bold text-black">Add New Image</h3>
            <button onclick="closeAddModal()" class="text-gray-600 hover:text-black">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6" enctype="multipart/form-data" onsubmit="return validateAddForm()">
            <input type="hidden" name="action" value="add_image">
            <div class="mb-4">
                <label class="block text-black font-semibold text-base mb-2">Title *</label>
                <input type="text" name="title" required 
                       class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
            </div>
            <div class="mb-4">
                <label class="block text-black font-semibold text-base mb-2">Room Type *</label>
                <select name="room_type" required 
                        class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                    <option value="">Select Room Type</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['room_type'] ?? ''); ?>">
                            <?php echo htmlspecialchars(ucfirst($category['room_type'] ?? 'N/A')); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="living_room">Living Room</option>
                    <option value="bedroom">Bedroom</option>
                    <option value="kitchen">Kitchen</option>
                    <option value="bathroom">Bathroom</option>
                    <option value="office">Office</option>
                    <option value="outdoor">Outdoor</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-black font-semibold text-base mb-2">Image (JPEG/PNG, max 5MB)</label>
                <input type="file" name="image" accept="image/jpeg,image/png" 
                       class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
            </div>
            <div class="mb-6">
                <label class="block text-black font-semibold text-base mb-2">Description</label>
                <textarea name="description" rows="4" 
                          class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black"></textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="relative bg-gray-200 rounded-xl flex-1 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    Add Image
                </button>
                <button type="button" onclick="closeAddModal()" class="relative bg-gray-300 rounded-xl flex-1 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Locomotive Scroll JS -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.js"></script>
<!-- Vanilla Tilt for 3D tilt effect -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.2/vanilla-tilt.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Locomotive Scroll
        let scroll;
        try {
            scroll = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                lerp: 0.08,
                smartphone: { smooth: true },
                tablet: { smooth: true }
            });

            // Update scroll after load to ensure content visibility
            setTimeout(() => {
                scroll.update();
                setTimeout(() => scroll.update(), 500);
            }, 200);
        } catch (e) {
            console.error('Locomotive Scroll initialization failed:', e);
        }

        // Initialize Vanilla Tilt for gallery cards
        try {
            VanillaTilt.init(document.querySelectorAll('.tilt-card'), {
                max: 10,
                speed: 400,
                glare: true,
                'max-glare': 0.2
            });
        } catch (e) {
            console.error('Vanilla Tilt initialization failed:', e);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAddModal();
            }
        });
    });

    function openAddModal() {
        try {
            console.log('Opening Add Modal');
            const modal = document.getElementById('addModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.opacity = '1';
            modal.querySelector('div').classList.remove('scale-95');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } catch (e) {
            console.error('Error opening Add Modal:', e);
        }
    }

    function closeAddModal() {
        try {
            console.log('Closing Add Modal');
            const modal = document.getElementById('addModal');
            modal.style.opacity = '0';
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.opacity = '';
                document.body.style.overflow = ''; // Restore background scrolling
            }, 300);
        } catch (e) {
            console.error('Error closing Add Modal:', e);
        }
    }

    function validateAddForm() {
        const form = document.getElementById('addModal').querySelector('form');
        const title = form.querySelector('input[name="title"]').value.trim();
        const room_type = form.querySelector('select[name="room_type"]').value;
        const image = form.querySelector('input[name="image"]').files[0];

        if (!title) {
            alert('Title is required.');
            return false;
        }
        if (!room_type) {
            alert('Room Type is required.');
            return false;
        }
        if (image) {
            const allowedTypes = ['image/jpeg', 'image/png'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowedTypes.includes(image.type)) {
                alert('Image must be JPEG or PNG.');
                return false;
            }
            if (image.size > maxSize) {
                alert('Image size must not exceed 5MB.');
                return false;
            }
        }
        return true;
    }
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

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ensure modals are above all content */
    #addModal {
        z-index: 1000;
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