<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: gate/login/");
    exit();
}

$userId = $_SESSION['user_id'];
$successMessage = "";
$errorMessage = "";

// Ambil data user
$result = $conn->query("SELECT id_user, username, email, role, foto_profil, created_at FROM users WHERE id_user = $userId");
$userData = $result->fetch_assoc();

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $newUsername = $conn->real_escape_string($_POST['username']);
        $newEmail = $conn->real_escape_string($_POST['email']);
        
        if (empty($newUsername) || empty($newEmail)) {
            $errorMessage = "Username dan email tidak boleh kosong!";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Email tidak valid!";
        } else {
            $check = $conn->query("SELECT id_user FROM users WHERE username = '$newUsername' AND id_user != $userId");
            if ($check->num_rows > 0) {
                $errorMessage = "Username sudah digunakan!";
            } else {
                if ($conn->query("UPDATE users SET username = '$newUsername', email = '$newEmail' WHERE id_user = $userId")) {
                    $successMessage = "Profile berhasil diperbarui!";
                    $userData['username'] = $newUsername;
                    $userData['email'] = $newEmail;
                } else {
                    $errorMessage = "Gagal update: " . $conn->error;
                }
            }
        }
    }
    
    // Ganti password
    elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Ambil password dari database
        $passResult = $conn->query("SELECT password FROM users WHERE id_user = $userId");
        $userPass = $passResult->fetch_assoc();
        $dbPassword = $userPass['password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "Semua field harus diisi!";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = "Password baru tidak cocok!";
        } elseif (strlen($newPassword) < 4) {
            $errorMessage = "Password minimal 4 karakter!";
        } else {
            // Cek apakah password di database menggunakan hash atau plain text
            $passwordValid = false;
            
            // Cek jika password menggunakan password_hash (dimulai dengan $2y$)
            if (strpos($dbPassword, '$2y$') === 0) {
                // Password sudah di-hash
                if (password_verify($currentPassword, $dbPassword)) {
                    $passwordValid = true;
                }
            } else {
                // Password plain text
                if ($currentPassword === $dbPassword) {
                    $passwordValid = true;
                }
            }
            
            if (!$passwordValid) {
                $errorMessage = "Password saat ini salah!";
            } else {
                // Simpan password baru (disarankan menggunakan hash)
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                if ($conn->query("UPDATE users SET password = '$hashedPassword' WHERE id_user = $userId")) {
                    $successMessage = "Password berhasil diubah!";
                } else {
                    $errorMessage = "Gagal ubah password: " . $conn->error;
                }
            }
        }
    }
    
    // Upload foto
    elseif (isset($_POST['upload_photo']) && isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileError === 0) {
            if (in_array($ext, $allowed)) {
                if ($fileSize <= 2000000) { // 2MB
                    $uploadDir = 'asset/profile/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Hapus foto lama
                    if (!empty($userData['foto_profil']) && file_exists($uploadDir . $userData['foto_profil'])) {
                        unlink($uploadDir . $userData['foto_profil']);
                    }
                    
                    $newFileName = 'user_' . $userId . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpName, $destination)) {
                        $conn->query("UPDATE users SET foto_profil = '$newFileName' WHERE id_user = $userId");
                        $successMessage = "Foto profil berhasil diupload!";
                        $userData['foto_profil'] = $newFileName;
                    } else {
                        $errorMessage = "Gagal upload file!";
                    }
                } else {
                    $errorMessage = "Ukuran file terlalu besar! Maks 2MB.";
                }
            } else {
                $errorMessage = "Format tidak didukung! Gunakan JPG, PNG, GIF, WEBP.";
            }
        } else {
            $errorMessage = "Error upload file!";
        }
    }
    
    // Hapus foto
    elseif (isset($_POST['delete_photo'])) {
        if (!empty($userData['foto_profil']) && file_exists('asset/profile/' . $userData['foto_profil'])) {
            unlink('asset/profile/' . $userData['foto_profil']);
        }
        $conn->query("UPDATE users SET foto_profil = NULL WHERE id_user = $userId");
        $successMessage = "Foto profil dihapus!";
        $userData['foto_profil'] = null;
    }
}

// Ambil statistik
$totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE id_user = $userId")->fetch_assoc()['total'];

