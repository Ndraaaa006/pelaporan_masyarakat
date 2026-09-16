<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login sebagai masyarakat
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'masyarakat') {
    header("Location: login.php?pesan=akses_ditolak");
    exit();
}