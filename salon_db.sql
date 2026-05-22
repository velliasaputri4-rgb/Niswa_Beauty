-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Bulan Mei 2026 pada 13.19
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `salon_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `service` varchar(150) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `jumlah_orang` int(11) DEFAULT 1,
  `jenis_layanan` varchar(20) DEFAULT 'datang',
  `alamat_hs` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'booked',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `name`, `phone`, `email`, `service`, `date`, `time`, `jumlah_orang`, `jenis_layanan`, `alamat_hs`, `catatan`, `status`, `created_at`) VALUES
(1, 1, 'Admin', '08971440805', 'admin@niswabeauty.com', 'Hand Spa', '2026-05-21', '15:00:00', 1, 'datang', '', '', 'booked', '2026-05-21 07:42:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking_blocked_slots`
--

CREATE TABLE `booking_blocked_slots` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam` varchar(10) NOT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_booking_page`
--

CREATE TABLE `cms_booking_page` (
  `id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_content`
--

CREATE TABLE `cms_content` (
  `id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_content`
--

INSERT INTO `cms_content` (`id`, `section`, `key`, `value`, `updated_at`) VALUES
(1, 'kontak', 'salon_name', 'NISWÀ BEAUTY', '2026-05-21 07:41:01'),
(2, 'kontak', 'address', 'Jl.Lkr Bangsri, Jepara, Jawa Tengah 59453', '2026-05-21 07:41:01'),
(3, 'kontak', 'hours', 'Senin – Minggu, 09.00 – 20.00', '2026-05-21 07:41:01'),
(4, 'kontak', 'whatsapp', '62812345678', '2026-05-21 07:41:01'),
(5, 'kontak', 'email', 'niswabeauty15@gmail.com', '2026-05-21 07:41:01'),
(6, 'kontak', 'maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0!2d110.7708502!3d-6.5253308!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7123c39ad21875%3A0xd77e4fd098899e2c!2sNISWA%20BEAUTY%20Nail%20%26%20Foot%20Spa!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid', '2026-05-21 07:41:01'),
(7, 'kontak', 'maps_link', 'https://maps.app.goo.gl/czQHcN15FMvfFZy76', '2026-05-21 07:41:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_footer`
--

CREATE TABLE `cms_footer` (
  `id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_footer`
--

INSERT INTO `cms_footer` (`id`, `section`, `key`, `value`, `updated_at`) VALUES
(1, 'footer', 'brand_name', 'NISWÀ BEAUTY', '2026-05-21 07:42:02'),
(2, 'footer', 'brand_desc', 'Kecantikan bertemu kemewahan, dengan sentuhan profesional.', '2026-05-21 07:42:02'),
(3, 'footer', 'instagram_url', 'https://www.instagram.com/niswanail?igsh=MXJtYW1kenhuN3VpNA==', '2026-05-21 07:42:02'),
(4, 'footer', 'tiktok_url', 'https://www.tiktok.com/@niswabeauty?_r=1&_t=ZS-96BG9fNdy7Q', '2026-05-21 07:42:02'),
(5, 'footer', 'whatsapp_url', 'https://wa.me/0882006903068', '2026-05-21 07:42:02'),
(6, 'footer', 'address', 'Jl.Lkr Bangsri, Jepara, Jawa Tengah 59453', '2026-05-21 07:42:02'),
(7, 'footer', 'phone', '+62 882-0069-03068', '2026-05-21 07:42:02'),
(8, 'footer', 'email', 'niswabeauty15@gmail.com', '2026-05-21 07:42:02'),
(9, 'footer', 'hours', 'Senin – Sabtu: 09:00 – 20:00', '2026-05-21 07:42:02'),
(10, 'footer', 'copyright_text', 'NISWÀ BEAUTY. All rights reserved.', '2026-05-21 07:42:02'),
(11, 'footer', 'link_home', '#home', '2026-05-21 07:42:02'),
(12, 'footer', 'link_services', '#services', '2026-05-21 07:42:02'),
(13, 'footer', 'link_product', '#product', '2026-05-21 07:42:02'),
(14, 'footer', 'link_about', '#about', '2026-05-21 07:42:02'),
(15, 'footer', 'link_booking', 'booking.php', '2026-05-21 07:42:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_global_discount`
--

CREATE TABLE `cms_global_discount` (
  `id` int(11) NOT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `discount_pct` tinyint(3) UNSIGNED DEFAULT 0,
  `min_purchase` int(10) UNSIGNED DEFAULT 0,
  `label` varchar(200) DEFAULT '',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_global_discount`
--

INSERT INTO `cms_global_discount` (`id`, `enabled`, `discount_pct`, `min_purchase`, `label`, `updated_at`) VALUES
(1, 1, 10, 100000, 'Beli min. Rp 100.000, hemat 10%!', '2026-05-21 19:17:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_hero_slides`
--

CREATE TABLE `cms_hero_slides` (
  `id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_navbar`
--

CREATE TABLE `cms_navbar` (
  `id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_prices`
--

CREATE TABLE `cms_prices` (
  `id` int(11) NOT NULL,
  `category` varchar(120) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `price` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_prices`
--

INSERT INTO `cms_prices` (`id`, `category`, `name`, `price`, `description`, `image`, `sort_order`) VALUES
(1, 'Henna Series', 'Brow Henna', 'Rp 25.000', 'Pewarnaan alis dengan henna alami yang tahan lama. Mengisi alis tipis dan memberikan tampilan tegas, natural, dan rapi.', 'image/cms_6a0e55a3224763.94722835.jpg', 1),
(2, 'Henna Series', 'Nail Henna Tangan', 'Rp 25.000', 'Motif henna indah di kuku & tangan menggunakan bahan alami. Cocok untuk acara formal maupun casual.', 'image/cms_6a0e55b17bcbb3.12403765.jpg', 2),
(3, 'Henna Series', 'Nail Henna Kaki', 'Rp 30.000', 'Desain henna elegan di area kuku dan kaki. Bahan aman, cocok untuk semua usia.', 'image/cms_6a0e57146d2f26.78531606.jpg', 3),
(4, 'Henna Series', 'Bundling Meni-Henna', 'Rp 75.000', 'Paket hemat manicure lengkap + nail henna. Dua layanan kecantikan dalam satu sesi.', 'image/cms_6a0e561cb661f6.94434746.jpg', 4),
(5, 'Henna Series', 'Henna Fun', 'Rp 25.000 - 100.000', 'Henna dekoratif di tangan dengan berbagai motif pilihan. Semakin kompleks motif, semakin artistik hasilnya.', 'image/cms_6a0e56333a1b32.75236757.jpg', 5),
(6, 'Treatment Spa', 'Bundling Manicure & Pedicure', 'Rp 100.000', 'Paket lengkap perawatan tangan & kaki: scrub, masker, pemotongan kuku, dan finishing oil.', 'image/cms_6a0e5704aace45.98107038.jpg', 6),
(7, 'Treatment Spa', 'Manicure / Pedicure', 'Rp 60.000', 'Perawatan kuku dan kulit tangan atau kaki dengan teknik profesional. Kulit lebih lembut, kuku lebih sehat.', 'image/cms_6a0e56ee0ecd62.10699141.jpg', 7),
(8, 'Treatment Spa', 'Hand Spa', 'Rp 80.000', 'Perawatan intensif tangan: scrub eksfoliasi, masker pelembap, dan pijat relaksasi. Tangan terasa lembut & cerah.', 'image/cms_6a0e56d75f8402.08816020.jpg', 8),
(9, 'Treatment Spa', 'Foot Spa', 'Rp 100.000', 'Terapi kaki lengkap mulai dari perendaman, scrub, masker, hingga pijat refleksi. Cocok setelah hari panjang.', 'image/cms_6a0e56c3c08e97.95956242.jpg', 9),
(10, 'Treatment Spa', 'Callus Treatment', 'Rp 70.000 - 150.000', 'Pengangkatan kapalan dan kulit keras di telapak kaki secara profesional. Makin tebal kalus, makin intensif perawatannya.', 'image/cms_6a0e56ae4bfba0.28962839.jpg', 10),
(11, 'Brow & Lash', 'Brow Bomb', 'Rp 100.000', 'Perawatan alis all-in-one: lifting, tinting, dan setting. Alis tampak tebal, tegas, dan terbentuk sempurna tanpa makeup.', 'image/cms_6a0e555d50a6e9.61055985.jpeg', 11),
(12, 'Brow & Lash', 'Lashlift', 'Rp 70.000', 'Keriting bulu mata permanen tanpa sambungan. Mata terlihat lebih besar dan terbuka secara alami hingga 6–8 minggu.', 'image/cms_6a0e556bb622d0.47968855.jpeg', 12),
(13, 'Brow & Lash', 'Lashlift Tint', 'Rp 90.000', 'Lashlift plus pewarnaan bulu mata agar lebih gelap dan dramatis. Tanpa maskara pun sudah memukau.', 'image/cms_6a0e559126cad9.79699282.jpg', 13),
(14, 'Rambut', 'Creambath', 'Rp 75.000', 'Perawatan rambut dengan krim nutrisi, pijat kepala, dan uap hangat. Rambut lebih lebat, lembut, dan berkilau.', 'image/cms_6a0e57c8001212.05824194.jpg', 14),
(15, 'Rambut', 'Hair Mask', 'Rp 45.000 - 90.000', 'Masker rambut intensif sesuai jenis rambut. Menutrisi dari dalam, mengurangi frizz, dan mengembalikan kilau alami.', 'image/cms_6a0e57da8c9534.09958115.jpg', 15),
(16, 'Rambut', 'Hair Spa', 'Rp 100.000', 'Spa rambut lengkap: shampo, kondisioner, masker, uap, dan pijat. Solusi untuk rambut rusak & kering.', 'image/cms_6a0e57ec075c54.64378793.jpg', 16),
(17, 'Rambut', 'Cuci,Catok,Blow', 'Rp 25.000 - 50.000', 'Cuci rambut + blow dry atau catok sesuai selera. Rambut bersih, rapi, dan siap tampil.', 'image/cms_6a0e57f99ac288.86433628.jpg', 17),
(18, 'Rambut', 'Bleaching S', 'Rp 40.000', 'Bleaching parsial (highlight/poni) untuk mencerahkan area tertentu. Cocok untuk warna pastel atau ombre.', 'image/cms_6a0e58222361f0.33910812.jpg', 18),
(19, 'Rambut', 'Coloring Full', 'Rp 120.000 - 300.000', 'Pewarnaan rambut penuh dari akar hingga ujung. Pilihan warna beragam, hasil merata dan tahan lama.', 'image/cms_6a0e5830d7f833.74831264.jpg', 19),
(20, 'Rambut', 'Bleaching', 'Rp 200.000 - 1.200.000', 'Bleaching full atau intensif untuk mengangkat pigmen rambut. Harga tergantung panjang dan ketebalan rambut.', 'image/cms_6a0e5843df3ab7.79684647.jpg', 20),
(21, 'Rambut', 'Balayage', 'Rp 250.000 - 700.000', 'Teknik pewarnaan gradasi tangan bebas yang menghasilkan tampilan natural sun-kissed. Setiap hasil unik dan personal.', 'image/cms_6a0e5854496d70.49372882.jpg', 21),
(22, 'Rambut', 'Down Peim Poni', 'Rp 100.000 - 300.000', 'Pelurus poni dengan teknik perm down. Poni turun rapi tahan lama tanpa perlu di-styling setiap hari.', 'image/cms_6a0e5871347b70.73840879.jpg', 22),
(23, 'Rambut', 'Keriting Klasik', 'Rp 300.000 - 700.000', 'Keriting permanen dengan batang spiral klasik. Cocok untuk tampilan volume dan berkarakter.', 'image/cms_6a0e58c731fdc3.08130845.jpg', 23),
(24, 'Rambut', 'Keriting Digital', 'Rp 450.000 - 1.700.000', 'Keriting digital dengan alat pemanas modern. Hasil lebih bergelombang lembut, tahan lama, dan terlihat natural.', 'image/cms_6a0e58d654ae88.18283024.jpg', 24),
(25, 'Rambut', 'Keratin Treatment', 'Rp 200.000', 'Perawatan keratin untuk melembutkan dan meluruskan rambut secara alami. Mengurangi frizz & mudah diatur.', 'image/cms_6a0e58e59768f8.73781912.jpg', 25),
(26, 'Rambut', 'Smoothing', 'Rp 200.000 - 400.000', 'Pelurusan rambut semi-permanen yang membuat rambut lurus, halus, dan mudah di-styling. Tahan 3–6 bulan.', 'image/cms_6a0e58f6bfe5b2.57022917.jpg', 26),
(27, 'Nail Art & Services', 'Press On Nail Basic', 'Rp 50.000', 'Press on nail siap pakai dengan desain simpel dan elegan. Mudah dipasang sendiri, tahan beberapa hari.', 'image/cms_6a0e5664ba16c3.78714889.png', 27),
(28, 'Nail Art & Services', 'Press On Nail Motif', 'Rp 75.000', 'Press on nail dengan motif artistik dan detail lebih kompleks. Cocok untuk event spesial.', 'image/cms_6a0e56726b1fb9.59592397.jpeg', 28),
(29, 'Nail Art & Services', 'Kids Basic Gel', 'Rp 40.000', 'Gel kuku aman khusus anak-anak. Warna solid lembut yang tahan lama dan tidak berbau menyengat.', 'image/cms_6a0e5683a0c157.29104885.jpg', 29),
(30, 'Nail Art & Services', 'Kids Gel + 4 Sticker', 'Rp 50.000', 'Gel warna + 4 stiker kuku pilihan anak. Tampilan lucu dan menggemaskan.', 'image/cms_6a0e56940eab14.01984531.jpg', 30),
(31, 'Nail Art & Services', 'Kids Gel + Full Sticker', 'Rp 55.000', 'Gel warna + stiker kuku penuh di semua jari. Seru untuk tampilan spesial si kecil.', 'image/cms_6a0e5732197079.25305826.jpg', 31),
(32, 'Nail Art & Services', 'Gel Basic Tangan / Kaki', 'Rp 85.000', 'Gel warna solid untuk tangan atau kaki dengan hasil rapi dan tahan lama. Cocok untuk tampilan sehari-hari maupun acara spesial.', 'image/cms_6a0e57463e4a78.57362829.jpg', 32),
(33, 'Nail Art & Services', 'Extension', 'Rp 50.000', 'Perpanjangan kuku menggunakan bahan gel berkualitas. Kuku tampak lebih panjang dan elegan secara instan.', 'image/cms_6a0e5756e7e9d9.88509734.jpg', 33),
(34, 'Nail Art & Services', 'Gel French / Cat Eyes', 'Rp 105.000', 'Gel dengan desain French classic atau efek cat eye yang memukau. Hasil bersih, presisi, dan tahan lama.', 'image/cms_6a0e5766def235.25488143.jpg', 34),
(35, 'Nail Art & Services', 'Remove Gel', 'Rp 50.000', 'Pembersihan gel kuku secara aman tanpa merusak kuku asli. Proses cepat dan nyaman menggunakan teknik profesional.', 'image/cms_6a0e577a6eb8b9.82250988.jpg', 35),
(36, 'Nail Art & Services', 'Gel Ombre / Blush On', 'Rp 135.000', 'Gradasi warna lembut ombre atau efek blush on di kuku. Tampilan feminin, romantis, dan cocok untuk berbagai kesempatan.', 'image/cms_6a0e5797ad6205.58605133.jpg', 36),
(37, 'Nail Art & Services', 'Remove Extension', 'Rp 65.000', 'Pelepasan extension kuku secara aman dan menyeluruh. Kuku asli tetap terjaga kesehatannya setelah proses pengangkatan.', 'image/cms_6a0e57a5a6d0e7.33057121.jpg', 37),
(38, 'Nail Art & Services', 'Bundling Nail Art + Extension', 'Rp 150.000', 'Paket hemat: extension kuku plus nail art desain pilihan. Dua layanan premium dalam satu sesi yang efisien.', 'image/cms_6a0e57b8eac1f8.24138183.jpg', 38);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_products`
--

CREATE TABLE `cms_products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `price` varchar(60) DEFAULT NULL,
  `category` varchar(60) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `discount_pct` tinyint(3) UNSIGNED DEFAULT 0,
  `min_purchase` int(10) UNSIGNED DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_products`
--

INSERT INTO `cms_products` (`id`, `name`, `price`, `category`, `image`, `sort_order`, `discount_pct`, `min_purchase`, `updated_at`) VALUES
(1, 'Ruby Bloom Nails', 'Rp 55.000', 'glam', 'image/cms_6a0e5970516b03.01632759.jpeg', 0, 0, 0, '2026-05-21 08:02:19'),
(2, 'Velvet Wine Nails', 'Rp 55.000', 'glam', 'image/cms_6a0e5994f08501.77076453.jpeg', 1, 0, 0, '2026-05-21 08:02:12'),
(3, 'Ivory Blossom Nails', 'Rp 55.000', 'glam', 'image/cms_6a0e59d0b5d8c1.97333025.jpeg', 2, 0, 0, '2026-05-21 08:03:12'),
(4, 'Lavender Muse', 'Rp 55.000', 'glam', 'image/cms_6a0e5a3d388b45.48371889.jpeg', 3, 0, 0, '2026-05-21 08:05:01'),
(5, 'Pink Sakura Glow', 'Rp 55.000', 'glam', 'image/cms_6a0e5a674a7597.38337673.jpeg', 4, 0, 0, '2026-05-21 08:05:43'),
(6, 'Midnight Celeste', 'Rp 55.000', 'glam', 'image/cms_6a0e5a8a94c8f8.94708700.jpeg', 5, 0, 0, '2026-05-21 08:06:18'),
(7, 'Sunset Petal French', 'Rp 50.000', 'simple', 'image/cms_6a0e5abaa616b4.07455583.jpeg', 6, 5, 0, '2026-05-21 19:19:02'),
(8, 'Blue Porcelain Bloom', 'Rp 50.000', 'simple', 'image/cms_6a0e5ad718af38.06929014.jpeg', 7, 0, 0, '2026-05-21 08:07:35'),
(9, 'Ruby Floral Line Art', 'Rp 50.000', 'simple', 'image/cms_6a0e5af0ee05c3.52105253.jpeg', 8, 0, 0, '2026-05-21 08:08:00'),
(10, 'red heart french nails', 'Rp 50.000', 'simple', 'image/cms_6a0e5b0954c0a6.09256343.jpeg', 9, 0, 0, '2026-05-21 08:08:25'),
(11, '3D flower jelly nails', 'Rp 50.000', 'simple', 'image/cms_6a0e5b24738067.89519595.jpeg', 10, 0, 0, '2026-05-21 08:08:52'),
(12, 'milky pink botanical nails', 'Rp 50.000', 'simple', 'image/cms_6a0e5b401f21d1.60377482.jpeg', 11, 0, 0, '2026-05-21 08:09:20'),
(13, 'rose marble floral nails', 'Rp 50.000', 'simple', 'image/cms_6a0e5b5be609a1.17705846.jpeg', 12, 0, 0, '2026-05-21 08:09:47'),
(14, 'pink cat eye gold line', 'Rp 50.000', 'simple', 'image/cms_6a0e5b7447be14.19253542.jpeg', 13, 0, 0, '2026-05-21 08:10:12'),
(15, 'Caramel Star Elegance', 'Rp 75.000', 'wedding', 'image/cms_6a0e5bb13a1db0.96465877.jpeg', 14, 0, 0, '2026-05-21 08:11:13'),
(16, 'Mocha Petal Marble', 'Rp 75.000', 'wedding', 'image/cms_6a0e5bdadd8904.16580938.jpeg', 15, 0, 0, '2026-05-21 08:11:54'),
(17, 'Emerald Aura Nails', 'Rp 55.000', 'glam', 'image/cms_6a0e59f46598a9.66177536.jpeg', 0, 0, 0, '2026-05-21 08:03:48'),
(18, 'Sapphire Luxe Nails', 'Rp 55.000', 'glam', 'image/cms_6a0e5a11de0480.21478970.jpeg', 0, 0, 0, '2026-05-21 08:04:17'),
(19, 'Emerald Marble Luxe', 'Rp 75.000', 'wedding', 'image/cms_6a0e5c086532e0.34914800.jpeg', 0, 0, 0, '2026-05-21 08:12:40'),
(20, 'Blush Petal Glow', 'Rp 75.000', 'wedding', 'image/cms_6a0e5c576c30f3.81454049.jpeg', 0, 0, 0, '2026-05-21 08:13:59'),
(21, 'Rose Sakura Charm', 'Rp 75.000', 'wedding', 'image/cms_6a0e5c8a6688d1.56239580.jpeg', 0, 0, 0, '2026-05-21 08:14:50'),
(22, 'silver metalic', 'Rp 75.000', 'wedding', 'image/cms_6a0e5d9b4a4bd3.68407531.jpeg', 0, 0, 0, '2026-05-21 08:19:23'),
(23, 'Golden Sunflower Nude', 'Rp 75.000', 'wedding', 'image/cms_6a0e98e0a544b2.63051309.jpeg', 0, 0, 0, '2026-05-21 12:32:16'),
(24, 'Pearl Chrome Royal', 'Rp 75.000', 'wedding', 'image/cms_6a0e5d07e3bd01.94268475.jpeg', 0, 0, 0, '2026-05-21 08:16:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_profil`
--

CREATE TABLE `cms_profil` (
  `id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_services`
--

CREATE TABLE `cms_services` (
  `id` int(11) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_services`
--

INSERT INTO `cms_services` (`id`, `name`, `image`, `gallery`, `sort_order`, `updated_at`) VALUES
(1, 'Haircut', 'image/cms_6a0e51da224be8.57098066.jpg', 'image/download (9).jpg,image/I LOVE HAIRSTYLE __.jpg,image/Long layers cutting_ (1).jpg,image/download (10).jpg', 1, '2026-05-21 07:29:14'),
(2, 'Coloring', 'image/cms_6a0e51fbe47f65.59603019.jpg', 'image/WhatsApp Image 2026-05-08 at 11.00.07.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.07 (1).jpeg,image/WhatsApp Image 2026-05-08 at 11.00.08.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11 (1).jpeg', 2, '2026-05-21 07:29:47'),
(3, 'Nailart', 'image/Fall nails brown nails inspo.jpg', 'image/Fall nails brown nails inspo.jpg,image/download (11).jpg,image/download (12).jpg,image/download (13).jpg,image/download (14).jpg', 3, '2026-05-21 07:27:39'),
(4, 'Hair Treatment', 'image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg', 'image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg,image/Keratin Hair Transformation 💫 Before & After.jpg,image/cms_6a0db37a8f1c00.20137839.jpg,image/cms_6a03d505e37591.37961910.jpg,image/download (19).jpg', 4, '2026-05-21 07:34:55'),
(5, 'Foot SPA', 'image/download (8).jpg', 'image/download (8).jpg,image/footspa.jpeg,image/cms_6a03cd101ba686.02645848.jpg,image/WhatsApp Image 2026-05-06 at 10.04.34.jpeg,image/cms_6a0db6dace79e0.98341738.jpg', 5, '2026-05-21 07:33:15'),
(6, 'Henna Series', 'image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg', 'image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg,image/WhatsApp Image 2026-05-08 at 11.05.55.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (1).jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (2).jpeg', 6, '2026-05-21 07:27:39'),
(7, 'Press on Nail', 'image/cms_6a0e543cb5f658.97899333.jpeg', 'image/download (6).jpg,image/download (15).jpg,image/download (18).jpg,image/download (17).jpg', 7, '2026-05-21 07:39:24'),
(8, 'Eye Lash', 'image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg', 'image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.30.jpeg,image/eyelash.jpeg,image/WhatsApp Image 2026-05-12 at 14.53.59.jpeg', 8, '2026-05-21 07:38:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cms_testimonials`
--

CREATE TABLE `cms_testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `service_tag` varchar(100) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `avatar_color` varchar(120) DEFAULT 'linear-gradient(135deg,#f9a8d4,#f472b6)',
  `sort_order` int(11) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cms_testimonials`
--

INSERT INTO `cms_testimonials` (`id`, `name`, `service_tag`, `text`, `avatar_color`, `sort_order`, `updated_at`) VALUES
(1, 'Ninda Ayu', 'Nail Art & Foot Spa', 'Nail art-nya bagus banget, hasilnya rapi dan tahan lama! Mbak-mbaknya ramah dan sabar. Foot spa-nya juga bikin kaki lega banget. Pasti balik lagi! 🌟', 'linear-gradient(135deg,#f9a8d4,#f472b6)', 1, '2026-05-21 07:27:39'),
(2, 'Rizka Amalia', 'Lashlift', 'Lashlift-nya keren banget, mata jadi keliatan lebih segar dan melek. Tempatnya bersih dan nyaman, harga juga worth it. Recommended banget!', 'linear-gradient(135deg,#6ee7b7,#34d399)', 2, '2026-05-21 07:27:39'),
(3, 'Siti Maryam', 'Callus Treatment', 'Callus treatment-nya top banget, kaki jadi mulus dan lembut. Pelayanan cepat dan tidak mengecewakan. Sudah langganan di sini dari lama dan selalu puas!', 'linear-gradient(135deg,#fde68a,#f59e0b)', 3, '2026-05-21 07:27:39'),
(4, 'Dian Pertiwi', 'Smoothing', 'Smoothing-nya hasilnya halus banget dan tahan lama! Stafnya profesional dan ramah. Tempatnya cozy, betah deh berlama-lama di sini. Recommended!', 'linear-gradient(135deg,#a78bfa,#7c3aed)', 4, '2026-05-21 07:27:39'),
(5, 'Fatimah Zahra', 'Henna Series', 'Henna series-nya cantik banget, detail dan presisi! Mbak-mbaknya sabar banget ngerjainnya. Harganya juga sangat terjangkau untuk kualitas segini.', 'linear-gradient(135deg,#86efac,#16a34a)', 5, '2026-05-21 07:27:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_price` varchar(20) NOT NULL,
  `qty` int(11) DEFAULT 1,
  `total` varchar(20) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `product_image` varchar(500) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT 'COD',
  `payment_status` varchar(20) DEFAULT 'pending',
  `order_status` varchar(30) DEFAULT 'menunggu_konfirmasi',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `nama`, `whatsapp`, `alamat`, `product_name`, `product_price`, `qty`, `total`, `catatan`, `product_image`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, 1, 'Vellia Ragil Saputri', '08971440805', 'l', 'Ruby Floral Line Art x1, Blue Porcelain Bloom x1, Sunset Petal French x13', 'Rp 562.500', 1, 'Rp 567.500', '', '[\"image/cms_6a0e5af0ee05c3.52105253.jpeg\",\"image/cms_6a0e5ad718af38.06929014.jpeg\",\"image/cms_6a0e5abaa616b4.07455583.jpeg\"]', 'COD', 'pending', 'menunggu_konfirmasi', '2026-05-21 08:23:22'),
(2, 1, 'Vellia Ragil Saputri', '089629633152', 'smk nnegeri 1 bangsri', 'Sunset Petal French x15, Blue Porcelain Bloom x1, Ruby Floral Line Art x1', 'Rp 637.500', 1, 'Rp 642.500', '', '[\"image/cms_6a0e5abaa616b4.07455583.jpeg\",\"image/cms_6a0e5ad718af38.06929014.jpeg\",\"image/cms_6a0e5af0ee05c3.52105253.jpeg\"]', 'COD', 'pending', 'menunggu_konfirmasi', '2026-05-21 10:21:29'),
(3, 1, 'Admin', '089629633152', 'j', 'Ruby Floral Line Art', 'Rp 50.000', 1, 'Rp 55.000', '', 'image/cms_6a0e5af0ee05c3.52105253.jpeg', 'COD', 'pending', 'menunggu_konfirmasi', '2026-05-21 10:25:22'),
(4, 1, 'Admin', '089629633152', 'kjgjg', 'red heart french nails x16', 'Rp 600.000', 1, 'Rp 605.000', '', '[\"image/cms_6a0e5b0954c0a6.09256343.jpeg\"]', 'COD', 'pending', 'menunggu_konfirmasi', '2026-05-21 10:26:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@niswabeauty.com', '$2y$12$vSbgoSBz5VC76iXH51KLSe7HyrTaiNCnyCzW7H.fwbZHyHK1apZgi', 'admin', '2026-05-21 07:25:45');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookings_user` (`user_id`);

--
-- Indeks untuk tabel `booking_blocked_slots`
--
ALTER TABLE `booking_blocked_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tanggal_jam` (`tanggal`,`jam`);

--
-- Indeks untuk tabel `cms_booking_page`
--
ALTER TABLE `cms_booking_page`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bkpg_sec_key` (`section`,`key`);

--
-- Indeks untuk tabel `cms_content`
--
ALTER TABLE `cms_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_section_key` (`section`,`key`);

--
-- Indeks untuk tabel `cms_footer`
--
ALTER TABLE `cms_footer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_footer_sec_key` (`section`,`key`);

--
-- Indeks untuk tabel `cms_global_discount`
--
ALTER TABLE `cms_global_discount`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cms_hero_slides`
--
ALTER TABLE `cms_hero_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cms_navbar`
--
ALTER TABLE `cms_navbar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_navbar_sec_key` (`section`,`key`);

--
-- Indeks untuk tabel `cms_prices`
--
ALTER TABLE `cms_prices`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cms_products`
--
ALTER TABLE `cms_products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cms_profil`
--
ALTER TABLE `cms_profil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_profil_sec_key` (`section`,`key`);

--
-- Indeks untuk tabel `cms_services`
--
ALTER TABLE `cms_services`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `booking_blocked_slots`
--
ALTER TABLE `booking_blocked_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cms_booking_page`
--
ALTER TABLE `cms_booking_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cms_content`
--
ALTER TABLE `cms_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `cms_footer`
--
ALTER TABLE `cms_footer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `cms_global_discount`
--
ALTER TABLE `cms_global_discount`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `cms_hero_slides`
--
ALTER TABLE `cms_hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cms_navbar`
--
ALTER TABLE `cms_navbar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cms_prices`
--
ALTER TABLE `cms_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `cms_products`
--
ALTER TABLE `cms_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `cms_profil`
--
ALTER TABLE `cms_profil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cms_services`
--
ALTER TABLE `cms_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
