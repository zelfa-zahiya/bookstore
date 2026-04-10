<?php
// Mulai session di AWAL file
session_start();

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $db_connection_failed = true;
} else {
    $db_connection_failed = false;
}

// Cek status login
$isLoggedIn = false;
$userData = null;
$cartItemCount = 0;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    if (!$db_connection_failed) {
        $stmt = $conn->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $isLoggedIn = true;
            $userData = $row;
            
            $cartStmt = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) as total FROM cart WHERE id_user = ?");
            $cartStmt->bind_param("i", $userId);
            $cartStmt->execute();
            $cartRes = $cartStmt->get_result();
            if ($cartRow = $cartRes->fetch_assoc()) {
                $cartItemCount = (int)$cartRow['total'];
            }
            $cartStmt->close();
        }
        $stmt->close();
    }
}

// Ambil parameter pencarian dari URL
$searchContact = isset($_GET['search_contact']) ? trim($_GET['search_contact']) : '';

// Konten kontak untuk dicari
$contactContents = [
    ['title' => 'Alamat Kami', 'content' => 'Jl. Buku Indah No. 123, Jakarta Selatan, Indonesia'],
    ['title' => 'Telepon / WhatsApp', 'content' => '+62 812-3456-7890, +62 813-9876-5432'],
    ['title' => 'Email', 'content' => 'info@zelfastore.com, cs@zelfastore.com'],
    ['title' => 'Jam Operasional', 'content' => 'Senin - Jumat: 08:00 - 20:00, Sabtu - Minggu: 09:00 - 17:00'],
    ['title' => 'Layanan Customer Service', 'content' => 'CS tersedia 24/7 melalui WhatsApp dan Email'],
    ['title' => 'Media Sosial', 'content' => 'Instagram: @zelfastore, Facebook: zelfa store, Twitter: @zelfastore'],
    ['title' => 'Maps / Lokasi', 'content' => 'Berada di pusat kota Jakarta Selatan, dekat dengan stasiun MRT']
];

// Fungsi untuk membersihkan teks sebelum pencarian
function cleanSearchTextContact($text) {
    return strtolower($text);
}

// Filter konten berdasarkan pencarian
$searchResultsContact = [];
$hasSearchContact = !empty($searchContact);

