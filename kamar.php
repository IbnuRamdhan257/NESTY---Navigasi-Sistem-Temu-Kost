<?php
session_start();
include 'koneksi.php';

// Cek akses Admin
if (!isset($_SESSION['username']) || $_SESSION['level'] != 'Admin') {
    header("location: login.php");
    exit();
}

// 1. Proses Tambah Kamar Baru
if (isset($_POST['tambah_kamar'])) {
    $nama_hunian = mysqli_real_escape_string($conn, $_POST['nama_hunian']);
    $deskripsi   = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $status      = mysqli_real_escape_string($conn, $_POST['status_hunian']);
    $harga_kost  = mysqli_real_escape_string($conn, $_POST['harga_kost']);
    $listrik     = mysqli_real_escape_string($conn, $_POST['listrik']);
    $air         = mysqli_real_escape_string($conn, $_POST['air']);

    $query_kamar = "INSERT INTO kost_hunian (nama_hunian, deskripsi_hunian, status_hunian) VALUES ('$nama_hunian', '$deskripsi', '$status')";
    if (mysqli_query($conn, $query_kamar)) {
        $id_hunian = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO kost_biaya (id_hunian, listrik, air, sampah, kost) VALUES ('$id_hunian', '$listrik', '$air', 15000, '$harga_kost')");
        echo "<script>alert('Kamar berhasil ditambahkan!'); window.location='kamar.php';</script>";
    }
}

// 2. Proses Hapus Kamar
if (isset($_GET['hapus'])) {
    $id_hapus = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM kost_biaya WHERE id_hunian='$id_hapus'");
    mysqli_query($conn, "DELETE FROM kost_hunian WHERE id_hunian='$id_hapus'");
    echo "<script>alert('Kamar berhasil dihapus!'); window.location='kamar.php';</script>";
}

// 3. Query Ambil Seluruh Data Kamar + Biaya + Nama Penghuni (JOIN kost_sewa & kost_user)
$query_kamar = mysqli_query($conn, "SELECT 
    kost_hunian.*, 
    kost_biaya.kost, 
    kost_biaya.listrik, 
    kost_biaya.air,
    kost_user.nama AS nama_penghuni,
    kost_user.nohp
    FROM kost_hunian 
    LEFT JOIN kost_biaya ON kost_hunian.id_hunian = kost_biaya.id_hunian
    LEFT JOIN kost_sewa ON kost_hunian.id_hunian = kost_sewa.id_hunian AND kost_sewa.status = 'Aktif'
    LEFT JOIN kost_user ON kost_sewa.id_user = kost_user.id_user
    GROUP BY kost_hunian.id_hunian
    ORDER BY kost_hunian.id_hunian ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Data Kamar - Admin Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-door-open-fill me-2"></i>Admin Panel - Sistem Kost</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                <a class="nav-link active" href="kamar.php">Data Kamar</a>
                <a class="nav-link" href="verifikasi_pembayaran.php">Verifikasi Bayar</a>
                <a class="nav-link" href="komplain_admin.php">Komplain</a>
                <a class="nav-link text-danger" href="login.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-house-gear me-2 text-primary"></i>Kelola Data Kamar Kost</h4>
                <p class="text-muted small mb-0">Daftar seluruh unit kamar, harga sewa, dan informasi penghuni aktif.</p>
            </div>
            <!-- Tombol Modal Tambah Kamar -->
            <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahKamar">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kamar Baru
            </button>
        </div>

        <!-- TABEL DATA KAMAR & PENGHUNI -->
        <div class="card border-0 shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle small table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Nama Unit Kamar</th>
                            <th>Fasilitas</th>
                            <th>Harga Sewa / Bln</th>
                            <th>Status Unit</th>
                            <th>Penghuni (Diisi Oleh)</th>
                            <th>Kontak HP / WA</th>
                            <th style="width: 80px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($query_kamar) > 0) {
                            while ($row = mysqli_fetch_assoc($query_kamar)) {
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="fw-bold text-primary"><?php echo $row['nama_hunian']; ?></td>
                            <td><?php echo $row['deskripsi_hunian']; ?></td>
                            <td class="fw-bold text-success">
                                Rp <?php echo number_format($row['kost'] ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td>
                                <?php if ($row['status_hunian'] == 'Full') { ?>
                                    <span class="badge bg-danger"><i class="bi bi-person-fill"></i> Terisi (Full)</span>
                                <?php } else { ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Kosong</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!empty($row['nama_penghuni'])) { ?>
                                    <strong class="text-dark"><i class="bi bi-person me-1"></i><?php echo $row['nama_penghuni']; ?></strong>
                                <?php } else { ?>
                                    <span class="text-muted italic"><em>Belum ada penghuni</em></span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!empty($row['nohp'])) { ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['nohp']); ?>" target="_blank" class="text-decoration-none text-success fw-bold">
                                        <i class="bi bi-whatsapp me-1"></i><?php echo $row['nohp']; ?>
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">-</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <a href="kamar.php?hapus=<?php echo $row['id_hunian']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kamar ini?');" title="Hapus Kamar">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada data kamar.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH KAMAR BARU -->
    <div class="modal fade" id="modalTambahKamar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Unit Kamar Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Kamar / Unit</label>
                            <input type="text" name="nama_hunian" class="form-control" placeholder="Contoh: Kamar E-01 (VIP)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Fasilitas & Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" placeholder="Contoh: AC, Kamar Mandi Dalam, Wi-Fi" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Harga Sewa Kost/Bln</label>
                                <input type="number" name="harga_kost" class="form-control" placeholder="1500000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Status Awal</label>
                                <select name="status_hunian" class="form-select">
                                    <option value="Kosong">Kosong</option>
                                    <option value="Full">Terisi (Full)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Biaya Listrik / Bln</label>
                                <input type="number" name="listrik" class="form-control" value="100000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Biaya Air / Bln</label>
                                <input type="number" name="air" class="form-control" value="50000" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_kamar" class="btn btn-primary btn-sm fw-bold">Simpan Kamar</button>
                    </div>
                </form>
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