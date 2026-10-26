<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['level'] != 'User') {
    header("location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Proses Simpan Bukti Pembayaran
if (isset($_POST['upload_bayar'])) {
    $nama_rekening   = mysqli_real_escape_string($conn, $_POST['nama_rekening']);
    $no_rekening     = mysqli_real_escape_string($conn, $_POST['no_rekening']);
    $nama_bank       = mysqli_real_escape_string($conn, $_POST['nama_bank']);
    $metode_bayar    = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $invoice         = 'INV-' . time();
    $tgl_bayar       = date('Y-m-d H:i:s');
    
    // Konfigurasi Upload File Foto
    $nama_file       = $_FILES['bukti_trf']['name'];
    $ukuran_file     = $_FILES['bukti_trf']['size'];
    $tmp_file        = $_FILES['bukti_trf']['tmp_name'];
    $ekstensi_diizinkan = array('png', 'jpg', 'jpeg');
    $x               = explode('.', $nama_file);
    $ekstensi        = strtolower(end($x));

    // Pastikan folder 'uploads' sudah ada di direktori kost/
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    if (in_array($ekstensi, $ekstensi_diizinkan) === true) {
        $nama_file_baru = uniqid() . '_' . $nama_file;
        $lokasi_tujuan  = 'uploads/' . $nama_file_baru;

        if (move_uploaded_file($tmp_file, $lokasi_tujuan)) {
            // Masukkan ke tabel kost_pembayaran dengan status 'Verifikasi'
            $query = "INSERT INTO kost_pembayaran (invoice, nama_rekening, no_rekening, nama_bank, metode_pembayaran, bukti_trf, tgl_bayar, status) 
                      VALUES ('$invoice', '$nama_rekening', '$no_rekening', '$nama_bank', '$metode_bayar', '$nama_file_baru', '$tgl_bayar', 'Verifikasi')";
            
            if (mysqli_query($conn, $query)) {
                echo "<script>alert('Bukti pembayaran berhasil dikirim! Menunggu verifikasi Admin.'); window.location='member_dashboard.php';</script>";
            } else {
                echo "<script>alert('Gagal menyimpan ke database!');</script>";
            }
        } else {
            echo "<script>alert('Gagal mengunggah file foto!');</script>";
        }
    } else {
        echo "<script>alert('Format file harus PNG, JPG, atau JPEG!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Bukti Pembayaran - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="member_dashboard.php"><i class="bi bi-upload me-2"></i>Konfirmasi Pembayaran</a>
            <a href="member_dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 600px;">
        <div class="card border-0 shadow-sm p-4">
            <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-receipt me-2"></i>Form Upload Bukti Transfer</h4>
            <p class="text-muted small mb-4">Silakan transfer tagihan bulanan Anda ke Rekening Bank Kost, lalu unggah foto struk atau tangkapan layar (screenshot) bukti transfer di sini.</p>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Pemilik Rekening Pengirim</label>
                    <input type="text" name="nama_rekening" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Rekening Pengirim</label>
                    <input type="text" name="no_rekening" class="form-control" placeholder="Contoh: 1234567890" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Bank Pengirim</label>
                        <select name="nama_bank" class="form-select" required>
                            <option value="">-- Pilih Bank --</option>
                            <option value="BCA">BCA</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="BNI">BNI</option>
                            <option value="BRI">BRI</option>
                            <option value="E-Wallet (Dana/OVO/Gopay)">E-Wallet (Dana / OVO / Gopay)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select" required>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Unggah Foto Bukti Transfer (JPG/PNG)</label>
                    <input type="file" name="bukti_trf" class="form-control" accept=".jpg, .jpeg, .png" required>
                    <div class="form-text text-muted">Pastikan nominal dan tanggal transfer terlihat jelas.</div>
                </div>
                <div class="d-grid">
                    <button type="submit" name="upload_bayar" class="btn btn-primary fw-bold py-2">
                        <i class="bi bi-send-check me-2"></i>Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER COPYRIGHT -->
    <footer class="text-center py-3 my-4 border-top text-muted small">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <strong>Farrel Septian Wiguna</strong>. All Rights Reserved.</p>
        </div>
    </footer>
    
</body>
</html>