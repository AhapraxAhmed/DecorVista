<?php
require_once 'config/config.php';

$pageTitle = 'Login - DecorVista';
$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit();
}

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            // Check user credentials
            $stmt = $db->prepare("SELECT u.user_id, u.username, u.email, u.password, u.role, u.is_active, 
                                         ud.first_name, ud.last_name 
                                  FROM users u 
                                  LEFT JOIN user_details ud ON u.user_id = ud.user_id 
                                  WHERE u.email = ? AND u.is_active = 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['login_time'] = time();
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header('Location: admin/dashboard.php');
                            break;
                        case 'designer':
                            header('Location: designer/dashboard.php');
                            break;
                        default:
                            header('Location: dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        }
    }
}

include 'includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-white">
    <div class="max-w-md w-full space-y-8">
        <!-- Login Form Card -->
        <div class="bg-black/5 border border-black/10 rounded-2xl p-8 shadow-lg">
            <div class="text-center">
                <h2 class="font-heading text-3xl font-bold text-black mb-2">Welcome Back</h2>
                <p class="text-gray-700 mb-8">Sign in to your DecorVista account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-black mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-black mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-black/20 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent transition-all duration-300"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword('password')" 
                                class="absolute right-3 top-3 text-gray-500 hover:text-black">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" 
                               class="h-4 w-4 text-black focus:ring-black border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-black">
                            Remember me
                        </label>
                    </div>
                    <a href="/forgot-password.php" class="text-sm underline hover:text-black transition-colors">
                        Forgot password?
                    </a>
                </div>
                
                <button type="submit" name="login" 
                        class="w-full bg-black text-white py-3 rounded-lg font-semibold hover:bg-gray-900 transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-black">
                    Don't have an account? 
                    <a href="./register.php" class="underline hover:text-black font-medium transition-colors">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Demo Credentials -->
        <div class="bg-black/5 border border-black/10 rounded-lg p-4 text-center">
            <h3 class="font-semibold text-black mb-2">Demo Credentials</h3>
            <div class="text-sm text-gray-700 space-y-1">
                <p><strong>Admin:</strong> admin@decorvista.com / admin123</p>
                <p><strong>Designer:</strong> john@decorvista.com / password</p>
                <p><strong>User:</strong> jane@decorvista.com / password</p>
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
</script>
