<?php
// Mulai session di AWAL file
session_start();

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $db_connection_failed = true;
} else {
    $db_connection_failed = false;
}

// Cek status login dari SESSION
$isLoggedIn = false;
$userData = null;
$cartItemCount = 0;

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    if (!$db_connection_failed) {
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
}

// FUNGSI UNTUK MENAMPILKAN GAMBAR DARI FOLDER
function getBookImage($gambar) {
    if (empty($gambar) || $gambar == 'default.jpg') {
        return 'https://placehold.co/300x420?text=No+Image';
    }
    return '/asset/books_cover/' . $gambar;
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Ambil parameter filter
$kategori_slug = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Ambil daftar kategori dari database
$categories = [];
if (!$db_connection_failed) {
    $catQuery = "SELECT id_kategori, nama_kategori FROM categories ORDER BY nama_kategori";
    $catResult = $conn->query($catQuery);
    if ($catResult && $catResult->num_rows > 0) {
        while ($row = $catResult->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// Mapping slug ke nama kategori
$slug_to_category = [
    'fiksi' => 'Fiksi',
    'non-fiksi' => 'Non-Fiksi',
    'dongeng' => 'Dongeng',
    'komik' => 'Komik',
    'novel' => 'Novel',
    'cerita rakyat' => 'Cerita Rakyat',
    'biografi' => 'Biografi',
    'buku pelajaran' => 'Buku Pelajaran'
];

$selected_category_name = '';
$selected_category_id = null;

if ($kategori_slug && isset($slug_to_category[$kategori_slug])) {
    $selected_category_name = $slug_to_category[$kategori_slug];
    foreach ($categories as $cat) {
        if (strtolower($cat['nama_kategori']) == strtolower($selected_category_name)) {
            $selected_category_id = $cat['id_kategori'];
            break;
        }
    }
}

// Bangun query untuk mengambil buku
$where_conditions = [];
$params = [];
$types = "";

if ($selected_category_id) {
    $where_conditions[] = "b.id_kategori = ?";
    $params[] = $selected_category_id;
    $types .= "i";
}

if ($search) {
    $where_conditions[] = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Sorting
$order_clause = "";
switch ($sort) {
    case 'termurah':
        $order_clause = "ORDER BY b.harga ASC";
        break;
    case 'termahal':
        $order_clause = "ORDER BY b.harga DESC";
        break;
    case 'terlaris':
        $order_clause = "ORDER BY COALESCE((SELECT SUM(oi.jumlah) FROM order_items oi WHERE oi.id_buku = b.id_buku), 0) DESC";
        break;
    default:
        $order_clause = "ORDER BY b.created_at DESC";
        break;
}

// Query untuk menghitung total data
$count_sql = "SELECT COUNT(*) as total FROM books b $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (count($params) > 0) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_books / $limit);
$count_stmt->close();

// Query untuk mengambil data buku
$sql = "SELECT b.*, c.nama_kategori 
        FROM books b 
        LEFT JOIN categories c ON b.id_kategori = c.id_kategori 
        $where_clause 
        $order_clause 
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$books_result = $stmt->get_result();
$books = [];
if ($books_result && $books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Buku | zelfa store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            overflow-x: hidden;
        }

        /* Ubah warna nama user di dropdown menjadi putih */
        .avatar-dropdown-toggle span:not(.avatar-circle) {
            color: white !important;
        }

        /* Atau cara yang lebih spesifik */
        .avatar-dropdown-toggle span:last-child {
            color: white !important;
        }

        /* Alternatif: langsung target ke span yang berisi nama user */
        .avatar-dropdown-toggle span {
            color: white;
        }

        .avatar-dropdown-toggle span.avatar-circle {
            color: white; /* Untuk huruf inisial di avatar juga putih */
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-blur {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            animation: floatBg 20s infinite ease-in-out;
        }

        .blur-1 { background: #6366f1; top: -150px; right: -150px; }
        .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
        .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }

        @keyframes floatBg {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }

        /* Top Bar - Dikosongkan */
        .top-bar {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 10px 0;
            font-size: 13px;
            position: relative;
            z-index: 100;
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(18, 18, 24, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 0;
            position: relative;
            z-index: 100;
            transition: all 0.3s;
        }

        .navbar-custom.scrolled {
            padding: 8px 0;
            background: rgba(18, 18, 24, 0.98);
        }

        /* LOGO TEKS GRADIENT */
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .logo-text:hover {
            transform: scale(1.02);
            letter-spacing: -0.3px;
        }

        @media (max-width: 768px) {
            .logo-text {
                font-size: 22px;
            }
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 500;
            margin: 0 8px;
            transition: all 0.3s;
            position: relative;
        }

        .nav-link:hover {
            color: #818cf8 !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            transition: width 0.3s;
        }

        .nav-link:hover::after {
            width: 30px;
        }

        .nav-link.active {
            color: #ffffff !important;
        }

        .nav-link.active::after {
            width: 30px;
        }

        /* Search Container */
        .search-container {
            position: relative;
            width: 280px;
        }

        .search-input {
            width: 100%;
            padding: 10px 18px 10px 42px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.08);
            width: 320px;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }

        /* Icon Nav Links */
        .icon-nav-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            text-decoration: none;
            position: relative;
        }

        .icon-nav-link:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ec4899, #f472b6);
            color: white;
            font-size: 10px;
            font-weight: 600;
            border-radius: 50%;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
        }

        /* Avatar Dropdown */
        .avatar-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 5px 12px 5px 5px;
            transition: all 0.3s;
        }

        .avatar-dropdown-toggle:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .dropdown-menu-custom {
            background: rgba(18, 18, 24, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            margin-top: 10px;
            min-width: 220px;
        }

        .dropdown-item-custom {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .dropdown-item-custom:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            padding-left: 25px;
        }

        /* Auth Buttons */
        .btn-auth {
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-login {
            background: transparent;
            border: 1px solid rgba(99, 102, 241, 0.5);
            color: #818cf8;
        }

        .btn-login:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        /* Page Header */
        .page-header {
            padding: 60px 0 40px;
            position: relative;
            z-index: 10;
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
        }

        /* Filter Sidebar */
        .filter-sidebar {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 28px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: sticky;
            top: 20px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #6366f1;
            display: inline-block;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li {
            margin-bottom: 8px;
        }

        .category-list a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: block;
            padding: 10px 14px;
            border-radius: 12px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .category-list a:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            transform: translateX(5px);
        }

        .category-list a.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(236, 72, 153, 0.2));
            color: #a78bfa;
            border-left: 3px solid #6366f1;
        }

        .sort-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 12px 16px;
            color: white;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s;
        }

        .sort-select:focus {
            outline: none;
            border-color: #6366f1;
        }

        /* Book Card */
        .book-card {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s;
            border: 1px solid rgba(255, 255, 255, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-10px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .book-img-container {
            position: relative;
            padding-top: 130%;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }

        .book-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .book-card:hover .book-img {
            transform: scale(1.08);
        }

        .book-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-category {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: #a78bfa;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            background: rgba(99, 102, 241, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            width: fit-content;
        }

        .book-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: white;
            min-height: 44px;
        }

        .book-author {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
            min-height: 36px;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }

        .book-author i {
            flex-shrink: 0;
        }

        .book-price {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #f472b6, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            min-height: 40px;
        }

        .book-stock {
            font-size: 11px;
            margin-bottom: 16px;
            padding: 4px 0;
            min-height: 30px;
        }

        .book-stock.in-stock {
            color: #22c55e;
        }

        .book-stock.out-stock {
            color: #ef4444;
        }

        .book-button-wrapper {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-add-cart {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 500;
            color: white;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            text-align: center;
            display: block;
            text-decoration: none;
        }

        .btn-add-cart:hover:not(:disabled) {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .btn-add-cart:disabled {
            background: rgba(255, 255, 255, 0.03);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-detail {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 8px 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-detail:hover {
            border-color: #6366f1;
            color: #818cf8;
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            min-width: 300px;
            background: rgba(18, 18, 24, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 15px 20px;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-success {
            border-left: 4px solid #22c55e;
        }

        .toast-error {
            border-left: 4px solid #ef4444;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9998;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pagination */
        .pagination-custom {
            margin-top: 50px;
            justify-content: center;
        }

        .pagination-custom .page-link {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            margin: 0 5px;
            border-radius: 12px;
            padding: 10px 16px;
            transition: all 0.3s;
        }

        .pagination-custom .page-link:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: #818cf8;
        }

        .pagination-custom .page-item.active .page-link {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-color: transparent;
            color: white;
        }

        .pagination-custom .page-item.disabled .page-link {
            opacity: 0.5;
        }

        /* No Books */
        .no-books {
            text-align: center;
            padding: 80px 20px;
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
        }

        .no-books i {
            font-size: 70px;
            color: rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .no-books h4 {
            font-size: 24px;
            margin-bottom: 12px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 12px 32px;
            font-weight: 600;
            font-size: 14px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.5);
            color: white;
        }

        /* Footer */
        footer {
            background: rgba(18, 18, 24, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px 0 30px;
            margin-top: 80px;
            position: relative;
            z-index: 10;
        }

        .footer-brand {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 16px;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .footer-links a:hover {
            color: #818cf8;
            transform: translateX(5px);
        }

        .footer-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 36px; }
            .filter-sidebar {
                margin-bottom: 30px;
                position: static;
            }
            .search-container { width: 100%; }
            .search-input:focus { width: 100%; }
            .navbar-nav { margin: 20px 0; }
            .search-container { margin: 15px 0; }
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 28px; }
            .page-header { padding: 40px 0 20px; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a2e;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 10px;
        }

        ::selection {
            background: #6366f1;
            color: white;
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-notification" id="toastNotification">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2" style="color: #22c55e; font-size: 20px;"></i>
            <div>
                <strong id="toastTitle">Berhasil!</strong>
                <p class="mb-0 small" id="toastMessage">Buku berhasil ditambahkan ke keranjang</p>
            </div>
        </div>
    </div>

    <!-- Top Bar - Dikosongkan -->
    <div class="top-bar d-none d-lg-block">
        <div class="container">
            <div class="d-flex justify-content-end">
                <!-- Top Bar dikosongkan -->
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <span class="logo-text">zelfa store</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Koleksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="../about_us.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="../contact.php">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <form method="GET" action="" id="searchForm">
                            <input type="text" class="search-input" placeholder="Cari buku..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php if ($kategori_slug): ?>
                            <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_slug); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <?php if ($isLoggedIn && $userData): ?>
                        <a href="../cart.php" class="icon-nav-link position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge" id="cartCount"><?php echo $cartItemCount; ?></span>
                        </a>
                        <div class="dropdown">
                            <button class="avatar-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="avatar-circle"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                                <li><a class="dropdown-item dropdown-item-custom" href="../profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="../orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="../my_messages.php"><i class="fas fa-shopping-bag me-2"></i> My Messaage</a></li>
                                <?php if($userData['role'] == 'admin'): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="../admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="../gate/login/" class="btn-auth btn-login">Masuk</a>
                        <a href="../gate/register/" class="btn-auth btn-register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>
                <?php if ($selected_category_name): ?>
                    📚 Buku <?php echo htmlspecialchars($selected_category_name); ?>
                <?php elseif ($search): ?>
                    🔍 Hasil Pencarian: "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    ✨ Semua Koleksi Buku
                <?php endif; ?>
            </h1>
            <p>Temukan berbagai koleksi buku terbaik untuk menemani harimu</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 col-md-12">
                <div class="filter-sidebar">
                    <div class="filter-title">
                        <i class="fas fa-filter me-2"></i> Filter Kategori
                    </div>
                    
                    <div class="mb-4">
                        <ul class="category-list">
                            <li><a href="?" class="<?php echo (!$selected_category_name && !$search) ? 'active' : ''; ?>">📚 Semua Buku</a></li>
                            <?php foreach ($categories as $cat): ?>
                            <li><a href="?kategori=<?php echo strtolower($cat['nama_kategori']); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                                   class="<?php echo (strtolower($selected_category_name) == strtolower($cat['nama_kategori'])) ? 'active' : ''; ?>">
                                📖 <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <label class="fw-bold mb-2"><i class="fas fa-sort-amount-down me-2"></i> Urutkan</label>
                        <select class="sort-select" id="sortSelect">
                            <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Termurah</option>
                            <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Termahal</option>
                        </select>
                    </div>
                    
                    <div class="text-muted small pt-3 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                        <i class="fas fa-book me-1"></i> Menampilkan <?php echo count($books); ?> dari <?php echo $total_books; ?> buku
                    </div>
                </div>
            </div>
            
            <!-- Book Grid -->
            <div class="col-lg-9 col-md-12">
                <?php if (count($books) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($books as $book): ?>
                        <div class="col-lg-4 col-md-6 col-sm-6">
                            <div class="book-card">
                                <div class="book-img-container">
                                    <img class="book-img" 
                                         src="<?php echo getBookImage($book['gambar'] ?? ''); ?>" 
                                         alt="<?php echo htmlspecialchars($book['judul']); ?>"
                                         onerror="this.src='https://placehold.co/300x390?text=No+Image'">
                                </div>
                                <div class="book-info">
                                    <span class="book-category"><?php echo htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
                                    <div class="book-title"><?php echo htmlspecialchars($book['judul']); ?></div>
                                    <div class="book-author"><i class="fas fa-user-edit me-1"></i> <?php echo htmlspecialchars($book['penulis']); ?></div>
                                    <div class="book-price"><?php echo formatRupiah($book['harga']); ?></div>
                                    <div class="book-stock <?php echo $book['stok'] > 0 ? 'in-stock' : 'out-stock'; ?>">
                                        <i class="fas <?php echo $book['stok'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                        <?php echo $book['stok'] > 0 ? 'Stok: ' . $book['stok'] : 'Stok Habis'; ?>
                                    </div>
                                    <div class="book-button-wrapper">
                                        <?php if ($book['stok'] > 0): ?>
                                            <button class="btn-add-cart add-to-cart" 
                                                    data-id="<?php echo $book['id_buku']; ?>"
                                                    data-stok="<?php echo $book['stok']; ?>">
                                                <i class="fas fa-shopping-cart me-1"></i> Tambah ke Keranjang
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-add-cart" disabled>
                                                <i class="fas fa-times-circle me-1"></i> Stok Habis
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-detail view-detail" data-id="<?php echo $book['id_buku']; ?>">
                                            <i class="fas fa-eye me-1"></i> Lihat Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination pagination-custom">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page-2 && $i <= $page+2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php elseif (($i == $page-3 || $i == $page+3) && $page-3 > 1 && $page+3 < $total_pages): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-books">
                        <i class="fas fa-book-open"></i>
                        <h4>Belum ada buku</h4>
                        <p class="text-muted"><?php echo $search ? 'Tidak ada buku yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Belum ada buku dalam kategori ini'; ?></p>
                        <a href="?" class="btn-primary-custom mt-3">Lihat Semua Buku <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">zelfa store</div>
                    <p class="footer-text">Toko buku online terpercaya yang menyediakan berbagai koleksi buku berkualitas untuk menemani perjalanan literasimu.</p>
                </div>
                <div class="col-md-2 mb-4 offset-md-1">
                    <div class="footer-title">Tentang</div>
                    <div class="footer-links">
                        <a href="../about_us.php">Tentang Kami</a>
                        <a href="../contact.php">Kontak</a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="footer-title">Layanan</div>
                    <div class="footer-links">
                        <a href="../cart.php">Keranjang</a>
                        <a href="../orders.php">Pesanan</a>
                        <a href="../wishlist.php">Wishlist</a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="footer-title">Kontak</div>
                    <div class="footer-links">
                        <a href="#"><i class="fas fa-map-marker-alt me-2"></i> Jakarta, Indonesia</a>
                        <a href="#"><i class="fas fa-phone me-2"></i> +62 812-3456-7890</a>
                        <a href="#"><i class="fas fa-envelope me-2"></i> info@zelfastore.com</a>
                    </div>
                </div>
            </div>
            <hr class="mt-3" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="footer-text small mb-0">&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle search form submit with debounce
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const form = this.closest('form');
                    if (form) form.submit();
                }, 500);
            });
        }
        
        // Handle sort change
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('sort', this.value);
                urlParams.set('page', '1');
                window.location.href = '?' + urlParams.toString();
            });
        }
        
        // Show toast notification
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('toastNotification');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            const icon = toast.querySelector('.fa-check-circle');
            
            toastTitle.textContent = isSuccess ? 'Berhasil!' : 'Gagal!';
            toastMessage.textContent = message;
            
            if (isSuccess) {
                toast.classList.add('toast-success');
                toast.classList.remove('toast-error');
                if (icon) {
                    icon.style.color = '#22c55e';
                    icon.className = 'fas fa-check-circle me-2';
                }
            } else {
                toast.classList.add('toast-error');
                toast.classList.remove('toast-success');
                if (icon) {
                    icon.style.color = '#ef4444';
                    icon.className = 'fas fa-times-circle me-2';
                }
            }
            
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        // Update cart count in navbar
        function updateCartCount(count) {
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                cartBadge.textContent = count;
                if (count > 0) {
                    cartBadge.style.display = 'inline-block';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }
        
        // Add to cart functionality
const addToCartButtons = document.querySelectorAll('.add-to-cart');
addToCartButtons.forEach(button => {
    button.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const bookId = this.dataset.id;
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        
        if (!isLoggedIn) {
            showToast('Silakan login terlebih dahulu', false);
            setTimeout(() => {
                window.location.href = '../gate/login/';
            }, 1500);
            return;
        }
        
        // Show loading
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
        
        try {
            const formData = new FormData();
            formData.append('id_buku', bookId);
            formData.append('jumlah', 1);
            
            // Panggil ajax yang ada di folder ajax (naik satu level)
            const response = await fetch('../ajax/add_to_cart.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, true);
                if (result.cart_count !== undefined) {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        cartBadge.textContent = result.cart_count;
                        if (result.cart_count > 0) {
                            cartBadge.style.display = 'inline-block';
                        } else {
                            cartBadge.style.display = 'none';
                        }
                    }
                }
                
                // Add animation to button
                const btn = this;
                btn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    btn.style.transform = '';
                }, 200);
            } else {
                showToast(result.message, false);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Terjadi kesalahan, silakan coba lagi', false);
        } finally {
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        }
    });
});
        
        // View detail functionality
        const viewDetailButtons = document.querySelectorAll('.view-detail');
        viewDetailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.dataset.id;
                window.location.href = 'detail.php?id=' + bookId;
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>