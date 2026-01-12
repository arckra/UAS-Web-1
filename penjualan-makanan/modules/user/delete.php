<?php
if (!hasRole('admin')) {
    setAlert('danger', 'Akses ditolak!');
    redirect('dashboard');
}

$db = new Database();
$id = isset($param) ? $param : 0;

// Cek apakah user mencoba menghapus dirinya sendiri
if ($id == $_SESSION['user_id']) {
    setAlert('danger', 'Tidak dapat menghapus akun sendiri!');
    redirect('user');
}

// Ambil data user
$user = $db->query("SELECT * FROM users WHERE id = :id")
           ->bind(':id', $id)
           ->fetch();

if (!$user) {
    setAlert('danger', 'User tidak ditemukan!');
    redirect('user');
}

// Hapus avatar jika bukan default
if ($user['avatar'] != 'default.jpg' && file_exists('assets/gambar/' . $user['avatar'])) {
    unlink('assets/gambar/' . $user['avatar']);
}

// Hapus user
$delete = $db->query("DELETE FROM users WHERE id = :id")
             ->bind(':id', $id)
             ->execute();

if ($delete) {
    setAlert('success', 'User "' . $user['name'] . '" berhasil dihapus!');
} else {
    setAlert('danger', 'Gagal menghapus user!');
}

redirect('user');