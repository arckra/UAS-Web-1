<?php
require_once 'config/database.php';
require_once 'core/Database.php';

// Helper function untuk redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Helper function untuk cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function untuk cek role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function untuk format rupiah
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper function untuk format tanggal
function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Helper function untuk alert
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// ... kode sebelumnya sama ...

// Ambil URL dari parameter
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Default module dan action
$module = !empty($url[0]) ? $url[0] : 'dashboard';
$action = !empty($url[1]) ? $url[1] : 'index';
$param = !empty($url[2]) ? $url[2] : '';

// ============ PERBAIKAN INI ============
// Handle untuk URL seperti: produk/detail/1
if ($module === 'produk' && !empty($url[1])) {
    // Jika segment kedua adalah angka (ID), maka action adalah detail
    if (is_numeric($url[1])) {
        $action = 'detail';
        $param = $url[1];
    } 
    // Jika segment kedua adalah action (detail, edit, delete, tambah)
    else if (in_array($url[1], ['detail', 'edit', 'delete', 'tambah'])) {
        $action = $url[1];
        $param = !empty($url[2]) ? $url[2] : '';
    }
}
// ============ SAMPAI SINI ============

// Halaman yang tidak perlu login
$publicPages = ['auth'];

// Cek apakah user sudah login
if (!in_array($module, $publicPages) && !isLoggedIn()) {
    redirect('auth/login');
}

// Routing ke module yang sesuai
// Cek apakah module adalah dashboard (special case)
if ($module === 'dashboard') {
    require_once 'views/dashboard.php';
} else {
    // Cek di folder modules
    $modulePath = "modules/{$module}/{$action}.php";
    
    // Handle khusus untuk module 'produk'
    if ($module === 'produk') {
        // CRUD: detail, edit, delete, tambah
        if (in_array($action, ['detail', 'edit', 'delete', 'tambah'])) {
            $modulePath = "modules/produk/{$action}.php";
        }
        // Halaman utama produk ada di modules/user/produk.php
        else if ($action === 'index' || empty($action)) {
            $modulePath = "modules/user/produk.php";
        }
    }
    
    if (file_exists($modulePath)) {
        if ($module === 'produk' && in_array($action, ['detail', 'edit', 'delete']) && $param) {
            $_GET['id'] = $param; // Set ID ke $_GET
        }
        require_once $modulePath;
    } else {
        // 404 Page
        http_response_code(404);
        require_once 'views/header.php';
        echo '<div class="container mt-5 text-center">
                <h1 class="display-1">404</h1>
                <p class="lead">Halaman tidak ditemukan</p>
                <p class="text-muted">Module: <code>' . htmlspecialchars($module) . '</code></p>
                <p class="text-muted">Action: <code>' . htmlspecialchars($action) . '</code></p>
                <p class="text-muted">Param: <code>' . htmlspecialchars($param) . '</code></p>
                <a href="' . BASE_URL . 'dashboard" class="btn btn-primary">Kembali ke Dashboard</a>
              </div>';
        require_once 'views/footer.php';
    }
}