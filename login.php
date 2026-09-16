<?php
session_start();
require_once 'config/koneksi.php';

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        // --- 1. CEK KE TABEL PETUGAS (Admin / Petugas) ---
        $stmt_petugas = mysqli_prepare($koneksi, "SELECT * FROM petugas WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_petugas, "s", $username);
        mysqli_stmt_execute($stmt_petugas);
        $result_petugas = mysqli_stmt_get_result($stmt_petugas);

        if ($row_p = mysqli_fetch_assoc($result_petugas)) {
            if (password_verify($password, $row_p['password'])) {
                session_regenerate_id(true); // Keamanan sesi
                $_SESSION['id_petugas']   = $row_p['id_petugas'];
                $_SESSION['nama_petugas'] = $row_p['nama_petugas'];
                $_SESSION['level']        = $row_p['level']; // 'admin' atau 'petugas'
                
                header("Location: admin/dashboard.php");
                exit();
            }
        }

        // --- 2. CEK KE TABEL MASYARAKAT (Warga) ---
        $stmt_warga = mysqli_prepare($koneksi, "SELECT * FROM masyarakat WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_warga, "s", $username);
        mysqli_stmt_execute($stmt_warga);
        $result_warga = mysqli_stmt_get_result($stmt_warga);

        if ($row_w = mysqli_fetch_assoc($result_warga)) {
            // Menggunakan password_verify karena password di tabel masyarakat sudah di-hash
            if (password_verify($password, $row_w['password'])) {
                session_regenerate_id(true); // Keamanan sesi
                $_SESSION['nik']   = $row_w['nik'];
                $_SESSION['nama']  = $row_w['nama'];
                $_SESSION['level'] = 'masyarakat';

                header("Location: dashboard.php");
                exit();
            }
        }

        // Jika tidak cocok di kedua tabel
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Layanan Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            background: #fff;
        }
        .login-header {
            background: #fff;
            padding: 30px 20px 10px;
            text-align: center;
        }
        .login-header .icon-box {
            width: 65px;
            height: 65px;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 15px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 12px 0 0 12px;
            border: 1px solid #dee2e6;
            border-right: none;
            color: #6c757d;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        .btn-primary-custom {
            border-radius: 12px;
            padding: 12px;
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

    <div class="login-card">
        <div class="login-header">
            <div class="icon-box">
                <i class="bi bi-person-fill-lock"></i>
            </div>
            <h4 class="fw-bold mb-1">Login Pengaduan</h4>
            <p class="text-muted small">Silakan Login Menggunakan Akun Yang Sudah Terdaftar</p>
        </div>
        <div class="card-body p-4 pt-2">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center rounded-3 p-2 mb-3 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-primary-custom w-100 text-white mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
                <div class="text-center">
                    <p class="small text-muted mb-0">Belum punya akun warga? <a href="register.php" class="text-decoration-none fw-bold text-primary">Registrasi di sini</a></p>
                </div>
            </form>
        </div>
        <div class="bg-light text-center py-3 border-top">
            <a href="index.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>