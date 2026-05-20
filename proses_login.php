<?php
// proses_login.php — NISWÀ BEAUTY
// Menangani autentikasi login untuk admin dan user biasa

session_start();
require_once __DIR__ . '/db.php';

// Pastikan koneksi berhasil
if (!$conn) {
    $_SESSION['login_error'] = 'Koneksi database gagal. Pastikan MySQL aktif dan database salon_db sudah diimport.';
    header("Location: login.php");
    exit;
}

// ── AUTO-FIX: tambah kolom role jika tabel users belum punya ──
$cekRole = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($cekRole && mysqli_num_rows($cekRole) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
}
// Pastikan tidak ada role NULL
mysqli_query($conn, "UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");

// ── Ambil input ──────────────────────────────────────────────
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$redirect = trim($_POST['redirect'] ?? 'index.php');

// ── Validasi input kosong ────────────────────────────────────
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Email dan password wajib diisi.';
    header("Location: login.php");
    exit;
}

// ── Validasi redirect (keamanan) ────────────────────────────
// Hanya izinkan nama file .php biasa, tanpa path traversal
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $redirect) || str_contains($redirect, '..')) {
    $redirect = 'index.php';
}

// ── Cari user di database ────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['login_error'] = 'Terjadi kesalahan server. Coba lagi.';
    header("Location: login.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$valid = false;

if ($user) {
    $hash = $user['password'];

    // Cek password bcrypt (password_hash — standar)
    if (password_verify($password, $hash)) {
        $valid = true;

    // Cek password MD5 lama (upgrade otomatis ke bcrypt)
    } elseif (strlen($hash) === 32 && md5($password) === $hash) {
        $valid   = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $newHash, $user['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
}

// ── Proses hasil login ───────────────────────────────────────
if ($valid) {
    // Set session
    $_SESSION['user']       = $user['nama'];
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = !empty($user['role']) ? $user['role'] : 'user';

    // Hapus error sebelumnya
    unset($_SESSION['login_error'], $_SESSION['last_email']);

    // Admin → selalu ke dashboard
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
        exit;
    }

    // User biasa → tidak boleh ke halaman admin
    if (in_array($redirect, ['dashboard.php', 'cms.php'])) {
        $redirect = 'index.php';
    }

    header("Location: " . $redirect);
    exit;

} else {
    // Login gagal — simpan pesan error dan email terakhir untuk prefill form
    $_SESSION['login_error'] = 'Email atau password salah. Periksa kembali dan coba lagi.';
    $_SESSION['last_email']  = htmlspecialchars($email);
    header("Location: login.php");
    exit;
}