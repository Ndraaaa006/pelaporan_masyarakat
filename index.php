<?php
/**
 * WargaSuara - Layanan Aspirasi & Pengaduan Online Rakyat
 */

// Konfigurasi Session yang Aman
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

session_start();
require_once 'config/koneksi.php';

$query_selesai = null;
// Menggunakan ORDER BY pengaduan.id_pengaduan DESC agar data terbaru tampil di urutan paling atas
$stmt = mysqli_prepare($koneksi, "SELECT pengaduan.*, masyarakat.nama, tanggapan.foto AS foto_tanggapan, tanggapan.tanggapan 
    FROM pengaduan 
    JOIN masyarakat ON pengaduan.nik = masyarakat.nik 
    LEFT JOIN tanggapan ON pengaduan.id_pengaduan = tanggapan.id_pengaduan
    WHERE pengaduan.status = ? 
    ORDER BY pengaduan.id_pengaduan DESC LIMIT 6");

if ($stmt) {
    $status = 'selesai';
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    $query_selesai = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WargaSuara — Suara Rakyat, Solusi Cepat</title>
    <!-- Icon Megafon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22 fill=%22%232a5298%22><path d=%22M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-1.773l-.405.012A1.5 1.5 0 0 1 4.5 10.3V5.7a1.5 1.5 0 0 1 1.183-1.469l.405-.012c2.422-.07 4.75-.672 6.912-1.773V2.5zM3 4.5a.5.5 0 0 0-.5.5v6a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5z%22/></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bs-body-font-family: 'Plus Jakarta Sans', sans-serif;
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        body {
            font-family: var(--bs-body-font-family);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar {
            backdrop-filter: blur(12px);
            background-color: rgba(255, 255, 255, 0.85) !important;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        [data-bs-theme="dark"] .navbar {
            background-color: rgba(15, 23, 42, 0.85) !important;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .hero-section {
            background: var(--primary-gradient);
            color: #ffffff;
            padding: 110px 0 130px 0;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            pointer-events: none;
        }
        .custom-card {
            border: none;
            border-radius: 16px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            background: var(--bs-body-bg);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .custom-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .icon-box {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(42, 82, 152, 0.1);
            color: #2a5298;
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
        }
        .badge-soft {
            background-color: rgba(42, 82, 152, 0.1);
            color: #2a5298;
            font-weight: 600;
        }
        [data-bs-theme="dark"] .badge-soft {
            background-color: rgba(56, 122, 232, 0.15);
            color: #60a5fa;
        }
        .btn-main {
            background-color: #2a5298;
            border: none;
            color: #fff;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        .btn-main:hover {
            background-color: #1e3c72;
            color: #fff;
        }
        footer {
            background-color: #0f172a;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-dark text-decoration-none" href="index.php">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                    <i class="bi bi-megaphone-fill fs-6"></i>
                </div>
                <span class="fs-5 tracking-tight">Warga<span class="text-primary">Suara</span></span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center gap-lg-3">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill fw-medium" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill fw-medium" href="#alur">Alur Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-pill fw-medium" href="#aduan">Aduan Selesai</a>
                    </li>
                    <li class="nav-item my-2 my-lg-0">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 border-opacity-25" id="darkModeToggle" title="Ganti Tema">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                            <span id="themeText" class="small fw-semibold">Tema</span>
                        </button>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <?php if (isset($_SESSION['login']) || isset($_SESSION['user_id']) || isset($_SESSION['nik']) || isset($_SESSION['id_petugas'])): ?>
                            <a href="dashboard.php" class="btn btn-main rounded-pill px-4 py-2 shadow-sm">Dashboard Saya</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-main rounded-pill px-4 py-2 shadow-sm">Masuk / Daftar</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container position-relative py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <span class="badge badge-soft px-3 py-2 rounded-pill mb-3 text-white bg-white bg-opacity-10 border border-white border-opacity-25">
                        <i class="bi bi-megaphone-fill text-info me-1"></i> Portal Aspirasi & Pengaduan Resmi Masyarakat
                    </span>
                    <h1 class="display-5 fw-bold mb-3 lh-tight">Suara Anda Penentu Kemajuan Bersama</h1>
                    <p class="lead mb-4 text-white-50 mx-auto fs-6" style="max-width: 600px;">
                        Sampaikan keluhan, kritik, atau saran pembangunan fasilitas umum secara transparan dan langsung ditangani oleh instansi berwenang.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="dashboard.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 py-3 shadow">
                            <i class="bi bi-pencil-square me-1"></i> Buat Laporan Baru
                        </a>
                        <a href="#alur" class="btn btn-outline-light fw-bold rounded-pill px-4 py-3">
                            Pelajari Alur <i class="bi bi-arrow-down ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alur Layanan Section -->
    <section id="alur" class="py-5 my-3">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-2">Bagaimana Cara Kerjanya?</h2>
                <p class="text-secondary small">Proses pengaduan dirancang cepat, ringkas, dan terpantau secara real-time.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="custom-card p-4 h-100">
                        <div class="icon-box">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <h5 class="fw-bold mb-2">1. Autentikasi Akun</h5>
                        <p class="text-secondary small mb-0">Masuk atau daftar dengan identitas warga yang sah agar laporan tervalidasi dengan baik oleh sistem.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="custom-card p-4 h-100">
                        <div class="icon-box">
                            <i class="bi bi-file-earmark-richtext"></i>
                        </div>
                        <h5 class="fw-bold mb-2">2. Tulis & Unggah Bukti</h5>
                        <p class="text-secondary small mb-0">Deskripsikan permasalahan secara detail dan lampirkan foto dokumentasi kondisi di lapangan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="custom-card p-4 h-100">
                        <div class="icon-box">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <h5 class="fw-bold mb-2">3. Tindak Lanjut Petugas</h5>
                        <p class="text-secondary small mb-0">Laporan diverifikasi dan dikerjakan oleh instansi terkait hingga status berubah menjadi selesai.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Aduan Selesai -->
    <section id="aduan" class="py-5 bg-body-tertiary border-top border-bottom">
        <div class="container py-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
                <div>
                    <h2 class="fw-bold mb-1">Aduan Publik Selesai</h2>
                    <p class="text-secondary small mb-0">Daftar laporan warga yang berhasil diselesaikan beserta foto bukti penanganan dari petugas lapangan.</p>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold align-self-start">
                    <i class="bi bi-check-circle-fill me-1"></i> Transparansi Teruji
                </span>
            </div>

            <div class="row g-4">
                <?php
                if ($query_selesai && mysqli_num_rows($query_selesai) > 0) {
                    while ($row = mysqli_fetch_assoc($query_selesai)) {
                        $foto = !empty($row['foto_tanggapan']) ? $row['foto_tanggapan'] : ($row['foto'] ?? '');
                        $safe_foto = basename($foto);
                        $path_foto = 'uploads/' . $safe_foto;
                ?>
                    <div class="col-md-4">
                        <div class="custom-card h-100 overflow-hidden d-flex flex-column">
                            <?php if (!empty($safe_foto) && file_exists($path_foto)) { ?>
                                <a href="<?= htmlspecialchars($path_foto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" title="Klik untuk memperbesar foto">
                                    <img src="<?= htmlspecialchars($path_foto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="w-100" style="height: 200px; object-fit: cover;" alt="Bukti Penanganan Selesai">
                                </a>
                            <?php } else { ?>
                                <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            <?php } ?>

                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 small fw-semibold">Selesai</span>
                                    <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($row['tgl_pengaduan'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                                </div>
                                <h6 class="fw-bold text-dark mb-2 text-truncate"><i class="bi bi-person-fill text-primary me-1"></i> <?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h6>
                                <p class="text-secondary small flex-grow-1 mb-3" style="line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars(substr($row['isi_laporan'] ?? '', 0, 90), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>...
                                </p>
                                <div class="bg-body-secondary rounded-3 p-2 text-center mt-auto">
                                    <small class="text-success fw-semibold">
                                        <i class="bi bi-check2-all me-1"></i> 
                                        <?= !empty($row['tanggapan']) ? htmlspecialchars(substr($row['tanggapan'], 0, 45)).'...' : 'Penanganan Selesai' ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    }
                    mysqli_free_result($query_selesai);
                } else {
                    echo '<div class="col-12 text-center py-5">
                            <div class="p-5 border rounded-4 bg-body text-muted shadow-sm">
                                <i class="bi bi-inbox fs-2 mb-2 d-block text-primary"></i>
                                <h6 class="fw-bold mb-1">Belum Ada Aduan Selesai</h6>
                                <p class="small mb-0 text-secondary">Aduan yang telah rampung ditangani akan muncul otomatis di ruang publik ini.</p>
                            </div>
                          </div>';
                }

                if ($stmt) {
                    mysqli_stmt_close($stmt);
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-5">
                    <h5 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-megaphone-fill fs-6"></i>
                        </div>
                        WargaSuara
                    </h5>
                    <p class="small text-secondary mb-3" style="line-height: 1.7; max-width: 380px;">
                        Layanan pengaduan masyarakat terpadu untuk mewujudkan pelayanan publik yang lebih responsif, bersih, dan akuntabel.
                    </p>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold text-white mb-3">Tautan Navigasi</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a href="index.php" class="text-secondary text-decoration-none hover-white">Beranda Utama</a></li>
                        <li><a href="#alur" class="text-secondary text-decoration-none hover-white">Alur Pelayanan</a></li>
                        <li><a href="#aduan" class="text-secondary text-decoration-none hover-white">Daftar Aduan Selesai</a></li>
                        <li><a href="login.php" class="text-secondary text-decoration-none hover-white">Masuk Sistem</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-white mb-3">Kontak & Layanan</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-secondary">
                        <li><i class="bi bi-geo-alt text-primary me-2"></i> Jl. Malioboro No. 12, Yogyakarta</li>
                        <li>
                            <a href="https://wa.me/6282240212641?text=Halo%20Admin%20WargaSuara" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none">
                                <i class="bi bi-whatsapp text-success me-2"></i> (+62) 822-4021-2641
                            </a>
                        </li>
                        <li><i class="bi bi-envelope text-info me-2"></i> layanan@wargasuara.go.id</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small text-secondary">
                    &copy; <?= date('Y') ?> WargaSuara. Hak Cipta Dilindungi Undang-Undang.
                </div>
                <div class="col-md-6 text-center text-md-end small text-secondary mt-2 mt-md-0">
                    Sistem Aplikasi Web Profesional
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Dark/Light Mode
        const htmlElement = document.documentElement;
        const toggleBtn = document.getElementById('darkModeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');

        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateUI(savedTheme);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                let current = htmlElement.getAttribute('data-bs-theme');
                let next = current === 'light' ? 'dark' : 'light';
                htmlElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('theme', next);
                updateUI(next);
            });
        }

        function updateUI(theme) {
            if (!themeIcon) return;
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun-fill text-warning';
                if(themeText) themeText.textContent = 'Terang';
            } else {
                themeIcon.className = 'bi bi-moon-fill';
                if(themeText) themeText.textContent = 'Gelap';
            }
        }
    </script>
</body>
</html>