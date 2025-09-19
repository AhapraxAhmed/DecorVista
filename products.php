<?php
require_once 'config/config.php';

$pageTitle = 'Products - DecorVista';

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));

// Build WHERE clause for main products
$where_conditions = ["p.is_active = 1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $param_types .= "i";
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $param_types .= "d";
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $param_types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

// Build ORDER BY clause
$order_by = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    'featured' => 'p.created_at DESC',
    default => 'p.created_at DESC'
};

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$stmt = $db->prepare($count_query);
if (!$stmt) {
    die("Query preparation failed: " . $db->error);
}
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calculate pagination
$products_per_page = 12;
$total_pages = ceil($total_products / $products_per_page);
$offset = ($page - 1) * $products_per_page;

// Get products
$select = "SELECT p.*, c.name as category_name";
$joins = "FROM products p LEFT JOIN categories c ON p.category_id = c.category_id";
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $select .= ", CASE WHEN f.product_id IS NOT NULL THEN 1 ELSE 0 END as is_favorited";
    $joins .= " LEFT JOIN favorites f ON p.product_id = f.product_id AND f.user_id = ?";
}

$products_query = "$select $joins WHERE $where_clause ORDER BY $order_by LIMIT ? OFFSET ?";
$stmt = $db->prepare($products_query);
if (!$stmt) {
    die("Query preparation failed: " . $db->error);
}
$all_params = array_merge($params, isLoggedIn() ? [$user_id] : [], [$products_per_page, $offset]);
$all_param_types = $param_types . (isLoggedIn() ? "i" : "") . "ii";
$stmt->bind_param($all_param_types, ...$all_params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get favorited products for logged-in user
$favorites = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $favorites_query = "SELECT p.*, c.name as category_name
                       FROM products p
                       INNER JOIN favorites f ON p.product_id = f.product_id AND f.user_id = ?
                       LEFT JOIN categories c ON p.category_id = c.category_id
                       WHERE p.is_active = 1
                       ORDER BY f.created_at DESC";
    $stmt = $db->prepare($favorites_query);
    if (!$stmt) {
        die("Favorites query preparation failed: " . $db->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get categories for filter
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categories_result = $db->query($categories_query);
$categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

include 'includes/header.php';
?>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pb-20">
    <!-- Page Header -->
    <section class="py-20 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-2xl p-8 mb-8 scroll-fade-in">
                <div class="text-center">
                    <h1 class="font-heading text-4xl font-bold text-black mb-4">Product Catalog</h1>
                    <p class="text-gray-600 text-lg">Discover beautiful furniture and decor for your home</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Favorites Section -->
    <?php if (isLoggedIn() && !empty($favorites)): ?>
        <section class="py-8 px-4" data-scroll-section>
            <div class="max-w-7xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-bold text-black mb-6 font-heading">Your Favorites</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($favorites as $index => $product): ?>
                        <div class="relative">
                            <button onclick="toggleFavorite(<?php echo $product['product_id']; ?>)" 
                                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/50 flex items-center justify-center text-white hover:bg-black/70 transition-colors z-10">
                                <i class="fas fa-heart text-red-500"></i>
                            </button>
                            <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl overflow-hidden card-hover-effect scroll-fade-in" style="animation-delay: <?php echo ($index % 8) * 0.1; ?>s">
                                <!-- Product Image -->
                                <div class="aspect-square bg-gray-200 relative overflow-hidden group">
                                    <?php if ($product['image']): ?>
                                        <img src="./uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                            <i class="fas fa-image text-4xl text-gray-600"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-700 font-medium uppercase tracking-wide">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-black mb-2 line-clamp-2">
                                        <a href="/  detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="hover:text-gray-700 transition-colors">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($product['brand']): ?>
                                        <p class="text-sm text-gray-600 mb-2">
                                            by <?php echo htmlspecialchars($product['brand']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (strlen($product['description'] ?? '') > 100 ? '...' : ''); ?>
                                    </p>
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="text-xl font-bold text-gray-800">
                                            $<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                        <a href="./product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>View More
                                        </a>
                                        <?php if (isLoggedIn()): ?>
                                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                    class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors">
                                                <i class="fas fa-cart-plus mr-1"></i>Add
                                            </button>
                                        <?php else: ?>
                                            <a href="/login.php" class="bg-gray-200 text-gray-600 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors">
                                                <i class="fas fa-sign-in-alt mr-1"></i>Login
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Search and Filters -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl p-6 mb-8 scroll-fade-in">
                <form method="GET" action="" class="space-y-4 lg:space-y-0 lg:flex lg:items-end lg:space-x-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-search mr-2 text-gray-700"></i>Search Products
                        </label>
                        <input type="text" id="search" name="search" 
                               class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                               placeholder="Search by name, brand, or description..."
                               value="<?php echo htmlspecialchars($search); ?>">
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
                                <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="lg:w-32">
                        <label for="min_price" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-dollar-sign mr-2 text-gray-700"></i>Min Price
                        </label>
                        <input type="number" id="min_price" name="min_price" min="0" step="0.01"
                               class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                               placeholder="0"
                               value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    </div>
                    
                    <div class="lg:w-32">
                        <label for="max_price" class="block text-sm font-medium text-black mb-2">Max Price</label>
                        <input type="number" id="max_price" name="max_price" min="0" step="0.01"
                               class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                               placeholder="Any"
                               value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                    </div>
                    
                    <!-- Sort -->
                    <div class="lg:w-40">
                        <label for="sort" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-sort mr-2 text-gray-700"></i>Sort By
                        </label>
                        <select id="sort" name="sort" 
                                class="flex h-10 w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Featured</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-md font-medium hover:bg-gray-900 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
                
                <!-- Active Filters -->
                <?php if (!empty($search) || $category_id > 0 || $min_price > 0 || $max_price > 0): ?>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-600">Active filters:</span>
                            
                            <?php if (!empty($search)): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Search: "<?php echo htmlspecialchars($search); ?>"
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($category_id > 0): ?>
                                <?php
                                $cat_name = '';
                                foreach ($categories as $cat) {
                                    if ($cat['category_id'] == $category_id) {
                                        $cat_name = $cat['name'];
                                        break;
                                    }
                                }
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Category: <?php echo htmlspecialchars($cat_name); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($min_price > 0): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Min Price: $<?php echo number_format($min_price, 2); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['min_price' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($max_price > 0): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-200 text-gray-700 border border-gray-300">
                                    Max Price: $<?php echo number_format($max_price, 2); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['max_price' => ''])); ?>" class="ml-2 text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <a href="/products.php" class="text-sm text-gray-700 hover:text-gray-900 transition-colors">
                                <i class="fas fa-times-circle mr-1"></i>Clear all filters
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Results Summary -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6 scroll-fade-in">
                <div class="text-gray-600">
                    Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                    <?php if ($page > 1): ?>
                        (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                    <?php endif; ?>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <div class="text-sm text-gray-600">
                        <a href="/cart.php" class="text-gray-700 hover:text-gray-900 transition-colors">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            View Cart (<span id="cart-count-text">0</span>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Products Grid -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <?php if (empty($products)): ?>
                <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl p-12 text-center scroll-fade-in">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-3xl text-gray-600"></i>
                    </div>
                    <h3 class="font-heading text-xl font-semibold text-black mb-2">No products found</h3>
                    <p class="text-gray-600 mb-6">Try adjusting your search criteria or browse all products.</p>
                    <a href="/products.php" class="bg-gray-800 text-white px-6 py-3 rounded-md font-medium hover:bg-gray-900 transition-colors">
                        Browse All Products
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($products as $index => $product): ?>
                        <div class="relative">
                            <?php if (isLoggedIn()): ?>
                                <button onclick="toggleFavorite(<?php echo $product['product_id']; ?>)" 
                                        class="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/50 flex items-center justify-center text-white hover:bg-black/70 transition-colors z-10">
                                    <i class="fas fa-heart <?php echo ($product['is_favorited'] ?? 0) ? 'text-red-500' : 'text-gray-600'; ?>"></i>
                                </button>
                            <?php endif; ?>
                            <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl overflow-hidden card-hover-effect scroll-fade-in" style="animation-delay: <?php echo ($index % 8) * 0.1; ?>s">
                                <!-- Product Image -->
                                <div class="aspect-square bg-gray-200 relative overflow-hidden group">
                                    <?php if ($product['image']): ?>
                                        <img src="./uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                            <i class="fas fa-image text-4xl text-gray-600"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-700 font-medium uppercase tracking-wide">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-black mb-2 line-clamp-2">
                                        <a href="/product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="hover:text-gray-700 transition-colors">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if ($product['brand']): ?>
                                        <p class="text-sm text-gray-600 mb-2">
                                            by <?php echo htmlspecialchars($product['brand']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (strlen($product['description'] ?? '') > 100 ? '...' : ''); ?>
                                    </p>
                                    
                                    <div class="flex justify-between items-center">
                                        <div class="text-xl font-bold text-gray-800">
                                            $<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                        <a href="./product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>View More
                                        </a>
                                        <?php if (isLoggedIn()): ?>
                                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                    class="bg-gray-800 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-900 transition-colors">
                                                <i class="fas fa-cart-plus mr-1"></i>Add
                                            </button>
                                        <?php else: ?>
                                            <a href="/login.php" class="bg-gray-200 text-gray-600 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors">
                                                <i class="fas fa-sign-in-alt mr-1"></i>Login
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white/80 backdrop-blur-lg border border-gray-200 rounded-xl p-6 scroll-fade-in">
                        <nav class="flex justify-center">
                            <div class="flex space-x-2">
                                <!-- Previous Page -->
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="px-4 py-2 border border-gray-200 rounded-lg text-black hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-chevron-left mr-1"></i>Previous
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Page Numbers -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="px-4 py-2 border rounded-lg transition-colors <?php 
                                           echo $i === $page ? 
                                               'bg-gray-800 text-white border-gray-800' : 
                                               'border-gray-200 text-black hover:bg-gray-200'; 
                                       ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <!-- Next Page -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="px-4 py-2 border border-gray-200 rounded-lg text-black hover:bg-gray-200 transition-colors">
                                        Next<i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </nav>
                    </div>
                <?php endif; ?>
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
</style>

<!-- Locomotive Scroll -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<!-- Toastify for notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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

function showNotification(message, type = 'info') {
    Toastify({
        text: message,
        duration: 3000,
        gravity: 'top',
        position: 'right',
        backgroundColor: type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6',
        stopOnFocus: true
    }).showToast();

    if (window.locomotiveScroll) {
        setTimeout(() => window.locomotiveScroll.update(), 100);
    }
}

function addToCart(productId) {
    <?php if (!isLoggedIn()): ?>
        window.location.href = '/login.php';
        return;
    <?php endif; ?>
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('./api/add-to-cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
    });
}

function toggleFavorite(productId) {
    <?php if (!isLoggedIn()): ?>
        window.location.href = '/login.php';
        return;
    <?php endif; ?>
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    
    fetch('./api/toggle-favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const button = event.target.closest('button');
            const icon = button.querySelector('i');
            if (data.added) {
                icon.classList.remove('text-gray-600');
                icon.classList.add('text-red-500');
                showNotification('Added to favorites!', 'success');
            } else {
                icon.classList.remove('text-red-500');
                icon.classList.add('text-gray-600');
                showNotification('Removed from favorites', 'info');
            }
            // Refresh page to update favorites section
            setTimeout(() => {
                window.location.reload();
            }, 1000); // Delay to allow notification to be seen
        } else {
            showNotification(data.message || 'Error updating favorites', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Added to favorites!', 'success');
        setTimeout(() => {
                    window.location.reload();
        }, 300); // Delay to allow notification to be seen
    });
}

function updateCartCount() {
    const cartCountElement = document.getElementById('cart-count-text');
    if (cartCountElement) {
        fetch('./api/cart-count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cartCountElement.textContent = data.count;
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
    }
}

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<script src="/assets/js/scroll-animations.js"></script>