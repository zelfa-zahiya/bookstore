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

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$payment_method = isset($_GET['method']) ? $_GET['method'] : '';

if (!$order_id) {
    header("Location: index.php");
    exit();
}

// Ambil detail order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id_order = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: index.php");
    exit();
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
    <title>Pembayaran Berhasil - halaman_senja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; font-family: 'Inter', sans-serif; color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
        .bg-blur { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: floatBg 20s infinite ease-in-out; }
        .blur-1 { background: #6366f1; top: -150px; right: -150px; }
        .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
        .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }
        @keyframes floatBg { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(50px,-50px) scale(1.1); } 66% { transform: translate(-30px,30px) scale(0.9); } }

        .success-container { position: relative; z-index: 10; max-width: 500px; margin: 20px; }
        .success-card { background: rgba(18,18,24,0.95); backdrop-filter: blur(20px); border-radius: 32px; padding: 50px 40px; text-align: center; border: 1px solid rgba(255,255,255,0.1); }
        .success-icon { width: 90px; height: 90px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; font-size: 45px; box-shadow: 0 0 30px rgba(16,185,129,0.3); }
        .success-title { font-size: 28px; font-weight: 700; margin-bottom: 10px; background: linear-gradient(135deg, #ffffff, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .order-info { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; margin: 25px 0; text-align: left; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .info-label { color: rgba(255,255,255,0.6); font-size: 13px; }
        .info-value { font-weight: 600; color: white; }
        .btn-group { display: flex; gap: 15px; justify-content: center; margin-top: 20px; }
        .btn-primary-custom { background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; padding: 12px 28px; font-weight: 600; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary-custom:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); color: white; }
        .btn-secondary-custom { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; padding: 12px 28px; font-weight: 600; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-secondary-custom:hover { background: rgba(255,255,255,0.15); color: white; }
        @media (max-width: 480px) { .success-card { padding: 30px 20px; } .btn-group { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Pesanan Berhasil!</h1>
            <p class="text-muted">Terima kasih telah berbelanja di halaman_senja</p>
            
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">No. Pesanan</span>
                    <span class="info-value">#<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Pembayaran</span>
                    <span class="info-value"><?php echo formatRupiah($order['total_amount']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode Pembayaran</span>
                    <span class="info-value">
                        <?php if ($payment_method == 'cod'): ?>
                            <i class="fas fa-truck me-1"></i> Cash on Delivery (COD)
                        <?php else: ?>
                            <i class="fas fa-university me-1"></i> Transfer Bank
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value text-success">
                        <?php if ($payment_method == 'cod'): ?>
                            <i class="fas fa-clock me-1"></i> Menunggu Konfirmasi
                        <?php else: ?>
                            <i class="fas fa-hourglass-half me-1"></i> Menunggu Pembayaran
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($payment_method == 'transfer'): ?>
            <div class="alert alert-info" style="background: rgba(99,102,241,0.1); border: 1px solid #6366f1; border-radius: 16px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi Pembayaran Transfer:</strong><br>
                <small>Silakan transfer ke rekening BCA 1234567890 a.n halaman_senja<br>
                Upload bukti transfer di halaman pesanan Anda.</small>
            </div>
            <?php else: ?>
            <div class="alert alert-success" style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; border-radius: 16px;">
                <i class="fas fa-truck me-2"></i>
                <strong>Pesanan akan segera diproses!</strong><br>
                <small>Pesanan Anda akan segera kami proses dan kirim ke alamat Anda.</small>
            </div>
            <?php endif; ?>
            
            <div class="btn-group">
                <a href="orders.php" class="btn-primary-custom">
                    <i class="fas fa-shopping-bag"></i> Lihat Pesanan Saya
                </a>
                <a href="index.php" class="btn-secondary-custom">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>