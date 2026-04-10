<?php
// Handle category CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $nama_kategori = $_POST['nama_kategori'];
        $deskripsi = $_POST['deskripsi'];
        
        $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)");
        $stmt->execute([$nama_kategori, $deskripsi]);
        
        echo "<script>alert('Category added successfully!'); window.location.href='?page=categories';</script>";
    } elseif (isset($_POST['edit_category'])) {
        $id_kategori = $_POST['id_kategori'];
        $nama_kategori = $_POST['nama_kategori'];
        $deskripsi = $_POST['deskripsi'];
        
        $stmt = $pdo->prepare("UPDATE categories SET nama_kategori=?, deskripsi=? WHERE id_kategori=?");
        $stmt->execute([$nama_kategori, $deskripsi, $id_kategori]);
        
        echo "<script>alert('Category updated successfully!'); window.location.href='?page=categories';</script>";
    }
}

// DELETE DENGAN PENGECEKAN STOK DARI TABEL BOOKS
if (isset($_GET['delete'])) {
    $id_kategori = $_GET['delete'];
    
    // CEK APAKAH MASIH ADA BUKU DENGAN STOK > 0 DI KATEGORI INI
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE id_kategori = ? AND stok > 0");
    $stmt->execute([$id_kategori]);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        // MASIH ADA BUKU YANG STOKNYA > 0 -> TIDAK BISA HAPUS KATEGORI
        echo "<script>alert('Tidak bisa menghapus kategori! Masih terdapat " . $result['total'] . " buku dengan stok > 0 di kategori ini. Hapus atau kurangi stok buku menjadi 0 terlebih dahulu.'); window.location.href='?page=categories';</script>";
    } else {
        // SEMUA BUKU DI KATEGORI INI STOKNYA 0 -> BISA HAPUS KATEGORI
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id_kategori = ?");
        $stmt->execute([$id_kategori]);
        echo "<script>alert('Category deleted successfully!'); window.location.href='?page=categories';</script>";
    }
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll();
?>

