<?php
session_start();
include 'koneksi.php';

// Cek akses Member (User)
if (!isset($_SESSION['username']) || $_SESSION['level'] != 'User') {
    header("location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$nama_user = $_SESSION['nama'] ?? $_SESSION['username'];

// 1. Ambil Informasi Kamar & Sewa Aktif Member
$query_sewa = mysqli_query($conn, "SELECT 
    kost_sewa.*, 
    kost_hunian.nama_hunian, 
    kost_hunian.deskripsi_hunian,
    kost_biaya.kost AS harga_kost,
    kost_biaya.listrik,
    kost_biaya.air
    FROM kost_sewa 
    JOIN kost_hunian ON kost_sewa.id_hunian = kost_hunian.id_hunian 
    LEFT JOIN kost_biaya ON kost_hunian.id_hunian = kost_biaya.id_hunian
    WHERE kost_sewa.id_user = '$id_user' AND kost_sewa.status = 'Aktif' 
    LIMIT 1");

$sewa_aktif = mysqli_fetch_assoc($query_sewa);
$id_sewa = $sewa_aktif['id_sewa'] ?? 0;

// 2. Ambil Tagihan Bulan Ini
$query_tagihan = mysqli_query($conn, "SELECT * FROM kost_tagihan WHERE id_sewa = '$id_sewa' ORDER BY id_tagihan DESC LIMIT 1");
$tagihan_aktif = mysqli_fetch_assoc($query_tagihan);

// 3. Ambil Riwayat Pembayaran Member
$query_riwayat = mysqli_query($conn, "SELECT * FROM kost_pembayaran WHERE status='Paid' ORDER BY id_pembayaran DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Penghuni - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- NAVBAR MEMBER -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="member_dashboard.php">
                <i class="bi bi-house-door-fill me-2"></i>Portal Penghuni Kost
            </a>
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-2">
                <a class="nav-link active text-white" href="member_dashboard.php">Dashboard</a>
                
                <!-- Tombol Navigasi Pindah / Ganti Kamar -->
                <a class="btn btn-light btn-sm fw-bold text-primary rounded-pill px-3" href="pindah_kamar.php">
                    <i class="bi bi-arrow-left-right me-1"></i> Ganti Kamar
                </a>

                <a class="nav-link text-warning fw-bold ms-2" href="login.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- HEADER UCAPAN -->
        <div class="card border-0 shadow-sm p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>! 👋</h4>
                    <p class="text-muted small mb-0">Pantau informasi hunian, pembayaran sewa, dan pengajuan komplain Anda di sini.</p>
                </div>
                <span class="badge bg-primary fs-6 px-3 py-2 align-self-start align-self-md-center">
                    <i class="bi bi-calendar-event me-1"></i> <?php echo date('d M Y'); ?>
                </span>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- KARTU INFORMASI KAMAR AKTIF & TOMBOL GANTI KAMAR -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-door-open-fill me-2"></i>Kamar Ditempati</h5>
                        <span class="badge bg-success px-2 py-1">Status: Aktif</span>
                    </div>

                    <?php if ($sewa_aktif) { ?>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <h3 class="fw-bold text-dark mb-1"><?php echo $sewa_aktif['nama_hunian']; ?></h3>
                            <p class="text-muted small mb-2"><?php echo $sewa_aktif['deskripsi_hunian']; ?></p>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Sewa Bulanan:</span>
                                <strong class="text-dark">Rp <?php echo number_format($sewa_aktif['harga_kost'] ?? 0, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Jatuh Tempo:</span>
                                <strong class="text-danger"><?php echo date('d M Y', strtotime($sewa_aktif['tgl_jatuhtempo'])); ?></strong>
                            </div>
                        </div>

                        <!-- TOMBOL UTAMA UNTUK PINDAH / GANTI KAMAR -->
                        <div class="d-grid">
                            <a href="pindah_kamar.php" class="btn btn-outline-primary fw-bold">
                                <i class="bi bi-arrow-left-right me-2"></i>Pilih / Ganti Kamar Lain
                            </a>
                        </div>
                    <?php } else { ?>
                        <div class="text-center py-4">
                            <i class="bi bi-exclamation-circle text-warning fs-1 mb-2"></i>
                            <p class="text-muted mb-3">Anda belum terikat pada unit kamar manapun.</p>
                            <a href="pindah_kamar.php" class="btn btn-primary btn-sm fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> Pilih Kamar Sekarang
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- KARTU TAGIHAN & UNTUK BAYAR -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                    <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-receipt me-2 text-warning"></i>Status Tagihan Bulan Ini</h5>
                    
                    <?php 
                    $total_tagihan = ($sewa_aktif['harga_kost'] ?? 0) + ($sewa_aktif['listrik'] ?? 0) + ($sewa_aktif['air'] ?? 0);
                    ?>

                    <div class="p-3 border rounded mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Tagihan:</span>
                            <h4 class="fw-bold text-success mb-0">Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></h4>
                        </div>
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Termasuk biaya sewa kamar, air, & listrik.</p>
                    </div>

                    <div class="d-grid gap-2 mt-auto">
                        <a href="bayar.php" class="btn btn-success fw-bold">
                            <i class="bi bi-upload me-2"></i>Upload Bukti Pembayaran
                        </a>
                        <a href="komplain.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-chat-left-dots me-1"></i> Ajukan Komplain / Kendala Kamar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL RIWAYAT PEMBAYARAN PENGHUNI -->
        <div class="card border-0 shadow-sm p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Pembayaran Anda</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>No. Invoice</th>
                            <th>Metode</th>
                            <th>Tanggal Upload</th>
                            <th>Status Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($query_riwayat) > 0) {
                            while ($r = mysqli_fetch_assoc($query_riwayat)) {
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="fw-bold text-primary"><?php echo $r['invoice']; ?></td>
                            <td><?php echo $r['metode_pembayaran']; ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($r['tgl_bayar'])); ?></td>
                            <td>
                                <?php if ($r['status'] == 'Paid') { ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> LUNAS</span>
                                <?php } else { ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-3'>Belum ada riwayat transaksi pembayaran.</td></tr>";
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>