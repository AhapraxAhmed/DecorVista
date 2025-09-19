<?php
require_once 'config/config.php';

$pageTitle = 'Interior Designers - DecorVista';

// Get raw mysqli connection from your $db object
function get_mysqli_conn($db) {
    if ($db instanceof mysqli) return $db;
    if (isset($db->conn) && $db->conn instanceof mysqli) return $db->conn;
    if (method_exists($db, 'getConnection')) {
        $c = $db->getConnection();
        if ($c instanceof mysqli) return $c;
    }
    return null;
}

$conn = get_mysqli_conn($db);
if (!$conn) {
    die('Database connection is not a mysqli instance. Check config.php.');
}

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$specialization = sanitizeInput($_GET['specialization'] ?? '');
$min_rating = (float)($_GET['min_rating'] ?? 0);
$sort = sanitizeInput($_GET['sort'] ?? 'rating');
$page = (int)($_GET['page'] ?? 1);

// Build WHERE clause
$where_conditions = ["u.is_active = 1", "u.role = 'designer'"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_conditions[] = "(ud.firstname LIKE ? OR ud.lastname LIKE ? OR id.specialization LIKE ? OR id.bio LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $param_types .= "ssss";
}

if (!empty($specialization)) {
    $where_conditions[] = "id.specialization LIKE ?";
    $params[] = "%$specialization%";
    $param_types .= "s";
}

if ($min_rating > 0) {
    $where_conditions[] = "id.rating >= ?";
    $params[] = $min_rating;
    $param_types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

$order_by = match($sort) {
    'name' => 'ud.firstname ASC, ud.lastname ASC',
    'experience' => 'id.years_experience DESC',
    'price_low' => 'id.hourly_rate ASC',
    'price_high' => 'id.hourly_rate DESC',
    default => 'id.rating DESC, id.total_reviews DESC'
};

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM users u 
                JOIN user_details ud ON u.user_id = ud.user_id 
                JOIN interior_designers id ON u.user_id = id.user_id 
                WHERE $where_clause";

$stmt = $conn->prepare($count_query);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error . "\nQuery: $count_query");
}

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_designers = $stmt->get_result()->fetch_assoc()['total'];

// Calculate pagination
$designers_per_page = 12;
$total_pages = ceil($total_designers / $designers_per_page);
$offset = ($page - 1) * $designers_per_page;

// Get designers
$designers_query = "SELECT u.user_id, u.username, u.email,
                           ud.first_name, ud.last_name, ud.phone, ud.profile_image,
                           id.designer_id, id.years_experience, id.specialization, id.portfolio_url,
                           id.hourly_rate, id.bio, id.availability_status, id.rating, id.total_reviews
                    FROM users u 
                    JOIN interior_designers id ON u.user_id = id.user_id
                    LEFT JOIN user_details ud ON u.user_id = ud.user_id
                    WHERE $where_clause 
                    ORDER BY $order_by 
                    LIMIT ? OFFSET ?";

$stmt = $conn->prepare($designers_query);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error . "\nQuery: $designers_query");
}

$all_params = array_merge($params, [$designers_per_page, $offset]);
$all_param_types = $param_types . "ii";
$stmt->bind_param($all_param_types, ...$all_params);
$stmt->execute();
$designers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique specializations for filter
$specializations_query = "SELECT DISTINCT specialization 
                          FROM interior_designers 
                          WHERE specialization IS NOT NULL AND specialization != '' 
                          ORDER BY specialization";

