<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: ../gate/login');
exit();
?>