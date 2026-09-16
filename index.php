<?php
session_start();
require_once 'config/koneksi.php';

$query_selesai = null;
$stmt = mysqli_prepare($koneksi, "SELECT pengaduan.*, masyarakat.nama 
    FROM pengaduan 
    JOIN masyarakat ON pengaduan.nik = masyarakat.nik 
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
    <title>WargaSuara - Layanan Aspirasi & Pengaduan Online Rakyat</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22 fill=%22%230d6efd%22><path d=%22M5.338 1.59a61.44 61.44 0 0 0-2.834.858.75.75 0 0 0-.5.7v6.903c0 3.14 1.93 5.37 4.14 6.57l.19.103.19-.103c2.21-1.2 4.14-3.43 4.14-6.57V3.148a.75.75 0 0 0-.5-.7 61.44 61.44 0 0 0-2.834-.858l-.515-.132zm-.714 1.705c1.43-.372 2.87-.372 4.304 0 .584.152 1.157.348 1.716.586v4.622c0 2.455-1.465 4.314-3.57 5.394-2.105-1.08-3.57-2.94-3.57-5.394V3.881c.56-.238 1.132-.434 1.716-.586z%22/></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body, .navbar, .card, footer, .bg-white {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 80px 0;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
        }
        [data-bs-theme="dark"] .hero-section {
            background: linear-gradient(135deg, #1b263b 0%, #0d1b2a 100%);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-size: 1.5rem;
            margin: 0 auto 15px auto;
        }
        .card-aduan {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-aduan:hover {
            transform: translateY(-5px);
        }
        /* Menyoroti menu aktif dengan tegas */
        .navbar-nav .nav-link.active {
            color: #0d6efd !important;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Navbar dengan ID agar mudah dideteksi scroll -->
    <nav class="navbar navbar-expand-lg shadow-sm py-3 sticky-top bg-body">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="index.php">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <span>Warga<span class="text-info">Suara</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#cara-kerja">Cara Kerja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#aduan-selesai">Aduan Selesai</a>
                    </li>
                    
                    <!-- Tombol Switch Dark / Light Mode -->
                    <li class="nav-item">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-2" id="darkModeToggle" title="Ubah Tema Tampilan">
                            <i class="bi bi-moon-fill" id="themeIcon"></i>
                            <span id="themeText" class="small">Tema</span>
                        </button>
                    </li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <?php if (isset($_SESSION['login']) || isset($_SESSION['user_id']) || isset($_SESSION['nik'])): ?>
                            <a href="dashboard.php" class="btn btn-primary rounded-pill px-4">Dashboard Saya</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary rounded-pill px-4">Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section / Beranda -->
    <section id="beranda" class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <span class="badge bg-light text-primary px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">Layanan Aspirasi & Pengaduan Online Resmi</span>
                    <h1 class="display-4 fw-bold mb-3">Sampaikan Keluhan Anda untuk Lingkungan Lebih Baik</h1>
                    <p class="lead mb-4 text-white-50">Sampaikan laporan, keluhan, atau aspirasi pembangunan di sekitar Anda dengan mudah, cepat, dan transparan.</p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="dashboard.php" class="btn btn-light btn-lg fw-bold rounded-pill px-4 shadow">
                            <i class="bi bi-pencil-square me-2"></i>Buat Pengaduan Sekarang
                        </a>
                        <a href="#cara-kerja" class="btn btn-outline-light btn-lg fw-bold rounded-pill px-4">
                            Pelajari Alur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cara Kerja Section -->
    <section id="cara-kerja" class="py-5 border-top">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Alur Pengaduan</h2>
                <p class="text-muted">3 langkah mudah menyampaikan aspirasi Anda</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="feature-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h5 class="fw-bold">1. Login / Daftar</h5>
                        <p class="text-muted small">Masuk menggunakan akun warga yang sudah terdaftar di sistem.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="feature-icon">
                            <i class="bi bi-chat-square-text-fill"></i>
                        </div>
                        <h5 class="fw-bold">2. Tulis Laporan</h5>
                        <p class="text-muted small">Tuliskan judul, detail keluhan, dan unggah foto bukti kejadian di lapangan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="feature-icon">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h5 class="fw-bold">3. Pantau Status</h5>
                        <p class="text-muted small">Tunggu petugas memverifikasi dan melihat tanggapan langsung di dashboard Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Aduan Selesai -->
    <section id="aduan-selesai" class="py-5 bg-body-tertiary">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Aduan Masyarakat yang Telah Selesai</h2>
                <p class="text-muted">Bukti transparansi penanganan laporan warga oleh petugas berwenang</p>
            </div>

            <div class="row g-4">
                <?php
                if ($query_selesai && mysqli_num_rows($query_selesai) > 0) {
                    while ($row = mysqli_fetch_assoc($query_selesai)) {
                        $foto = $row['foto'] ?? '';
                        $path_foto = 'uploads/' . basename($foto);
                ?>
                    <div class="col-md-4">
                        <div class="card card-aduan h-100 overflow-hidden">
                            <?php if (!empty($foto) && file_exists($path_foto)) { ?>
                                <a href="<?= htmlspecialchars($path_foto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" title="Klik untuk melihat foto ukuran penuh">
                                    <img src="<?= htmlspecialchars($path_foto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Foto Bukti Aduan">
                                </a>
                            <?php } else { ?>
                                <img src="https://via.placeholder.com/400x200?text=Tanpa+Foto+Bukti" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Default Image">
                            <?php } ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success rounded-pill px-3 py-1">Selesai</span>
                                    <small class="text-muted"><?= htmlspecialchars($row['tgl_pengaduan'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                                </div>
                                
                                <h6 class="fw-bold mb-1">Pelapor: <?= htmlspecialchars($row['nama'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h6>
                                <p class="card-text text-secondary small flex-grow-1">
                                    <?= nl2br(htmlspecialchars(substr($row['isi_laporan'] ?? '', 0, 100), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>...
                                </p>
                                
                                <hr class="my-2 border-secondary opacity-25">
                                <div class="text-center">
                                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Telah Ditindaklanjuti Petugas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    }
                    mysqli_free_result($query_selesai);
                } else {
                    echo '<div class="col-12 text-center py-4">
                            <div class="alert alert-info rounded-4 p-4 shadow-sm">
                                <h5 class="fw-bold mb-1">Belum ada aduan yang selesai ditangani</h5>
                                <p class="mb-0">Aduan masyarakat yang telah selesai diproses oleh petugas akan otomatis tampil di sini.</p>
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

    <!-- Footer Modern -->
    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <h5 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check"></i>WargaSuara
                    </h5>
                    <p class="text-white-50 small">
                        Kanal aspirasi dan pengaduan resmi masyarakat. Wujudkan pelayanan publik yang cepat, transparan, dan akuntabel.
                    </p>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold mb-3">Tautan Cepat</h6>
                    <ul class="list-unstyled small text-white-50">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Beranda</a></li>
                        <li class="mb-2"><a href="#cara-kerja" class="text-white-50 text-decoration-none">Cara Kerja</a></li>
                        <li class="mb-2"><a href="#aduan-selesai" class="text-white-50 text-decoration-none">Aduan Selesai</a></li>
                        <li class="mb-2"><a href="login.php" class="text-white-50 text-decoration-none">Login Warga / Petugas</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold mb-3">Kontak Instansi</h6>
                    <ul class="list-unstyled small text-white-50">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i> Jl. AmbarKetawang No. 45, Yogyakarta</li>
                        <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i> (+62) 822-4021-2641</li>
                        <li class="mb-2"><i class="bi bi-envelope-fill me-2"></i> ciksup@wargasuara.go.id</li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3">Jam Layanan</h6>
                    <p class="text-white-50 small mb-1">Setiap Hari</p>
                    <p class="fw-bold small mb-3">08.00 - 16.00 WIB</p>
                    <span class="badge bg-primary text-wrap">Sistem Online 24 Jam</span>
                </div>
            </div>

            <hr class="border-secondary">

            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-white-50">&copy; <?= htmlspecialchars(date('Y'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> WargaSuara. Hak Cipta Dilindungi.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-white-50">Sistem Layanan Publik Berbasis Web</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script JavaScript untuk Dark Mode & Scroll Spy (Menu aktif otomatis berubah) -->
    <script>
        // 1. Logika Dark / Light Mode
        const htmlElement = document.documentElement;
        const toggleBtn = document.getElementById('darkModeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');

        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateButtonUI(savedTheme);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                let currentTheme = htmlElement.getAttribute('data-bs-theme');
                let newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                htmlElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateButtonUI(newTheme);
            });
        }

        function updateButtonUI(theme) {
            if (!themeIcon) return;
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun-fill text-warning';
                if(themeText) themeText.textContent = 'Terang';
            } else {
                themeIcon.className = 'bi bi-moon-fill';
                if(themeText) themeText.textContent = 'Gelap';
            }
        }

        // 2. Logika Scroll Spy (Menu Berubah Aktif Otomatis saat Layar Digulir)
        const sections = document.querySelectorAll('header, section');
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (window.pageYOffset >= (sectionTop - 150)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current || (current === 'beranda' && link.getAttribute('href') === 'index.php')) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>