<?php
date_default_timezone_set('Asia/Jakarta');

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../gate/login/");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Update status pesanan
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $order_status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'] ?? null;
    
    $allowedOrderStatus = ['pending', 'pending_payment', 'processing', 'shipping', 'delivered', 'cancelled'];
    $allowedPaymentStatus = ['unpaid', 'paid', 'cod'];
    
    if (in_array($order_status, $allowedOrderStatus)) {
        $updateStmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id_order = ?");
        $updateStmt->bind_param("si", $order_status, $order_id);
        if ($updateStmt->execute()) {
            $success = true;
        } else {
            $error = "Gagal update status pesanan: " . $updateStmt->error;
        }
        $updateStmt->close();
    } else {
        $error = "Status pesanan tidak valid!";
    }
    
    if ($payment_status && in_array($payment_status, $allowedPaymentStatus)) {
        $updatePayStmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id_order = ?");
        $updatePayStmt->bind_param("si", $payment_status, $order_id);
        $updatePayStmt->execute();
        $updatePayStmt->close();
    }
    
    if (isset($success)) {
        echo "<script>alert('Status pesanan berhasil diupdate!'); window.location.href='?page=orders';</script>";
    } elseif (isset($error)) {
        echo "<script>alert('Error: $error'); window.location.href='?page=orders';</script>";
    }
}

// Ambil semua pesanan
$query = "SELECT o.*, u.username, u.email 
          FROM orders o 
          JOIN users u ON o.id_user = u.id_user 
          ORDER BY o.created_at DESC";
$result = $conn->query($query);
$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

function formatRupiah($angka) {
    if ($angka === null) return "Rp 0";
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getOrderStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge-order status-pending"><i class="fas fa-clock me-1"></i> Pending</span>';
        case 'pending_payment':
            return '<span class="badge-order status-pending-payment"><i class="fas fa-hourglass-half me-1"></i> Pending Payment</span>';
        case 'processing':
            return '<span class="badge-order status-processing"><i class="fas fa-spinner fa-spin me-1"></i> Processing</span>';
        case 'shipping':
            return '<span class="badge-order status-shipping"><i class="fas fa-truck me-1"></i> Shipping</span>';
        case 'delivered':
            return '<span class="badge-order status-delivered"><i class="fas fa-check-circle me-1"></i> Delivered</span>';
        case 'completed':
            return '<span class="badge-order status-delivered"><i class="fas fa-check-circle me-1"></i> Selesai</span>';
        case 'cancelled':
            return '<span class="badge-order status-cancelled"><i class="fas fa-times-circle me-1"></i> Cancelled</span>';
        default:
            return '<span class="badge-order status-pending">' . $status . '</span>';
    }
}

