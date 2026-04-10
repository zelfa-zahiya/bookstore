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

echo "<h2>🔍 Debug Data Contacts</h2>";

// Ambil semua data
$result = $conn->query("SELECT id_contact, email, subjek, pesan, balasan_admin, status, replied_at FROM contacts ORDER BY id_contact DESC");
?>

<style>
    body { font-family: Arial, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
    table { border-collapse: collapse; width: 100%; background: #1a1a2e; }
    th { background: #6366f1; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #333; }
    .kosong { background: #ff4444; color: white; padding: 5px 10px; border-radius: 5px; }
    .ada { background: #22c55e; color: white; padding: 5px 10px; border-radius: 5px; }
    h2, h3 { color: #fff; }
</style>

<table border="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
    <tr style="background: #6366f1;">
        <th>ID</th>
        <th>Email</th>
        <th>Subjek</th>
        <th>Pesan</th>
        <th>Balasan Admin</th>
        <th>Status</th>
        <th>Replied At</th>
    </tr>
    <?php 
    $adaBalasan = 0;
    $total = 0;
    while ($row = $result->fetch_assoc()): 
        $total++;
        if (!empty($row['balasan_admin'])) $adaBalasan++;
    ?>
    <tr>
        <td><?php echo $row['id_contact']; ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['subjek']); ?></td>
        <td><?php echo htmlspecialchars(substr($row['pesan'], 0, 50)); ?>...</td>
        <td style="background: <?php echo !empty($row['balasan_admin']) ? '#1a3a1a' : '#3a1a1a'; ?>">
            <?php if (!empty($row['balasan_admin'])): ?>
                <span class="ada">✅ ADA BALASAN</span><br>
                <?php echo htmlspecialchars(substr($row['balasan_admin'], 0, 80)); ?>...
            <?php else: ?>
                <span class="kosong">❌ KOSONG / BELUM DIBALAS</span>
            <?php endif; ?>
        </td>
        <td>
            <?php 
            $badge = '';
            if ($row['status'] == 'unread') $badge = '🟡 Menunggu';
            elseif ($row['status'] == 'read') $badge = '🔵 Dibaca';
            elseif ($row['status'] == 'replied') $badge = '🟢 Dibalas';
            echo $badge;
            ?>
        </td>
        <td><?php echo $row['replied_at'] ?? '-'; ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<h3>📊 Ringkasan:</h3>
<ul>
    <li>Total pesan: <?php echo $total; ?></li>
    <li>Pesan yang sudah dibalas admin: <?php echo $adaBalasan; ?></li>
    <li>Pesan yang belum dibalas: <?php echo $total - $adaBalasan; ?></li>
</ul>

<?php
// Cek email user yang login (jika ada session)
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userStmt = $conn->prepare("SELECT email FROM users WHERE id_user = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    echo "<h3>👤 Email user yang sedang login: <span style='color: #a78bfa;'>" . htmlspecialchars($user['email']) . "</span></h3>";
    
    // Cek apakah ada pesan dengan email tersebut
    $checkStmt = $conn->prepare("SELECT COUNT(*) as total FROM contacts WHERE email = ?");
    $checkStmt->bind_param("s", $user['email']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    echo "<p>📧 Jumlah pesan untuk email ini: <strong>" . $checkRow['total'] . "</strong></p>";
    $checkStmt->close();
    
    // Cek pesan yang sudah dibalas untuk user ini
    $repliedStmt = $conn->prepare("SELECT COUNT(*) as total FROM contacts WHERE email = ? AND balasan_admin IS NOT NULL AND balasan_admin != ''");
    $repliedStmt->bind_param("s", $user['email']);
    $repliedStmt->execute();
    $repliedResult = $repliedStmt->get_result();
    $repliedRow = $repliedResult->fetch_assoc();
    echo "<p>✅ Pesan yang sudah dibalas untuk email ini: <strong>" . $repliedRow['total'] . "</strong></p>";
    $repliedStmt->close();
}

$conn->close();
?>