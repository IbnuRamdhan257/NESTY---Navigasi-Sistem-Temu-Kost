<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

if (isset($_POST['bayar'])) {
    $invoice = "INV-" . rand(10000, 99999);
    $metode = $_POST['metode_pembayaran'];
    $nama_rekening = mysqli_real_escape_string($conn, $_POST['nama_rekening']);
    $nama_bank = mysqli_real_escape_string($conn, $_POST['nama_bank']);
    $no_rekening = mysqli_real_escape_string($conn, $_POST['no_rekening']);
    
    // Upload file bukti transfer
    $nama_file = $_FILES['bukti_trf']['name'];
    $tmp_file = $_FILES['bukti_trf']['tmp_name'];
    
    // Pastikan nama file unik agar tidak menimpa file lain
    $file_baru = time() . "_" . $nama_file;
    $path = "uploads/" . $file_baru;

    if (move_uploaded_file($tmp_file, $path)) {
        $query = "INSERT INTO kost_pembayaran (invoice, metode_pembayaran, nama_rekening, nama_bank, no_rekening, bukti_trf, status, tgl_bayar) 
                  VALUES ('$invoice', '$metode', '$nama_rekening', '$nama_bank', '$no_rekening', '$file_baru', 'Verifikasi', NOW())";
                  
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Bukti pembayaran berhasil di-upload dan menunggu verifikasi admin!'); window.location='member_dashboard.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan ke database: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Gagal mengunggah file gambar ke folder uploads!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Pembayaran - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 500px;">
        <div class="card shadow border-0 p-4">
            <div class="text-center mb-3">
                <i class="bi bi-wallet2 text-success display-4"></i>
                <h4 class="fw-bold mt-2">Form Konfirmasi Pembayaran</h4>
                <p class="text-muted small">Silakan transfer sesuai nominal ke rekening BCA: <strong>1234567890 (a.n Pengelola Kost)</strong></p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Metode Pembayaran</label>
                    <select name="metode_pembayaran" class="form-select">
                        <option value="Transfer">Transfer Bank</option>
                        <option value="Cash">Cash / Tunai</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Pemilik Rekening</label>
                    <input type="text" name="nama_rekening" class="form-control" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Bank Pengirim</label>
                    <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: BCA / Mandiri / BRI" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Rekening Pengirim</label>
                    <input type="text" name="no_rekening" class="form-control" placeholder="1234xxxx" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Upload Foto Bukti Transfer</label>
                    <input type="file" name="bukti_trf" class="form-control" accept="image/*" required>
                </div>
                <button type="submit" name="bayar" class="btn btn-success w-100 fw-bold py-2 mt-2">
                    <i class="bi bi-upload me-1"></i> Kirim Bukti Transfer
                </button>
                <a href="member_dashboard.php" class="btn btn-outline-secondary w-100 btn-sm mt-2">Batal</a>
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