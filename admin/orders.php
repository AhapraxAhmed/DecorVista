<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Orders - Admin';

// Handle actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? '';

    if ($action === 'update_status' && $order_id) {
        $status = $_POST['status'] ?? 'pending';
        $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $db->prepare($update_query);
        if ($stmt) {
            $stmt->bind_param("si", $status, $order_id);
            if ($stmt->execute()) {
                header("Location: orders.php?msg=Order status updated successfully");
                exit;
            } else {
                $error = "Failed to update order status: " . $db->error;
            }
        } else {
            $error = "Failed to prepare update query: " . $db->error;
        }
    } elseif ($action === 'delete' && $order_id) {
        $delete_query = "DELETE FROM orders WHERE order_id = ?";
        $stmt = $db->prepare($delete_query);
        if ($stmt) {
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                header("Location: orders.php?msg=Order deleted successfully");
                exit;
            } else {
                $error = "Failed to delete order: " . $db->error;
            }
        } else {
            $error = "Failed to prepare delete query: " . $db->error;
        }
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch orders with user & product info
$query = "SELECT o.*, u.username, p.name as product_name
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.user_id
          LEFT JOIN products p ON o.product_id = p.product_id
          WHERE $where_clause
          ORDER BY o.order_date DESC";

$stmt = $db->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "SQL Error: " . $db->error;
}

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
                        <h1 class="text-4xl font-extrabold text-black mb-2">Manage Orders</h1>
                        <p class="text-gray-600 text-lg">View and update customer orders with ease</p>
                    </div>
                    <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-lg transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="md:w-48">
                        <select name="status" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="bg-gray-100 rounded-2xl shadow-lg overflow-x-auto" data-scroll data-scroll-speed="2">
                <table class="min-w-full text-black">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Order ID</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">User</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Product</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Quantity</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Total</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Status</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Created</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Orders Found</h3>
                                    <p class="text-gray-500 text-base">No orders match your current filter criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr class="border-b border-gray-300 tilt-card transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                    <td class="px-6 py-4 text-base"><?php echo htmlspecialchars($order['order_id'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-base"><?php echo htmlspecialchars($order['username'] ?? 'Unknown User'); ?></td>
                                    <td class="px-6 py-4 text-base"><?php echo htmlspecialchars($order['product_name'] ?? 'Unknown Product'); ?></td>
                                    <td class="px-6 py-4 text-base"><?php echo htmlspecialchars($order['quantity'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-base">$<?php echo number_format((float)($order['total_price'] ?? 0), 2); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-sm font-medium rounded-full 
                                            <?php
                                                switch ($order['status'] ?? '') {
                                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'completed': echo 'bg-green-100 text-green-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                            <?php echo ucfirst($order['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-base"><?php echo isset($order['order_date']) ? date('M j, Y g:i A', strtotime($order['order_date'])) : 'N/A'; ?></td>
                                    <td class="px-6 py-4 flex gap-3">
                                        <!-- Update Status -->
                                        <form method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id'] ?? ''; ?>">
                                            <select name="status" class="px-3 py-2 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-sm focus:outline-none focus:border-black">
                                                <option value="pending" <?php echo ($order['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo ($order['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo ($order['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo ($order['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                Update
                                            </button>
                                        </form>
                                        <!-- Delete -->
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this order?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id'] ?? ''; ?>">
                                            <button type="submit" class="relative bg-gray-300 rounded-xl px-4 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Locomotive Scroll JS -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4/dist/locomotive-scroll.min.js"></script>
<!-- Vanilla Tilt for 3D tilt effect -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.2/vanilla-tilt.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Locomotive Scroll
        const scroll = new LocomotiveScroll({
            el: document.querySelector('[data-scroll-container]'),
            smooth: true,
            lerp: 0.08,
            smartphone: { smooth: true },
            tablet: { smooth: true }
        });

        // Update scroll after load to ensure content visibility
        setTimeout(() => {
            scroll.update();
            // Force another update after a longer delay for dynamic content
            setTimeout(() => scroll.update(), 500);
        }, 200);

        // Initialize Vanilla Tilt for table rows
        VanillaTilt.init(document.querySelectorAll('.tilt-card'), {
            max: 10,
            speed: 400,
            glare: true,
            'max-glare': 0.2
        });
    });
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

    /* Ensure footer is not overlapped */
    footer {
        position: relative;
        z-index: 10;
    }
</style>

<?php include '../includes/footer.php'; ?>