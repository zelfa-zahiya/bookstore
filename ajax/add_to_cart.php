<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

// Cek login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'];
$bookId = isset($_POST['id_buku']) ? (int)$_POST['id_buku'] : 0;
$jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID buku tidak valid']);
    exit;
}

// Cek stok buku
$stockQuery = $conn->prepare("SELECT stok, judul FROM books WHERE id_buku = ?");
$stockQuery->bind_param("i", $bookId);
$stockQuery->execute();
$stockResult = $stockQuery->get_result();

if ($stockResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Buku tidak ditemukan']);
    exit;
}

$book = $stockResult->fetch_assoc();
$stokTersedia = $book['stok'];
$judulBuku = $book['judul'];
$stockQuery->close();

if ($stokTersedia <= 0) {
    echo json_encode(['success' => false, 'message' => 'Stok buku habis']);
    exit;
}

if ($jumlah > $stokTersedia) {
    echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Stok tersedia: $stokTersedia"]);
    exit;
}

// Cek apakah buku sudah ada di cart
$checkQuery = $conn->prepare("SELECT id_cart, jumlah FROM cart WHERE id_user = ? AND id_buku = ?");
$checkQuery->bind_param("ii", $userId, $bookId);
$checkQuery->execute();
$checkResult = $checkQuery->get_result();

if ($checkResult->num_rows > 0) {
    // Update jumlah
    $cartItem = $checkResult->fetch_assoc();
    $newJumlah = $cartItem['jumlah'] + $jumlah;
    
    if ($newJumlah > $stokTersedia) {
        echo json_encode(['success' => false, 'message' => "Stok tidak mencukupi! Stok tersedia: $stokTersedia"]);
        exit;
    }
    
    $updateQuery = $conn->prepare("UPDATE cart SET jumlah = ? WHERE id_cart = ?");
    $updateQuery->bind_param("ii", $newJumlah, $cartItem['id_cart']);
    
    if ($updateQuery->execute()) {
        // Hitung total item di cart
        $countQuery = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) as total FROM cart WHERE id_user = ?");
        $countQuery->bind_param("i", $userId);
        $countQuery->execute();
        $countResult = $countQuery->get_result();
        $cartCount = $countResult->fetch_assoc()['total'];
        $countQuery->close();
        
        echo json_encode([
            'success' => true, 
            'message' => "Jumlah $judulBuku diperbarui di keranjang",
            'cart_count' => (int)$cartCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui keranjang: ' . $conn->error]);
    }
    $updateQuery->close();
} else {
    // Tambah baru ke cart
    $insertQuery = $conn->prepare("INSERT INTO cart (id_user, id_buku, jumlah) VALUES (?, ?, ?)");
    $insertQuery->bind_param("iii", $userId, $bookId, $jumlah);
    
    if ($insertQuery->execute()) {
        // Hitung total item di cart
        $countQuery = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) as total FROM cart WHERE id_user = ?");
        $countQuery->bind_param("i", $userId);
        $countQuery->execute();
        $countResult = $countQuery->get_result();
        $cartCount = $countResult->fetch_assoc()['total'];
        $countQuery->close();
        
        echo json_encode([
            'success' => true, 
            'message' => "$judulBuku berhasil ditambahkan ke keranjang",
            'cart_count' => (int)$cartCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke keranjang: ' . $conn->error]);
    }
    $insertQuery->close();
}

$checkQuery->close();
$conn->close();
?>