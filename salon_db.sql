-- =============================================
-- DATABASE: salon_db — NISWÀ BEAUTY
-- Schema lengkap semua tabel yang dipakai aplikasi
-- Jalankan script ini di phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS salon_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE salon_db;

-- ─── USERS ───────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun admin default (password: admin123)
INSERT IGNORE INTO users (nama, email, password, role) VALUES
('Admin', 'admin@niswàbeauty.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ─── BOOKINGS ────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(120),
    service VARCHAR(150),
    date DATE,
    time TIME,
    jumlah_orang INT DEFAULT 1,
    jenis_layanan VARCHAR(20) DEFAULT 'datang',
    alamat_hs TEXT,
    catatan TEXT,
    status VARCHAR(20) DEFAULT 'booked',
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data booking
INSERT INTO bookings (name, phone, email, service, date, time) VALUES
('Sari Dewi',     '081234567890', 'sari@email.com',  'Nail Art',           '2025-06-01', '10:00:00'),
('Budi Santoso',  '082345678901', 'budi@email.com',  'Facial',             '2025-06-02', '13:00:00'),
('Rina Putri',    '083456789012', 'rina@email.com',  'Manicure & Pedicure','2025-06-03', '15:00:00');

-- ─── BOOKING BLOCKED SLOTS ───────────────────
CREATE TABLE IF NOT EXISTS booking_blocked_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jam VARCHAR(10) NOT NULL,
    alasan VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT NOW(),
    UNIQUE KEY uk_tanggal_jam (tanggal, jam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ORDERS (produk) ─────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    nama VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    product_price VARCHAR(20) NOT NULL,
    qty INT DEFAULT 1,
    total VARCHAR(20),
    catatan TEXT,
    product_image VARCHAR(500) DEFAULT NULL,
    payment_method VARCHAR(30) DEFAULT 'COD',
    payment_status VARCHAR(20) DEFAULT 'pending',
    order_status VARCHAR(30) DEFAULT 'menunggu_konfirmasi',
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS CONTENT (konten umum halaman) ───────
CREATE TABLE IF NOT EXISTS cms_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(80) NOT NULL,
    `key` VARCHAR(120) NOT NULL,
    value LONGTEXT,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uk_section_key (section, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS SERVICES ────────────────────────────
CREATE TABLE IF NOT EXISTS cms_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120),
    image VARCHAR(255),
    gallery TEXT,
    sort_order INT DEFAULT 0,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS PRICES ──────────────────────────────
CREATE TABLE IF NOT EXISTS cms_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(120),
    name VARCHAR(200),
    price VARCHAR(60),
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS PRODUCTS ────────────────────────────
CREATE TABLE IF NOT EXISTS cms_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    price VARCHAR(60),
    category VARCHAR(60),
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS TESTIMONIALS ────────────────────────
CREATE TABLE IF NOT EXISTS cms_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    service_tag VARCHAR(100),
    text TEXT,
    avatar_color VARCHAR(120) DEFAULT 'linear-gradient(135deg,#f9a8d4,#f472b6)',
    sort_order INT DEFAULT 0,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS PROFIL ──────────────────────────────
CREATE TABLE IF NOT EXISTS cms_profil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(80) NOT NULL,
    `key` VARCHAR(120) NOT NULL,
    value LONGTEXT,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uk_profil_sec_key (section, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS HERO SLIDES ─────────────────────────
CREATE TABLE IF NOT EXISTS cms_hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255),
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS NAVBAR ──────────────────────────────
CREATE TABLE IF NOT EXISTS cms_navbar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(80) NOT NULL,
    `key` VARCHAR(120) NOT NULL,
    value LONGTEXT,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uk_navbar_sec_key (section, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS FOOTER ──────────────────────────────
CREATE TABLE IF NOT EXISTS cms_footer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(80) NOT NULL,
    `key` VARCHAR(120) NOT NULL,
    value LONGTEXT,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uk_footer_sec_key (section, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CMS BOOKING PAGE ────────────────────────
CREATE TABLE IF NOT EXISTS cms_booking_page (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(80) NOT NULL,
    `key` VARCHAR(120) NOT NULL,
    value LONGTEXT,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uk_bkpg_sec_key (section, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;