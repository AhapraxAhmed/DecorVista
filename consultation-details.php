<?php
require_once 'config/config.php';

// Require login
requireLogin();

$consultation_id = (int)($_GET['id'] ?? 0);
$error = '';

// Handle cancellation before any output
if ($_POST && isset($_POST['cancel_consultation'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $cancel_query = "UPDATE consultations SET status = 'cancelled' WHERE consultation_id = ? AND user_id = ?";
        $stmt = $db->prepare($cancel_query);
        $stmt->bind_param("ii", $consultation_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            header('Location: ./dashboard.php?message=Consultation+cancelled+successfully');
            exit();
        } else {
            $error = 'Failed to cancel consultation. Please try again.';
        }
    }
}

if ($consultation_id <= 0) {
    header('Location: ./dashboard.php');
    exit();
}

// Fetch consultation details
$consultation_query = "SELECT c.consultation_id, c.user_id, c.designer_id, c.scheduled_date, c.scheduled_time, 
                             c.duration_hours, c.status, c.notes, c.client_requirements, c.total_cost,
                             ud.first_name AS designer_first_name, ud.last_name AS designer_last_name
                      FROM consultations c
                      JOIN interior_designers id ON c.designer_id = id.designer_id
                      JOIN user_details ud ON id.user_id = ud.user_id
                      WHERE c.consultation_id = ? AND c.user_id = ?";
$stmt = $db->prepare($consultation_query);
if ($stmt === false) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("ii", $consultation_id, $_SESSION['user_id']);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if (!$consultation) {
    header('Location: ./dashboard.php?error=Consultation+not+found');
    exit();
}

$pageTitle = 'Consultation Details - DecorVista';

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="glass rounded-2xl p-8 mb-8">
        <div class="text-center">
            <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Consultation Details</h1>
            <p class="text-gray-600">Details for your consultation with <?php echo htmlspecialchars($consultation['designer_first_name'] . ' ' . $consultation['designer_last_name']); ?></p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 glass">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="glass rounded-xl p-8">
        <h2 class="font-heading text-xl font-semibold text-gray-900 mb-6">Consultation Information</h2>

        <div class="space-y-6">
            <!-- Designer Info -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Designer</h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($consultation['designer_first_name'] . ' ' . $consultation['designer_last_name']); ?></p>
            </div>

            <!-- Scheduled Date and Time -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Scheduled Date & Time</h3>
                <p class="text-gray-600">
                    <?php echo date('F j, Y', strtotime($consultation['scheduled_date'])) . ' at ' . date('h:i A', strtotime($consultation['scheduled_time'])); ?>
                </p>
            </div>

            <!-- Duration -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Duration</h3>
                <p class="text-gray-600"><?php echo $consultation['duration_hours'] == 1 ? '1 hour' : $consultation['duration_hours'] . ' hours'; ?></p>
            </div>

            <!-- Consultation Type -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Consultation Type</h3>
                <p class="text-gray-600"><?php echo htmlspecialchars(ucfirst($consultation['client_requirements'])); ?></p>
            </div>

            <!-- Total Cost -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Total Cost</h3>
                <p class="text-gray-600"><?php echo formatPrice($consultation['total_cost']); ?></p>
            </div>

            <!-- Status -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Status</h3>
                <p class="text-gray-600 capitalize"><?php echo htmlspecialchars($consultation['status']); ?></p>
            </div>

            <!-- Notes -->
            <?php if (!empty($consultation['notes'])): ?>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Project Notes</h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($consultation['notes']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <?php if ($consultation['status'] == 'pending' || $consultation['status'] == 'confirmed'): ?>
                <?php
                $scheduled_datetime = strtotime($consultation['scheduled_date'] . ' ' . $consultation['scheduled_time']);
                $current_time = time();
                $hours_until_consultation = ($scheduled_datetime - $current_time) / 3600;
                ?>
                <?php if ($hours_until_consultation > 24): ?>
                    <div class="pt-4 flex flex-col sm:flex-row gap-4">
                        <form method="POST" action="" class="w-full sm:w-1/2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="consultation_id" value="<?php echo $consultation['consultation_id']; ?>">
                            <button type="submit" name="cancel_consultation" class="w-full px-4 py-3 bg-red-600 text-white font-semibold rounded-lg glass-hover hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:outline-none transition-all duration-300 shadow-md">
                                <i class="fas fa-times-circle mr-2"></i>Cancel Consultation
                            </button>
                        </form>
                        <a href="./dashboard.php" class="w-full sm:w-1/2 px-4 py-3 bg-purple-600 text-white font-semibold rounded-lg glass-hover hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:outline-none transition-all duration-300 text-center shadow-md">
                            <i class="fas fa-arrow-left mr-2"></i>Return to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="pt-4">
                    <a href="./dashboard.php" class="w-full px-4 py-3 bg-purple-600 text-white font-semibold rounded-lg glass-hover hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:outline-none transition-all duration-300 text-center shadow-md">
                        <i class="fas fa-arrow-left mr-2"></i>Return to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>