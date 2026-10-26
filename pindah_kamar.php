<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['level'] != 'User') {
    header("location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Ambil kamar aktif member saat ini
$q_sewa = mysqli_query($conn, "SELECT kost_sewa.*, kost_hunian.nama_hunian 
    FROM kost_sewa 
    JOIN kost_hunian ON kost_sewa.id_hunian = kost_hunian.id_hunian 
    WHERE kost_sewa.id_user = '$id_user' AND kost_sewa.status = 'Aktif' LIMIT 1");
$sewa_aktif = mysqli_fetch_assoc($q_sewa);

// Proses Pindah Kamar
if (isset($_GET['kamar_baru'])) {
    $id_kamar_baru = mysqli_real_escape_string($conn, $_GET['kamar_baru']);
    $id_kamar_lama = $sewa_aktif['id_hunian'];
    $id_sewa       = $sewa_aktif['id_sewa'];

    // 1. Kosongkan kamar lama
    mysqli_query($conn, "UPDATE kost_hunian SET status_hunian = 'Kosong' WHERE id_hunian = '$id_kamar_lama'");

    // 2. Isi kamar baru
    mysqli_query($conn, "UPDATE kost_hunian SET status_hunian = 'Full' WHERE id_hunian = '$id_kamar_baru'");

    // 3. Update relasi sewa ke kamar baru
    mysqli_query($conn, "UPDATE kost_sewa SET id_hunian = '$id_kamar_baru' WHERE id_sewa = '$id_sewa'");

    echo "<script>alert('Berhasil pindah kamar!'); window.location='member_dashboard.php';</script>";
}

// Ambil daftar kamar kosong
$query_kamar_kosong = mysqli_query($conn, "SELECT kost_hunian.*, kost_biaya.kost 
    FROM kost_hunian 
    LEFT JOIN kost_biaya ON kost_hunian.id_hunian = kost_biaya.id_hunian 
    WHERE kost_hunian.status_hunian = 'Kosong'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pindah Kamar - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="member_dashboard.php"><i class="bi bi-arrow-left-right me-2"></i>Pindah Kamar Kost</a>
            <a href="member_dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card border-0 shadow-sm p-4 mb-4 text-center">
            <h5 class="fw-bold mb-1">Kamar Anda Saat Ini:</h5>
            <span class="badge bg-primary fs-5 d-inline-block px-3 py-2 mt-2">
                <i class="bi bi-door-open-fill me-1"></i> <?php echo $sewa_aktif['nama_hunian'] ?? 'Belum ada kamar'; ?>
            </span>
        </div>

        <h5 class="fw-bold mb-3"><i class="bi bi-building-check me-2 text-primary"></i>Pilih Kamar Baru yang Tersedia:</h5>
        
        <div class="row g-4">
            <?php 
            if (mysqli_num_rows($query_kamar_kosong) > 0) {
                while ($k = mysqli_fetch_assoc($query_kamar_kosong)) { 
            ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold text-primary mb-2"><i class="bi bi-door-open me-1"></i> <?php echo $k['nama_hunian']; ?></h5>
                        <p class="text-muted small mb-3"><?php echo $k['deskripsi_hunian']; ?></p>
                        <div class="bg-light p-2 rounded mb-3 small">
                            <strong>Biaya Sewa / Bulan:</strong><br>
                            <span class="fs-5 fw-bold text-success">Rp <?php echo number_format($k['kost'] ?? 1000000, 0, ',', '.'); ?></span>
                        </div>
                        <div class="mt-auto">
                            <a href="pindah_kamar.php?kamar_baru=<?php echo $k['id_hunian']; ?>" class="btn btn-warning w-100 fw-bold text-dark" onclick="return confirm('Pindah ke kamar ini? Kamar lama Anda akan otomatis dilepas.');">
                                <i class="bi bi-arrow-left-right me-1"></i> Pindah ke Kamar Ini
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else {
                echo "<div class='col-12 text-center py-4'><div class='alert alert-info'>Tidak ada pilihan kamar kosong lain saat ini.</div></div>";
            }
            ?>
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