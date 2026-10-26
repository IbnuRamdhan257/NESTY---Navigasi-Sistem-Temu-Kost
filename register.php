<?php
session_start();
include 'koneksi.php';

if (isset($_POST['register'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $nohp     = mysqli_real_escape_string($conn, $_POST['nohp']);
    $alamat   = mysqli_real_escape_string($conn, $_POST['alamat']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Pengecekan username atau email unik
    $cek_user = mysqli_query($conn, "SELECT * FROM kost_user WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($cek_user) > 0) {
        $error = "Username atau Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO kost_user (username, password, email, nama, nohp, alamat, level, status) 
                  VALUES ('$username', '$password', '$email', '$nama', '$nohp', '$alamat', 'User', 'Aktif')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Pendaftaran akun berhasil! Silakan login.'); window.location='login.php';</script>";
        } else {
            $error = "Gagal mendaftar: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Member - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-center py-5" style="min-height: 100vh;">
    <div class="card shadow border-0 p-4" style="width: 100%; max-width: 480px;">
        <div class="text-center mb-3">
            <i class="bi bi-person-plus-fill text-primary display-4"></i>
            <h4 class="fw-bold mt-2">Daftar Akun Member</h4>
            <p class="text-muted small">Buat akun untuk memilih kamar dan mengelola sewa kost</p>
        </div>

        <?php if (isset($error)) { echo "<div class='alert alert-danger p-2 small'>$error</div>"; } ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">No. HP / WhatsApp</label>
                    <input type="text" name="nohp" class="form-control" placeholder="08123456789" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@domain.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Alamat Asal</label>
                <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat asal daerah Anda" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 fw-bold py-2">Daftar Sekarang</button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="small text-muted mb-0">Sudah punya akun? <a href="login.php" class="text-primary text-decoration-none fw-bold">Login di sini</a></p>
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