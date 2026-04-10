<?php
// Mulai session di AWAL file, sebelum HTML apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
if (file_exists('db_connection.php')) {
    require_once 'db_connection.php';
} else {
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
}

// Cek status login dari SESSION
$isLoggedIn = false;
$userData = null;
$cartItemCount = 0;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Jika koneksi database berhasil, ambil data user
    if (isset($conn) && $conn && !$conn->connect_error) {
        $stmt = $conn->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $isLoggedIn = true;
            $userData = $row;
            
            // Ambil jumlah cart
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
    } else {
        // Jika koneksi gagal tapi session ada, tetap anggap logged in untuk demo
        $isLoggedIn = true;
        $userData = ['id_user' => $userId, 'username' => $_SESSION['username'] ?? 'User', 'email' => '', 'role' => 'user'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a3a5f;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --gradient-start: #1a3a5f;
            --gradient-end: #2c5a8c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
        }

        /* Navbar Styles */
        .navbar-custom {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar-top {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 8px 0;
            font-size: 13px;
        }

        .navbar-top a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-left: 28px;
            font-weight: 500;
        }

        .navbar-top a:first-child {
            margin-left: 0;
        }

        .navbar-top a:hover {
            color: white;
            transform: translateY(-2px);
        }

        .topbar-right-links {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }

        .nav-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
            margin-right: 50px;
            transition: transform 0.3s ease;
        }

        .nav-logo:hover {
            transform: scale(1.05);
        }

        .navbar-container {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 0;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            gap: 0;
        }

        /* Categories Dropdown */
        .categories-dropdown {
            border: none;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .categories-dropdown:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .dropdown-menu-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            padding: 10px 0;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item-custom {
            padding: 12px 25px;
            color: #4a5568;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-item-custom:hover {
            background: linear-gradient(135deg, rgba(26, 58, 95, 0.1) 0%, rgba(44, 90, 140, 0.1) 100%);
            color: var(--primary-color);
            padding-left: 30px;
        }

        /* Search Container */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 550px;
        }

        .search-input {
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            padding: 12px 20px 12px 50px;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 58, 95, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            z-index: 10;
        }

        /* Navigation Icons */
        .icon-nav-link {
            color: #4a5568;
            font-size: 1.2rem;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .icon-nav-link:hover {
            color: var(--primary-color);
            background: rgba(26, 58, 95, 0.1);
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            font-size: 10px;
            border-radius: 50%;
            padding: 2px 6px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        /* Avatar Dropdown */
        .avatar-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            background: none;
            border: none;
            color: #4a5568;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .avatar-dropdown-toggle:hover {
            background: rgba(26, 58, 95, 0.1);
            color: var(--primary-color);
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        /* Auth Buttons */
        .btn-auth {
            border-radius: 50px;
            padding: 8px 24px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-login {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-login:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border: none;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 58, 95, 0.3);
        }

        /* Mobile Navigation */
        .navbar-toggler {
            border: none;
            padding: 10px;
            border-radius: 12px;
            background: rgba(26, 58, 95, 0.1);
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .navbar-left {
                width: 100%;
                justify-content: space-between;
                margin-bottom: 10px;
            }
            
            .navbar-center {
                order: 3;
                width: 100%;
                margin: 10px 0;
            }
            
            .navbar-right {
                display: none;
            }
            
            .navbar-top {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .nav-logo {
                height: 35px;
                margin-right: 20px;
            }
            
            .categories-dropdown {
                padding: 8px 15px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="navbar-top d-none d-lg-block">
        <div class="container">
            <div class="topbar-right-links">
                <a href="contact.php"><i class="fas fa-headphones-alt me-2"></i> Bantuan 24/7</a>
                <a href="store-location.php"><i class="fas fa-map-marker-alt me-2"></i> Lokasi Toko</a>
                <a href="promo.php"><i class="fas fa-tags me-2"></i> Promo Spesial</a>
                <a href="faq.php"><i class="fas fa-question-circle me-2"></i> FAQ</a>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container">
            <div class="navbar-container">
                <div class="navbar-left">
                    <a class="navbar-brand" href="index.php">
                        <img src="asset/logo/logo.jpeg" alt="halaman_senja" class="nav-logo"
                            onerror="this.onerror=null; this.src='https://placehold.co/160x50?text=HALAMAN+SENJA';">
                    </a>
                    <div class="dropdown d-none d-lg-block">
                        <button class="categories-dropdown dropdown-toggle" type="button" id="categoriesDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bars me-2"></i>Kategori Buku
                        </button>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=fiksi">
                                <i class="fas fa-book-open me-2"></i>Fiksi
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=non-fiksi">
                                <i class="fas fa-graduation-cap me-2"></i>Non-Fiksi
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=komik">
                                <i class="fas fa-comic-book me-2"></i>Komik & Manga
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=pendidikan">
                                <i class="fas fa-chalkboard-user me-2"></i>Pendidikan
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=anak">
                                <i class="fas fa-child me-2"></i>Buku Anak
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=bisnis">
                                <i class="fas fa-chart-line me-2"></i>Bisnis & Ekonomi
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=novel">
                                <i class="fas fa-heart me-2"></i>Novel
                            </a></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php?kategori=motivasi">
                                <i class="fas fa-rocket me-2"></i>Motivasi
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item dropdown-item-custom" href="books.php">
                                <i class="fas fa-eye me-2"></i>Semua Kategori
                            </a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="navbar-center">
                    <div class="search-container">
                        <div class="search-icon"><i class="fas fa-search"></i></div>
                        <input type="text" class="search-input" placeholder="Cari judul buku, penulis, atau penerbit..." id="searchInput">
                    </div>
                </div>
                
                <div class="navbar-right d-none d-lg-flex align-items-center" id="navbarAuthSection">
                    <?php if ($isLoggedIn && $userData): ?>
                        <!-- TAMPILAN USER YANG SUDAH LOGIN -->
                        <div class="d-flex align-items-center">
                            <a href="cart.php" class="icon-nav-link position-relative" title="Keranjang">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge" id="cartCount"><?php echo $cartItemCount; ?></span>
                            </a>
                            <div class="dropdown">
                                <button class="avatar-dropdown-toggle dropdown-toggle" type="button" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="avatar-circle">
                                        <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
                                    </span>
                                    <span id="usernameDisplay"><?php echo htmlspecialchars($userData['username']); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end" aria-labelledby="accountDropdown">
                                    <li><a class="dropdown-item dropdown-item-custom" href="profile.php">
                                        <i class="fas fa-user me-2"></i>Profil Saya
                                    </a></li>
                                    <li><a class="dropdown-item dropdown-item-custom" href="orders.php">
                                        <i class="fas fa-shopping-bag me-2"></i>Pesanan Saya
                                    </a></li>
                                    <?php if($userData['role'] == 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item dropdown-item-custom" href="admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                    </a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item dropdown-item-custom" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Keluar
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- TAMPILAN USER BELUM LOGIN -->
                        <div class="d-flex align-items-center gap-2">
                            <a href="gate/login/" class="btn btn-auth btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </a>
                            <a href="gate/register/" class="btn btn-auth btn-register">
                                <i class="fas fa-user-plus me-2"></i>Daftar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button class="navbar-toggler ms-2 d-lg-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto d-lg-none mt-3">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom active" href="index.php">
                            <i class="fas fa-home me-2"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="books.php">
                            <i class="fas fa-book me-2"></i>Katalog Buku
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="about.php">
                            <i class="fas fa-info-circle me-2"></i>Tentang Kami
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="contact.php">
                            <i class="fas fa-envelope me-2"></i>Kontak
                        </a>
                    </li>
                    <li><hr class="my-2"></li>
                    <?php if ($isLoggedIn && $userData): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart me-2"></i>Keranjang 
                                <?php if($cartItemCount > 0): ?>
                                    <span class="badge bg-danger"><?php echo $cartItemCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profil Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>Pesanan Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Keluar
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="gate/login/">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gate/register/">
                                <i class="fas fa-user-plus me-2"></i>Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        window.location.href = `books.php?search=${encodeURIComponent(query)}`;
                    }
                }
            });
        }
        
        // Update cart badge dynamically
        function updateCartBadge() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge && data.count !== undefined) {
                        cartBadge.textContent = data.count;
                        if (data.count === 0) {
                            cartBadge.style.display = 'none';
                        } else {
                            cartBadge.style.display = 'inline-block';
                        }
                    }
                })
                .catch(error => console.log('Error updating cart badge:', error));
        }
        
        // Update cart badge every 30 seconds
        if (document.getElementById('cartCount')) {
            setInterval(updateCartBadge, 30000);
        }
        
        // Add active class to current nav item
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link-custom, .dropdown-item-custom').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href !== '#!') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
</body>
</html>