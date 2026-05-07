<?php
$host = "localhost";
$user = "root";      // default XAMPP
$pass = "";          // default XAMPP kosong
$db   = "salon_db"; // nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

// cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>