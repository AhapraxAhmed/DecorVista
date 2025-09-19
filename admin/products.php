<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Products - Admin';

// Ensure uploads directory exists and is writable
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create Product
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $dimensions = trim($_POST['dimensions'] ?? '');
        $materials = trim($_POST['materials'] ?? '');
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $image = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image = uniqid('prod_') . '.' . $ext;
                $destination = $upload_dir . $image;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image file. Use JPEG or PNG, max 5MB.";
            }
        }

        // Server-side validation
        if (!empty($name) && $category_id > 0 && $price > 0) {
            if (isset($error)) {
                // Error already set from image upload
            } else {
                $insert_query = "INSERT INTO products 
                                (name, category_id, brand, price, description, image, dimensions, materials, stock_quantity, is_active, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                $stmt = $db->prepare($insert_query);
                if (!$stmt) {
                    $error = "SQL prepare failed: " . $db->error;
                } else {
                    $stmt->bind_param("sisdsssis", $name, $category_id, $brand, $price, $description, $image, $dimensions, $materials, $stock_quantity);
                    if ($stmt->execute()) {
                        header('Location: products.php?msg=Product added successfully');
                        exit;
                    } else {
                        $error = "SQL execute failed: " . $stmt->error;
                    }
                }
            }
        } else {
            $error = "Name, category, and price are required.";
        }
    } 
    // Update Product
    elseif ($action === 'update_product') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $dimensions = trim($_POST['dimensions'] ?? '');
        $materials = trim($_POST['materials'] ?? '');
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $image = trim($_POST['existing_image'] ?? '');

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image = uniqid('prod_') . '.' . $ext;
                $destination = $upload_dir . $image;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = "Failed to upload image.";
                } elseif (!empty($_POST['existing_image']) && file_exists($upload_dir . $_POST['existing_image'])) {
                    unlink($upload_dir . $_POST['existing_image']); // Delete old image
                }
            } else {
                $error = "Invalid image file. Use JPEG or PNG, max 5MB.";
            }
        }

        // Server-side validation
        if ($product_id > 0 && !empty($name) && $category_id > 0 && $price > 0) {
            if (isset($error)) {
                // Error already set from image upload
            } else {
                $update_query = "UPDATE products 
                                SET name = ?, category_id = ?, brand = ?, price = ?, description = ?, 
                                    image = ?, dimensions = ?, materials = ?, stock_quantity = ?, updated_at = NOW()
                                WHERE product_id = ?";
                $stmt = $db->prepare($update_query);
                if (!$stmt) {
                    $error = "SQL prepare failed: " . $db->error;
                } else {
                    $stmt->bind_param("sisdsssis", $name, $category_id, $brand, $price, $description, $image, $dimensions, $materials, $stock_quantity, $product_id);
                    if ($stmt->execute()) {
                        header('Location: products.php?msg=Product updated successfully');
                        exit;
                    } else {
                        $error = "SQL execute failed: " . $stmt->error;
                    }
                }
            }
        } else {
            $error = "Product ID, name, category, and price are required.";
        }
    } 
    // Delete Product
    elseif ($action === 'delete_product') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            // Get existing image to delete
            $query = "SELECT image FROM products WHERE product_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            if ($product && !empty($product['image']) && file_exists($upload_dir . $product['image'])) {
                unlink($upload_dir . $product['image']);
            }

            $delete_query = "DELETE FROM products WHERE product_id = ?";
            $stmt = $db->prepare($delete_query);
            if (!$stmt) {
                $error = "SQL prepare failed: " . $db->error;
            } else {
                $stmt->bind_param("i", $product_id);
                if ($stmt->execute()) {
                    header('Location: products.php?msg=Product deleted successfully');
                    exit;
                } else {
                    $error = "SQL execute failed: " . $stmt->error;
                }
            }
        } else {
            $error = "Invalid product ID.";
        }
    } 
    // Toggle Status
    elseif ($action === 'toggle_status') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            $toggle_query = "UPDATE products SET is_active = NOT is_active WHERE product_id = ?";
            $stmt = $db->prepare($toggle_query);
            if (!$stmt) {
                $error = "SQL prepare failed: " . $db->error;
            } else {
                $stmt->bind_param("i", $product_id);
                if ($stmt->execute()) {
                    header('Location: products.php?msg=Product status updated');
                    exit;
                } else {
                    $error = "SQL execute failed: " . $stmt->error;
                }
            }
        } else {
            $error = "Invalid product ID.";
        }
    }
}

