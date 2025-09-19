<?php
require_once 'config/config.php';

$pageTitle = 'Register - DecorVista';
$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit();
}

// Handle registration form submission
if ($_POST && isset($_POST['register'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $firstname = sanitizeInput($_POST['first_name'] ?? '');
        $lastname = sanitizeInput($_POST['last_name'] ?? '');
        $contactnumber = sanitizeInput($_POST['phone'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'homeowner');
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($firstname) || empty($lastname)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if username or email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password and create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $db->getConnection()->begin_transaction();
                
                try {
                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                    $stmt->execute();
                    
                    $user_id = $db->getLastInsertId();
                    
                    // Insert user details
                    $stmt = $db->prepare("INSERT INTO user_details (user_id, first_name, last_name, phone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $user_id, $firstname, $lastname, $contactnumber);
                    $stmt->execute();
                    
                    // If designer, create designer profile
                    if ($role === 'designer') {
                        $stmt = $db->prepare("INSERT INTO interior_designers (user_id, bio) VALUES (?, ?)");
                        $default_bio = "Professional interior designer ready to transform your space.";
                        $stmt->bind_param("is", $user_id, $default_bio);
                        $stmt->execute();
                    }
                    
                    $db->getConnection()->commit();
                    
                    // Set session variables and redirect
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;
                    $_SESSION['first_name'] = $firstname;
                    $_SESSION['last_name'] = $lastname;
                    $_SESSION['login_time'] = time();
                    
                    // Redirect based on role
                    switch ($role) {
                        case 'designer':
                            header('Location: ./designer/dashboard.php');
                            break;
                        default:
                            header('Location: /dashboard.php');
                    }
                    exit();
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    $error = 'Registration failed. Please try again.';
                }
            }
            $stmt->close();
        }
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-white">
    <div class="max-w-2xl w-full space-y-8">
        <!-- Registration Form Card -->
        <div class="bg-black/5 border border-black/10 rounded-2xl p-8 shadow-lg">
            <div class="text-center">
                <h2 class="font-heading text-3xl font-bold text-black mb-2">Join DecorVista</h2>
                <p class="text-gray-700 mb-8">Create your account and start designing</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- Account Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-black mb-3">
                        <i class="fas fa-user-tag mr-2"></i>Account Type
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="border border-black/20 rounded-lg p-4 cursor-pointer hover:bg-black/10 transition-colors">
                            <input type="radio" name="role" value="homeowner" checked class="sr-only">
                            <div class="flex items-center">
                                <i class="fas fa-home text-black text-xl mr-3"></i>
                                <div>
                                    <div class="font-medium text-black">Homeowner</div>
                                    <div class="text-sm text-gray-700">Browse products and book consultations</div>
                                </div>
                            </div>
                        </label>
                        <label class="border border-black/20 rounded-lg p-4 cursor-pointer hover:bg-black/10 transition-colors">
                            <input type="radio" name="role" value="designer" class="sr-only">
                            <div class="flex items-center">
                                <i class="fas fa-palette text-black text-xl mr-3"></i>
                                <div>
                                    <div class="font-medium text-black">Interior Designer</div>
                                    <div class="text-sm text-gray-700">Offer professional design services</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="firstname" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-user mr-2"></i>First Name *
                        </label>
                        <input type="text" id="firstname" name="first_name" required
                               class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                               placeholder="Enter your first name"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="lastname" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-user mr-2"></i>Last Name *
                        </label>
                        <input type="text" id="lastname" name="last_name" required
                               class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                               placeholder="Enter your last name"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Account Information -->
                <div>
                    <label for="username" class="block text-sm font-medium text-black mb-2">
                        <i class="fas fa-at mr-2"></i>Username *
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                           placeholder="Choose a username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-black mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email Address *
                    </label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div>
                    <label for="contactnumber" class="block text-sm font-medium text-black mb-2">
                        <i class="fas fa-phone mr-2"></i>Contact Number
                    </label>
                    <input type="tel" id="contactnumber" name="contactnumber"
                           class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                           placeholder="Enter your phone number"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-lock mr-2"></i>Password *
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                                   placeholder="Create a password">
                            <button type="button" onclick="togglePassword('password')" 
                                    class="absolute right-3 top-3 text-gray-500 hover:text-black">
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-black mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirm Password *
                        </label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                                   placeholder="Confirm your password">
                            <button type="button" onclick="togglePassword('confirm_password')" 
                                    class="absolute right-3 top-3 text-gray-500 hover:text-black">
                                <i class="fas fa-eye" id="confirm_password-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="flex items-center">
                    <input type="checkbox" id="terms" name="terms" required
                           class="h-4 w-4 text-black focus:ring-black border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-black">
                        I agree to the <a href="/terms.php" class="underline hover:text-gray-700">Terms of Service</a> 
                        and <a href="/privacy.php" class="underline hover:text-gray-700">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" name="register" 
                        class="w-full bg-black text-white py-3 rounded-lg font-semibold hover:bg-gray-900 transition-all duration-300">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-700">
                    Already have an account? 
                    <a href="login.php" class="underline hover:text-black font-medium transition-colors">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>


<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}

// Handle role selection styling
document.addEventListener('DOMContentLoaded', function() {
    const roleInputs = document.querySelectorAll('input[name="role"]');
    
    roleInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Remove selected styling from all labels
            roleInputs.forEach(radio => {
                const label = radio.closest('label');
                label.classList.remove('bg-gray-100', 'border-gray-300');
            });
            
            // Add selected styling to current label
            if (this.checked) {
                const label = this.closest('label');
                label.classList.add('bg-gray-100', 'border-gray-300');
            }
        });
    });
    
    // Set initial selection
    const checkedInput = document.querySelector('input[name="role"]:checked');
    if (checkedInput) {
        checkedInput.dispatchEvent(new Event('change'));
    }
});
</script>

