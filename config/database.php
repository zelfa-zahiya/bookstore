<?php
// config/database.php
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

// Fungsi helper
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getBookImage($gambar) {
    if (empty($gambar) || $gambar == 'default.jpg') {
        return 'https://placehold.co/300x420?text=No+Image';
    }
    return '/asset/books_cover/' . $gambar;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(jumlah), 0) as total FROM cart WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    $stmt->close();
    return $count;
}
?>