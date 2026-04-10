<?php
require_once '../config/config.php';
requireAdmin();

// Get current page from URL parameter, default to dashboard
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'books', 'categories', 'orders', 'users', 'contacts'];
$page = in_array($page, $allowed_pages) ? $page : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #ec4899;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f1f5f9;
            --gray: #64748b;
            --card-bg: rgba(255, 255, 255, 0.98);
            --sidebar-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
        }

        /* Premium Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            width: 280px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 20px 0 40px rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 30px 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            position: relative;
        }

        .logo-wrapper {
            position: relative;
            display: inline-block;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logo-icon i {
            font-size: 2rem;
            color: white;
        }

        .sidebar-header h3 {
            color: white;
            font-weight: 800;
            margin: 0;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #fff, var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            margin: 8px 0 0 0;
        }

        .sidebar-sticky {
            padding: 0 15px;
            flex: 1;
        }

        .sidebar .nav-item {
            margin-bottom: 8px;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 18px;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar .nav-link:hover::before {
            left: 100%;
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            width: 24px;
            font-size: 1.1rem;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(99, 102, 241, 0.2);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4);
        }

        /* Logout Button di Sidebar Bawah - Pojok Kiri */
        .sidebar-logout {
            padding: 20px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-sidebar-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 12px;
            color: #fca5a5;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(239, 68, 68, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logout-sidebar-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(239, 68, 68, 0.2), transparent);
            transition: left 0.5s;
        }

        .logout-sidebar-btn:hover::before {
            left: 100%;
        }

        .logout-sidebar-btn:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            transform: translateX(5px);
            border-color: transparent;
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4);
        }

        .logout-sidebar-btn i {
            font-size: 1.2rem;
        }

        .logout-sidebar-btn span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .logout-sidebar-btn .arrow-icon {
            margin-left: auto;
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .logout-sidebar-btn:hover .arrow-icon {
            transform: translateX(5px);
        }

        /* Top Navbar Premium */
        .top-navbar {
            position: fixed;
            top: 20px;
            right: 20px;
            left: 300px;
            z-index: 99;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 12px 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s;
        }

        .search-bar {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-bar i {
            color: var(--gray);
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            width: 200px;
            font-size: 0.9rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .user-profile:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info h6 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .user-info p {
            margin: 0;
            font-size: 0.7rem;
            color: var(--gray);
        }

        /* Main Content */
        main {
            margin-left: 300px;
            margin-top: 100px;
            padding: 0 30px 30px 30px;
            transition: all 0.4s;
        }

        .content-wrapper {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Premium Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 25px;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        /* Modern Table */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: var(--dark);
            font-weight: 700;
            padding: 15px;
            border: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table tbody tr:hover {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.05), rgba(236, 72, 153, 0.05));
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Premium Modal */
        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 25px;
            border: none;
        }

        /* Form Controls Premium */
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
            font-size: 0.9rem;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        /* Action Buttons */
        .btn-icon {
            width: 35px;
            height: 35px;
            padding: 0;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            margin: 0 3px;
            border: none;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .premium-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-top-color: var(--primary);
            border-right-color: var(--secondary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Scrollbar Premium */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .top-navbar {
                left: 20px;
                right: 20px;
            }
            
            main {
                margin-left: 0;
                padding: 20px;
                margin-top: 100px;
            }
            
            .stat-card {
                margin-bottom: 20px;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        /* Page Title */
        .page-title-nav {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="premium-spinner"></div>
    </div>

    <!-- Premium Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-wrapper">
                <div class="logo-icon">
                    <i class="fas fa-book-open"></i>
                </div>
            </div>
            <h3>BookStore Admin</h3>
            <p>Zelfa Bookstore</p>
        </div>
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'books' ? 'active' : ''; ?>" href="?page=books">
                        <i class="fas fa-book"></i> Books Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'categories' ? 'active' : ''; ?>" href="?page=categories">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'orders' ? 'active' : ''; ?>" href="?page=orders">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'users' ? 'active' : ''; ?>" href="?page=users">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $page == 'contacts' ? 'active' : ''; ?>" href="?page=contacts">
                        <i class="fas fa-envelope"></i> Contacts
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Logout Button di Pojok Kiri Bawah Sidebar -->
        <div class="sidebar-logout">
            <a href="../config/logout.php" class="logout-sidebar-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
                <i class="fas fa-arrow-right arrow-icon"></i>
            </a>
        </div>
    </div>

    <!-- Premium Top Navbar - Search Bar HANYA untuk halaman selain Dashboard -->
    <div class="top-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <?php if ($page == 'dashboard'): ?>
                <!-- Dashboard: Tampilkan judul, bukan search bar -->
                <div>
                    <h5 class="page-title-nav mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Dashboard Overview
                    </h5>
                </div>
            <?php else: ?>
                <!-- Halaman lain: Tampilkan search bar -->
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search <?php echo ucfirst($page); ?>...">
                </div>
            <?php endif; ?>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info d-none d-md-block">
                    <h6>Admin</h6>
                    <p>Administrator</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        <div class="content-wrapper">
            <?php
            // Include the requested page
            $page_file = "pages/{$page}/index.php";
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo "<div class='alert alert-danger'>Page not found!</div>";
            }
            ?>
        </div>
    </main>

    <!-- Mobile Menu Button -->
    <button class="btn btn-primary d-md-none" id="menuToggle" style="position: fixed; bottom: 30px; right: 30px; z-index: 999; border-radius: 15px; width: 50px; height: 50px; padding: 0; background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <i class="fas fa-bars"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Mobile menu toggle
        $('#menuToggle').click(function() {
            $('#sidebar').toggleClass('show');
            $(this).find('i').toggleClass('fa-bars fa-times');
        });

        // Close sidebar on outside click
        $(document).click(function(event) {
            if ($(window).width() <= 768) {
                if (!$(event.target).closest('#sidebar').length && !$(event.target).closest('#menuToggle').length) {
                    $('#sidebar').removeClass('show');
                    $('#menuToggle').find('i').removeClass('fa-times').addClass('fa-bars');
                }
            }
        });

        // Loading animation
        $(document).ajaxStart(function() {
            $('.loading-overlay').fadeIn();
        }).ajaxStop(function() {
            $('.loading-overlay').fadeOut();
        });

        // Search functionality - ONLY for pages with search bar
        $('#globalSearch').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('.table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    </script>
</body>
</html>