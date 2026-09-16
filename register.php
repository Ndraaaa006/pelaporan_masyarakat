<?php
include 'config/koneksi.php';

if (isset($_POST['register'])) {
    $nik      = trim($_POST['nik']);
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telp     = trim($_POST['telp']);

    $stmt = mysqli_prepare($koneksi, "INSERT INTO masyarakat (nik, nama, username, password, telp) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $nik, $nama, $username, $password, $telp);
    $insert = mysqli_stmt_execute($stmt);

    if ($insert) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Registrasi gagal! Cek kembali data Anda.'); window.location='register.php';</script>";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun Warga - Layanan Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-register {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: #fff;
            padding: 25px 20px;
            text-align: center;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
        }
        .btn-primary-custom {
            border-radius: 10px;
            padding: 11px;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-12 d-flex justify-content-center">
                <div class="card card-register bg-white">
                    <div class="card-header-custom">
                        <div class="mb-2">
                            <span class="bg-white bg-opacity-25 p-3 rounded-circle d-inline-flex">
                                <i class="bi bi-person-plus-fill fs-3 text-white"></i>
                            </span>
                        </div>
                        <h4 class="fw-bold mb-1">Registrasi Akun Warga</h4>
                        <p class="mb-0 small text-white-50">Lengkapi data di bawah ini untuk membuat akun</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">NIK</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-card-text text-muted"></i></span>
                                    <input type="text" name="nik" class="form-control border-start-0 ps-0" maxlength="16" placeholder="Masukkan 16 digit NIK" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="nama" class="form-control border-start-0 ps-0" maxlength="35" placeholder="Nama lengkap sesuai KTP" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-at text-muted"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0 ps-0" maxlength="25" placeholder="Username untuk login" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Buat password akun" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-secondary">No. Telepon / WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" name="telp" class="form-control border-start-0 ps-0" maxlength="13" placeholder="Contoh: 081234567890" required>
                                </div>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary-custom w-100 text-white mb-3">
                                <i class="bi bi-person-check-fill me-1"></i> Daftar Sekarang
                            </button>
                            <div class="text-center mt-3">
                                <p class="small text-muted mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-semibold">Login di sini</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>