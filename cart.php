<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$isLoggedIn = false;
$userData = null;
$cartItems = [];
$total = 0;
$cartItemCount = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id_user, username, email, phone, address, role FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $isLoggedIn = true;
        $userData = $row;
        
        // Ambil item cart
        $cartQuery = "SELECT c.*, b.judul, b.penulis, b.harga, b.gambar, b.stok 
                      FROM cart c 
                      JOIN books b ON c.id_buku = b.id_buku 
                      WHERE c.id_user = ?";
        $cartStmt = $conn->prepare($cartQuery);
        $cartStmt->bind_param("i", $userId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();
        while ($row = $cartResult->fetch_assoc()) {
            $cartItems[] = $row;
            $total += $row['harga'] * $row['jumlah'];
            $cartItemCount += $row['jumlah'];
        }
        $cartStmt->close();
    }
    $stmt->close();
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
    <title>Keranjang Belanja | zelfa store</title>
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

        /* Cart Container */
        .cart-container {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }

        .cart-card {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 3px;
        }

        /* Cart Items */
        .cart-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cart-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .book-image {
            width: 70px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
        }

        .book-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .book-author {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }

        .book-price {
            font-weight: 700;
            color: #a78bfa;
        }

        .quantity-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .quantity-btn:hover {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            transform: translateY(-2px);
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .quantity-number {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }

        .remove-btn {
            background: rgba(239, 68, 68, 0.15);
            border: none;
            color: #f87171;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 13px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .remove-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
        }

        /* Summary Card */
        .summary-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            position: sticky;
            top: 100px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #a78bfa;
        }

        .total-price {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 14px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
            display: block;
            text-align: center;
            text-decoration: none;
        }

        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-continue {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 40px;
            padding: 12px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            width: 100%;
            transition: all 0.3s;
            display: block;
            text-align: center;
            text-decoration: none;
            margin-top: 12px;
        }

        .btn-continue:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 50px;
            color: rgba(255, 255, 255, 0.3);
        }

        .empty-cart h4 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .btn-explore {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 12px 32px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-explore:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
            color: white;
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

        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 40px; }
            .section-title { font-size: 24px; }
            .cart-card { padding: 25px; }
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 32px; }
            .page-header { padding: 60px 0 40px; }
            .cart-card { padding: 20px; }
            .cart-item .row > div { margin-bottom: 10px; text-align: center; }
            .summary-card { margin-top: 20px; position: relative; top: 0; }
            .section-title::after { width: 40px; }
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
                    <li class="nav-item"><a class="nav-link" href="contact.php">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
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
            <h1>🛒 Keranjang Belanja</h1>
            <p>Lihat dan kelola buku pilihanmu</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="cart-container">
        <div class="cart-card">
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Keranjang Kosong</h4>
                    <p style="color: rgba(255, 255, 255, 0.5);">Yuk, mulai belanja buku favoritmu!</p>
                    <a href="books/" class="btn-explore mt-3">
                        <i class="fas fa-book-open me-2"></i> Jelajahi Buku
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <h2 class="section-title">Daftar Belanja</h2>
                        
                        <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" id="cart-item-<?php echo $item['id_cart']; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-2 col-4">
                                    <img src="asset/books_cover/<?php echo $item['gambar'] ?? 'default.jpg'; ?>" 
                                         class="book-image"
                                         onerror="this.src='https://placehold.co/70x90?text=No+Image'">
                                </div>
                                <div class="col-md-4 col-8">
                                    <div class="book-title"><?php echo htmlspecialchars($item['judul']); ?></div>
                                    <div class="book-author"><?php echo htmlspecialchars($item['penulis']); ?></div>
                                </div>
                                <div class="col-md-2 col-6">
                                    <div class="book-price" id="price-<?php echo $item['id_cart']; ?>">
                                        <?php echo formatRupiah($item['harga']); ?>
                                    </div>
                                </div>
                                <div class="col-md-2 col-6">
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="quantity-btn" onclick="updateCart(<?php echo $item['id_cart']; ?>, <?php echo $item['stok']; ?>, 'minus')">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span id="qty-<?php echo $item['id_cart']; ?>" class="quantity-number">
                                            <?php echo $item['jumlah']; ?>
                                        </span>
                                        <button class="quantity-btn" onclick="updateCart(<?php echo $item['id_cart']; ?>, <?php echo $item['stok']; ?>, 'plus')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 col-12 text-md-end text-center mt-3 mt-md-0">
                                    <button class="remove-btn" onclick="removeItem(<?php echo $item['id_cart']; ?>)">
                                        <i class="fas fa-trash me-1"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="summary-card">
                            <div class="summary-title">Ringkasan Belanja</div>
                            <hr class="border-secondary">
                            <div class="d-flex justify-content-between mb-2">
                                <span style="color: rgba(255, 255, 255, 0.6);">Total Item:</span>
                                <span id="totalItems" class="fw-bold"><?php echo count($cartItems); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span style="color: rgba(255, 255, 255, 0.6);">Total Harga:</span>
                                <span class="total-price" id="totalPrice"><?php echo formatRupiah($total); ?></span>
                            </div>
                            <hr class="border-secondary">
                            <a href="checkout.php" class="btn-checkout">
                                Lanjut ke Pembayaran <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                            <a href="books/" class="btn-continue">
                                <i class="fas fa-book me-2"></i> Lanjut Belanja
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">zelfa store</div>
                    <p class="footer-text">Toko buku online terpercaya yang menyediakan berbagai koleksi buku berkualitas untuk menemani perjalanan literasimu.</p>
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
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        async function updateCart(cartId, stok, action) {
            const qtySpan = document.getElementById(`qty-${cartId}`);
            let currentQty = parseInt(qtySpan.innerText);
            let newQty = currentQty;
            
            if (action === 'plus') {
                newQty = currentQty + 1;
                if (newQty > stok) {
                    alert(`Stok tidak mencukupi! Stok tersedia: ${stok}`);
                    return;
                }
            } else if (action === 'minus') {
                newQty = currentQty - 1;
                if (newQty < 1) {
                    if (confirm('Hapus buku dari keranjang?')) {
                        removeItem(cartId);
                    }
                    return;
                }
            }
            
            qtySpan.innerText = newQty;
            
            const buttons = document.querySelectorAll(`#cart-item-${cartId} .quantity-btn`);
            buttons.forEach(btn => btn.disabled = true);
            
            try {
                const formData = new URLSearchParams();
                formData.append('cart_id', cartId);
                formData.append('jumlah', newQty);
                
                const response = await fetch('ajax/update_cart_quantity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const priceSpan = document.getElementById(`price-${cartId}`);
                    if (priceSpan && data.item_total) {
                        priceSpan.innerText = formatRupiah(data.item_total);
                    }
                    
                    if (data.cart_total !== undefined) {
                        document.getElementById('totalPrice').innerHTML = formatRupiah(data.cart_total);
                        document.getElementById('totalItems').innerText = data.total_items;
                    }
                } else {
                    qtySpan.innerText = currentQty;
                    alert(data.message || 'Gagal mengupdate keranjang');
                }
            } catch (error) {
                console.error('Error:', error);
                qtySpan.innerText = currentQty;
                alert('Terjadi kesalahan, silakan coba lagi');
            } finally {
                buttons.forEach(btn => btn.disabled = false);
            }
        }
        
        async function removeItem(cartId) {
            if (!confirm('Yakin ingin menghapus buku ini dari keranjang?')) return;
            
            const cartItem = document.getElementById(`cart-item-${cartId}`);
            cartItem.style.opacity = '0.5';
            
            try {
                const formData = new URLSearchParams();
                formData.append('cart_id', cartId);
                
                const response = await fetch('ajax/remove_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });
                
                const data = await response.json();
                
                if (data.success) {
                    cartItem.style.display = 'none';
                    
                    if (data.cart_total !== undefined) {
                        document.getElementById('totalPrice').innerHTML = formatRupiah(data.cart_total);
                        document.getElementById('totalItems').innerText = data.total_items;
                    }
                    
                    if (data.total_items === 0) {
                        location.reload();
                    }
                } else {
                    cartItem.style.opacity = '';
                    alert(data.message || 'Gagal menghapus item');
                }
            } catch (error) {
                console.error('Error:', error);
                cartItem.style.opacity = '';
                alert('Terjadi kesalahan, silakan coba lagi');
            }
        }
        
        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>