<?php
session_start();
require_once '../config/koneksi.php';
require_once 'cek_akses.php';

// 1. Keamanan Autentikasi Ketat & Validasi Sesi
if (!isset($_SESSION['id_petugas']) || !isset($_SESSION['level']) || 
    ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'petugas')) {
    header("Location: login.php");
    exit();
}

// Mencegah Session Hijacking sederhana
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?pesan=expired");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 2. Mengambil Statistik dengan Prepared Statement
$stmt_stat = mysqli_prepare($koneksi, "
    SELECT 
        SUM(status = '0') as jml_masuk,
        SUM(status = 'proses') as jml_proses,
        SUM(status = 'selesai') as jml_selesai,
        COUNT(*) as jml_total
    FROM pengaduan
");

$stats = ['jml_masuk' => 0, 'jml_proses' => 0, 'jml_selesai' => 0, 'jml_total' => 0];
if ($stmt_stat) {
    mysqli_stmt_execute($stmt_stat);
    $stat_result = mysqli_stmt_get_result($stmt_stat);
    if ($row_stat = mysqli_fetch_assoc($stat_result)) {
        $stats = $row_stat;
    }
    mysqli_stmt_close($stmt_stat);
}

$jml_masuk   = (int)($stats['jml_masuk'] ?? 0);
$jml_proses  = (int)($stats['jml_proses'] ?? 0);
$jml_selesai = (int)($stats['jml_selesai'] ?? 0);
$jml_total   = (int)($stats['jml_total'] ?? 0);

// --- PENGATURAN PAGINATION ---
$limit = 10; // Batas 10 data per halaman
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$page = ($page > 1) ? $page : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Hitung total halaman
$total_halaman = ceil($jml_total / $limit);

// 3. Mengambil Data Pengaduan (Ditambah JOIN ke tabel petugas untuk nama_petugas)
$query = "SELECT p.id_pengaduan, p.tgl_pengaduan, p.nik, p.isi_laporan, p.foto, p.status, 
                 m.nama, t.tanggapan, t.tgl_tanggapan, pt.nama_petugas 
          FROM pengaduan p 
          JOIN masyarakat m ON p.nik = m.nik 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
          LEFT JOIN petugas pt ON t.id_petugas = pt.id_petugas
          ORDER BY p.tgl_pengaduan DESC 
          LIMIT ?, ?";

$stmt_laporan = mysqli_prepare($koneksi, $query);
$laporans = [];
if ($stmt_laporan) {
    mysqli_stmt_bind_param($stmt_laporan, "ii", $start, $limit);
    mysqli_stmt_execute($stmt_laporan);
    $result = mysqli_stmt_get_result($stmt_laporan);
    while ($row = mysqli_fetch_assoc($result)) {
        $laporans[] = $row;
    }
    mysqli_stmt_close($stmt_laporan);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas & Admin - Layanan Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22 fill=%22%232a5298%22><path d=%22M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-1.773l-.405.012A1.5 1.5 0 0 1 4.5 10.3V5.7a1.5 1.5 0 0 1 1.183-1.469l.405-.012c2.422-.07 4.75-.672 6.912-1.773V2.5zM3 4.5a.5.5 0 0 0-.5.5v6a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5z%22/></svg>">
    <style>
        :root {
            --primary-color: #2b4c7e;
            --secondary-color: #567ebb;
            --bg-body: #f8f9fa;
        }
        body {
            background-color: var(--bg-body);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333333;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #2b4c7e 0%, #1a3052 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .card-stat {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
        }
        .card-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .main-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            background: #ffffff;
        }
        .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background-color: #f1f3f5 !important;
            color: #495057;
            padding: 12px 10px;
        }
        .table td {
            padding: 12px 10px;
            vertical-align: middle;
        }
        .img-thumbnail-custom {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .img-thumbnail-custom:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="bi bi-window-sidebar me-2 fs-5"></i> Panel Layanan Pengaduan
            </a>
            <div class="d-flex align-items-center">
                <span class="text-light me-3 small d-none d-sm-inline">
                    Login sebagai: <strong class="text-white"><?= htmlspecialchars($_SESSION['nama_petugas'] ?? $_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong> 
                    <span class="badge bg-light text-dark ms-1 text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['level'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <a href="logout.php" class="btn btn-light btn-sm px-3 fw-semibold text-danger shadow-sm"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <!-- Kartu Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card card-stat p-3 border-start border-primary border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted d-block small fw-bold text-uppercase mb-1">Total Laporan</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_total ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-journal-text fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-stat p-3 border-start border-warning border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted d-block small fw-bold text-uppercase mb-1">Menunggu</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_masuk ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-stat p-3 border-start border-info border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted d-block small fw-bold text-uppercase mb-1">Diproses</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_proses ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-arrow-repeat fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-stat p-3 border-start border-success border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted d-block small fw-bold text-uppercase mb-1">Selesai</span>
                            <h3 class="fw-bold mb-0 text-dark"><?= $jml_selesai ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check2-all fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Pengaduan -->
        <div class="card main-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Data Pengaduan Masuk</h5>
                    <p class="text-muted small mb-0">Kelola dan berikan tanggapan terhadap laporan yang dikirimkan masyarakat.</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin'): ?>
                        <a href="tambah_petugas.php" class="btn btn-dark btn-sm px-3 rounded-2 shadow-sm d-flex align-items-center">
                            <i class="bi bi-person-plus me-1"></i> Tambah Petugas
                        </a>
                        <a href="cetak.php" target="_blank" class="btn btn-outline-secondary btn-sm px-3 rounded-2 shadow-sm d-flex align-items-center">
                            <i class="bi bi-printer me-1"></i> Cetak Laporan
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="11%">Tanggal</th>
                            <th width="15%">Pelapor</th>
                            <th width="20%">Isi Laporan</th>
                            <th width="7%" class="text-center">Foto</th>
                            <th width="9%">Status</th>
                            <th width="13%">Tanggapan</th>
                            <th width="10%">Petugas</th> <!-- Kolom Baru Ditambahkan -->
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporans)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted fst-italic">Belum ada data pengaduan yang masuk.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $start + 1; foreach ($laporans as $row): ?>
                                <tr>
                                    <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                                    <td>
                                        <span class="small text-secondary"><?= htmlspecialchars($row['tgl_pengaduan'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['nik'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['isi_laporan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <?= nl2br(htmlspecialchars($row['isi_laporan'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto'])): ?>
                                            <a href="../uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                                <img src="../uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" class="img-thumbnail-custom shadow-sm">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">Tidak ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == '0'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1 rounded-1 fw-normal">Menunggu</span>
                                        <?php elseif ($row['status'] == 'proses'): ?>
                                            <span class="badge bg-info text-white px-2 py-1 rounded-1 fw-normal">Diproses</span>
                                        <?php else: ?>
                                            <span class="badge bg-success px-2 py-1 rounded-1 fw-normal">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['tanggapan'])): ?>
                                            <div class="small text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['tanggapan'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <span class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($row['tgl_tanggapan'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic" style="font-size: 0.75rem;">Menunggu diverifikasi</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- KODE YANG DITANYAKAN DITEMPATKAN DI SINI -->
                                    <td>
                                        <span class="small text-dark">
                                            <?= htmlspecialchars($row['nama_petugas'] ?? 'Belum diverifikasi', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="tanggapan.php?id=<?= (int)$row['id_pengaduan'] ?>" class="btn btn-sm btn-primary px-2 py-1 rounded-1" title="Tanggapi">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                            <a href="hapus.php?id=<?= (int)$row['id_pengaduan'] ?>" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-1" title="Hapus" onclick="return confirm('Yakin ingin menghapus pengaduan ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Navigasi Pagination -->
            <?php if ($total_halaman > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Tombol Previous -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?halaman=<?= $page - 1 ?>">Sebelumnya</a>
                        </li>

                        <!-- Nomor Halaman -->
                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?halaman=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Tombol Next -->
                        <li class="page-item <?= ($page >= $total_halaman) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?halaman=<?= $page + 1 ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>