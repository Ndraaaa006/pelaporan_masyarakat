<?php
session_start();
require_once '../config/koneksi.php';

// Cek apakah sudah login (disamakan persis dengan dashboard.php)
if (!isset($_SESSION['id_petugas']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Ambil data foto untuk dihapus dari folder uploads (jika ada)
    $stmt_foto = mysqli_prepare($koneksi, "SELECT foto FROM pengaduan WHERE id_pengaduan = ?");
    mysqli_stmt_bind_param($stmt_foto, "i", $id);
    mysqli_stmt_execute($stmt_foto);
    $res_foto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_foto));
    mysqli_stmt_close($stmt_foto);

    if ($res_foto && !empty($res_foto['foto'])) {
        $path_foto = '../uploads/' . $res_foto['foto'];
        if (file_exists($path_foto)) {
            unlink($path_foto);
        }
    }

    // Hapus data tanggapan terkait terlebih dahulu (jika ada)
    $stmt_del_tanggapan = mysqli_prepare($koneksi, "DELETE FROM tanggapan WHERE id_pengaduan = ?");
    mysqli_stmt_bind_param($stmt_del_tanggapan, "i", $id);
    mysqli_stmt_execute($stmt_del_tanggapan);
    mysqli_stmt_close($stmt_del_tanggapan);

    // Hapus data pengaduan utama
    $stmt_del_pengaduan = mysqli_prepare($koneksi, "DELETE FROM pengaduan WHERE id_pengaduan = ?");
    mysqli_stmt_bind_param($stmt_del_pengaduan, "i", $id);
    mysqli_stmt_execute($stmt_del_pengaduan);
    mysqli_stmt_close($stmt_del_pengaduan);
}

header("Location: dashboard.php");
exit();