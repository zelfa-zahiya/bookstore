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
$jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;

if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID cart tidak valid']);
    exit;
}

if ($jumlah < 1) {
    $jumlah = 1;
}

// Verifikasi cart item milik user yang login
$verifyQuery = $conn->prepare("SELECT c.id_cart, c.id_buku, b.stok, b.harga FROM cart c JOIN books b ON c.id_buku = b.id_buku WHERE c.id_cart = ? AND c.id_user = ?");
$verifyQuery->bind_param("ii", $cartId, $userId);
$verifyQuery->execute();
$verifyResult = $verifyQuery->get_result();

if ($verifyResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    exit;
}

$cartData = $verifyResult->fetch_assoc();
$stokTersedia = $cartData['stok'];
$harga = $cartData['harga'];

if ($jumlah > $stokTersedia) {
    echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Maksimal $stokTersedia"]);
    exit;
}

// Update jumlah
$updateQuery = $conn->prepare("UPDATE cart SET jumlah = ? WHERE id_cart = ?");
$updateQuery->bind_param("ii", $jumlah, $cartId);

if ($updateQuery->execute()) {
    // Hitung total harga cart
    $totalQuery = $conn->prepare("SELECT SUM(b.harga * c.jumlah) as total, SUM(c.jumlah) as items FROM cart c JOIN books b ON c.id_buku = b.id_buku WHERE c.id_user = ?");
    $totalQuery->bind_param("i", $userId);
    $totalQuery->execute();
    $totalResult = $totalQuery->get_result();
    $totalData = $totalResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'item_total' => $harga * $jumlah,
        'cart_total' => (int)($totalData['total'] ?? 0),
        'total_items' => (int)($totalData['items'] ?? 0)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate keranjang']);
}

$verifyQuery->close();
$updateQuery->close();
$conn->close();
?>