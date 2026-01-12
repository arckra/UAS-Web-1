<?php

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Definisikan BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/penjualan-makanan/');
}

$pageTitle = 'Tambah Produk';

// Cek apakah file config/database.php ada
$configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($configPath)) {
    die("File database config tidak ditemukan di: $configPath");
}
require_once $configPath;

// Inisialisasi Database
$db = new Database();

// Ambil kategori untuk dropdown
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")
                 ->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $required_fields = ['name', 'price', 'stock', 'status'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field $field harus diisi!");
            }
        }

        // Bersihkan data harga
        $price = (float) str_replace(['.', ','], ['', '.'], $_POST['price']);
        $cost_price = !empty($_POST['cost_price']) ? 
                     (float) str_replace(['.', ','], ['', '.'], $_POST['cost_price']) : 
                     null;

        // Query INSERT
        $sql = "INSERT INTO products (name, description, category_id, price, cost_price, stock, min_stock, unit, status, created_at, updated_at)
                VALUES (:name, :description, :category_id, :price, :cost_price, :stock, :min_stock, :unit, :status, NOW(), NOW())";

        $stmt = $db->query($sql);
        $stmt->bind(':name', trim($_POST['name']));
        $stmt->bind(':description', trim($_POST['description'] ?? ''));
        $stmt->bind(':category_id', !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
        $stmt->bind(':price', $price);
        $stmt->bind(':cost_price', $cost_price);
        $stmt->bind(':stock', (int)$_POST['stock']);
        $stmt->bind(':min_stock', !empty($_POST['min_stock']) ? (int)$_POST['min_stock'] : 10);
        $stmt->bind(':unit', $_POST['unit'] ?? 'pcs');
        $stmt->bind(':status', $_POST['status']);
        
        if ($stmt->execute()) {
            $productId = $db->lastInsertId();
            
            $_SESSION['success_message'] = 'Produk berhasil ditambahkan!';
            header('Location: ' . BASE_URL . 'produk/detail/' . $productId);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Gagal menambahkan produk: ' . $e->getMessage();
    }
}

