<?php
require_once 'config/config.php';

// Require login
requireLogin();

$designer_id = (int)($_GET['designer_id'] ?? 0);
$error = '';
$success = '';

if ($designer_id <= 0) {
    header('Location: /designers.php');
    exit();
}

// Get designer details
$designer_query = "SELECT u.user_id, ud.first_name, ud.last_name, id.designer_id, id.hourly_rate, id.availability_status
                   FROM users u 
                   JOIN user_details ud ON u.user_id = ud.user_id 
                   JOIN interior_designers id ON u.user_id = id.user_id 
                   WHERE id.designer_id = ? AND u.is_active = 1";

$stmt = $db->prepare($designer_query);
if ($stmt === false) {
    die("Prepare failed: " . $db->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$designer = $stmt->get_result()->fetch_assoc();

if (!$designer || $designer['availability_status'] !== 'available') {
    header('Location: /designers.php');
    exit();
}

// Set default hourly rate if null
$hourly_rate = $designer['hourly_rate'] ?? 0;

$pageTitle = 'Book Consultation - ' . htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']) . ' - DecorVista';

// Handle booking form submission
if ($_POST && isset($_POST['book_consultation'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $consultation_date = sanitizeInput($_POST['consultation_date'] ?? '');
        $consultation_time = sanitizeInput($_POST['consultation_time'] ?? '');
        $consultation_type = sanitizeInput($_POST['consultation_type'] ?? 'online');
        $duration = (int)($_POST['duration'] ?? 60);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        if (empty($consultation_date) || empty($consultation_time)) {
            $error = 'Please select a date and time for your consultation.';
        } elseif ($hourly_rate == 0) {
            $error = 'This designer has no hourly rate set. Please contact support.';
        } else {
            $price = $hourly_rate * ($duration / 60);
            
            // Check if the time slot is available
            $check_query = "SELECT consultation_id FROM consultations 
                           WHERE designer_id = ? AND scheduled_date = ? AND scheduled_time = ? AND status != 'cancelled'";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param("iss", $designer_id, $consultation_date, $consultation_time);
            $stmt->execute();
            
            if ($stmt->get_result()->fetch_assoc()) {
                $error = 'This time slot is already booked. Please choose another time.';
            } else {
                // Create consultation booking
                $insert_query = "INSERT INTO consultations (user_id, designer_id, scheduled_date, scheduled_time, duration_hours, status, notes, client_requirements, total_cost) 
                                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
                $stmt = $db->prepare($insert_query);
                $duration_hours = $duration / 60;
                $stmt->bind_param("iississs", $_SESSION['user_id'], $designer_id, $consultation_date, $consultation_time, $duration_hours, $notes, $consultation_type, $price);
                
                if ($stmt->execute()) {
                    $consultation_id = $db->getConnection()->insert_id;
                    $success = 'Consultation booked successfully! You will receive a confirmation email shortly.';
                    
                    // Redirect to consultation details after a delay
                    header("refresh:3;url=./consultation-details.php?id=$consultation_id");
                } else {
                    $error = 'Failed to book consultation. Please try again.';
                }
            }
        }
    }
}

// Get designer availability for the next 30 days
$availability_query = "SELECT da.start_time, da.end_time
                       FROM designer_availability da
                       WHERE da.designer_id = ? AND da.is_active = 1";
$stmt = $db->prepare($availability_query);
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="glass rounded-2xl p-8 mb-8">
        <div class="text-center">
            <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Book Consultation</h1>
            <p class="text-gray-600">
                Schedule a consultation with <?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
            </p>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 glass">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 glass">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Booking Form -->
        <div class="lg:col-span-2">
            <div class="glass rounded-xl p-8">
                <h2 class="font-heading text-xl font-semibold text-gray-900 mb-6">Consultation Details</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Date Selection -->
                    <div>
                        <label for="consultation_date" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-2"></i>Preferred Date *
                        </label>
                        <input type="date" id="consultation_date" name="consultation_date" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent glass transition-all duration-300"
                               value="<?php echo htmlspecialchars($_POST['consultation_date'] ?? ''); ?>">
                    </div>
                    
                    <!-- Time Selection -->
                    <div>
                        <label for="consultation_time" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-clock mr-2"></i>Preferred Time *
                        </label>
                        <select id="consultation_time" name="consultation_time" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent glass">
                            <option value="">Select a time</option>
                            <!-- Time slots will be populated by JavaScript based on availability -->
                        </select>
                    </div>
                    
                    <!-- Consultation Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-video mr-2"></i>Consultation Type *
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="glass rounded-lg p-4 cursor-pointer hover:bg-purple-50 transition-colors">
                                <input type="radio" name="consultation_type" value="online" checked class="sr-only">
                                <div class="flex items-center">
                                    <i class="fas fa-video text-purple-600 text-xl mr-3"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">Online Video Call</div>
                                        <div class="text-sm text-gray-600">Meet via video conference</div>
                                    </div>
                                </div>
                            </label>
                            <label class="glass rounded-lg p-4 cursor-pointer hover:bg-purple-50 transition-colors">
                                <input type="radio" name="consultation_type" value="in-person" class="sr-only">
                                <div class="flex items-center">
                                    <i class="fas fa-handshake text-purple-600 text-xl mr-3"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">In-Person Meeting</div>
                                        <div class="text-sm text-gray-600">Meet at your location</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Duration -->
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-hourglass-half mr-2"></i>Duration
                        </label>
                        <select id="duration" name="duration" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent glass"
                                onchange="updatePrice()">
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    
                    <!-- Project Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-sticky-note mr-2"></i>Project Details & Notes
                        </label>
                        <textarea id="notes" name="notes" rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent glass transition-all duration-300"
                                  placeholder="Tell us about your project, style preferences, budget, timeline, etc."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" name="book_consultation" class="btn-primary w-full glass-hover">
                            <i class="fas fa-calendar-check mr-2"></i>Book Consultation
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Booking Summary -->
        <div class="lg:col-span-1">
            <div class="glass rounded-xl p-6 sticky top-24">
                <h3 class="font-heading text-lg font-semibold text-gray-900 mb-4">Booking Summary</h3>
                
                <!-- Designer Info -->
                <div class="mb-6">
                    <div class="font-medium text-gray-900">
                        <?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
                    </div>
                    <div class="text-sm text-gray-600">Interior Designer</div>
                </div>
                
                <!-- Pricing -->
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hourly Rate:</span>
                        <span class="font-medium"><?php echo $hourly_rate ? formatPrice($hourly_rate) : 'Not set'; ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Duration:</span>
                        <span class="font-medium" id="duration-display">1 hour</span>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-900">Total:</span>
                            <span class="text-xl font-bold text-purple-600" id="total-price">
                                <?php echo $hourly_rate ? formatPrice($hourly_rate) : 'Not set'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- What's Included -->
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 mb-3">What's Included:</h4>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Design consultation
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Style recommendations
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Product suggestions
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Follow-up summary
                        </li>
                    </ul>
                </div>
                
                <!-- Cancellation Policy -->
                <div class="text-xs text-gray-500">
                    <p class="mb-2"><strong>Cancellation Policy:</strong></p>
                    <p>Free cancellation up to 24 hours before the scheduled consultation. Cancellations within 24 hours may incur a 50% fee.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const hourlyRate = <?php echo json_encode($hourly_rate); ?>;
const availability = <?php echo json_encode($availability); ?>;

function updatePrice() {
    if (!hourlyRate) {
        document.getElementById('total-price').textContent = 'Not set';
        document.getElementById('duration-display').textContent = '1 hour';
        return;
    }
    const duration = document.getElementById('duration').value;
    const hours = duration / 60;
    const total = hourlyRate * hours;
    
    document.getElementById('total-price').textContent = '$' + total.toFixed(2);
    document.getElementById('duration-display').textContent = hours === 1 ? '1 hour' : hours + ' hours';
}

function generateTimeSlots() {
    const dateInput = document.getElementById('consultation_date');
    const timeSelect = document.getElementById('consultation_time');
    
    // Clear existing options
    timeSelect.innerHTML = '<option value="">Select a time</option>';
    
    // Check if there is availability data
    if (availability.length > 0) {
        const dayAvailability = availability[0]; // Use the first availability record
        const startTime = dayAvailability.start_time;
        const endTime = dayAvailability.end_time;
        
        // Generate time slots (1-hour intervals)
        let currentTime = new Date('2000-01-01 ' + startTime);
        const endDateTime = new Date('2000-01-01 ' + endTime);
        
        while (currentTime < endDateTime) {
            const timeString = currentTime.toTimeString().slice(0, 5);
            const displayTime = currentTime.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            const option = document.createElement('option');
            option.value = timeString;
            option.textContent = displayTime;
            timeSelect.appendChild(option);
            
            currentTime.setHours(currentTime.getHours() + 1);
        }
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No availability for this designer';
        option.disabled = true;
        timeSelect.appendChild(option);
    }
}

// Event listeners
document.getElementById('consultation_date').addEventListener('change', generateTimeSlots);

// Handle consultation type selection styling
document.addEventListener('DOMContentLoaded', function() {
    const typeInputs = document.querySelectorAll('input[name="consultation_type"]');
    
    typeInputs.forEach(input => {
        input.addEventListener('change', function() {
            typeInputs.forEach(radio => {
                const label = radio.closest('label');
                label.classList.remove('bg-purple-100', 'border-purple-300');
            });
            
            if (this.checked) {
                const label = this.closest('label');
                label.classList.add('bg-purple-100', 'border-purple-300');
            }
        });
    });
    
    const checkedInput = document.querySelector('input[name="consultation_type"]:checked');
    if (checkedInput) {
        checkedInput.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>