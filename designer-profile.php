<?php
require_once 'config/config.php';

$designer_id = (int)($_GET['id'] ?? 0);
if ($designer_id <= 0) {
    header('Location: /designers.php');
    exit();
}

$conn = $db->getConnection(); // mysqli connection

// ----------- GET DESIGNER DETAILS -----------
$designer_query = "SELECT u.user_id, u.username, u.email,
                          ud.first_name, ud.last_name, ud.phone, ud.profile_image,
                          id.designer_id, id.years_experience, id.specialization, id.portfolio_url,
                          id.hourly_rate, id.bio, id.availability_status, 
                          id.rating, id.total_reviews
                   FROM users u 
                   JOIN user_details ud ON u.user_id = ud.user_id 
                   JOIN interior_designers id ON u.user_id = id.user_id 
                   WHERE id.designer_id = ? AND u.is_active = 1";

$stmt = $conn->prepare($designer_query);
if (!$stmt) {
    die("Prepare failed for designer: " . $conn->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$designer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$designer) {
    header('Location: /designers.php');
    exit();
}

// ----------- GET DESIGNER REVIEWS -----------
$reviews_query = "SELECT r.*, ud.first_name, ud.last_name, c.scheduled_date, c.scheduled_time
                  FROM reviews r
                  JOIN users u ON r.user_id = u.user_id
                  JOIN user_details ud ON u.user_id = ud.user_id
                  LEFT JOIN consultations c ON r.consultation_id = c.consultation_id
                  WHERE r.designer_id = ? AND r.is_approved = 1
                  ORDER BY r.created_at DESC
                  LIMIT 10";

$stmt = $conn->prepare($reviews_query);
if (!$stmt) {
    die("Prepare failed for reviews: " . $conn->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ----------- GET AVAILABILITY -----------
$availability_query = "SELECT da.day_of_week, da.start_time, da.end_time
                       FROM designer_availability da
                       WHERE da.designer_id = ? AND da.is_active = 1
                       ORDER BY FIELD(da.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";

$stmt = $conn->prepare($availability_query);
if (!$stmt) {
    die("Prepare failed for availability: " . $conn->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ----------- GET RECENT PROJECTS -----------
$projects_query = "SELECT * FROM gallery WHERE designer_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 6";

$stmt = $conn->prepare($projects_query);
if (!$stmt) {
    die("Prepare failed for projects: " . $conn->error);
}
$stmt->bind_param("i", $designer_id);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'includes/header.php';
?>

<main data-scroll-container class="min-h-screen bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 pb-20">
    <!-- Breadcrumb -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm text-gray-600">
                    <li><a href="/index.php" class="hover:text-gray-800 transition-colors duration-300">Home</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li><a href="/designers.php" class="hover:text-gray-800 transition-colors duration-300">Designers</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-black"><?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?></li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Designer Header -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 mb-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="flex flex-col lg:flex-row lg:items-center lg:space-x-8">
                    <!-- Designer Photo -->
                    <div class="flex-shrink-0 mb-6 lg:mb-0">
                        <div class="w-32 h-32 lg:w-40 lg:h-40 rounded-full overflow-hidden bg-gray-200 mx-auto lg:mx-0">
                            <?php if ($designer['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($designer['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                    <i class="fas fa-user text-4xl lg:text-5xl text-gray-700"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Designer Info -->
                    <div class="flex-1 text-center lg:text-left">
                        <div class="mb-4">
                            <h1 class="font-heading text-3xl lg:text-4xl font-bold text-black mb-2">
                                <?php echo htmlspecialchars($designer['first_name'] . ' ' . $designer['last_name']); ?>
                            </h1>
                            
                            <?php if ($designer['specialization']): ?>
                                <p class="text-lg text-gray-600 font-medium mb-2">
                                    <?php echo htmlspecialchars($designer['specialization']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="flex flex-wrap justify-center lg:justify-start items-center space-x-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-briefcase mr-1 text-gray-700"></i>
                                    <?php echo $designer['years_experience']; ?> years experience
                                </div>
                                
                                <?php if ($designer['total_reviews'] > 0): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                                        <?php echo number_format($designer['rating'], 1); ?> (<?php echo $designer['total_reviews']; ?> reviews)
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-xs mr-2 <?php 
                                        echo $designer['availability_status'] === 'available' ? 'text-green-500' :
                                             ($designer['availability_status'] === 'busy' ? 'text-yellow-500' : 'text-red-500');
                                    ?>"></i>
                                    <?php echo ucfirst($designer['availability_status']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($designer['hourly_rate']): ?>
                            <div class="mb-6">
                                <span class="text-2xl lg:text-3xl font-bold text-gray-800">
                                    <?php echo formatPrice($designer['hourly_rate']); ?>
                                </span>
                                <span class="text-gray-600">/hour</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-center lg:justify-start space-y-3 sm:space-y-0 sm:space-x-4">
                            <?php if (isLoggedIn() && $designer['availability_status'] === 'available'): ?>
                                <a href="/book-consultation.php?designer_id=<?php echo $designer_id; ?>" 
                                   class="bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-300">
                                    <i class="fas fa-calendar-plus mr-2"></i>Book Consultation
                                </a>
                            <?php elseif (!isLoggedIn()): ?>
                                <a href="login.php" class="bg-gray-800 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-900 transition-colors duration-300">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Book
                                </a>
                            <?php else: ?>
                                <button class="bg-gray-300 text-gray-600 px-6 py-3 rounded-lg font-medium cursor-not-allowed" disabled>
                                    <i class="fas fa-calendar-times mr-2"></i>Currently Unavailable
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="contactDesigner()" class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors duration-300">
                                <i class="fas fa-envelope mr-2"></i>Send Message
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="py-8 px-4" data-scroll-section>
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Main Info -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- About -->
                    <?php if ($designer['bio']): ?>
                        <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                            <h2 class="font-heading text-xl font-semibold text-black mb-4">About</h2>
                            <div class="prose prose-lg max-w-none text-gray-600">
                                <?php echo nl2br(htmlspecialchars($designer['bio'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Portfolio/Recent Projects -->
                    <?php if (!empty($projects)): ?>
                        <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                            <h2 class="font-heading text-xl font-semibold text-black mb-6">Recent Projects</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($projects as $project): ?>
                                    <div class="aspect-square rounded-lg overflow-hidden group cursor-pointer">
                                        <img src="<?php echo htmlspecialchars($project['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($project['title']); ?>"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                             onclick="openProjectModal('<?php echo htmlspecialchars($project['image_url']); ?>', '<?php echo htmlspecialchars($project['title']); ?>', '<?php echo htmlspecialchars($project['description']); ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reviews -->
                    <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="font-heading text-xl font-semibold text-black">Client Reviews</h2>
                            <?php if ($designer['total_reviews'] > count($reviews)): ?>
                                <button class="text-gray-700 hover:text-gray-900 text-sm transition-colors duration-300">
                                    View All Reviews
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No reviews yet</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="border-b border-gray-200 pb-6 last:border-b-0">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <div class="font-medium text-black">
                                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                </div>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <div class="flex items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-sm"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="text-sm text-gray-600">
                                                        <?php echo timeAgo($review['created_at']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($review['comment']): ?>
                                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column - Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <h3 class="font-semibold text-black mb-4">Quick Stats</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Experience:</span>
                                <span class="font-medium text-black"><?php echo $designer['years_experience']; ?> years</span>
                            </div>
                            
                            <?php if ($designer['total_reviews'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Rating:</span>
                                    <span class="font-medium text-black"><?php echo number_format($designer['rating'], 1); ?>/5.0</span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Reviews:</span>
                                    <span class="font-medium text-black"><?php echo $designer['total_reviews']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($designer['hourly_rate']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Hourly Rate:</span>
                                    <span class="font-medium text-black"><?php echo formatPrice($designer['hourly_rate']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Availability -->
                    <?php if (!empty($availability)): ?>
                        <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                            <h3 class="font-semibold text-black mb-4">
                                <i class="fas fa-clock mr-2 text-gray-700"></i>Availability
                            </h3>
                            <div class="space-y-2">
                                <?php foreach ($availability as $slot): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 capitalize"><?php echo $slot['day_of_week']; ?>:</span>
                                        <span class="font-medium text-black">
                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contact Info -->
                    <div class="bg-white/80 backdrop-blur-lg rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <h3 class="font-semibold text-black mb-4">
                            <i class="fas fa-address-card mr-2 text-gray-700"></i>Contact Information
                        </h3>
                        <div class="space-y-3">
                            <?php if ($designer['phone']): ?>
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-phone text-gray-700"></i>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($designer['phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-envelope text-gray-700"></i>
                                <span class="text-gray-600">Contact via platform</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button onclick="contactDesigner()" class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors duration-300 w-full">
                                <i class="fas fa-paper-plane mr-2"></i>Send Message
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Project Modal -->
    <div id="projectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white/80 backdrop-blur-lg rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="font-heading text-xl font-semibold text-black"></h3>
                    <button onclick="closeProjectModal()" class="text-gray-600 hover:text-gray-800 transition-colors duration-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <img id="modalImage" src="/placeholder.svg" alt="" class="w-full rounded-lg mb-4">
                <p id="modalDescription" class="text-gray-600"></p>
            </div>
        </div>
    </div>

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
<script>
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('load', () => {
        try {
            window.locomotiveScroll = new LocomotiveScroll({
                el: document.querySelector('[data-scroll-container]'),
                smooth: true,
                multiplier: 1,
                lerp: 0.1,
                reloadOnContextChange: true
            });

            const resizeObserver = new ResizeObserver(() => {
                try {
                    window.locomotiveScroll.update();
                } catch (error) {
                    console.error('Error updating Locomotive Scroll:', error);
                }
            });
            resizeObserver.observe(document.querySelector('[data-scroll-container]'));

            const images = document.querySelectorAll('img');
            Promise.all(Array.from(images).map(img => {
                if (img.complete) return Promise.resolve();
                return new Promise(resolve => {
                    img.addEventListener('load', resolve);
                    img.addEventListener('error', () => {
                        console.warn(`Image failed to load: ${img.src}`);
                        resolve();
                    });
                });
            })).then(() => {
                try {
                    window.locomotiveScroll.update();
                } catch (error) {
                    console.error('Error updating Locomotive Scroll after images load:', error);
                }
            }).catch(error => {
                console.error('Error processing images:', error);
            });
        } catch (error) {
            console.error('Error initializing Locomotive Scroll:', error);
        }
    });
});

function contactDesigner() {
    try {
        <?php if (!isLoggedIn()): ?>
            window.location.href = 'login.php';
            return;
        <?php endif; ?>
        
        // In a real application, this would open a messaging system
        if (typeof showNotification === 'function') {
            showNotification('Messaging system would be implemented here. This is a demo application.', 'info');
        } else {
            alert('Messaging system would be implemented here. This is a demo application.');
        }
    } catch (error) {
        console.error('Error in contactDesigner:', error);
        if (typeof showNotification === 'function') {
            showNotification('An error occurred while trying to contact the designer.', 'error');
        } else {
            alert('An error occurred while trying to contact the designer.');
        }
    }
}

function openProjectModal(imageUrl, title, description) {
    try {
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');
        const projectModal = document.getElementById('projectModal');

        if (!modalImage || !modalTitle || !modalDescription || !projectModal) {
            throw new Error('Modal elements not found');
        }

        modalImage.src = imageUrl || '/placeholder.svg';
        modalTitle.textContent = title || 'Project';
        modalDescription.textContent = description || 'No description available';
        projectModal.classList.remove('hidden');
    } catch (error) {
        console.error('Error opening project modal:', error);
        if (typeof showNotification === 'function') {
            showNotification('An error occurred while opening the project modal.', 'error');
        } else {
            alert('An error occurred while opening the project modal.');
        }
    }
}

function closeProjectModal() {
    try {
        const projectModal = document.getElementById('projectModal');
        if (!projectModal) {
            throw new Error('Project modal not found');
        }
        projectModal.classList.add('hidden');
    } catch (error) {
        console.error('Error closing project modal:', error);
        if (typeof showNotification === 'function') {
            showNotification('An error occurred while closing the project modal.', 'error');
        } else {
            alert('An error occurred while closing the project modal.');
        }
    }
}

// Close modal when clicking outside
try {
    const projectModal = document.getElementById('projectModal');
    if (projectModal) {
        projectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProjectModal();
            }
        });
    }
} catch (error) {
    console.error('Error setting up modal click handler:', error);
}
</script>