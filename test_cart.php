<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h2>Debug Cart</h2>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Tidak login') . "<br>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM cart WHERE id_user = $user_id");
    echo "<h3>Isi Cart:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Cart</th><th>ID Buku</th><th>Jumlah</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id_cart']}</td><td>{$row['id_buku']}</td><td>{$row['jumlah']}</td></tr>";
    }
    echo "</table>";
}

$conn->close();
?>