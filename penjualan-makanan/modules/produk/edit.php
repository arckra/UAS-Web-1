<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Definisikan BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/penjualan-makanan/');
}

$pageTitle = 'Edit Produk';

// Cek apakah file config/database.php ada
$configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($configPath)) {
    die("File database config tidak ditemukan di: $configPath");
}
require_once $configPath;

// Inisialisasi Database
$db = new Database();

// Cek apakah produk ada
$product = $db->query("SELECT * FROM products WHERE id = :id")
              ->bind(':id', $id)
              ->fetch();

if (!$product) {
    $_SESSION['error_message'] = 'Produk tidak ditemukan!';
    header('Location: ' . BASE_URL . 'produk');
    exit;
}

// Ambil kategori untuk dropdown
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")
                 ->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['status'])) {
            throw new Exception("Nama, harga, dan status harus diisi!");
        }

        // Bersihkan data harga
        $price = (float) str_replace(['.', ','], ['', '.'], $_POST['price']);
        $cost_price = !empty($_POST['cost_price']) ? 
                     (float) str_replace(['.', ','], ['', '.'], $_POST['cost_price']) : 
                     null;

        // Query UPDATE dengan metode bind
        $sql = "UPDATE products 
                SET name = :name, 
                    description = :description, 
                    category_id = :category_id,
                    price = :price,
                    cost_price = :cost_price,
                    stock = :stock,
                    min_stock = :min_stock,
                    unit = :unit,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $db->query($sql);
        $stmt->bind(':id', $id);
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
            $_SESSION['success_message'] = 'Produk berhasil diperbarui!';
            header('Location: ' . BASE_URL . 'produk/detail/' . $id);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Gagal memperbarui produk: ' . $e->getMessage();
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
                <i class="bi bi-pencil-square me-2"></i>Edit Produk
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>user/produk">Produk</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>produk/detail/<?= $id ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail
            </a>
        </div>
    </div>

    <!-- Form Edit Produk -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card fade-in">
                <div class="card-header">
                    <i class="bi bi-box me-2"></i>Form Edit Produk
                </div>
                <div class="card-body">
                    <form method="POST" id="editProductForm">
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
                                               value="<?= htmlspecialchars($product['name']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Kategori</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
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
                                                   value="<?= number_format($product['price'], 0, ',', '.') ?>" 
                                                   required oninput="formatCurrency(this)">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cost_price" class="form-label">Harga Beli</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" id="cost_price" name="cost_price" 
                                                   value="<?= $product['cost_price'] ? number_format($product['cost_price'], 0, ',', '.') : '' ?>" 
                                                   oninput="formatCurrency(this)">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                            <input type="number" class="form-control" id="stock" name="stock" 
                                                   value="<?= $product['stock'] ?>" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="min_stock" class="form-label">Minimal Stok</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                                            <input type="number" class="form-control" id="min_stock" name="min_stock" 
                                                   value="<?= $product['min_stock'] ?>" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="unit" class="form-label">Satuan</label>
                                        <select class="form-select" id="unit" name="unit">
                                            <option value="pcs" <?= $product['unit'] == 'pcs' ? 'selected' : '' ?>>PCS</option>
                                            <option value="unit" <?= $product['unit'] == 'unit' ? 'selected' : '' ?>>Unit</option>
                                            <option value="set" <?= $product['unit'] == 'set' ? 'selected' : '' ?>>Set</option>
                                            <option value="pack" <?= $product['unit'] == 'pack' ? 'selected' : '' ?>>Pack</option>
                                            <option value="box" <?= $product['unit'] == 'box' ? 'selected' : '' ?>>Box</option>
                                            <option value="porsi" <?= $product['unit'] == 'porsi' ? 'selected' : '' ?>>Porsi</option>
                                            <option value="gelas" <?= $product['unit'] == 'gelas' ? 'selected' : '' ?>>Gelas</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="available" <?= $product['status'] == 'available' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="out_of_stock" <?= $product['status'] == 'out_of_stock' ? 'selected' : '' ?>>Habis</option>
                                            <option value="inactive" <?= $product['status'] == 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Deskripsi Produk</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="row mt-4">
                            <div class="col">
                                <div class="d-flex justify-content-between">
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Reset Perubahan
                                    </button>
                                    <div>
                                        <a href="<?= BASE_URL ?>produk/detail/<?= $id ?>" 
                                           class="btn btn-outline-danger me-2">
                                            <i class="bi bi-x-circle me-1"></i>Batal
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Perbarui Produk
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card fade-in" style="animation-delay: 0.2s;">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Informasi Produk
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>ID Produk:</span>
                            <span class="fw-bold">#<?= $product['id'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Dibuat:</span>
                            <span><?= date('d/m/Y H:i', strtotime($product['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Terakhir update:</span>
                            <span>
                                <?= $product['updated_at'] ? date('d/m/Y H:i', strtotime($product['updated_at'])) : '-' ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Status Saat Ini:</span>
                            <span>
                                <span class="badge bg-<?= $product['status'] == 'available' ? 'success' : 
                                                     ($product['status'] == 'out_of_stock' ? 'danger' : 'secondary') ?>">
                                    <?= $product['status'] == 'available' ? 'Tersedia' : 
                                       ($product['status'] == 'out_of_stock' ? 'Habis' : 'Nonaktif') ?>
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3 fade-in" style="animation-delay: 0.3s;">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i>Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>produk/detail/<?= $id ?>" class="btn btn-outline-info">
                            <i class="bi bi-eye me-1"></i>Lihat Detail
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="updateStock()">
                            <i class="bi bi-plus-slash-minus me-1"></i>Update Stok
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash me-1"></i>Hapus Produk
                        </button>
                    </div>
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
    }

    // Validasi form
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        const price = document.getElementById('price').value.replace(/\./g, '');
        if (!price || parseInt(price) <= 0) {
            e.preventDefault();
            alert('Harga jual harus diisi dengan nilai yang valid!');
            document.getElementById('price').focus();
            return false;
        }
        
        const stock = document.getElementById('stock').value;
        if (stock < 0) {
            e.preventDefault();
            alert('Stok tidak boleh negatif!');
            document.getElementById('stock').focus();
            return false;
        }
        
        const name = document.getElementById('name').value.trim();
        if (!name) {
            e.preventDefault();
            alert('Nama produk harus diisi!');
            document.getElementById('name').focus();
            return false;
        }
    });

    // Update stok cepat
    function updateStock() {
        const newStock = prompt('Masukkan jumlah stok baru:', '<?= $product['stock'] ?>');
        if (newStock !== null && !isNaN(newStock) && newStock >= 0) {
            document.getElementById('stock').value = newStock;
        }
    }

    // Konfirmasi hapus
    function confirmDelete() {
        if (confirm('Apakah Anda yakin ingin menghapus produk ini?\n\n' +
                   'Tindakan ini tidak dapat dibatalkan!')) {
            window.location.href = '<?= BASE_URL ?>produk/hapus/<?= $id ?>';
        }
    }
</script>

<?php
$footerPath = __DIR__ . '/../../views/footer.php';
if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>