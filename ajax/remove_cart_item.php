<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'];
$cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID cart tidak valid']);
    exit;
}

// Verifikasi dan hapus
$deleteQuery = $conn->prepare("DELETE FROM cart WHERE id_cart = ? AND id_user = ?");
$deleteQuery->bind_param("ii", $cartId, $userId);

if ($deleteQuery->execute()) {
    // Hitung sisa cart
    $totalQuery = $conn->prepare("SELECT COALESCE(SUM(b.harga * c.jumlah), 0) as total, COALESCE(SUM(c.jumlah), 0) as items FROM cart c JOIN books b ON c.id_buku = b.id_buku WHERE c.id_user = ?");
    $totalQuery->bind_param("i", $userId);
    $totalQuery->execute();
    $totalResult = $totalQuery->get_result();
    $totalData = $totalResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'cart_total' => (int)($totalData['total'] ?? 0),
        'total_items' => (int)($totalData['items'] ?? 0)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus item']);
}

$deleteQuery->close();
$conn->close();
?>