<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['level'] != 'Admin') {
    header("location: login.php");
    exit();
}

// Proses Aksi Verifikasi (Terima / Tolak)
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_pembayaran = mysqli_real_escape_string($conn, $_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi == 'terima') {
        // 1. Update status di kost_pembayaran menjadi Paid
        $update_pembayaran = mysqli_query($conn, "UPDATE kost_pembayaran SET status='Paid' WHERE id_pembayaran='$id_pembayaran'");

        // 2. Ambil invoice dari pembayaran tersebut untuk mengupdate kost_tagihan
        $get_invoice = mysqli_query($conn, "SELECT invoice FROM kost_pembayaran WHERE id_pembayaran='$id_pembayaran'");
        $data_inv = mysqli_fetch_assoc($get_invoice);
        $invoice = $data_inv['invoice'];

        // 3. Update status di kost_tagihan menjadi Paid
        mysqli_query($conn, "UPDATE kost_tagihan SET status='Paid' WHERE status='Verifikasi' OR status='Not Paid' LIMIT 1");

        if ($update_pembayaran) {
            echo "<script>alert('Pembayaran BERHASIL diterima & total pendapatan diperbarui!'); window.location='verifikasi_pembayaran.php';</script>";
        }
    } else {
        // Jika ditolak
        mysqli_query($conn, "UPDATE kost_pembayaran SET status='Not Paid' WHERE id_pembayaran='$id_pembayaran'");
        echo "<script>alert('Pembayaran ditolak.'); window.location='verifikasi_pembayaran.php';</script>";
    }
}

// Ambil data yang perlu diverifikasi saja
$data_pembayaran = mysqli_query($conn, "SELECT * FROM kost_pembayaran WHERE status = 'Verifikasi' ORDER BY id_pembayaran DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Pembayaran - Admin Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock-fill me-2"></i>Admin Panel - Sistem Kost</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                <a class="nav-link" href="kamar.php">Data Kamar</a>
                <a class="nav-link active" href="verifikasi_pembayaran.php">Verifikasi Bayar</a>
                <a class="nav-link" href="komplain_admin.php">Komplain</a>
                <a class="nav-link text-danger" href="login.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card shadow-sm p-4 border-0">
            <h5 class="mb-3 fw-bold"><i class="bi bi-cash-coin me-2 text-primary"></i>Daftar Konfirmasi Pembayaran (Perlu Verifikasi)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Invoice</th>
                            <th>Metode</th>
                            <th>Rekening Pengirim</th>
                            <th>Bukti Transfer</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($data_pembayaran) > 0) {
                            while ($row = mysqli_fetch_assoc($data_pembayaran)) { 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo $row['invoice']; ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo $row['metode_pembayaran']; ?></span></td>
                            <td>
                                <?php echo htmlspecialchars($row['nama_bank']); ?> - <?php echo htmlspecialchars($row['no_rekening']); ?><br>
                                <small class="text-muted">a.n. <?php echo htmlspecialchars($row['nama_rekening']); ?></small>
                            </td>
                            <td>
                                <?php 
                                $file_bukti = $row['bukti_trf'];
                                if (!empty($file_bukti) && file_exists("uploads/" . $file_bukti)) { 
                                ?>
                                    <a href="uploads/<?php echo $file_bukti; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-image"></i> Lihat Bukti
                                    </a>
                                <?php } elseif (!empty($file_bukti)) { ?>
                                    <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> File tidak ditemukan</span>
                                <?php } else { ?>
                                    <span class="text-muted small">Tidak ada lampiran</span>
                                <?php } ?>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark">Verifikasi</span>
                            </td>
                            <td>
                                <a href="verifikasi_pembayaran.php?aksi=terima&id=<?php echo $row['id_pembayaran']; ?>" class="btn btn-success btn-sm mb-1" onclick="return confirm('Terima dan setujui pembayaran ini?');">
                                    <i class="bi bi-check-lg"></i> Terima
                                </a>
                                <a href="verifikasi_pembayaran.php?aksi=tolak&id=<?php echo $row['id_pembayaran']; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Tolak pembayaran ini?');">
                                    <i class="bi bi-x-lg"></i> Tolak
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted py-4'>Tidak ada pembayaran baru yang perlu diverifikasi.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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