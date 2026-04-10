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

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: gate/login/");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = null;

// Ambil data user
$stmt = $conn->prepare("SELECT id_user, username, email FROM users WHERE id_user = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Jika user tidak ditemukan di database
if (!$userData) {
    session_destroy();
    header("Location: gate/login/");
    exit();
}

// Ambil semua pesanan user - TAMPILKAN SEMUA STATUS (termasuk completed)
$orderQuery = "SELECT * FROM orders WHERE id_user = ? ORDER BY created_at DESC";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("i", $userId);
$orderStmt->execute();
$ordersResult = $orderStmt->get_result();

// Debug: Cek jumlah pesanan (bisa dihapus setelah jadi)
// error_log("User ID: " . $userId . " - Total orders: " . $ordersResult->num_rows);

$ordersArray = [];
while ($order = $ordersResult->fetch_assoc()) {
    $ordersArray[] = $order;
}
$orderStmt->close();

// Fungsi helper
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y H:i', strtotime($date));
}

function getOrderStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge-status status-pending"><i class="fas fa-clock me-1"></i> Menunggu Konfirmasi</span>';
        case 'pending_payment':
            return '<span class="badge-status status-pending-payment"><i class="fas fa-hourglass-half me-1"></i> Menunggu Pembayaran</span>';
        case 'processing':
            return '<span class="badge-status status-processing"><i class="fas fa-spinner fa-spin me-1"></i> Diproses</span>';
        case 'shipping':
            return '<span class="badge-status status-shipping"><i class="fas fa-truck me-1"></i> Dikirim</span>';
        case 'delivered':
            return '<span class="badge-status status-delivered"><i class="fas fa-check-circle me-1"></i> Selesai</span>';
        case 'completed':
            return '<span class="badge-status status-delivered"><i class="fas fa-check-circle me-1"></i> Selesai</span>';
        case 'cancelled':
            return '<span class="badge-status status-cancelled"><i class="fas fa-times-circle me-1"></i> Dibatalkan</span>';
        default:
            return '<span class="badge-status status-pending">' . $status . '</span>';
    }
}

function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'unpaid':
            return '<span class="badge-status status-unpaid"><i class="fas fa-exclamation-circle me-1"></i> Belum Dibayar</span>';
        case 'paid':
            return '<span class="badge-status status-paid"><i class="fas fa-check-circle me-1"></i> Sudah Dibayar</span>';
        case 'cod':
            return '<span class="badge-status status-cod"><i class="fas fa-money-bill-wave me-1"></i> Bayar di Tempat</span>';
        default:
            return '<span class="badge-status status-unpaid">' . $status . '</span>';
    }
}

// Hitung statistik
$totalOrders = count($ordersArray);
$pendingOrders = 0;
$completedOrders = 0;

