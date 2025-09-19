<?php
require_once '../config/config.php';

requireLogin();
requireRole('admin');

$pageTitle = 'Manage Consultations - Admin';

/**
 * Normalize to a mysqli connection no matter how config.php exposes it.
 */
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
    die('Database connection is not a mysqli instance. Check config.php to expose a mysqli connection (e.g., $db or $db->conn).');
}

/* -------------------------
   Handle status updates
-------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $consultation_id = isset($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : 0;

    $allowed = ['approved', 'cancelled', 'pending', 'completed'];
    if ($consultation_id > 0 && in_array($action, $allowed, true)) {
        $sql = "UPDATE consultations SET status = ? WHERE consultation_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "SQL prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("si", $action, $consultation_id);
            if ($stmt->execute()) {
                header('Location: consultations.php?msg=Consultation status updated');
                exit;
            } else {
                $error = "SQL execute failed: " . $stmt->error;
            }
        }
    } else {
        $error = "Invalid action or consultation ID.";
    }
}

/* -------------------------
   Filters (search / status)
-------------------------- */
$search = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';

$where = "1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR d.first_name LIKE ? OR d.last_name LIKE ? OR d.specialization LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like, $like];
    $types  = "sssss";
}

if ($status !== '') {
    $where .= " AND c.status = ?";
    $params[] = $status;
    $types    .= "s";
}

/* -------------------------
   Fetch consultations
-------------------------- */
$sql = "
    SELECT
        c.consultation_id,
        c.scheduled_date,
        c.scheduled_time,
        c.status,
        c.notes,
        u.username AS homeowner,
        u.email AS homeowner_email,
        d.first_name, d.last_name, d.specialization
    FROM consultations c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN interior_designers d ON c.designer_id = d.designer_id
    WHERE $where
    ORDER BY c.scheduled_date DESC, c.scheduled_time DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $error = "SQL prepare failed: " . $conn->error;
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $consultations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        $error = "SQL execute failed: " . $stmt->error;
    }
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
                        <h1 class="text-4xl font-extrabold text-black mb-2">Manage Consultations</h1>
                        <p class="text-gray-600 text-lg">View and manage designer appointments</p>
                    </div>
                    <a href="dashboard.php" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-100 rounded-2xl p-8 mb-8 shadow-lg" data-scroll data-scroll-speed="1.5">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="q"
                            value="<?php echo htmlspecialchars($search ?? ''); ?>"
                            placeholder="Search by homeowner, email, designer, or specialization..."
                            class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black placeholder-gray-500 font-medium text-base focus:outline-none focus:border-black"
                        >
                    </div>
                    <div class="md:w-48">
                        <select name="status" class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-lg text-black font-medium text-base focus:outline-none focus:border-black">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($status ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="cancelled" <?php echo ($status ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo ($status ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="relative bg-gray-200 rounded-xl px-6 py-3 text-black font-semibold text-base transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-gray-100 rounded-2xl shadow-lg overflow-x-auto" data-scroll data-scroll-speed="2">
                <table class="min-w-full text-black">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">ID</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Homeowner</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Designer</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Specialization</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Date & Time</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Status</th>
                            <th class="px-6 py-4 text-left font-semibold text-black text-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultations)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Consultations Found</h3>
                                    <p class="text-gray-500 text-base">Try adjusting your search or status filter.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultations as $c): ?>
                                <tr class="border-b border-gray-300 tilt-card transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group">
                                    <td class="px-6 py-4 text-base"><?php echo (int)($c['consultation_id'] ?? 0); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-base"><?php echo htmlspecialchars($c['homeowner'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($c['homeowner_email'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-base">
                                        <?php echo htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: 'Unknown Designer'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-base"><?php echo htmlspecialchars($c['specialization'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-base">
                                        <?php
                                            $date = isset($c['scheduled_date']) ? date('M j, Y', strtotime($c['scheduled_date'])) : 'N/A';
                                            $time = isset($c['scheduled_time']) ? date('g:i A', strtotime($c['scheduled_time'])) : 'N/A';
                                            echo $date . ' at ' . $time;
                                        ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-4 py-2 text-sm font-medium rounded-full 
                                            <?php
                                                switch ($c['status'] ?? '') {
                                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'approved': echo 'bg-green-100 text-green-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    case 'completed': echo 'bg-blue-100 text-blue-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                            <?php echo ucfirst($c['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 flex gap-3">
                                        <!-- Approve -->
                                        <form method="POST">
                                            <input type="hidden" name="action" value="approved">
                                            <input type="hidden" name="consultation_id" value="<?php echo (int)($c['consultation_id'] ?? 0); ?>">
                                            <button type="submit" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" title="Approve">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                <i class="fas fa-check mr-2"></i>Approve
                                            </button>
                                        </form>
                                        <!-- Mark Completed -->
                                        <form method="POST">
                                            <input type="hidden" name="action" value="completed">
                                            <input type="hidden" name="consultation_id" value="<?php echo (int)($c['consultation_id'] ?? 0); ?>">
                                            <button type="submit" class="relative bg-gray-200 rounded-xl px-4 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" title="Mark as completed">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                <i class="fas fa-flag-checkered mr-2"></i>Complete
                                            </button>
                                        </form>
                                        <!-- Cancel -->
                                        <form method="POST" onsubmit="return confirm('Cancel this consultation?');">
                                            <input type="hidden" name="action" value="cancelled">
                                            <input type="hidden" name="consultation_id" value="<?php echo (int)($c['consultation_id'] ?? 0); ?>">
                                            <button type="submit" class="relative bg-gray-300 rounded-xl px-4 py-2 text-black font-semibold text-sm transform transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/20 group" title="Cancel">
                                                <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                                <i class="fas fa-times mr-2"></i>Cancel
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

    /* Override any conflicting glass styles */
    .glass-card, .glass-button {
        background: transparent !important;
        border: none !important;
    }
</style>

<?php include '../includes/footer.php'; ?>