<?php
require_once '../config/config.php';

// Require designer login
requireLogin();
requireRole('designer');

$pageTitle = 'Designer Profile - DecorVista';
$user_id = $_SESSION['user_id'];

// ===== Get Designer Info =====
$designer_query = "SELECT ud.first_name, ud.last_name, ud.phone
                   FROM user_details ud
                   WHERE ud.user_id = ?";

$stmt = $db->prepare($designer_query);
if (!$stmt) {
    error_log("Prepare failed: " . $db->getConnection()->error);
    die("An error occurred. Please try again later.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$designer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ===== Handle Profile Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');

    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        $_SESSION['error'] = "First name and last name are required.";
    } else {
        $update_query = "UPDATE user_details SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
        $stmt = $db->prepare($update_query);
        if (!$stmt) {
            error_log("Prepare failed (update): " . $db->getConnection()->error);
            die("An error occurred. Please try again later.");
        }
        $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }

    header("Location: profile.php");
    exit();
}

include '../includes/header.php';
?>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200">
    <section class="py-16 px-4" data-scroll-section>
        <div class="container mx-auto">
            <div class="bg-white/90 backdrop-blur-md p-8 rounded-2xl shadow-xl max-w-lg mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 text-center mb-6">Your Designer Profile</h1>
                
                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 text-green-800 p-4 mb-6 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 text-red-800 p-4 mb-6 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Profile Form -->
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="first_name" class="block text-gray-600 text-sm font-medium mb-2">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($designer['first_name'] ?? ''); ?>" 
                                   class="w-full p-3 rounded-lg bg-gray-50 text-gray-800 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-600 transition-all duration-300" required>
                        </div>
                        <div>
                            <label for="last_name" class="block text-gray-600 text-sm font-medium mb-2">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($designer['last_name'] ?? ''); ?>" 
                                   class="w-full p-3 rounded-lg bg-gray-50 text-gray-800 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-600 transition-all duration-300" required>
                        </div>
                        <div>
                            <label for="phone" class="block text-gray-600 text-sm font-medium mb-2">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($designer['phone'] ?? ''); ?>" 
                                   class="w-full p-3 rounded-lg bg-gray-50 text-gray-800 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-600 transition-all duration-300">
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors duration-300 flex items-center justify-center w-full sm:w-auto mx-auto">
                            <i class="fas fa-save mr-2"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</main>

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
    input, textarea {
        font-family: inherit;
        transition: all 0.3s ease;
    }
    input:focus, textarea:focus {
        box-shadow: 0 0 0 3px rgba(55, 65, 81, 0.2);
    }
    button {
        font-family: inherit;
        font-weight: 500;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let scrollInstance = null;

        const initScroll = () => {
            scrollInstance = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                multiplier: 0.8,
                lerp: 0.08,
                reloadOnContextChange: true
            });

            const resizeObserver = new ResizeObserver(() => {
                scrollInstance.update();
            });
            resizeObserver.observe(document.querySelector('[data-scroll-container]'));

            const images = document.querySelectorAll('img');
            if (images.length) {
                Promise.all(
                    Array.from(images).map(img => {
                        if (img.complete) return Promise.resolve();
                        return new Promise(resolve => {
                            img.addEventListener('load', resolve);
                            img.addEventListener('error', resolve);
                        });
                    })
                ).then(() => {
                    scrollInstance.update();
                });
            }
        };

        window.addEventListener('load', initScroll);

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (scrollInstance) {
                scrollInstance.destroy();
            }
        });
    });
</script>

</body>
</html>