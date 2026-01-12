<?php
session_start();

require_once 'user.php';

// Definisikan BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/penjualan-makanan/');
}

$controller = new UserController();

// Tentukan action berdasarkan URL
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'index':
        $controller->index();
        break;
        
    case 'add':
        $controller->add();
        break;
        
    case 'add_process':
        $controller->add_process();
        break;
        
    case 'edit':
        if ($id) {
            $controller->edit($id);
        } else {
            header('Location: ' . BASE_URL . 'user');
        }
        break;
        
    case 'edit_process':
        if ($id) {
            $controller->edit_process($id);
        } else {
            header('Location: ' . BASE_URL . 'user');
        }
        break;
        
    case 'delete':
        if ($id) {
            $controller->delete($id);
        } else {
            header('Location: ' . BASE_URL . 'user');
        }
        break;
        
    case 'profile':
        $controller->profile();
        break;
        
    case 'profile_update':
        $controller->profile_update();
        break;
        
    default:
        header('Location: ' . BASE_URL . 'user');
        break;
}