<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "salon_db");
mysqli_set_charset($conn, 'utf8mb4');

// ── AUTO-FIX: tambah kolom role jika belum ada ───────────────
// Masalah umum: tabel users dibuat dari proses-register.php lama
// tanpa kolom role, sehingga SELECT role selalu NULL → login gagal.
$cekRole = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($cekRole && mysqli_num_rows($cekRole) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
}
mysqli_query($conn, "UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
// ─────────────────────────────────────────────────────────────

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$redirect = trim($_POST['redirect'] ?? 'product.php');

if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php$/', $redirect)) {
    $redirect = 'product.php';
}
if ($redirect === 'dashboard.php') {
    $redirect = 'product.php';
}

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Email dan password wajib diisi.';
    header("Location: login.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, nama, email, password, role FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$valid = false;

if ($user) {
    $hash = $user['password'];
    if (password_verify($password, $hash)) {
        $valid = true;
    } elseif (md5($password) === $hash) {
        $valid   = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $newHash, $user['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
}

if ($valid) {
    $_SESSION['user']       = $user['nama'];
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = !empty($user['role']) ? $user['role'] : 'user';

    unset($_SESSION['login_error'], $_SESSION['last_email']);

    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: " . $redirect);
    }
    exit;

} else {
    $_SESSION['login_error'] = 'Email atau password salah. Periksa kembali dan coba lagi.';
    $_SESSION['last_email']  = $email;
    header("Location: login.php");
    exit;
}