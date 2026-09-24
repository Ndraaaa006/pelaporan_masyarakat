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
} else if (time() - $_SESSION['CREATED'] > 1800) { 
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
        body { font-family: Arial, sans-serif; color: #333; margin: 20px; background: #fff; }
        .cetak-header { text-align: center; margin-bottom: 20px; }
        .cetak-header h2, .cetak-header p { margin: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; padding: 8px; font-size: 13px; }
        th { background-color: #f4f4f4; text-align: left; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e2e8f0;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0d6efd; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">
            🖨️ Cetak / Simpan PDF
        </button>
        <span style="font-size: 12px; color: #666; margin-left: 10px;">Klik tombol ini untuk cetak dokumen.</span>
    </div>

    <div class="cetak-header">
        <h2>LAPORAN PENGADUAN MASYARAKAT</h2>
        <p>Aplikasi Pelayanan Pengaduan Masyarakat (UKK RPL)</p>
        <hr style="border: 1px solid #333; margin-top: 10px;">
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
            
            // 3. Menggunakan Prepared Statement untuk Mencegah SQL Injection
            $stmt = mysqli_prepare($koneksi, "SELECT id_pengaduan, tgl_pengaduan, nik, isi_laporan, status FROM pengaduan ORDER BY tgl_pengaduan DESC");
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($data = mysqli_fetch_assoc($result)) {
                // 4. Sanitasi Output dengan htmlspecialchars untuk Mencegah XSS
                $tgl_pengaduan = htmlspecialchars($data['tgl_pengaduan'], ENT_QUOTES, 'UTF-8');
                $nik           = htmlspecialchars($data['nik'], ENT_QUOTES, 'UTF-8');
                $isi_laporan   = htmlspecialchars($data['isi_laporan'], ENT_QUOTES, 'UTF-8');
                $status        = htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8');

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