<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$isLoggedIn = isset($_SESSION['user']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin');
$userName   = $_SESSION['user'] ?? '';

/* ══════════════════════════════════════════════
   DATABASE CONNECTION
══════════════════════════════════════════════ */
$conn = @mysqli_connect("localhost", "root", "", "salon_db");
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    // Pastikan tabel orders ada
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
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
        created_at DATETIME DEFAULT NOW()
    )");
    // AUTO-FIX kolom
    foreach (["user_id"=>"ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL",
              "catatan" =>"ALTER TABLE orders ADD COLUMN catatan TEXT",
              "total"   =>"ALTER TABLE orders ADD COLUMN total VARCHAR(20)"] as $col=>$sql) {
        $cek = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$col'");
        if ($cek && mysqli_num_rows($cek) === 0) mysqli_query($conn, $sql);
    }
}

/* ══════════════════════════════════════════════
   HELPER FUNCTIONS
══════════════════════════════════════════════ */
function getContent($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function getProfil($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_profil WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function esc($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

/* ══════════════════════════════════════════════
   LOAD SEMUA DATA DARI DATABASE
══════════════════════════════════════════════ */

// ── Hero ──
$hero = [
    'title'         => getContent($conn,'hero','title',         'Temukan Kecantikan Terbaikmu'),
    'subtitle'      => getContent($conn,'hero','subtitle',      'Layanan premium untuk tampilan terbaik Anda'),
    'btn_primary'   => getContent($conn,'hero','btn_primary',   'Reservasi Sekarang'),
    'btn_secondary' => getContent($conn,'hero','btn_secondary', 'Lihat Layanan'),
    'img1'          => getContent($conn,'hero','img1',          'image/homenailart.jpeg'),
    'img2'          => getContent($conn,'hero','img2',          ''),
    'img3'          => getContent($conn,'hero','img3',          ''),
];

// ── Kontak ──
$kontak = [
    'salon_name' => getContent($conn,'kontak','salon_name','NISWÀ BEAUTY'),
    'address'    => getContent($conn,'kontak','address',   'Jl. Watulumpang, Bangsri, Jepara'),
    'hours'      => getContent($conn,'kontak','hours',     'Senin – Minggu, 08.00 – 20.00'),
    'whatsapp'   => getContent($conn,'kontak','whatsapp',  '62812345678'),
    'maps_embed' => getContent($conn,'kontak','maps_embed','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0!2d110.7708502!3d-6.5253308!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7123c39ad21875%3A0xd77e4fd098899e2c!2sNISWA%20BEAUTY%20Nail%20%26%20Foot%20Spa!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid'),
    'maps_link'  => getContent($conn,'kontak','maps_link', 'https://maps.app.goo.gl/czQHcN15FMvfFZy76'),
];

// ── Profil ──
$profil = [
    'owner_name'    => getProfil($conn,'profil','owner_name',    'Niswa'),
    'owner_tagline' => getProfil($conn,'profil','owner_tagline', '"Kecantikan adalah kepercayaan diri yang paling murni."'),
    'owner_bio1'    => getProfil($conn,'profil','owner_bio1',
        'Pendiri Niswa Beauty memulai perjalanan usahanya dari jasa henna keliling dengan nama Niswa Henna. Dengan penuh semangat dan ketekunan, layanan dilakukan dari rumah ke rumah untuk memenuhi kebutuhan pelanggan di sekitar Jepara.' . "\n\n" .
        'Pada tahun 2018, dunia kecantikan khususnya nail art dan fake nails mulai berkembang pesat. Melihat peluang tersebut, pendiri mulai mempelajari dan mengembangkan layanan nail art menggunakan perlengkapan sederhana seperti nail polish. Berawal dari daerah Lebak Pakis Aji, hasil karya yang teliti dan pelayanan yang baik membuat nama Niswa mulai dikenal masyarakat.' . "\n\n" .
        'Perjalanan usaha semakin berkembang ketika mendapat dukungan dan inspirasi dari salah satu teman di Dubai dalam pengembangan dunia kecantikan. Memasuki tahun 2019, usaha mulai berjalan lebih lancar setelah mendapatkan supplier lokal dan pelanggan dari luar daerah seperti Kudus dan Tanjung, Semarang.'
    ),
    'owner_bio2'    => getProfil($conn,'profil','owner_bio2',
        'Tahun 2020–2021 menjadi masa penuh perjuangan sekaligus perkembangan. Pendiri mulai dikenal oleh beberapa publik figur lokal di Jepara yang menggunakan jasa nail art Niswa. Bahkan pada masa awal, beberapa layanan diberikan secara gratis sebagai bentuk belajar dan membangun relasi. Dukungan teman-teman menjadi salah satu alasan usaha ini terus bertahan dan berkembang.' . "\n\n" .
        'Pada tahun 2022, perjalanan usaha sempat mengalami ujian ketika pendiri mengalami keguguran sehingga mulai membatasi pekerjaan dengan lokasi yang terlalu jauh. Namun semangat untuk terus berkembang tidak berhenti. Di masa tersebut, usaha seserahan berkembang pesat dan menjadi salah satu layanan yang diminati pelanggan.' . "\n\n" .
        'Saat merintis sendiri, jam kerja dimulai dari pukul 10 pagi hingga 9 malam dengan jumlah pelanggan yang bisa mencapai lebih dari 7 orang per hari. Hingga kini, pendiri Niswa Beauty terus belajar dan berkembang, terutama dalam bidang media sosial, pelayanan, dan branding, dengan tetap mempertahankan sikap rendah hati dalam membangun usaha sendiri.'
    ),
    'store_name'    => getProfil($conn,'profil','store_name',    'NISWÀ BEAUTY'),
    'store_tagline' => getProfil($conn,'profil','store_tagline', '"Premium Beauty Experience di Jantung Jepara"'),
    'store_bio1'    => getProfil($conn,'profil','store_bio1',
        'Niswa Beauty merupakan usaha di bidang kecantikan yang berawal dari layanan henna sederhana bernama Niswa Henna. Seiring berkembangnya tren kecantikan pada tahun 2018, usaha ini mulai merambah ke layanan nail art dan fake nails untuk memenuhi kebutuhan pelanggan, khususnya calon pengantin.' . "\n\n" .
        'Dengan kualitas pelayanan dan hasil karya yang terus berkembang, Niswa mulai dikenal oleh masyarakat sekitar hingga mendapatkan pelanggan dari luar daerah pada tahun 2019. Perkembangan usaha semakin baik setelah memiliki supplier lokal dan jaringan pelanggan yang lebih luas.' . "\n\n" .
        'Pada tahun 2020, Niswa Beauty membuka studio kecil pertama di rumah daerah Tengguli. Tidak hanya melayani nail art, usaha ini juga menyediakan layanan wedding, gift, dan seserahan. Seiring waktu, layanan nail art menjadi semakin diminati dan dikenal oleh berbagai kalangan di Jepara.'
    ),
    'store_bio2'    => getProfil($conn,'profil','store_bio2',
        'Tanggal 15 Juli 2023 menjadi tonggak penting dengan resmi berdirinya Niswa Beauty bersama dua orang tim pertama. Sejak saat itu, usaha berkembang lebih profesional dengan pelayanan yang semakin lengkap dan terstruktur. Beberapa kerja sama dari luar kota hingga tawaran bergabung dengan brand kecantikan besar pernah datang, namun Niswa Beauty memilih untuk tetap berkembang secara mandiri.'
    ),
    'store_image'   => getProfil($conn,'profil','store_image',   'image/WhatsApp Image 2026-05-08 at 10.02.50.jpeg'),
    'tech_text'     => getProfil($conn,'profil','tech_text',
        'Niswà Beauty juga terus mengikuti perkembangan zaman. Berawal dari promosi sederhana melalui Story WhatsApp, kini hadir lebih luas lewat Instagram dan TikTok — termasuk penggunaan sistem pembayaran digital QRIS sejak awal tahun 2025. Hingga saat ini, Niswà Beauty terus berkembang untuk memberikan pengalaman kecantikan terbaik bagi setiap pelanggan.'
    ),
];

// ── Services ──
$servicesRows  = $conn ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM cms_services ORDER BY sort_order, id"), MYSQLI_ASSOC) : [];
// Fallback jika tabel kosong
$defaultServices = [
    ['id'=>0,'name'=>'Haircut',       'image'=>'image/download (9).jpg',                          'gallery'=>'image/download (9).jpg,image/I LOVE HAIRSTYLE __.jpg,image/Long layers cutting_ (1).jpg,image/download (10).jpg'],
    ['id'=>0,'name'=>'Coloring',      'image'=>'image/WhatsApp Image 2026-05-08 at 11.00.07.jpeg','gallery'=>'image/WhatsApp Image 2026-05-08 at 11.00.07.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.07 (1).jpeg,image/WhatsApp Image 2026-05-08 at 11.00.08.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11 (1).jpeg'],
    ['id'=>0,'name'=>'Nailart',       'image'=>'image/Fall nails brown nails inspo.jpg',          'gallery'=>'image/Fall nails brown nails inspo.jpg,image/download (11).jpg,image/download (12).jpg,image/download (13).jpg,image/download (14).jpg'],
    ['id'=>0,'name'=>'Hair Treatment','image'=>'image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg','gallery'=>'image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg,image/Keratin Hair Transformation 💫 Before & After.jpg'],
    ['id'=>0,'name'=>'Foot SPA',      'image'=>'image/download (8).jpg',                          'gallery'=>'image/download (8).jpg,image/footspa.jpeg'],
    ['id'=>0,'name'=>'Henna Series',  'image'=>'image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg','gallery'=>'image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg,image/WhatsApp Image 2026-05-08 at 11.05.55.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (1).jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (2).jpeg'],
    ['id'=>0,'name'=>'Press on Nail', 'image'=>'image/download (6).jpg',                          'gallery'=>'image/download (6).jpg,image/download (15).jpg'],
    ['id'=>0,'name'=>'Eye Lash',      'image'=>'image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg','gallery'=>'image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.30.jpeg'],
];
$services = !empty($servicesRows) ? $servicesRows : $defaultServices;

// ── Harga ──
$pricesRows = $conn ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM cms_prices ORDER BY category, sort_order, id"), MYSQLI_ASSOC) : [];
// Kelompokkan per kategori
$priceList = [];
if (!empty($pricesRows)) {
    foreach ($pricesRows as $pr) {
        $priceList[$pr['category']][] = [
            'name'  => $pr['name'],
            'price' => $pr['price'],
            'desc'  => $pr['description'] ?? '',
        ];
    }
} else {
    // Fallback hardcoded
    $priceList = [
        'Henna Series' => [
            ['name'=>'Brow Henna','price'=>'Rp 25.000'],
            ['name'=>'Nail Henna Tangan','price'=>'Rp 25.000'],
            ['name'=>'Nail Henna Kaki','price'=>'Rp 30.000'],
            ['name'=>'Bundling Meni-Henna','price'=>'Rp 75.000'],
            ['name'=>'Henna Fun','price'=>'Rp 25.000 - 100.000'],
        ],
        'Treatment Spa' => [
            ['name'=>'Bundling Manicure & Pedicure','price'=>'Rp 100.000'],
            ['name'=>'Manicure / Pedicure','price'=>'Rp 60.000'],
            ['name'=>'Hand Spa','price'=>'Rp 80.000'],
            ['name'=>'Foot Spa','price'=>'Rp 100.000'],
            ['name'=>'Callus Treatment','price'=>'Rp 70.000 - 150.000'],
        ],
        'Brow & Lash' => [
            ['name'=>'Brow Bomb','price'=>'Rp 100.000'],
            ['name'=>'Lashlift','price'=>'Rp 70.000'],
            ['name'=>'Lashlift Tint','price'=>'Rp 90.000'],
        ],
        'Rambut' => [
            ['name'=>'Creambath','price'=>'Rp 75.000'],
            ['name'=>'Hair Mask','price'=>'Rp 45.000 - 90.000'],
            ['name'=>'Hair Spa','price'=>'Rp 100.000'],
            ['name'=>'Cuci,Catok,Blow','price'=>'Rp 25.000 - 50.000'],
            ['name'=>'Bleaching S','price'=>'Rp 40.000'],
            ['name'=>'Coloring Full','price'=>'Rp 120.000 - 300.000'],
            ['name'=>'Bleaching','price'=>'Rp 200.000 - 1.200.000'],
            ['name'=>'Balayage','price'=>'Rp 250.000 - 700.000'],
            ['name'=>'Down Peim Poni','price'=>'Rp 100.000 - 300.000'],
            ['name'=>'Keriting Klasik','price'=>'Rp 300.000 - 700.000'],
            ['name'=>'Keriting Digital','price'=>'Rp 450.000 - 1.700.000'],
            ['name'=>'Keratin Treatment','price'=>'Rp 200.000'],
            ['name'=>'Smoothing','price'=>'Rp 200.000 - 400.000'],
        ],
        'Nail Art & Services' => [
            ['name'=>'Press On Nail Basic','price'=>'Rp 50.000'],
            ['name'=>'Press On Nail Motif','price'=>'Rp 75.000'],
            ['name'=>'Kids Basic Gel','price'=>'Rp 40.000'],
            ['name'=>'Kids Gel + 4 Sticker','price'=>'Rp 50.000'],
            ['name'=>'Kids Gel + Full Sticker','price'=>'Rp 55.000'],
            ['name'=>'Gel Basic Tangan / Kaki','price'=>'Rp 85.000'],
            ['name'=>'Extension','price'=>'Rp 50.000'],
            ['name'=>'Gel French / Cat Eyes','price'=>'Rp 105.000'],
            ['name'=>'Remove Gel','price'=>'Rp 50.000'],
            ['name'=>'Gel Ombre / Blush On','price'=>'Rp 135.000'],
            ['name'=>'Remove Extension','price'=>'Rp 65.000'],
            ['name'=>'Bundling Nail Art + Extension','price'=>'Rp 150.000'],
        ],
    ];
}

// ── Produk ──
$productsRows = $conn ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM cms_products ORDER BY sort_order, id"), MYSQLI_ASSOC) : [];
$defaultProducts = [
    ["name"=>"Cat Eye Nails",         "price"=>"Rp 22.000", "category"=>"simple",  "image"=>"image/nail1,22k.jpeg"],
    ["name"=>"Cat Eye Nails Pink",    "price"=>"Rp 17.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-07 at 10.10.41.jpeg"],
    ["name"=>"Cat Eye Coquette Nails","price"=>"Rp 22.000", "category"=>"glam",    "image"=>"image/cateyeqouket.jpeg"],
    ["name"=>"Butterfly Nails",       "price"=>"Rp 25.000", "category"=>"wedding", "image"=>"image/WhatsApp Image 2026-05-06 at 11.22.27.jpeg"],
    ["name"=>"Cat Eye Nails",         "price"=>"Rp 20.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-06 at 11.05.13.jpeg"],
    ["name"=>"Cat Eye Coquette Nails","price"=>"Rp 22.000", "category"=>"glam",    "image"=>"image/WhatsApp Image 2026-05-06 at 10.21.26.jpeg"],
    ["name"=>"Elegant Nails",         "price"=>"Rp 22.000", "category"=>"glam",    "image"=>"image/WhatsApp Image 2026-05-06 at 10.21.24.jpeg"],
    ["name"=>"Cat Eye Nails",         "price"=>"Rp 20.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-06 at 11.05.12.jpeg"],
    ["name"=>"Cat Eye Red Nails",     "price"=>"Rp 20.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-06 at 11.05.12 (1).jpeg"],
    ["name"=>"Simple Nails",          "price"=>"Rp 17.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-07 at 10.09.45.jpeg"],
    ["name"=>"Cat Eye Pink Nails",    "price"=>"Rp 20.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-06 at 11.05.11.jpeg"],
    ["name"=>"Sun Flower",            "price"=>"Rp 17.000", "category"=>"glam",    "image"=>"image/WhatsApp Image 2026-05-07 at 10.05.32.jpeg"],
    ["name"=>"Bling bling Nails",     "price"=>"Rp 17.000", "category"=>"glam",    "image"=>"image/WhatsApp Image 2026-05-07 at 10.10.14.jpeg"],
    ["name"=>"Elegant Nails",         "price"=>"Rp 17.000", "category"=>"simple",  "image"=>"image/WhatsApp Image 2026-05-07 at 10.06.14.jpeg"],
    ["name"=>"Elegant Nails",         "price"=>"Rp 25.000", "category"=>"wedding", "image"=>"image/WhatsApp Image 2026-05-06 at 11.20.31.jpeg"],
    ["name"=>"Elegant Nails",         "price"=>"Rp 25.000", "category"=>"wedding", "image"=>"image/WhatsApp Image 2026-05-06 at 11.17.28.jpeg"],
];
$products = !empty($productsRows) ? $productsRows : $defaultProducts;

// ── Testimoni ──
$testiRows = $conn ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM cms_testimonials ORDER BY sort_order, id"), MYSQLI_ASSOC) : [];
$defaultTestis = [
    ['name'=>'Ninda Ayu',    'service_tag'=>'Nail Art & Foot Spa',  'text'=>'Nail art-nya bagus banget, hasilnya rapi dan tahan lama! Mbak-mbaknya ramah dan sabar. Foot spa-nya juga bikin kaki lega banget. Pasti balik lagi! 🌟',    'avatar_color'=>'linear-gradient(135deg,#f9a8d4,#f472b6)'],
    ['name'=>'Rizka Amalia', 'service_tag'=>'Lashlift',             'text'=>'Lashlift-nya keren banget, mata jadi keliatan lebih segar dan melek. Tempatnya bersih dan nyaman, harga juga worth it. Recommended banget!',              'avatar_color'=>'linear-gradient(135deg,#6ee7b7,#34d399)'],
    ['name'=>'Siti Maryam',  'service_tag'=>'Callus Treatment',     'text'=>'Callus treatment-nya top banget, kaki jadi mulus dan lembut. Pelayanan cepat dan tidak mengecewakan. Sudah langganan di sini dari lama dan selalu puas!',   'avatar_color'=>'linear-gradient(135deg,#fde68a,#f59e0b)'],
    ['name'=>'Dian Pertiwi', 'service_tag'=>'Smoothing',            'text'=>'Smoothing-nya hasilnya halus banget dan tahan lama! Stafnya profesional dan ramah. Tempatnya cozy, betah deh berlama-lama di sini. Recommended!',           'avatar_color'=>'linear-gradient(135deg,#a78bfa,#7c3aed)'],
    ['name'=>'Fatimah Zahra','service_tag'=>'Henna Series',         'text'=>'Henna series-nya cantik banget, detail dan presisi! Mbak-mbaknya sabar banget ngerjainnya. Harganya juga sangat terjangkau untuk kualitas segini.',          'avatar_color'=>'linear-gradient(135deg,#86efac,#16a34a)'],
];
$testimonials = !empty($testiRows) ? $testiRows : $defaultTestis;

/* ══════════════════════════════════════════════
   HANDLE AJAX ORDER
══════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $nama          = trim($_POST['nama']          ?? '');
    $whatsapp      = trim($_POST['whatsapp']      ?? '');
    $alamat        = trim($_POST['alamat']        ?? '');
    $product_name  = trim($_POST['product_name']  ?? '');
    $product_price = trim($_POST['product_price'] ?? '');
    $qty           = max(1, (int)($_POST['qty']   ?? 1));
    $catatan       = trim($_POST['catatan']       ?? '');
    $user_id       = $_SESSION['user_id']         ?? null;

    $harga_num = (int) preg_replace('/[^0-9]/', '', $product_price);
    $total     = 'Rp ' . number_format($harga_num * $qty, 0, ',', '.');

    $errors = [];
    if (empty($nama))     $errors[] = 'Nama wajib diisi.';
    if (empty($whatsapp)) $errors[] = 'WhatsApp wajib diisi.';
    if (empty($alamat))   $errors[] = 'Alamat wajib diisi.';

    if (!empty($errors)) {
        echo json_encode(['success'=>false,'message'=>implode('<br>', $errors)]);
    } elseif (!$conn) {
        echo json_encode(['success'=>false,'message'=>'Koneksi database gagal.']);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (user_id,nama,whatsapp,alamat,product_name,product_price,qty,total,catatan)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        mysqli_stmt_bind_param($stmt, "isssssiss",
            $user_id,$nama,$whatsapp,$alamat,$product_name,$product_price,$qty,$total,$catatan
        );
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal menyimpan: '.mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}

/* ── Ambil kategori unik produk untuk filter tombol ── */
$categories = [];
foreach ($products as $p) {
    $cat = $p['category'] ?? '';
    if ($cat && !in_array($cat, $categories)) $categories[] = $cat;
}

$pageTitle = esc($kontak['salon_name']) . ' — Premium Beauty Experience';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="Salon kecantikan premium di Jepara. Layanan profesional untuk hair, skin, nail & lebih.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css?v=5.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-cream {
            background: #CBB89D; color: #fff; border: none; border-radius: 50px;
            padding: 12px 32px; font-weight: 600; font-size: 15px; letter-spacing: 0.5px;
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(203,184,157,0.4);
        }
        .btn-cream:hover {
            background: #b8a082; color: #fff;
            transform: translateY(-2px); box-shadow: 0 8px 25px rgba(203,184,157,0.5);
        }
        /* Product Section */
        .section-product { background: #fdfaf7; padding: 45px 0; }
        .product-section-title { text-align: center; margin-bottom: 40px; }
        .product-section-title h2 { font-weight: 600; font-size: 32px; font-family: 'Playfair Display', serif; }
        .product-section-title span { color: #8B6F5E; }
        .product-section-title p { color: #777; margin-top: 8px; }
        .filter-buttons { text-align: center; margin-bottom: 40px; }
        .filter-buttons button {
            border: none; background: none; margin: 5px 12px;
            font-weight: 500; color: #888; cursor: pointer;
            font-size: 16px; font-family: 'Poppins', sans-serif; transition: 0.2s;
        }
        .filter-buttons button.active { color: #8B6F5E; border-bottom: 2px solid #8B6F5E; }
        .product-card {
            background: #fff; border-radius: 20px; overflow: hidden; transition: 0.3s; cursor: pointer;
        }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(139,111,94,0.15); }
        .product-img { width: 100%; height: 260px; object-fit: cover; }
        .product-info { padding: 15px; }
        .product-name { font-weight: 500; font-family: 'Poppins', sans-serif; }
        .product-price { color: #8B6F5E; font-weight: 600; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 20px; }
        .btn-beli {
            width: 100%; padding: 8px 0; margin-top: 8px;
            background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
            color: #fff; border: none; border-radius: 10px;
            font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: 0.2s;
        }
        .btn-beli:hover { opacity: 0.85; }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div id="loadingScreen">
    <div class="loading-logo"><?= esc($kontak['salon_name']) ?></div>
    <div class="loading-bar-wrap"><div class="loading-bar"></div></div>
    <div class="loading-text">Loading Beauty...</div>
</div>

<?php include 'navbar.php'; ?>

<!-- ══ HERO ══ -->
<section class="hero-slider">
    <div id="heroCarousel" class="carousel slide carousel-fade">
        <div class="carousel-inner">
            <?php
            // Kumpulkan slide yang ada
            $slides = array_filter([$hero['img1'], $hero['img2'], $hero['img3']]);
            if (empty($slides)) $slides = ['image/homenailart.jpeg'];
            $firstSlide = true;
            foreach ($slides as $slideImg):
            ?>
            <div class="carousel-item <?= $firstSlide ? 'active' : '' ?>">
                <img src="<?= esc($slideImg) ?>" class="d-block w-100" alt="<?= esc($kontak['salon_name']) ?>">
                <?php if ($firstSlide): ?>
                <div class="carousel-caption">
                    <h1><?= esc($hero['title']) ?></h1>
                    <p><?= esc($hero['subtitle']) ?></p>
                    <div class="hero-btn-group">
                        <a href="booking.php" class="hero-btn-primary">
                            <i class="fas fa-calendar-alt"></i> <?= esc($hero['btn_primary']) ?>
                        </a>
                        <a href="#layanan" class="hero-btn-outline">
                            <?= esc($hero['btn_secondary']) ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; $firstSlide = false; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══ SERVICES GRID ══ -->
<section id="layanan" class="services-clean">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="services-title">Layanan Kami</h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2" style="font-size:14px;">Klik layanan untuk melihat contoh hasil</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $idx => $svc):
                // Kumpulkan gallery: split by comma, buang yg kosong
                $gallery = array_values(array_filter(array_map('trim', explode(',', $svc['gallery'] ?? ''))));
                // Kalau gallery kosong, pakai image utama
                if (empty($gallery) && !empty($svc['image'])) $gallery = [$svc['image']];
                // Simpan gallery ke JS variable lewat data-attribute (aman dari karakter spesial)
                $galleryJsonAttr = htmlspecialchars(json_encode($gallery, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                $svcName = esc($svc['name']);
                $svcNameJs = htmlspecialchars($svc['name'], ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="service-box"
                     data-svc-name="<?= $svcNameJs ?>"
                     data-svc-gallery="<?= $galleryJsonAttr ?>"
                     onclick="openSvcFromEl(this)">
                    <?php if (!empty($svc['image'])): ?>
                    <img src="<?= esc($svc['image']) ?>" alt="<?= $svcName ?>">
                    <?php endif; ?>
                    <div class="overlay"><i class="fas fa-images me-1"></i><?= $svcName ?></div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══ SERVICE GALLERY MODAL ══ -->
<div id="serviceGalleryModal" aria-hidden="true">
    <div class="sgm-backdrop" onclick="closeServiceGallery()"></div>
    <div class="sgm-dialog" role="dialog" aria-modal="true">
        <div class="sgm-header">
            <div class="sgm-title-wrap">
                <i class="fas fa-images sgm-title-icon"></i>
                <span id="sgmTitle">Layanan</span>
            </div>
            <button class="sgm-close" onclick="closeServiceGallery()" aria-label="Tutup">&times;</button>
        </div>
        <div class="sgm-main">
            <button class="sgm-arrow sgm-arrow-left" id="sgmPrev" onclick="sgmNav(-1)" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>
            <div class="sgm-img-wrap">
                <img id="sgmMainImg" src="" alt="" class="sgm-main-img">
                <div class="sgm-counter" id="sgmCounter">1 / 1</div>
            </div>
            <button class="sgm-arrow sgm-arrow-right" id="sgmNext" onclick="sgmNav(1)" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="sgm-thumbs" id="sgmThumbs"></div>
        <div class="sgm-footer">
            <a href="booking.php" class="sgm-book-btn"><i class="fas fa-calendar-alt me-2"></i>Reservasi Layanan Ini</a>
        </div>
    </div>
</div>
<script>
(function () {
    var modal=document.getElementById('serviceGalleryModal'),mainImg=document.getElementById('sgmMainImg'),
        titleEl=document.getElementById('sgmTitle'),counter=document.getElementById('sgmCounter'),
        thumbWrap=document.getElementById('sgmThumbs'),prevBtn=document.getElementById('sgmPrev'),
        nextBtn=document.getElementById('sgmNext'),_photos=[],_cur=0;
    // Baca data dari element (aman dari karakter spesial di nama file)
    window.openSvcFromEl=function(el){
        var name   = el.getAttribute('data-svc-name') || '';
        var photos = [];
        try { photos = JSON.parse(el.getAttribute('data-svc-gallery') || '[]'); } catch(e){}
        if (!photos.length) return;
        openServiceGallery(name, photos);
    };
    window.openServiceGallery=function(name,photos){
        _photos=photos;_cur=0;titleEl.textContent=name;
        thumbWrap.innerHTML='';
        photos.forEach(function(src,i){
            var t=document.createElement('img');t.src=src;t.alt=name+' '+(i+1);
            t.className='sgm-thumb'+(i===0?' active':'');
            t.onclick=function(){sgmGoTo(i);};thumbWrap.appendChild(t);
        });
        updateView();modal.classList.add('is-open');document.body.style.overflow='hidden';
        prevBtn.style.display=photos.length>1?'':'none';
        nextBtn.style.display=photos.length>1?'':'none';
        thumbWrap.style.display=photos.length>1?'':'none';
    };
    window.closeServiceGallery=function(){modal.classList.remove('is-open');document.body.style.overflow='';};
    window.sgmNav=function(dir){sgmGoTo((_cur+dir+_photos.length)%_photos.length);};
    function sgmGoTo(idx){_cur=idx;updateView();}
    function updateView(){
        mainImg.style.opacity='0';
        setTimeout(function(){mainImg.src=_photos[_cur];mainImg.style.opacity='1';},120);
        counter.textContent=(_cur+1)+' / '+_photos.length;
        thumbWrap.querySelectorAll('.sgm-thumb').forEach(function(t,i){t.classList.toggle('active',i===_cur);});
    }
    document.addEventListener('keydown',function(e){
        if(!modal.classList.contains('is-open'))return;
        if(e.key==='Escape')closeServiceGallery();
        if(e.key==='ArrowLeft')sgmNav(-1);if(e.key==='ArrowRight')sgmNav(1);
    });
    var touchX=null;
    modal.addEventListener('touchstart',function(e){touchX=e.touches[0].clientX;},{passive:true});
    modal.addEventListener('touchend',function(e){
        if(touchX===null)return;var dx=e.changedTouches[0].clientX-touchX;
        if(Math.abs(dx)>50)sgmNav(dx<0?1:-1);touchX=null;
    },{passive:true});
})();
</script>

<!-- ══ DAFTAR HARGA ══ -->
<section id="harga" class="price-list-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="services-title">Daftar <span style="color:#8B6F5E;">Harga</span></h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2">Harga transparan, kualitas terjamin</p>
        </div>

        <div class="price-cards-grid">
        <?php
        // Urutkan: Treatment Spa atas, Nail Art paling bawah
        $catOrder = ["Brow & Lash","Treatment Spa","Henna Series","Nail Art & Services","Rambut"];
        $sorted = [];
        foreach ($catOrder as $c) { if (isset($priceList[$c])) $sorted[$c] = $priceList[$c]; }
        foreach ($priceList as $c => $v) { if (!isset($sorted[$c])) $sorted[$c] = $v; }

        // Fungsi ambil nilai numerik terendah dari string harga (misal "Rp 25.000 - 100.000" → 25000)
        function lowestPrice($priceStr) {
            preg_match_all('/[\d.]+/', $priceStr, $m);
            $nums = array_map(fn($n) => (int)str_replace('.', '', $n), $m[0]);
            return empty($nums) ? 0 : min($nums);
        }

        // Urutkan item dalam setiap card dari harga terendah
        foreach ($sorted as $cat => &$items) {
            usort($items, fn($a, $b) => lowestPrice($a['price']) - lowestPrice($b['price']));
        }
        unset($items);

        $delay=0; foreach ($sorted as $cat => $items): ?>
        <div class="price-card" data-aos="fade-up" data-aos-delay="<?= $delay*80 ?>">
            <div class="price-card-header">
                <span class="price-card-label"><?= esc($cat) ?></span>
                <span class="price-acc-count"><?= count($items) ?> layanan</span>
            </div>
            <div class="price-acc-body">
                <table class="price-table">
                    <thead><tr><th>Layanan</th><th class="text-end">Harga</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                        <tr class="price-row-clickable" 
                            data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                            data-price="<?= htmlspecialchars($row['price'], ENT_QUOTES) ?>"
                            data-desc="<?= htmlspecialchars($row['desc'] ?? '', ENT_QUOTES) ?>"
                            data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>">
                            <td>
                                <?= esc($row['name']) ?>
                                <span class="price-row-hint"><i class="fa-solid fa-circle-info"></i></span>
                            </td>
                            <td class="text-end price-cell"><?= esc($row['price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $delay++; endforeach; ?>
        </div>

        <div class="text-center mt-4" data-aos="fade-up">
            <p class="text-muted small mb-3">* Harga dapat berubah sewaktu-waktu. Hubungi kami untuk info terkini.</p>
        </div>
    </div>
</section>

<!-- ══ MODAL DESKRIPSI HARGA ══ -->
<div id="priceModal" class="price-modal-overlay" onclick="closePriceModal(event)">
    <div class="price-modal-box">
        <button class="price-modal-close" onclick="closePriceModal(null)"><i class="fa-solid fa-xmark"></i></button>
        <div class="price-modal-cat" id="pmCat"></div>
        <div class="price-modal-name" id="pmName"></div>
        <div class="price-modal-price" id="pmPrice"></div>
        <div class="price-modal-divider"></div>
        <div class="price-modal-desc" id="pmDesc"></div>
        <a id="pmWa" href="#" target="_blank" class="price-modal-wa" style="display:none !important;"></a>
    </div>
</div>

<style>
/* ── Clickable rows ── */
.price-row-clickable { cursor: pointer; }
.price-row-clickable:hover { background: #fdf0e8 !important; }
.price-row-clickable:hover td:first-child { color: #8B6F5E; font-weight: 600; }
.price-row-hint {
    display: inline-flex; align-items: center; justify-content: center;
    width: 16px; height: 16px; border-radius: 50%;
    background: #f0e4d8; color: #c4a080; font-size: 9px;
    margin-left: 6px; opacity: 0; transition: opacity .2s;
    vertical-align: middle;
}
.price-row-clickable:hover .price-row-hint { opacity: 1; }

/* ── Modal overlay ── */
.price-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(50,35,30,0.55); backdrop-filter: blur(4px);
    align-items: center; justify-content: center; padding: 20px;
}
.price-modal-overlay.open { display: flex; animation: pmFadeIn .2s ease; }
@keyframes pmFadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ── Modal box ── */
.price-modal-box {
    background: #fff; border-radius: 20px; padding: 32px 28px 26px;
    max-width: 400px; width: 100%; position: relative;
    box-shadow: 0 24px 64px rgba(50,35,30,0.22);
    animation: pmSlideUp .25s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes pmSlideUp { from { transform: translateY(20px) scale(.97); opacity:0; } to { transform: none; opacity:1; } }

.price-modal-close {
    position: absolute; top: 14px; right: 16px;
    background: #f5ede6; border: none; border-radius: 50%;
    width: 30px; height: 30px; font-size: 14px; color: #8B6F5E;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.price-modal-close:hover { background: #e8d8cc; }

.price-modal-cat {
    display: inline-block; background: #fdf0e8; color: #a07050;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; border-radius: 50px; padding: 3px 12px;
    margin-bottom: 12px; font-family: 'Poppins', sans-serif;
}
.price-modal-name {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 700; color: #3a2a22;
    line-height: 1.2; margin-bottom: 8px;
}
.price-modal-price {
    font-size: 20px; font-weight: 800; color: #8B6F5E;
    font-family: 'Poppins', sans-serif; margin-bottom: 4px;
}
.price-modal-divider {
    border: none; border-top: 1px solid #f0e8df;
    margin: 16px 0;
}
.price-modal-desc {
    font-size: 13.5px; color: #666; line-height: 1.7;
    font-family: 'Poppins', sans-serif; min-height: 40px;
    margin-bottom: 20px;
}
.price-modal-wa {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; background: linear-gradient(135deg,#25d366,#128c4a);
    color: #fff; font-weight: 700; font-size: 14px; border-radius: 50px;
    padding: 12px; text-decoration: none; font-family: 'Poppins', sans-serif;
    transition: all .2s; box-shadow: 0 4px 16px rgba(37,211,102,0.3);
}
.price-modal-wa:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,211,102,0.4); color:#fff; }
</style>

<script>
// ── Deskripsi otomatis per layanan ──
var priceDescriptions = {
    // Henna
    'Brow Henna': 'Pewarnaan alis dengan henna alami yang tahan lama. Mengisi alis tipis dan memberikan tampilan tegas, natural, dan rapi.',
    'Nail Henna Tangan': 'Motif henna indah di kuku & tangan menggunakan bahan alami. Cocok untuk acara formal maupun casual.',
    'Nail Henna Kaki': 'Desain henna elegan di area kuku dan kaki. Bahan aman, cocok untuk semua usia.',
    'Bundling Meni-Henna': 'Paket hemat manicure lengkap + nail henna. Dua layanan kecantikan dalam satu sesi.',
    'Henna Fun': 'Henna dekoratif di tangan dengan berbagai motif pilihan. Semakin kompleks motif, semakin artistik hasilnya.',
    // Treatment Spa
    'Bundling Manicure & Pedicure': 'Paket lengkap perawatan tangan & kaki: scrub, masker, pemotongan kuku, dan finishing oil.',
    'Manicure / Pedicure': 'Perawatan kuku dan kulit tangan atau kaki dengan teknik profesional. Kulit lebih lembut, kuku lebih sehat.',
    'Hand Spa': 'Perawatan intensif tangan: scrub eksfoliasi, masker pelembap, dan pijat relaksasi. Tangan terasa lembut & cerah.',
    'Foot Spa': 'Terapi kaki lengkap mulai dari perendaman, scrub, masker, hingga pijat refleksi. Cocok setelah hari panjang.',
    'Callus Treatment': 'Pengangkatan kapalan dan kulit keras di telapak kaki secara profesional. Makin tebal kalus, makin intensif perawatannya.',
    // Brow & Lash
    'Brow Bomb': 'Perawatan alis all-in-one: lifting, tinting, dan setting. Alis tampak tebal, tegas, dan terbentuk sempurna tanpa makeup.',
    'Lashlift': 'Keriting bulu mata permanen tanpa sambungan. Mata terlihat lebih besar dan terbuka secara alami hingga 6–8 minggu.',
    'Lashlift Tint': 'Lashlift plus pewarnaan bulu mata agar lebih gelap dan dramatis. Tanpa maskara pun sudah memukau.',
    // Rambut
    'Creambath': 'Perawatan rambut dengan krim nutrisi, pijat kepala, dan uap hangat. Rambut lebih lebat, lembut, dan berkilau.',
    'Hair Mask': 'Masker rambut intensif sesuai jenis rambut. Menutrisi dari dalam, mengurangi frizz, dan mengembalikan kilau alami.',
    'Hair Spa': 'Spa rambut lengkap: shampo, kondisioner, masker, uap, dan pijat. Solusi untuk rambut rusak & kering.',
    'Cuci,Catok,Blow': 'Cuci rambut + blow dry atau catok sesuai selera. Rambut bersih, rapi, dan siap tampil.',
    'Bleaching S': 'Bleaching parsial (highlight/poni) untuk mencerahkan area tertentu. Cocok untuk warna pastel atau ombre.',
    'Coloring Full': 'Pewarnaan rambut penuh dari akar hingga ujung. Pilihan warna beragam, hasil merata dan tahan lama.',
    'Bleaching': 'Bleaching full atau intensif untuk mengangkat pigmen rambut. Harga tergantung panjang dan ketebalan rambut.',
    'Balayage': 'Teknik pewarnaan gradasi tangan bebas yang menghasilkan tampilan natural sun-kissed. Setiap hasil unik dan personal.',
    'Down Peim Poni': 'Pelurus poni dengan teknik perm down. Poni turun rapi tahan lama tanpa perlu di-styling setiap hari.',
    'Keriting Klasik': 'Keriting permanen dengan batang spiral klasik. Cocok untuk tampilan volume dan berkarakter.',
    'Keriting Digital': 'Keriting digital dengan alat pemanas modern. Hasil lebih bergelombang lembut, tahan lama, dan terlihat natural.',
    'Keratin Treatment': 'Perawatan keratin untuk melembutkan dan meluruskan rambut secara alami. Mengurangi frizz & mudah diatur.',
    'Smoothing': 'Pelurusan rambut semi-permanen yang membuat rambut lurus, halus, dan mudah di-styling. Tahan 3–6 bulan.',
    // Nail Art
    'Press On Nail Basic': 'Press on nail siap pakai dengan desain simpel dan elegan. Mudah dipasang sendiri, tahan beberapa hari.',
    'Press On Nail Motif': 'Press on nail dengan motif artistik dan detail lebih kompleks. Cocok untuk event spesial.',
    'Kids Basic Gel': 'Gel kuku aman khusus anak-anak. Warna solid lembut yang tahan lama dan tidak berbau menyengat.',
    'Kids Gel + 4 Sticker': 'Gel warna + 4 stiker kuku pilihan anak. Tampilan lucu dan menggemaskan.',
    'Kids Gel + Full Sticker': 'Gel warna + stiker kuku penuh di semua jari. Seru untuk tampilan spesial si kecil.',
    'Gel Basic Tangan / Kaki': 'Gel warna solid untuk tangan atau kaki dengan hasil rapi dan tahan lama. Cocok untuk tampilan sehari-hari maupun acara spesial.',
    'Extension': 'Perpanjangan kuku menggunakan bahan gel berkualitas. Kuku tampak lebih panjang dan elegan secara instan.',
    'Gel French / Cat Eyes': 'Gel dengan desain French classic atau efek cat eye yang memukau. Hasil bersih, presisi, dan tahan lama.',
    'Remove Gel': 'Pembersihan gel kuku secara aman tanpa merusak kuku asli. Proses cepat dan nyaman menggunakan teknik profesional.',
    'Gel Ombre / Blush On': 'Gradasi warna lembut ombre atau efek blush on di kuku. Tampilan feminin, romantis, dan cocok untuk berbagai kesempatan.',
    'Remove Extension': 'Pelepasan extension kuku secara aman dan menyeluruh. Kuku asli tetap terjaga kesehatannya setelah proses pengangkatan.',
    'Bundling Nail Art + Extension': 'Paket hemat: extension kuku plus nail art desain pilihan. Dua layanan premium dalam satu sesi yang efisien.',
};

function getDesc(name, cat) {
    if (priceDescriptions[name]) return priceDescriptions[name];
    // Fallback generik berdasarkan kategori
    var fallbacks = {
        'Nail Art & Services': 'Layanan nail art profesional dengan bahan berkualitas. Hubungi kami untuk konsultasi desain.',
        'Treatment Spa': 'Perawatan spa premium menggunakan bahan pilihan. Nikmati relaksasi dan hasil optimal.',
        'Henna Series': 'Layanan henna menggunakan bahan alami aman. Cocok untuk berbagai kesempatan.',
        'Brow & Lash': 'Perawatan alis dan bulu mata untuk tampilan lebih percaya diri.',
        'Rambut': 'Perawatan rambut profesional untuk hasil maksimal sesuai kebutuhan kamu.',
    };
    return fallbacks[cat] || 'Hubungi kami untuk informasi lebih lengkap tentang layanan ini.';
}

document.querySelectorAll('.price-row-clickable').forEach(function(row) {
    row.addEventListener('click', function() {
        var name  = this.dataset.name;
        var price = this.dataset.price;
        var desc  = this.dataset.desc;
        var cat   = this.dataset.cat;
        document.getElementById('pmCat').textContent   = cat;
        document.getElementById('pmName').textContent  = name;
        document.getElementById('pmPrice').textContent = price;
        document.getElementById('pmDesc').textContent  = desc || getDesc(name, cat);
        var wa = '<?= $kontak["whatsapp"] ?? "62882006900" ?>';
        var msg = encodeURIComponent('Halo, saya ingin booking layanan *' + name + '* (' + price + '). Apakah tersedia?');
        document.getElementById('pmWa').href = 'https://wa.me/' + wa + '?text=' + msg;
        document.getElementById('priceModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    });
});

function closePriceModal(e) {
    if (e && e.target !== document.getElementById('priceModal')) return;
    document.getElementById('priceModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePriceModal(null);
});
</script>

<style>
.price-list-section { background: linear-gradient(180deg,#fdfaf7 0%,#f5ede4 100%); padding: 45px 0; }
.price-cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }
.price-card { border-radius:20px; overflow:hidden; box-shadow:0 4px 20px rgba(139,111,94,0.12); border:1px solid rgba(214,193,163,0.3); background:#fff; transition:transform .32s cubic-bezier(0.4,0,0.2,1),box-shadow .32s cubic-bezier(0.4,0,0.2,1); }
.price-card:hover { transform:translateY(-6px); box-shadow:0 16px 48px rgba(139,111,94,0.22); }
.price-card-header { display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#8B6F5E,#D6C1A3); padding:16px 20px; }
.price-card-label { font-weight:700; font-size:15px; color:#fff; font-family:'Poppins',sans-serif; }
.price-acc-count { font-size:11px; color:rgba(255,255,255,0.9); background:rgba(255,255,255,0.2); border-radius:20px; padding:3px 12px; font-weight:500; font-family:'Poppins',sans-serif; }
.price-acc-body { border-top:1px solid rgba(139,111,94,0.12); }
.price-table { width:100%; border-collapse:collapse; background:#fff; font-family:'Poppins',sans-serif; font-size:13.5px; }
.price-table thead tr { background:#faf5f0; border-bottom:2px solid #f0e8df; }
.price-table th { padding:10px 20px; color:#8B6F5E; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.price-table tbody tr { border-bottom:1px solid #f5ede6; transition:background .15s; }
.price-table tbody tr:last-child { border-bottom:none; }
.price-table tbody tr:hover { background:#fdf8f4; }
.price-table td { padding:11px 20px; color:#444; vertical-align:middle; }
.price-cell { color:#8B6F5E; font-weight:700; font-size:13px; white-space:nowrap; }
@media(max-width:576px){ .price-cards-grid { grid-template-columns:1fr; } }
</style>

<!-- ══ PRODUK COLLECTION ══ -->
<section id="produk" class="section-product">
    <div class="container">
        <div class="product-section-title" data-aos="fade-up">
            <h2>Press On Nail <span>Collection</span></h2>
            <div class="title-line"></div>
            <p>Press On Nails premium untuk tampil cantik instan <i class="fa-solid fa-sparkles" style="color:#D6C1A3;font-size:0.9em;"></i></p>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons" data-aos="fade-up" data-aos-delay="100">
            <button class="active" data-filter="simple">Simple</button>
            <button data-filter="glam">Glam</button>
            <button data-filter="wedding">Wedding</button>
        </div>

        <div class="product-grid" data-aos="fade-up" data-aos-delay="150">
        <?php foreach ($products as $p):
            $img = esc($p['image'] ?? $p['img'] ?? '');
            $pName = esc($p['name']);
            $pPrice = esc($p['price']);
            $pCat = esc($p['category'] ?? 'simple');
        ?>
            <div class="product-card" data-category="<?= $pCat ?>">
                <div class="product-card-img-wrap">
                    <img src="<?= $img ?>" alt="<?= $pName ?>" class="product-img" loading="lazy">
                    <div class="product-card-overlay">
                        <button class="btn-preview" onclick="showProductPreview('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['image'] ?? $p['img'] ?? '') ?>')">
                            <i class="fas fa-eye"></i> Lihat
                        </button>
                    </div>
                    <span class="product-badge-cat"><?= esc(ucfirst($pCat)) ?></span>
                </div>
                <div class="product-info">
                    <div class="product-name"><?= $pName ?></div>
                    <div class="product-price"><?= $pPrice ?></div>
                    <button class="btn-beli" onclick="handleBeli('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['image'] ?? $p['img'] ?? '') ?>')">
                        <i class="fas fa-shopping-bag me-1"></i> Beli Sekarang
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.section-product { background:linear-gradient(180deg,#f5ede4 0%,#fdfaf7 100%); padding:50px 0; }
.product-section-title { text-align:center; margin-bottom:32px; }
.product-section-title h2 { font-weight:700; font-size:34px; font-family:'Playfair Display',serif; color:#2d1f17; }
.product-section-title span { color:#8B6F5E; }
.product-section-title p { color:#888; margin-top:10px; font-size:15px; }
.filter-buttons { text-align:center; margin-bottom:40px; display:flex; justify-content:center; gap:8px; flex-wrap:wrap; }
.filter-buttons button { border:1.5px solid #e8ddd4; background:#fff; border-radius:50px; padding:8px 20px; font-weight:500; color:#666; cursor:pointer; font-size:14px; font-family:'Poppins',sans-serif; transition:all .25s cubic-bezier(0.4,0,0.2,1); box-shadow:0 2px 8px rgba(139,111,94,0.06); }
.filter-buttons button:hover:not(.active) { background:linear-gradient(135deg,rgba(139,111,94,0.1),rgba(214,193,163,0.15)); color:#8B6F5E; border-color:#D6C1A3; transform:translateY(-2px); box-shadow:0 6px 18px rgba(139,111,94,0.15); }
.filter-buttons button.active { background:linear-gradient(135deg,#8B6F5E,#D6C1A3); color:#fff; border-color:transparent; box-shadow:0 6px 20px rgba(139,111,94,0.35); transform:translateY(-2px); }
.product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }
.product-card { background:#fff; border-radius:20px; overflow:hidden; transition:.3s; }
.product-card:hover { transform:translateY(-8px); box-shadow:0 12px 40px rgba(139,111,94,0.15); }
.product-card-img-wrap { position:relative; overflow:hidden; }
.product-card-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.35); display:flex; align-items:center; justify-content:center; opacity:0; transition:.3s; }
.product-card:hover .product-card-overlay { opacity:1; }
.btn-preview { background:rgba(255,255,255,0.92); color:#5A4A42; border:none; border-radius:50px; padding:9px 22px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transform:translateY(8px); transition:transform .3s,background .2s; box-shadow:0 4px 14px rgba(0,0,0,0.12); }
.product-card:hover .btn-preview { transform:translateY(0); }
.product-badge-cat { position:absolute; top:12px; left:12px; background:rgba(255,255,255,0.88); color:#5A4A42; font-size:10px; font-weight:700; font-family:'Poppins',sans-serif; letter-spacing:.8px; text-transform:uppercase; padding:4px 12px; border-radius:50px; backdrop-filter:blur(6px); border:1px solid rgba(255,255,255,0.6); }
.product-img { width:100%; height:260px; object-fit:cover; }
.product-info { padding:16px; }
.product-name { font-weight:600; font-family:'Poppins',sans-serif; font-size:14px; color:#2d1f17; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.product-price { color:#8B6F5E; font-weight:700; font-size:15px; margin-bottom:10px; }
.btn-beli { width:100%; padding:9px 0; background:linear-gradient(135deg,#8B6F5E,#D6C1A3); color:#fff; border:none; border-radius:12px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:all .25s; box-shadow:0 4px 14px rgba(139,111,94,0.25); }
.btn-beli:hover { box-shadow:0 8px 24px rgba(139,111,94,0.40); transform:translateY(-1px); }
@media(max-width:576px){ .product-grid { grid-template-columns:repeat(2,1fr); gap:14px; } .product-img { height:180px; } }
</style>

<!-- ══ SECTION LOKASI ══ -->
<section id="lokasi" class="location-section">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span>Temukan Kami</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150">Lokasi <span style="color:var(--cream-accent);">Kami</span></h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="250" style="font-size:15px;">Kunjungi kami langsung untuk pengalaman kecantikan terbaik</p>
            </div>
        </div>

        <div class="lokasi-split-wrap" data-aos="fade-up" data-aos-delay="300">
            <!-- Kiri: Maps -->
            <div class="lokasi-map-col">
                <div class="lokasi-map-box">
                    <iframe
                        src="<?= esc($kontak['maps_embed']) ?>"
                        width="100%" height="100%" style="border:0;display:block;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <!-- Kanan: Info -->
            <div class="lokasi-info-col">
                <div class="lokasi-info-box">
                    <div class="lokasi-brand">
                        <i class="fas fa-spa lokasi-brand-icon"></i>
                        <div>
                            <div class="lokasi-brand-name"><?= esc($kontak['salon_name']) ?></div>
                            <div class="lokasi-brand-sub">Premium Beauty Experience</div>
                        </div>
                    </div>

                    <div class="lokasi-divider"></div>

                    <div class="lokasi-detail-list">
                        <div class="lokasi-detail-item">
                            <div class="lokasi-detail-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <div class="lokasi-detail-label">Alamat</div>
                                <div class="lokasi-detail-value"><?= esc($kontak['address']) ?></div>
                            </div>
                        </div>
                        <div class="lokasi-detail-item">
                            <div class="lokasi-detail-icon"><i class="fas fa-clock"></i></div>
                            <div>
                                <div class="lokasi-detail-label">Jam Operasional</div>
                                <div class="lokasi-detail-value"><?= esc($kontak['hours']) ?></div>
                            </div>
                        </div>
                        <div class="lokasi-detail-item">
                            <div class="lokasi-detail-icon"><i class="fab fa-whatsapp"></i></div>
                            <div>
                                <div class="lokasi-detail-label">WhatsApp</div>
                                <div class="lokasi-detail-value">+<?= esc($kontak['whatsapp']) ?></div>
                            </div>
                        </div>
                        <div class="lokasi-detail-item">
                            <div class="lokasi-detail-icon"><i class="fas fa-envelope"></i></div>
                            <div>
                                <div class="lokasi-detail-label">Email</div>
                                <div class="lokasi-detail-value">niswabeauty15@gmail.com</div>
                            </div>
                        </div>
                    </div>

                    <div class="lokasi-divider"></div>

                    <div class="lokasi-actions">
                        <a href="<?= esc($kontak['maps_link']) ?>" target="_blank" class="lokasi-btn-primary">
                            <i class="fas fa-directions"></i> Petunjuk Arah
                        </a>
                        <a href="https://wa.me/<?= esc($kontak['whatsapp']) ?>" target="_blank" class="lokasi-btn-secondary">
                            <i class="fab fa-whatsapp"></i> Chat Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.lokasi-split-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 16px 56px rgba(139,111,94,0.18);
    border: 1px solid #EDE5D8;
    min-height: 460px;
}
.lokasi-map-col { position: relative; min-height: 380px; }
.lokasi-map-box { position: absolute; inset: 0; }
.lokasi-map-box iframe { width: 100%; height: 100%; }

.lokasi-info-col { background: linear-gradient(160deg,#5A4A42 0%,#8B6F5E 100%); }
.lokasi-info-box { padding: 40px 36px; height: 100%; display: flex; flex-direction: column; justify-content: center; }

.lokasi-brand { display: flex; align-items: center; gap: 14px; margin-bottom: 4px; }
.lokasi-brand-icon { font-size: 32px; color: #D6C1A3; flex-shrink: 0; }
.lokasi-brand-name { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: #fff; line-height: 1.2; }
.lokasi-brand-sub { font-size: 12px; color: rgba(255,255,255,0.65); font-family: 'Poppins', sans-serif; letter-spacing: .5px; margin-top: 2px; }

.lokasi-divider { border: none; border-top: 1px solid rgba(255,255,255,0.15); margin: 22px 0; }

.lokasi-detail-list { display: flex; flex-direction: column; gap: 18px; }
.lokasi-detail-item { display: flex; align-items: flex-start; gap: 14px; }
.lokasi-detail-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,0.12);
    display: flex; align-items: center; justify-content: center;
    color: #D6C1A3; font-size: 15px; flex-shrink: 0;
    transition: background .2s;
}
.lokasi-detail-item:hover .lokasi-detail-icon { background: rgba(255,255,255,0.22); }
.lokasi-detail-label { font-size: 10.5px; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: .8px; font-family: 'Poppins', sans-serif; font-weight: 600; margin-bottom: 2px; }
.lokasi-detail-value { font-size: 13.5px; color: #fff; font-family: 'Poppins', sans-serif; font-weight: 500; line-height: 1.4; }

.lokasi-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.lokasi-btn-primary {
    flex: 1; min-width: 130px; text-align: center;
    background: linear-gradient(135deg,#D6C1A3,#c4a882);
    color: #3a2e28; border: none; border-radius: 50px;
    padding: 11px 20px; font-size: 13px; font-weight: 700;
    font-family: 'Poppins', sans-serif; text-decoration: none;
    transition: all .25s; box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.lokasi-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.22); color: #3a2e28; }
.lokasi-btn-secondary {
    flex: 1; min-width: 130px; text-align: center;
    background: rgba(255,255,255,0.12); color: #fff;
    border: 1.5px solid rgba(255,255,255,0.3); border-radius: 50px;
    padding: 11px 20px; font-size: 13px; font-weight: 600;
    font-family: 'Poppins', sans-serif; text-decoration: none;
    transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.lokasi-btn-secondary:hover { background: rgba(255,255,255,0.22); color: #fff; transform: translateY(-2px); }

@media (max-width: 768px) {
    .lokasi-split-wrap { grid-template-columns: 1fr; }
    .lokasi-map-col { min-height: 280px; position: relative; }
    .lokasi-map-box { position: relative; height: 280px; }
    .lokasi-info-box { padding: 28px 22px; }
}
</style>

<!-- ══ SECTION TESTIMONI ══ -->
<section id="testimoni" class="testimoni-section py-5">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span>Kata Mereka</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150">Testimoni <span style="color:var(--cream-accent);">Pelanggan</span></h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="250" style="font-size:15px;">Kepercayaan pelanggan adalah kebanggaan kami</p>
                <div class="testimoni-rating-badge" data-aos="fade-up" data-aos-delay="350">
                    <div class="testimoni-score">5.0</div>
                    <div class="testimoni-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <div class="testimoni-rating-label">Berdasarkan ulasan Google Maps</div>
                    <a href="<?= esc($kontak['maps_link']) ?>" target="_blank" class="review-write-btn ms-3">
                        <i class="fab fa-google"></i> Tulis Ulasan
                    </a>
                </div>
            </div>
        </div>

        <div class="testimoni-carousel-wrapper" data-aos="fade-up" data-aos-delay="200">
            <div class="testimoni-track" id="testimoniTrack">
                <?php foreach ($testimonials as $t):
                    $firstLetter = mb_strtoupper(mb_substr($t['name'], 0, 1, 'UTF-8'), 'UTF-8');
                    $color = $t['avatar_color'] ?? 'linear-gradient(135deg,#f9a8d4,#f472b6)';
                ?>
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"<?= esc($t['text']) ?>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:<?= esc($color) ?>;"><?= $firstLetter ?></div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name"><?= esc($t['name']) ?></div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag"><?= esc($t['service_tag']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="testimoni-nav testimoni-prev" id="testimoniPrev" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>
            <button class="testimoni-nav testimoni-next" id="testimoniNext" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="testimoni-dots" id="testimoniDots"></div>
    </div>
</section>


<?php include 'footer.php'; ?>
<button id="backToTop"><i class="fas fa-chevron-up"></i></button>

<!-- ══ PRODUCT PREVIEW MODAL ══ -->
<div id="productPreviewModal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;" onclick="if(event.target===this)closeProductPreview()">
    <div style="background:#fff;border-radius:24px;width:92%;max-width:380px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.25);animation:ppFadeIn .25s ease;">
        <div style="position:relative;">
            <img id="ppImg" src="" alt="" style="width:100%;height:280px;object-fit:cover;display:block;">
            <button onclick="closeProductPreview()" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.45);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="padding:20px 22px 22px;">
            <div id="ppName" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:16px;color:#2d1f17;margin-bottom:4px;"></div>
            <div id="ppPrice" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;color:#8B6F5E;margin-bottom:18px;"></div>
            <button id="ppBeli" style="width:100%;padding:12px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;font-family:'Poppins',sans-serif;cursor:pointer;">
                <i class="fas fa-shopping-bag me-2"></i>Beli Sekarang
            </button>
        </div>
    </div>
</div>
<style>@keyframes ppFadeIn{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}</style>

<!-- ══ ORDER MODAL ══ -->
<div id="orderModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:24px;width:100%;max-width:420px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.25);">
        <div style="padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0e8df;padding-bottom:16px;margin-bottom:20px;">
            <div>
                <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#8B6F5E;font-weight:600;margin-bottom:4px;">Pemesanan</div>
                <h5 id="modalProductName" style="font-weight:700;font-size:16px;color:#2d1f17;margin:0;font-family:'Poppins',sans-serif;"></h5>
            </div>
            <button onclick="closeOrder()" style="background:none;border:none;font-size:22px;color:#999;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="padding:0 24px 24px;">
            <div style="display:flex;align-items:center;gap:14px;background:#faf5f0;border-radius:14px;padding:14px;margin-bottom:18px;">
                <img id="modalProductImg" src="" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:10px;">
                <div>
                    <div id="modalProductNameInner" style="font-weight:600;font-size:14px;color:#2d1f17;font-family:'Poppins',sans-serif;"></div>
                    <div id="modalProductPrice" style="color:#8B6F5E;font-weight:700;font-size:15px;font-family:'Poppins',sans-serif;"></div>
                </div>
            </div>
            <form method="POST" id="orderForm">
                <input type="hidden" name="order" value="1">
                <input type="hidden" name="product_name" id="inputProductName">
                <input type="hidden" name="product_price" id="inputProductPrice">
                <div id="orderError" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;margin-bottom:14px;"></div>
                <?php
                $fields = [
                    ['label'=>'Nama Lengkap','name'=>'nama','type'=>'text','req'=>true,'placeholder'=>'Nama Anda'],
                    ['label'=>'Nomor WhatsApp','name'=>'whatsapp','type'=>'tel','req'=>true,'placeholder'=>'08xxxxxxxxxx'],
                ];
                foreach ($fields as $f): ?>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;"><?= $f['label'] ?></label>
                    <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" <?= $f['req']?'required':'' ?> placeholder="<?= $f['placeholder'] ?>"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;">
                </div>
                <?php endforeach; ?>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Alamat Pengiriman</label>
                    <textarea name="alamat" required placeholder="Alamat lengkap..." rows="2"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;resize:none;"></textarea>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Jumlah</label>
                    <input type="number" name="qty" min="1" max="10" value="1"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;">
                </div>
                <button type="submit"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;font-family:Poppins,sans-serif;cursor:pointer;">
                    <i class="fas fa-shopping-bag me-2"></i>Konfirmasi Pesanan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ══ SUCCESS ORDER ══ -->
<div id="successOrder" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:90%;max-width:360px;padding:32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <i class="fas fa-check-circle" style="font-size:56px;color:#10b981;margin-bottom:16px;display:block;"></i>
        <h5 style="font-weight:700;margin-bottom:8px;">Pesanan Berhasil!</h5>
        <p style="color:#666;font-size:13px;margin-bottom:20px;">Terima kasih!</p>
        <button onclick="document.getElementById('successOrder').style.display='none'"
            style="background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:30px;padding:10px 28px;font-weight:600;font-family:Poppins,sans-serif;cursor:pointer;">
            Tutup
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js?v=5.0"></script>
<script>AOS.init();</script>
<script src="script.js"></script>

<!-- Hero Carousel -->
<script>
(function(){
    function startCarousel(){
        var el=document.getElementById('heroCarousel');if(!el)return;
        var old=bootstrap.Carousel.getInstance(el);if(old)old.dispose();
        var carousel=new bootstrap.Carousel(el,{interval:3000,ride:'carousel',wrap:true,pause:false});
        carousel.cycle();
        document.addEventListener('visibilitychange',function(){if(!document.hidden)carousel.cycle();});
        window.addEventListener('focus',function(){carousel.cycle();});
    }
    if(document.readyState==='complete'){startCarousel();}else{window.addEventListener('load',startCarousel);}
})();
</script>

<!-- Product Filter -->
<script>
(function(){
    // Terapkan filter awal: tampilkan hanya "simple" saat load
    document.querySelectorAll(".product-card").forEach(function(card){
        card.style.display = card.dataset.category === "simple" ? "" : "none";
    });

    document.querySelectorAll(".filter-buttons button").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.querySelectorAll(".filter-buttons button").forEach(function(b){ b.classList.remove("active"); });
            btn.classList.add("active");
            var filter = btn.dataset.filter;
            document.querySelectorAll(".product-card").forEach(function(card){
                var show = card.dataset.category === filter;
                card.style.display = show ? "" : "none";
                if(show) card.style.animation = "cardFadeIn 0.3s ease forwards";
            });
        });
    });
})();
</script>
<style>
@keyframes cardFadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
</style>

<!-- Order Modal Script -->
<script>
function handleBeli(name,price,img){
    document.getElementById('modalProductName').textContent=name;
    document.getElementById('modalProductNameInner').textContent=name;
    document.getElementById('modalProductPrice').textContent=price;
    document.getElementById('modalProductImg').src=img;
    document.getElementById('orderForm').reset();
    document.getElementById('inputProductName').value=name;
    document.getElementById('inputProductPrice').value=price;
    document.getElementById('orderError').style.display='none';
    document.getElementById('orderModal').style.display='flex';
}
function closeOrder(){document.getElementById('orderModal').style.display='none';}
document.getElementById('orderModal').addEventListener('click',function(e){if(e.target===this)closeOrder();});

document.getElementById('orderForm').addEventListener('submit',function(e){
    e.preventDefault();
    var errBox=document.getElementById('orderError');errBox.style.display='none';
    fetch(window.location.href,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
    .then(res=>res.json())
    .then(data=>{
        if(data.success){closeOrder();document.getElementById('successOrder').style.display='flex';this.reset();}
        else{errBox.innerHTML=data.message;errBox.style.display='block';}
    })
    .catch(()=>{errBox.innerHTML='Terjadi kesalahan. Silakan coba lagi.';errBox.style.display='block';});
});

function showProductPreview(name,price,img){
    document.getElementById('ppImg').src=img;
    document.getElementById('ppName').textContent=name;
    document.getElementById('ppPrice').textContent=price;
    document.getElementById('ppBeli').onclick=function(){closeProductPreview();handleBeli(name,price,img);};
    document.getElementById('productPreviewModal').style.display='flex';
}
function closeProductPreview(){document.getElementById('productPreviewModal').style.display='none';}
</script>

<!-- Testimoni Carousel -->
<script>
(function(){
    var track=document.getElementById('testimoniTrack');
    var prevBtn=document.getElementById('testimoniPrev');
    var nextBtn=document.getElementById('testimoniNext');
    var dotsWrap=document.getElementById('testimoniDots');
    if(!track)return;
    var cards=Array.from(track.querySelectorAll('.testimoni-card'));
    var current=0;var autoTimer=null;
    function getPerView(){return window.innerWidth>=992?3:window.innerWidth>=600?2:1;}
    var perView=getPerView();
    var totalSlides=Math.max(1,cards.length-perView+1);
    dotsWrap.innerHTML='';
    for(var i=0;i<totalSlides;i++){
        var dot=document.createElement('span');
        dot.className='testimoni-dot'+(i===0?' active':'');
        dot.dataset.i=i;
        dot.addEventListener('click',function(){goTo(+this.dataset.i);});
        dotsWrap.appendChild(dot);
    }
    function goTo(idx){
        current=Math.max(0,Math.min(idx,totalSlides-1));
        var pct=current*(100/perView);
        track.style.transform='translateX(-'+pct+'%)';
        dotsWrap.querySelectorAll('.testimoni-dot').forEach(function(d,i){d.classList.toggle('active',i===current);});
        cards.forEach(function(c,i){c.classList.toggle('is-active',i>=current&&i<current+perView);});
    }
    function startAuto(){autoTimer=setInterval(function(){goTo(current+1<totalSlides?current+1:0);},4000);}
    function stopAuto(){clearInterval(autoTimer);}
    if(prevBtn)prevBtn.addEventListener('click',function(){stopAuto();goTo(current>0?current-1:totalSlides-1);startAuto();});
    if(nextBtn)nextBtn.addEventListener('click',function(){stopAuto();goTo(current+1<totalSlides?current+1:0);startAuto();});
    goTo(0);startAuto();
    window.addEventListener('resize',function(){perView=getPerView();totalSlides=Math.max(1,cards.length-perView+1);goTo(0);});
})();
</script>

</body>
</html>