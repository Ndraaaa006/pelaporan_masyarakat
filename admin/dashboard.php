<?php
session_start();
require_once '../config/koneksi.php';
require_once 'cek_akses.php';

// 1. Keamanan Autentikasi Ketat (Pastikan level admin atau petugas)
if (!isset($_SESSION['id_petugas']) || !isset($_SESSION['level']) || 
    ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'petugas')) {
    header("Location: login.php");
    exit();
}

// 2. Gunakan Prepared Statement untuk Mengambil Statistik secara Aman & Efisien dalam 1 Kueri
$stmt_stat = mysqli_prepare($koneksi, "
    SELECT 
        SUM(status = '0') as jml_masuk,
        SUM(status = 'proses') as jml_proses,
        SUM(status = 'selesai') as jml_selesai,
        COUNT(*) as jml_total
    FROM pengaduan
");
mysqli_stmt_execute($stmt_stat);
$stat_result = mysqli_stmt_get_result($stmt_stat);
$stats = mysqli_fetch_assoc($stat_result);
mysqli_stmt_close($stmt_stat);

$jml_masuk   = (int)($stats['jml_masuk'] ?? 0);
$jml_proses  = (int)($stats['jml_proses'] ?? 0);
$jml_selesai = (int)($stats['jml_selesai'] ?? 0);
$jml_total   = (int)($stats['jml_total'] ?? 0);

// 3. Menggunakan Prepared Statement untuk Menarik Data Pengaduan & Masyarakat
$query = "SELECT p.id_pengaduan, p.tgl_pengaduan, p.nik, p.isi_laporan, p.foto, p.status, 
                 m.nama, t.tanggapan, t.tgl_tanggapan 
          FROM pengaduan p 
          JOIN masyarakat m ON p.nik = m.nik 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
          ORDER BY p.tgl_pengaduan DESC";

$stmt_laporan = mysqli_prepare($koneksi, $query);
mysqli_stmt_execute($stmt_laporan);
$result = mysqli_stmt_get_result($stmt_laporan);

$laporans = [];
while ($row = mysqli_fetch_assoc($result)) {
    $laporans[] = $row;
}
mysqli_stmt_close($stmt_laporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin & Petugas - Layanan Pengaduan Masyarakat</title>
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
        .table th {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="bi bi-shield-lock-fill me-2 fs-4"></i> Panel Admin & Petugas
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small">Halo, <strong class="fs-6"><?= htmlspecialchars($_SESSION['nama_petugas'] ?? $_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($_SESSION['level'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <!-- Kartu Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-start border-primary border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Total Pengaduan</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $jml_total ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 fs-3">
                            <i class="bi bi-chat-square-text"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-warning border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Menunggu</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $jml_masuk ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 fs-3">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-info border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Diproses</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $jml_proses ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 fs-3">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-success border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Selesai</small>
                            <h3 class="fw-bold mb-0 mt-1"><?= $jml_selesai ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 fs-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengaduan -->
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Daftar Pengaduan Masyarakat</h5>
                <!-- Tombol Cetak Aman Terintegrasi -->
                <a href="cetak.php" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Pelapor (NIK / Nama)</th>
                            <th>Isi Laporan</th>
                            <th>Foto</th>
                            <th>Status</th>
                            <th>Tanggapan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporans)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada data pengaduan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($laporans as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $no++ ?></td>
                                    <td><small><?= htmlspecialchars($row['tgl_pengaduan'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['nik'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            echo nl2br(htmlspecialchars($row['isi_laporan'] ?? '', ENT_QUOTES, 'UTF-8')); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['foto'])): ?>
                                            <a href="../uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                                <img src="../uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" width="55" height="55" class="rounded-3 object-fit-cover shadow-sm">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Tidak ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == '0'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill">Menunggu</span>
                                        <?php elseif ($row['status'] == 'proses'): ?>
                                            <span class="badge bg-info text-white rounded-pill">Diproses</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['tanggapan'])): ?>
                                            <div class="small"><?= htmlspecialchars($row['tanggapan'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['tgl_tanggapan'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Belum ditanggapi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Validasi ID menjadi Integer murni untuk Mencegah URL Tampering / SQL Injection -->
                                            <a href="tanggapan.php?id=<?= (int)$row['id_pengaduan'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                <i class="bi bi-chat-left-dots-fill me-1"></i> Tanggapi
                                            </a>
                                            <a href="hapus.php?id=<?= (int)$row['id_pengaduan'] ?>" class="btn btn-sm btn-danger rounded-pill px-2" onclick="return confirm('Apakah Anda yakin ingin menghapus pengaduan ini?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>