foreach ($ordersArray as $order) {
    $status = $order['order_status'];
    if ($status == 'delivered' || $status == 'completed') {
        $completedOrders++;
    } elseif ($status == 'pending' || $status == 'pending_payment' || $status == 'processing' || $status == 'shipping') {
        $pendingOrders++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - zelfa store</title>
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
        .orders-container { position: relative; z-index: 10; max-width: 1200px; margin: 40px auto 60px; padding: 0 20px; }
        
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
        
        .page-header { margin-bottom: 30px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-title { flex: 1; }
        .page-title { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #ffffff, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 8px; }
        .page-subtitle { color: rgba(255,255,255,0.5); font-size: 14px; }
        
        .stats-row { display: flex; gap: 20px; margin-bottom: 35px; flex-wrap: wrap; }
        .stat-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; flex: 1; min-width: 150px; border: 1px solid rgba(255,255,255,0.05); text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(99,102,241,0.3); }
        .stat-number { font-size: 28px; font-weight: 700; color: white; }
        .stat-label { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 5px; }
        
        .order-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 20px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; transition: all 0.3s; }
        .order-card:hover { transform: translateY(-3px); border-color: rgba(99,102,241,0.3); }
        .order-header { padding: 18px 25px; background: rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .order-number { font-size: 16px; font-weight: 700; }
        .order-number i { color: #818cf8; margin-right: 8px; }
        .order-date { font-size: 12px; color: rgba(255,255,255,0.5); }
        .order-body { padding: 22px 25px; }
        .order-items { margin-bottom: 20px; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .order-item:last-child { border-bottom: none; }
        .order-total { display: flex; justify-content: space-between; padding-top: 15px; margin-top: 10px; border-top: 2px solid rgba(255,255,255,0.1); }
        .order-address { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 15px; margin-top: 15px; font-size: 13px; }
        .order-address i { color: #818cf8; margin-right: 8px; }
        
        .badge-status { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; }
        .status-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .status-pending-payment { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .status-processing { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
        .status-shipping { background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); }
        .status-delivered { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .status-cancelled { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .status-unpaid { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .status-paid { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .status-cod { background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.3); }
        
        .btn-detail { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; padding: 8px 20px; font-size: 12px; color: white; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-detail:hover { background: rgba(99,102,241,0.2); border-color: #6366f1; color: white; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: rgba(18,18,24,0.8); border-radius: 24px; }
        .empty-state i { font-size: 70px; color: rgba(255,255,255,0.1); margin-bottom: 20px; }
        .empty-state h4 { font-size: 22px; margin-bottom: 10px; }
        .empty-state p { color: rgba(255,255,255,0.5); margin-bottom: 25px; }
        
        .btn-primary-custom { background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; padding: 12px 28px; font-weight: 600; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary-custom:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); color: white; }
        
        footer { background: rgba(18,18,24,0.95); border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 60px; }
        
        @media (max-width: 768px) {
            .orders-container { margin-top: 30px; }
            .page-title { font-size: 24px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .stats-row { flex-direction: column; }
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

    <div class="orders-container">
        <div class="page-header">
            <div class="header-title">
                <h1 class="page-title"><i class="fas fa-shopping-bag me-2"></i> Pesanan Saya</h1>
                <p class="page-subtitle">Riwayat dan status pesanan Anda</p>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">Diproses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completedOrders; ?></div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>

        <?php if (empty($ordersArray)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h4>Belum Ada Pesanan</h4>
                <p>Anda belum melakukan pemesanan apapun.</p>
                <a href="books/" class="btn-primary-custom">
                    <i class="fas fa-book"></i> Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($ordersArray as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-number">
                        <i class="fas fa-receipt"></i> #<?php echo str_pad($order['id_order'], 8, '0', STR_PAD_LEFT); ?>
                        <span class="ms-2"><?php echo getOrderStatusBadge($order['order_status']); ?></span>
                        <span class="ms-2"><?php echo getPaymentStatusBadge($order['payment_status']); ?></span>
                    </div>
                    <div class="order-date">
                        <i class="fas fa-calendar-alt me-1"></i> <?php echo formatDate($order['created_at']); ?>
                    </div>
                </div>
                <div class="order-body">
                    <div class="order-items">
                        <?php
                        // Ambil detail item pesanan
                        $itemQuery = "SELECT oi.*, b.judul, b.penulis 
                                     FROM order_items oi 
                                     JOIN books b ON oi.id_buku = b.id_buku 
                                     WHERE oi.order_id = ?";
                        $itemStmt = $conn->prepare($itemQuery);
                        $itemStmt->bind_param("i", $order['id_order']);
                        $itemStmt->execute();
                        $items = $itemStmt->get_result();
                        
                        if ($items->num_rows > 0):
                            while ($item = $items->fetch_assoc()):
                        ?>
                        <div class="order-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['judul']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($item['penulis']); ?> x <?php echo $item['quantity']; ?></small>
                            </div>
                            <div class="fw-bold"><?php echo formatRupiah($item['price'] * $item['quantity']); ?></div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-info-circle"></i> Tidak ada item pesanan
                        </div>
                        <?php endif; 
                        $itemStmt->close();
                        ?>
                    </div>
                    
                    <div class="order-total">
                        <span>Total Pembayaran</span>
                        <span class="h5 text-primary mb-0"><?php echo formatRupiah($order['total_amount']); ?></span>
                    </div>
                    
                    <?php if (!empty($order['shipping_address'])): ?>
                    <div class="order-address">
                        <i class="fas fa-map-marker-alt"></i> <strong>Alamat Pengiriman:</strong><br>
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($order['notes'])): ?>
                    <div class="order-address mt-2" style="background: rgba(99,102,241,0.05);">
                        <i class="fas fa-sticky-note"></i> <strong>Catatan:</strong><br>
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['payment_method']) && $order['payment_method'] == 'transfer' && $order['payment_status'] == 'unpaid'): ?>
                    <div class="mt-3 text-center">
                        <button class="btn-detail" onclick="showPaymentInfo('<?php echo $order['id_order']; ?>')">
                            <i class="fas fa-credit-card me-1"></i> Informasi Pembayaran
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tombol Detail Pesanan -->
                    <div class="mt-3 text-end">
                        <a href="order_detail.php?id=<?php echo $order['id_order']; ?>" class="btn-detail">
                            <i class="fas fa-eye me-1"></i> Lihat Detail
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Informasi Pembayaran -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(18,18,24,0.98); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-university me-2"></i> Informasi Pembayaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-building-columns fa-3x text-primary"></i>
                    </div>
                    <div class="bg-dark p-3 rounded mb-3">
                        <strong>BCA</strong><br>
                        No. Rekening: 123 456 7890<br>
                        a.n: zelfa store
                    </div>
                    <div class="bg-dark p-3 rounded mb-3">
                        <strong>Mandiri</strong><br>
                        No. Rekening: 987 654 3210<br>
                        a.n: zelfa store
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-clock me-1"></i> Transfer sesuai total pembayaran dan konfirmasi pembayaran Anda di menu ini.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0" style="color: rgba(255,255,255,0.5);">&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
        </div>
    </footer>

    <script>
        function showPaymentInfo(orderId) {
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>