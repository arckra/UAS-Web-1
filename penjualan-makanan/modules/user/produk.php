<?php
$pageTitle = 'Manajemen Produk';
$db = new Database();

// Ambil parameter pencarian dan filter
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';

// Konstruksi query dasar
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

$params = [];

// Tambahkan kondisi pencarian
if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.code LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Tambahkan filter kategori
if (!empty($category_id) && $category_id != 'all') {
    $query .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// Tambahkan filter status
if (!empty($status) && $status != 'all') {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
}

// Tambahkan sorting dan pagination
$query .= " ORDER BY p.created_at DESC";

// Ambil semua produk
$products = $db->query($query, $params)->fetchAll();

// Ambil kategori untuk dropdown filter
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Ambil statistik produk
$totalProducts = count($products);
$activeProducts = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'available'")->fetch()['total'];
$lowStockProducts = $db->query("SELECT COUNT(*) as total FROM products WHERE stock <= min_stock AND status = 'available'")->fetch()['total'];

require_once 'views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-box-seam me-2"></i>Manajemen Produk
            </h2>
            <p class="text-muted">Kelola produk dan stok barang</p>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>produk/tambah" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Produk
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4 fade-in" style="animation-delay: 0.4s;">
        <div class="card-header">
            <i class="bi bi-funnel me-2"></i>Filter Produk
        </div>
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>user/produk" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari Produk</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" 
                                placeholder="Nama, kode, atau deskripsi..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="all">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all">Semua Status</option>
                        <option value="available" <?= $status == 'available' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="out_of_stock" <?= $status == 'out_of_stock' ? 'selected' : '' ?>>Habis</option>
                        <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="<?= BASE_URL ?>user/produk" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card fade-in" style="animation-delay: 0.5s;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table me-2"></i>Daftar Produk</span>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
                <button type="button" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Belum ada produk</p>
                    <a href="<?= BASE_URL ?>produk/tambah" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Produk Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($products as $product): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                    <?php if (!empty($product['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $product['category_name'] ?? '<span class="text-muted">-</span>' ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $product['stock'] <= $product['min_stock'] ? 'bg-warning' : ($product['stock'] > 0 ? 'bg-success' : 'bg-danger') ?>">
                                        <?= $product['stock'] ?>
                                    </span>
                                    <?php if ($product['stock'] <= $product['min_stock']): ?>
                                        <br><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Min: <?= $product['min_stock'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold"><?= rupiah($product['price']) ?></td>
                                <td class="text-center">
                                    <?php if ($product['status'] == 'available'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Tersedia
                                        </span>
                                    <?php elseif ($product['status'] == 'out_of_stock'): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle"></i> Habis
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-slash-circle"></i> Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= BASE_URL ?>produk/detail/<?= $product['id'] ?>" 
                                            class="btn btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>produk/edit/<?= $product['id'] ?>" 
                                            class="btn btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>produk/delete/<?= $product['id'] ?>" 
                                            class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($products)): ?>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col">
                    <small class="text-muted">
                        Menampilkan <?= count($products) ?> dari <?= $totalProducts ?> produk
                    </small>
                </div>
                <div class="col-auto">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Konfirmasi hapus produk
    function confirmDelete(productId) {
        if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
            window.location.href = '<?= BASE_URL ?>produk/hapus/' + productId;
        }
    }
    
    // Quick status change
    function changeStatus(productId, newStatus) {
        if (confirm('Ubah status produk?')) {
            fetch('<?= BASE_URL ?>api/produk/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: productId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal mengubah status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }
    }
    
    // Auto-refresh stok status
    setInterval(() => {
        const lowStockBadges = document.querySelectorAll('.badge.bg-warning');
        lowStockBadges.forEach(badge => {
            badge.classList.toggle('bg-warning');
            badge.classList.toggle('bg-danger');
        });
    }, 1000);
</script>

<?php require_once 'views/footer.php'; ?>