$specializations = $conn->query($specializations_query)->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div data-scroll-container>
    <main data-scroll-section>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl p-8 mb-8 shadow-sm">
            <div class="text-center">
                <h1 class="font-heading text-4xl font-bold text-gray-900 mb-4">Interior Designers</h1>
                <p class="text-gray-600 text-lg">Connect with professional designers to transform your space</p>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 mb-8 shadow-sm">
            <form method="GET" action="" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-2"></i>Search Designers
                    </label>
                    <input type="text" id="search" name="search" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-300"
                           placeholder="Search by name, specialization, or bio..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="md:w-48">
                    <label for="specialization" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-palette mr-2"></i>Specialization
                    </label>
                    <select id="specialization" name="specialization" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec['specialization']); ?>" 
                                    <?php echo $specialization === $spec['specialization'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:w-32">
                    <label for="min_rating" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-star mr-2"></i>Min Rating
                    </label>
                    <select id="min_rating" name="min_rating" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        <option value="">Any Rating</option>
                        <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="3" <?php echo $min_rating == 3 ? 'selected' : ''; ?>>3+ Stars</option>
                        <option value="2" <?php echo $min_rating == 2 ? 'selected' : ''; ?>>2+ Stars</option>
                    </select>
                </div>
                
                <div class="md:w-40">
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sort mr-2"></i>Sort By
                    </label>
                    <select id="sort" name="sort" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="experience" <?php echo $sort === 'experience' ? 'selected' : ''; ?>>Most Experience</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    </select>
                </div>
                
                <div>
                   <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-all duration-300">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                </div>
            </form>
        </div>
        
        <div class="flex justify-between items-center mb-6">
            <div class="text-gray-600">
                Showing <?php echo count($designers); ?> of <?php echo $total_designers; ?> designers
            </div>
        </div>
        
        <?php if (empty($designers)): ?>
            <div class="bg-white rounded-xl p-12 text-center shadow-sm">
                <i class="fas fa-user-tie text-6xl text-gray-300 mb-4"></i>
                <h3 class="font-heading text-xl font-semibold text-gray-900 mb-2">No designers found</h3>
                <p class="text-gray-600 mb-6">Try adjusting your search criteria.</p>
                <a href="/designers.php" class="btn-secondary">Browse All Designers</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
                <?php foreach ($designers as $designer): ?>
                    <div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-300 group">
                        <div class="aspect-square bg-gray-100 relative overflow-hidden">
                            <?php if ($designer['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($designer['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($designer['firstname'] . ' ' . $designer['lastname']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                    <i class="fas fa-user text-6xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="absolute top-3 left-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php 
                                    echo $designer['availability_status'] === 'available' ? 'bg-green-100 text-green-800' :
                                         ($designer['availability_status'] === 'busy' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                ?>">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <?php echo ucfirst($designer['availability_status']); ?>
                                </span>
                            </div>
                            
                            <?php if ($designer['total_reviews'] > 0): ?>
                                <div class="absolute top-3 right-3 bg-white bg-opacity-90 px-2 py-1 rounded-full shadow">
                                    <div class="flex items-center space-x-1">
                                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                                        <span class="text-sm font-medium"><?php echo number_format($designer['rating'], 1); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="font-heading text-xl font-semibold text-gray-900 mb-1">
                                    <a href="/designer-profile.php?id=<?php echo $designer['designer_id']; ?>" 
                                       class="hover:text-gray-600 transition-colors">
<?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($designer['specialization']): ?>
                                    <p class="text-sm text-gray-600 font-medium uppercase tracking-wide">
                                        <?php echo htmlspecialchars($designer['specialization']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-briefcase mr-1"></i>
<?php echo $designer['years_experience']; ?> years experience
                                </div>
                                
                                <?php if ($designer['total_reviews'] > 0): ?>
                                    <div class="text-sm text-gray-600">
                                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                                        <?php echo $designer['total_reviews']; ?> review<?php echo $designer['total_reviews'] !== 1 ? 's' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($designer['bio']): ?>
                                <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars(substr($designer['bio'], 0, 150)) . (strlen($designer['bio']) > 150 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <?php if ($designer['hourly_rate']): ?>
                                        <div class="text-lg font-bold text-gray-900">
                                            <?php echo formatPrice($designer['hourly_rate']); ?>/hour
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="designer-profile.php?id=<?php echo $designer['designer_id']; ?>" 
                                       class="btn-secondary text-sm">
                                        View Profile
                                    </a>
                                    
                                    <?php if (isLoggedIn() && $designer['availability_status'] === 'available'): ?>
                                        <a href="./book-consultation.php?designer_id=<?php echo $designer['designer_id']; ?>" 
                                           class="btn-primary text-sm">
                                            Book Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <nav class="flex justify-center">
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-chevron-left mr-1"></i>Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="px-4 py-2 border rounded-lg transition-colors <?php 
                                       echo $i === $page ? 
                                           'bg-gray-900 text-white border-gray-900' : 
                                           'border-gray-300 text-gray-700 hover:bg-gray-100'; 
                                   ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                    Next<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl p-8 mt-12 text-center shadow-sm">
            <h2 class="font-heading text-2xl font-semibold text-gray-900 mb-4">Are you an interior designer?</h2>
            <p class="text-gray-600 mb-6">Join our platform and connect with clients looking for professional design services.</p>
            <a href="register.php" class="btn-primary">
                <i class="fas fa-user-plus mr-2"></i>Join as Designer
            </a>
        </div>
    </div>
</main>

<?php

include 'includes/footer.php';
?>

</div>
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    const scroll = new LocomotiveScroll({
        el: document.querySelector('[data-scroll-container]'),
        smooth: true,
        multiplier: 1.2, // optional, adjust speed
        class: 'is-revealed'
    });
</script>