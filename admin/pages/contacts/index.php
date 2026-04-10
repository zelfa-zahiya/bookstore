<?php
// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Handle contact reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_contact'])) {
    $id_contact = $_POST['id_contact'];
    $balasan_admin = $_POST['balasan_admin'];
    
    $stmt = $pdo->prepare("UPDATE contacts SET balasan_admin = ?, status = 'replied', replied_at = NOW() WHERE id_contact = ?");
    $stmt->execute([$balasan_admin, $id_contact]);
    
    echo "<script>alert('Reply sent successfully!'); window.location.href='?page=contacts';</script>";
}

// Update status to read
if (isset($_GET['mark_read'])) {
    $id_contact = $_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE contacts SET status = 'read' WHERE id_contact = ?");
    $stmt->execute([$id_contact]);
    echo "<script>window.location.href='?page=contacts';</script>";
}

// Delete contact
if (isset($_GET['delete'])) {
    $id_contact = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id_contact = ?");
    $stmt->execute([$id_contact]);
    echo "<script>alert('Contact deleted successfully!'); window.location.href='?page=contacts';</script>";
}

// Fetch all contacts
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
$contacts = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" style="color: #ffffff; font-weight: 800;">
        <i class="fas fa-envelope me-2" style="color: #fbbf24;"></i> Contact Messages
    </h1>
    <div>
        <span class="badge bg-danger me-2">Total: <?php echo count($contacts); ?></span>
    </div>
</div>

<?php if (empty($contacts)): ?>
<div class="alert alert-info text-center">
    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
    <p>No contact messages found</p>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($contacts as $contact): ?>
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-header <?php echo $contact['status'] == 'unread' ? 'bg-warning' : ($contact['status'] == 'replied' ? 'bg-success text-white' : 'bg-light'); ?>">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <!-- PERBAIKAN: Gunakan kolom yang ada di database -->
                        <strong><?php echo htmlspecialchars($contact['name'] ?? 'Guest'); ?></strong> 
                        (<?php echo htmlspecialchars($contact['email'] ?? 'No email'); ?>)
                        <span class="badge bg-secondary ms-2"><?php echo date('d/m/Y H:i', strtotime($contact['created_at'])); ?></span>
                    </div>
                    <div>
                        <span class="badge bg-<?php echo $contact['status'] == 'unread' ? 'danger' : ($contact['status'] == 'read' ? 'info' : 'success'); ?>">
                            <?php echo ucfirst($contact['status']); ?>
                        </span>
                        <?php if ($contact['status'] == 'unread'): ?>
                        <a href="?page=contacts&mark_read=<?php echo $contact['id_contact']; ?>" class="btn btn-sm btn-primary ms-2">
                            <i class="fas fa-check"></i> Mark as Read
                        </a>
                        <?php endif; ?>
                        <a href="?page=contacts&delete=<?php echo $contact['id_contact']; ?>" class="btn btn-sm btn-danger ms-2" onclick="return confirm('Yakin?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- PERBAIKAN: Gunakan 'subjek' bukan 'subject' -->
                <h5 class="card-title">Subject: <?php echo htmlspecialchars($contact['subjek'] ?? '-'); ?></h5>
                <!-- PERBAIKAN: Gunakan 'pesan' bukan 'message' -->
                <p class="card-text"><?php echo nl2br(htmlspecialchars($contact['pesan'] ?? '')); ?></p>
                
                <?php if (!empty($contact['balasan_admin'])): ?>
                <div class="mt-3 p-3 bg-light rounded">
                    <strong><i class="fas fa-reply me-1"></i> Admin Reply:</strong><br>
                    <?php echo nl2br(htmlspecialchars($contact['balasan_admin'])); ?>
                    <?php if (!empty($contact['replied_at'])): ?>
                    <br><small class="text-muted">Replied on: <?php echo date('d/m/Y H:i', strtotime($contact['replied_at'])); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($contact['status'] != 'replied'): ?>
                <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#replyModal<?php echo $contact['id_contact']; ?>">
                    <i class="fas fa-reply"></i> Reply
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal<?php echo $contact['id_contact']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Reply to <?php echo htmlspecialchars($contact['name'] ?? 'Guest'); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_contact" value="<?php echo $contact['id_contact']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <div class="p-2 bg-light rounded"><?php echo htmlspecialchars($contact['email'] ?? '-'); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Original Message:</label>
                            <div class="p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($contact['pesan'] ?? '')); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Reply:</label>
                            <textarea name="balasan_admin" class="form-control" rows="5" required placeholder="Write your reply here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reply_contact" class="btn btn-primary">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>