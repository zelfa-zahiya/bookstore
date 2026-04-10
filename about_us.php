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

// Cek status login
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

// Ambil parameter pencarian dari URL
$searchAbout = isset($_GET['search_about']) ? trim($_GET['search_about']) : '';

// Konten tentang untuk dicari (data statis)
$aboutContents = [
    ['title' => 'Siapa Kami?', 'content' => 'zelfa store adalah toko buku online yang lahir dari kecintaan terhadap membaca. Kami percaya bahwa setiap buku adalah jendela menuju dunia baru. Berdiri sejak 2025, kami telah melayani ribuan pembaca setia di seluruh Indonesia dengan koleksi buku yang terus berkembang. Kami berkomitmen untuk memberikan pengalaman berbelanja buku yang mudah, nyaman, dan menyenangkan.'],
    ['title' => 'Visi', 'content' => 'Menjadi toko buku online terpercaya yang mendukung literasi Indonesia.'],
    ['title' => 'Misi', 'content' => 'Menyediakan buku berkualitas dengan harga terbaik, memberikan pelayanan yang ramah dan cepat, serta membangun komunitas pembaca yang aktif.'],
    ['title' => 'Visi & Misi', 'content' => 'Visi: Menjadi toko buku online terpercaya yang mendukung literasi Indonesia. Misi: Menyediakan buku berkualitas dengan harga terbaik, memberikan pelayanan yang ramah dan cepat, serta membangun komunitas pembaca yang aktif.'],
    ['title' => 'Koleksi Lengkap', 'content' => 'Ribuan judul buku tersedia dari berbagai genre seperti Novel, Fiksi, Non-Fiksi, Komik, dan masih banyak lagi.'],
    ['title' => 'Harga Terbaik', 'content' => 'Diskon dan promo menarik setiap hari untuk setiap pembelian buku.'],
    ['title' => 'Pengiriman Cepat', 'content' => 'Pengiriman ke seluruh Indonesia dengan packing aman dan proses cepat.'],
    ['title' => 'Customer Service', 'content' => 'Layanan customer service 24 jam siap membantu Anda.'],
    ['title' => 'Tim Kami', 'content' => 'Kami memiliki tim yang berdedikasi: Founder & CEO Ahmad Fauzi, Head of Operations Siti Nurhaliza, Marketing Manager Budi Santoso, dan Customer Service Lead Dewi Lestari.']
];

// Fungsi untuk membersihkan teks sebelum pencarian
function cleanSearchText($text) {
    // Ubah ke lowercase
    $text = strtolower($text);
    // Hapus karakter khusus (opsional, bisa disesuaikan)
    // $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    return $text;
}

// Filter konten berdasarkan pencarian
$searchResults = [];
$hasSearch = !empty($searchAbout);

