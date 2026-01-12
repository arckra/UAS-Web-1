<?php
$pageTitle = 'Profil Saya';
$db = new Database();
$error = '';
$success = '';

// Ambil data user yang sedang login
$user = $db->query("SELECT * FROM users WHERE id = :id")
           ->bind(':id', $_SESSION['user_id'])
           ->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($name) || empty($email)) {
        $error = 'Nama dan email harus diisi!';
    } else {
        // Cek email duplikat
        $check = $db->query("SELECT id FROM users WHERE email = :email AND id != :id")
                    ->bind(':email', $email)
                    ->bind(':id', $_SESSION['user_id'])
                    ->fetch();
        
        if ($check) {
            $error = 'Email sudah digunakan!';
        } else {
            $avatar = $user['avatar'];
            
            // Upload avatar baru
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    if ($user['avatar'] != 'default.jpg' && file_exists('assets/gambar/' . $user['avatar'])) {
                        unlink('assets/gambar/' . $user['avatar']);
                    }
                    
                    $avatar = 'avatar_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['avatar']['tmp_name'], 'assets/gambar/' . $avatar);
                }
            }
            
            $query = "UPDATE users SET name = :name, email = :email, phone = :phone, 
                      address = :address, avatar = :avatar WHERE id = :id";
            
            // Update password jika diisi
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $error = 'Password dan konfirmasi tidak sama!';
                } elseif (strlen($_POST['password']) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } else {
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET name = :name, email = :email, password = :password, 
                              phone = :phone, address = :address, avatar = :avatar WHERE id = :id";
                }
            }
            
            if (empty($error)) {
                $stmt = $db->query($query)
                           ->bind(':name', $name)
                           ->bind(':email', $email)
                           ->bind(':phone', $phone)
                           ->bind(':address', $address)
                           ->bind(':avatar', $avatar)
                           ->bind(':id', $_SESSION['user_id']);
                
                if (isset($hashedPassword)) {
                    $stmt->bind(':password', $hashedPassword);
                }
                
                if ($stmt->execute()) {
                    // Update session
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['avatar'] = $avatar;
                    
                    $success = 'Profil berhasil diupdate!';
                    $user = $db->query("SELECT * FROM users WHERE id = :id")
                               ->bind(':id', $_SESSION['user_id'])
                               ->fetch();
                } else {
                    $error = 'Gagal mengupdate profil!';
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
                <i class="bi bi-person-circle me-2"></i>Profil Saya
            </h2>
            <p class="text-muted">Kelola informasi profil Anda</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card text-center">
                <div class="card-body py-5">
                    <img src="<?= BASE_URL ?>assets/gambar/<?= $user['avatar'] ?>" 
                         alt="Avatar" class="rounded-circle mb-3" 
                         width="150" height="150" style="object-fit: cover; border: 5px solid #f3f4f6;">
                    <h4 class="mb-1"><?= $user['name'] ?></h4>
                    <p class="text-muted mb-3"><?= $user['email'] ?></p>
                    <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'info' ?> px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i><?= ucfirst($user['role']) ?>
                    </span>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="bi bi-clock-history me-1"></i>
                        Bergabung: <?= tanggal_indo($user['created_at']) ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square me-2"></i>Edit Profil
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle me-2"></i><?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= $user['name'] ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" value="<?= $user['phone'] ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3"><?= $user['address'] ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" name="avatar" class="form-control input-image" accept="image/*">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maks: 2MB</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3"><i class="bi bi-key me-2"></i>Ubah Password</h5>
                        <p class="text-muted small">Kosongkan jika tidak ingin mengubah password</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control" minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>