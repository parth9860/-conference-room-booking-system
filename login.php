<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Handle login
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        }
    } elseif (isset($_POST['register'])) {
        // Handle registration
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if user already exists
            $query = "SELECT id FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'User already exists';
            } else {
                // Check if this is the first user (make them admin)
                $countQuery = "SELECT COUNT(*) as count FROM users";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $userCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                $role = ($userCount == 0) ? 'admin' : 'user';
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $insertQuery = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $insertStmt = $db->prepare($insertQuery);
                
                if ($insertStmt->execute([$name, $email, $hashedPassword, $role])) {
                    $userId = $db->lastInsertId();
                    
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role;
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conference Room Booking - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="mx-auto mb-4 w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                <i data-lucide="building-2" class="w-6 h-6 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Conference Room Booking</h1>
            <p class="text-gray-600">Sign in to manage your reservations</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Welcome</h2>
                <p class="text-gray-600">Sign in to your account or create a new one</p>
            </div>

            <!-- Tab Navigation -->
            <div class="flex mb-6 bg-gray-100 rounded-lg p-1">
                <button onclick="showTab('login')" id="login-tab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md bg-white text-gray-900 shadow-sm">
                    Login
                </button>
                <button onclick="showTab('register')" id="register-tab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900">
                    Register
                </button>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login-form" class="tab-content">
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your password">
                    </div>
                    <button type="submit" name="login" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Sign In
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="tab-content hidden">
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="reg-name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="reg-name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your full name">
                    </div>
                    <div>
                        <label for="reg-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="reg-email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="reg-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="reg-password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Create a password">
                    </div>
                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Confirm your password">
                    </div>
                    <button type="submit" name="register" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Create Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active styles from all tabs
            document.querySelectorAll('[id$="-tab"]').forEach(tab => {
                tab.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                tab.classList.add('text-gray-600', 'hover:text-gray-900');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-form').classList.remove('hidden');
            
            // Add active styles to selected tab
            const activeTab = document.getElementById(tabName + '-tab');
            activeTab.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
            activeTab.classList.remove('text-gray-600', 'hover:text-gray-900');
        }

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
