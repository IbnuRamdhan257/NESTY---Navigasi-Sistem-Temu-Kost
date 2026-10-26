<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM kost_user WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['username'] = $data['username'];
        $_SESSION['level']    = $data['level'];
        $_SESSION['id_user']  = $data['id_user'];

        if ($data['level'] == 'Admin') {
            header("location: admin_dashboard.php");
        } else {
            header("location: member_dashboard.php");
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kost</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow border-0 p-4" style="width: 100%; max-width: 380px;">
        <div class="text-center mb-3">
            <i class="bi bi-house-door-fill text-primary display-4"></i>
            <h4 class="fw-bold mt-2">Sistem Kost</h4>
            <p class="text-muted small">Silakan Login Terlebih Dahulu</p>
        </div>
        
        <?php if (isset($error)) { echo "<div class='alert alert-danger p-2 small text-center'>$error</div>"; } ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold py-2 mt-2">Masuk</button>
        </form>

        <!-- LINK UNTUK USER BARU (REGISTRASI) -->
        <div class="text-center mt-4 pt-3 border-top">
            <p class="small text-muted mb-0">Belum punya akun? <a href="register.php" class="text-primary text-decoration-none fw-bold">Daftar Akun Baru</a></p>
        </div>
    </div>
    
</body>
</html>