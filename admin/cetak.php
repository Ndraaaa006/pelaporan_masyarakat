<?php
session_start();

// 1. Keamanan Sesi & Otorisasi Ketat
if (!isset($_SESSION['level']) || ($_SESSION['level'] !== 'admin' && $_SESSION['level'] !== 'petugas')) {
    header('Location: ../index.php');
    exit;
}

// 2. Proteksi Session Hijacking / Fixation Sederhana
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) { // Session expired dalam 30 menit
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

require_once '../config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Pengaduan Masyarakat</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .cetak-header { text-align: center; margin-bottom: 20px; }
        .cetak-header h2, .cetak-header p { margin: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; font-size: 13px; }
        th { background-color: #f4f4f4; text-align: left; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="cetak-header">
        <h2>LAPORAN PENGADUAN MASYARAKAT</h2>
        <p>Aplikasi Pelayanan Pengaduan Masyarakat (UKK RPL)</p>
        <hr style="border: 1px solid #333; margin-top: 10px;">
    </div>

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px;">Cetak Dokumen</button>
        <a href="dashboard.php" style="margin-left: 10px; text-decoration: none; color: #555;">Kembali ke Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="15%">NIK Pelapor</th>
                <th width="40%">Isi Laporan</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            
            // 3. Menggunakan Prepared Statement untuk Mencegah SQL Injection (walaupun tidak ada parameter GET/POST)
            $stmt = mysqli_prepare($koneksi, "SELECT id_pengaduan, tgl_pengaduan, nik, isi_laporan, status FROM pengaduan ORDER BY tgl_pengaduan DESC");
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($data = mysqli_fetch_assoc($result)) {
                // 4. Sanitasi Output dengan htmlspecialchars untuk Mencegah XSS (Cross-Site Scripting)
                $tgl_pengaduan = htmlspecialchars($data['tgl_pengaduan'], ENT_QUOTES, 'UTF-8');
                $nik           = htmlspecialchars($data['nik'], ENT_QUOTES, 'UTF-8');
                $isi_laporan   = htmlspecialchars($data['isi_laporan'], ENT_QUOTES, 'UTF-8');
                $status        = htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8');

                // Konversi tampilan status agar lebih rapi
                if ($status == '0') {
                    $text_status = 'Pending';
                } elseif ($status == 'proses') {
                    $text_status = 'Diproses';
                } else {
                    $text_status = 'Selesai';
                }
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= $tgl_pengaduan; ?></td>
                <td><?= $nik; ?></td>
                <td><?= $isi_laporan; ?></td>
                <td class="text-center"><?= $text_status; ?></td>
            </tr>
            <?php 
            }
            // Tutup statement
            mysqli_stmt_close($stmt);
            ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; float: right; text-align: center;">
        <p>Mengetahui,</p>
        <p><strong>Administrator / Petugas</strong></p>
        <br><br><br>
        <p><u>( ......................................... )</u></p>
    </div>

</body>
</html>