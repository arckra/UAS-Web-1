<?php
if (!hasRole('admin')) {
    setAlert('danger', 'Akses ditolak!');
    redirect('dashboard');
}

$pageTitle = 'Edit User';
$db = new Database();
$error = '';

// Ambil ID dari URL
$id = isset($param) ? $param : 0;

// Ambil data user
$user = $db->query("SELECT * FROM users WHERE id = :id")
           ->bind(':id', $id)
           ->fetch();

if (!$user) {
    setAlert('danger', 'User tidak ditemukan!');
    redirect('user');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];
    
    if (empty($name) || empty($email) || empty($role)) {
        $error = 'Mohon lengkapi semua field yang wajib diisi!';
    } else {
        // Cek email duplikat (kecuali email sendiri)
        $check = $db->query("SELECT id FROM users WHERE email = :email AND id != :id")
                    ->bind(':email', $email)
                    ->bind(':id', $id)
                    ->fetch();
        
        if ($check) {
            $error = 'Email sudah digunakan user lain!';
        } else {
            $avatar = $user['avatar'];
            
            // Upload avatar baru jika ada
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    // Hapus avatar lama (kecuali default)
                    if ($user['avatar'] != 'default.jpg' && file_exists('assets/gambar/' . $user['avatar'])) {
                        unlink('assets/gambar/' . $user['avatar']);
                    }
                    
                    $avatar = 'avatar_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['avatar']['tmp_name'], 'assets/gambar/' . $avatar);
                }
            }
            
            // Update user
            $query = "UPDATE users SET name = :name, email = :email, role = :role, 
                      phone = :phone, address = :address, avatar = :avatar, status = :status 
                      WHERE id = :id";
            
            // Jika password diisi, update juga password
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $error = 'Password dan konfirmasi password tidak sama!';
                } elseif (strlen($_POST['password']) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } else {
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET name = :name, email = :email, password = :password, 
                              role = :role, phone = :phone, address = :address, avatar = :avatar, 
                              status = :status WHERE id = :id";
                }
            }
            
            if (empty($error)) {
                $stmt = $db->query($query)
                           ->bind(':name', $name)
                           ->bind(':email', $email)
                           ->bind(':role', $role)
                           ->bind(':phone', $phone)
                           ->bind(':address', $address)
                           ->bind(':avatar', $avatar)
                           ->bind(':status', $status)
                           ->bind(':id', $id);
                
                if (isset($hashedPassword)) {
                    $stmt->bind(':password', $hashedPassword);
                }
                
                if ($stmt->execute()) {
                    setAlert('success', 'User berhasil diupdate!');
                    redirect('user');
                } else {
                    $error = 'Gagal mengupdate user!';
                }
            }
        }
    }
}

require_once 'views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-pencil-square me-2"></i>Edit User
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>user">User</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-card-text me-2"></i>Form Edit User
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= $user['name'] ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control" minlength="6">
                                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="kasir" <?= $user['role'] == 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" value="<?= $user['phone'] ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3"><?= $user['address'] ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control input-image" accept="image/*">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maks: 2MB</small>
                            <div class="mt-2">
                                <img src="<?= BASE_URL ?>assets/gambar/<?= $user['avatar'] ?>" class="preview-image" style="max-width: 150px; border-radius: 10px;">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update
                            </button>
                            <a href="<?= BASE_URL ?>user" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>