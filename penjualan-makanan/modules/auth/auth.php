<?php
// penjualan-makanan/modules/auth/auth.php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login() {
        // Jika sudah login, redirect ke dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
            
            $this->db->query('SELECT * FROM users WHERE email = :email AND status = "active"');
            $this->db->bind(':email', $email);
            $user = $this->db->single();
            
            if ($user && password_verify($password, $user->password)) {
                // Set session
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['user_avatar'] = $user->avatar;
                
                // Update last login
                $this->db->query('UPDATE users SET last_login = NOW() WHERE id = :id');
                $this->db->bind(':id', $user->id);
                $this->db->execute();
                
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            } else {
                $error = 'Email atau password salah!';
                require_once 'modules/auth/login.php';
            }
        } else {
            require_once 'modules/auth/login.php';
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }
    
    public static function checkRole($allowedRoles) {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}