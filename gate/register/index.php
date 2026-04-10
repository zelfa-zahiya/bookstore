<?php
require_once '../../config/config.php';
requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = $_POST['terms'] ?? '';
    
    // Validasi
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (empty($terms)) {
        $error = 'Anda harus menyetujui syarat & ketentuan!';
    } else {
        try {
            $check_sql = "SELECT id_user FROM users WHERE username = :username OR email = :email";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':username' => $username, ':email' => $email]);
            
            if ($check_stmt->rowCount() > 0) {
                $error = 'Username atau email sudah terdaftar!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $sql = "INSERT INTO users (nama_lengkap, username, email, password, role, created_at, updated_at) 
                        VALUES (:nama_lengkap, :username, :email, :password, 'user', NOW(), NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nama_lengkap' => $nama_lengkap,
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);
                
                $success = 'Registrasi berhasil! Silakan login.';
                $nama_lengkap = $username = $email = '';
            }
        } catch(PDOException $e) {
            $error = 'Registrasi gagal: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | BookStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }

        .register-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
        }

        .register-card {
            background: rgba(18, 18, 24, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 40px 36px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.4s ease;
        }

        .register-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 30px 55px -12px rgba(99, 102, 241, 0.2);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
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

        .form-group {
            margin-bottom: 20px;
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
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            font-size: 14px;
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
            z-index: 2;
        }

        .toggle-password:hover {
            color: #6366f1;
        }

        /* Password Strength Meter */
        .password-strength {
            margin-top: 12px;
        }

        .strength-bar-container {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
            border-radius: 3px;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 8px;
            font-weight: 500;
            text-align: right;
        }

        .terms-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }

        .terms-group input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #6366f1;
        }

        .terms-group label {
            margin: 0;
            font-weight: 500;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }

        .terms-group a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-group a:hover {
            text-decoration: underline;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 18px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }

        .login-section {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-section p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }

        .login-section a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-section a:hover {
            color: #a78bfa;
            text-decoration: underline;
        }

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

        @media (max-width: 480px) {
            .register-card {
                padding: 32px 24px;
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
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <div class="register-wrapper">
        <div class="register-card">
            <div class="logo-section">
                <div class="logo-circle">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Create Account</h2>
                <p>Join our community of readers</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <script>
                    setTimeout(function() { window.location.href = '../login'; }, 2000);
                </script>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" 
                               value="<?php echo isset($nama_lengkap) ? htmlspecialchars($nama_lengkap) : ''; ?>" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" placeholder="Pilih username unik"
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="contoh@email.com"
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <button class="toggle-password" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Meter -->
                    <div class="password-strength">
                        <div class="strength-bar-container">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Ulangi password" required>
                        <button class="toggle-password" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        Saya menyetujui <a href="#">Syarat & Ketentuan</a> dan <a href="#">Kebijakan Privasi</a>
                    </label>
                </div>

                <button type="submit" class="btn-register" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>

                <div class="login-section">
                    <p>Sudah punya akun? <a href="../login">Masuk di sini</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        if (togglePassword && passwordField) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordField = document.getElementById('confirm_password');
        if (toggleConfirmPassword && confirmPasswordField) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
                confirmPasswordField.type = type;
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Password strength berdasarkan panjang karakter
        // < 4 karakter: lemah (merah, bar 33%)
        // 4-5 karakter: sedang (kuning, bar 66%)
        // >= 6 karakter: kuat (hijau, bar 100%)
        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            if (!strengthBar || !strengthText) return;
            
            const length = password.length;
            if (length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
                return;
            }
            
            if (length < 4) {
                strengthBar.style.width = '33%';
                strengthBar.style.background = '#ef4444'; // merah
                strengthText.textContent = 'Password lemah';
                strengthText.style.color = '#ef4444';
            } else if (length >= 4 && length <= 5) {
                strengthBar.style.width = '66%';
                strengthBar.style.background = '#f59e0b'; // kuning
                strengthText.textContent = 'Password sedang';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = '#10b981'; // hijau
                strengthText.textContent = 'Password kuat';
                strengthText.style.color = '#10b981';
            }
        }

        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                updatePasswordStrength(this.value);
            });
            // initial check
            updatePasswordStrength(passwordInput.value);
        }

        // Form validation
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;
                const terms = document.getElementById('terms').checked;
                
                if (password.length < 4) {
                    e.preventDefault();
                    alert('Password minimal 4 karakter!');
                    return false;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Password dan konfirmasi password tidak cocok!');
                    return false;
                }
                
                if (!terms) {
                    e.preventDefault();
                    alert('Anda harus menyetujui syarat & ketentuan!');
                    return false;
                }
                
                if (registerBtn) {
                    registerBtn.classList.add('loading');
                    registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftar...';
                }
            });
        }

        // Smooth entrance animation
        window.addEventListener('load', function() {
            const card = document.querySelector('.register-card');
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>