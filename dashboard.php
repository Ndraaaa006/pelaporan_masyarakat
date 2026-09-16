<?php
session_start();
require_once 'config/koneksi.php';
require_once 'cek_akses_warga.php';

// Cek apakah sudah login sebagai masyarakat
if (!isset($_SESSION['nik']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'] ?? $_SESSION['user_id'];
$nama = $_SESSION['nama'] ?? 'Warga';

$error = '';
$sukses = '';

// Proses Kirim Pengaduan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_pengaduan = date('Y-m-d');
    $judul         = trim($_POST['judul'] ?? '');
    $isi_input     = trim($_POST['isi_laporan'] ?? '');
    $foto          = '';

    // Handle Upload Foto secara Aman
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validasi ekstensi dan mime type file asli untuk mencegah upload file jahat (.php dsb)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($ext, $allowed_ext) && in_array($mime_type, $allowed_mimes)) {
            // Batasi ukuran maksimal file (misal 2MB)
            if ($_FILES['foto']['size'] <= 2 * 1024 * 1024) {
                $foto = time() . '_' . uniqid() . '.' . $ext;
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
            } else {
                $error = "Ukuran foto terlalu besar! Maksimal 2MB.";
            }
        } else {
            $error = "Format foto tidak valid atau bukan gambar yang aman!";
        }
    }

    if (empty($error)) {
        if (!empty($judul) && !empty($isi_input)) {
            // Gabungkan judul dan isi_laporan menggunakan teks baris baru (\n) yang bersih dari tag HTML liar
            // Ini mencegah teks nempel tanpa spasi saat dibaca di dashboard admin/warga
            $isi_laporan = "Judul: " . $judul . "\n\n" . "Isi Laporan:\n" . $isi_input;

            $stmt = mysqli_prepare($koneksi, "INSERT INTO pengaduan (tgl_pengaduan, nik, isi_laporan, foto, status) VALUES (?, ?, ?, ?, '0')");
            mysqli_stmt_bind_param($stmt, "ssss", $tgl_pengaduan, $nik, $isi_laporan, $foto);
            if (mysqli_stmt_execute($stmt)) {
                $sukses = "Laporan berhasil dikirim!";
            } else {
                $error = "Gagal mengirim laporan: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Judul dan isi laporan tidak boleh kosong!";
        }
    }
}

// Ambil daftar pengaduan milik warga yang sedang login secara aman
$stmt_laporan = mysqli_prepare($koneksi, "SELECT p.*, t.tanggapan, t.tgl_tanggapan 
                                        FROM pengaduan p 
                                        LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
                                        WHERE p.nik = ? 
                                        ORDER BY p.tgl_pengaduan DESC");
mysqli_stmt_bind_param($stmt_laporan, "s", $nik);
mysqli_stmt_execute($stmt_laporan);
$result_laporans = mysqli_stmt_get_result($stmt_laporan);
$laporans = [];
while ($row = mysqli_fetch_assoc($result_laporans)) {
    $laporans[] = $row;
}
mysqli_stmt_close($stmt_laporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WargaSuara - Layanan Aspirasi & Pengaduan Online Rakyat</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22 fill=%22%230d6efd%22><path d=%22M5.338 1.59a61.44 61.44 0 0 0-2.834.858.75.75 0 0 0-.5.7v6.903c0 3.14 1.93 5.37 4.14 6.57l.19.103.19-.103c2.21-1.2 4.14-3.43 4.14-6.57V3.148a.75.75 0 0 0-.5-.7 61.44 61.44 0 0 0-2.834-.858l-.515-.132zm-.714 1.705c1.43-.372 2.87-.372 4.304 0 .584.152 1.157.348 1.716.586v4.622c0 2.455-1.465 4.314-3.57 5.394-2.105-1.08-3.57-2.94-3.57-5.394V3.881c.56-.238 1.132-.434 1.716-.586z%22/></svg>">
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
        .form-control, .form-select {
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
        .table th {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="bi bi-chat-square-text-fill me-2 fs-4"></i> Pelaporan Masyarakat
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small">Halo, <strong class="fs-6"><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3 fs-4">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0">Tulis Pengaduan</h5>
                                <small class="text-muted">Sampaikan keluhan Anda</small>
                            </div>
                        </div>

                        <?php if($error): ?>
                            <div class="alert alert-danger d-flex align-items-center rounded-3 p-2 small mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if($sukses): ?>
                            <div class="alert alert-success d-flex align-items-center rounded-3 p-2 small mb-3">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div><?= htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Judul Laporan</label>
                                <input type="text" name="judul" class="form-control" required placeholder="Contoh: Jalan Rusak di RT 05">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Isi Laporan / Keluhan</label>
                                <textarea name="isi_laporan" class="form-control" rows="4" required placeholder="Tuliskan detail kejadian, lokasi, dll..."></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-secondary">Foto Bukti (Opsional)</label>
                                <input type="file" name="foto" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100 text-white">
                                <i class="bi bi-send-fill me-1"></i> Kirim Laporan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3 fs-4">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0">Riwayat Pengaduan Saya</h5>
                                    <small class="text-muted">Daftar laporan yang pernah Anda kirim</small>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Isi Laporan</th>
                                        <th>Foto</th>
                                        <th>Status</th>
                                        <th>Tanggapan Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($laporans)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                Belum ada pengaduan yang dikirim.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach($laporans as $row): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= $no++ ?></td>
                                                <td><small class="text-muted"><?= htmlspecialchars($row['tgl_pengaduan'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                                <td>
                                                    <span class="small text-secondary"><?= nl2br(htmlspecialchars($row['isi_laporan'], ENT_QUOTES, 'UTF-8')) ?></span>
                                                </td>
                                                <td>
                                                    <?php if($row['foto']): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                                            <img src="uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" width="55" height="55" class="rounded-3 object-fit-cover shadow-sm">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Tidak ada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($row['status'] == '0'): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1">Menunggu</span>
                                                    <?php elseif($row['status'] == 'proses'): ?>
                                                        <span class="badge bg-info text-white rounded-pill px-3 py-1">Diproses</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success rounded-pill px-3 py-1">Selesai</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if(!empty($row['tanggapan'])): ?>
                                                        <div class="small text-dark"><?= nl2br(htmlspecialchars($row['tanggapan'], ENT_QUOTES, 'UTF-8')) ?></div>
                                                        <small class="text-muted d-block mt-1"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($row['tgl_tanggapan'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">Belum ditanggapi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>