if ($hasSearchContact) {
    $searchLower = cleanSearchTextContact($searchContact);
    $searchTerms = explode(' ', $searchLower);
    $searchTerms = array_filter($searchTerms);
    
    foreach ($contactContents as $content) {
        $titleLower = cleanSearchTextContact($content['title']);
        $contentLower = cleanSearchTextContact($content['content']);
        
        $matchFound = false;
        
        // Pencarian kata kunci lengkap
        if (strpos($titleLower, $searchLower) !== false || strpos($contentLower, $searchLower) !== false) {
            $matchFound = true;
        }
        
        // Pencarian per kata
        if (!$matchFound && !empty($searchTerms)) {
            $matchCount = 0;
            foreach ($searchTerms as $term) {
                if (strlen($term) > 2) {
                    if (strpos($titleLower, $term) !== false || strpos($contentLower, $term) !== false) {
                        $matchCount++;
                    }
                }
            }
            if ($matchCount >= ceil(count($searchTerms) / 2)) {
                $matchFound = true;
            }
        }
        
        if ($matchFound) {
            $exists = false;
            foreach ($searchResultsContact as $existing) {
                if ($existing['title'] === $content['title']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $searchResultsContact[] = $content;
            }
        }
    }
}

// Ambil pesan error/success dari session
$error = isset($_SESSION['contact_errors']) ? $_SESSION['contact_errors'] : null;
$success = isset($_SESSION['contact_success']) ? $_SESSION['contact_success'] : null;

// Hapus session setelah ditampilkan
unset($_SESSION['contact_errors']);
unset($_SESSION['contact_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami - zelfa store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            overflow-x: hidden;
        }

        .avatar-dropdown-toggle span:not(.avatar-circle) {
            color: white !important;
        }

        .avatar-dropdown-toggle span:last-child {
            color: white !important;
        }

        .avatar-dropdown-toggle span {
            color: white;
        }

        .avatar-dropdown-toggle span.avatar-circle {
            color: white;
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
            pointer-events: none;
        }

        .bg-blur {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            animation: floatBg 20s infinite ease-in-out;
        }

        .blur-1 { background: #6366f1; top: -150px; right: -150px; }
        .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
        .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }

        @keyframes floatBg {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }

        /* Top Bar */
        .top-bar {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 10px 0;
            font-size: 13px;
            position: relative;
            z-index: 100;
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(18, 18, 24, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 0;
            position: relative;
            z-index: 100;
            transition: all 0.3s;
        }

        .navbar-custom.scrolled {
            padding: 8px 0;
            background: rgba(18, 18, 24, 0.98);
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .logo-text:hover {
            transform: scale(1.02);
            letter-spacing: -0.3px;
        }

        @media (max-width: 768px) {
            .logo-text {
                font-size: 22px;
            }
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 500;
            margin: 0 8px;
            transition: all 0.3s;
            position: relative;
        }

        .nav-link:hover {
            color: #818cf8 !important;
        }

        .nav-link.active {
            color: #818cf8 !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            transition: width 0.3s;
        }

        .nav-link:hover::after {
            width: 30px;
        }

        /* Search Container */
        .search-container {
            position: relative;
            width: 280px;
        }

        .search-input {
            width: 100%;
            padding: 10px 18px 10px 42px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.08);
            width: 320px;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .icon-nav-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            text-decoration: none;
            position: relative;
        }

        .icon-nav-link:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ec4899, #f472b6);
            color: white;
            font-size: 10px;
            font-weight: 600;
            border-radius: 50%;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
        }

        .avatar-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 5px 12px 5px 5px;
            transition: all 0.3s;
        }

        .avatar-dropdown-toggle:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .dropdown-menu-custom {
            background: rgba(18, 18, 24, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            margin-top: 10px;
            min-width: 220px;
        }

        .dropdown-item-custom {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .dropdown-item-custom:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            padding-left: 25px;
        }

        .btn-auth {
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-login {
            background: transparent;
            border: 1px solid rgba(99, 102, 241, 0.5);
            color: #818cf8;
        }

        .btn-login:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 80px 0 60px;
            margin-bottom: 60px;
            position: relative;
            z-index: 10;
        }

        .page-header h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
        }

        /* Contact Container */
        .contact-container {
            position: relative;
            z-index: 10;
        }

        /* Search Results */
        .search-results {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-result-item {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-title {
            font-size: 20px;
            font-weight: 700;
            color: #a78bfa;
            margin-bottom: 10px;
        }

        .search-result-content {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            font-size: 14px;
        }

        .highlight {
            background: rgba(99, 102, 241, 0.3);
            color: #ffffff;
            padding: 0 2px;
            border-radius: 4px;
        }

        .btn-reset {
            background: rgba(255,255,255,0.05);
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: rgba(99,102,241,0.2);
            color: #a78bfa;
        }

        /* Info Card */
        .info-card {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: center;
            height: 100%;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.05);
        }

        .info-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            transition: all 0.3s;
        }

        .info-card:hover .info-icon {
            transform: scale(1.1);
        }

        .info-card h6 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .info-card p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-bottom: 0;
        }

        /* Form Container */
        .form-container {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            margin-top: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .form-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            background: rgba(30, 30, 40, 0.9) !important;
            border: 1px solid rgba(99, 102, 241, 0.3) !important;
            color: #ffffff !important;
            padding: 14px 18px !important;
            border-radius: 14px !important;
            font-size: 14px !important;
            width: 100% !important;
            transition: all 0.3s !important;
        }

        .form-control:focus {
            background: rgba(40, 40, 55, 0.95) !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2) !important;
            outline: none !important;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-send {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 14px 30px;
            font-weight: 600;
            font-size: 15px;
            color: white;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-send:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.5);
        }

        /* Alert */
        .alert-custom {
            padding: 15px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border-left: 4px solid #10b981;
            color: #6ee7b7;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-left: 4px solid #ef4444;
            color: #fca5a5;
        }

        /* Footer */
        footer {
            background: rgba(18, 18, 24, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px 0 30px;
            margin-top: 80px;
            position: relative;
            z-index: 10;
        }

        .footer-brand {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 16px;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .footer-links a:hover {
            color: #818cf8;
            transform: translateX(5px);
        }

        .footer-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icons a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 40px; }
            .search-container { width: 100%; }
            .search-input:focus { width: 100%; }
            .navbar-nav { margin: 20px 0; }
            .form-container { padding: 30px 20px; }
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 32px; }
            .page-header p { font-size: 14px; }
            .form-title { font-size: 20px; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1a1a2e;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <!-- Top Bar - Dikosongkan -->
    <div class="top-bar d-none d-lg-block">
        <div class="container">
            <div class="d-flex justify-content-end">
                <!-- Top Bar dikosongkan -->
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="logo-text">zelfa store</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="books/">Koleksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- SEARCH UNTUK HALAMAN KONTAK -->
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="contactSearchInput" placeholder="Cari di halaman Kontak..." value="<?php echo htmlspecialchars($searchContact); ?>" onkeypress="handleContactSearch(event)">
                    </div>
                    
                    <?php if ($isLoggedIn && $userData): ?>
                        <a href="cart.php" class="icon-nav-link position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge"><?php echo $cartItemCount; ?></span>
                        </a>
                        <div class="dropdown">
                            <button class="avatar-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="avatar-circle"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                                <li><a class="dropdown-item dropdown-item-custom" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="my_messages.php"><i class="fas fa-envelope me-2"></i> My Message</a></li>
                                <?php if($userData['role'] == 'admin'): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="gate/login/" class="btn-auth btn-login">Masuk</a>
                        <a href="gate/register/" class="btn-auth btn-register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1>📞 Hubungi Kami</h1>
            <p>Kami siap membantu Anda 24/7</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container contact-container">
        
        <?php if ($hasSearchContact): ?>
            <!-- Hasil Pencarian Kontak -->
            <div class="search-results">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="form-title" style="font-size: 24px; margin-bottom: 0;">🔍 Hasil Pencarian</h3>
                    <a href="contact.php" class="btn-reset">
                        <i class="fas fa-times me-1"></i> Hapus
                    </a>
                </div>
                <p class="text-white-50 mb-4">Menampilkan hasil untuk: <strong>"<?php echo htmlspecialchars($searchContact); ?>"</strong></p>
                
                <?php if (empty($searchResultsContact)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-white-50 mb-3"></i>
                        <p class="text-white-50">Tidak ditemukan konten yang sesuai dengan "<strong><?php echo htmlspecialchars($searchContact); ?></strong>"</p>
                        <p class="text-white-50 small">Coba kata kunci lain seperti: "telepon", "email", "alamat", "whatsapp", dll.</p>
                        <div class="mt-3">
                            <a href="contact.php" class="btn-send" style="padding: 10px 24px; text-decoration: none; width: auto;">Lihat Semua Kontak</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($searchResultsContact as $result): ?>
                    <div class="search-result-item">
                        <div class="search-result-title">
                            <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($result['title']); ?>
                        </div>
                        <div class="search-result-content">
                            <?php 
                                $content = $result['content'];
                                $searchLower = strtolower($searchContact);
                                $contentLower = strtolower($content);
                                if (strpos($contentLower, $searchLower) !== false) {
                                    $highlighted = preg_replace('/(' . preg_quote($searchContact, '/') . ')/i', '<span class="highlight">$1</span>', $content);
                                    echo $highlighted;
                                } else {
                                    echo htmlspecialchars($content);
                                }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Info Cards (selalu tampil, tapi bisa disembunyikan jika user mau? Biarkan tetap tampil) -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="info-card" id="alamat">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h6>Alamat Kami</h6>
                    <p>Jl. Buku Indah No. 123<br>Jakarta Selatan, Indonesia</p>
                </div>
            </div>
            <div class="col-md-4" id="telepon">
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                    <h6>Telepon / WhatsApp</h6>
                    <p>+62 812-3456-7890<br>+62 813-9876-5432</p>
                </div>
            </div>
            <div class="col-md-4" id="email">
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <h6>Email</h6>
                    <p>info@zelfastore.com<br>cs@zelfastore.com</p>
                </div>
            </div>
        </div>

        <!-- Info Tambahan -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-clock"></i></div>
                    <h6>Jam Operasional</h6>
                    <p>Senin - Jumat: 08:00 - 20:00<br>Sabtu - Minggu: 09:00 - 17:00</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-headset"></i></div>
                    <h6>Layanan Customer Service</h6>
                    <p>CS tersedia 24/7 melalui WhatsApp dan Email</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
        <div class="alert-custom alert-success">
            <i class="fas fa-check-circle fa-lg"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error && is_array($error)): ?>
            <?php foreach ($error as $err): ?>
            <div class="alert-custom alert-error">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span><?php echo $err; ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Contact Form -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="form-container">
                    <h3 class="form-title">✉️ Kirim Pesan</h3>
                    
                    <form method="POST" action="send_contact.php">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama Anda" 
                                   value="<?php echo $isLoggedIn ? htmlspecialchars($userData['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="contoh: nama@email.com" 
                                   value="<?php echo $isLoggedIn ? htmlspecialchars($userData['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Subjek</label>
                            <input type="text" name="subject" class="form-control" placeholder="Subjek pesan Anda" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Pesan</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Tulis pesan Anda di sini..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Kirim Pesan
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p style="color: rgba(255,255,255,0.4); font-size: 12px;">
                            <i class="fas fa-clock me-1"></i> Kami akan membalas pesan Anda dalam 1x24 jam
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">zelfa store</div>
                    <p class="footer-text">Toko buku online terpercaya yang menyediakan berbagai koleksi buku berkualitas untuk menemani perjalanan literasimu.</p>
                    <div class="social-icons">
                    </div>
                </div>
                <div class="col-md-2 mb-4 offset-md-1">
                    <div class="footer-title">Tentang</div>
                    <div class="footer-links">
                        <a href="about_us.php">Tentang Kami</a>
                        <a href="contact.php">Kontak</a>
                        <a href="#">Blog</a>
                        <a href="#">Karir</a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="footer-title">Layanan</div>
                    <div class="footer-links">
                        <a href="cart.php">Keranjang</a>
                        <a href="orders.php">Pesanan</a>
                        <a href="wishlist.php">Wishlist</a>
                        <a href="my_messages.php">Pesan Saya</a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="footer-title">Kontak</div>
                    <div class="footer-links">
                        <a href="#"><i class="fas fa-map-marker-alt me-2"></i> Jakarta, Indonesia</a>
                        <a href="#"><i class="fas fa-phone me-2"></i> +62 812-3456-7890</a>
                        <a href="#"><i class="fas fa-envelope me-2"></i> info@zelfastore.com</a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary mt-3">
            <div class="text-center">
                <p class="footer-text small mb-0">&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
            </div>
        </div>
    </footer>

    <script>
        // Fungsi untuk handle search di halaman kontak
        function handleContactSearch(event) {
            if (event.key === 'Enter') {
                const searchTerm = event.target.value.trim();
                if (searchTerm !== '') {
                    window.location.href = 'contact.php?search_contact=' + encodeURIComponent(searchTerm);
                } else {
                    window.location.href = 'contact.php';
                }
            }
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>