<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>dashboard">
                <i class="bi bi-shop fs-4 me-2"></i>
                <span class="fw-bold"><?= SITE_NAME ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= $module == 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="masterData" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-database me-1"></i>Master Data
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>produk"><i class="bi bi-box-seam me-2"></i>Produk</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>user/user"><i class="bi bi-people me-2"></i>Pelanggan</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $module == 'transaksi' ? 'active' : '' ?>" href="<?= BASE_URL ?>transaksi">
                            <i class="bi bi-cart3 me-1"></i>Transaksi
                        </a>
                    </li>
                    
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $module == 'laporan' ? 'active' : '' ?>" href="<?= BASE_URL ?>laporan">
                            <i class="bi bi-graph-up me-1"></i>Laporan
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $module == 'user' ? 'active' : '' ?>" href="<?= BASE_URL ?>user">
                            <i class="bi bi-person-gear me-1"></i>User
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <img src="<?= BASE_URL ?>assets/gambar/<?= $_SESSION['avatar'] ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                            <span><?= $_SESSION['name'] ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">
                                <div class="text-muted small"><?= ucfirst($_SESSION['role']) ?></div>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>user/profile"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Alert -->
    <?php 
    $alert = getAlert();
    if ($alert): 
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $alert['type'] == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>