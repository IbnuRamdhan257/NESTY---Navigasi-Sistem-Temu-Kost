<?php
session_start();
include 'koneksi.php';

// 1. CEK SESI LOGIN (LEBIH FLEKSIBEL AGAR TIDAK KEMBALI KE LOGIN)
if (!isset($_SESSION['username']) && !isset($_SESSION['id_user'])) {
    header("location: login.php");
    exit();
}

// 2. AUTO-CREATE TABEL KOMPLAIN JIKA BELUM ADA DI DATABASE
$table_check_query = "CREATE TABLE IF NOT EXISTS `kost_komplain` (
  `id_komplain` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_hunian` INT(11) DEFAULT NULL,
  `judul_komplain` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `tgl_komplain` DATETIME NOT NULL,
  `status` ENUM('Pending','Proses','Selesai') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id_komplain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $table_check_query);

// Ambil ID User dari Sesi
$id_user   = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0;
$nama_user = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Penghuni Kost';

// 3. AMBIL DATA KAMAR AKTIF MEMBER
$q_sewa = mysqli_query($conn, "SELECT kost_sewa.*, kost_hunian.nama_hunian 
    FROM kost_sewa 
    JOIN kost_hunian ON kost_sewa.id_hunian = kost_hunian.id_hunian 
    WHERE kost_sewa.id_user = '$id_user' AND kost_sewa.status = 'Aktif' LIMIT 1");
$sewa_aktif = mysqli_fetch_assoc($q_sewa);

// 4. PROSES SIMPAN KOMPLAIN
if (isset($_POST['kirim_komplain'])) {
    $judul_komplain  = mysqli_real_escape_string($conn, $_POST['judul_komplain']);
    $deskripsi       = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $tgl_komplain    = date('Y-m-d H:i:s');
    $id_hunian       = $sewa_aktif['id_hunian'] ?? 'NULL';

    $query_insert = "INSERT INTO kost_komplain (id_user, id_hunian, judul_komplain, deskripsi, tgl_komplain, status) 
                     VALUES ('$id_user', " . ($id_hunian !== 'NULL' ? "'$id_hunian'" : "NULL") . ", '$judul_komplain', '$deskripsi', '$tgl_komplain', 'Pending')";

    if (mysqli_query($conn, $query_insert)) {
        echo "<script>alert('Komplain berhasil dikirim! Admin akan segera memproses kendala Anda.'); window.location='komplain.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal mengirim komplain: " . mysqli_error($conn) . "');</script>";
    }
}

// 5. AMBIL RIWAYAT KOMPLAIN
$q_riwayat_komplain = mysqli_query($conn, "SELECT * FROM kost_komplain WHERE id_user = '$id_user' ORDER BY id_komplain DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ajukan Komplain - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- NAVBAR MEMBER -->
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="member_dashboard.php">
                <i class="bi bi-chat-left-dots-fill me-2"></i>Komplain & Kendala Kamar
            </a>
            <a href="member_dashboard.php" class="btn btn-outline-light btn-sm fw-bold">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </nav>

    <div class="container my-4 flex-grow-1">
        <div class="row g-4">
            <!-- FORM AJUKAN KOMPLAIN -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-pencil-square me-2"></i>Form Komplain</h5>
                    <p class="text-muted small mb-3">Sampaikan kendala fasilitas kamar atau lingkungan kost Anda di sini.</p>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kamar Anda</label>
                            <input type="text" class="form-control bg-light" value="<?php echo $sewa_aktif['nama_hunian'] ?? 'Belum ada kamar aktif'; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kategori / Judul Kendala</label>
                            <input type="text" name="judul_komplain" class="form-control" placeholder="Contoh: AC Tidak Dingin / Keran Bocor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Deskripsi Detail Kendala</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan detail masalah yang dialami..." required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="kirim_komplain" class="btn btn-primary fw-bold">
                                <i class="bi bi-send me-1"></i> Kirim Komplain
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL RIWAYAT KOMPLAIN MEMBER -->
            <div class="col-md-7">
                <div class="card border-0 shadow-sm p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Komplain Anda</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Kendala</th>
                                    <th>Deskripsi</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($q_riwayat_komplain && mysqli_num_rows($q_riwayat_komplain) > 0) {
                                    while ($k = mysqli_fetch_assoc($q_riwayat_komplain)) {
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($k['judul_komplain'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($k['tgl_komplain'])); ?></td>
                                    <td>
                                        <?php if ($k['status'] == 'Selesai') { ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Selesai</span>
                                        <?php } elseif ($k['status'] == 'Proses') { ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-gear-fill me-1"></i> Diproses</span>
                                        <?php } else { ?>
                                            <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i> Menunggu</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted py-4'>Belum ada komplain yang diajukan.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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