// CEK APAKAH TABEL WISHLIST ADA, JIKA TIDAK MAKA SET 0
$wishlistCount = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'wishlist'");
if ($tableCheck->num_rows > 0) {
    $wishlistCount = $conn->query("SELECT COUNT(*) as total FROM wishlist WHERE id_user = $userId")->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Saya - zelfa store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; font-family: 'Inter', sans-serif; color: white; }
        
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
        .bg-blur { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: floatBg 20s infinite ease-in-out; }
        .blur-1 { background: #6366f1; top: -150px; right: -150px; }
        .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
        .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }
        @keyframes floatBg { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(50px,-50px) scale(1.1); } 66% { transform: translate(-30px,30px) scale(0.9); } }

        .navbar-custom { background: rgba(18,18,24,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 0; position: relative; z-index: 100; }
        
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 40px;
            padding: 8px 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: rgba(99,102,241,0.2);
            border-color: #6366f1;
            color: white;
            transform: translateX(-3px);
        }
        
        .profile-container {
            position: relative;
            z-index: 10;
            max-width: 1000px;
            margin: 40px auto 60px;
            padding: 0 20px;
        }
        
        .profile-card {
            background: rgba(18,18,24,0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            padding: 40px 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            position: relative;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 0 auto 20px;
            border: 3px solid white;
        }
        
        .camera-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #1a1a2e;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            border: 2px solid white;
        }
        
        .profile-name {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: rgba(255,255,255,0.8);
        }
        
        .profile-role {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            margin-top: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-tabs {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 0 20px;
        }
        
        .nav-tabs .nav-link {
            color: rgba(255,255,255,0.6);
            border: none;
            padding: 15px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #818cf8;
        }
        
        .nav-tabs .nav-link.active {
            color: #818cf8;
            background: transparent;
            border-bottom: 2px solid #6366f1;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            border-radius: 12px;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: #6366f1;
            color: white;
            box-shadow: none;
        }
        
        .form-label {
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 45px;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.6);
            transition: all 0.3s;
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: #818cf8;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4);
            color: white;
        }
        
        .btn-outline-custom {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 40px;
            padding: 10px;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-outline-custom:hover {
            background: rgba(99,102,241,0.2);
            border-color: #6366f1;
            color: #818cf8;
        }
        
        .alert-custom {
            border-radius: 12px;
            margin: 20px;
        }
        
        footer {
            background: rgba(18,18,24,0.95);
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            padding: 40px 0;
            margin-top: 60px;
            color: rgba(255,255,255,0.5);
        }
        
        @media (max-width: 768px) {
            .profile-container { margin: 30px auto; }
            .stats-grid { padding: 15px; gap: 10px; }
            .stat-number { font-size: 22px; }
            .tab-content { padding: 20px; }
            .navbar-custom .container { flex-direction: column; gap: 15px; }
            .logo-text { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <div class="logo-text">zelfa store</div>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>
    
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if (!empty($userData['foto_profil']) && file_exists('asset/profile/' . $userData['foto_profil'])): ?>
                        <img src="asset/profile/<?php echo $userData['foto_profil']; ?>" alt="Profile">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="camera-icon" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <h3 class="profile-name"><?php echo htmlspecialchars($userData['username']); ?></h3>
                <p class="profile-email"><?php echo htmlspecialchars($userData['email']); ?></p>
                <span class="profile-role">
                    <i class="fas fa-user"></i> <?php echo $userData['role'] == 'admin' ? 'Admin' : 'Member'; ?>
                </span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalOrders; ?></div>
                    <div class="stat-label">Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('d/m/Y', strtotime($userData['created_at'])); ?></div>
                    <div class="stat-label">Bergabung</div>
                </div>
            </div>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-custom" style="background: #22c55e20; border-color: #22c55e; color: #22c55e;">
                    <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-custom" style="background: #ef444420; border-color: #ef4444; color: #ef4444;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#editProfile">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#changePassword">
                        <i class="fas fa-lock"></i> Ganti Password
                    </button>
                </li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="editProfile">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary-custom">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
                
                <div class="tab-pane fade" id="changePassword">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('current_password', this)"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="new_password" id="new_password" required>
                                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('new_password', this)"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary-custom">
                            <i class="fas fa-key"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="profile-card mt-4">
            <div style="padding: 20px;">
                <h5 style="margin-bottom: 15px;"><i class="fas fa-link"></i> Menu Cepat</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="orders.php" class="btn btn-outline-custom w-100">
                            <i class="fas fa-shopping-bag"></i> Pesanan
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="cart.php" class="btn btn-outline-custom w-100">
                            <i class="fas fa-cart"></i> Keranjang
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="my_messages.php" class="btn btn-outline-custom w-100">
                            <i class="fas fa-envelope"></i> Pesan Saya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(18,18,24,0.98); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-white">Upload Foto Profil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="upload_photo" value="1">
                        <div class="text-center mb-3">
                            <?php if (!empty($userData['foto_profil']) && file_exists('asset/profile/' . $userData['foto_profil'])): ?>
                                <img src="asset/profile/<?php echo $userData['foto_profil']; ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" name="profile_photo" accept="image/*" required>
                        <small class="text-muted" style="color: rgba(255,255,255,0.5);">Format: JPG, PNG, GIF | Maks: 2MB</small>
                    </div>
                    <div class="modal-footer border-0">
                        <?php if (!empty($userData['foto_profil'])): ?>
                            <button type="submit" name="delete_photo" class="btn btn-danger" onclick="return confirm('Hapus foto?')">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn-primary-custom">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconElement) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            if (type === 'password') {
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>