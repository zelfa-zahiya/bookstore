<?php
// Set timezone ke WIB
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

if (!isset($_SESSION['user_id'])) {
    header("Location: gate/login/");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = null;

// Ambil data user
$stmt = $conn->prepare("SELECT id_user, username, email FROM users WHERE id_user = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Ambil semua pesan user berdasarkan email
$stmt = $conn->prepare("SELECT * FROM contacts WHERE email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $userData['email']);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

function formatDate($date) {
    return date('d M Y H:i', strtotime($date));
}

function getStatusBadge($status) {
    switch ($status) {
        case 'unread': 
            return '<span class="badge-status status-pending"><i class="fas fa-clock me-1"></i> Menunggu Balasan</span>';
        case 'read': 
            return '<span class="badge-status status-processing"><i class="fas fa-check-circle me-1"></i> Telah Dibaca</span>';
        case 'replied': 
            return '<span class="badge-status status-delivered"><i class="fas fa-reply-all me-1"></i> Sudah Dibalas</span>';
        default: 
            return '<span class="badge-status status-pending">' . $status . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Saya - zelfa store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; font-family: 'Inter', sans-serif; color: white; }
        
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
        .bg-blur { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(100px); opacity: 0.3; animation: floatBg 20s infinite ease-in-out; }
        .blur-1 { background: #6366f1; top: -150px; right: -150px; }
        .blur-2 { background: #ec4899; bottom: -150px; left: -150px; animation-delay: 5s; }
        .blur-3 { background: #06b6d4; top: 30%; left: 20%; width: 400px; height: 400px; animation-delay: 10s; }
        @keyframes floatBg { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(50px,-50px) scale(1.1); } 66% { transform: translate(-30px,30px) scale(0.9); } }

        .navbar-custom { background: rgba(18,18,24,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 12px 0; position: relative; z-index: 100; }
        .messages-container { position: relative; z-index: 10; max-width: 1000px; margin: 40px auto 60px; padding: 0 20px; }
        
        /* Logo Teks */
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        /* Button Back */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 40px;
            padding: 8px 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: rgba(99,102,241,0.2);
            border-color: #6366f1;
            color: white;
            transform: translateX(-3px);
        }
        
        .page-header { margin-bottom: 30px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-title { flex: 1; }
        .page-title { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #ffffff, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 8px; }
        .page-subtitle { color: rgba(255,255,255,0.5); font-size: 14px; }
        
        .stats-row { display: flex; gap: 20px; margin-bottom: 35px; flex-wrap: wrap; }
        .stat-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; flex: 1; min-width: 150px; border: 1px solid rgba(255,255,255,0.05); text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(99,102,241,0.3); }
        .stat-number { font-size: 28px; font-weight: 700; color: white; }
        .stat-label { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 5px; }
        
        .message-card { background: rgba(18,18,24,0.8); backdrop-filter: blur(10px); border-radius: 20px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; transition: all 0.3s; }
        .message-card:hover { transform: translateY(-3px); border-color: rgba(99,102,241,0.3); }
        .message-header { padding: 18px 25px; background: rgba(0,0,0,0.2); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .message-subject { font-size: 16px; font-weight: 700; }
        .message-subject i { color: #818cf8; margin-right: 8px; }
        .message-date { font-size: 12px; color: rgba(255,255,255,0.5); }
        .message-body { padding: 22px 25px; }
        
        .user-message { background: rgba(99,102,241,0.08); border-radius: 16px; padding: 18px; margin-bottom: 18px; border-left: 4px solid #6366f1; }
        .user-message-label { font-size: 12px; font-weight: 600; color: #818cf8; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .user-message-text { color: rgba(255,255,255,0.85); line-height: 1.6; font-size: 14px; }
        
        .admin-reply { background: rgba(16,185,129,0.08); border-radius: 16px; padding: 18px; border-left: 4px solid #10b981; }
        .admin-reply-label { font-size: 12px; font-weight: 600; color: #34d399; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .admin-reply-text { color: rgba(255,255,255,0.85); line-height: 1.6; font-size: 14px; }
        .reply-date { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 12px; text-align: right; }
        
        .waiting-message { text-align: center; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; font-size: 13px; color: rgba(255,255,255,0.5); }
        
        .badge-status { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; }
        .status-pending { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .status-processing { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
        .status-delivered { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        
        .empty-state { text-align: center; padding: 60px 20px; background: rgba(18,18,24,0.8); border-radius: 24px; }
        .empty-state i { font-size: 70px; color: rgba(255,255,255,0.1); margin-bottom: 20px; }
        .empty-state h4 { font-size: 22px; margin-bottom: 10px; }
        .empty-state p { color: rgba(255,255,255,0.5); margin-bottom: 25px; }
        
        .btn-primary-custom { background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; padding: 12px 28px; font-weight: 600; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary-custom:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(99,102,241,0.4); color: white; }
        
        footer { background: rgba(18,18,24,0.95); border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 60px; }
        
        @media (max-width: 768px) {
            .messages-container { margin-top: 30px; }
            .page-title { font-size: 24px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .message-header { flex-direction: column; align-items: flex-start; }
            .stats-row { flex-direction: column; }
            .navbar-custom .container { flex-direction: column; gap: 15px; }
            .logo-text { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="bg-blur blur-1"></div>
        <div class="bg-blur blur-2"></div>
        <div class="bg-blur blur-3"></div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <!-- LOGO: zelfa store -->
            <div class="logo-text">zelfa store</div>
            
            <!-- BACK BUTTON dengan tanda panah - menggantikan nama user -->
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>

    <div class="messages-container">
        <div class="page-header">
            <div class="header-title">
                <h1 class="page-title"><i class="fas fa-envelope me-2"></i> Pesan Saya</h1>
                <p class="page-subtitle">Riwayat pesan dan balasan dari admin</p>
            </div>
        </div>

        <?php 
        $messagesArray = [];
        while ($row = $messages->fetch_assoc()) { 
            $messagesArray[] = $row;
        }
        $totalMessages = count($messagesArray);
        $repliedCount = count(array_filter($messagesArray, function($m) { return $m['status'] == 'replied'; }));
        $unreadCount = count(array_filter($messagesArray, function($m) { return $m['status'] == 'unread'; }));
        ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalMessages; ?></div>
                <div class="stat-label">Total Pesan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $repliedCount; ?></div>
                <div class="stat-label">Sudah Dibalas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unreadCount; ?></div>
                <div class="stat-label">Menunggu Balasan</div>
            </div>
        </div>

        <?php if (empty($messagesArray)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Belum Ada Pesan</h4>
                <p>Anda belum pernah mengirim pesan kepada kami.</p>
                <a href="contact.php" class="btn-primary-custom">
                    <i class="fas fa-paper-plane"></i> Kirim Pesan Sekarang
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($messagesArray as $message): ?>
            <div class="message-card">
                <div class="message-header">
                    <div class="message-subject">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($message['subjek'] ?? 'Tidak ada subjek'); ?>
                        <?php echo getStatusBadge($message['status']); ?>
                    </div>
                    <div class="message-date">
                        <i class="fas fa-calendar-alt me-1"></i> <?php echo formatDate($message['created_at']); ?>
                    </div>
                </div>
                <div class="message-body">
                    <div class="user-message">
                        <div class="user-message-label">
                            <i class="fas fa-user-circle"></i> Pesan Anda
                        </div>
                        <div class="user-message-text">
                            <?php echo nl2br(htmlspecialchars($message['pesan'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($message['balasan_admin'])): ?>
                    <div class="admin-reply">
                        <div class="admin-reply-label">
                            <i class="fas fa-user-tie"></i> Balasan Admin
                        </div>
                        <div class="admin-reply-text">
                            <?php echo nl2br(htmlspecialchars($message['balasan_admin'])); ?>
                        </div>
                        <?php if (!empty($message['replied_at'])): ?>
                        <div class="reply-date">
                            <i class="fas fa-reply me-1"></i> Dibalas pada <?php echo formatDate($message['replied_at']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="waiting-message">
                        <i class="fas fa-hourglass-half me-1"></i> 
                        Pesan ini belum dibalas. Kami akan segera merespon!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0" style="color: rgba(255,255,255,0.5);">&copy; 2025 zelfa store - Teman Membaca Setiamu</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>