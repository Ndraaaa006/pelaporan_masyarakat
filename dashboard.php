<?php
session_start();
require_once 'config/koneksi.php';
require_once 'cek_akses_warga.php';

// 1. Keamanan Autentikasi Ketat & Anti Session Hijacking
if (!isset($_SESSION['nik']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?pesan=expired");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Generate CSRF Token untuk Keamanan Formulir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$nik = $_SESSION['nik'] ?? $_SESSION['user_id'];
$nama = $_SESSION['nama'] ?? 'Warga';

$error = '';

// 2. Proses Kirim Pengaduan dengan Keamanan Tingkat Tinggi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Kesalahan keamanan validasi token (CSRF Token Mismatch). Silakan muat ulang halaman.";
    } else {
        $tgl_pengaduan = date('Y-m-d');
        $judul         = trim($_POST['judul'] ?? '');
        $isi_input     = trim($_POST['isi_laporan'] ?? '');
        $foto          = '';

        // Validasi Panjang Karakter untuk Mencegah Payload Berlebih
        if (mb_strlen($judul) > 150) {
            $error = "Judul terlalu panjang! Maksimal 150 karakter.";
        } elseif (mb_strlen($isi_input) > 2000) {
            $error = "Isi laporan terlalu panjang! Maksimal 2000 karakter.";
        } else {
            // Handle Upload Foto Secara Ketat & Aman
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png'];
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                finfo_close($finfo);
                $allowed_mimes = ['image/jpeg', 'image/png'];

                if (in_array($ext, $allowed_ext) && in_array($mime_type, $allowed_mimes)) {
                    if ($_FILES['foto']['size'] <= 2 * 1024 * 1024) { // Maksimal 2MB
                        $foto = preg_replace("/[^a-zA-Z0-9]/", "", $nik) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (!is_dir('uploads')) {
                            mkdir('uploads', 0755, true);
                        }
                        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
                    } else {
                        $error = "Ukuran foto terlalu besar! Batas maksimal adalah 2MB.";
                    }
                } else {
                    $error = "Format file foto tidak diizinkan. Hanya menerima ekstensi JPG dan PNG yang aman.";
                }
            }
        }

        if (empty($error)) {
            if (!empty($judul) && !empty($isi_input)) {
                $isi_laporan = "Judul: " . $judul . "\n\n" . "Isi Laporan:\n" . $isi_input;

                $stmt = mysqli_prepare($koneksi, "INSERT INTO pengaduan (tgl_pengaduan, nik, isi_laporan, foto, status) VALUES (?, ?, ?, ?, '0')");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $tgl_pengaduan, $nik, $isi_laporan, $foto);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header("Location: " . $_SERVER['PHP_SELF'] . "?status=sukses");
                        exit;
                    } else {
                        $error = "Gagal menyimpan data ke database.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = "Terjadi kesalahan pada sistem database.";
                }
            } else {
                $error = "Kolom Judul dan Isi Laporan wajib diisi.";
            }
        }
    }
}

$is_sukses = (isset($_GET['status']) && $_GET['status'] === 'sukses');

