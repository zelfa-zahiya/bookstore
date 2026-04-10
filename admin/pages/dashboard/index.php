<?php
// Get statistics with clean queries
$stats = [
    'total_books' => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn(),
];

// Recent orders - PERBAIKAN: gunakan order_status, bukan status
$recent_orders = $pdo->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.id_user = u.id_user 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Low stock books
$low_stock_books = $pdo->query("
    SELECT * FROM books 
    WHERE stok <= 5 
    ORDER BY stok ASC 
    LIMIT 5
")->fetchAll();

// Status badge mapping - PERBAIKAN: sesuaikan dengan nilai order_status
$status_badge = [
    'pending' => 'warning',
    'pending_payment' => 'warning', 
    'processing' => 'info',
    'shipping' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger'
];

// Fungsi untuk mendapatkan label status
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'pending_payment' => 'Pending Payment',
        'processing' => 'Processing',
        'shipping' => 'Shipping',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return $labels[$status] ?? ucfirst($status);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <!-- Heading Dashboard dengan gaya premium (putih, tebal, ikon) -->
    <h1 class="h2" style="color: #ffffff; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; text-shadow: 2px 2px 8px rgba(0,0,0,0.3); letter-spacing: -0.5px;">
        <i class="fas fa-chart-line me-2" style="color: #fbbf24;"></i> Dashboard
    </h1>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card card-stats bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Books</h5>
                        <h2><?= number_format($stats['total_books']) ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-book fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stats bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Orders</h5>
                        <h2><?= number_format($stats['total_orders']) ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stats bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Users</h5>
                        <h2><?= number_format($stats['total_users']) ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card card-stats bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Revenue</h5>
                        <h2>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-7">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Recent Orders</h5>
                <a href="?page=orders" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                                <tr class="text-center">
                                    <td colspan="5" class="py-4 text-muted">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $order['id_order'] ?></td>
                                        <td><?= htmlspecialchars($order['username']) ?></td>
                                        <td>Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $status_badge[$order['order_status']] ?? 'secondary' ?> px-2 py-1">
                                                <?= getStatusLabel($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td><small><?= date('d/m/Y', strtotime($order['created_at'])) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Low Stock Alert</h5>
                <a href="?page=books" class="btn btn-sm btn-link">Manage Books</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Book Title</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($low_stock_books)): ?>
                                <tr class="text-center">
                                    <td colspan="3" class="py-4 text-muted">All stocks are good</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($low_stock_books as $book): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($book['judul']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $book['stok'] <= 0 ? 'danger' : 'warning' ?> px-2 py-1">
                                                <i class="fas fa-box me-1"></i><?= $book['stok'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?page=books&action=edit&id=<?= $book['id_buku'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Update
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.card-stats {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    border-radius: 10px;
}

.card-stats:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.opacity-50 {
    opacity: 0.5;
}

.table-light {
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
}

.btn-link {
    text-decoration: none;
    padding: 0;
}

.card {
    border-radius: 10px;
    overflow: hidden;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>