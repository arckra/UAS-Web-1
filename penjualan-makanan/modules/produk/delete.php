<?php

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Anda harus login terlebih dahulu!';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Definisikan BASE_URL jika belum ada
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/penjualan-makanan/');
}

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

// Cek apakah produk ada
// Cek apakah produk ada - MENGGUNAKAN PATTERN LAMA
$product = $db->query("SELECT * FROM products WHERE id = :id")
                ->bind(':id', $id)
                ->fetch();

if (!$product) {
    $_SESSION['error_message'] = 'Produk tidak ditemukan!';
    header('Location: ' . BASE_URL . 'produk');
    exit;
}

// Cek apakah produk digunakan dalam transaksi - MENGGUNAKAN PATTERN LAMA
$checkTransaction = $db->query("SELECT COUNT(*) as total FROM transaction_details WHERE product_id = :id")
                        ->bind(':id', $id)
                        ->fetch();
if ($checkTransaction['total'] > 0) {
    // Jika ada transaksi, ubah status menjadi inactive
    try {
        $db->query("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = :id", 
                    [':id' => $id]);
        
        // Log aktivitas jika tabel activity_logs ada
        try {
            $logData = [
                ':user_id' => $_SESSION['user_id'],
                ':action' => 'SOFT_DELETE_PRODUCT',
                ':description' => 'Menonaktifkan produk (karena ada transaksi): ' . $product['name'],
                ':created_at' => date('Y-m-d H:i:s')
            ];
            $db->query("INSERT INTO activity_logs (user_id, action, description, created_at) 
                        VALUES (:user_id, :action, :description, :created_at)", $logData);
        } catch (Exception $e) {
            // Skip jika tabel activity_logs tidak ada
        }
        
        $_SESSION['success_message'] = 'Produk telah dinonaktifkan karena terdapat dalam transaksi!';
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Gagal menonaktifkan produk: ' . $e->getMessage();
    }
} else {
    // Jika tidak ada transaksi, hapus permanen
    try {
        // Simpan data untuk log sebelum dihapus
        $productName = $product['name'];
        
        // Hapus produk
        $db->query("DELETE FROM products WHERE id = :id", [':id' => $id]);
        
        // Log aktivitas jika tabel activity_logs ada
        try {
            $logData = [
                ':user_id' => $_SESSION['user_id'],
                ':action' => 'DELETE_PRODUCT',
                ':description' => 'Menghapus produk: ' . $productName,
                ':created_at' => date('Y-m-d H:i:s')
            ];
            $db->query("INSERT INTO activity_logs (user_id, action, description, created_at) 
                       VALUES (:user_id, :action, :description, :created_at)", $logData);
        } catch (Exception $e) {
            // Skip jika tabel activity_logs tidak ada
        }
        
        $_SESSION['success_message'] = 'Produk berhasil dihapus permanen!';
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Gagal menghapus produk: ' . $e->getMessage();
    }
}

// Redirect kembali ke halaman produk
header('Location: ' . BASE_URL . 'produk');
exit;
?>