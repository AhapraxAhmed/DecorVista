<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Users - Admin';

// Handle user actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action === 'toggle_status' && $user_id) {
        $toggle_query = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
        $stmt = $db->prepare($toggle_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        header('Location: users.php?msg=User status updated');
        exit;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR ud.first_name LIKE ? OR ud.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Correct SQL query
$users_query = "SELECT u.user_id, u.username, u.email, u.is_active, u.created_at,
                       ud.first_name, ud.last_name, u.role, ud.phone
                FROM users u
                LEFT JOIN user_details ud ON u.user_id = ud.user_id
                WHERE $where_clause
                ORDER BY u.created_at DESC";

$stmt = $db->prepare($users_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

// Fetch users safely
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];

include '../includes/header.php';
?>

<!-- Tailwind CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<!-- Locomotive Scroll CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.css">
<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="min-h-screen bg-white text-black" data-scroll-container>
    <div data-scroll-section>
        <div class="container mx-auto px-4 py-12">

            <!-- Header -->
            <div class="bg-gray-100 rounded-2xl p-6 mb-8 transform transition-all duration-500 hover:shadow-2xl hover:shadow-black/20" data-scroll data-scroll-speed="1">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-black mb-2">Manage Users</h1>
                        <p class="text-gray-600">View and manage all platform users</p>
                    </div>
                    <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-medium transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-6 mb-8" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search users..." 
                               class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 focus:outline-none focus:border-black/50">
                    </div>
                    <div class="md:w-48">
                        <select name="role" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black focus:outline-none focus:border-black/50">
                            <option value="">All Roles</option>
                            <option value="homeowner" <?= $role_filter === 'homeowner' ? 'selected' : '' ?>>Homeowner</option>
                            <option value="designer" <?= $role_filter === 'designer' ? 'selected' : '' ?>>Designer</option>
                            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-medium transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-gray-100 rounded-2xl overflow-hidden margin" data-scroll data-scroll-speed="2">
                <div class="overflow-x-auto">
                    <?php if (!empty($users)): ?>
                        <table class="w-full">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-black font-semibold">User</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Email</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Phone</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Role</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Status</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Joined</th>
                                    <th class="px-6 py-4 text-left text-black font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-300/50">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-semibold text-black"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                                <div class="text-gray-600 text-sm">@<?= htmlspecialchars($user['username']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full <?= $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : ($user['role'] === 'designer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                        <td class="px-6 py-4">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <button type="submit" class="text-black hover:text-gray-800 mr-3" onclick="return confirm('Are you sure you want to <?= $user['is_active'] ? 'deactivate' : 'activate' ?> this user?')">
                                                    <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-12" data-scroll data-scroll-speed="3">
                            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl text-gray-600 mb-2">No users found</h3>
                            <p class="text-gray-500">Try adjusting your search criteria</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const scroll = new LocomotiveScroll({
            el: document.querySelector("[data-scroll-container]"),
            smooth: true,
            lerp: 0.07, 
            multiplier: 1, 
        });
    });
</script>
<style>
    .margin{
        margin-bottom:100px;
    }
</style>