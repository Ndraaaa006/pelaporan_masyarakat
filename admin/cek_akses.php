<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'petugas')) {
    header("Location: ../login.php?pesan=belum_login");
    exit();
}
?>