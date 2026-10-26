<?php
session_start();
include 'koneksi.php';

// Cek Akses Admin
if (!isset($_SESSION['username']) || $_SESSION['level'] != 'Admin') {
    header("location: login.php");
    exit();
}

// PROSES UPDATE STATUS KOMPLAIN
if (isset($_POST['update_status'])) {
    $id_komplain  = intval($_POST['id_komplain']);
    $status_baru  = mysqli_real_escape_string($conn, $_POST['status']);

    $q_update = "UPDATE kost_komplain SET status = '$status_baru' WHERE id_komplain = '$id_komplain'";
    if (mysqli_query($conn, $q_update)) {
        echo "<script>alert('Status komplain berhasil diperbarui!'); window.location='komplain_admin.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal memperbarui status: " . mysqli_error($conn) . "');</script>";
    }
}

// QUERY AMBIL DATA KOMPLAIN (FIXED: PERBAIKAN NAMA KOLOM TANGGAL & RELASI KAMAR)
$query_komplain = mysqli_query($conn, "SELECT 
    kk.*, 
    ku.nama AS nama_penghuni,
    COALESCE(kh.nama_hunian, 'Kamar Umum/Lainnya') AS nama_hunian
    FROM kost_komplain kk
    LEFT JOIN kost_user ku ON kk.id_user = ku.id_user
    LEFT JOIN kost_sewa ks ON (ku.id_user = ks.id_user AND ks.status = 'Aktif')
    LEFT JOIN kost_hunian kh ON (kk.id_hunian = kh.id_hunian OR ks.id_hunian = kh.id_hunian)
    GROUP BY kk.id_komplain
    ORDER BY kk.id_komplain DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Komplain - Admin Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- NAVBAR ADMIN -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Panel - Sistem Kost</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                <a class="nav-link" href="kamar.php">Data Kamar</a>
                <a class="nav-link" href="verifikasi_pembayaran.php">Verifikasi Bayar</a>
                <a class="nav-link active" href="komplain_admin.php">Komplain</a>
                <a class="nav-link text-danger" href="login.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Daftar Komplain Penghuni</h4>
                <p class="text-muted small mb-0">Kelola dan perbarui status laporan kendala dari para penghuni kost.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle small table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Penghuni</th>
                            <th>Kamar</th>
                            <th>Judul Kendala</th>
                            <th>Deskripsi / Detail</th>
                            <th>Tanggal Laporan</th>
                            <th>Status Saat Ini</th>
                            <th style="width: 180px;" class="text-center">Aksi Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($query_komplain && mysqli_num_rows($query_komplain) > 0) {
                            while ($k = mysqli_fetch_assoc($query_komplain)) {
                                $status = $k['status'] ?? 'Pending';
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($k['nama_penghuni'] ?? 'Unknown'); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($k['nama_hunian']); ?></span></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($k['judul_komplain'] ?? $k['komplain'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                            <td><?php echo !empty($k['tgl_komplain']) ? date('d M Y H:i', strtotime($k['tgl_komplain'])) : '-'; ?></td>
                            <td>
                                <?php if ($status == 'Selesai') { ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Selesai</span>
                                <?php } elseif ($status == 'Proses') { ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-gear-fill me-1"></i> Diproses</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i> Pending</span>
                                <?php } ?>
                            </td>
                            <td>
                                <form method="POST" action="" class="d-flex gap-1">
                                    <input type="hidden" name="id_komplain" value="<?php echo $k['id_komplain']; ?>">
                                    <select name="status" class="form-select form-select-sm" style="font-size: 11px;">
                                        <option value="Pending" <?php if($status=='Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Proses" <?php if($status=='Proses') echo 'selected'; ?>>Proses</option>
                                        <option value="Selesai" <?php if($status=='Selesai') echo 'selected'; ?>>Selesai</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm px-2" style="font-size: 11px;">
                                        <i class="bi bi-save"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada laporan komplain dari penghuni.</td></tr>";
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