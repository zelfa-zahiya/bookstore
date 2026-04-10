<?php
// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

?>
<?php
session_start();

$host = 'localhost';
$dbname = 'bookstore';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is regular user
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ../gate/login');
        exit();
    }
}

// Redirect if already logged in
function requireGuest() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header('Location: ../admin');
        } else {
            header('Location: ../../');
        }
        exit();
    }
}
?>