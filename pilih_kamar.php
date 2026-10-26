<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username']) || $_SESSION['level'] != 'User') {
    header("location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Pengecekan apakah member sudah memiliki sewa aktif
$cek_sewa = mysqli_query($conn, "SELECT * FROM kost_sewa WHERE id_user = '$id_user'");
$sudah_sewa = mysqli_num_rows($cek_sewa) > 0;

// Eksekusi pemilihan kamar
if (isset($_GET['id_hunian']) && !$sudah_sewa) {
    $id_hunian = mysqli_real_escape_string($conn, $_GET['id_hunian']);
    $tgl_jatuhtempo = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    // 1. Simpan ke tabel kost_sewa
    $query_sewa = "INSERT INTO kost_sewa (id_user, id_hunian, tgl_jatuhtempo, status) VALUES ('$id_user', '$id_hunian', '$tgl_jatuhtempo', 'Aktif')";
    if (mysqli_query($conn, $query_sewa)) {
        $id_sewa = mysqli_insert_id($conn);
        
        // 2. Ambil rincian biaya dari kost_biaya
        $q_biaya = mysqli_query($conn, "SELECT * FROM kost_biaya WHERE id_hunian = '$id_hunian'");
        $b = mysqli_fetch_assoc($q_biaya);
        
        $listrik = $b['listrik'] ?? 100000;
        $air = $b['air'] ?? 50000;

        // 3. Buat Tagihan Pertama di kost_tagihan
        mysqli_query($conn, "INSERT INTO kost_tagihan (id_sewa, tgl_tagihan, air, listrik, tgl_jatuhtempo, status) 
                            VALUES ('$id_sewa', NOW(), '$air', '$listrik', '$tgl_jatuhtempo', 'Not Paid')");
        
        // 4. Ubah status kamar menjadi 'Full'
        mysqli_query($conn, "UPDATE kost_hunian SET status_hunian = 'Full' WHERE id_hunian = '$id_hunian'");

        echo "<script>alert('Berhasil memilih kamar! Tagihan pertama Anda telah dibuat.'); window.location='member_dashboard.php';</script>";
    }
}

// Ambil daftar kamar kosong
$query_kamar = mysqli_query($conn, "SELECT kost_hunian.*, kost_biaya.kost, kost_biaya.listrik, kost_biaya.air 
    FROM kost_hunian 
    LEFT JOIN kost_biaya ON kost_hunian.id_hunian = kost_biaya.id_hunian 
    WHERE kost_hunian.status_hunian = 'Kosong'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Kamar - Member Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="member_dashboard.php"><i class="bi bi-house-door-fill me-2"></i>Pilih Kamar Kost</a>
            <a href="member_dashboard.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Pilihan Kamar Kost Tersedia</h3>
            <p class="text-muted">Pilih unit kamar yang sesuai dengan kebutuhan Anda</p>
        </div>

        <?php if ($sudah_sewa) { ?>
            <div class="alert alert-warning text-center shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Anda telah mendaftar pada unit kamar aktif.
                <br><a href="member_dashboard.php" class="btn btn-sm btn-primary mt-2">Kembali ke Dashboard</a>
            </div>
        <?php } else { ?>
            <div class="row g-4">
                <?php 
                if (mysqli_num_rows($query_kamar) > 0) {
                    while ($k = mysqli_fetch_assoc($query_kamar)) { 
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
                                <a href="pilih_kamar.php?id_hunian=<?php echo $k['id_hunian']; ?>" class="btn btn-success w-100 fw-bold" onclick="return confirm('Pilih dan tempati unit kamar ini?');">
                                    <i class="bi bi-check-circle me-1"></i> Pilih Kamar Ini
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo "<div class='col-12 text-center py-4'><div class='alert alert-info'>Mohon maaf, semua unit kamar saat ini sudah terisi penuh.</div></div>";
                }
                ?>
            </div>
        <?php } ?>
    </div>

    <!-- FOOTER COPYRIGHT -->
    <footer class="text-center py-3 my-4 border-top text-muted small">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <strong>Farrel Septian Wiguna</strong>. All Rights Reserved.</p>
        </div>
    </footer>
    
</body>
</html>