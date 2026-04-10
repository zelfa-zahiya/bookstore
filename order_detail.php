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

// Ambil ID order dari parameter URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId == 0) {
    header("Location: orders.php");
    exit();
}

// Ambil data order dan pastikan order milik user yang login
$orderQuery = "SELECT * FROM orders WHERE id_order = ? AND id_user = ?";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("ii", $orderId, $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();
$orderStmt->close();

// Jika order tidak ditemukan atau bukan milik user
if (!$order) {
    header("Location: orders.php");
    exit();
}

// Ambil detail item pesanan
$itemQuery = "SELECT oi.*, b.judul, b.penulis, b.gambar, b.id_buku 
              FROM order_items oi 
              JOIN books b ON oi.id_buku = b.id_buku 
              WHERE oi.order_id = ?";
$itemStmt = $conn->prepare($itemQuery);
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result();
$itemStmt->close();

// Ambil data user
$userQuery = "SELECT username, email, no_telepon, alamat FROM users WHERE id_user = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

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

function getPaymentMethod($method) {
    switch ($method) {
        case 'transfer':
            return '<i class="fas fa-university me-2"></i> Transfer Bank';
        case 'cod':
            return '<i class="fas fa-money-bill-wave me-2"></i> Cash On Delivery (COD)';
        default:
            return $method;
    }
}

// Fungsi untuk mendapatkan status step progress
function getProgressStep($status) {
    $steps = [
        'pending' => 0,
        'pending_payment' => 1,
        'processing' => 2,
        'shipping' => 3,
        'delivered' => 4,
        'completed' => 4
    ];
    return isset($steps[$status]) ? $steps[$status] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo str_pad($order['id_order'], 8, '0', STR_PAD_LEFT); ?> - zelfa store</title>
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
        .detail-container { position: relative; z-index: 10; max-width: 1200px; margin: 40px auto 60px; padding: 0 20px; }
        
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
        
        .page-header { margin-bottom: 30px; margin-top: 20px; }
        .page-title { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #ffffff, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 8px; }
        
        .order-info-card, .items-card, .summary-card, .address-card {
            background: rgba(18,18,24,0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .card-header {
            padding: 18px 25px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-weight: 700;
            font-size: 18px;
        }
        
        .card-header i {
            color: #818cf8;
            margin-right: 10px;
        }
        
        .card-body {
            padding: 22px 25px;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .info-label {
            width: 180px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
        }
        
        .info-value {
            flex: 1;
            color: white;
        }
        
        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .status-pending-payment { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .status-processing { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
        .status-shipping { background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); }
        .status-delivered { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .status-cancelled { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .status-unpaid { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .status-paid { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .status-cod { background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.3); }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-img {
            width: 70px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .item-detail {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-author {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        
        .summary-total {
            border-top: 2px solid rgba(255,255,255,0.1);
            padding-top: 15px;
            margin-top: 10px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4);
            color: white;
        }
        
        .btn-outline-custom {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-outline-custom:hover {
            background: rgba(99,102,241,0.2);
            border-color: #6366f1;
            color: white;
        }
        
        .progress-step {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        
        .progress-step::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255,255,255,0.1);
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            background: rgba(18,18,24,0.9);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 14px;
            color: rgba(255,255,255,0.5);
        }
        
        .step.active .step-icon {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            color: white;
        }
        
        .step.completed .step-icon {
            background: #10b981;
            border-color: transparent;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        
        .step.active .step-label {
            color: white;
            font-weight: 600;
        }
        
        footer { background: rgba(18,18,24,0.95); border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 60px; }
        
        /* Style untuk print / invoice */
        @media print {
            .bg-animation, .navbar-custom, footer, .btn-back, .btn-primary-custom, .btn-outline-custom {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 20px !important;
            }
            .detail-container {
                margin: 0 !important;
                padding: 0 !important;
            }
            .order-info-card, .items-card, .summary-card, .address-card {
                background: white !important;
                color: black !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            .card-header {
                background: #f5f5f5 !important;
                color: black !important;
                border-bottom: 1px solid #ddd !important;
            }
            .info-label {
                color: #666 !important;
            }
            .info-value, .item-title, .item-author, .summary-row {
                color: black !important;
            }
            .badge-status {
                border: 1px solid #ddd !important;
                background: #f5f5f5 !important;
                color: #333 !important;
            }
            .item-img {
                border: 1px solid #ddd;
            }
            .page-title {
                background: none !important;
                color: #333 !important;
                -webkit-background-clip: unset !important;
            }
            .step-icon {
                background: #f5f5f5 !important;
                border-color: #ddd !important;
                color: #666 !important;
            }
            .step.completed .step-icon {
                background: #10b981 !important;
                color: white !important;
            }
            .step.active .step-icon {
                background: #6366f1 !important;
                color: white !important;
            }
        }
        
        @media (max-width: 768px) {
            .info-row { flex-direction: column; }
            .info-label { width: 100%; margin-bottom: 5px; }
            .progress-step { flex-wrap: wrap; gap: 15px; }
            .progress-step::before { display: none; }
            .step { min-width: 80px; }
            .navbar-custom .container { flex-direction: column; gap: 15px; }
            .logo-text { margin-bottom: 10px; }
            .item-row { flex-direction: column; text-align: center; }
            .item-img { margin: 0 auto 10px auto; }
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
            <a href="orders.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
            </a>
        </div>
    </nav>

    <div class="detail-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-receipt me-2"></i> 
                Detail Pesanan #<?php echo str_pad($order['id_order'], 8, '0', STR_PAD_LEFT); ?>
            </h1>
            <p class="text-muted">Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <!-- Progress Status - PERBAIKAN untuk status completed -->
        <div class="order-info-card">
            <div class="card-body">
                <?php
                $statusOrder = $order['order_status'];
                $currentStep = getProgressStep($statusOrder);
                
                $steps = [
                    0 => ['label' => 'Pesanan Dibuat', 'icon' => 'fa-file-invoice'],
                    1 => ['label' => 'Menunggu Bayar', 'icon' => 'fa-hourglass-half'],
                    2 => ['label' => 'Diproses', 'icon' => 'fa-box'],
                    3 => ['label' => 'Dikirim', 'icon' => 'fa-truck'],
                    4 => ['label' => 'Selesai', 'icon' => 'fa-check-circle']
                ];
                
                if ($statusOrder == 'cancelled') {
                    echo '<div class="text-center text-danger py-3">
                            <i class="fas fa-times-circle fa-3x mb-2"></i>
                            <h5>Pesanan Dibatalkan</h5>
                          </div>';
                } else {
                ?>
                <div class="progress-step">
                    <?php for ($i = 0; $i <= 4; $i++): ?>
                    <div class="step <?php 
                        if ($i < $currentStep) echo 'completed';
                        if ($i == $currentStep) echo 'active';
                    ?>">
                        <div class="step-icon">
                            <?php if ($i < $currentStep): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas <?php echo $steps[$i]['icon']; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?php echo $steps[$i]['label']; ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Informasi Pesanan -->
        <div class="order-info-card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Informasi Pesanan
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-label">Nomor Pesanan</div>
                    <div class="info-value">#<?php echo str_pad($order['id_order'], 8, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal Pemesanan</div>
                    <div class="info-value"><?php echo formatDate($order['created_at']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status Pesanan</div>
                    <div class="info-value"><?php echo getOrderStatusBadge($order['order_status']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status Pembayaran</div>
                    <div class="info-value"><?php echo getPaymentStatusBadge($order['payment_status']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Metode Pembayaran</div>
                    <div class="info-value"><?php echo getPaymentMethod($order['payment_method']); ?></div>
                </div>
            </div>
        </div>

        <!-- Detail Item Pesanan - PERBAIKAN foto buku -->
        <div class="items-card">
            <div class="card-header">
                <i class="fas fa-shopping-bag"></i> Detail Pesanan
            </div>
            <div class="card-body">
                <?php if ($items->num_rows > 0): ?>
                    <?php 
                    $items->data_seek(0);
                    while ($item = $items->fetch_assoc()): 
                        // Cari path gambar yang benar
                        $gambarPath = '';
                        if (!empty($item['gambar'])) {
                            // Cek beberapa kemungkinan path
                            if (file_exists("uploads/books/" . $item['gambar'])) {
                                $gambarPath = "uploads/books/" . $item['gambar'];
                            } elseif (file_exists("uploads/" . $item['gambar'])) {
                                $gambarPath = "uploads/" . $item['gambar'];
                            } elseif (file_exists("../uploads/books/" . $item['gambar'])) {
                                $gambarPath = "../uploads/books/" . $item['gambar'];
                            } elseif (file_exists("../uploads/" . $item['gambar'])) {
                                $gambarPath = "../uploads/" . $item['gambar'];
                            } else {
                                $gambarPath = '';
                            }
                        }
                    ?>
                    <div class="item-row">
                        <div class="item-img">
                            <?php if (!empty($gambarPath) && file_exists($gambarPath)): ?>
                                <img src="<?php echo $gambarPath; ?>" style="width: 70px; height: 90px; object-fit: cover; border-radius: 8px;" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                            <?php else: ?>
                                <img src="assets/img/book-placeholder.jpg" style="width: 70px; height: 90px; object-fit: cover; border-radius: 8px;" alt="No Image" onerror="this.src='https://placehold.co/70x90?text=No+Image'">
                            <?php endif; ?>
                        </div>
                        <div class="item-detail">
                            <div class="item-title"><?php echo htmlspecialchars($item['judul']); ?></div>
                            <div class="item-author"><?php echo htmlspecialchars($item['penulis']); ?></div>
                            <div class="mt-1">
                                <small>Quantity: <?php echo $item['quantity']; ?> x <?php echo formatRupiah($item['price']); ?></small>
                            </div>
                        </div>
                        <div class="fw-bold fs-5">
                            <?php echo formatRupiah($item['price'] * $item['quantity']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle"></i> Tidak ada item pesanan
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ringkasan Pembayaran -->
        <div class="summary-card">
            <div class="card-header">
                <i class="fas fa-calculator"></i> Ringkasan Pembayaran
            </div>
            <div class="card-body">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatRupiah($order['total_amount']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Biaya Pengiriman</span>
                    <span><?php echo isset($order['shipping_cost']) && $order['shipping_cost'] > 0 ? formatRupiah($order['shipping_cost']) : 'Rp 0'; ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span><strong>Total Pembayaran</strong></span>
                    <span class="fs-4" style="color: #f472b6;"><?php echo formatRupiah($order['total_amount']); ?></span>
                </div>
            </div>
        </div>

        <!-- Alamat Pengiriman -->
        <div class="address-card">
            <div class="card-header">
                <i class="fas fa-map-marker-alt"></i> Informasi Pelanggan
            </div>
            <div class="card-body">
                <div class="info-row">
                    <div class="info-label">Nama Penerima</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">No. Telepon</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['no_telepon'] ?? '-'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Alamat Pengiriman</div>
                    <div class="info-value">
                        <?php 
                        if (!empty($order['shipping_address'])) {
                            echo nl2br(htmlspecialchars($order['shipping_address']));
                        } elseif (!empty($user['alamat'])) {
                            echo nl2br(htmlspecialchars($user['alamat']));
                        } else {
                            echo '-';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan (jika ada) -->
        <?php if (!empty($order['notes'])): ?>
        <div class="address-card">
            <div class="card-header">
                <i class="fas fa-sticky-note"></i> Catatan
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tombol Aksi -->
        <div class="d-flex gap-3 justify-content-end mt-3 flex-wrap">
            <?php if ($order['payment_method'] == 'transfer' && $order['payment_status'] == 'unpaid'): ?>
                <button class="btn-primary-custom" onclick="showPaymentInfo()">
                    <i class="fas fa-credit-card"></i> Informasi Pembayaran
                </button>
            <?php endif; ?>
            
            <?php if ($order['order_status'] == 'delivered' || $order['order_status'] == 'completed'): ?>
                <a href="books/" class="btn-primary-custom">
                    <i class="fas fa-shopping-cart"></i> Belanja Lagi
                </a>
            <?php endif; ?>
            
            <a href="orders.php" class="btn-outline-custom">
                <i class="fas fa-list"></i> Semua Pesanan
            </a>
            
            <button onclick="window.print()" class="btn-outline-custom">
                <i class="fas fa-print"></i> Cetak Invoice
            </button>
        </div>
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
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-1"></i> Total yang harus ditransfer: <strong><?php echo formatRupiah($order['total_amount']); ?></strong>
                    </div>
                    <div class="bg-dark p-3 rounded mb-3">
                        <strong>🏦 BCA</strong><br>
                        No. Rekening: 123 456 7890<br>
                        a.n: zelfa store
                    </div>
                    <div class="bg-dark p-3 rounded mb-3">
                        <strong>🏦 Mandiri</strong><br>
                        No. Rekening: 987 654 3210<br>
                        a.n: zelfa store
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-clock me-1"></i> Transfer sesuai total pembayaran. Setelah transfer, silakan konfirmasi pembayaran Anda.
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
        function showPaymentInfo() {
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>