// 3. Ambil Riwayat Pengaduan Menggunakan Prepared Statement
$stmt_laporan = mysqli_prepare($koneksi, "SELECT p.*, t.tanggapan, t.tgl_tanggapan 
                                        FROM pengaduan p 
                                        LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
                                        WHERE p.nik = ? 
                                        ORDER BY p.tgl_pengaduan DESC");
$laporans = [];
if ($stmt_laporan) {
    mysqli_stmt_bind_param($stmt_laporan, "s", $nik);
    mysqli_stmt_execute($stmt_laporan);
    $result_laporans = mysqli_stmt_get_result($stmt_laporan);
    while ($row = mysqli_fetch_assoc($result_laporans)) {
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
    <title>Portal Aspirasi & Layanan Pengaduan Warga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22 fill=%22%232a5298%22><path d=%22M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-1.773l-.405.012A1.5 1.5 0 0 1 4.5 10.3V5.7a1.5 1.5 0 0 1 1.183-1.469l.405-.012c2.422-.07 4.75-.672 6.912-1.773V2.5zM3 4.5a.5.5 0 0 0-.5.5v6a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5z%22/></svg>">
    <style>
        :root {
            --brand-dark: #0f172a;
            --brand-primary: #2563eb;
            --brand-surface: #ffffff;
            --brand-bg: #f8fafc;
        }
        body {
            background-color: var(--brand-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #334155;
            letter-spacing: -0.01em;
        }
        .navbar-modern {
            background-color: var(--brand-surface);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .card-custom {
            background-color: var(--brand-surface);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
            transition: all 0.2s ease-in-out;
        }
        .form-control {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.925rem;
            color: #0f172a;
            transition: all 0.15s ease;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn-submit {
            background-color: var(--brand-dark);
            color: #ffffff;
            border-radius: 8px;
            padding: 11px;
            font-weight: 500;
            font-size: 0.925rem;
            border: none;
            transition: background-color 0.15s ease, transform 0.1s ease;
        }
        .btn-submit:hover {
            background-color: #1e293b;
            color: #ffffff;
        }
        .table-custom th {
            background-color: #f1f5f9 !important;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-custom td {
            padding: 14px;
            vertical-align: middle;
            font-size: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-modern sticky-top py-3 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center" href="#">
                <div class="bg-primary text-white p-2 rounded-2 me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-shield-check fs-6"></i>
                </div>
                <span>Layanan Warga</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <span class="d-block text-muted" style="font-size: 0.75rem;">Masuk Sebagai</span>
                    <strong class="text-dark small"><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-2 fw-medium"><i class="bi bi-power me-1"></i> Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-custom p-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="fw-bold m-0 text-dark">Buat Laporan Baru</h6>
                    </div>
                    <p class="text-muted small mb-4">Gunakan formulir berikut untuk melaporkan kendala fasilitas atau layanan publik di lingkungan Anda.</p>

                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 p-3 small mb-4 d-flex align-items-start">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-6 mt-0.5"></i>
                            <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Judul Laporan</label>
                            <input type="text" name="judul" class="form-control" required placeholder="Ringkasan singkat masalah...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Detail / Isi Laporan</label>
                            <textarea name="isi_laporan" class="form-control" rows="4" required placeholder="Jelaskan lokasi lengkap dan kronologi kejadian..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Foto Bukti (Opsional)</label>
                            <input type="file" name="foto" class="form-control" accept=".jpg, .jpeg, .png">
                            <div class="form-text" style="font-size: 0.75rem;">Format JPG/PNG, ukuran maksimum 2MB.</div>
                        </div>
                        <button type="submit" class="btn btn-submit w-100 shadow-sm">
                            <i class="bi bi-send me-1"></i> Kirim Laporan Sekarang
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold m-0 text-dark">Riwayat Laporan Saya</h6>
                            <span class="text-muted small">Pantau status penanganan pengaduan Anda secara berkala</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="6%">No</th>
                                    <th width="15%">Tanggal</th>
                                    <th width="28%">Isi Laporan</th>
                                    <th width="10%" class="text-center">Foto</th>
                                    <th width="14%">Status</th>
                                    <th width="27%">Tanggapan Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($laporans)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted fst-italic">
                                            <i class="bi bi-folder2-open fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                            Belum ada riwayat laporan yang tercatat dalam sistem.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach($laporans as $row): ?>
                                        <tr>
                                            <td class="text-center fw-semibold text-muted"><?= $no++ ?></td>
                                            <td><span class="text-secondary small"><?= htmlspecialchars($row['tgl_pengaduan'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td>
                                                <div class="text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($row['isi_laporan'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= nl2br(htmlspecialchars($row['isi_laporan'], ENT_QUOTES, 'UTF-8')) ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if(!empty($row['foto'])): ?>
                                                    <a href="uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                                        <img src="uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" width="42" height="42" class="rounded-2 object-fit-cover border shadow-sm">
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small" style="font-size: 0.75rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($row['status'] == '0'): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2.5 py-1 rounded-2 fw-medium">Menunggu</span>
                                                <?php elseif($row['status'] == 'proses'): ?>
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1 rounded-2 fw-medium">Diproses</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-2 fw-medium">Selesai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($row['tanggapan'])): ?>
                                                    <div class="text-dark small mb-0"><?= nl2br(htmlspecialchars($row['tanggapan'], ENT_QUOTES, 'UTF-8')) ?></div>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($row['tgl_tanggapan'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic" style="font-size: 0.75rem;">Menunggu verifikasi petugas</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        <?php if ($is_sukses): ?>
        Swal.fire({
            icon: 'success',
            title: 'Terimakasih Atas Laporan yang Anda Berikan',
            html: 'Jika dalam 1x24 jam tidak ada respon harap hubungi kontak dibawah ini:<br><br>' +
                  '<a href="https://wa.me/6282240212641" target="_blank" class="btn btn-success btn-sm px-3 py-2 rounded-2 text-decoration-none fw-semibold">' +
                  '<i class="bi bi-whatsapp me-2 fs-6"></i>082240212641</a>',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#0f172a',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>