function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'unpaid':
            return '<span class="badge-payment status-unpaid"><i class="fas fa-exclamation-circle me-1"></i> Unpaid</span>';
        case 'paid':
            return '<span class="badge-payment status-paid"><i class="fas fa-check-circle me-1"></i> Paid</span>';
        case 'cod':
            return '<span class="badge-payment status-cod"><i class="fas fa-money-bill-wave me-1"></i> COD</span>';
        default:
            return '<span class="badge-payment status-unpaid">' . $status . '</span>';
    }
}
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #0a0a0a; font-family: 'Inter', sans-serif; color: white; }
    
    .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
    .bg-blur { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: floatBg 20s infinite ease-in-out; }
    .blur-1 { background: #6366f1; top: -150px; right: -150px; }
    .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
    .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }
    @keyframes floatBg { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(50px,-50px) scale(1.1); } 66% { transform: translate(-30px,30px) scale(0.9); } }

    .orders-container {
        position: relative;
        z-index: 10;
        padding: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: rgba(18,18,24,0.8);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 20px;
        transition: all 0.3s;
        border: 1px solid rgba(255,255,255,0.05);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        border-color: rgba(99,102,241,0.3);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #6366f1, #ec4899);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    
    .stat-icon i {
        font-size: 24px;
        color: white;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        background: linear-gradient(135deg, #ffffff, #a78bfa);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
        font-weight: 500;
    }
    
    .main-card {
        background: rgba(18,18,24,0.8);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.05);
        overflow: hidden;
    }
    
    .card-header-custom {
        background: rgba(0,0,0,0.2);
        border-bottom: 1px solid rgba(255,255,255,0.05);
        padding: 20px 25px;
    }
    
    .order-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .order-table thead th {
        background: rgba(0,0,0,0.2);
        padding: 15px;
        font-weight: 600;
        font-size: 13px;
        color: rgba(255,255,255,0.7);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .order-table tbody tr {
        border-bottom: 1px solid rgba(255,255,255,0.05);
        transition: all 0.2s;
    }
    
    .order-table tbody tr:hover {
        background: rgba(99,102,241,0.05);
    }
    
    .order-table td {
        padding: 16px 15px;
        vertical-align: middle;
        color: rgba(255,255,255,0.8);
    }
    
    .badge-order, .badge-payment {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 11px;
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
    
    .btn-action {
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        transition: all 0.3s;
        margin: 0 3px;
        font-weight: 500;
        border: none;
    }
    
    .btn-view {
        background: rgba(59,130,246,0.15);
        color: #60a5fa;
        border: 1px solid rgba(59,130,246,0.3);
    }
    
    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }
    
    .btn-edit {
        background: rgba(245,158,11,0.15);
        color: #fbbf24;
        border: 1px solid rgba(245,158,11,0.3);
    }
    
    .btn-edit:hover {
        background: #f59e0b;
        color: white;
    }
    
    .order-id {
        font-weight: 700;
        color: #818cf8;
        font-family: monospace;
        font-size: 14px;
    }
    
    .user-name {
        font-weight: 600;
        color: white;
        margin-bottom: 3px;
    }
    
    .user-email {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
    }
    
    .total-amount {
        font-weight: 700;
        background: linear-gradient(135deg, #f472b6, #a78bfa);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-size: 15px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 60px;
        color: rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }
    
    .empty-state h4 {
        font-size: 18px;
        color: white;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: rgba(255,255,255,0.5);
    }
    
    .modal-content-custom {
        background: rgba(18,18,24,0.98);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 24px;
    }
    
    .form-select-custom {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: white;
        padding: 12px 16px;
        border-radius: 12px;
        width: 100%;
    }
    
    .form-select-custom:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .form-select-custom option {
        background: #1a1a2e;
        color: white;
    }
    
    .btn-refresh {
        background: rgba(255,255,255,0.05);
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 30px;
        padding: 8px 20px;
        transition: all 0.3s;
    }
    
    .btn-refresh:hover {
        background: rgba(99,102,241,0.2);
        border-color: #6366f1;
    }
    
    .info-box {
        background: rgba(255,255,255,0.03);
        border-radius: 16px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .info-label {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 5px;
    }
    
    .info-value {
        font-weight: 600;
        color: white;
    }
    
    /* Item table styles - DIPERBAIKI */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
        overflow: hidden;
    }
    
    .items-table thead th {
        background: rgba(0,0,0,0.4);
        padding: 12px 15px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,0.8);
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .items-table tbody td {
        padding: 12px 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: rgba(255,255,255,0.8);
        vertical-align: middle;
    }
    
    .items-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .items-table tfoot td {
        padding: 12px 15px;
        background: rgba(99,102,241,0.15);
        font-weight: 700;
        border-top: 1px solid rgba(99,102,241,0.3);
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-end {
        text-align: right;
    }
    
    .text-start {
        text-align: left;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: rgba(255,255,255,0.7);
        padding: 10px;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 30px;
        padding: 8px 16px;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px;
        margin: 0 3px;
        border-radius: 10px;
        background: rgba(255,255,255,0.05);
        border: none;
        color: rgba(255,255,255,0.7);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #6366f1;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        color: white;
    }
    
    .modal-header-custom {
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding: 20px 25px;
    }
    
    .modal-footer-custom {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding: 15px 25px;
    }
    
    .product-name {
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .product-author {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
    }
</style>

<div class="orders-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-value"><?php echo count($orders); ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-value">
                <?php 
                $pending = count(array_filter($orders, function($o) { 
                    return in_array($o['order_status'], ['pending', 'pending_payment', 'processing']); 
                }));
                echo $pending;
                ?>
            </div>
            <div class="stat-label">Sedang Diproses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value">
                <?php 
                $completed = count(array_filter($orders, function($o) { 
                    return $o['order_status'] == 'delivered' || $o['order_status'] == 'completed'; 
                }));
                echo $completed;
                ?>
            </div>
            <div class="stat-label">Selesai</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value">
                <?php 
                $totalRevenue = array_sum(array_column($orders, 'total_amount'));
                echo formatRupiah($totalRevenue);
                ?>
            </div>
            <div class="stat-label">Total Pendapatan</div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-0" style="font-weight: 700; color: white;">
                        <i class="fas fa-list me-2" style="color: #818cf8;"></i> Daftar Pesanan
                    </h5>
                    <small style="color: rgba(255,255,255,0.5);">Kelola semua pesanan pelanggan</small>
                </div>
                <button class="btn-refresh" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
        
        <div class="table-responsive p-3">
            <table class="table order-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID Order</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status Pesanan</th>
                        <th>Status Pembayaran</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="padding: 0;">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>Belum Ada Pesanan</h4>
                                <p>Belum ada pesanan dari pelanggan</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><span class="order-id">#<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?></span></td>
                            <td>
                                <div class="user-name"><?php echo htmlspecialchars($order['username']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($order['email']); ?></div>
                            </div>
                            <td><span class="total-amount"><?php echo formatRupiah($order['total_amount']); ?></span></td>
                            <td>
                                <?php if ($order['payment_method'] == 'cod'): ?>
                                    <span class="badge-payment status-cod"><i class="fas fa-truck me-1"></i> COD</span>
                                <?php else: ?>
                                    <span class="badge-payment status-paid"><i class="fas fa-university me-1"></i> Transfer</span>
                                <?php endif; ?>
                            </div>
                            <td><?php echo getOrderStatusBadge($order['order_status']); ?></td>
                            <td><?php echo getPaymentStatusBadge($order['payment_status']); ?></td>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small></td>
                            <td>
                                <button class="btn-action btn-view" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $order['id_order']; ?>">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id_order']; ?>">
                                    <i class="fas fa-edit"></i> Status
                                </button>
                            </div>
                        </tr>
                        
                        <!-- MODAL DETAIL PESANAN - DIPERBAIKI TOTAL -->
                        <div class="modal fade" id="detailModal<?php echo $order['id_order']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content modal-content-custom">
                                    <div class="modal-header modal-header-custom">
                                        <h5 class="modal-title" style="color: white;">
                                            <i class="fas fa-receipt me-2" style="color: #818cf8;"></i> 
                                            Detail Pesanan #<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Informasi Pelanggan -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <div class="info-label"><i class="fas fa-user me-1"></i> Pelanggan</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($order['username']); ?></div>
                                                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($order['email']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <div class="info-label"><i class="fas fa-calendar me-1"></i> Tanggal Pesanan</div>
                                                    <div class="info-value"><?php echo date('d F Y H:i', strtotime($order['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Alamat Pengiriman -->
                                        <div class="info-box">
                                            <div class="info-label"><i class="fas fa-map-marker-alt me-1"></i> Alamat Pengiriman</div>
                                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                                        </div>
                                        
                                        <!-- Catatan -->
                                        <?php if (!empty($order['notes'])): ?>
                                        <div class="info-box" style="background: rgba(245,158,11,0.1);">
                                            <div class="info-label"><i class="fas fa-sticky-note me-1"></i> Catatan</div>
                                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Item Pesanan - TABEL RAPI -->
                                        <h6 class="fw-bold mb-3 mt-3" style="color: white;">
                                            <i class="fas fa-shopping-bag me-2" style="color: #818cf8;"></i> Item Pesanan
                                        </h6>
                                        
                                        <div class="table-responsive">
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-start">Buku</th>
                                                        <th class="text-center" style="width: 80px;">Qty</th>
                                                        <th class="text-end" style="width: 150px;">Harga</th>
                                                        <th class="text-end" style="width: 150px;">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $itemQuery = "SELECT oi.*, b.judul, b.penulis FROM order_items oi JOIN books b ON oi.id_buku = b.id_buku WHERE oi.order_id = ?";
                                                    $itemStmt = $conn->prepare($itemQuery);
                                                    $itemStmt->bind_param("i", $order['id_order']);
                                                    $itemStmt->execute();
                                                    $itemsResult = $itemStmt->get_result();
                                                    if ($itemsResult->num_rows > 0):
                                                        while ($item = $itemsResult->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td class="text-start">
                                                            <div class="product-name"><?php echo htmlspecialchars($item['judul']); ?></div>
                                                            <div class="product-author"><?php echo htmlspecialchars($item['penulis']); ?></div>
                                                        </td>
                                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                        <td class="text-end"><?php echo formatRupiah($item['price']); ?></td>
                                                        <td class="text-end"><?php echo formatRupiah($item['price'] * $item['quantity']); ?></td>
                                                    </tr>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Tidak ada item pesanan</td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                                        <td class="text-end" style="color: #f472b6; font-size: 18px;">
                                                            <strong><?php echo formatRupiah($order['total_amount']); ?></strong>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <!-- Informasi Pembayaran -->
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <div class="info-label"><i class="fas fa-credit-card me-1"></i> Metode Pembayaran</div>
                                                    <div class="info-value">
                                                        <?php echo $order['payment_method'] == 'cod' ? 'Cash On Delivery (COD)' : 'Transfer Bank'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box">
                                                    <div class="info-label"><i class="fas fa-tag me-1"></i> Status</div>
                                                    <div class="info-value">
                                                        <?php echo getOrderStatusBadge($order['order_status']); ?>
                                                        <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer modal-footer-custom">
                                        <button type="button" class="btn" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border-radius: 30px; color: white;">
                                            <i class="fas fa-times me-1"></i> Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- MODAL UPDATE STATUS -->
                        <div class="modal fade" id="statusModal<?php echo $order['id_order']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content modal-content-custom">
                                    <div class="modal-header modal-header-custom">
                                        <h5 class="modal-title" style="color: white;">
                                            <i class="fas fa-edit me-2" style="color: #fbbf24;"></i>
                                            Update Status #<?php echo str_pad($order['id_order'], 6, '0', STR_PAD_LEFT); ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color: white;">Status Pesanan</label>
                                                <select name="order_status" class="form-select-custom">
                                                    <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>📋 Pending</option>
                                                    <option value="pending_payment" <?php echo $order['order_status'] == 'pending_payment' ? 'selected' : ''; ?>>⏳ Pending Payment</option>
                                                    <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>⚙️ Processing</option>
                                                    <option value="shipping" <?php echo $order['order_status'] == 'shipping' ? 'selected' : ''; ?>>🚚 Shipping</option>
                                                    <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>✅ Delivered</option>
                                                    <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                                </select>
                                            </div>
                                            <?php if ($order['payment_method'] == 'transfer'): ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color: white;">Status Pembayaran</label>
                                                <select name="payment_status" class="form-select-custom">
                                                    <option value="unpaid" <?php echo $order['payment_status'] == 'unpaid' ? 'selected' : ''; ?>>💰 Unpaid</option>
                                                    <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>✅ Paid</option>
                                                </select>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer modal-footer-custom">
                                            <button type="button" class="btn" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border-radius: 30px; color: white;">Batal</button>
                                            <button type="submit" name="update_status" class="btn" style="border-radius: 30px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; color: white;">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        "pageLength": 10,
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "→",
                "previous": "←"
            }
        },
        "order": [[6, 'desc']]
    });
});
</script>