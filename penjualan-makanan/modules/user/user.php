<?php

class User {
    private $db;
    
    public function __construct() {
        // Cek apakah sudah login
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit();
        }
        
        // Definisikan BASE_URL
        if (!defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost/penjualan-makanan/');
        }
        
        // Cek apakah file config/database.php ada
        $configPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($configPath)) {
            die("File database config tidak ditemukan di: $configPath");
        }
        require_once $configPath;
        
        // Inisialisasi Database (sesuai dengan pola kode Anda sebelumnya)
        $this->db = new Database();
    }
    
    // Method: GET /user
    public function index() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Query dengan filter
        $where = "WHERE 1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (name LIKE :search OR email LIKE :search2 OR phone LIKE :search3)";
            $searchTerm = "%$search%";
        }
        
        if ($role && in_array($role, ['admin', 'user', 'kasir'])) {
            $where .= " AND role = :role";
        }
        
        // Total data
        $countSql = "SELECT COUNT(*) as total FROM users $where";
        $countStmt = $this->db->query($countSql);
        
        if ($search) {
            $countStmt->bind(':search', $searchTerm);
            $countStmt->bind(':search2', $searchTerm);
            $countStmt->bind(':search3', $searchTerm);
        }
        
        if ($role && in_array($role, ['admin', 'user', 'kasir'])) {
            $countStmt->bind(':role', $role);
        }
        
        $countResult = $countStmt->fetch();
        $total = $countResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);
        
        // Data users
        $sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->query($sql);
        
        if ($search) {
            $stmt->bind(':search', $searchTerm);
            $stmt->bind(':search2', $searchTerm);
            $stmt->bind(':search3', $searchTerm);
        }
        
        if ($role && in_array($role, ['admin', 'user', 'kasir'])) {
            $stmt->bind(':role', $role);
        }
        
        $stmt->bind(':limit', $limit);
        $stmt->bind(':offset', $offset);
        
        $users = $stmt->fetchAll();
        
        // Tampilkan view
        $this->renderView('index', [
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'role' => $role
        ]);
    }
    
    // Method: GET /user/add
    public function add() {
        // Hanya admin yang bisa tambah user
        if ($_SESSION['user_role'] != 'admin') {
            $_SESSION['error'] = 'Anda tidak memiliki akses untuk menambah user!';
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        $this->renderView('add');
    }
    
    // Method: POST /user/add (proses tambah)
    public function add_process() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: ' . BASE_URL . 'user/add');
            exit();
        }
        
        // Validasi data
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'role' => $_POST['role'] ?? 'user',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        // Validasi
        $errors = [];
        
        if (empty($data['name'])) $errors[] = 'Nama harus diisi';
        if (empty($data['email'])) $errors[] = 'Email harus diisi';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
        if (empty($data['password'])) $errors[] = 'Password harus diisi';
        if ($data['password'] != $data['confirm_password']) $errors[] = 'Password tidak sama';
        
        // Cek email sudah terdaftar
        $existing = $this->db->query("SELECT id FROM users WHERE email = :email")
                            ->bind(':email', $data['email'])
                            ->fetch();
        if ($existing) $errors[] = 'Email sudah terdaftar';
        
        if (empty($errors)) {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Tambah user
            $sql = "INSERT INTO users (name, email, password, role, phone, address, created_at, updated_at)
                    VALUES (:name, :email, :password, :role, :phone, :address, NOW(), NOW())";
            
            $stmt = $this->db->query($sql);
            $stmt->bind(':name', $data['name']);
            $stmt->bind(':email', $data['email']);
            $stmt->bind(':password', $hashedPassword);
            $stmt->bind(':role', $data['role']);
            $stmt->bind(':phone', $data['phone']);
            $stmt->bind(':address', $data['address']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User berhasil ditambahkan!';
                header('Location: ' . BASE_URL . 'user');
                exit();
            } else {
                $errors[] = 'Gagal menambahkan user. Silakan coba lagi.';
            }
        }
        
        // Tampilkan form dengan error
        $this->renderView('add', ['errors' => $errors, 'data' => $data]);
    }
    
    // Method: GET /user/edit/{id}
    public function edit($id = null) {
        if (!$id) {
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        $user = $this->db->query("SELECT * FROM users WHERE id = :id")
                        ->bind(':id', $id)
                        ->fetch();
        
        if (!$user) {
            $_SESSION['error'] = 'User tidak ditemukan!';
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        $this->renderView('edit', ['user' => $user]);
    }
    
    // Method: POST /user/edit/{id}
    public function edit_process($id = null) {
        if (!$id || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role' => $_POST['role'] ?? 'user',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validasi
        $errors = [];
        
        if (empty($data['name'])) $errors[] = 'Nama harus diisi';
        if (empty($data['email'])) $errors[] = 'Email harus diisi';
        
        // Cek email duplikat (kecuali untuk user ini)
        $existing = $this->db->query(
            "SELECT id FROM users WHERE email = :email AND id != :id"
        )->bind(':email', $data['email'])
         ->bind(':id', $id)
         ->fetch();
         
        if ($existing) $errors[] = 'Email sudah digunakan user lain';
        
        if (empty($errors)) {
            // Update user
            $sql = "UPDATE users SET 
                    name = :name, email = :email, role = :role, phone = :phone, 
                    address = :address, status = :status, updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $this->db->query($sql);
            $stmt->bind(':name', $data['name']);
            $stmt->bind(':email', $data['email']);
            $stmt->bind(':role', $data['role']);
            $stmt->bind(':phone', $data['phone']);
            $stmt->bind(':address', $data['address']);
            $stmt->bind(':status', $data['status']);
            $stmt->bind(':id', $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User berhasil diupdate!';
                header('Location: ' . BASE_URL . 'user');
                exit();
            } else {
                $errors[] = 'Gagal mengupdate user';
            }
        }
        
        $user = $this->db->query("SELECT * FROM users WHERE id = :id")
                        ->bind(':id', $id)
                        ->fetch();
        $this->renderView('edit', ['user' => $user, 'errors' => $errors, 'data' => $data]);
    }
    
    // Method: GET /user/delete/{id}
    public function delete($id = null) {
        if (!$id || $_SESSION['user_role'] != 'admin') {
            $_SESSION['error'] = 'Akses ditolak!';
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        // Tidak boleh hapus diri sendiri
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Tidak bisa menghapus akun sendiri!';
            header('Location: ' . BASE_URL . 'user');
            exit();
        }
        
        $stmt = $this->db->query("DELETE FROM users WHERE id = :id")
                        ->bind(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus user';
        }
        
        header('Location: ' . BASE_URL . 'user');
        exit();
    }
    
    // Method: GET /user/profile
    public function profile() {
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id"
        )->bind(':id', $_SESSION['user_id'])
         ->fetch();
        
        $this->renderView('profile', ['user' => $user]);
    }
    
    // Method: POST /user/profile_update
    public function profile_update() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: ' . BASE_URL . 'user/profile');
            exit();
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'current_password' => $_POST['current_password'] ?? '',
            'new_password' => $_POST['new_password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        $errors = [];
        
        // Validasi
        if (empty($data['name'])) $errors[] = 'Nama harus diisi';
        
        // Cek email duplikat
        $existing = $this->db->query(
            "SELECT id FROM users WHERE email = :email AND id != :id"
        )->bind(':email', $data['email'])
         ->bind(':id', $_SESSION['user_id'])
         ->fetch();
         
        if ($existing) $errors[] = 'Email sudah digunakan user lain';
        
        // Jika ingin ganti password
        if (!empty($data['new_password'])) {
            // Cek password lama
            $user = $this->db->query(
                "SELECT password FROM users WHERE id = :id"
            )->bind(':id', $_SESSION['user_id'])
             ->fetch();
            
            if (!password_verify($data['current_password'], $user['password'])) {
                $errors[] = 'Password lama salah';
            }
            
            if ($data['new_password'] != $data['confirm_password']) {
                $errors[] = 'Password baru tidak sama';
            }
            
            if (strlen($data['new_password']) < 6) {
                $errors[] = 'Password minimal 6 karakter';
            }
        }
        
        if (empty($errors)) {
            if (!empty($data['new_password'])) {
                // Update dengan password baru
                $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET 
                        name = :name, email = :email, phone = :phone, address = :address, 
                        password = :password, updated_at = NOW() 
                        WHERE id = :id";
                
                $stmt = $this->db->query($sql);
                $stmt->bind(':name', $data['name']);
                $stmt->bind(':email', $data['email']);
                $stmt->bind(':phone', $data['phone']);
                $stmt->bind(':address', $data['address']);
                $stmt->bind(':password', $hashedPassword);
                $stmt->bind(':id', $_SESSION['user_id']);
            } else {
                // Update tanpa ganti password
                $sql = "UPDATE users SET 
                        name = :name, email = :email, phone = :phone, address = :address, 
                        updated_at = NOW() WHERE id = :id";
                
                $stmt = $this->db->query($sql);
                $stmt->bind(':name', $data['name']);
                $stmt->bind(':email', $data['email']);
                $stmt->bind(':phone', $data['phone']);
                $stmt->bind(':address', $data['address']);
                $stmt->bind(':id', $_SESSION['user_id']);
            }
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_email'] = $data['email'];
                
                $_SESSION['success'] = 'Profile berhasil diupdate!';
                header('Location: ' . BASE_URL . 'user/profile');
                exit();
            } else {
                $errors[] = 'Gagal mengupdate profile';
            }
        }
        
        $user = $this->db->query(
            "SELECT * FROM users WHERE id = :id"
        )->bind(':id', $_SESSION['user_id'])
         ->fetch();
        
        $this->renderView('profile', ['user' => $user, 'errors' => $errors, 'data' => $data]);
    }
    
    // Helper untuk render view
    private function renderView($view, $data = []) {
        // Extract data ke variabel
        extract($data);
        
        // Include header (sesuai dengan struktur folder Anda)
        $headerPath = __DIR__ . '/../../views/header.php';
        if (file_exists($headerPath)) {
            require_once $headerPath;
        }
        
        // Include view file (sesuai dengan struktur folder)
        $viewFile = __DIR__ . "/{$view}.php";
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "<div class='container mt-4'><div class='alert alert-danger'>View {$view}.php tidak ditemukan di: $viewFile</div></div>";
        }
        
        // Include footer
        $footerPath = __DIR__ . '/../../views/footer.php';
        if (file_exists($footerPath)) {
            require_once $footerPath;
        }
    }
}
?>