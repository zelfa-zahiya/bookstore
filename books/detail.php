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

// Ambil ID buku dari URL
$id_buku = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_buku <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data buku
$query = "SELECT b.*, c.nama_kategori 
          FROM books b 
          LEFT JOIN categories c ON b.id_kategori = c.id_kategori 
          WHERE b.id_buku = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_buku);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    header("Location: index.php");
    exit();
}
$stmt->close();

// Cek status login
$isLoggedIn = false;
$userData = null;
$cartItemCount = 0;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
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

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getBookImage($gambar) {
    if (empty($gambar) || $gambar == 'default.jpg') {
        return 'https://placehold.co/400x500?text=No+Image';
    }
    return '../asset/books_cover/' . $gambar;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['judul']); ?> | halaman_senja</title>
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
        .nav-logo { height: 45px; width: auto; }
        .nav-link { color: rgba(255,255,255,0.7) !important; font-weight: 500; margin: 0 8px; transition: all 0.3s; }
        .nav-link:hover { color: #818cf8 !important; }
        
        .search-container { position: relative; width: 280px; }
        .search-input { width: 100%; padding: 10px 18px 10px 42px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; color: white; font-size: 14px; }
        .search-input:focus { outline: none; border-color: #6366f1; width: 320px; }
        .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.4); }
        
        .icon-nav-link { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255,255,255,0.05); transition: all 0.3s; color: rgba(255,255,255,0.7); font-size: 18px; text-decoration: none; position: relative; }
        .icon-nav-link:hover { background: rgba(99,102,241,0.15); color: #818cf8; }
        .cart-badge { position: absolute; top: -5px; right: -5px; background: linear-gradient(135deg, #ec4899, #f472b6); color: white; font-size: 10px; border-radius: 50%; padding: 2px 6px; }
        
        .avatar-dropdown-toggle { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; padding: 5px 12px 5px 5px; }
        .avatar-circle { width: 34px; height: 34px; background: linear-gradient(135deg, #6366f1, #ec4899); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .dropdown-menu-custom { background: rgba(18,18,24,0.98); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; }
        .dropdown-item-custom { color: rgba(255,255,255,0.8); padding: 10px 20px; transition: all 0.3s; font-size: 14px; }
        .dropdown-item-custom:hover { background: rgba(99,102,241,0.15); color: #818cf8; }
        
        .btn-auth { padding: 8px 24px; border-radius: 40px; font-weight: 500; font-size: 13px; text-decoration: none; }
        .btn-login { background: transparent; border: 1px solid rgba(99,102,241,0.5); color: #818cf8; }
        .btn-login:hover { background: #6366f1; color: white; }
        .btn-register { background: linear-gradient(135deg, #6366f1, #ec4899); color: white; }
        
        /* Detail Page Styles */
        .detail-container { position: relative; z-index: 10; max-width: 1200px; margin: 100px auto 60px; padding: 0 20px; }
        .detail-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 24px; padding: 40px; border: 1px solid rgba(255,255,255,0.05); }
        .book-cover { border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.5); }
        .book-cover img { width: 100%; height: auto; transition: transform 0.3s; }
        .book-cover img:hover { transform: scale(1.02); }
        .book-title { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #ffffff, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 15px; }
        .book-meta { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .meta-item { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.6); font-size: 14px; }
        .meta-item i { color: #818cf8; width: 20px; }
        .book-price { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #f472b6, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; margin: 20px 0; }
        .book-stock { display: inline-block; padding: 8px 20px; border-radius: 40px; font-size: 14px; font-weight: 600; margin-bottom: 25px; }
        .stock-available { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .stock-out { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .book-description { color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 30px; }
        .btn-add-cart-detail { background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; padding: 14px 36px; font-weight: 600; font-size: 16px; color: white; transition: all 0.3s; }
        .btn-add-cart-detail:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); }
        .btn-back { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; padding: 12px 28px; color: white; text-decoration: none; transition: all 0.3s; }
        .btn-back:hover { background: rgba(255,255,255,0.15); color: white; }
        footer { background: rgba(18,18,24,0.95); border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 80px; }
        
        @media (max-width: 768px) {
            .detail-container { margin-top: 80px; }
            .book-title { font-size: 24px; }
            .book-price { font-size: 28px; }
            .detail-card { padding: 25px; }
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
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../asset/logo/logo.jpeg" alt="halaman_senja" class="nav-logo" onerror="this.src='https://placehold.co/140x45?text=BOOKS'">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php">Koleksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="../about_us.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="../contact.php">Kontak</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Cari buku..." id="searchInput">
                    </div>
                    <?php if ($isLoggedIn && $userData): ?>
                        <a href="../wishlist.php" class="icon-nav-link"><i class="far fa-heart"></i></a>
                        <a href="../cart.php" class="icon-nav-link position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge"><?php echo $cartItemCount; ?></span>
                        </a>
                        <div class="dropdown">
                            <button class="avatar-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="avatar-circle"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                                <li><a class="dropdown-item dropdown-item-custom" href="../profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="../orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="../my_messages.php"><i class="fas fa-envelope me-2"></i> Pesan Saya</a></li>
                                <?php if($userData['role'] == 'admin'): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="../admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="../gate/login/" class="btn-auth btn-login">Masuk</a>
                        <a href="../gate/register/" class="btn-auth btn-register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Detail Buku -->
    <div class="detail-container">
        <div class="detail-card">
            <div class="row">
                <div class="col-md-5 mb-4 mb-md-0">
                    <div class="book-cover">
                        <img src="<?php echo getBookImage($book['gambar']); ?>" alt="<?php echo htmlspecialchars($book['judul']); ?>">
                    </div>
                </div>
                <div class="col-md-7">
                    <h1 class="book-title"><?php echo htmlspecialchars($book['judul']); ?></h1>
                    
                    <div class="book-meta">
                        <div class="meta-item">
                            <i class="fas fa-user-edit"></i>
                            <span><?php echo htmlspecialchars($book['penulis']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo htmlspecialchars($book['penerbit'] ?? '-'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
                        </div>
                    </div>
                    
                    <div class="book-price">
                        <?php echo formatRupiah($book['harga']); ?>
                    </div>
                    
                    <div class="book-stock <?php echo $book['stok'] > 0 ? 'stock-available' : 'stock-out'; ?>">
                        <i class="fas <?php echo $book['stok'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                        <?php echo $book['stok'] > 0 ? 'Tersedia ' . $book['stok'] . ' eksemplar' : 'Stok Habis'; ?>
                    </div>
                    
                    <div class="book-description">
                        <strong>Deskripsi Buku:</strong><br>
                        <?php echo !empty($book['deskripsi']) ? nl2br(htmlspecialchars($book['deskripsi'])) : 'Belum ada deskripsi untuk buku ini.'; ?>
                    </div>
                    
                    <div class="d-flex gap-3 flex-wrap">
                        <?php if ($isLoggedIn): ?>
                            <button class="btn-add-cart-detail add-to-cart" data-id="<?php echo $book['id_buku']; ?>" data-stok="<?php echo $book['stok']; ?>" <?php echo $book['stok'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart me-2"></i> 
                                <?php echo $book['stok'] > 0 ? 'Tambah ke Keranjang' : 'Stok Habis'; ?>
                            </button>
                        <?php else: ?>
                            <a href="../gate/login/" class="btn-add-cart-detail text-decoration-none text-center" style="display: inline-block;">
                                <i class="fas fa-sign-in-alt me-2"></i> Login untuk Membeli
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Koleksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-0" style="color: rgba(255,255,255,0.5);">&copy; 2025 halaman senja - Teman Membaca Setiamu</p>
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
                        window.location.href = 'index.php?search=' + encodeURIComponent(searchTerm);
                    }
                }
            });
        }

        // Add to cart
        const addToCartBtn = document.querySelector('.add-to-cart');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', async function() {
                const bookId = this.dataset.id;
                const stok = parseInt(this.dataset.stok);
                
                if (stok <= 0) {
                    alert('Maaf, stok buku ini habis.');
                    return;
                }
                
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menambahkan...';
                this.disabled = true;
                
                try {
                    const response = await fetch('../ajax/add_to_cart.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id_buku=' + bookId + '&jumlah=1'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge) cartBadge.textContent = data.cart_count;
                        alert('✅ Buku berhasil ditambahkan ke keranjang!');
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
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>