if ($hasSearch) {
    $searchLower = cleanSearchText($searchAbout);
    // Pecah kata kunci menjadi array (untuk pencarian partial)
    $searchTerms = explode(' ', $searchLower);
    // Hapus kata kosong
    $searchTerms = array_filter($searchTerms);
    
    foreach ($aboutContents as $content) {
        $titleLower = cleanSearchText($content['title']);
        $contentLower = cleanSearchText($content['content']);
        
        $matchFound = false;
        
        // Cek apakah kata kunci lengkap cocok
        if (strpos($titleLower, $searchLower) !== false || strpos($contentLower, $searchLower) !== false) {
            $matchFound = true;
        }
        
        // Jika tidak cocok, coba pecah kata per kata
        if (!$matchFound && !empty($searchTerms)) {
            $matchCount = 0;
            foreach ($searchTerms as $term) {
                if (strlen($term) > 2) { // Abaikan kata pendek (kurang dari 3 huruf)
                    if (strpos($titleLower, $term) !== false || strpos($contentLower, $term) !== false) {
                        $matchCount++;
                    }
                }
            }
            // Jika setidaknya 50% kata kunci cocok, anggap match
            if ($matchCount >= ceil(count($searchTerms) / 2)) {
                $matchFound = true;
            }
        }
        
        // Cek khusus untuk "visi" dan "misi" secara terpisah
        if (!$matchFound) {
            if ((strpos($searchLower, 'visi') !== false && (strpos($titleLower, 'visi') !== false || strpos($contentLower, 'visi') !== false)) ||
                (strpos($searchLower, 'misi') !== false && (strpos($titleLower, 'misi') !== false || strpos($contentLower, 'misi') !== false))) {
                $matchFound = true;
            }
        }
        
        if ($matchFound) {
            // Hindari duplikasi (misal Visi dan Misi terpisah tapi sudah ada Visi & Misi)
            $exists = false;
            foreach ($searchResults as $existing) {
                if ($existing['title'] === $content['title']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $searchResults[] = $content;
            }
        }
    }
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | zelfa store</title>
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

        .nav-link.active {
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
        }

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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 80px 0 60px;
            margin-bottom: 60px;
            position: relative;
            z-index: 10;
        }

        .page-header h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
        }

        /* About Container */
        .about-container {
            position: relative;
            z-index: 10;
        }

        .about-card {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px;
            margin-bottom: 50px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .section-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #ffffff, #a78bfa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 3px;
        }

        .about-text {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            font-size: 15px;
        }

        /* Stats Section */
        .stats-section {
            background: rgba(18, 18, 24, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px;
            margin-bottom: 50px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        /* Feature Card */
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: center;
            height: 100%;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.05);
        }

        .feature-icon {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            transition: all 0.3s;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }

        .feature-card h6 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        /* Team Section */
        .team-section {
            margin-top: 50px;
        }

        .team-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .team-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
        }

        .team-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .team-role {
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
        }

        /* Search Results */
        .search-results {
            background: rgba(18, 18, 24, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-result-item {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-title {
            font-size: 20px;
            font-weight: 700;
            color: #a78bfa;
            margin-bottom: 10px;
        }

        .search-result-content {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            font-size: 14px;
        }

        .highlight {
            background: rgba(99, 102, 241, 0.3);
            color: #ffffff;
            padding: 0 2px;
            border-radius: 4px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
            border-radius: 24px;
            padding: 50px;
            text-align: center;
            margin-top: 50px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cta-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .cta-text {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 25px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            padding: 12px 32px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
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

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icons a {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 40px; }
            .section-title { font-size: 28px; }
            .stat-number { font-size: 36px; }
            .search-container { width: 100%; }
            .search-input:focus { width: 100%; }
            .navbar-nav { margin: 20px 0; }
        }

        @media (max-width: 768px) {
            .page-header h1 { font-size: 32px; }
            .about-card { padding: 30px 20px; }
            .stats-section { padding: 30px 20px; }
            .stat-number { font-size: 28px; }
            .section-title::after { width: 40px; }
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
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
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
            <a class="navbar-brand" href="index.php">
                <span class="logo-text">zelfa store</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: white;"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="books/">Koleksi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about_us.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Kontak</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Cari di halaman Tentang..." id="searchInput" value="<?php echo htmlspecialchars($searchAbout); ?>">
                    </div>
                    
                    <?php if ($isLoggedIn && $userData): ?>
                        <a href="cart.php" class="icon-nav-link position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge"><?php echo $cartItemCount; ?></span>
                        </a>
                        <div class="dropdown">
                            <button class="avatar-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown">
                                <span class="avatar-circle"><?php echo strtoupper(substr($userData['username'], 0, 1)); ?></span>
                                <span><?php echo htmlspecialchars($userData['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                                <li><a class="dropdown-item dropdown-item-custom" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a></li>
                                <li><a class="dropdown-item dropdown-item-custom" href="my_messages.php"><i class="fas fa-envelope me-2"></i> My Message</a></li>
                                <?php if($userData['role'] == 'admin'): ?>
                                <li><a class="dropdown-item dropdown-item-custom" href="admin/"><i class="fas fa-tachometer-alt me-2"></i> Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-custom text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="gate/login/" class="btn-auth btn-login">Masuk</a>
                        <a href="gate/register/" class="btn-auth btn-register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1>📖 Tentang zelfa store</h1>
            <p>Teman Membaca Setiamu</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container about-container">
        
        <?php if ($hasSearch): ?>
            <!-- Hasil Pencarian -->
            <div class="search-results">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="section-title" style="font-size: 24px; margin-bottom: 0;">🔍 Hasil Pencarian</h3>
                    <a href="about_us.php" class="btn-reset" style="background: rgba(255,255,255,0.05); padding: 8px 16px; border-radius: 20px; color: white; text-decoration: none; font-size: 13px;">
                        <i class="fas fa-times me-1"></i> Hapus
                    </a>
                </div>
                <p class="text-white-50 mb-4">Menampilkan hasil untuk: <strong>"<?php echo htmlspecialchars($searchAbout); ?>"</strong></p>
                
                <?php if (empty($searchResults)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-white-50 mb-3"></i>
                        <p class="text-white-50">Tidak ditemukan konten yang sesuai dengan "<strong><?php echo htmlspecialchars($searchAbout); ?></strong>"</p>
                        <p class="text-white-50 small">Coba kata kunci lain seperti: "koleksi", "pengiriman", "customer service", dll.</p>
                        <div class="mt-3">
                            <a href="about_us.php" class="btn-primary-custom" style="padding: 8px 20px; font-size: 14px;">Lihat Semua Konten</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($searchResults as $result): ?>
                    <div class="search-result-item">
                        <div class="search-result-title"><?php echo htmlspecialchars($result['title']); ?></div>
                        <div class="search-result-content">
                            <?php 
                                $content = $result['content'];
                                $searchLower = strtolower($searchAbout);
                                $contentLower = strtolower($content);
                                if (strpos($contentLower, $searchLower) !== false) {
                                    $highlighted = preg_replace('/(' . preg_quote($searchAbout, '/') . ')/i', '<span class="highlight">$1</span>', $content);
                                    echo $highlighted;
                                } else {
                                    echo htmlspecialchars($content);
                                }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- About Card (selalu tampil, kecuali jika ada pencarian dan user ingin melihat hasil saja? Biarkan tetap tampil) -->
        <div class="about-card" id="siapa-kami">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="section-title">Siapa Kami?</h2>
                    <p class="about-text">zelfa store adalah toko buku online yang lahir dari kecintaan terhadap membaca. Kami percaya bahwa setiap buku adalah jendela menuju dunia baru.</p>
                    <p class="about-text mt-3">Berdiri sejak 2025, kami telah melayani ribuan pembaca setia di seluruh Indonesia dengan koleksi buku yang terus berkembang. Kami berkomitmen untuk memberikan pengalaman berbelanja buku yang mudah, nyaman, dan menyenangkan.</p>
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title">Visi & Misi</h2>
                    <p class="about-text"><strong class="text-primary">Visi:</strong> Menjadi toko buku online terpercaya yang mendukung literasi Indonesia.</p>
                    <p class="about-text mt-3"><strong class="text-primary">Misi:</strong> Menyediakan buku berkualitas dengan harga terbaik, memberikan pelayanan yang ramah dan cepat, serta membangun komunitas pembaca yang aktif.</p>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="row text-center">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">5000+</div>
                        <div class="stat-label">Buku Tersedia</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Pembaca Setia</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Partner Penerbit</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Layanan Customer</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Why Choose Us -->
        <h2 class="section-title text-center mb-5" style="display: block; text-align: center;">✨ Mengapa Memilih Kami?</h2>
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-sm-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-book"></i></div>
                    <h6>Koleksi Lengkap</h6>
                    <p>Ribuan judul buku tersedia dari berbagai genre</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-tag"></i></div>
                    <h6>Harga Terbaik</h6>
                    <p>Diskon dan promo menarik setiap hari</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <h6>Pengiriman Cepat</h6>
                    <p>Sampai ke seluruh Indonesia dengan aman</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h6>Customer Service</h6>
                    <p>Siap membantu Anda 24 jam sehari</p>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <h2 class="section-title text-center mb-5" style="display: block; text-align: center;">👥 Tim Kami</h2>
        <div class="row g-4 team-section">
            <div class="col-md-3 col-sm-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="team-name">Ahmad Fauzi</div>
                    <div class="team-role">Founder & CEO</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="team-name">Siti Aisyah</div>
                    <div class="team-role">Head of Operations</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="team-name">Budi Sutikno</div>
                    <div class="team-role">Marketing Manager</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="team-card">
                    <div class="team-avatar"><i class="fas fa-user-circle"></i></div>
                    <div class="team-name">Dewi Lestari</div>
                    <div class="team-role">Customer Service Lead</div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h3 class="cta-title">📚 Mulai Petualangan Membacamu</h3>
            <p class="cta-text">Temukan buku impianmu dan dapatkan penawaran terbaik</p>
            <a href="books/" class="btn-primary-custom">Jelajahi Koleksi <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand">zelfa store</div>
                    <p class="footer-text">Toko buku online terpercaya yang menyediakan berbagai koleksi buku berkualitas untuk menemani perjalanan literasimu.</p>
                    <div class="social-icons">
                    </div>
                </div>
                <div class="col-md-2 mb-4 offset-md-1">
                    <div class="footer-title">Tentang</div>
                    <div class="footer-links">
                        <a href="about_us.php">Tentang Kami</a>
                        <a href="contact.php">Kontak</a>
                        <a href="#">Blog</a>
                        <a href="#">Karir</a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="footer-title">Layanan</div>
                    <div class="footer-links">
                        <a href="cart.php">Keranjang</a>
                        <a href="orders.php">Pesanan</a>
                        <a href="wishlist.php">Wishlist</a>
                        <a href="my_messages.php">Pesan Saya</a>
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
            <hr class="border-secondary mt-3">
            <div class="text-center">
                <p class="footer-text small mb-0">&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
            </div>
        </div>
    </footer>

    <script>
        // Search functionality untuk halaman Tentang
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = this.value.trim();
                    if (searchTerm) {
                        // Redirect ke halaman yang sama dengan parameter search_about
                        window.location.href = 'about_us.php?search_about=' + encodeURIComponent(searchTerm);
                    } else {
                        // Jika kosong, reset ke halaman tentang biasa
                        window.location.href = 'about_us.php';
                    }
                }
            });
        }

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>