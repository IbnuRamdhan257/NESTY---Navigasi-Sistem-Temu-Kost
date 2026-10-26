<?php
session_start();
include 'koneksi.php';

if (isset($_POST['kirim_komplain'])) {
    $id_user = $_POST['id_user'];
    $isi     = mysqli_real_escape_string($conn, $_POST['isi']);

    // Cari id_sewa member terkait jika ada
    $q_sewa = mysqli_query($conn, "SELECT id_sewa FROM kost_sewa WHERE id_user = '$id_user' AND status = 'Aktif' LIMIT 1");
    $d_sewa = mysqli_fetch_assoc($q_sewa);
    $id_sewa = $d_sewa['id_sewa'] ?? 0;

    $query = "INSERT INTO kost_komplain (id_user, id_sewa, isi, status, tanggal) VALUES ('$id_user', '$id_sewa', '$isi', 'N', NOW())";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Komplain Anda telah terkirim dan akan ditindaklanjuti oleh pengelola.'); window.location='member_dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim komplain: " . mysqli_error($conn) . "'); window.location='member_dashboard.php';</script>";
    }
} else {
    header("location: member_dashboard.php");
}
?>