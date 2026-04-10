<?php
// Handle CRUD operations
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_book'])) {
        $judul = $_POST['judul'];
        $penulis = $_POST['penulis'];
        $penerbit = $_POST['penerbit'];
        $tahun = $_POST['tahun'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        $id_kategori = $_POST['id_kategori'];
        $deskripsi = $_POST['deskripsi'];
        
        // ========== HANDLE IMAGE UPLOAD ==========
        $gambar = 'default.jpg';
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . '/../../../asset/books_cover/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $judul) . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO books (judul, penulis, penerbit, tahun, harga, stok, id_kategori, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$judul, $penulis, $penerbit, $tahun, $harga, $stok, $id_kategori, $deskripsi, $gambar]);
        
        echo "<script>alert('Book added successfully!'); window.location.href='?page=books';</script>";
    } elseif (isset($_POST['edit_book'])) {
        $id_buku = $_POST['id_buku'];
        $judul = $_POST['judul'];
        $penulis = $_POST['penulis'];
        $penerbit = $_POST['penerbit'];
        
        $harga = $_POST['harga'];
        $harga = str_replace('Rp', '', $harga);
        $harga = str_replace('.', '', $harga);
        $harga = str_replace(',', '.', $harga);
        $harga = (float) $harga;
        
        $stok = $_POST['stok'];
        $id_kategori = $_POST['id_kategori'];
        $deskripsi = $_POST['deskripsi'];
        $gambar_lama = $_POST['gambar_lama'];
        
        if ($harga > 99999999999) {
            $harga = 99999999999;
        }
        
        $gambar = $gambar_lama;
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . '/../../../asset/books_cover/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if ($gambar_lama != 'default.jpg' && file_exists($upload_dir . $gambar_lama)) {
                    unlink($upload_dir . $gambar_lama);
                }
                
                $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $judul) . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE books SET judul=?, penulis=?, penerbit=?, harga=?, stok=?, id_kategori=?, deskripsi=?, gambar=? WHERE id_buku=?");
        $stmt->execute([$judul, $penulis, $penerbit, $harga, $stok, $id_kategori, $deskripsi, $gambar, $id_buku]);
        
        echo "<script>alert('Book updated successfully!'); window.location.href='?page=books';</script>";
    }
}

