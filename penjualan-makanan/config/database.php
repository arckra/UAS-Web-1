<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_penjualan_makanan');

// Konfigurasi aplikasi
// AUTO DETECT BASE URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . "://" . $host . $script . '/');
define('SITE_NAME', 'FoodSales Restaurant');

// Konfigurasi session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (set ke 0 untuk production)
error_reporting(E_ALL);
ini_set('display_errors', 1);