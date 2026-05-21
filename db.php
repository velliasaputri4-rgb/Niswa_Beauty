<?php
// db.php — NISWÀ BEAUTY
// Koneksi database + auto-setup: buat database, tabel, dan akun admin otomatis
// Tidak perlu import SQL manual — cukup jalankan aplikasi, semua siap sendiri.

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'salon_db');
}

if (!isset($conn) || !$conn) {

    // ── 1. Koneksi tanpa pilih database dulu (buat DB jika belum ada) ──────
    $connInit = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);

    if (!$connInit) {
        die('<div style="font-family:Poppins,sans-serif;background:#fff0f5;color:#c0392b;
            padding:20px 30px;margin:20px;border-left:5px solid #e74c3c;border-radius:8px;font-size:15px;">
            <strong>❌ Koneksi MySQL Gagal!</strong><br>
            Error: ' . mysqli_connect_error() . '<br><br>
            <small>Pastikan XAMPP / Laragon MySQL sudah aktif.</small>
        </div>');
    }

    // ── 2. Buat database jika belum ada ─────────────────────────────────────
    mysqli_query($connInit, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mysqli_select_db($connInit, DB_NAME);
    mysqli_set_charset($connInit, 'utf8mb4');

    $conn = $connInit;

    // ── 3. Buat semua tabel (CREATE TABLE IF NOT EXISTS — aman diulang) ─────

    // USERS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        nama        VARCHAR(100)                        NOT NULL,
        email       VARCHAR(120)                        NOT NULL UNIQUE,
        password    VARCHAR(255)                        NOT NULL,
        role        ENUM('user','admin')    NOT NULL    DEFAULT 'user',
        created_at  DATETIME                            DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-fix: tambah kolom role jika tabel lama belum punya
    $cekRole = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    if ($cekRole && mysqli_num_rows($cekRole) === 0) {
        mysqli_query($conn, "ALTER TABLE users
            ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
    }
    mysqli_query($conn, "UPDATE users SET role='user' WHERE role IS NULL OR role=''");

    // BOOKINGS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bookings (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         INT             DEFAULT NULL,
        name            VARCHAR(100)    NOT NULL,
        phone           VARCHAR(30),
        email           VARCHAR(120),
        service         VARCHAR(150),
        date            DATE,
        time            TIME,
        jumlah_orang    INT             DEFAULT 1,
        jenis_layanan   VARCHAR(20)     DEFAULT 'datang',
        alamat_hs       TEXT,
        catatan         TEXT,
        status          VARCHAR(20)     DEFAULT 'booked',
        created_at      DATETIME        DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // BOOKING BLOCKED SLOTS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS booking_blocked_slots (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        tanggal     DATE            NOT NULL,
        jam         VARCHAR(10)     NOT NULL,
        alasan      VARCHAR(255)    DEFAULT NULL,
        created_at  DATETIME        DEFAULT NOW(),
        UNIQUE KEY uk_tanggal_jam (tanggal, jam)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ORDERS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         INT             DEFAULT NULL,
        nama            VARCHAR(100)    NOT NULL,
        whatsapp        VARCHAR(20)     NOT NULL,
        alamat          TEXT            NOT NULL,
        product_name    VARCHAR(100)    NOT NULL,
        product_price   VARCHAR(20)     NOT NULL,
        qty             INT             DEFAULT 1,
        total           VARCHAR(20),
        catatan         TEXT,
        product_image   VARCHAR(500)    DEFAULT NULL,
        payment_method  VARCHAR(30)     DEFAULT 'COD',
        payment_status  VARCHAR(20)     DEFAULT 'pending',
        order_status    VARCHAR(30)     DEFAULT 'menunggu_konfirmasi',
        created_at      DATETIME        DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS CONTENT
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_content (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        section     VARCHAR(80)     NOT NULL,
        `key`       VARCHAR(120)    NOT NULL,
        value       LONGTEXT,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_section_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS SERVICES
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_services (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(120),
        image       VARCHAR(255),
        gallery     TEXT,
        sort_order  INT             DEFAULT 0,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS PRICES
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_prices (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        category    VARCHAR(120),
        name        VARCHAR(200),
        price       VARCHAR(60),
        description TEXT            DEFAULT NULL,
        image       VARCHAR(255)    DEFAULT NULL,
        sort_order  INT             DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS PRODUCTS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_products (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(150),
        price       VARCHAR(60),
        category    VARCHAR(60),
        image       VARCHAR(255),
        sort_order  INT             DEFAULT 0,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-fix: tambah kolom category jika belum ada (tabel lama)
    $cekCatCol = mysqli_query($conn, "SHOW COLUMNS FROM cms_products LIKE 'category'");
    if ($cekCatCol && mysqli_num_rows($cekCatCol) === 0) {
        mysqli_query($conn, "ALTER TABLE cms_products ADD COLUMN category VARCHAR(60) DEFAULT 'simple' AFTER price");
    }

    // Auto-fix: isi category NULL/kosong dengan 'simple'
    mysqli_query($conn, "UPDATE cms_products SET category='simple' WHERE category IS NULL OR category=''");

    // Auto-seed: kalau tabel kosong, insert produk default
    $cekProduk = mysqli_query($conn, "SELECT COUNT(*) as n FROM cms_products");
    $jmlProduk = $cekProduk ? (int)(mysqli_fetch_assoc($cekProduk)['n'] ?? 0) : 0;
    if ($jmlProduk === 0) {
        $defaultProds = [
            ['Cat Eye Nails',          'Rp 22.000', 'simple',  'image/nail1,22k.jpeg'],
            ['Cat Eye Nails Pink',     'Rp 17.000', 'simple',  'image/WhatsApp Image 2026-05-07 at 10.10.41.jpeg'],
            ['Cat Eye Coquette Nails', 'Rp 22.000', 'glam',    'image/cateyeqouket.jpeg'],
            ['Butterfly Nails',        'Rp 25.000', 'wedding', 'image/WhatsApp Image 2026-05-06 at 11.22.27.jpeg'],
            ['Cat Eye Nails',          'Rp 20.000', 'simple',  'image/WhatsApp Image 2026-05-06 at 11.05.13.jpeg'],
            ['Cat Eye Coquette Nails', 'Rp 22.000', 'glam',    'image/WhatsApp Image 2026-05-06 at 10.21.26.jpeg'],
            ['Elegant Nails',          'Rp 22.000', 'glam',    'image/WhatsApp Image 2026-05-06 at 10.21.24.jpeg'],
            ['Cat Eye Nails',          'Rp 20.000', 'simple',  'image/WhatsApp Image 2026-05-06 at 11.05.12.jpeg'],
            ['Cat Eye Red Nails',      'Rp 20.000', 'simple',  'image/WhatsApp Image 2026-05-06 at 11.05.12 (1).jpeg'],
            ['Simple Nails',           'Rp 17.000', 'simple',  'image/WhatsApp Image 2026-05-07 at 10.09.45.jpeg'],
            ['Cat Eye Pink Nails',     'Rp 20.000', 'simple',  'image/WhatsApp Image 2026-05-06 at 11.05.11.jpeg'],
            ['Sun Flower',             'Rp 17.000', 'glam',    'image/WhatsApp Image 2026-05-07 at 10.05.32.jpeg'],
            ['Bling bling Nails',      'Rp 17.000', 'glam',    'image/WhatsApp Image 2026-05-07 at 10.10.14.jpeg'],
            ['Elegant Nails',          'Rp 17.000', 'simple',  'image/WhatsApp Image 2026-05-07 at 10.06.14.jpeg'],
            ['Elegant Nails',          'Rp 25.000', 'wedding', 'image/WhatsApp Image 2026-05-06 at 11.20.31.jpeg'],
            ['Elegant Nails',          'Rp 25.000', 'wedding', 'image/WhatsApp Image 2026-05-06 at 11.17.28.jpeg'],
        ];
        $stmtP = mysqli_prepare($conn,
            "INSERT INTO cms_products (name, price, category, image, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultProds as $i => $pd) {
            mysqli_stmt_bind_param($stmtP, 'ssssi', $pd[0], $pd[1], $pd[2], $pd[3], $i);
            mysqli_stmt_execute($stmtP);
        }
        mysqli_stmt_close($stmtP);
    }

    // CMS TESTIMONIALS
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_testimonials (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        name            VARCHAR(100),
        service_tag     VARCHAR(100),
        text            TEXT,
        avatar_color    VARCHAR(120)    DEFAULT 'linear-gradient(135deg,#f9a8d4,#f472b6)',
        sort_order      INT             DEFAULT 0,
        updated_at      DATETIME        DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS PROFIL
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_profil (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        section     VARCHAR(80)     NOT NULL,
        `key`       VARCHAR(120)    NOT NULL,
        value       LONGTEXT,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_profil_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS HERO SLIDES
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_hero_slides (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        image       VARCHAR(255),
        sort_order  INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS NAVBAR
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_navbar (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        section     VARCHAR(80)     NOT NULL,
        `key`       VARCHAR(120)    NOT NULL,
        value       LONGTEXT,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_navbar_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS FOOTER
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_footer (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        section     VARCHAR(80)     NOT NULL,
        `key`       VARCHAR(120)    NOT NULL,
        value       LONGTEXT,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_footer_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // CMS BOOKING PAGE
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_booking_page (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        section     VARCHAR(80)     NOT NULL,
        `key`       VARCHAR(120)    NOT NULL,
        value       LONGTEXT,
        updated_at  DATETIME        DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_bkpg_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── 4. Auto-fix: ganti email admin lama yang pakai karakter à ───────────
    // (email dengan à tidak bisa diketik di form login biasa)
    mysqli_query($conn, "UPDATE users
        SET email = 'admin@niswabeauty.com'
        WHERE role = 'admin'
          AND email LIKE '%nisw%beauty%'
          AND email != 'admin@niswabeauty.com'");

    // ── 5. Buat akun admin default jika belum ada sama sekali ───────────────
    // Email    : admin@niswabeauty.com
    // Password : admin123
    $cekAdmin = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' LIMIT 1");
    if (!$cekAdmin || mysqli_num_rows($cekAdmin) === 0) {
        $hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtA = mysqli_prepare($conn,
            "INSERT IGNORE INTO users (nama, email, password, role) VALUES ('Admin', 'admin@niswabeauty.com', ?, 'admin')");
        mysqli_stmt_bind_param($stmtA, 's', $hashAdmin);
        mysqli_stmt_execute($stmtA);
        mysqli_stmt_close($stmtA);
    }
}
