<?php
session_start();
require_once '../config/koneksi.php';

// Cek apakah sudah login sebagai petugas/admin
if (!isset($_SESSION['id_petugas']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id_pengaduan = $_GET['id'] ?? null;
if (!$id_pengaduan) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$sukses = '';

// Proses Simpan Tanggapan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggapan   = trim($_POST['tanggapan'] ?? '');
    $status      = $_POST['status'] ?? 'selesai';
    $tgl_tanggapan = date('Y-m-d');
    $id_petugas  = $_SESSION['id_petugas'] ?? null;

    if (!empty($tanggapan)) {
        // Cek apakah pengaduan ini sudah pernah ditanggapi sebelumnya
        $cek_tanggapan = mysqli_query($koneksi, "SELECT * FROM tanggapan WHERE id_pengaduan = '$id_pengaduan'");
        
        if (mysqli_num_rows($cek_tanggapan) > 0) {
            // Update tanggapan yang sudah ada
            $stmt = mysqli_prepare($koneksi, "UPDATE tanggapan SET tgl_tanggapan = ?, tanggapan = ?, id_petugas = ? WHERE id_pengaduan = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $tgl_tanggapan, $tanggapan, $id_petugas, $id_pengaduan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // Insert tanggapan baru
            $stmt = mysqli_prepare($koneksi, "INSERT INTO tanggapan (id_pengaduan, tgl_tanggapan, tanggapan, id_petugas) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issi", $id_pengaduan, $tgl_tanggapan, $tanggapan, $id_petugas);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Update status pengaduan
        $stmt_status = mysqli_prepare($koneksi, "UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?");
        mysqli_stmt_bind_param($stmt_status, "si", $status, $id_pengaduan);
        mysqli_stmt_execute($stmt_status);
        mysqli_stmt_close($stmt_status);

        $sukses = "Tanggapan berhasil dikirim!";
    } else {
        $error = "Tanggapan tidak boleh kosong!";
    }
}

// Ambil detail pengaduan & pelapor
$query = "SELECT p.*, m.nama, t.tanggapan, t.tgl_tanggapan 
          FROM pengaduan p 
          JOIN masyarakat m ON p.nik = m.nik 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
          WHERE p.id_pengaduan = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_pengaduan);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanggapi Pengaduan - Panel Admin & Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <i class="bi bi-shield-lock-fill me-2 fs-4"></i> Panel Admin & Petugas
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small">Halo, <strong class="fs-6"><?= htmlspecialchars($_SESSION['nama_petugas'] ?? $_SESSION['username']) ?></strong></span>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row g-4">
            <!-- Detail Laporan Warga -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text-fill text-primary me-2"></i> Detail Laporan</h5>
                    <hr>
                    <div class="mb-3">
                        <small class="text-muted d-block">Nama Pelapor</small>
                        <strong class="fs-5"><?= htmlspecialchars($data['nama']) ?></strong>
                        <span class="text-muted small"> (NIK: <?= htmlspecialchars($data['nik']) ?>)</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Tanggal Pengaduan</small>
                        <span><?= htmlspecialchars($data['tgl_pengaduan']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Isi Laporan</small>
                        <div class="p-3 bg-light rounded-3 mt-1"><?= $data['isi_laporan'] ?></div>
                    </div>
                    <?php if (!empty($data['foto'])): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Foto Bukti</small>
                            <a href="../uploads/<?= htmlspecialchars($data['foto']) ?>" target="_blank">
                                <img src="../uploads/<?= htmlspecialchars($data['foto'] ?? '') ?>" class="img-fluid rounded-3 shadow-sm" style="max-height: 220px; object-fit: cover;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Beri Tanggapan -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-chat-dots-fill text-success me-2"></i> Form Tanggapan Petugas</h5>
                    <hr>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3 small"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($sukses): ?>
                        <div class="alert alert-success rounded-3 small">
                            <?= htmlspecialchars($sukses) ?> <a href="dashboard.php" class="alert-link">Kembali ke Dashboard</a>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Status Laporan</label>
                            <select name="status" class="form-select" required>
                                <option value="proses" <?= ($data['status'] == 'proses') ? 'selected' : '' ?>>Diproses</option>
                                <option value="selesai" <?= ($data['status'] == 'selesai' || $data['status'] == '0') ? 'selected' : '' ?>>Selesai</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-secondary">Teks Tanggapan</label>
                            <textarea name="tanggapan" class="form-control" rows="5" required placeholder="Tuliskan tanggapan atau tindak lanjut untuk warga..."><?= htmlspecialchars($data['tanggapan'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-send-fill me-1"></i> Simpan Tanggapan
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>