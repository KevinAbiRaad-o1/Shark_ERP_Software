<?php
session_start();
require_once 'DataBaseconnection/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare("SELECT u.user_id, u.password, e.id as employee_id, e.is_active
                             FROM users u 
                             JOIN employee e ON u.person_id = e.id 
                             WHERE u.username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Check if account is active
        if ($user && $user['is_active'] == 0) {
            $error = "This account is deactivated. Please contact HR.";
        }
        // TEMPORARY: Plain text comparison (remove later)
        elseif ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            
            // Redirect based on role (maintaining role security)
            $roleStmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
            $roleStmt->execute([$user['employee_id']]);
            $role = $roleStmt->fetchColumn();
            
            header("Location: User panels/{$role}/index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "System error. Please try again.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shark Inventory Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4b6cb7;
            --secondary-color: #182848;
            --danger-color: #dc3545;
            --light-bg: #f5f5f5;
            --text-dark: #333;
            --text-muted: #6c757d;
        }
        
        body {
            background-color: var(--light-bg);
            height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .logo i {
            font-size: 2.5rem;
            margin-right: 1rem;
        }
        
        .login-right {
            padding: 3rem;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 5;
        }
        
        .toggle-password {
            position: absolute;
            right: 0rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            z-index: 5;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
        }
        
        .btn-login:hover {
            background-color: var(--secondary-color);
        }
        
        /* Full-screen loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        
        .loading-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            padding: 20px;
        }
                
        @media (max-width: 767.98px) {
            .login-left {
                padding: 2rem;
                text-align: center;
            }
            
            .login-right {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Full-screen loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <img src="slogan.jpg" class="loading-image" alt="Loading">
    </div>

    <div class="container">
        <div class="row login-container">
            <div class="col-md-6 login-left d-none d-md-block">
                <div class="logo">
                    <h1 class="mb-0">Shark ERP</h1>
                </div>
                <p class="lead">Manage your inventory with precision and ease</p>
            </div>
            
            <div class="col-md-6 login-right">
                <div class="login-form">
                    <h2 class="mb-3">Welcome Back</h2>
                    <p class="text-muted mb-4">Please enter your credentials to login</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm">
                        <div class="mb-3 position-relative">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                            </div>
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <span class="input-group-text toggle-password" onclick="togglePasswordVisibility()">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-login mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Loading overlay control
        function showLoading() {
            const overlay = document.getElementById("loadingOverlay");
            
            // Hide after 0.5 seconds with fade effect
            setTimeout(() => {
                overlay.style.opacity = "0";
                setTimeout(() => {
                    overlay.style.display = "none";
                }, 500);
            }, 500);
        }

        // Start loading when page opens
        window.addEventListener("load", showLoading);

        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.querySelector(".toggle-password i");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("bi-eye");
                eyeIcon.classList.add("bi-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("bi-eye-slash");
                eyeIcon.classList.add("bi-eye");
            }
        }

        // Form submission handler
        document.getElementById("loginForm").addEventListener("submit", function() {
            // Show loading overlay during authentication
            document.getElementById("loadingOverlay").style.display = "flex";
            document.getElementById("loadingOverlay").style.opacity = "1";
        });
    </script>
</body>
</html>