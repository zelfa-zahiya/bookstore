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

if (!isset($_SESSION['user_id'])) {
    header("Location: gate/login/");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = null;
$cartItems = [];
$total = 0;

// Ambil data user
$stmt = $conn->prepare("SELECT id_user, username, email FROM users WHERE id_user = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Ambil item cart
$cartQuery = "SELECT c.*, b.judul, b.penulis, b.harga, b.stok 
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
}
$cartStmt->close();

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// Proses checkout
$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validasi
    if (empty($address)) $errors[] = "Alamat wajib diisi";
    if (empty($phone)) $errors[] = "No. Telepon wajib diisi";
    if (empty($payment_method)) $errors[] = "Pilih metode pembayaran";
    
    if (empty($errors)) {
        // Update data user
        $updateUser = $conn->prepare("UPDATE users SET phone = ?, address = ? WHERE id_user = ?");
        $updateUser->bind_param("ssi", $phone, $address, $userId);
        $updateUser->execute();
        $updateUser->close();
        
        // Set status berdasarkan metode pembayaran
        $order_status = 'pending';
        $payment_status = ($payment_method == 'transfer') ? 'unpaid' : 'cod';
        
        // Simpan order
        $orderStmt = $conn->prepare("INSERT INTO orders (id_user, total_amount, payment_method, order_status, payment_status, shipping_address, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $orderStmt->bind_param("idsssss", $userId, $total, $payment_method, $order_status, $payment_status, $address, $notes);
        
        if ($orderStmt->execute()) {
            $orderId = $conn->insert_id;
            $orderStmt->close();
            
            // Simpan order items & kurangi stok
            foreach ($cartItems as $item) {
                $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, id_buku, quantity, price) VALUES (?, ?, ?, ?)");
                $itemStmt->bind_param("iiid", $orderId, $item['id_buku'], $item['jumlah'], $item['harga']);
                $itemStmt->execute();
                $itemStmt->close();
                
                // Kurangi stok
                $newStok = $item['stok'] - $item['jumlah'];
                $updateStok = $conn->prepare("UPDATE books SET stok = ? WHERE id_buku = ?");
                $updateStok->bind_param("ii", $newStok, $item['id_buku']);
                $updateStok->execute();
                $updateStok->close();
            }
            
            // Kosongkan cart
            $conn->query("DELETE FROM cart WHERE id_user = $userId");
            
            // Redirect ke halaman sukses
            header("Location: payment_success.php?order_id=$orderId&method=" . urlencode($payment_method));
            exit();
        } else {
            $errors[] = "Gagal memproses pesanan: " . $conn->error;
        }
    }
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
    <title>Checkout - halaman_senja</title>
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
        .checkout-container { position: relative; z-index: 10; max-width: 1200px; margin: 100px auto 60px; padding: 0 20px; }
        .checkout-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 24px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); }
        
        .form-control {
            background: rgba(30, 30, 40, 0.9) !important;
            border: 1px solid rgba(99, 102, 241, 0.3) !important;
            color: #ffffff !important;
            padding: 12px 16px !important;
            border-radius: 12px !important;
            width: 100% !important;
        }
        .form-control:focus {
            background: rgba(40, 40, 55, 0.95) !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.2) !important;
            outline: none !important;
        }
        .form-control:disabled {
            background: rgba(30, 30, 40, 0.6) !important;
            color: rgba(255,255,255,0.7) !important;
        }
        .form-label { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.8); margin-bottom: 8px; }
        
        .payment-card { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .payment-card.selected { border-color: #6366f1; background: rgba(99,102,241,0.1); }
        .payment-card:hover { background: rgba(99,102,241,0.05); }
        .summary-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; position: sticky; top: 20px; }
        .btn-place-order { background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; padding: 14px; font-weight: 600; color: white; width: 100%; transition: all 0.3s; }
        .btn-place-order:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); }
        .bank-info { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 15px; margin-top: 15px; display: none; }
        .bank-info.show { display: block; }
        .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; }
        footer { background: rgba(18,18,24,0.95); border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 60px; }
        
        @media (max-width: 768px) { .checkout-container { margin-top: 80px; } }
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
            <a class="navbar-brand" href="index.php">
                <img src="asset/logo/logo.jpeg" alt="halaman_senja" class="nav-logo" onerror="this.src='https://placehold.co/140x45?text=BOOKS'">
            </a>
            <a href="cart.php" class="btn btn-outline-light btn-sm">← Kembali ke Keranjang</a>
        </div>
    </nav>

    <div class="checkout-container">
        <div class="checkout-card">
            <h2 class="mb-4"><i class="fas fa-credit-card me-2"></i> Checkout</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <div><i class="fas fa-exclamation-circle me-2"></i> <?php echo $err; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-7">
                    <form method="POST" id="checkoutForm">
                        <h5 class="mb-3"><i class="fas fa-user me-2"></i> Informasi Pengiriman</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" required placeholder="0812-3456-7890">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="3" required placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Tolong dibungkus rapih, atau request waktu pengiriman"></textarea>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4"><i class="fas fa-money-bill-wave me-2"></i> Metode Pembayaran</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-card" data-method="cod">
                                    <div class="d-flex align-items-center">
                                        <div class="payment-icon me-3">
                                            <i class="fas fa-truck fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Cash on Delivery (COD)</h6>
                                            <small class="text-muted">Bayar di tempat saat buku diterima</small>
                                        </div>
                                        <div class="ms-auto">
                                            <i class="far fa-circle" style="font-size: 20px; color: #6366f1;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-card" data-method="transfer">
                                    <div class="d-flex align-items-center">
                                        <div class="payment-icon me-3">
                                            <i class="fas fa-university fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Transfer Bank</h6>
                                            <small class="text-muted">Bayar via BCA, Mandiri, BNI, BRI</small>
                                        </div>
                                        <div class="ms-auto">
                                            <i class="far fa-circle" style="font-size: 20px; color: #6366f1;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="bankInfo" class="bank-info">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i> Informasi Rekening Bank</h6>
                            <div class="row small">
                                <div class="col-md-6 mb-2">
                                    <div class="bg-dark p-2 rounded">
                                        <strong>BCA</strong><br>
                                        No. Rek: 123 456 7890<br>
                                        a.n: halaman_senja
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="bg-dark p-2 rounded">
                                        <strong>Mandiri</strong><br>
                                        No. Rek: 987 654 3210<br>
                                        a.n: halaman_senja
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-2 small mb-0">
                                <i class="fas fa-clock me-1"></i> Transfer harus dilakukan dalam waktu 1x24 jam.
                            </div>
                        </div>
                        
                        <input type="hidden" name="payment_method" id="payment_method" required>
                    </form>
                </div>
                
                <div class="col-lg-5">
                    <div class="summary-card">
                        <h5 class="mb-3"><i class="fas fa-shopping-bag me-2"></i> Ringkasan Pesanan</h5>
                        <hr class="border-secondary">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo htmlspecialchars($item['judul']); ?> <small class="text-muted">x<?php echo $item['jumlah']; ?></small></span>
                            <span><?php echo formatRupiah($item['harga'] * $item['jumlah']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <hr class="border-secondary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?php echo formatRupiah($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim</span>
                            <span class="text-success">GRATIS</span>
                        </div>
                        <hr class="border-secondary">
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total Pembayaran</strong>
                            <strong class="text-primary h5"><?php echo formatRupiah($total); ?></strong>
                        </div>
                        <button type="submit" form="checkoutForm" class="btn-place-order">
                            <i class="fas fa-check-circle me-2"></i> Buat Pesanan
                        </button>
                        <p class="text-muted small text-center mt-3 mb-0">
                            <i class="fas fa-lock me-1"></i> Data Anda aman dan terenkripsi
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer><div class="container text-center"><p class="mb-0">&copy; 2025 halaman senja - Teman Membaca Setiamu</p></div></footer>

    <script>
        const paymentCards = document.querySelectorAll('.payment-card');
        const paymentMethodInput = document.getElementById('payment_method');
        const bankInfo = document.getElementById('bankInfo');
        
        paymentCards.forEach(card => {
            card.addEventListener('click', function() {
                paymentCards.forEach(c => {
                    c.classList.remove('selected');
                    const icon = c.querySelector('.fa-circle, .fa-check-circle');
                    if (icon) {
                        icon.classList.remove('fa-check-circle');
                        icon.classList.add('fa-circle');
                    }
                });
                
                this.classList.add('selected');
                const method = this.dataset.method;
                paymentMethodInput.value = method;
                
                const selectedIcon = this.querySelector('.fa-circle');
                if (selectedIcon) {
                    selectedIcon.classList.remove('fa-circle');
                    selectedIcon.classList.add('fa-check-circle');
                }
                
                if (method === 'transfer') {
                    bankInfo.classList.add('show');
                } else {
                    bankInfo.classList.remove('show');
                }
            });
        });
        
        const form = document.getElementById('checkoutForm');
        const submitBtn = document.querySelector('.btn-place-order');
        
        if (form) {
            form.addEventListener('submit', function() {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
                submitBtn.disabled = true;
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>