<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login DAN levelnya admin atau petugas
if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'petugas')) {
    header("Location: ../login.php?pesan=akses_ditolak");
    exit();
}