<style>
    /* Warna latar belakang mengikuti halaman Orders Admin */
    .categories-container {
        position: relative;
        z-index: 10;
        padding: 20px;
    }
    
    .main-card {
        background: white;
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .card-header-custom {
        background: rgba(0,0,0,0.02);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 20px 25px;
    }
    
    .card-header-custom h2 {
        color: #1a1a2e !important;
        margin: 0;
        font-size: 24px;
    }
    
    .card-header-custom small {
        color: rgba(0,0,0,0.5) !important;
        font-size: 13px;
    }
    
    .table-custom {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-custom thead th {
        background: rgba(0,0,0,0.02);
        padding: 15px;
        font-weight: 600;
        font-size: 13px;
        color: rgba(0,0,0,0.7);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .table-custom tbody tr {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    
    .table-custom tbody tr:hover {
        background: rgba(99,102,241,0.03);
    }
    
    .table-custom td {
        padding: 16px 15px;
        vertical-align: middle;
        color: rgba(0,0,0,0.8);
    }
    
    .btn-add {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        border: none;
        border-radius: 40px;
        padding: 10px 24px;
        font-weight: 600;
        color: white;
        transition: all 0.3s;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(99,102,241,0.4);
        color: white;
    }
    
    .btn-edit {
        background: rgba(59,130,246,0.15);
        color: #2563eb;
        border: 1px solid rgba(59,130,246,0.3);
        border-radius: 30px;
        padding: 6px 14px;
        font-size: 12px;
        transition: all 0.3s;
    }
    
    .btn-edit:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background: rgba(239,68,68,0.15);
        color: #dc2626;
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 30px;
        padding: 6px 14px;
        font-size: 12px;
        transition: all 0.3s;
    }
    
    .btn-delete:hover {
        background: #ef4444;
        color: white;
        transform: translateY(-2px);
    }
    
    .modal-content-custom {
        background: white;
        border-radius: 24px;
        border: 1px solid rgba(0,0,0,0.1);
    }
    
    .modal-header-custom {
        border-bottom: 1px solid rgba(0,0,0,0.1);
        padding: 20px 25px;
    }
    
    .modal-header-custom .modal-title {
        color: #1a1a2e;
        font-weight: 700;
    }
    
    .modal-footer-custom {
        border-top: 1px solid rgba(0,0,0,0.1);
        padding: 15px 25px;
    }
    
    .form-control-custom {
        background: rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.1);
        color: #1a1a2e;
        padding: 12px 16px;
        border-radius: 12px;
        width: 100%;
        transition: all 0.3s;
    }
    
    .form-control-custom:focus {
        outline: none;
        border-color: #6366f1;
        background: white;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    
    .form-label-custom {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 8px;
        display: block;
    }
    
    .btn-cancel {
        background: rgba(0,0,0,0.05);
        border-radius: 30px;
        padding: 8px 20px;
        color: #1a1a2e;
        border: none;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: rgba(0,0,0,0.1);
    }
    
    .btn-save {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        border: none;
        border-radius: 30px;
        padding: 8px 24px;
        color: white;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(99,102,241,0.4);
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: rgba(0,0,0,0.7);
        padding: 10px;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        background: rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 30px;
        padding: 8px 16px;
        color: #1a1a2e;
    }
    
    .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: #6366f1;
    }
    
    .dataTables_wrapper .dataTables_filter input::placeholder {
        color: rgba(0,0,0,0.4);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px;
        margin: 0 3px;
        border-radius: 10px;
        background: rgba(0,0,0,0.05);
        border: none;
        color: rgba(0,0,0,0.7);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #6366f1;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        color: white;
    }
    
    .dataTables_wrapper .dataTables_length select {
        background: rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 4px 8px;
        color: #1a1a2e;
    }
    
    .badge-category {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: linear-gradient(135deg, #6366f1, #ec4899);
        color: white;
    }
</style>

<div class="categories-container">
    <div class="main-card">
        <div class="card-header-custom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-tags me-2" style="color: #6366f1;"></i> Categories Management
                    </h2>
                    <small>Kelola semua kategori buku</small>
                </div>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-1"></i> Add Category
                </button>
            </div>
        </div>
        
        <div class="table-responsive p-3">
            <table class="table table-custom" id="categoriesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($categories as $category): 
                    ?>
                    <tr>
                        <td><span class="badge-category">#<?php echo $no++; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($category['nama_kategori']); ?></strong></td>
                        <td><?php echo htmlspecialchars($category['deskripsi']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                        <td>
                            <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id_kategori']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?page=categories&delete=<?php echo $category['id_kategori']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['id_kategori']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content modal-content-custom">
                                <div class="modal-header modal-header-custom">
                                    <h5 class="modal-title">
                                        <i class="fas fa-edit me-2" style="color: #f59e0b;"></i>
                                        Edit Category
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body" style="padding: 25px;">
                                        <input type="hidden" name="id_kategori" value="<?php echo $category['id_kategori']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label-custom">Category Name</label>
                                            <input type="text" name="nama_kategori" class="form-control-custom" value="<?php echo htmlspecialchars($category['nama_kategori']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label-custom">Description</label>
                                            <textarea name="deskripsi" class="form-control-custom" rows="3"><?php echo htmlspecialchars($category['deskripsi']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer modal-footer-custom">
                                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="edit_category" class="btn-save">Update Category</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2" style="color: #10b981;"></i>
                    Add New Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding: 25px;">
                    <div class="mb-3">
                        <label class="form-label-custom">Category Name</label>
                        <input type="text" name="nama_kategori" class="form-control-custom" placeholder="Enter category name..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Description</label>
                        <textarea name="deskripsi" class="form-control-custom" rows="3" placeholder="Enter category description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn-save">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        "pageLength": 10,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "emptyTable": "No category data available",
            "zeroRecords": "No matching data found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "→",
                "previous": "←"
            }
        },
        "order": [[0, 'asc']]
    });
});
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>