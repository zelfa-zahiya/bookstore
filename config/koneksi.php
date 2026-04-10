<?php
// Mulai session di AWAL file
session_start();

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Panggil file koneksi
require_once 'config/koneksi.php';

// Cek status login dari SESSION
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

// ... lanjutkan dengan kode lainnya ...
<?php
// config/koneksi.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset ke UTF-8
$conn->set_charset("utf8");

// Jangan tutup koneksi di sini, biarkan terbuka untuk digunakan
// $conn akan ditutup di masing-masing file yang membutuhkan
?>