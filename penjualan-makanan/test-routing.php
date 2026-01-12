<?php
/**
 * TEST ROUTING & BASE URL
 * Akses file ini: http://localhost/penjualan-makanan/test-routing.php
 * Untuk cek apakah routing sudah berjalan dengan baik
 */

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Routing & Base URL</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .status-ok { color: #10b981; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3 class='mb-0'>🔍 Test Routing & Configuration</h3>
        </div>
        <div class='card-body'>";

// Test 1: PHP Version
echo "<div class='mb-4'>
        <h5>1. PHP Version</h5>";
$phpVersion = phpversion();
echo "<p>PHP Version: <strong>$phpVersion</strong></p>";
if (version_compare($phpVersion, '7.0.0', '>=')) {
    echo "<p class='status-ok'>✅ PHP Version OK (>= 7.0)</p>";
} else {
    echo "<p class='status-error'>❌ PHP Version terlalu lama, minimal PHP 7.0</p>";
}
echo "</div>";

// Test 2: Base URL Detection
echo "<div class='mb-4'>
        <h5>2. Base URL Detection</h5>";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script = str_replace('/test-routing.php', '', $_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . "://" . $host . $script . '/';

echo "<p>Detected Base URL: <code>$baseUrl</code></p>";
echo "<p class='status-ok'>✅ Base URL berhasil di-detect</p>";
echo "</div>";

// Test 3: Mod Rewrite
echo "<div class='mb-4'>
        <h5>3. Apache Mod Rewrite</h5>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<p class='status-ok'>✅ mod_rewrite AKTIF</p>";
    } else {
        echo "<p class='status-error'>❌ mod_rewrite TIDAK AKTIF - Routing tidak akan jalan!</p>";
        echo "<p><strong>Solusi:</strong> Aktifkan mod_rewrite di httpd.conf</p>";
    }
} else {
    echo "<p class='text-warning'>⚠️ Tidak bisa cek mod_rewrite (bukan masalah)</p>";
}
echo "</div>";

// Test 4: .htaccess File
echo "<div class='mb-4'>
        <h5>4. File .htaccess</h5>";
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo "<p class='status-ok'>✅ File .htaccess DITEMUKAN</p>";
    echo "<p>Location: <code>$htaccessPath</code></p>";
} else {
    echo "<p class='status-error'>❌ File .htaccess TIDAK DITEMUKAN</p>";
    echo "<p><strong>Solusi:</strong> Pastikan file .htaccess ada di root folder</p>";
}
echo "</div>";

// Test 5: Database Connection
echo "<div class='mb-4'>
        <h5>5. Database Connection</h5>";
try {
    $dsn = 'mysql:host=localhost;dbname=sistem_penjualan_makanan;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='status-ok'>✅ Koneksi Database BERHASIL</p>";
    echo "<p>Database: <strong>sistem_penjualan_makanan</strong></p>";
    
    // Cek tabel
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Jumlah tabel: <strong>" . count($tables) . "</strong></p>";
} catch (PDOException $e) {
    echo "<p class='status-error'>❌ Koneksi Database GAGAL</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solusi:</strong> Pastikan database sudah diimport & MySQL running</p>";
}
echo "</div>";

// Test 6: Folder Structure
echo "<div class='mb-4'>
        <h5>6. Folder Structure</h5>";
$folders = ['assets', 'config', 'core', 'modules', 'views'];
$allOk = true;
foreach ($folders as $folder) {
    if (is_dir(__DIR__ . '/' . $folder)) {
        echo "<p>✅ <code>$folder/</code> - OK</p>";
    } else {
        echo "<p class='status-error'>❌ <code>$folder/</code> - TIDAK DITEMUKAN</p>";
        $allOk = false;
    }
}
if ($allOk) {
    echo "<p class='status-ok mt-3'>✅ Semua folder lengkap!</p>";
}
echo "</div>";

// Test 7: Important Files
echo "<div class='mb-4'>
        <h5>7. Important Files</h5>";
$files = [
    'index.php',
    'config/database.php',
    'core/Database.php',
    'modules/auth/login.php',
    'views/header.php',
    'views/footer.php',
    'assets/css/style.css'
];
$allOk = true;
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p>✅ <code>$file</code> - OK</p>";
    } else {
        echo "<p class='status-error'>❌ <code>$file</code> - TIDAK DITEMUKAN</p>";
        $allOk = false;
    }
}
if ($allOk) {
    echo "<p class='status-ok mt-3'>✅ Semua file penting ada!</p>";
}
echo "</div>";

// Test 8: Server Info
echo "<div class='mb-4'>
        <h5>8. Server Information</h5>
        <pre>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "PHP Self: " . $_SERVER['PHP_SELF'] . "\n";
echo "</pre>
      </div>";

// Summary & Next Steps
echo "<div class='alert alert-info'>
        <h5>📋 Next Steps:</h5>
        <ol>
            <li>Jika semua ✅ hijau, coba akses: <a href='$baseUrl' target='_blank'>$baseUrl</a></li>
            <li>Atau langsung ke login: <a href='{$baseUrl}auth/login' target='_blank'>{$baseUrl}auth/login</a></li>
            <li>Jika ada ❌ merah, perbaiki dulu sesuai solusi yang diberikan</li>
            <li>Setelah berhasil, HAPUS file <code>test-routing.php</code> ini</li>
        </ol>
      </div>";

echo "</div></div></div>
</body>
</html>";
?>