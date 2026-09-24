<?php
session_start();
require_once '../config/koneksi.php';
require_once 'cek_akses.php';

// Validasi ketat: Hanya ADMIN yang boleh mengakses halaman tambah petugas
if ($_SESSION['level'] !== 'admin') {
    header("Location: dashboard.php?pesan=akses_ditolak");
    exit();
}

$error = '';
$sukses = '';

if (isset($_POST['tambah'])) {
    $nama_petugas = trim($_POST['nama_petugas']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];
    $telp         = trim($_POST['telp']);
    $level        = 'petugas'; // Otomatis diset sebagai petugas

    if (empty($nama_petugas) || empty($username) || empty($password) || empty($telp)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Cek apakah username sudah terdaftar
        $stmt_cek = mysqli_prepare($koneksi, "SELECT username FROM petugas WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cek, "s", $username);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);

        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $error = "Username sudah digunakan oleh petugas lain!";
        } else {
            // Enkripsi password dengan password_hash (Standar keamanan tertinggi)
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Simpan ke database menggunakan prepared statement
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO petugas (nama_petugas, username, password, telp, level) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $nama_petugas, $username, $password_hashed, $telp, $level);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $sukses = "Akun petugas berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan petugas: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_cek);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Petugas - Administrator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill me-3">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <h4 class="fw-bold mb-0">Tambah Petugas Baru</h4>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger p-2 small rounded-3 mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sukses)): ?>
                            <div class="alert alert-success p-2 small rounded-3 mb-3">
                                <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Nama Lengkap Petugas</label>
                                <input type="text" name="nama_petugas" class="form-control rounded-3" placeholder="Masukkan nama lengkap" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Username</label>
                                <input type="text" name="username" class="form-control rounded-3" placeholder="Masukkan username untuk login" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Password</label>
                                <input type="password" name="password" class="form-control rounded-3" placeholder="Masukkan password" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Nomor Telepon</label>
                                <input type="number" name="telp" class="form-control rounded-3" placeholder="Contoh: 081234567890" required>
                            </div>
                            <button type="submit" name="tambah" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold">
                                <i class="bi bi-person-plus-fill me-1"></i> Simpan Petugas Baru
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>