<?php
session_start();
include 'koneksi.php';

// Cek akses Admin
if (!isset($_SESSION['username']) || $_SESSION['level'] != 'Admin') {
    header("location: login.php");
    exit();
}

// 1. QUERY TOTAL PENDAPATAN (MENGHITUNG SEMUA TRANSAKSI BERSTATUS PAID)
$query_pendapatan = mysqli_query($conn, "SELECT 
    (SELECT COALESCE(SUM(air + listrik), 0) FROM kost_tagihan WHERE status = 'Paid') 
    + 
    (SELECT COALESCE(COUNT(*) * 1000000, 0) FROM kost_pembayaran WHERE status = 'Paid') 
    AS total_pendapatan");

$data_pendapatan = mysqli_fetch_assoc($query_pendapatan);
$total_pendapatan = $data_pendapatan['total_pendapatan'] ?? 0;

// 2. DATA STATISTIK OVERVIEW
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost_user WHERE level='User'"))['total'];
$total_kamar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost_hunian"))['total'];
$kamar_full = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost_hunian WHERE status_hunian='Full'"))['total'];
$kamar_kosong = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost_hunian WHERE status_hunian='Kosong'"))['total'];
$pembayaran_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kost_pembayaran WHERE status='Verifikasi'"))['total'];

// 3. QUERY LIST KAMAR & PENGHUNI (UNTUK TABEL UTAMA)
$query_list_kamar = mysqli_query($conn, "SELECT 
    kost_hunian.id_hunian,
    kost_hunian.nama_hunian,
    kost_hunian.deskripsi_hunian,
    kost_hunian.status_hunian,
    kost_user.nama AS nama_penghuni,
    kost_user.nohp
    FROM kost_hunian 
    LEFT JOIN kost_sewa ON kost_hunian.id_hunian = kost_sewa.id_hunian 
    LEFT JOIN kost_user ON kost_sewa.id_user = kost_user.id_user 
    GROUP BY kost_hunian.id_hunian
    ORDER BY kost_hunian.id_hunian ASC");

// 4. QUERY TRANSAKSI PEMBAYARAN LUNAS (NAMA, KAMAR, & METODE FIX)
$query_orang_lunas = mysqli_query($conn, "SELECT 
    kp.*, 
    COALESCE(ku.nama, kp.nama_rekening) AS nama_penghuni,
    COALESCE(kh.nama_hunian, 'Kamar Kost') AS nama_hunian
    FROM kost_pembayaran kp
    LEFT JOIN kost_user ku ON (kp.nama_rekening = ku.nama OR kp.nama_rekening = ku.username)
    LEFT JOIN kost_sewa ks ON ku.id_user = ks.id_user
    LEFT JOIN kost_hunian kh ON ks.id_hunian = kh.id_hunian
    WHERE kp.status = 'Paid' 
    GROUP BY kp.id_pembayaran
    ORDER BY kp.id_pembayaran DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <!-- NAVBAR ADMIN -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-speedometer2 me-2"></i>Admin Panel - Sistem Kost</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                <a class="nav-link" href="kamar.php">Data Kamar</a>
                <a class="nav-link" href="verifikasi_pembayaran.php">Verifikasi Bayar</a>
                <a class="nav-link" href="komplain_admin.php">Komplain</a>
                <a class="nav-link text-danger" href="login.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Dashboard Overview</h4>
                <p class="text-muted small mb-0">Selamat datang, Administrator (<?php echo $_SESSION['username']; ?>)</p>
            </div>
            <span class="badge bg-secondary px-3 py-2"><?php echo date('d M Y'); ?></span>
        </div>

        <!-- KARTU STATISTIK UTAMA -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Pendapatan (Lunas)</h6>
                            <h3 class="fw-bold mb-0">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                        </div>
                        <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark mb-1">Perlu Verifikasi Bayar</h6>
                            <h3 class="fw-bold mb-0"><?php echo $pembayaran_pending; ?> Transaksi</h3>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Penghuni / Kamar</h6>
                            <h3 class="fw-bold mb-0"><?php echo $total_user; ?> Penghuni / <?php echo $total_kamar; ?> Kamar</h3>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL FITUR: DAFTAR KAMAR & PENGHUNI (VERSION RINGKAS + SEARCH BAR) -->
        <div class="card border-0 shadow-sm p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-door-open-fill me-2"></i>Daftar Kamar & Penghuni Kost</h5>
                    <p class="text-muted small mb-0">Total: <strong><?php echo $total_kamar; ?></strong> unit kamar</p>
                </div>
                <!-- Kotak Pencarian -->
                <div style="width: 100%; max-width: 280px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchKamar" class="form-control" placeholder="Cari kamar / penghuni..." onkeyup="filterKamar()">
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                <table class="table table-hover align-middle small table-striped mb-0" id="tabelKamar">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Kamar</th>
                            <th>Status</th>
                            <th>Penghuni Aktif</th>
                            <th class="text-end">No. WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($query_list_kamar) > 0) {
                            while ($kmr = mysqli_fetch_assoc($query_list_kamar)) {
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="fw-bold text-primary"><?php echo $kmr['nama_hunian']; ?></td>
                            <td>
                                <?php if ($kmr['status_hunian'] == 'Full') { ?>
                                    <span class="badge bg-danger">Terisi</span>
                                <?php } else { ?>
                                    <span class="badge bg-success">Kosong</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (!empty($kmr['nama_penghuni'])) { ?>
                                    <span class="fw-bold text-dark"><i class="bi bi-person me-1"></i><?php echo $kmr['nama_penghuni']; ?></span>
                                <?php } else { ?>
                                    <span class="text-muted"><em>- Kosong -</em></span>
                                <?php } ?>
                            </td>
                            <td class="text-end">
                                <?php if (!empty($kmr['nohp'])) { ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $kmr['nohp']); ?>" target="_blank" class="btn btn-success btn-sm py-0 px-2" style="font-size: 11px;">
                                        <i class="bi bi-whatsapp me-1"></i><?php echo $kmr['nohp']; ?>
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">-</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-3'>Belum ada data kamar.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ROW BAWAH: TRANSAKSI PEMBAYARAN LUNAS (KIRI) & STATISTIK KAMAR (KANAN) -->
        <div class="row g-4">
            <!-- Kolom Kiri: Tabel Transaksi Pembayaran Lunas -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-3 text-success"><i class="bi bi-check-circle-fill me-2"></i>Transaksi Pembayaran Lunas</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Invoice</th>
                                    <th>Nama Penghuni</th>
                                    <th>Kamar</th>
                                    <th>Metode</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no_lunas = 1;
                                if (mysqli_num_rows($query_orang_lunas) > 0) {
                                    while ($row = mysqli_fetch_assoc($query_orang_lunas)) {
                                ?>
                                <tr>
                                    <td><?php echo $no_lunas++; ?></td>
                                    <td><strong><?php echo $row['invoice']; ?></strong></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_penghuni']); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['nama_hunian']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $metode = $row['metode_pembayaran'] ?? $row['metode'] ?? $row['nama_bank'] ?? 'Transfer';
                                            echo !empty($metode) ? htmlspecialchars($metode) : 'Transfer';
                                        ?>
                                    </td>
                                    <td><?php echo $row['tgl_bayar']; ?></td>
                                    <td><span class="badge bg-success">LUNAS</span></td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-muted py-3'>Belum ada riwayat pembayaran lunas.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Grafik Statistik Kamar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Statistik Kamar</h5>
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <canvas id="kamarChart"></canvas>
                    </div>
                    <div class="mt-3 text-center small text-muted pt-2 border-top">
                        <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> Full: <strong><?php echo $kamar_full; ?></strong></span>
                        <span><i class="bi bi-circle-fill text-success"></i> Kosong: <strong><?php echo $kamar_kosong; ?></strong></span>
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

    <!-- SCRIPT JAVASCRIPT LIVE SEARCH & DOUGHNUT CHART -->
    <script>
        function filterKamar() {
            let input = document.getElementById("searchKamar");
            let filter = input.value.toLowerCase();
            let table = document.getElementById("tabelKamar");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let tdKamar = tr[i].getElementsByTagName("td")[1];
                let tdStatus = tr[i].getElementsByTagName("td")[2];
                let tdPenghuni = tr[i].getElementsByTagName("td")[3];
                let tdHp = tr[i].getElementsByTagName("td")[4];

                if (tdKamar || tdPenghuni || tdStatus || tdHp) {
                    let txtKamar = tdKamar ? tdKamar.textContent || tdKamar.innerText : "";
                    let txtStatus = tdStatus ? tdStatus.textContent || tdStatus.innerText : "";
                    let txtPenghuni = tdPenghuni ? tdPenghuni.textContent || tdPenghuni.innerText : "";
                    let txtHp = tdHp ? tdHp.textContent || tdHp.innerText : "";

                    if (txtKamar.toLowerCase().indexOf(filter) > -1 || 
                        txtStatus.toLowerCase().indexOf(filter) > -1 || 
                        txtPenghuni.toLowerCase().indexOf(filter) > -1 ||
                        txtHp.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }

        const ctx = document.getElementById('kamarChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Terisi (Full)', 'Kosong'],
                datasets: [{
                    data: [<?php echo $kamar_full; ?>, <?php echo $kamar_kosong; ?>],
                    backgroundColor: ['#dc3545', '#198754']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>