if (isset($_GET['delete'])) {
    $id_buku = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT gambar FROM books WHERE id_buku = ?");
    $stmt->execute([$id_buku]);
    $book = $stmt->fetch();
    if ($book && $book['gambar'] != 'default.jpg') {
        $upload_dir = __DIR__ . '/../../../asset/books_cover/';
        if (file_exists($upload_dir . $book['gambar'])) {
            unlink($upload_dir . $book['gambar']);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM books WHERE id_buku = ?");
    $stmt->execute([$id_buku]);
    echo "<script>alert('Book deleted successfully!'); window.location.href='?page=books';</script>";
}

// Fetch all books
$stmt = $pdo->query("SELECT b.*, c.nama_kategori FROM books b LEFT JOIN categories c ON b.id_kategori = c.id_kategori ORDER BY b.created_at DESC");
$books = $stmt->fetchAll();

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori");
$categories = $stmt->fetchAll();
?>

<!-- Tambahkan CSS tambahan untuk memperbaiki tampilan tabel -->
<style>
    /* Perbaikan tampilan tabel agar action buttons tidak turun ke bawah */
    .table-responsive {
        overflow-x: auto;
    }
    
    #booksTable {
        width: 100% !important;
        min-width: 800px;
    }
    
    #booksTable td,
    #booksTable th {
        vertical-align: middle !important;
        white-space: nowrap;
    }
    
    /* Khusus kolom action - biarkan tombol inline */
    #booksTable td:last-child {
        white-space: nowrap;
        width: 1%;
    }
    
    #booksTable td:last-child .btn {
        margin-right: 5px;
        display: inline-block;
    }
    
    /* Kolom judul dan penulis bisa sedikit lebih lebar dan wrap jika perlu */
    #booksTable td:nth-child(3),
    #booksTable td:nth-child(4) {
        white-space: normal;
        max-width: 200px;
        min-width: 120px;
    }
    
    /* Kolom harga, stock, kategori */
    #booksTable td:nth-child(6),
    #booksTable td:nth-child(7),
    #booksTable td:nth-child(8) {
        white-space: nowrap;
    }
    
    /* Gambar preview */
    #booksTable td:nth-child(2) img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    /* Responsif untuk mobile */
    @media (max-width: 768px) {
        #booksTable td:nth-child(3),
        #booksTable td:nth-child(4) {
            max-width: 150px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" style="color: #ffffff; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; text-shadow: 2px 2px 8px rgba(0,0,0,0.3); letter-spacing: -0.5px;">
        <i class="fas fa-book me-2" style="color: #fbbf24;"></i> Books Management
    </h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
        <i class="fas fa-plus"></i> Add New Books
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="booksTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">IMAGE</th>
                        <th>TITLE</th>
                        <th>AUTHOR</th>
                        <th>PUBLISHER</th>
                        <th>PRICE</th>
                        <th>STOCK</th>
                        <th>CATEGORY</th>
                        <th style="width: 130px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($books as $book): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <?php 
                            if ($book['gambar'] && $book['gambar'] != 'default.jpg' && file_exists(__DIR__ . '/../../../asset/books_cover/' . $book['gambar'])): ?>
                                <img src="../../../asset/books_cover/<?php echo $book['gambar']; ?>" 
                                     width="45" height="45" 
                                     style="object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/45?text=No+Image" 
                                     width="45" height="45" 
                                     style="border-radius: 8px;">
                            <?php endif; ?>
                          </td>
                        <td><?php echo htmlspecialchars($book['judul'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($book['penulis'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($book['penerbit'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($book['harga'] ?? 0, 0, ',', '.'); ?></td>
                        <td><?php echo $book['stok'] ?? 0; ?></td>
                        <td><?php echo htmlspecialchars($book['nama_kategori'] ?? '-'); ?></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-info" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal"
                                data-id="<?php echo $book['id_buku']; ?>"
                                data-judul="<?php echo htmlspecialchars($book['judul'] ?? ''); ?>"
                                data-penulis="<?php echo htmlspecialchars($book['penulis'] ?? ''); ?>"
                                data-penerbit="<?php echo htmlspecialchars($book['penerbit'] ?? ''); ?>"
                                data-harga="<?php echo $book['harga'] ?? 0; ?>"
                                data-stok="<?php echo $book['stok'] ?? 0; ?>"
                                data-kategori="<?php echo $book['id_kategori'] ?? 0; ?>"
                                data-deskripsi='<?php echo addslashes($book['deskripsi'] ?? ''); ?>'
                                data-gambar="<?php echo $book['gambar'] ?? ''; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?page=books&delete=<?php echo $book['id_buku']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_buku" id="edit_id">
                    <input type="hidden" name="gambar_lama" id="edit_gambar_lama">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Title</label>
                            <input type="text" name="judul" id="edit_judul" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Author</label>
                            <input type="text" name="penulis" id="edit_penulis" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Publisher</label>
                            <input type="text" name="penerbit" id="edit_penerbit" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="harga" id="edit_harga" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stock</label>
                            <input type="number" name="stok" id="edit_stok" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Category</label>
                            <select name="id_kategori" id="edit_kategori" class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_kategori']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Current Image</label><br>
                            <img id="edit_gambar_preview" src="" width="100" height="100" style="object-fit: cover; border-radius: 8px;">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Change Image (Optional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                            <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_book" class="btn btn-primary">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Title</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Author</label>
                            <input type="text" name="penulis" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Publisher</label>
                            <input type="text" name="penerbit" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="harga" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stock</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Category</label>
                            <select name="id_kategori" class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_kategori']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Image</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" required>
                            <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_judul').value = button.getAttribute('data-judul');
            document.getElementById('edit_penulis').value = button.getAttribute('data-penulis');
            document.getElementById('edit_penerbit').value = button.getAttribute('data-penerbit');
            document.getElementById('edit_harga').value = button.getAttribute('data-harga');
            document.getElementById('edit_stok').value = button.getAttribute('data-stok');
            document.getElementById('edit_kategori').value = button.getAttribute('data-kategori');
            document.getElementById('edit_deskripsi').value = button.getAttribute('data-deskripsi');
            document.getElementById('edit_gambar_lama').value = button.getAttribute('data-gambar');
            
            var previewImg = document.getElementById('edit_gambar_preview');
            var gambar = button.getAttribute('data-gambar');
            if (gambar && gambar != '' && gambar != 'default.jpg') {
                previewImg.src = '../../../asset/books_cover/' + gambar;
                previewImg.onerror = function() {
                    this.src = 'https://via.placeholder.com/100?text=No+Image';
                };
            } else {
                previewImg.src = 'https://via.placeholder.com/100?text=No+Image';
            }
        });
    }
});

$(document).ready(function() {
    $('#booksTable').DataTable({
        "pageLength": 10,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        },
        "scrollX": true,
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": [1, 8] }
        ]
    });
});
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>