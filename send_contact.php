<?php
// WAJIB: Set timezone di BARIS PERTAMA setelah <?php
date_default_timezone_set('Asia/Jakarta');

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Debug: Cek waktu
// error_log("Waktu saat ini: " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Nama lengkap wajib diisi";
    if (empty($email)) $errors[] = "Alamat email wajib diisi";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (empty($subject)) $errors[] = "Subjek wajib diisi";
    if (empty($message)) $errors[] = "Pesan wajib diisi";
    
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        header("Location: contact.php");
        exit();
    }
    
    $id_user = $_SESSION['user_id'] ?? null;
    $status = 'unread';
    $created_at = date('Y-m-d H:i:s'); // Ini akan menggunakan timezone Asia/Jakarta
    
    $sql = "INSERT INTO contacts (id_user, name, email, subjek, pesan, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $_SESSION['contact_errors'] = ["Error: " . $conn->error];
        header("Location: contact.php");
        exit();
    }
    
    $stmt->bind_param("issssss", $id_user, $name, $email, $subject, $message, $status, $created_at);
    
    if ($stmt->execute()) {
        $_SESSION['contact_success'] = "Pesan Anda berhasil dikirim! Kami akan segera merespon.";
        header("Location: contact.php?success=1");
    } else {
        $_SESSION['contact_errors'] = ["Gagal mengirim pesan: " . $stmt->error];
        header("Location: contact.php");
    }
    
    $stmt->close();
} else {
    header("Location: contact.php");
}

$conn->close();
?>