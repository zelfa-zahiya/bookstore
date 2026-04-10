<?php
// Mulai session di awal file
session_start();

// Hapus semua data session
$_SESSION = array();

// Hapus session cookie jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hancurkan session di server
session_destroy();

// Redirect ke halaman utama dengan parameter logout=success
header("Location: index.php?logout=success");
exit();
?>