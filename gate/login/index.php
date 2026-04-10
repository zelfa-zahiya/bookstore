<?php
require_once '../../config/config.php';
requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header('Location: ../../admin');
            } else {
                header('Location: ../..');
            }
            exit();
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-blur {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }

        .blur-1 {
            background: #6366f1;
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }

        .blur-2 {
            background: #ec4899;
            bottom: -150px;
            left: -150px;
            animation-delay: 5s;
        }

        .blur-3 {
            background: #06b6d4;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(50px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-30px, 30px) scale(0.9);
            }
        }

        /* Main Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
        }

        /* Card */
        .login-card {
            background: rgba(18, 18, 24, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 48px 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.4s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 30px 55px -12px rgba(99, 102, 241, 0.2);
        }

        /* Logo */
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-circle {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }

        .logo-circle::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 24px;
            z-index: -1;
            opacity: 0.5;
            filter: blur(10px);
        }

        .logo-circle i {
            font-size: 34px;
            color: white;
        }

        .logo-section h2 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }

        .logo-section p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Form */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 18px;
            transition: all 0.3s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 15px 16px 15px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            color: white;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-wrapper input:focus + i {
            color: #6366f1;
        }

        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Password Toggle */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #6366f1;
        }

        /* Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .checkbox-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-custom input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #6366f1;
        }

        .checkbox-custom span {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .forgot-link {
            font-size: 13px;
            color: #818cf8;
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #a78bfa;
            text-decoration: underline;
        }

        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 18px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }

        /* Register */
        .register-section {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .register-section p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        .register-section a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-section a:hover {
            color: #a78bfa;
            text-decoration: underline;
        }

        /* Alert */
        .alert-custom {
            padding: 14px 18px;
            border-radius: 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-left: 3px solid #ef4444;
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border-left: 3px solid #10b981;
            color: #6ee7b7;
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 36px 28px;
            }
            
            .logo-circle {
                width: 60px;
                height: 60px;
            }
            
            .logo-circle i {
                font-size: 28px;
            }
            
            .logo-section h2 {
                font-size: 24px;
            }
        }

        /* Input Number Hide Arrows */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-circle">
                    <i class="fas fa-book-open"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Sign in to continue to BookStore</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label>Email or Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="text" name="username" placeholder="Enter your email or username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required id="password">
                        <button class="toggle-password" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-custom">
                        <input type="checkbox" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-arrow-right"></i> Sign In
                </button>
            </form>

            <div class="register-section">
                <p>Don't have an account? <a href="../register">Create account</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.type === 'password' ? 'text' : 'password';
                password.type = type;
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        const usernameInput = document.querySelector('input[name="username"]');
        
        if (localStorage.getItem('rememberedUser')) {
            usernameInput.value = localStorage.getItem('rememberedUser');
            if (rememberCheckbox) rememberCheckbox.checked = true;
        }
        
        // Form submit handler
        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                if (rememberCheckbox && rememberCheckbox.checked) {
                    localStorage.setItem('rememberedUser', usernameInput.value);
                } else if (rememberCheckbox) {
                    localStorage.removeItem('rememberedUser');
                }
                
                if (loginBtn) {
                    loginBtn.classList.add('loading');
                    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
                }
            });
        }
        
        // Input focus effects
        document.querySelectorAll('.input-wrapper input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Smooth entrance animation
        window.addEventListener('load', function() {
            document.querySelector('.login-card').style.opacity = '0';
            document.querySelector('.login-card').style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                document.querySelector('.login-card').style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                document.querySelector('.login-card').style.opacity = '1';
                document.querySelector('.login-card').style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>