// Path untuk header.php
$headerPath = __DIR__ . '/../../views/header.php';
if (!file_exists($headerPath)) {
    die("File header tidak ditemukan di: $headerPath");
}
require_once $headerPath;
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-plus-circle me-2"></i>Tambah Produk Baru
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>user/produk">Produk</a></li>
                    <li class="breadcrumb-item active">Tambah Produk</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>user/produk" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Form Tambah Produk -->
    <div class="card fade-in">
        <div class="card-header">
            <i class="bi bi-box me-2"></i>Form Produk Baru
        </div>
        <div class="card-body">
            <form method="POST" id="productForm">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Kolom Kiri -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box"></i></span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required placeholder="Contoh: Nasi Goreng Spesial" 
                                       value="<?= $_POST['name'] ?? '' ?>">
                            </div>
                            <div class="form-text">Nama produk yang akan ditampilkan</div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                        <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="price" name="price" 
                                           required placeholder="0" 
                                           value="<?= $_POST['price'] ?? '' ?>"
                                           oninput="formatCurrency(this)">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost_price" class="form-label">Harga Beli</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="cost_price" name="cost_price" 
                                           placeholder="0" 
                                           value="<?= $_POST['cost_price'] ?? '' ?>"
                                           oninput="formatCurrency(this)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                    <input type="number" class="form-control" id="stock" name="stock" 
                                           value="<?= $_POST['stock'] ?? 0 ?>" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="min_stock" class="form-label">Minimal Stok</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                                    <input type="number" class="form-control" id="min_stock" name="min_stock" 
                                           value="<?= $_POST['min_stock'] ?? 10 ?>" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Satuan</label>
                                <select class="form-select" id="unit" name="unit">
                                    <option value="pcs" <?= (isset($_POST['unit']) && $_POST['unit'] == 'pcs') ? 'selected' : 'selected' ?>>PCS</option>
                                    <option value="unit" <?= (isset($_POST['unit']) && $_POST['unit'] == 'unit') ? 'selected' : '' ?>>Unit</option>
                                    <option value="set" <?= (isset($_POST['unit']) && $_POST['unit'] == 'set') ? 'selected' : '' ?>>Set</option>
                                    <option value="pack" <?= (isset($_POST['unit']) && $_POST['unit'] == 'pack') ? 'selected' : '' ?>>Pack</option>
                                    <option value="box" <?= (isset($_POST['unit']) && $_POST['unit'] == 'box') ? 'selected' : '' ?>>Box</option>
                                    <option value="porsi" <?= (isset($_POST['unit']) && $_POST['unit'] == 'porsi') ? 'selected' : '' ?>>Porsi</option>
                                    <option value="gelas" <?= (isset($_POST['unit']) && $_POST['unit'] == 'gelas') ? 'selected' : '' ?>>Gelas</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available" <?= (isset($_POST['status']) && $_POST['status'] == 'available') ? 'selected' : 'selected' ?>>Tersedia</option>
                                    <option value="out_of_stock" <?= (isset($_POST['status']) && $_POST['status'] == 'out_of_stock') ? 'selected' : '' ?>>Habis</option>
                                    <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi Produk</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" placeholder="Deskripsi singkat tentang produk..."><?= $_POST['description'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="row mt-4">
                    <div class="col">
                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset Form
                            </button>
                            <div>
                                <a href="<?= BASE_URL ?>produk" class="btn btn-outline-danger me-2">
                                    <i class="bi bi-x-circle me-1"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Simpan Produk
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Card -->
    <div class="card mt-4 fade-in" style="animation-delay: 0.2s;">
        <div class="card-header">
            <i class="bi bi-eye me-2"></i>Preview Produk
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="preview-icon">
                        <i class="bi bi-egg-fried" style="font-size: 4rem; color: #6c757d;"></i>
                    </div>
                </div>
                <div class="col-md-9">
                    <table class="table table-sm">
                        <tr>
                            <td width="30%"><strong>Nama Produk:</strong></td>
                            <td><span id="previewName">-</span></td>
                        </tr>
                        <tr>
                            <td><strong>Kategori:</strong></td>
                            <td><span id="previewCategory">-</span></td>
                        </tr>
                        <tr>
                            <td><strong>Harga Jual:</strong></td>
                            <td><span id="previewPrice">Rp 0</span></td>
                        </tr>
                        <tr>
                            <td><strong>Stok:</strong></td>
                            <td><span id="previewStock">0</span> <span id="previewUnit">pcs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span class="badge bg-success" id="previewStatus">Tersedia</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Format currency input
    function formatCurrency(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        input.value = value;
        
        // Update preview
        if (input.id === 'price') {
            document.getElementById('previewPrice').textContent = 'Rp ' + (value || '0');
        }
    }

    // Update preview secara real-time
    document.getElementById('name').addEventListener('input', function() {
        document.getElementById('previewName').textContent = this.value || '-';
    });

    document.getElementById('category_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('previewCategory').textContent = selectedOption.text || '-';
    });

    document.getElementById('stock').addEventListener('input', function() {
        document.getElementById('previewStock').textContent = this.value || '0';
    });

    document.getElementById('unit').addEventListener('change', function() {
        document.getElementById('previewUnit').textContent = this.value;
    });

    document.getElementById('status').addEventListener('change', function() {
        const status = this.value;
        const badge = document.getElementById('previewStatus');
        badge.textContent = status === 'available' ? 'Tersedia' : 
                          status === 'out_of_stock' ? 'Habis' : 'Nonaktif';
        badge.className = status === 'available' ? 'badge bg-success' :
                         status === 'out_of_stock' ? 'badge bg-danger' : 'badge bg-secondary';
    });

    // Validasi form sebelum submit
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const price = document.getElementById('price').value.replace(/\./g, '');
        if (!price || parseInt(price) <= 0) {
            e.preventDefault();
            alert('Harga jual harus diisi dengan nilai yang valid!');
            document.getElementById('price').focus();
            return false;
        }
        
        const name = document.getElementById('name').value.trim();
        if (!name) {
            e.preventDefault();
            alert('Nama produk harus diisi!');
            document.getElementById('name').focus();
            return false;
        }
        
        const stock = document.getElementById('stock').value;
        if (stock < 0) {
            e.preventDefault();
            alert('Stok tidak boleh negatif!');
            document.getElementById('stock').focus();
            return false;
        }
    });
</script>

<?php
$footerPath = __DIR__ . '/../../views/footer.php';
if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>