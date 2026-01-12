<?php
$pageTitle = 'Dashboard';
$db = new Database();

// Ambil statistik
$totalProduk = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'available'")->fetch()['total'];
$totalKategori = $db->query("SELECT COUNT(*) as total FROM categories WHERE status = 'active'")->fetch()['total'];
$totalPelanggan = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'")->fetch()['total'];

// Transaksi hari ini
$transaksiHariIni = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(grand_total), 0) as pendapatan 
                                FROM transactions 
                                WHERE DATE(created_at) = CURDATE() 
                                AND status = 'completed'")->fetch();

// Produk terlaris
$produkTerlaris = $db->query("SELECT p.name, p.price, c.name as category, 
                              COALESCE(SUM(td.quantity), 0) as total_sold,
                              COALESCE(SUM(td.subtotal), 0) as total_revenue
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id
                              LEFT JOIN transaction_details td ON p.id = td.product_id
                              LEFT JOIN transactions t ON td.transaction_id = t.id AND t.status = 'completed'
                              GROUP BY p.id, p.name, p.price, c.name
                              ORDER BY total_sold DESC
                              LIMIT 5")->fetchAll();

// Produk stok menipis
$stokMenurun = $db->query("SELECT id, name, stock, min_stock 
                           FROM products 
                           WHERE stock <= min_stock 
                           AND status = 'available'
                           ORDER BY stock ASC
                           LIMIT 5")->fetchAll();

// Transaksi terbaru
$transaksiTerbaru = $db->query("SELECT t.*, c.name as customer_name, u.name as user_name
                                FROM transactions t
                                LEFT JOIN customers c ON t.customer_id = c.id
                                LEFT JOIN users u ON t.user_id = u.id
                                WHERE t.status = 'completed'
                                ORDER BY t.created_at DESC
                                LIMIT 5")->fetchAll();

require_once 'views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </h2>
            <p class="text-muted">Selamat datang, <?= $_SESSION['name'] ?>!</p>
        </div>
        <div class="col-auto">
            <div class="text-end">
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i><?= tanggal_indo(date('Y-m-d')) ?>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-clock me-1"></i><span id="jam"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card primary fade-in">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon primary me-3">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Transaksi Hari Ini</div>
                            <h3 class="mb-0 fw-bold"><?= $transaksiHariIni['total'] ?></h3>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-cash-stack me-1"></i>
                            <?= rupiah($transaksiHariIni['pendapatan']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card success fade-in" style="animation-delay: 0.1s;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon success me-3">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Total Produk</div>
                            <h3 class="mb-0 fw-bold"><?= $totalProduk ?></h3>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-tag me-1"></i>
                            <?= $totalKategori ?> Kategori
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card warning fade-in" style="animation-delay: 0.2s;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon warning me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Total Pelanggan</div>
                            <h3 class="mb-0 fw-bold"><?= $totalPelanggan ?></h3>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-person-check me-1"></i>
                            Pelanggan Aktif
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card info fade-in" style="animation-delay: 0.3s;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon info me-3">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Stok Menipis</div>
                            <h3 class="mb-0 fw-bold"><?= count($stokMenurun) ?></h3>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="bi bi-box me-1"></i>
                            Perlu Restock
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Row -->
    <div class="row g-3">
        <!-- Produk Terlaris -->
        <div class="col-xl-6">
            <div class="card fade-in" style="animation-delay: 0.4s;">
                <div class="card-header">
                    <i class="bi bi-trophy me-2"></i>Produk Terlaris
                </div>
                <div class="card-body">
                    <?php if (empty($produkTerlaris)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data penjualan</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Produk</th>
                                        <th class="text-end">Terjual</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($produkTerlaris as $item): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= $item['name'] ?></strong>
                                            <br><small class="text-muted"><?= $item['category'] ?></small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary"><?= $item['total_sold'] ?> item</span>
                                        </td>
                                        <td class="text-end fw-bold"><?= rupiah($item['total_revenue']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Stok Menipis -->
        <div class="col-xl-6">
            <div class="card fade-in" style="animation-delay: 0.5s;">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Produk Stok Menipis
                </div>
                <div class="card-body">
                    <?php if (empty($stokMenurun)): ?>
                        <div class="empty-state">
                            <i class="bi bi-check-circle"></i>
                            <p>Semua stok aman!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Produk</th>
                                        <th class="text-center">Stok</th>
                                        <th class="text-center">Min. Stok</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($stokMenurun as $item): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $item['name'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= $item['stock'] ?></span>
                                        </td>
                                        <td class="text-center"><?= $item['min_stock'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">
                                                <i class="bi bi-exclamation-triangle"></i> Menipis
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Transaksi Terbaru -->
        <div class="col-12">
            <div class="card fade-in" style="animation-delay: 0.6s;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt me-2"></i>Transaksi Terbaru</span>
                    <a href="<?= BASE_URL ?>transaksi" class="btn btn-sm btn-primary">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($transaksiTerbaru)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Kasir</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksiTerbaru as $trx): ?>
                                    <tr>
                                        <td><code><?= $trx['transaction_code'] ?></code></td>
                                        <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                                        <td><?= $trx['customer_name'] ?? '<em>Walk-in</em>' ?></td>
                                        <td><?= $trx['user_name'] ?></td>
                                        <td class="text-end fw-bold"><?= rupiah($trx['grand_total']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Selesai
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update jam real-time
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jam').textContent = `${hours}:${minutes}:${seconds} WIB`;
    }
    
    updateClock();
    setInterval(updateClock, 1000);
</script>

<?php require_once 'views/footer.php'; ?>