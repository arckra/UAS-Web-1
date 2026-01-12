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

$pageTitle = 'Detail Produk';

// Cek apakah file config/database.php ada
$configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($configPath)) {
    die("File database config tidak ditemukan di: $configPath");
}
require_once $configPath;

// Inisialisasi Database
$db = new Database();

// Ambil ID produk dari URL
$id = $_GET['id'] ?? 0;

// Cek apakah produk ada - MENGGUNAKAN PATTERN LAMA
$product = $db->query("SELECT p.*, c.name as category_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.id = :id")
              ->bind(':id', $id)
              ->fetch();

if (!$product) {
    $_SESSION['error_message'] = 'Produk tidak ditemukan!';
    header('Location: ' . BASE_URL . 'produk');
    exit;
}

// Ambil riwayat transaksi - MENGGUNAKAN PATTERN LAMA
$transactions = $db->query("SELECT t.transaction_code, t.created_at, td.quantity, td.subtotal
                            FROM transaction_details td
                            JOIN transactions t ON td.transaction_id = t.id
                            WHERE td.product_id = :id AND t.status = 'completed'
                            ORDER BY t.created_at DESC
                            LIMIT 10")
                    ->bind(':id', $id)
                    ->fetchAll();

// Hitung total terjual - MENGGUNAKAN PATTERN LAMA
$totalSold = $db->query("SELECT SUM(quantity) as total FROM transaction_details 
                        WHERE product_id = :id")
                ->bind(':id', $id)
                ->fetch();

// Path untuk header.php
$headerPath = __DIR__ . '/../../views/header.php';
if (!file_exists($headerPath)) {
    die("File header tidak ditemukan di: $headerPath");
}
require_once $headerPath;
?>

<!-- KONTEN HTML TETAP SAMA -->
<!-- ... sisa kode HTML tetap seperti sebelumnya ... -->

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-box-seam me-2"></i>Detail Produk
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
            <div class="btn-group" role="group">
                <a href="<?= BASE_URL ?>produk/edit/<?= $id ?>" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $id ?>)">
                    <i class="bi bi-trash me-1"></i>Hapus
                </button>
                <a href="<?= BASE_URL ?>user/produk" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Product Details -->
    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8">
            <div class="card fade-in">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="product-icon mb-3">
                                <i class="bi bi-egg-fried" style="font-size: 5rem; color: #0d6efd;"></i>
                            </div>
                            <span class="badge bg-<?= $product['status'] == 'available' ? 'success' : 
                                                 ($product['status'] == 'out_of_stock' ? 'danger' : 'secondary') ?>">
                                <?= $product['status'] == 'available' ? 'Tersedia' : 
                                   ($product['status'] == 'out_of_stock' ? 'Habis' : 'Nonaktif') ?>
                            </span>
                        </div>
                        <div class="col-md-9">
                            <h3 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h3>
                            <h5 class="text-primary mb-4"><?= rupiah($product['price']) ?></h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>ID Produk:</strong></td>
                                            <td><code>#<?= $product['id'] ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kategori:</strong></td>
                                            <td><?= !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : '<span class="text-muted">-</span>' ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Satuan:</strong></td>
                                            <td><?= strtoupper($product['unit'] ?? 'pcs') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Stok:</strong></td>
                                            <td>
                                                <span class="badge <?= $product['stock'] <= ($product['min_stock'] ?? 10) ? 'bg-warning' : 
                                                                   ($product['stock'] > 0 ? 'bg-success' : 'bg-danger') ?>">
                                                    <?= $product['stock'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Min. Stok:</strong></td>
                                            <td><?= $product['min_stock'] ?? 10 ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Harga Beli:</strong></td>
                                            <td><?= !empty($product['cost_price']) ? rupiah($product['cost_price']) : '-' ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if (!empty($product['description'])): ?>
                            <div class="mt-4">
                                <h6 class="fw-bold">Deskripsi:</h6>
                                <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Statistics -->
            <div class="card fade-in" style="animation-delay: 0.1s;">
                <div class="card-header">
                    <i class="bi bi-graph-up me-2"></i>Statistik
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="display-6 fw-bold text-primary">
                            <?= $totalSold['total'] ?: '0' ?>
                        </div>
                        <p class="text-muted mb-0">Total Terjual</p>
                    </div>
                    
                    <hr class="my-3">
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Revenue:</span>
                            <span class="fw-bold">
                                <?php
                                if (!empty($transactions)) {
                                    $revenue = 0;
                                    foreach ($transactions as $trx) {
                                        $revenue += $trx['subtotal'];
                                    }
                                    echo rupiah($revenue);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Profit:</span>
                            <span class="fw-bold text-success">
                                <?php
                                if (!empty($product['cost_price']) && $product['cost_price'] > 0 && !empty($totalSold['total'])) {
                                    $profit = ($product['price'] - $product['cost_price']) * $totalSold['total'];
                                    echo rupiah($profit);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Info -->
            <div class="card mt-3 fade-in" style="animation-delay: 0.2s;">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Informasi
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>ID Produk:</span>
                            <span class="fw-bold">#<?= $product['id'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Tanggal dibuat:</span>
                            <span><?= date('d/m/Y H:i', strtotime($product['created_at'])) ?></span>
                        </li>
                        <?php if (!empty($product['updated_at']) && $product['updated_at'] != '0000-00-00 00:00:00'): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Terakhir update:</span>
                            <span><?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <?php if (!empty($transactions)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card fade-in" style="animation-delay: 0.3s;">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Transaksi Terbaru
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $trx): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($trx['transaction_code']) ?></code></td>
                                    <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                                    <td class="text-end"><?= $trx['quantity'] ?></td>
                                    <td class="text-end"><?= rupiah($trx['subtotal']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Konfirmasi hapus produk
    function confirmDelete(productId) {
        if (confirm('Apakah Anda yakin ingin menghapus produk ini?\n\nProduk yang sudah dihapus tidak dapat dikembalikan.')) {
            window.location.href = '<?= BASE_URL ?>produk/hapus/' + productId;
        }
    }
</script>

<?php
$footerPath = __DIR__ . '/../../views/footer.php';
if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>