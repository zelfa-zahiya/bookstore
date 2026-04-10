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

// Ambil buku terbaru
$featuredBooks = [];
if (!$db_connection_failed) {
    $query = "SELECT b.*, c.nama_kategori FROM books b 
              LEFT JOIN categories c ON b.id_kategori = c.id_kategori 
              ORDER BY b.created_at DESC LIMIT 8";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $featuredBooks[] = $row;
        }
    }
}

// Jika tidak ada buku di database, tampilkan sample books
if (empty($featuredBooks)) {
    $featuredBooks = [
        ['id_buku' => 1, 'judul' => 'Laut Bercerita', 'penulis' => 'Leila S. Chudori', 'nama_kategori' => 'Novel', 'harga' => 89000, 'stok' => 12, 'gambar' => 'laut_bercerita.jpg'],
        ['id_buku' => 2, 'judul' => 'Pulang - Sebuah Kisah Tentang Keluarga dan Perjuangan', 'penulis' => 'Tere Liye', 'nama_kategori' => 'Novel', 'harga' => 95000, 'stok' => 8, 'gambar' => 'pulang.jpg'],
        ['id_buku' => 3, 'judul' => 'Filosofi Teras', 'penulis' => 'Henry Manampiring', 'nama_kategori' => 'Non-Fiksi', 'harga' => 79000, 'stok' => 15, 'gambar' => 'filosofi_teras.jpg'],
        ['id_buku' => 4, 'judul' => 'Nanti Kita Cerita Tentang Hari Ini', 'penulis' => 'Marchella FP', 'nama_kategori' => 'Fiksi', 'harga' => 85000, 'stok' => 5, 'gambar' => 'nkcthi.jpg'],
        ['id_buku' => 5, 'judul' => 'Atomic Habits', 'penulis' => 'James Clear', 'nama_kategori' => 'Non-Fiksi', 'harga' => 125000, 'stok' => 20, 'gambar' => 'atomic_habits.jpg'],
        ['id_buku' => 6, 'judul' => 'Bumi', 'penulis' => 'Tere Liye', 'nama_kategori' => 'Novel', 'harga' => 89000, 'stok' => 3, 'gambar' => 'bumi.jpg'],
        ['id_buku' => 7, 'judul' => 'One Piece Vol.105', 'penulis' => 'Eiichiro Oda', 'nama_kategori' => 'Komik', 'harga' => 45000, 'stok' => 10, 'gambar' => 'one_piece.jpg'],
        ['id_buku' => 8, 'judul' => 'Sebuah Seni untuk Bersikap Bodo Amat', 'penulis' => 'Mark Manson', 'nama_kategori' => 'Non-Fiksi', 'harga' => 75000, 'stok' => 7, 'gambar' => 'seni_bodo_amat.jpg'],
    ];
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>beranda | zelfa store</title>
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

        /* Ubah warna nama user di dropdown menjadi putih */
        .avatar-dropdown-toggle span:not(.avatar-circle) {
            color: white !important;
        }

        /* Atau cara yang lebih spesifik */
        .avatar-dropdown-toggle span:last-child {
            color: white !important;
        }

        /* Alternatif: langsung target ke span yang berisi nama user */
        .avatar-dropdown-toggle span {
            color: white;
        }

        .avatar-dropdown-toggle span.avatar-circle {
            color: white; /* Untuk huruf inisial di avatar juga putih */
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

        /* Top Bar - Dikosongkan */
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

        /* LOGO TEKS GRADIENT */
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
            font-size: 14px;
        }

        /* Icon Nav Links */
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

        /* Avatar Dropdown */
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

        /* Auth Buttons */
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

        /* Hero Section */
        .hero-section {
            padding: 100px 0 80px;
            position: relative;
            z-index: 10;
        }

        #judul {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .hero-content p {
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 32px;
            max-width: 90%;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 14px 36px;
            font-weight: 600;
            font-size: 15px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.5);
            color: white;
        }

        .hero-img-container {
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.5);
        }

        .hero-img {
            width: 100%;
            height: auto;
            transition: transform 0.5s;
        }

        .hero-img-container:hover .hero-img {
            transform: scale(1.05);
        }

        /* Section Title */
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 12px;
        }

        .section-subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        /* Book Card */
        .book-card {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s;
            border: 1px solid rgba(255, 255, 255, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-10px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .book-img-container {
            position: relative;
            padding-top: 130%;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }

        .book-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .book-card:hover .book-img {
            transform: scale(1.08);
        }

        .book-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-category {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: #a78bfa;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            background: rgba(99, 102, 241, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            width: fit-content;
        }

        .book-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: white;
            min-height: 44px;
        }

        .book-author {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
            min-height: 36px;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }

        .book-author i {
            flex-shrink: 0;
        }

        .book-price {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #f472b6, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            min-height: 40px;
        }

        .book-stock {
            font-size: 11px;
            margin-bottom: 16px;
            padding: 4px 0;
            min-height: 30px;
        }

        .book-stock.in-stock {
            color: #22c55e;
        }

        .book-stock.out-stock {
            color: #ef4444;
        }

        .book-button-wrapper {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-add-cart {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 500;
            color: white;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            text-align: center;
            display: block;
            text-decoration: none;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            color: white;
        }

        .btn-add-cart:disabled {
            background: rgba(255, 255, 255, 0.03);
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
            border-radius: 30px;
            padding: 60px;
            margin: 80px 0;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cta-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .cta-text {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
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
            #judul { font-size: 42px; }
            .hero-content p { max-width: 100%; }
            .hero-section { padding: 60px 0; }
            .section-title { font-size: 28px; }
            .cta-section { padding: 40px 20px; }
            .navbar-nav { margin: 20px 0; }
            .search-container { width: 100%; margin: 15px 0; }
            .search-input:focus { width: 100%; }
        }

        @media (max-width: 768px) {
            #judul { font-size: 32px; }
            .hero-section { text-align: center; }
            .hero-content p { text-align: center; }
            .btn-primary-custom { margin: 0 auto; }
            .section-title { font-size: 24px; }
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

        ::selection {
            background: #6366f1;
            color: white;
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
            <a class="navbar-brand" href="/index.php">
                <span class="logo-text">zelfa store</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="/index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="/books/">Koleksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="/about_us.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="/contact.php">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Cari buku..." id="searchInput">
                    </div>
                    
                    <?php if ($isLoggedIn && $userData): ?>
                        </a>
                        <div class="dropdown">
                            <button class="avatar-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="avatar-circle"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                                <li><a class="dropdown-item dropdown-item-custom" href="/profile.php"><i class="fas fa-shopping-bag me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="/orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="/my_messages.php"><i class="fas fa-envelope me-2"></i> My Message</a></li>
                                <?php if($userData['role'] == 'admin'): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="/admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="/gate/login/" class="btn-auth btn-login">Masuk</a>
                        <a href="/gate/register/" class="btn-auth btn-register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <section class="hero-section">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 id="judul">SELAMAT DATANG DI<br>ZELFA STORE</h1>
                        <p>Mau membeli buku favoritmu atau mencari bacaan seru? semuanya ada di sini. Jelajahi koleksi kami dan temukan buku yang menemani harimu.</p>
                        <a href="/books/" class="btn-primary-custom">Jelajahi Koleksi <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-img-container">
                        <img class="hero-img" src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=900&h=600&fit=crop" 
                            alt="zelfa_store"
                            onerror="this.src='https://picsum.photos/id/104/900/600'">
                    </div>
                </div>
            </div>
        </section>

        <!-- Buku Terbaru -->
        <section>
            <div class="section-header">
                <h2 class="section-title">✨ Koleksi Terbaru</h2>
                <p class="section-subtitle">Temukan buku-buku terbaru dari berbagai kategori</p>
            </div>
            <div class="row g-4">
                <?php foreach (array_slice($featuredBooks, 0, 8) as $book): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="book-card">
                        <div class="book-img-container">
                            <img class="book-img" src="/asset/books_cover/<?php echo $book['gambar'] ?? 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['judul']); ?>" 
                                 onerror="this.src='https://placehold.co/300x390?text=No+Image'">
                        </div>
                        <div class="book-info">
                            <span class="book-category"><?php echo htmlspecialchars($book['nama_kategori'] ?? 'Buku'); ?></span>
                            <div class="book-title"><?php echo htmlspecialchars($book['judul']); ?></div>
                            <div class="book-author"><i class="fas fa-user-edit me-1"></i> <?php echo htmlspecialchars($book['penulis']); ?></div>
                            <div class="book-price"><?php echo formatRupiah($book['harga']); ?></div>
                            <div class="book-stock <?php echo ($book['stok'] ?? 0) > 0 ? 'in-stock' : 'out-stock'; ?>">
                                <i class="fas <?php echo ($book['stok'] ?? 0) > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                <?php echo ($book['stok'] ?? 0) > 0 ? 'Stok: ' . $book['stok'] : 'Stok Habis'; ?>
                            </div>
                            <div class="book-button-wrapper">
                                <?php if ($isLoggedIn): ?>
                                    <button class="btn-add-cart add-to-cart" 
                                            data-id="<?php echo $book['id_buku']; ?>"
                                            data-stok="<?php echo $book['stok'] ?? 0; ?>"
                                            <?php echo ($book['stok'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-shopping-cart me-1"></i> 
                                        <?php echo ($book['stok'] ?? 0) > 0 ? 'Tambah ke Keranjang' : 'Stok Habis'; ?>
                                    </button>
                                <?php else: ?>
                                    <a href="/gate/login/" class="btn-add-cart text-center text-decoration-none">
                                        <i class="fas fa-sign-in-alt me-1"></i> Login untuk Beli
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- CTA Section -->
        <div class="cta-section">
            <h3 class="cta-title">📖 Temukan Buku Impianmu</h3>
            <p class="cta-text">Dapatkan berbagai koleksi buku terbaik dengan harga spesial</p>
            <a href="/books/" class="btn-primary-custom">Lihat Semua Buku <i class="fas fa-arrow-right"></i></a>
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
                        <a href="/about_us.php">Tentang Kami</a>
                        <a href="/contact.php">Kontak</a>
                        <a href="#">Blog</a>
                        <a href="#">Karir</a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="footer-title">Layanan</div>
                    <div class="footer-links">
                        <a href="/cart.php">Keranjang</a>
                        <a href="/orders.php">Pesanan</a>
                        <a href="/wishlist.php">Wishlist</a>
                        <a href="/my_messages.php">Pesan Saya</a>
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
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = this.value.trim();
                    if (searchTerm) {
                        window.location.href = '/books/?search=' + encodeURIComponent(searchTerm);
                    }
                }
            });
        }

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const bookId = this.dataset.id;
                const stok = parseInt(this.dataset.stok);
                
                if (stok <= 0) {
                    alert('❌ Maaf, stok buku ini habis.');
                    return;
                }
                
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
                this.disabled = true;
                
                try {
                    const response = await fetch('/ajax/add_to_cart.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id_buku=' + bookId + '&jumlah=1'
                    });
                    const data = await response.json();
                    if (data.success) {
                        const badge = document.querySelector('.cart-badge');
                        if (badge) {
                            badge.textContent = data.cart_count;
                            badge.style.transform = 'scale(1.2)';
                            setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
                        }
                        alert('✅ ' + (data.message || 'Buku ditambahkan ke keranjang!'));
                    } else {
                        alert('❌ ' + (data.message || 'Gagal menambahkan ke keranjang'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Terjadi kesalahan, silakan coba lagi.');
                } finally {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            });
        });

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