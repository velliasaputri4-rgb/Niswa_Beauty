<?php
// db.php — Koneksi database tunggal untuk seluruh aplikasi NISWÀ BEAUTY
// Semua file PHP harus require_once file ini, bukan buat koneksi sendiri.

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'salon_db');
}

// Hindari koneksi ganda jika sudah di-include sebelumnya
if (!isset($conn) || !$conn) {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        die('<div style="
            font-family: Poppins, sans-serif;
            background: #fff0f5;
            color: #c0392b;
            padding: 20px 30px;
            margin: 20px;
            border-left: 5px solid #e74c3c;
            border-radius: 8px;
            font-size: 15px;
        ">
            <strong>❌ Koneksi Database Gagal!</strong><br>
            Error: ' . mysqli_connect_error() . '<br><br>
            <small>Pastikan XAMPP MySQL aktif dan database <b>salon_db</b> sudah dibuat.</small>
        </div>');
    }

    mysqli_set_charset($conn, 'utf8mb4');
}