<?php
// Cek role admin
if (!hasRole('admin')) {
    setAlert('danger', 'Akses ditolak! Halaman ini hanya untuk admin.');
    redirect('dashboard');
}

$pageTitle = 'Manajemen User';
$db = new Database();

// Ambil data user dengan filter dan pencarian
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($role)) {
    $query .= " AND role = :role";
    $params[':role'] = $role;
}

if (!empty($status)) {
    $query .= " AND status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->query($query);
foreach ($params as $key => $value) {
    $stmt->bind($key, $value);
}
$users = $stmt->fetchAll();

require_once 'views/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-person-gear me-2"></i>Manajemen User
            </h2>
            <p class="text-muted">Kelola data pengguna sistem</p>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>user/add" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Tambah User
            </a>
        </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau telepon..." value="<?= $search ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="kasir" <?= $role == 'kasir' ? 'selected' : '' ?>>Kasir</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Data Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-table me-2"></i>Daftar User
            <span class="badge bg-primary ms-2"><?= count($users) ?> data</span>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Tidak ada data user</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover dataTable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama</th>
                                <th width="20%">Email</th>
                                <th width="15%">Telepon</th>
                                <th width="10%">Role</th>
                                <th width="10%">Status</th>
                                <th width="15%">Login Terakhir</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($users as $user): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= BASE_URL ?>assets/gambar/<?= $user['avatar'] ?>" 
                                                alt="Avatar" class="rounded-circle me-2" 
                                                width="40" height="40" style="object-fit: cover;">
                                        <strong><?= $user['name'] ?></strong>
                                    </div>
                                </td>
                                <td><?= $user['email'] ?></td>
                                <td><?= $user['phone'] ?? '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'info' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-<?= $user['status'] ?>">
                                        <?= $user['status'] == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></small>
                                    <?php else: ?>
                                        <em class="text-muted">Belum pernah</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>user/edit/<?= $user['id'] ?>" 
                                            class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="<?= BASE_URL ?>user/delete/<?= $user['id'] ?>" 
                                            class="btn btn-danger btn-delete" 
                                            data-name="<?= $user['name'] ?>"
                                            title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>