// Get products
$search = $_GET['search'] ?? '';
$category_filter = (int)($_GET['category_id'] ?? 0);

$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

$products_query = "SELECT p.*, c.name AS category_name
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.category_id
                   WHERE $where_clause
                   ORDER BY p.created_at DESC";

$stmt = $db->prepare($products_query);
if ($stmt === false) {
    $error = "Prepare failed: " . $db->error;
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        $error = "SQL execute failed: " . $stmt->error;
    }
}

// Get categories for filter and form dropdowns
$categories_query = "SELECT category_id, name AS category_name 
                     FROM categories 
                     ORDER BY name";
$result = $db->query($categories_query);
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<!-- Locomotive Scroll CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.css">
<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="bg-gradient-to-br from-gray-50 to-gray-200 text-black" data-scroll-container>
    <div data-scroll-section class="pb-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 mb-24">
            <!-- Error/Success Message -->
            <?php if (isset($error)) { ?>
                <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-8 shadow-md" data-scroll data-scroll-speed="1">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } elseif (isset($_GET['msg'])) { ?>
                <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-8 shadow-md" data-scroll data-scroll-speed="1">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php } ?>

            <!-- Header -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg transform transition-all duration-500 hover:shadow-2xl hover:shadow-black/20" data-scroll data-scroll-speed="1">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-4xl font-extrabold text-black mb-2">Manage Products</h1>
                        <p class="text-gray-600 text-lg">Add, edit, and manage product catalog</p>
                    </div>
                    <div class="flex gap-4">
                        <button onclick="openAddModal()" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <i class="fas fa-plus mr-2"></i>Add Product
                        </button>
                        <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-500 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4" id="filterForm">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                               placeholder="Search products..." 
                               class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                    </div>
                    <div class="md:w-48">
                        <select name="category_id" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo (int)($category['category_id'] ?? 0); ?>" 
                                        <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name'] ?? 'N/A'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Products List -->
            <div class="bg-gray-100 rounded-2xl p-8 shadow-lg" data-scroll data-scroll-speed="2">
                <h2 class="text-2xl font-bold text-black mb-6">Product List</h2>
                <?php if (empty($products)) { ?>
                    <div class="text-center py-12">
                        <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Found</h3>
                        <p class="text-gray-500 text-base">Try adjusting your search criteria or add a new product.</p>
                    </div>
                <?php } else { ?>
                    <div class="space-y-4">
                        <?php foreach ($products as $product) { ?>
                            <div class="bg-white rounded-lg p-4 shadow-md flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" 
                                         class="w-16 h-16 object-cover rounded">
                                    <div>
                                        <h3 class="font-semibold text-lg text-black"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h3>
                                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?> | <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                                        <p class="text-gray-600 font-bold text-sm">$<?php echo number_format((float)($product['price'] ?? 0), 2); ?></p>
                                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (strlen($product['description'] ?? '') > 100 ? '...' : ''); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo ($product['is_active'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ($product['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="product_id" value="<?php echo (int)($product['product_id'] ?? 0); ?>">
                                        <button type="submit" class="relative bg-gray-200 rounded-lg px-3 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" 
                                                onclick="return confirm('Are you sure you want to <?php echo ($product['is_active'] ?? 0) ? 'deactivate' : 'activate'; ?> this product?')">
                                            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                            <i class="fas fa-<?php echo ($product['is_active'] ?? 0) ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                    </form>
                                    <button onclick='openEditModal(<?php echo json_encode($product, JSON_HEX_QUOT | JSON_HEX_APOS); ?>)' 
                                            class="relative bg-gray-200 rounded-lg px-3 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo (int)($product['product_id'] ?? 0); ?>, '<?php echo htmlspecialchars(str_replace('\'', '\\\'', $product['name'] ?? 'Product')); ?>')" 
                                            class="relative bg-red-200 rounded-lg px-3 py-2 text-red-800 font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                        <div class="absolute inset-0 bg-gradient-to-br from-red/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-100 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-auto shadow-2xl transform transition-all duration-300">
        <div class="flex justify-between items-center p-6 border-b border-gray-300">
            <h3 class="text-xl font-bold text-black">Add New Product</h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-black">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6" id="addProductForm" onsubmit="return validateAddForm()" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Product Name *</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Category *</label>
                    <select name="category_id" required 
                            class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo (int)($category['category_id'] ?? 0); ?>">
                                <?php echo htmlspecialchars($category['category_name'] ?? 'N/A'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Brand</label>
                    <input type="text" name="brand" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Price *</label>
                    <input type="number" name="price" step="0.01" min="0.01" required 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Dimensions</label>
                    <input type="text" name="dimensions" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Materials</label>
                    <input type="text" name="materials" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Stock Quantity</label>
                    <input type="number" name="stock_quantity" min="0" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Image (JPEG/PNG, max 5MB)</label>
                    <input type="file" name="image" accept="image/jpeg,image/png" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-black font-semibold mb-2">Description</label>
                <textarea name="description" rows="4" 
                          class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black"></textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="relative flex-1 bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-plus mr-2"></i>Add Product
                </button>
                <button type="button" onclick="closeAddModal()" class="relative flex-1 bg-gray-300 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-100 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-auto shadow-2xl transform transition-all duration-300">
        <div class="flex justify-between items-center p-6 border-b border-gray-300">
            <h3 class="text-xl font-bold text-black">Edit Product</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-black">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6" id="editProductForm" onsubmit="return validateEditForm()" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_product">
            <input type="hidden" name="product_id" id="edit_product_id">
            <input type="hidden" name="existing_image" id="edit_existing_image">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Product Name *</label>
                    <input type="text" name="name" id="edit_name" required 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Category *</label>
                    <select name="category_id" id="edit_category_id" required 
                            class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo (int)($category['category_id'] ?? 0); ?>">
                                <?php echo htmlspecialchars($category['category_name'] ?? 'N/A'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Brand</label>
                    <input type="text" name="brand" id="edit_brand" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Price *</label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0.01" required 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Dimensions</label>
                    <input type="text" name="dimensions" id="edit_dimensions" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Materials</label>
                    <input type="text" name="materials" id="edit_materials" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-black font-semibold mb-2">Stock Quantity</label>
                    <input type="number" name="stock_quantity" id="edit_stock_quantity" min="0" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-black font-semibold mb-2">Image (JPEG/PNG, max 5MB)</label>
                    <input type="file" name="image" accept="image/jpeg,image/png" 
                           class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                    <p class="text-gray-500 text-sm mt-1">Current: <span id="edit_image_name"></span></p>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-black font-semibold mb-2">Description</label>
                <textarea name="description" id="edit_description" rows="4" 
                          class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black"></textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="relative flex-1 bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-save mr-2"></i>Update Product
                </button>
                <button type="button" onclick="closeEditModal()" class="relative flex-1 bg-gray-300 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Product Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-gray-100 rounded-2xl max-w-md w-full shadow-2xl transform transition-all duration-300">
        <div class="flex justify-between items-center p-6 border-b border-gray-300">
            <h3 class="text-xl font-bold text-black">Delete Product</h3>
            <button onclick="closeDeleteModal()" class="text-gray-500 hover:text-black">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-6" id="deleteProductForm">
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="product_id" id="delete_product_id">
            <p class="text-gray-600 mb-6">Are you sure you want to delete the product "<span id="delete_product_name" class="font-semibold"></span>"? This action cannot be undone.</p>
            <div class="flex gap-4">
                <button type="submit" class="relative flex-1 bg-red-200 rounded-xl px-6 py-3 text-red-800 font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-red/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
                <button type="button" onclick="closeDeleteModal()" class="relative flex-1 bg-gray-300 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Locomotive Scroll JS -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.js"></script>
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

        // Modal animations
        const modals = ['addModal', 'editModal', 'deleteModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            modal.addEventListener('transitionend', () => {
                if (!modal.classList.contains('hidden')) {
                    modal.querySelector('div').classList.remove('scale-95');
                    if (scroll) scroll.update();
                }
            });
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
            }, 300);
        } catch (e) {
            console.error('Error closing Add Modal:', e);
        }
    }

    function openEditModal(product) {
        try {
            console.log('Opening Edit Modal with product:', product);
            const modal = document.getElementById('editModal');
            document.getElementById('edit_product_id').value = product.product_id || '';
            document.getElementById('edit_name').value = product.name || '';
            document.getElementById('edit_category_id').value = product.category_id || '';
            document.getElementById('edit_brand').value = product.brand || '';
            document.getElementById('edit_price').value = product.price || '';
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_existing_image').value = product.image || '';
            document.getElementById('edit_image_name').textContent = product.image || 'None';
            document.getElementById('edit_dimensions').value = product.dimensions || '';
            document.getElementById('edit_materials').value = product.materials || '';
            document.getElementById('edit_stock_quantity').value = product.stock_quantity || '0';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.opacity = '1';
            modal.querySelector('div').classList.remove('scale-95');
        } catch (e) {
            console.error('Error opening Edit Modal:', e);
        }
    }

    function closeEditModal() {
        try {
            console.log('Closing Edit Modal');
            const modal = document.getElementById('editModal');
            modal.style.opacity = '0';
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.opacity = '';
            }, 300);
        } catch (e) {
            console.error('Error closing Edit Modal:', e);
        }
    }

    function openDeleteModal(productId, productName) {
        try {
            console.log('Opening Delete Modal for product ID:', productId);
            const modal = document.getElementById('deleteModal');
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('delete_product_name').textContent = productName;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.opacity = '1';
            modal.querySelector('div').classList.remove('scale-95');
        } catch (e) {
            console.error('Error opening Delete Modal:', e);
        }
    }

    function closeDeleteModal() {
        try {
            console.log('Closing Delete Modal');
            const modal = document.getElementById('deleteModal');
            modal.style.opacity = '0';
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.style.opacity = '';
            }, 300);
        } catch (e) {
            console.error('Error closing Delete Modal:', e);
        }
    }

    function validateAddForm() {
        const form = document.getElementById('addProductForm');
        const name = form.querySelector('input[name="name"]').value.trim();
        const category = form.querySelector('select[name="category_id"]').value;
        const price = form.querySelector('input[name="price"]').value;
        const image = form.querySelector('input[name="image"]').files[0];

        if (!name) {
            alert('Product name is required.');
            return false;
        }
        if (!category) {
            alert('Category is required.');
            return false;
        }
        if (!price || price <= 0) {
            alert('Price must be a positive number.');
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

    function validateEditForm() {
        const form = document.getElementById('editProductForm');
        const product_id = form.querySelector('input[name="product_id"]').value;
        const name = form.querySelector('input[name="name"]').value.trim();
        const category = form.querySelector('select[name="category_id"]').value;
        const price = form.querySelector('input[name="price"]').value;
        const image = form.querySelector('input[name="image"]').files[0];

        if (!product_id) {
            alert('Product ID is required.');
            return false;
        }
        if (!name) {
            alert('Product name is required.');
            return false;
        }
        if (!category) {
            alert('Category is required.');
            return false;
        }
        if (!price || price <= 0) {
            alert('Price must be a positive number.');
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

    /* Ensure modals are above all content */
    #addModal, #editModal, #deleteModal {
        z-index: 1000;
    }

    /* Ensure footer is not overlapped */
    footer {
        position: relative;
        z-index: 10;
    }

    /* Override glass styles for consistency */
    .glass-card, .glass-button {
        background: transparent !important;
        border: none !important;
    }
</style>

<?php include '../includes/footer.php'; ?>