<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$isLoggedIn = isset($_SESSION['user']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin');
$userName   = $_SESSION['user'] ?? '';

/* ══════════════════════════════════════════════
   DATABASE CONNECTION
══════════════════════════════════════════════ */
require_once __DIR__ . '/db.php';
if ($conn) {

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
        payment_method VARCHAR(30) DEFAULT 'COD',
        payment_status VARCHAR(20) DEFAULT 'pending',
        order_status VARCHAR(30) DEFAULT 'menunggu_konfirmasi',
        created_at DATETIME DEFAULT NOW()
    )");
    // AUTO-FIX kolom
    foreach ([
        "user_id"        => "ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL",
        "catatan"        => "ALTER TABLE orders ADD COLUMN catatan TEXT",
        "total"          => "ALTER TABLE orders ADD COLUMN total VARCHAR(20)",
        "payment_method" => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(30) DEFAULT 'COD'",
        "payment_status" => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending'",
        "order_status"   => "ALTER TABLE orders ADD COLUMN order_status VARCHAR(30) DEFAULT 'menunggu_konfirmasi'",
    ] as $col=>$sql) {
        $cek = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$col'");
        if ($cek && mysqli_num_rows($cek) === 0) mysqli_query($conn, $sql);
    }
    // Tabel diskon global (auto-create dan seed)
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_global_discount (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enabled TINYINT(1) DEFAULT 0,
        discount_pct TINYINT UNSIGNED DEFAULT 0,
        min_purchase INT UNSIGNED DEFAULT 0,
        label VARCHAR(200) DEFAULT '',
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cntGd = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_global_discount"))['c'] ?? 0);
    if ($cntGd === 0) {
        mysqli_query($conn, "INSERT INTO cms_global_discount (enabled,discount_pct,min_purchase,label) VALUES (0,25,25000,'Beli min. Rp 25.000, hemat 25%!')");
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
function lowestPrice($s) {
    preg_match_all('/[\d.]+/', $s, $m);
    $n = array_map(fn($x) => (int)str_replace('.','', $x), $m[0]);
    return $n ? min($n) : 0;
}

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

// ── Section Titles (bisa diedit dari CMS) ──
$sec = [
    // Layanan
    'layanan_title'    => getContent($conn,'section','layanan_title',   'Layanan Kami'),
    'layanan_subtitle' => getContent($conn,'section','layanan_subtitle','Klik layanan untuk melihat contoh hasil'),
    // Harga
    'harga_title'      => getContent($conn,'section','harga_title',    'Daftar Harga'),
    'harga_subtitle'   => getContent($conn,'section','harga_subtitle', 'Harga transparan, kualitas terjamin (Harga tergantung panjang rambut)'),
    // Produk
    'produk_title'     => getContent($conn,'section','produk_title',   'Press On Nail Collection'),
    'produk_subtitle'  => getContent($conn,'section','produk_subtitle','Press On Nails premium untuk tampil cantik instan'),
    // Testimoni
    'testi_label'         => getContent($conn,'section','testi_label',         'Kata Mereka'),
    'testi_title'         => getContent($conn,'section','testi_title',         'Testimoni Pelanggan'),
    'testi_subtitle'      => getContent($conn,'section','testi_subtitle',      'Kepercayaan pelanggan adalah kebanggaan kami'),
    'testi_rating'        => getContent($conn,'section','testi_rating',        '5.0'),
    'testi_rating_label'  => getContent($conn,'section','testi_rating_label',  'Berdasarkan ulasan Google Maps'),
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
            'image' => $pr['image'] ?? '',
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
// Pakai DB hanya jika ada produk DAN punya kolom category yang terisi
$dbHasCategory = !empty($productsRows) && !empty(array_filter(array_column($productsRows, 'category')));
$products = $dbHasCategory ? $productsRows : $defaultProducts;

// ── Testimoni ──
$testiRows = $conn ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM cms_testimonials ORDER BY sort_order, id"), MYSQLI_ASSOC) : [];

// ── Diskon Global ──
$gdRow = $conn ? mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM cms_global_discount WHERE id=1 LIMIT 1"
) ?: false) : null;
$globalDisc = [
    'enabled'      => (int)($gdRow['enabled']      ?? 0),
    'discount_pct' => (int)($gdRow['discount_pct'] ?? 0),
    'min_purchase' => (int)($gdRow['min_purchase'] ?? 0),
    'label'        => $gdRow['label'] ?? '',
];
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
// Load helper notifikasi WhatsApp
if (file_exists(__DIR__ . '/notify.php')) {
    require_once __DIR__ . '/notify.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $nama           = trim($_POST['nama']           ?? '');
    $whatsapp       = trim($_POST['whatsapp']       ?? '');
    $alamat         = trim($_POST['alamat']         ?? '');
    $product_name   = trim($_POST['product_name']   ?? '');
    $product_price  = trim($_POST['product_price']  ?? '');
    $qty            = max(1, (int)($_POST['qty']    ?? 1));
    $catatan        = trim($_POST['catatan']        ?? '');
    $product_image  = trim($_POST['product_image']  ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'COD');
    $user_id        = $_SESSION['user_id']          ?? null;
    $disc_pct       = trim($_POST['disc_pct']       ?? '');
    $disc_amt       = trim($_POST['disc_amt']       ?? '');
    $ori_price_raw  = trim($_POST['ori_price']      ?? '');

    // Hanya izinkan COD
    if (!in_array($payment_method, ['COD'])) {
        $payment_method = 'COD';
    }

    $harga_num     = (int) preg_replace('/[^0-9]/', '', $product_price); // harga after-diskon
    $ori_price_num = !empty($ori_price_raw)
                     ? (int) preg_replace('/[^0-9]/', '', $ori_price_raw) // harga asli sebelum diskon
                     : $harga_num;
    $ongkir        = 5000;
    $is_cart       = !empty($_POST['is_cart']) && $_POST['is_cart'] == '1';

    // Subtotal after-diskon (dipakai untuk hitung total bayar)
    $subtotal      = $is_cart ? $harga_num : ($harga_num * $qty);
    // Subtotal harga asli sebelum diskon (untuk baris "Subtotal" di notifikasi)
    $subtotal_ori  = $is_cart ? $ori_price_num : ($ori_price_num * $qty);
    $total         = 'Rp ' . number_format($subtotal + $ongkir, 0, ',', '.');
    $subtotal_ori_fmt = 'Rp ' . number_format($subtotal_ori, 0, ',', '.');
    $subtotal_fmt  = 'Rp ' . number_format($subtotal_ori, 0, ',', '.'); // tampilkan harga asli di notif

    $errors = [];
    if (empty($nama))     $errors[] = 'Nama wajib diisi.';
    if (empty($whatsapp)) $errors[] = 'WhatsApp wajib diisi.';
    if (empty($alamat))   $errors[] = 'Alamat wajib diisi.';

    if (!empty($errors)) {
        echo json_encode(['success'=>false,'message'=>implode('<br>', $errors)]);
    } elseif (!$conn) {
        echo json_encode(['success'=>false,'message'=>'Koneksi database gagal: '.mysqli_connect_error()]);
    } else {
        $order_status  = 'menunggu_konfirmasi';
        $payment_status = 'pending';

        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (user_id,nama,whatsapp,alamat,product_name,product_price,qty,total,catatan,product_image,payment_method,payment_status,order_status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$stmt) {
            echo json_encode(['success'=>false,'message'=>'Prepare gagal: '.mysqli_error($conn)]);
            exit;
        }
        mysqli_stmt_bind_param($stmt, "isssssissssss",
            $user_id,$nama,$whatsapp,$alamat,$product_name,$product_price,$qty,$total,$catatan,$product_image,$payment_method,$payment_status,$order_status
        );
        if (mysqli_stmt_execute($stmt)) {
            $order_id  = mysqli_insert_id($conn);
            $wa_number = $kontak['whatsapp'];

            // ── Kirim notifikasi WA ke admin ──
            if (function_exists('notifyAdminOrder')) {
                notifyAdminOrder([
                    'order_id'       => $order_id,
                    'nama'           => $nama,
                    'whatsapp'       => $whatsapp,
                    'alamat'         => $alamat,
                    'product_name'   => $product_name,
                    'product_price'  => $subtotal_fmt,
                    'qty'            => $qty,
                    'total'          => $total,
                    'catatan'        => $catatan,
                    'payment_method' => $payment_method,
                    'is_cart'        => $is_cart,
                    'disc_pct'       => $disc_pct,
                    'disc_amt'       => $disc_amt,
                ]);
            }

            // ── Kirim konfirmasi otomatis ke customer ──
            if (function_exists('notifyCustomerOrder')) {
                notifyCustomerOrder([
                    'order_id'       => $order_id,
                    'nama'           => $nama,
                    'whatsapp'       => $whatsapp,
                    'alamat'         => $alamat,
                    'product_name'   => $product_name,
                    'product_price'  => $subtotal_fmt,
                    'qty'            => $qty,
                    'total'          => $total,
                    'catatan'        => $catatan,
                    'payment_method' => $payment_method,
                    'is_cart'        => $is_cart,
                    'disc_pct'       => $disc_pct,
                    'disc_amt'       => $disc_amt,
                ]);
            }

            echo json_encode([
                'success'    => true,
                'order_id'   => $order_id,
                'payment'    => $payment_method,
                'wa_number'  => $wa_number,
                'nama'       => $nama,
                'product'    => $product_name,
                'total'      => $total,
                'alamat'     => $alamat,
                'whatsapp'   => $whatsapp,
            ]);
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
/* ══ CART CSS — Shopee Style ══ */
#cart-fab{position:fixed;bottom:28px;right:28px;z-index:1200;width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 6px 24px rgba(139,111,94,0.45);transition:transform .25s,box-shadow .25s;}
#cart-fab:hover{transform:translateY(-3px) scale(1.06);box-shadow:0 12px 32px rgba(139,111,94,0.5);}
#cart-badge{position:absolute;top:-4px;right:-4px;background:#ee4d2d;color:#fff;font-size:10px;font-weight:700;font-family:'Poppins',sans-serif;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;border:2px solid #fff;line-height:1;padding:0 3px;}
#cart-overlay{display:none;position:fixed;inset:0;background:rgba(50,35,30,0.5);backdrop-filter:blur(3px);z-index:99998;opacity:0;transition:opacity .3s;}
#cart-overlay.open{display:block;opacity:1;}
#cart-sidebar{position:fixed;top:0;right:0;width:420px;max-width:100vw;height:100%;background:#f5f5f5;z-index:99999;display:flex;flex-direction:column;transform:translateX(110%);transition:transform .35s cubic-bezier(0.4,0,0.2,1);box-shadow:-8px 0 40px rgba(50,35,30,0.18);}
#cart-sidebar.open{transform:translateX(0);}

/* Cart Header */
.cart-header{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid #f0e8df;background:#fff;flex-shrink:0;}
.cart-header-title{font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:#3a2a22;display:flex;align-items:center;gap:8px;}
.cart-header-title i{color:#8B6F5E;}
.cart-header-count{font-size:12px;color:#999;font-weight:400;margin-left:4px;}
.cart-header-actions{display:flex;align-items:center;gap:8px;}
#cart-close-btn{background:#f5ede6;border:none;border-radius:50%;width:32px;height:32px;font-size:16px;color:#8B6F5E;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;}
#cart-close-btn:hover{background:#e8d8cc;}

/* Select-all bar */
.cart-select-bar{display:flex;align-items:center;gap:10px;padding:10px 18px;background:#fff;border-bottom:1px solid #f0e8df;flex-shrink:0;}
.cart-select-bar label{font-family:'Poppins',sans-serif;font-size:13px;color:#555;cursor:pointer;display:flex;align-items:center;gap:8px;user-select:none;}
.cart-select-bar input[type=checkbox]{width:17px;height:17px;accent-color:#8B6F5E;cursor:pointer;}
#cart-clear-btn{margin-left:auto;background:none;border:none;color:#bbb;font-size:11px;font-family:'Poppins',sans-serif;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .2s;}
#cart-clear-btn:hover{background:#fdf0e8;color:#e74c3c;}

/* Cart Body */
.cart-body{flex:1;overflow-y:auto;padding:10px;}
.cart-body::-webkit-scrollbar{width:4px;}
.cart-body::-webkit-scrollbar-track{background:#f5f5f5;}
.cart-body::-webkit-scrollbar-thumb{background:#D6C1A3;border-radius:4px;}

/* Empty state */
#cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;color:#ccc;font-family:'Poppins',sans-serif;gap:12px;}
#cart-empty i{font-size:56px;color:#e8d8cc;}
#cart-empty p{font-size:14px;color:#bbb;margin:0;}
#cart-empty small{font-size:11px;color:#ccc;}

/* Cart Item — Shopee card style */
.cart-item{display:flex;align-items:flex-start;gap:12px;padding:14px;background:#fff;border-radius:12px;margin-bottom:8px;animation:cartItemIn .25s ease;border:1px solid #f0e8df;}
@keyframes cartItemIn{from{opacity:0;transform:translateX(12px);}to{opacity:1;transform:none;}}
.cart-item-check{flex-shrink:0;margin-top:4px;}
.cart-item-check input[type=checkbox]{width:17px;height:17px;accent-color:#8B6F5E;cursor:pointer;}
.cart-item-img{width:72px;height:72px;object-fit:cover;border-radius:10px;flex-shrink:0;border:1px solid #f0e8df;}
.cart-item-img-placeholder{background:linear-gradient(135deg,#fdf0e8,#f5e6d8);display:flex;align-items:center;justify-content:center;color:#D6C1A3;font-size:26px;}
.cart-item-info{flex:1;min-width:0;}
.cart-item-name{font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;color:#2d1f17;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.cart-item-type{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#bbb;font-family:'Poppins',sans-serif;margin:3px 0 6px;background:#f5ede6;display:inline-block;padding:2px 8px;border-radius:20px;}
.cart-item-price{font-size:14px;font-weight:800;color:#ee4d2d;font-family:'Poppins',sans-serif;}
.cart-item-subtotal{font-size:11px;color:#aaa;font-family:'Poppins',sans-serif;margin-top:1px;}

/* Qty controls */
.cart-item-bottom{display:flex;align-items:center;justify-content:space-between;margin-top:8px;}
.cart-qty-wrap{display:flex;align-items:center;border:1px solid #e8ddd4;border-radius:8px;overflow:hidden;}
.cart-qty-btn{width:30px;height:30px;border:none;background:#fff;color:#8B6F5E;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;line-height:1;}
.cart-qty-btn:hover{background:#fdf0e8;}
.cart-qty-val{font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;color:#3a2a22;min-width:34px;text-align:center;border-left:1px solid #f0e8df;border-right:1px solid #f0e8df;height:30px;display:flex;align-items:center;justify-content:center;}
.cart-remove-btn{background:none;border:none;color:#ccc;cursor:pointer;font-size:12px;padding:5px 8px;transition:color .2s;font-family:'Poppins',sans-serif;display:flex;align-items:center;gap:4px;}
.cart-remove-btn:hover{color:#e74c3c;}

/* Cart Footer */
#cart-footer{flex-shrink:0;border-top:1px solid #e8ddd4;background:#fff;display:none;}
.cart-summary-box{padding:12px 18px;background:#fff8f5;border-bottom:1px solid #f0e8df;}
.cart-summary-row{display:flex;justify-content:space-between;align-items:center;font-family:'Poppins',sans-serif;font-size:13px;color:#666;margin-bottom:4px;}
.cart-summary-row:last-child{margin-bottom:0;}
.cart-summary-row strong{color:#2d1f17;font-size:14px;}
.cart-selected-info{font-size:11px;color:#8B6F5E;font-weight:600;}
.cart-total-row{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-top:1px solid #f0e8df;}
.cart-total-label{font-family:'Poppins',sans-serif;font-size:13px;color:#888;font-weight:500;}
#cart-total{font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;color:#8B6F5E;}
.cart-checkout-wrap{padding:12px 18px 18px;}
#cart-checkout-btn{width:100%;padding:13px 0;background:linear-gradient(135deg,#CBB89D,#D6C1A3);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:'Poppins',sans-serif;cursor:pointer;transition:all .25s;box-shadow:0 6px 20px rgba(203,184,157,0.4);display:flex;align-items:center;justify-content:center;gap:8px;}
#cart-checkout-btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(203,184,157,0.5);background:linear-gradient(135deg,#b8a082,#CBB89D);}
#cart-checkout-btn:disabled{background:#ccc;box-shadow:none;cursor:not-allowed;transform:none;}
.cart-note{text-align:center;font-size:11px;color:#bbb;margin-top:8px;font-family:'Poppins',sans-serif;}

/* Toast */
.cart-toast{position:fixed;bottom:100px;right:28px;z-index:9999;background:#3a2a22;color:#fff;padding:11px 18px;border-radius:50px;font-size:13px;font-family:'Poppins',sans-serif;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.25);opacity:0;transform:translateY(16px);transition:opacity .3s,transform .3s;white-space:nowrap;display:flex;align-items:center;gap:8px;}
.cart-toast.show{opacity:1;transform:translateY(0);}
.cart-toast .toast-img{width:28px;height:28px;border-radius:6px;object-fit:cover;}

/* Product buttons */
.btn-add-cart{position:relative;overflow:hidden;width:100%;padding:8px 0;background:#fff;color:#8B6F5E;border:2px solid #D6C1A3;border-radius:12px;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:color .25s,border-color .25s,box-shadow .25s,transform .2s;display:flex;align-items:center;justify-content:center;gap:6px;margin-top:5px;}
.btn-add-cart::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);opacity:0;transition:opacity .25s;border-radius:10px;z-index:-1;}
.btn-add-cart:hover::before{opacity:1;}
.btn-add-cart:hover{color:#fff;border-color:transparent;box-shadow:0 6px 18px rgba(139,111,94,0.35);transform:translateY(-1px);}
.btn-add-cart:active{transform:translateY(0) scale(0.97);box-shadow:0 2px 8px rgba(139,111,94,0.2);}
.btn-cart-price{display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:50px;padding:4px 11px;font-size:10.5px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:all .2s;box-shadow:0 3px 10px rgba(139,111,94,0.2);margin-top:5px;white-space:nowrap;}
.btn-cart-price:hover{transform:translateY(-2px) scale(1.04);box-shadow:0 7px 18px rgba(139,111,94,0.4);}
.btn-cart-price:active{transform:translateY(0) scale(0.97);box-shadow:0 2px 8px rgba(139,111,94,0.2);}

@media(max-width:480px){#cart-sidebar{width:100vw;}#cart-fab{bottom:20px;right:18px;}.cart-toast{right:16px;bottom:90px;font-size:12px;max-width:calc(100vw - 32px);white-space:normal;}}
    </style>
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
        /* product styles defined below in section-product block */
    </style>
</head>
<body>

<!-- ══ CART FAB ══ -->
<button id="cart-fab" title="Keranjang Belanja" aria-label="Buka keranjang">
    <i class="fas fa-shopping-cart"></i>
    <span id="cart-badge">0</span>
</button>
<!-- ══ CART OVERLAY ══ -->
<div id="cart-overlay"></div>
<!-- ══ CART SIDEBAR ══ -->
<div id="cart-sidebar" role="dialog" aria-modal="true" aria-label="Keranjang Belanja">
    <div class="cart-header">
        <div class="cart-header-title">
            <i class="fas fa-shopping-cart"></i> Keranjang
            <span class="cart-header-count" id="cart-count-label"></span>
        </div>
        <div class="cart-header-actions">
            <button id="cart-close-btn" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <!-- Select All bar -->
    <div class="cart-select-bar" id="cart-select-bar" style="display:none;">
        <label>
            <input type="checkbox" id="cart-select-all" onchange="NiswaCart.toggleSelectAll(this.checked)">
            Pilih Semua
        </label>
        <button id="cart-clear-btn"><i class="fas fa-trash-alt"></i> Hapus Dipilih</button>
    </div>
    <div class="cart-body">
        <div id="cart-empty">
            <i class="fas fa-shopping-cart"></i>
            <p>Keranjang masih kosong</p>
            <small>Tambahkan produk atau layanan favorit kamu!</small>
        </div>
        <div id="cart-item-list"></div>
    </div>
    <div id="cart-footer">
        <div class="cart-summary-box">
            <div class="cart-summary-row">
                <span class="cart-selected-info" id="cart-selected-info">0 item dipilih</span>
                <span id="cart-selected-subtotal" style="font-family:'Poppins',sans-serif;font-size:13px;color:#8B6F5E;font-weight:700;"></span>
            </div>
        </div>
        <!-- Promo Global Discount notice -->
        <div id="cart-gd-promo" style="display:none;margin:0 14px 8px;padding:8px 12px;border-radius:10px;border:1.5px solid #f5dbb5;font-family:'Poppins',sans-serif;font-size:11.5px;line-height:1.5;"></div>
        <div class="cart-total-row">
            <span class="cart-total-label">Total Pembayaran</span>
            <span id="cart-total">Rp 0</span>
        </div>
        <div class="cart-checkout-wrap">
            <button id="cart-checkout-btn" disabled>
                <i class="fas fa-credit-card"></i> Beli (<span id="cart-checkout-count">0</span>)
            </button>
            <p class="cart-note"><i class="fas fa-lock me-1"></i> Pesanan dikonfirmasi via WhatsApp</p>
        </div>
    </div>
</div>

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
<style>
/* ── Service Card (estetik seperti produk) ── */
.services-clean { background: #fdfaf7 !important; padding: 50px 0; }

.svc-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform .3s cubic-bezier(0.4,0,0.2,1), box-shadow .3s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 0 4px 20px rgba(139,111,94,0.10);
    border: 1px solid rgba(214,193,163,0.25);
}
.svc-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(139,111,94,0.20);
}

/* Bagian gambar */
.svc-card-img-wrap {
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.svc-card-img-wrap img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    transition: transform .45s ease;
}
.svc-card:hover .svc-card-img-wrap img {
    transform: scale(1.06);
}

/* Badge nama layanan (pojok kiri atas) */
.svc-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255,255,255,0.88);
    color: #5A4A42;
    font-size: 10px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    letter-spacing: .7px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 50px;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.6);
    z-index: 2;
}

/* Badge jumlah foto (pojok kanan atas) */
.svc-photo-count {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(139,111,94,0.82);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    padding: 4px 10px;
    border-radius: 50px;
    backdrop-filter: blur(6px);
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Hover overlay + tombol lihat */
.svc-card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity .3s;
    z-index: 3;
}
.svc-card:hover .svc-card-overlay { opacity: 1; }
.svc-btn-preview {
    background: rgba(255,255,255,0.92);
    color: #5A4A42;
    border: none;
    border-radius: 50px;
    padding: 9px 22px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transform: translateY(8px);
    transition: transform .3s, background .2s;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    gap: 6px;
}
.svc-card:hover .svc-btn-preview { transform: translateY(0); }

/* Bagian info bawah */
.svc-card-info {
    padding: 16px 18px 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.svc-card-name {
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #2d1f17;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.svc-card-sub {
    font-size: 11.5px;
    color: #aaa;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    gap: 5px;
}
.svc-card-divider {
    border: none;
    border-top: 1px solid #f0ebe5;
    margin: 12px 0 10px;
}
.svc-card-cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.svc-cta-label {
    font-size: 11.5px;
    color: #8B6F5E;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.svc-cta-arrow {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg,#8B6F5E,#D6C1A3);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    transition: transform .25s;
}
.svc-card:hover .svc-cta-arrow { transform: translateX(3px); }

@media (max-width: 576px) {
    .svc-card-img-wrap img { height: 180px; }
}
</style>

<section id="layanan" class="services-clean">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="services-title"><?= esc($sec['layanan_title']) ?></h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2" style="font-size:14px;"><?= esc($sec['layanan_subtitle']) ?></p>
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
                $photoCount = count($gallery);
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="svc-card"
                     data-svc-name="<?= $svcNameJs ?>"
                     data-svc-gallery="<?= $galleryJsonAttr ?>"
                     onclick="openSvcFromEl(this)">

                    <!-- Gambar + Overlay -->
                    <div class="svc-card-img-wrap">
                        <?php if (!empty($svc['image'])): ?>
                        <img src="<?= esc($svc['image']) ?>" alt="<?= $svcName ?>" loading="lazy">
                        <?php endif; ?>

                        <!-- Badge nama layanan -->
                        <span class="svc-badge"><?= $svcName ?></span>

                        <!-- Hover overlay -->
                        <div class="svc-card-overlay"></div>
                    </div>

                    <!-- Info bawah -->
                    <div class="svc-card-info">
                        <div class="svc-card-name"><?= $svcName ?></div>
                        <div class="svc-card-sub">
                            <i class="fas fa-spa" style="color:#D6C1A3;font-size:10px;"></i>
                            Niswa Beauty
                        </div>
                        <div class="svc-card-divider"></div>
                        <div class="svc-card-cta">
                            <span class="svc-cta-label">
                                <i class="fas fa-eye" style="font-size:10px;"></i>
                                Lihat Galeri
                            </span>
                            <span class="svc-cta-arrow"><i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>

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
            <h2 class="services-title"><?= esc($sec['harga_title']) ?></h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2"><?= esc($sec['harga_subtitle']) ?></p>
        </div>

        <div class="price-cards-grid">
        <?php
        // Urutkan: Treatment Spa atas, Nail Art paling bawah
        $catOrder = ["Brow & Lash","Treatment Spa","Henna Series","Nail Art & Services","Rambut"];
        $sorted = [];
        foreach ($catOrder as $c) { if (isset($priceList[$c])) $sorted[$c] = $priceList[$c]; }
        foreach ($priceList as $c => $v) { if (!isset($sorted[$c])) $sorted[$c] = $v; }

        // Urutkan item tiap kategori dari harga terendah
        foreach ($sorted as $cat => &$items) {
            usort($items, fn($a,$b) => lowestPrice($a['price']) - lowestPrice($b['price']));
        }
        unset($items);

        // Bangun array card — Rambut dipecah 6 & 7, Nail Art dipecah 6 & 6
        $priceCards = [];
        foreach ($sorted as $cat => $items) {
            if ($cat === 'Rambut') {
                $priceCards[] = ['cat'=>$cat, 'label'=>'Rambut', 'badge'=>'≤ Rp 100rb', 'items'=>array_slice($items, 0, 6)];
                $priceCards[] = ['cat'=>$cat, 'label'=>'Rambut', 'badge'=>'> Rp 100rb',  'items'=>array_slice($items, 6)];
            } elseif ($cat === 'Nail Art & Services') {
                $priceCards[] = ['cat'=>$cat, 'label'=>'Nail Art & Services', 'badge'=>'≤ Rp 50rb', 'items'=>array_slice($items, 0, 6)];
                $priceCards[] = ['cat'=>$cat, 'label'=>'Nail Art & Services', 'badge'=>'> Rp 50rb',  'items'=>array_slice($items, 6)];
            } else {
                $priceCards[] = ['cat'=>$cat, 'label'=>$cat, 'badge'=>'', 'items'=>$items];
            }
        }

        foreach ($priceCards as $delay => $card):
            $cat   = $card['cat'];
            $items = $card['items'];
            if (empty($items)) continue;
        ?>
        <div class="price-card" data-aos="fade-up" data-aos-delay="<?= $delay * 80 ?>">
            <div class="price-card-header">
                <span class="price-card-label"><?= esc($card['label']) ?></span>
                <div class="price-card-header-right">
                    <?php if ($card['badge']): ?>
                    <span class="price-card-badge"><?= esc($card['badge']) ?></span>
                    <?php endif; ?>
                    <span class="price-acc-count"><?= count($items) ?> layanan</span>
                </div>
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
                            data-image="<?= htmlspecialchars($row['image'] ?? '', ENT_QUOTES) ?>"
                            data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>">
                            <td>
                                <?= esc($row['name']) ?>
                                <?php if (!empty($row['image'])): ?>
                                <span class="price-row-hint"><i class="fa-solid fa-image"></i></span>
                                <?php else: ?>
                                <span class="price-row-hint"><i class="fa-solid fa-circle-info"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end price-cell"><?= esc($row['price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
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
        <img id="pmImg" src="" alt="" class="price-modal-img" style="display:none;" onerror="this.style.display='none'">
        <div class="price-modal-name" id="pmName"></div>
        <div class="price-modal-price" id="pmPrice"></div>
        <div class="price-modal-divider"></div>
        <div class="price-modal-desc" id="pmDesc"></div>
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
    margin-top: 4px;
}
.price-modal-wa:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,211,102,0.4); color:#fff; }
.price-modal-img {
    width: 100%; max-height: 200px; object-fit: cover;
    border-radius: 14px; margin-bottom: 14px;
    border: 1px solid #f0e8df;
}
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
        var img   = this.dataset.image || '';
        document.getElementById('pmCat').textContent   = cat;
        document.getElementById('pmName').textContent  = name;
        document.getElementById('pmPrice').textContent = price;
        document.getElementById('pmDesc').textContent  = desc || getDesc(name, cat);
        var pmImg = document.getElementById('pmImg');
        if (img) {
            pmImg.src = img;
            pmImg.style.display = 'block';
        } else {
            pmImg.src = '';
            pmImg.style.display = 'none';
        }
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
.price-list-section { background: #fdfaf7; padding: 45px 0; }
.price-cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }
.price-card { border-radius:20px; overflow:hidden; box-shadow:0 4px 20px rgba(139,111,94,0.12); border:1px solid rgba(214,193,163,0.3); background:#fff; transition:transform .32s cubic-bezier(0.4,0,0.2,1),box-shadow .32s cubic-bezier(0.4,0,0.2,1); }
.price-card:hover { transform:translateY(-6px); box-shadow:0 16px 48px rgba(139,111,94,0.22); }
.price-card-header { display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#8B6F5E,#D6C1A3); padding:16px 20px; gap:8px; }
.price-card-label { font-weight:700; font-size:15px; color:#fff; font-family:'Poppins',sans-serif; flex:1; min-width:0; }
.price-card-header-right { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.price-card-badge { font-size:10px; font-weight:700; font-family:'Poppins',sans-serif; color:#8B6F5E; background:#fff; border-radius:20px; padding:3px 10px; white-space:nowrap; }
.price-acc-count { font-size:11px; color:rgba(255,255,255,0.95); background:rgba(255,255,255,0.25); border-radius:20px; padding:3px 12px; font-weight:600; font-family:'Poppins',sans-serif; white-space:nowrap; flex-shrink:0; }
.price-acc-body { border-top:1px solid rgba(139,111,94,0.12); overflow-x:hidden; }
.price-table { width:100%; border-collapse:collapse; background:#fff; font-family:'Poppins',sans-serif; font-size:13.5px; table-layout:fixed; }
.price-table thead tr { background:#faf5f0; border-bottom:2px solid #f0e8df; }
.price-table th { padding:10px 16px; color:#8B6F5E; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.price-table th:first-child { width:55%; }
.price-table th:last-child { width:45%; }
.price-table tbody tr { border-bottom:1px solid #f5ede6; transition:background .15s; }
.price-table tbody tr:last-child { border-bottom:none; }
.price-table tbody tr:hover { background:#fdf8f4; }
.price-table td { padding:10px 16px; color:#444; vertical-align:middle; word-break:break-word; }
.price-cell { color:#8B6F5E; font-weight:700; font-size:12.5px; word-break:break-word; white-space:normal; line-height:1.4; }
@media(max-width:576px){ .price-cards-grid { grid-template-columns:1fr; } .price-table { font-size:12.5px; } .price-table td { padding:9px 12px; } .price-cell { font-size:12px; } }
</style>

<!-- ══ PRODUK COLLECTION ══ -->
<?php
$productsByCategory = ['simple' => [], 'glam' => [], 'wedding' => []];
foreach ($products as $p) {
    $cat = strtolower(trim($p['category'] ?? 'simple'));
    if (isset($productsByCategory[$cat]) && count($productsByCategory[$cat]) < 8) {
        $productsByCategory[$cat][] = $p;
    }
}
$catLabels = ['simple' => 'Simple', 'glam' => 'Glam', 'wedding' => 'Wedding'];
?>
<section id="produk" class="section-product">
    <div class="container">
        <div class="product-section-title" data-aos="fade-up">
            <h2><?= esc($sec['produk_title']) ?></h2>
            <div class="title-line"></div>
            <p><?= esc($sec['produk_subtitle']) ?> <i class="fa-solid fa-sparkles" style="color:#D6C1A3;font-size:0.9em;"></i></p>
        </div>

        <?php if ($globalDisc['enabled'] && $globalDisc['discount_pct'] > 0): ?>
        <div data-aos="fade-up" data-aos-delay="60" style="max-width:600px;margin:0 auto 28px;background:linear-gradient(135deg,#8B6F5E,#C4A882);border-radius:16px;padding:14px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 6px 24px rgba(139,111,94,0.28);">
            <div style="background:rgba(255,255,255,0.22);border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-tag" style="color:#fff;font-size:18px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-family:'Poppins',sans-serif;font-weight:800;font-size:15px;color:#fff;line-height:1.3;">
                    <?= esc($globalDisc['label'] ?: ('Diskon ' . $globalDisc['discount_pct'] . '%!')) ?>
                </div>
                <div style="font-family:'Poppins',sans-serif;font-size:12px;color:rgba(255,255,255,0.88);margin-top:2px;">
                    Diskon <strong><?= $globalDisc['discount_pct'] ?>%</strong>
                    <?php if ($globalDisc['min_purchase'] > 0): ?>
                    · Min. belanja <strong>Rp <?= number_format($globalDisc['min_purchase'],0,',','.') ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <div style="background:rgba(255,255,255,0.95);border-radius:12px;padding:6px 14px;flex-shrink:0;">
                <span style="font-family:'Poppins',sans-serif;font-weight:800;font-size:18px;color:#8B6F5E;"><?= $globalDisc['discount_pct'] ?>%</span>
                <span style="font-family:'Poppins',sans-serif;font-size:10px;font-weight:600;color:#8B6F5E;display:block;text-align:center;margin-top:-3px;">OFF</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tab Buttons -->
        <div class="prod-tab-wrap" data-aos="fade-up" data-aos-delay="80">
            <?php $first = true; foreach ($catLabels as $key => $label): ?>
            <button class="prod-tab-btn<?= $first ? ' active' : '' ?>" data-tab="<?= $key ?>"><?= $label ?></button>
            <?php $first = false; endforeach; ?>
        </div>

        <!-- Tab Panels -->
        <?php $first = true; foreach ($productsByCategory as $catKey => $catProducts): ?>
        <div class="prod-tab-panel<?= $first ? ' active' : '' ?>" id="panel-<?= $catKey ?>">
            <?php if (!empty($catProducts)): ?>
            <div class="product-grid">
            <?php foreach ($catProducts as $p):
                $img     = esc($p['image'] ?? $p['img'] ?? '');
                $pName   = esc($p['name']);
                $pPrice  = esc($p['price']);
                $pDisc   = (int)($p['discount_pct'] ?? 0);
                $pMinBuy = (int)($p['min_purchase'] ?? 0);
            ?>
                <div class="product-card" data-category="<?= $catKey ?>">
                    <div class="product-card-img-wrap">
                        <img src="<?= $img ?>" alt="<?= $pName ?>" class="product-img" loading="lazy">
                        <div class="product-card-overlay">
                            <button class="btn-preview" onclick="showProductPreview('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['image'] ?? $p['img'] ?? '') ?>')">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                        </div>
                        <span class="product-badge-cat"><?= strtoupper($catLabels[$catKey]) ?></span>
                        <?php if ($pDisc > 0): ?>
                        <span class="product-badge-disc"><?= $pDisc ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-info-top">
                            <div class="product-name"><?= $pName ?></div>
                            <div class="product-price"><?= $pPrice ?></div>
                            <?php if ($pDisc > 0): ?>
                            <div class="product-disc-info">
                                <i class="fas fa-tag" style="margin-right:4px;"></i>
                                Diskon <strong><?= $pDisc ?>%</strong>
                                <?php if ($pMinBuy > 0): ?>
                                · Min. beli <strong>Rp <?= number_format($pMinBuy,0,',','.') ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="product-disc-placeholder"></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info-actions">
                            <button class="btn-beli" onclick="handleBeli('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['image'] ?? $p['img'] ?? '') ?>',<?= $pDisc ?>,<?= $pMinBuy ?>)">
                                <i class="fas fa-shopping-bag me-1"></i> Beli Sekarang
                            </button>
                            <button class="btn-add-cart" onclick="openQtyPicker('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['image'] ?? $p['img'] ?? '') ?>','Produk',<?= $pDisc ?>,<?= $pMinBuy ?>)">
                                <i class="fas fa-cart-plus"></i> + Keranjang
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php $first = false; endforeach; ?>

    </div>
</section>

<style>
.prod-tab-wrap { display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin-bottom:32px; }
.prod-tab-btn {
    border: 1.5px solid #e8ddd4; background: #fff; border-radius: 50px;
    padding: 9px 28px; font-weight: 600; color: #888; cursor: pointer;
    font-size: 14px; font-family: 'Poppins', sans-serif;
    transition: background .22s, color .22s, border-color .22s, box-shadow .22s;
    box-shadow: 0 2px 8px rgba(139,111,94,0.06); outline: none;
}
.prod-tab-btn:hover:not(.active) {
    background: #f5ede6; color: #8B6F5E; border-color: #D6C1A3;
    box-shadow: 0 4px 14px rgba(139,111,94,0.13);
}
.prod-tab-btn.active {
    background: linear-gradient(135deg,#8B6F5E,#C4A882);
    color: #fff; border-color: transparent;
    box-shadow: 0 4px 16px rgba(139,111,94,0.30);
}
.prod-tab-panel { display: none; }
.prod-tab-panel.active { display: block; animation: tabFadeIn .28s ease; }
@keyframes tabFadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
</style>

<script>
(function(){
    var btns = document.querySelectorAll('.prod-tab-btn');
    var panels = document.querySelectorAll('.prod-tab-panel');
    btns.forEach(function(btn){
        btn.addEventListener('click', function(){
            btns.forEach(function(b){ b.classList.remove('active'); });
            panels.forEach(function(p){ p.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.getElementById('panel-' + btn.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });
})();
</script>

<style>
.section-product { background:#fdfaf7; padding:50px 0; }
.product-section-title { text-align:center; margin-bottom:32px; }
.product-section-title h2 { font-weight:700; font-size:34px; font-family:'Playfair Display',serif; color:#2d1f17; }
.product-section-title span { color:#8B6F5E; }
.product-section-title p { color:#888; margin-top:10px; font-size:15px; }
.filter-buttons { text-align:center; margin-bottom:40px; display:flex; justify-content:center; gap:8px; flex-wrap:wrap; }
.filter-buttons button { border:1.5px solid #e8ddd4; background:#fff; border-radius:50px; padding:8px 24px; font-weight:500; color:#888; cursor:pointer; font-size:14px; font-family:'Poppins',sans-serif; transition:background .22s, color .22s, border-color .22s, box-shadow .22s; box-shadow:0 2px 8px rgba(139,111,94,0.06); outline:none; }
.filter-buttons button:hover:not(.active) { background:#f5ede6; color:#8B6F5E; border-color:#D6C1A3; box-shadow:0 4px 14px rgba(139,111,94,0.13); }
.filter-buttons button.active { background:linear-gradient(135deg,#8B6F5E,#C4A882); color:#fff; border-color:transparent; box-shadow:0 4px 16px rgba(139,111,94,0.30); }
.product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }
.product-card { background:#fff; border-radius:20px; overflow:hidden; transition:transform .3s cubic-bezier(0.4,0,0.2,1), box-shadow .3s cubic-bezier(0.4,0,0.2,1); display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(139,111,94,0.10); border:1px solid rgba(214,193,163,0.25); }
.product-card:hover { transform:translateY(-8px); box-shadow:0 16px 48px rgba(139,111,94,0.22); }
.product-card-img-wrap { position:relative; overflow:hidden; flex-shrink:0; }
.product-img { width:100%; height:260px; object-fit:cover; display:block; transition:transform .45s ease; }
.product-card:hover .product-img { transform:scale(1.06); }
.product-card-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.38); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .3s; z-index:3; }
.product-card:hover .product-card-overlay { opacity:1; }
.btn-preview { background:rgba(255,255,255,0.92); color:#5A4A42; border:none; border-radius:50px; padding:9px 22px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transform:translateY(8px); transition:transform .3s, background .2s; box-shadow:0 4px 14px rgba(0,0,0,0.12); display:flex; align-items:center; gap:6px; }
.product-card:hover .btn-preview { transform:translateY(0); }
.btn-preview:hover { background:#fff; color:#8B6F5E; }
.product-badge-cat { position:absolute; top:12px; left:12px; background:rgba(255,255,255,0.88); color:#5A4A42; font-size:10px; font-weight:700; font-family:'Poppins',sans-serif; letter-spacing:.8px; text-transform:uppercase; padding:4px 12px; border-radius:50px; backdrop-filter:blur(6px); border:1px solid rgba(255,255,255,0.6); z-index:2; }
.product-badge-disc { position:absolute; top:12px; right:12px; background:linear-gradient(135deg,#e74c3c,#c0392b); color:#fff; font-size:10px; font-weight:800; font-family:'Poppins',sans-serif; letter-spacing:.5px; padding:4px 10px; border-radius:50px; box-shadow:0 3px 10px rgba(231,76,60,0.4); z-index:2; }
.product-info { padding:16px; display:flex; flex-direction:column; flex:1; }
.product-info-top { flex:1; }
.product-name { font-weight:600; font-family:'Poppins',sans-serif; font-size:14px; color:#2d1f17; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:color .25s; }
.product-card:hover .product-name { color:#8B6F5E; }
.product-price { color:#8B6F5E; font-weight:700; font-size:15px; margin-bottom:6px; }
.product-disc-info { font-size:11px; color:#c0392b; margin-bottom:8px; padding:5px 9px; background:#fff5f5; border-radius:8px; border:1px dashed #f5b7b1; }
.product-disc-placeholder { margin-bottom:8px; height:0; }
.product-info-actions { margin-top:auto; display:flex; flex-direction:column; gap:6px; }
.btn-beli { position:relative; overflow:hidden; width:100%; padding:9px 0; background:linear-gradient(135deg,#8B6F5E,#D6C1A3); color:#fff; border:none; border-radius:12px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; transition:color .25s,box-shadow .25s,transform .2s; box-shadow:0 4px 14px rgba(139,111,94,0.25); }
.btn-beli::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,#6e5549,#b89e85); opacity:0; transition:opacity .25s; border-radius:12px; z-index:-1; }
.btn-beli:hover::before { opacity:1; }
.btn-beli:hover { box-shadow:0 8px 22px rgba(139,111,94,0.42); transform:translateY(-1px); }
.btn-beli:active { transform:translateY(0) scale(0.97); box-shadow:0 2px 8px rgba(139,111,94,0.2); }
@media(max-width:576px){ .product-grid { grid-template-columns:repeat(2,1fr); gap:14px; } .product-img { height:180px; } }
</style>

<!-- ══ SECTION LOKASI ══ -->
<section id="lokasi" class="location-section">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span>Temukan Kami</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150">Lokasi <span>Kami</span></h2>
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

.lokasi-info-col { background: #fff; border-left: 1px solid #EDE5D8; }
.lokasi-info-box { padding: 40px 36px; height: 100%; display: flex; flex-direction: column; justify-content: center; }

.lokasi-brand { display: flex; align-items: center; gap: 14px; margin-bottom: 4px; }
.lokasi-brand-icon { font-size: 32px; color: #8B6F5E; flex-shrink: 0; }
.lokasi-brand-name { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: #2d1f17; line-height: 1.2; }
.lokasi-brand-sub { font-size: 12px; color: #999; font-family: 'Poppins', sans-serif; letter-spacing: .5px; margin-top: 2px; }

.lokasi-divider { border: none; border-top: 1px solid #f0e8df; margin: 22px 0; }

.lokasi-detail-list { display: flex; flex-direction: column; gap: 18px; }
.lokasi-detail-item { display: flex; align-items: flex-start; gap: 14px; }
.lokasi-detail-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: #fdf0e8;
    display: flex; align-items: center; justify-content: center;
    color: #8B6F5E; font-size: 15px; flex-shrink: 0;
    transition: background .2s;
}
.lokasi-detail-item:hover .lokasi-detail-icon { background: #f5e4d4; }
.lokasi-detail-label { font-size: 10.5px; color: #aaa; text-transform: uppercase; letter-spacing: .8px; font-family: 'Poppins', sans-serif; font-weight: 600; margin-bottom: 2px; }
.lokasi-detail-value { font-size: 13.5px; color: #2d1f17; font-family: 'Poppins', sans-serif; font-weight: 500; line-height: 1.4; }

.lokasi-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.lokasi-btn-primary {
    flex: 1; min-width: 130px; text-align: center;
    background: linear-gradient(135deg,#6b4f3a,#a07850);
    color: #fff; border: none; border-radius: 50px;
    padding: 11px 20px; font-size: 13px; font-weight: 700;
    font-family: 'Poppins', sans-serif; text-decoration: none;
    transition: all .25s; box-shadow: 0 4px 16px rgba(107,79,58,0.25);
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.lokasi-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(107,79,58,0.35); color: #fff; }
.lokasi-btn-secondary {
    flex: 1; min-width: 130px; text-align: center;
    background: #fff; color: #2d1f17;
    border: 1.5px solid #e0d0c0; border-radius: 50px;
    padding: 11px 20px; font-size: 13px; font-weight: 600;
    font-family: 'Poppins', sans-serif; text-decoration: none;
    transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.lokasi-btn-secondary:hover { background: #fdf0e8; border-color: #c4a882; color: #2d1f17; transform: translateY(-2px); }

@media (max-width: 768px) {
    .lokasi-split-wrap { grid-template-columns: 1fr; }
    .lokasi-map-col { min-height: 280px; position: relative; }
    .lokasi-map-box { position: relative; height: 280px; }
    .lokasi-info-box { padding: 28px 22px; }
}
</style>

<style>
.section-title {
    font-family: 'Playfair Display', serif !important;
    font-size: 34px !important;
    font-weight: 700 !important;
    color: #2d1f17 !important;
}
@media(max-width:576px){ .section-title { font-size: 26px !important; } }
</style>

<!-- ══ SECTION TESTIMONI ══ -->
<style>
/* ── Testimoni: slide per grup, responsif mobile ── */
.testimoni-carousel-wrapper {
    overflow: hidden;
    position: relative;
    padding: 6px 0 20px;
}
.testimoni-track {
    display: flex;
    transition: transform 0.5s cubic-bezier(0.4,0,0.2,1);
    will-change: transform;
    align-items: stretch;
}
.testimoni-group {
    display: flex;
    flex-shrink: 0;
    gap: 16px;
    box-sizing: border-box;
    align-items: stretch;
    padding: 4px 2px 8px;
}
.testimoni-group .testimoni-card {
    flex: 1;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    min-width: 0;
}
.testimoni-group .testimoni-text {
    flex: 1;
}
/* Mobile: satu card penuh, padding horizontal */
@media (max-width: 599px) {
    .testimoni-group {
        gap: 0;
        padding: 4px 8px 8px;
    }
    /* Rating badge responsif */
    .testimoni-rating-badge {
        border-radius: 16px !important;
        padding: 10px 16px !important;
        gap: 8px !important;
    }
    .testimoni-score {
        font-size: 22px !important;
    }
    .review-write-btn {
        margin-left: 0 !important;
    }
    /* Testimoni section padding */
    .testimoni-section {
        padding-left: 0;
        padding-right: 0;
    }
    .testimoni-section .container {
        padding-left: 12px;
        padding-right: 12px;
    }
    /* Footer card: service tag ke baris baru */
    .testimoni-footer {
        flex-direction: row;
        flex-wrap: wrap;
    }
    .testimoni-service-tag {
        width: 100%;
        text-align: center;
    }
}
/* Dots */
.testimoni-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}
.testimoni-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e0d3c8;
    cursor: pointer;
    transition: background 0.3s, transform 0.3s, width 0.3s;
    display: inline-block;
}
.testimoni-dot.active {
    background: #8B6F5E;
    width: 24px;
    border-radius: 4px;
    transform: none;
}
</style>
<section id="testimoni" class="testimoni-section py-5">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span><?= esc($sec['testi_label']) ?></span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150"><?= esc($sec['testi_title']) ?></h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="250" style="font-size:15px;"><?= esc($sec['testi_subtitle']) ?></p>
                <div class="testimoni-rating-badge" data-aos="fade-up" data-aos-delay="350">
                    <div class="testimoni-score"><?= esc($sec['testi_rating']) ?></div>
                    <div class="testimoni-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <div class="testimoni-rating-label"><?= esc($sec['testi_rating_label']) ?></div>
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
            <button id="ppBeli" class="btn-beli" style="padding:12px;font-size:14px;font-weight:700;">
                <i class="fas fa-shopping-bag me-2"></i>Beli Sekarang
            </button>
        </div>
    </div>
</div>
<style>@keyframes ppFadeIn{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}</style>

<!-- ══ QTY PICKER MODAL ══ -->
<div id="qtyPickerOverlay" onclick="closeQtyPicker()" style="display:none;position:fixed;inset:0;z-index:10010;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);"></div>
<div id="qtyPickerSheet" style="display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(30px);z-index:10011;background:#fff;border-radius:20px;box-shadow:0 12px 48px rgba(0,0,0,0.22);padding:0;width:92%;max-width:400px;opacity:0;transition:transform .28s cubic-bezier(.32,1,.6,1),opacity .25s ease;">

    <!-- Info produk -->
    <div style="display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #f0e8df;">
        <img id="qpImg" src="" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:12px;border:1.5px solid #f0e8df;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div id="qpName" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;color:#2d1f17;line-height:1.4;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
            <div id="qpPrice" style="font-family:'Poppins',sans-serif;font-weight:800;font-size:17px;color:#8B6F5E;"></div>
        </div>
        <button onclick="closeQtyPicker()" style="background:#f5ede6;border:none;border-radius:50%;width:30px;height:30px;font-size:17px;color:#8B6F5E;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>

    <!-- Stepper + subtotal -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 18px;">
        <div style="display:flex;align-items:center;gap:0;background:#f8f4f0;border-radius:50px;padding:3px;border:1.5px solid #ede0d4;">
            <button onclick="qpChangeQty(-1)" style="width:38px;height:38px;border:none;background:transparent;border-radius:50%;font-size:22px;font-weight:700;color:#8B6F5E;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;" onmousedown="this.style.background='#ede0d4'" onmouseup="this.style.background='transparent'" onmouseleave="this.style.background='transparent'">−</button>
            <span id="qpQtyVal" style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:#2d1f17;min-width:38px;text-align:center;user-select:none;">1</span>
            <button onclick="qpChangeQty(1)" style="width:38px;height:38px;border:none;background:transparent;border-radius:50%;font-size:22px;font-weight:700;color:#8B6F5E;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;" onmousedown="this.style.background='#ede0d4'" onmouseup="this.style.background='transparent'" onmouseleave="this.style.background='transparent'">+</button>
        </div>
        <div id="qpSubtotal" style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:800;color:#2d1f17;"></div>
    </div>

    <!-- Tombol konfirmasi -->
    <div style="padding:0 18px 18px;">
        <button id="qpAddBtn" onclick="qpConfirmAdd()" style="width:100%;padding:13px;border:none;border-radius:14px;background:linear-gradient(135deg,#8B6F5E,#C4A882);color:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 4px 16px rgba(139,111,94,0.30);transition:transform .15s;" onmousedown="this.style.transform='scale(.97)'" onmouseup="this.style.transform='scale(1)'" onmouseleave="this.style.transform='scale(1)'">
            <i class="fas fa-cart-plus"></i>
            <span id="qpAddBtnText">Tambah ke Keranjang</span>
        </button>
    </div>
</div>

<!-- ══ COD PAYMENT GATEWAY STYLES ══ -->
<style>
.cod-payment-section { margin-bottom:14px; }
.cod-payment-label { font-size:13px;font-weight:600;margin-bottom:10px;display:block;color:#2d1f17; }
.cod-method-card {
    border:2px solid #e8e0d8; border-radius:14px; padding:14px 16px;
    cursor:pointer; transition:all .2s; background:#fff;
    display:flex; align-items:center; gap:14px; position:relative;
}
.cod-method-card:hover { border-color:#D6C1A3; background:#fdf8f4; }
.cod-method-card.selected { border-color:#8B6F5E; background:#fdf5ee; box-shadow:0 0 0 3px rgba(139,111,94,0.12); }
.cod-method-icon {
    width:48px; height:48px; border-radius:12px; display:flex;
    align-items:center; justify-content:center; font-size:22px;
    background:linear-gradient(135deg,#f5ede6,#ede0d4); color:#8B6F5E; flex-shrink:0;
}
.cod-method-info { flex:1; }
.cod-method-title { font-family:'Poppins',sans-serif; font-size:13.5px; font-weight:700; color:#2d1f17; }
.cod-method-desc { font-family:'Poppins',sans-serif; font-size:11.5px; color:#aaa; margin-top:2px; line-height:1.4; }
.cod-method-badge {
    font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px;
    background:linear-gradient(135deg,#10b981,#059669); color:#fff;
    font-family:'Poppins',sans-serif; letter-spacing:.3px; flex-shrink:0;
}
.cod-method-check {
    position:absolute; top:10px; right:12px;
    width:20px; height:20px; border-radius:50%;
    border:2px solid #e8e0d8; display:flex; align-items:center; justify-content:center;
    transition:all .2s; font-size:10px; color:transparent;
}
.cod-method-card.selected .cod-method-check { border-color:#8B6F5E; background:#8B6F5E; color:#fff; }

/* COD info box */
.cod-info-box {
    background: linear-gradient(135deg, #f0faf5, #e8f5f0);
    border: 1.5px solid #a7d7c5; border-radius:12px;
    padding:12px 14px; margin-bottom:14px;
    font-family:'Poppins',sans-serif; font-size:12px; color:#2d6a4f;
}
.cod-info-box .cod-info-title { font-weight:700; font-size:13px; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.cod-info-box ul { margin:0; padding-left:16px; }
.cod-info-box li { margin-bottom:4px; line-height:1.5; }

/* Submit button COD */
.btn-cod-submit {
    width:100%; padding:14px; border:none; border-radius:14px; cursor:pointer;
    font-size:15px; font-weight:700; font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#8B6F5E,#C4A882);
    color:#fff; box-shadow:0 6px 20px rgba(139,111,94,0.35);
    transition:all .25s; display:flex; align-items:center; justify-content:center; gap:8px;
    position:relative; overflow:hidden;
}
.btn-cod-submit:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(139,111,94,0.45); }
.btn-cod-submit:active { transform:translateY(0); box-shadow:0 4px 12px rgba(139,111,94,0.3); }
.btn-cod-submit:disabled { background:#ccc; box-shadow:none; cursor:not-allowed; transform:none; }

/* Step indicator */
.cod-steps { display:flex; align-items:center; gap:0; margin-bottom:16px; }
.cod-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
.cod-step:not(:last-child)::after { content:''; position:absolute; top:13px; left:calc(50% + 13px); right:calc(-50% + 13px); height:2px; background:#e8e0d8; z-index:0; }
.cod-step.active:not(:last-child)::after { background:#8B6F5E; }
.cod-step-dot { width:28px; height:28px; border-radius:50%; border:2.5px solid #e8e0d8; background:#fff; z-index:1; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#bbb; transition:all .3s; }
.cod-step.active .cod-step-dot { border-color:#8B6F5E; background:#8B6F5E; color:#fff; }
.cod-step.done .cod-step-dot { border-color:#10b981; background:#10b981; color:#fff; }
.cod-step-label { font-size:9.5px; font-weight:600; color:#bbb; font-family:'Poppins',sans-serif; margin-top:4px; text-align:center; letter-spacing:.3px; transition:color .3s; }
.cod-step.active .cod-step-label { color:#8B6F5E; }
.cod-step.done .cod-step-label { color:#10b981; }

/* Ongkir estimasi */
.cod-shipping-info { background:#fff8f0; border:1.5px solid #f5dbb5; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-family:'Poppins',sans-serif; font-size:12px; color:#a07030; display:flex; align-items:center; gap:10px; }
</style>

<!-- ══ ORDER MODAL ══ -->
<div id="orderModal" style="display:none;position:fixed;inset:0;z-index:10020;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px);">
    <div style="background:#fff;border-radius:24px;width:100%;max-width:460px;max-height:92vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.28);">
        <!-- Header -->
        <div style="padding:18px 22px 14px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0e8df;position:sticky;top:0;background:#fff;z-index:2;border-radius:24px 24px 0 0;">
            <div>
                <div style="font-size:10px;letter-spacing:1.8px;text-transform:uppercase;color:#8B6F5E;font-weight:700;margin-bottom:3px;">
                    <i class="fas fa-store me-1"></i> Checkout Produk
                </div>
                <h5 id="modalProductName" style="font-weight:700;font-size:15px;color:#2d1f17;margin:0;font-family:'Poppins',sans-serif;"></h5>
            </div>
            <button onclick="closeOrder()" style="background:#f5ede6;border:none;border-radius:50%;width:34px;height:34px;font-size:18px;color:#8B6F5E;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;" onmouseover="this.style.background='#e8d8cc'" onmouseout="this.style.background='#f5ede6'">&times;</button>
        </div>

        <div style="padding:18px 22px 22px;">
            <!-- Step Indicator -->
            <div class="cod-steps">
                <div class="cod-step done" id="step1">
                    <div class="cod-step-dot"><i class="fas fa-check" style="font-size:9px;"></i></div>
                    <div class="cod-step-label">Produk</div>
                </div>
                <div class="cod-step active" id="step2">
                    <div class="cod-step-dot">2</div>
                    <div class="cod-step-label">Pengiriman</div>
                </div>
                <div class="cod-step" id="step3">
                    <div class="cod-step-dot">3</div>
                    <div class="cod-step-label">Pembayaran</div>
                </div>
                <div class="cod-step" id="step4">
                    <div class="cod-step-dot">4</div>
                    <div class="cod-step-label">Selesai</div>
                </div>
            </div>

            <!-- Product Info -->
            <div style="display:flex;align-items:center;gap:14px;background:#faf5f0;border-radius:14px;padding:14px;margin-bottom:18px;border:1px solid #f0e8df;">
                <img id="modalProductImg" src="" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #e8e0d8;">
                <div style="flex:1;">
                    <div id="modalProductNameInner" style="font-weight:600;font-size:14px;color:#2d1f17;font-family:'Poppins',sans-serif;line-height:1.4;"></div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:4px;flex-wrap:wrap;">
                        <div id="modalProductPrice" style="color:#8B6F5E;font-weight:800;font-size:16px;font-family:'Poppins',sans-serif;"></div>
                        <div id="modalProductPriceOri" style="display:none;color:#bbb;font-weight:500;font-size:12px;font-family:'Poppins',sans-serif;text-decoration:line-through;"></div>
                    </div>
                    <div id="modalDiscBadge" style="display:none;margin-top:5px;background:#fff3e0;color:#c97000;font-size:11px;font-weight:700;font-family:'Poppins',sans-serif;padding:3px 10px;border-radius:20px;align-items:center;gap:4px;">
                        <i class="fas fa-tag"></i> <span id="modalDiscText"></span>
                    </div>
                </div>
            </div>

            <form method="POST" id="orderForm">
                <input type="hidden" name="order" value="1">
                <input type="hidden" name="product_name" id="inputProductName">
                <input type="hidden" name="product_price" id="inputProductPrice">
                <input type="hidden" name="product_image" id="inputProductImage">
                <input type="hidden" name="payment_method" id="inputPaymentMethod" value="COD">
                <input type="hidden" name="is_cart" id="inputIsCart" value="">
                <input type="hidden" name="disc_pct" id="inputDiscPct" value="0">
                <input type="hidden" name="disc_amt" id="inputDiscAmt" value="">
                <input type="hidden" name="ori_price" id="inputOriPrice" value="">

                <div id="orderError" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;margin-bottom:14px;">
                    <i class="fas fa-exclamation-circle me-1"></i> <span id="orderErrorText"></span>
                </div>

                <!-- Nama & WhatsApp -->
                <?php
                $fields = [
                    ['label'=>'Nama Lengkap','name'=>'nama','type'=>'text','req'=>true,'placeholder'=>'Nama penerima','icon'=>'fas fa-user'],
                    ['label'=>'Nomor WhatsApp','name'=>'whatsapp','type'=>'tel','req'=>true,'placeholder'=>'08xxxxxxxxxx','icon'=>'fab fa-whatsapp'],
                ];
                foreach ($fields as $f): ?>
                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px;font-weight:600;margin-bottom:7px;display:block;color:#2d1f17;"><?= $f['label'] ?> <span style="color:#e74c3c;">*</span></label>
                    <div style="position:relative;">
                        <i class="<?= $f['icon'] ?>" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#C4A882;font-size:14px;"></i>
                        <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" <?= $f['req']?'required':'' ?> placeholder="<?= $f['placeholder'] ?>"
                            style="width:100%;padding:11px 14px 11px 38px;border:1.5px solid #e8e0d8;border-radius:11px;font-size:13px;font-family:Poppins,sans-serif;outline:none;transition:border-color .2s;"
                            onfocus="this.style.borderColor='#8B6F5E'" onblur="this.style.borderColor='#e8e0d8'">
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Alamat -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:12.5px;font-weight:600;margin-bottom:7px;display:block;color:#2d1f17;">Alamat Pengiriman <span style="color:#e74c3c;">*</span></label>
                    <div style="position:relative;">
                        <i class="fas fa-map-marker-alt" style="position:absolute;left:13px;top:13px;color:#C4A882;font-size:14px;"></i>
                        <textarea name="alamat" required placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, kode pos..." rows="3"
                            style="width:100%;padding:11px 14px 11px 38px;border:1.5px solid #e8e0d8;border-radius:11px;font-size:13px;font-family:Poppins,sans-serif;outline:none;resize:none;transition:border-color .2s;"
                            onfocus="this.style.borderColor='#8B6F5E'" onblur="this.style.borderColor='#e8e0d8'"></textarea>
                    </div>
                </div>

                <!-- Jumlah -->
                <div id="qtyField" style="margin-bottom:14px;">
                    <label style="font-size:12.5px;font-weight:600;margin-bottom:7px;display:block;color:#2d1f17;">Jumlah</label>
                    <input type="number" name="qty" id="inputQty" min="1" max="10" value="1"
                        style="width:100%;padding:11px 14px;border:1.5px solid #e8e0d8;border-radius:11px;font-size:13px;font-family:Poppins,sans-serif;outline:none;transition:border-color .2s;"
                        onfocus="this.style.borderColor='#8B6F5E'" onblur="this.style.borderColor='#e8e0d8'">
                    <div id="qtyCartInfo" style="display:none;background:#f0faf5;border:1.5px solid #a7d7c5;border-radius:11px;padding:10px 14px;font-size:13px;font-family:Poppins,sans-serif;color:#2d6a4f;font-weight:500;align-items:center;gap:8px;">
                        <i class="fas fa-shopping-cart" style="color:#40916c;"></i>
                        <span id="qtyCartText"></span>
                    </div>
                </div>

                <!-- Catatan -->
                <div style="margin-bottom:16px;">
                    <label style="font-size:12.5px;font-weight:600;margin-bottom:7px;display:block;color:#2d1f17;">Catatan <span style="color:#aaa;font-weight:400;">(opsional)</span></label>
                    <div style="position:relative;">
                        <i class="fas fa-sticky-note" style="position:absolute;left:13px;top:13px;color:#C4A882;font-size:13px;"></i>
                        <textarea name="catatan" placeholder="Contoh: warna pilihan, ukuran, atau permintaan khusus lainnya..." rows="2"
                            style="width:100%;padding:11px 14px 11px 38px;border:1.5px solid #e8e0d8;border-radius:11px;font-size:13px;font-family:Poppins,sans-serif;outline:none;resize:none;transition:border-color .2s;"
                            onfocus="this.style.borderColor='#8B6F5E'" onblur="this.style.borderColor='#e8e0d8'"></textarea>
                    </div>
                </div>

                <!-- ══ METODE PEMBAYARAN COD ══ -->
                <div class="cod-payment-section">
                    <label class="cod-payment-label"><i class="fas fa-wallet me-2" style="color:#8B6F5E;"></i>Metode Pembayaran</label>
                    <div class="cod-method-card selected" id="codCard" onclick="selectPayment('COD', this)">
                        <div class="cod-method-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="cod-method-info">
                            <div class="cod-method-title">Bayar di Tempat (COD)</div>
                            <div class="cod-method-desc">Bayar tunai saat produk diterima</div>
                        </div>
                        <div class="cod-method-badge">Tersedia</div>
                        <div class="cod-method-check"><i class="fas fa-check"></i></div>
                    </div>
                </div>

                <!-- Info COD -->
                <div class="cod-info-box">
                    <div class="cod-info-title">
                        <i class="fas fa-info-circle" style="color:#10b981;"></i> Cara Kerja COD
                    </div>
                    <ul>
                        <li>Pesanan dikonfirmasi via WhatsApp setelah submit</li>
                        <li>Tim kami akan menghubungi untuk verifikasi pengiriman</li>
                        <li>Siapkan uang tunai sesuai total saat kurir tiba</li>
                        <li>Pembayaran dilakukan kepada kurir/tim pengiriman</li>
                    </ul>
                </div>

                <!-- Info ongkir -->
                <div class="cod-shipping-info">
                    <i class="fas fa-truck" style="font-size:20px;flex-shrink:0;"></i>
                    <div>
                        <div style="font-weight:700;font-size:12.5px;">Ongkos Kirim — Jepara</div>
                        <div style="font-size:11.5px;margin-top:1px;">Flat <strong>Rp 5.000</strong> untuk seluruh wilayah Jepara.</div>
                    </div>
                </div>

                <!-- Ringkasan Pembayaran -->
                <div id="orderSummaryBox" style="background:linear-gradient(135deg,#faf5f0,#f5ede6);border-radius:14px;padding:14px 16px;margin-bottom:16px;font-family:'Poppins',sans-serif;font-size:13px;border:1px solid #ede0d4;">
                    <div style="font-weight:700;font-size:12px;color:#8B6F5E;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-receipt"></i> Ringkasan Pesanan
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#888;margin-bottom:7px;">
                        <span>Subtotal</span><span id="summarySubtotal" style="font-weight:600;color:#2d1f17;">-</span>
                    </div>
                    <div id="summaryDiscRow" style="display:none;justify-content:space-between;color:#c97000;margin-bottom:7px;">
                        <span id="summaryDiscLabel">Diskon</span><span id="summaryDiscAmt" style="font-weight:600;">-</span>
                    </div>
                    <div id="summaryMinBuyRow" style="display:none;align-items:center;gap:6px;color:#e74c3c;margin-bottom:7px;font-size:11.5px;background:#fff5f5;border:1px dashed #f5b7b1;border-radius:8px;padding:6px 10px;">
                        <i class="fas fa-lock" style="flex-shrink:0;font-size:11px;"></i>
                        <span id="summaryMinBuyText"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#888;margin-bottom:7px;">
                        <span>Ongkos Kirim (Jepara)</span>
                        <span style="color:#10b981;font-weight:700;font-size:12px;">Rp 5.000</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#888;margin-bottom:7px;">
                        <span>Metode Bayar</span>
                        <span style="color:#8B6F5E;font-weight:700;display:flex;align-items:center;gap:5px;"><i class="fas fa-money-bill-wave" style="font-size:11px;"></i> <span id="summaryPayMethod">COD</span></span>
                    </div>
                    <div style="border-top:1.5px dashed #e8ddd4;margin:10px 0;"></div>
                    <div style="display:flex;justify-content:space-between;font-weight:800;color:#2d1f17;font-size:15px;">
                        <span>Total (+ Ongkir)</span>
                        <span id="summaryTotal" style="color:#8B6F5E;">-</span>
                    </div>
                </div>

                <button type="submit" class="btn-cod-submit" id="btnCodSubmit">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Konfirmasi & Pesan Sekarang</span>
                </button>
                <div style="text-align:center;margin-top:10px;font-size:11px;color:#bbb;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:5px;">
                    <i class="fas fa-lock" style="font-size:10px;"></i> Pesanan aman & dikonfirmasi via WhatsApp
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ SUCCESS ORDER COD ══ -->
<div id="successOrder" style="display:none;position:fixed;inset:0;z-index:10021;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:24px;width:90%;max-width:400px;padding:0;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.25);">
        <!-- Header success -->
        <div style="background:linear-gradient(135deg,#8B6F5E,#C4A882);padding:28px 24px 22px;text-align:center;">
            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;border:3px solid rgba(255,255,255,0.4);">
                <i class="fas fa-check" style="font-size:32px;color:#fff;"></i>
            </div>
            <h5 style="font-weight:800;font-size:20px;color:#fff;margin:0 0 5px;font-family:'Playfair Display',serif;">Pesanan Berhasil!</h5>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;font-family:'Poppins',sans-serif;">Terima kasih telah memesan di Niswà Beauty</p>
        </div>
        <!-- Detail COD -->
        <div style="padding:20px 22px;">
            <!-- Badge COD -->
            <div style="background:#f0faf5;border:1.5px solid #a7d7c5;border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-money-bill-wave" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-family:'Poppins',sans-serif;font-size:12.5px;font-weight:700;color:#2d6a4f;">Metode: Bayar di Tempat (COD)</div>
                    <div style="font-family:'Poppins',sans-serif;font-size:11.5px;color:#52b788;margin-top:2px;">Siapkan uang tunai saat kurir tiba</div>
                </div>
            </div>
            <!-- Info steps -->
            <div style="font-family:'Poppins',sans-serif;font-size:12.5px;color:#555;margin-bottom:16px;">
                <div style="font-weight:700;font-size:13px;color:#2d1f17;margin-bottom:10px;"><i class="fas fa-list-ol me-2" style="color:#8B6F5E;"></i>Langkah Selanjutnya:</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#8B6F5E,#C4A882);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px;">1</div>
                        <span>Tim kami akan menghubungi kamu via <b>WhatsApp</b> untuk konfirmasi pesanan</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#8B6F5E,#C4A882);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px;">2</div>
                        <span>Pesanan dikemas & dikirim ke alamat kamu</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#8B6F5E,#C4A882);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px;">3</div>
                        <span>Bayar tunai kepada kurir saat barang diterima ✓</span>
                    </div>
                </div>
            </div>
            <!-- Buttons -->
            <a id="successWaCustomerBtn" href="#" target="_blank" style="display:none;"></a>
            <button onclick="document.getElementById('successOrder').style.display='none'"
                style="width:100%;background:#f5ede6;color:#8B6F5E;border:none;border-radius:12px;padding:12px;font-weight:600;font-family:'Poppins',sans-serif;font-size:13.5px;cursor:pointer;transition:background .2s;"
                onmouseover="this.style.background='#ede0d4'" onmouseout="this.style.background='#f5ede6'">
                Tutup
            </button>
        </div>
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
    document.querySelectorAll(".product-card").forEach(function(card){
        card.style.display = card.dataset.category === "simple" ? "" : "none";
    });

    document.querySelectorAll(".filter-buttons button").forEach(function(btn){\n        btn.addEventListener("click", function(){
            document.querySelectorAll(".filter-buttons button").forEach(function(b){ b.classList.remove("active"); });
            btn.classList.add("active");
            var filter = btn.dataset.filter;
            document.querySelectorAll(".product-card").forEach(function(card){
                var show = card.dataset.category === filter;
                card.style.display = show ? "" : "none";
                if(show){
                    card.classList.remove("card-fade-in");
                    void card.offsetWidth;
                    card.classList.add("card-fade-in");
                    card.addEventListener("animationend", function(){
                        card.classList.remove("card-fade-in");
                    }, {once: true});
                }
            });
        });
    });
})();
</script>
<style>
@keyframes cardFadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.card-fade-in{animation:cardFadeIn 0.3s ease forwards;}
</style>

<!-- Order Modal Script -->
<script>
function parseRp(str){return parseInt((str||'0').replace(/[^0-9]/g,''))||0;}
function fmtRp(n){return 'Rp '+n.toLocaleString('id-ID');}

function updateOrderSummary(){
    var modal=document.getElementById('orderModal');
    if(modal&&modal.dataset.cartMode==='1')return;
    var qty=parseInt(document.querySelector('#orderForm [name="qty"]').value)||1;
    var oriPrice=parseInt(modal.dataset.oriPrice)||0;
    var disc=parseInt(modal.dataset.disc)||0;
    var minBuy=parseInt(modal.dataset.minBuy)||0;
    var ongkir=5000;
    var subtotal=oriPrice*qty;
    // Diskon hanya aktif jika subtotal >= minBuy (atau minBuy=0)
    var discActive=disc>0&&(minBuy===0||subtotal>=minBuy);
    var discAmt=discActive?Math.round(subtotal*disc/100):0;
    var total=subtotal-discAmt+ongkir;
    document.getElementById('summarySubtotal').textContent=fmtRp(subtotal);
    var discRow=document.getElementById('summaryDiscRow');
    var minBuyRow=document.getElementById('summaryMinBuyRow');
    if(discActive){
        discRow.style.display='flex';
        document.getElementById('summaryDiscLabel').textContent='Diskon '+disc+'%';
        document.getElementById('summaryDiscAmt').textContent='- '+fmtRp(discAmt);
        if(minBuyRow)minBuyRow.style.display='none';
    } else if(disc>0&&minBuy>0&&subtotal<minBuy){
        discRow.style.display='none';
        if(minBuyRow){
            minBuyRow.style.display='flex';
            var kurang=minBuy-subtotal;
            document.getElementById('summaryMinBuyText').textContent='Tambah Rp '+kurang.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+' lagi untuk diskon '+disc+'%';
        }
    } else {
        discRow.style.display='none';
        if(minBuyRow)minBuyRow.style.display='none';
    }
    document.getElementById('summaryTotal').textContent=fmtRp(total);
    document.getElementById('inputProductPrice').value=fmtRp(subtotal-discAmt);
    // ── Simpan data diskon ke hidden input supaya terkirim via POST ──
    var discPctEl=document.getElementById('inputDiscPct');
    var discAmtEl=document.getElementById('inputDiscAmt');
    var oriPriceEl=document.getElementById('inputOriPrice');
    if(discPctEl) discPctEl.value = discActive ? disc : 0;
    if(discAmtEl) discAmtEl.value = discActive && discAmt>0 ? fmtRp(discAmt) : '';
    if(oriPriceEl) oriPriceEl.value = fmtRp(subtotal);
}

function handleBeli(name,price,img,disc,minBuy){
    // Pastikan qty picker tertutup dulu
    var qpOverlay=document.getElementById('qtyPickerOverlay');
    var qpSheet=document.getElementById('qtyPickerSheet');
    if(qpOverlay){qpOverlay.style.display='none';qpOverlay.style.opacity='0';}
    if(qpSheet){qpSheet.style.display='none';}
    document.body.style.overflow='hidden';
    disc=disc||0;
    minBuy=minBuy||0;
    var oriNum=parseRp(price);
    var meetsMin=(minBuy===0||(oriNum>=minBuy));
    var discAmt=meetsMin?Math.round(oriNum*disc/100):0;
    var finalNum=oriNum-discAmt;
    var finalPrice=fmtRp(finalNum);

    var modal=document.getElementById('orderModal');
    modal.dataset.oriPrice=oriNum;
    modal.dataset.disc=disc;
    modal.dataset.minBuy=minBuy;
    modal.dataset.cartMode='0';
    var isCartEl=document.getElementById('inputIsCart');
    if(isCartEl)isCartEl.value='0';
    var qtyInputBox=document.querySelector('#qtyField input[type="number"]');
    var qtyCartInfo=document.getElementById('qtyCartInfo');
    if(qtyInputBox){qtyInputBox.style.display='';qtyInputBox.disabled=false;qtyInputBox.value=1;}
    if(qtyCartInfo){qtyCartInfo.style.display='none';}

    document.getElementById('modalProductName').textContent=name;
    document.getElementById('modalProductNameInner').textContent=name;
    document.getElementById('modalProductImg').src=img;
    document.getElementById('orderForm').reset();
    document.getElementById('inputProductName').value=name;
    document.getElementById('inputProductImage').value=img||'';
    var isCartEl=document.getElementById('inputIsCart');
    if(isCartEl)isCartEl.value='0';

    var priceEl=document.getElementById('modalProductPrice');
    var oriEl=document.getElementById('modalProductPriceOri');
    var discBadge=document.getElementById('modalDiscBadge');
    if(disc>0&&meetsMin){
        priceEl.textContent=finalPrice;
        oriEl.textContent=price;
        oriEl.style.display='block';
        discBadge.style.display='inline-flex';
        document.getElementById('modalDiscText').textContent='Hemat '+disc+'%';
    } else {
        priceEl.textContent=price;
        oriEl.style.display='none';
        discBadge.style.display='none';
    }

    document.getElementById('orderError').style.display='none';
    modal.style.display='flex';
    updateOrderSummary();
}
function closeOrder(){document.getElementById('orderModal').style.display='none';}
document.addEventListener('DOMContentLoaded', function() {
    var orderModalEl = document.getElementById('orderModal');
    if (orderModalEl) {
        orderModalEl.addEventListener('click', function(e){ if(e.target===this) closeOrder(); });
    }
    var qtyInput = document.querySelector('#orderForm [name="qty"]');
    if (qtyInput) qtyInput.addEventListener('input', updateOrderSummary);

    var orderFormEl = document.getElementById('orderForm');
    if (!orderFormEl) return;
    orderFormEl.addEventListener('submit',function(e){
    e.preventDefault();
    var errBox=document.getElementById('orderError');
    var errText=document.getElementById('orderErrorText');
    errBox.style.display='none';

    var btn = document.getElementById('btnCodSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memproses pesanan...</span>';

    var cleanUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
    fetch(cleanUrl,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new FormData(this)})
    .then(res=>res.json())
    .then(data=>{
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shopping-bag"></i><span>Konfirmasi & Pesan Sekarang</span>';
        if(data.success){
            closeOrder();

            // ── Pesan notif ke ADMIN (dikirim dari sisi customer, WA terbuka ke nomor admin) ──
            var waAdminNum = '6289714408805';
            var custWa = (data.whatsapp || '').replace(/[^0-9]/g,'');
            if(custWa.charAt(0)==='0') custWa = '62' + custWa.slice(1);

            var msgKeAdmin = '🛍️ *PESANAN BARU - NISWÀ BEAUTY*\n\n'
                + '━━━━━━━━━━━━━━━━━━━━\n'
                + '📋 *Order ID:* #' + (data.order_id || '') + '\n'
                + '━━━━━━━━━━━━━━━━━━━━\n'
                + '👤 *Nama:* ' + (data.nama || '') + '\n'
                + '📱 *WhatsApp Customer:* ' + (data.whatsapp || '') + '\n'
                + '📍 *Alamat:* ' + (data.alamat || '') + '\n'
                + '📦 *Produk:* ' + (data.product || '') + '\n'
                + '💰 *Total Produk:* ' + (data.total || '') + '\n'
                + '🚚 *Ongkos Kirim:* Rp 5.000 (Jepara)\n'
                + '💵 *Pembayaran:* Bayar di Tempat (COD)\n'
                + '━━━━━━━━━━━━━━━━━━━━\n'
                + '⏰ ' + new Date().toLocaleString('id-ID') + '\n\n'
                + 'Mohon segera konfirmasi pesanan ke customer. Terima kasih! 🙏';

            var waAdminUrl = 'https://wa.me/' + waAdminNum + '?text=' + encodeURIComponent(msgKeAdmin);

            // Simpan URL admin (untuk internal use jika dibutuhkan)
            var custBtn = document.getElementById('successWaCustomerBtn');
            if(custBtn) custBtn.style.display = 'none';

            document.getElementById('successOrder').style.display='flex';
            this.reset();
        } else {
            errText.innerHTML = data.message || 'Terjadi kesalahan.';
            errBox.style.display='block';
        }
    })
    .catch((err)=>{
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shopping-bag"></i><span>Konfirmasi & Pesan Sekarang</span>';
        errText.innerHTML = 'Terjadi kesalahan koneksi: ' + err;
        errBox.style.display = 'block';
    });
    });
}); // end DOMContentLoaded

function selectPayment(method, el) {
    document.querySelectorAll('.cod-method-card').forEach(function(c){ c.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('inputPaymentMethod').value = method;
    var summaryPayMethod = document.getElementById('summaryPayMethod');
    if (summaryPayMethod) summaryPayMethod.textContent = method;
}

function showProductPreview(name,price,img,disc){
    disc=disc||0;
    document.getElementById('ppImg').src=img;
    document.getElementById('ppName').textContent=name;
    document.getElementById('ppPrice').textContent=price;
    document.getElementById('ppBeli').onclick=function(){closeProductPreview();handleBeli(name,price,img,disc);};
    document.getElementById('productPreviewModal').style.display='flex';
}
function closeProductPreview(){document.getElementById('productPreviewModal').style.display='none';}
</script>

<!-- Testimoni Carousel -->
<script>
(function(){
    var track=document.getElementById('testimoniTrack');
    var dotsWrap=document.getElementById('testimoniDots');
    if(!track)return;

    var origCards=Array.from(track.querySelectorAll('.testimoni-card'));
    var current=0;
    var autoTimer=null;

    function getPerView(){
        return window.innerWidth>=992?3:window.innerWidth>=600?2:1;
    }

    function buildGroups(){
        var pv=getPerView();
        track.innerHTML='';
        dotsWrap.innerHTML='';

        // Bagi card ke grup
        var groups=[];
        for(var i=0;i<origCards.length;i+=pv){
            groups.push(origCards.slice(i,i+pv));
        }
        var total=groups.length;

        // Set lebar track = total grup × 100%
        track.style.width=(total*100)+'%';

        groups.forEach(function(grp,gi){
            var g=document.createElement('div');
            g.className='testimoni-group';
            g.style.width=(100/total)+'%';
            // Isi card — jika grup terakhir kurang, tambah placeholder agar rata
            grp.forEach(function(c){ g.appendChild(c.cloneNode(true)); });
            var missing=pv-grp.length;
            for(var m=0;m<missing;m++){
                var ph=document.createElement('div');
                ph.className='testimoni-card';
                ph.style.visibility='hidden';
                g.appendChild(ph);
            }
            track.appendChild(g);

            // Buat dot
            var dot=document.createElement('span');
            dot.className='testimoni-dot'+(gi===0?' active':'');
            dot.dataset.i=gi;
            dot.addEventListener('click',function(){
                stopAuto();
                goTo(+this.dataset.i);
                startAuto();
            });
            dotsWrap.appendChild(dot);
        });

        current=0;
        track.style.transform='translateX(0)';
    }

    function goTo(idx){
        var total=track.querySelectorAll('.testimoni-group').length;
        current=((idx%total)+total)%total;
        track.style.transform='translateX(-'+(current*(100/total))+'%)';
        dotsWrap.querySelectorAll('.testimoni-dot').forEach(function(d,i){
            d.classList.toggle('active',i===current);
        });
    }

    function startAuto(){
        clearInterval(autoTimer);
        autoTimer=setInterval(function(){
            var total=track.querySelectorAll('.testimoni-group').length;
            goTo((current+1)%total);
        },4000);
    }
    function stopAuto(){ clearInterval(autoTimer); }

    function init(){
        buildGroups();
        startAuto();
    }

    if(document.readyState==='complete'){ init(); }
    else{ window.addEventListener('load', init); }

    var resizeTimer;
    window.addEventListener('resize',function(){
        clearTimeout(resizeTimer);
        resizeTimer=setTimeout(function(){
            stopAuto(); buildGroups(); startAuto();
        },200);
    });

    /* ── Touch / Swipe Support ── */
    var touchStartX=0, touchEndX=0;
    track.addEventListener('touchstart',function(e){
        touchStartX=e.changedTouches[0].screenX;
        stopAuto();
    },{passive:true});
    track.addEventListener('touchend',function(e){
        touchEndX=e.changedTouches[0].screenX;
        var diff=touchStartX-touchEndX;
        if(Math.abs(diff)>40){
            var total=track.querySelectorAll('.testimoni-group').length;
            if(diff>0){ goTo((current+1)%total); }
            else { goTo((current-1+total)%total); }
        }
        startAuto();
    },{passive:true});
})();
</script>

<!-- ══ GLOBAL DISCOUNT CONFIG ══ -->
<script>
var NISWAGD = {
    enabled:      <?= $globalDisc['enabled'] ? 'true' : 'false' ?>,
    discount_pct: <?= $globalDisc['discount_pct'] ?>,
    min_purchase: <?= $globalDisc['min_purchase'] ?>,
    label:        <?= str_replace(["\u{2028}", "\u{2029}", '</'], ['\u2028', '\u2029', '<\/'], json_encode($globalDisc['label'])) ?>
};
</script>

<!-- ══ CART SCRIPT — Shopee Style ══ -->
<script>
var NiswaCart=(function(){
    var STORAGE_KEY='niswa_cart_v2';
    var items=[];
    function load(){try{items=JSON.parse(localStorage.getItem(STORAGE_KEY)||'[]');}catch(e){items=[];}}
    function save(){try{localStorage.setItem(STORAGE_KEY,JSON.stringify(items));}catch(e){};}
    function totalQty(){return items.reduce(function(s,i){return s+i.qty;},0);}
    function selectedItems(){return items.filter(function(i){return i.selected;});}
    // Hitung total harga asli (sebelum diskon apapun)
    function selectedOriTotal(){
        return selectedItems().reduce(function(s,i){
            var oriNum=i.oriNum||parsePrice(i.oriPrice||i.price);
            return s+(oriNum*i.qty);
        },0);
    }
    // Cek apakah diskon global aktif berdasarkan total cart
    function globalDiscActive(oriTotal){
        if(!NISWAGD||!NISWAGD.enabled||NISWAGD.discount_pct<=0) return false;
        if(NISWAGD.min_purchase>0 && oriTotal<NISWAGD.min_purchase) return false;
        return true;
    }
    function selectedTotal(){
        var sel=selectedItems();
        var oriTot=selectedOriTotal();
        // Terapkan diskon global jika aktif (override per-produk)
        if(globalDiscActive(oriTot)){
            return oriTot - Math.round(oriTot * NISWAGD.discount_pct / 100);
        }
        // Fallback: diskon per-produk
        return sel.reduce(function(s,i){
            var oriNum=i.oriNum||parsePrice(i.oriPrice||i.price);
            var minBuy=i.minBuy||0;
            var subtotalItem=oriNum*i.qty;
            var discActive=i.disc>0&&(minBuy===0||subtotalItem>=minBuy);
            var discAmt=discActive?Math.round(subtotalItem*i.disc/100):0;
            return s+(subtotalItem-discAmt);
        },0);
    }
    function parsePrice(str){var nums=(str+'').match(/[\d.]+/g);if(!nums)return 0;return parseInt(nums[0].replace(/\./g,''),10)||0;}
    function fmt(n){return 'Rp '+n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
    function escHtml(str){return(str+'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
    function add(name,price,img,type,disc,minBuy){
        disc=parseInt(disc)||0;
        minBuy=parseInt(minBuy)||0;
        var oriNum=parsePrice(price);
        // Diskon aktif hanya jika memenuhi minimum (cek per 1 item dulu, akan di-recheck saat qty berubah)
        var meetsMin=(minBuy===0||(oriNum>=minBuy));
        var discAmt=meetsMin?Math.round(oriNum*disc/100):0;
        var numPrice=oriNum-discAmt;
        var finalPrice=(disc>0&&meetsMin)?fmt(numPrice):price;
        var existing=items.find(function(i){return i.name===name&&i.type===type;});
        if(existing){existing.qty++;existing.selected=true;}
        else{items.push({name:name,price:finalPrice,oriPrice:price,numPrice:numPrice,oriNum:oriNum,disc:disc,minBuy:minBuy,img:img||'',type:type||'Produk',qty:1,selected:true});}
        save();render();showToast(name,img);
    }
    function addWithQty(name,price,img,type,disc,minBuy,qty){
        qty=parseInt(qty)||1;
        disc=parseInt(disc)||0;
        minBuy=parseInt(minBuy)||0;
        var oriNum=parsePrice(price);
        var meetsMin=(minBuy===0||(oriNum*qty>=minBuy));
        var discAmt=meetsMin?Math.round(oriNum*disc/100):0;
        var numPrice=oriNum-discAmt;
        var finalPrice=(disc>0&&meetsMin)?fmt(numPrice):price;
        var existing=items.find(function(i){return i.name===name&&i.type===type;});
        if(existing){existing.qty+=qty;existing.selected=true;}
        else{items.push({name:name,price:finalPrice,oriPrice:price,numPrice:numPrice,oriNum:oriNum,disc:disc,minBuy:minBuy,img:img||'',type:type||'Produk',qty:qty,selected:true});}
        save();render();showToast(name,img);
    }
    function setQty(idx,qty){
        if(qty<1){if(confirm('Hapus "'+items[idx].name+'" dari keranjang?')){remove(idx);}return;}
        items[idx].qty=qty;save();render();
    }
    function remove(idx){items.splice(idx,1);save();render();}
    function removeSelected(){items=items.filter(function(i){return !i.selected;});save();render();}
    function clear(){items=[];save();render();}
    function toggleSelect(idx,val){items[idx].selected=val;save();render();}
    function toggleSelectAll(val){items.forEach(function(i){i.selected=val;});save();render();}
    function render(){
        var badge=document.getElementById('cart-badge');
        var qty=totalQty();
        if(badge){badge.textContent=qty;badge.style.display=qty>0?'flex':'none';}
        var listEl=document.getElementById('cart-item-list');
        var emptyEl=document.getElementById('cart-empty');
        var footerEl=document.getElementById('cart-footer');
        var selectBar=document.getElementById('cart-select-bar');
        var countLabel=document.getElementById('cart-count-label');
        if(!listEl)return;
        if(countLabel)countLabel.textContent=qty>0?'('+qty+')':'';
        listEl.innerHTML='';
        if(items.length===0){
            if(emptyEl)emptyEl.style.display='flex';
            if(footerEl)footerEl.style.display='none';
            if(selectBar)selectBar.style.display='none';
            return;
        }
        if(emptyEl)emptyEl.style.display='none';
        if(footerEl)footerEl.style.display='block';
        if(selectBar)selectBar.style.display='flex';
        items.forEach(function(item,idx){
            var div=document.createElement('div');div.className='cart-item';
            var imgHtml=item.img?'<img src="'+escHtml(item.img)+'" class="cart-item-img" alt="">':'<div class="cart-item-img cart-item-img-placeholder"><i class="fas fa-spa"></i></div>';
            var oriNum=item.oriNum||parsePrice(item.oriPrice||item.price);
            var minBuy=item.minBuy||0;
            var subtotalItem=oriNum*item.qty;
            var discActive=item.disc>0&&(minBuy===0||subtotalItem>=minBuy);
            var discAmt=discActive?Math.round(subtotalItem*item.disc/100):0;
            var displayPrice=discActive?fmt(oriNum-Math.round(oriNum*item.disc/100)):fmt(oriNum);
            // Badge diskon
            var discBadge='';
            if(item.disc>0){
                if(discActive){
                    discBadge='<span style="background:#fff3e0;color:#c97000;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:Poppins,sans-serif;display:inline-block;margin-bottom:4px;"><i class="fas fa-tag"></i> Hemat '+item.disc+'%</span>';
                } else {
                    var kurang=minBuy-subtotalItem;
                    discBadge='<span style="background:#fff5f5;color:#e74c3c;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;font-family:Poppins,sans-serif;display:inline-block;margin-bottom:4px;border:1px dashed #f5b7b1;"><i class="fas fa-lock"></i> Tambah Rp '+kurang.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+' untuk diskon '+item.disc+'%</span>';
                }
            }
            var priceHtml=item.disc>0
                ?'<div style="display:flex;align-items:center;gap:6px;"><span class="cart-item-price">'+escHtml(displayPrice)+'</span>'+(discActive?'<span style="font-size:11px;color:#bbb;text-decoration:line-through;font-family:Poppins,sans-serif;">'+escHtml(fmt(oriNum))+'</span>':'')+'</div>'
                :'<div class="cart-item-price">'+escHtml(displayPrice)+'</div>';
            div.innerHTML='<div class="cart-item-check"><input type="checkbox" '+(item.selected?'checked':'')+' onchange="NiswaCart.toggleSelect('+idx+',this.checked)"></div>'+
                imgHtml+
                '<div class="cart-item-info">'+
                '<div class="cart-item-name">'+escHtml(item.name)+'</div>'+
                '<span class="cart-item-type">'+escHtml(item.type)+'</span>'+
                priceHtml+
                discBadge+
                '<div class="cart-item-bottom">'+
                '<div class="cart-qty-wrap">'+
                '<button class="cart-qty-btn" onclick="NiswaCart.setQty('+idx+','+(item.qty-1)+')">-</button>'+
                '<span class="cart-qty-val">'+item.qty+'</span>'+
                '<button class="cart-qty-btn" onclick="NiswaCart.setQty('+idx+','+(item.qty+1)+')">+</button>'+
                '</div>'+
                '<button class="cart-remove-btn" onclick="NiswaCart.remove('+idx+')"><i class="fas fa-trash-alt"></i></button>'+
                '</div></div>';
            listEl.appendChild(div);
        });
        var selAll=document.getElementById('cart-select-all');
        if(selAll)selAll.checked=items.length>0&&items.every(function(i){return i.selected;});
        var sel=selectedItems();
        var oriTot=selectedOriTotal();
        var selTot=selectedTotal();
        var selInfo=document.getElementById('cart-selected-info');
        var selSub=document.getElementById('cart-selected-subtotal');
        var totalEl=document.getElementById('cart-total');
        var chkBtn=document.getElementById('cart-checkout-btn');
        var chkCount=document.getElementById('cart-checkout-count');
        if(selInfo)selInfo.textContent=sel.length+' item dipilih';
        if(selSub)selSub.textContent=sel.length>0?fmt(selTot):'';
        if(totalEl)totalEl.textContent=fmt(selTot);
        if(chkCount)chkCount.textContent=sel.reduce(function(s,i){return s+i.qty;},0);
        if(chkBtn)chkBtn.disabled=sel.length===0;
        // Tampilkan/hide promo global di cart footer
        var gdBox=document.getElementById('cart-gd-promo');
        if(gdBox){
            if(NISWAGD&&NISWAGD.enabled&&NISWAGD.discount_pct>0&&sel.length>0){
                var kurangGd=NISWAGD.min_purchase>0?Math.max(0,NISWAGD.min_purchase-oriTot):0;
                if(globalDiscActive(oriTot)){
                    gdBox.innerHTML='<i class="fas fa-tag" style="color:#c97000;margin-right:5px;"></i><span style="color:#7a5000;font-weight:700;font-size:11.5px;">Diskon '+NISWAGD.discount_pct+'% aktif! Hemat '+fmt(oriTot-selTot)+'</span>';
                    gdBox.style.background='#fff8ee';gdBox.style.borderColor='#f5dbb5';
                } else if(kurangGd>0){
                    gdBox.innerHTML='<i class="fas fa-fire" style="color:#e05c00;margin-right:5px;"></i><span style="color:#7a5000;font-size:11px;font-weight:600;">Tambah <strong>Rp '+kurangGd.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')+'</strong> lagi untuk diskon '+NISWAGD.discount_pct+'%!</span>';
                    gdBox.style.background='#fff3e0';gdBox.style.borderColor='#f5c27f';
                } else {
                    gdBox.innerHTML='';
                }
                gdBox.style.display='block';
            } else {
                gdBox.style.display='none';
            }
        }
    }
    function checkout(){
        var sel=selectedItems();if(sel.length===0)return;
        var names=sel.map(function(i){return i.name+' x'+i.qty;}).join(', ');
        var selTot=selectedTotal();
        // Hitung total sebelum diskon dan total diskon
        var oriTot=selectedOriTotal();
        var totalDisc=oriTot-selTot;
        var total=fmt(selTot);var firstImg=sel[0].img||'';
        var pNameEl=document.getElementById('inputProductName');
        var pPriceEl=document.getElementById('inputProductPrice');
        var modalNameEl=document.getElementById('modalProductName');
        var modalNameInEl=document.getElementById('modalProductNameInner');
        var modalPriceEl=document.getElementById('modalProductPrice');
        var modalOriEl=document.getElementById('modalProductPriceOri');
        var modalDiscBadge=document.getElementById('modalDiscBadge');
        var modalImgEl=document.getElementById('modalProductImg');
        if(pNameEl)pNameEl.value=names;if(pPriceEl)pPriceEl.value=total;
        if(modalNameEl)modalNameEl.textContent='Pesanan ('+sel.length+' item)';
        if(modalNameInEl)modalNameInEl.textContent=names;
        if(modalPriceEl)modalPriceEl.textContent=total;
        if(modalOriEl){
            if(totalDisc>0){modalOriEl.textContent=fmt(oriTot);modalOriEl.style.display='block';}
            else{modalOriEl.style.display='none';}
        }
        if(modalDiscBadge){
            if(totalDisc>0){
                modalDiscBadge.style.display='inline-flex';
                document.getElementById('modalDiscText').textContent='Hemat '+fmt(totalDisc);
            } else {modalDiscBadge.style.display='none';}
        }
        if(modalImgEl&&firstImg)modalImgEl.src=firstImg;
        var form=document.getElementById('orderForm');if(form)form.reset();
        if(pNameEl)pNameEl.value=names;if(pPriceEl)pPriceEl.value=total;
        // ── Simpan data diskon cart ke hidden input ──
        var discPctEl=document.getElementById('inputDiscPct');
        var discAmtEl=document.getElementById('inputDiscAmt');
        var oriPriceEl=document.getElementById('inputOriPrice');
        if(discPctEl) discPctEl.value = totalDisc>0 ? 'cart' : 0;
        if(discAmtEl) discAmtEl.value = totalDisc>0 ? fmt(totalDisc) : '';
        if(oriPriceEl) oriPriceEl.value = fmt(oriTot);
        var imgInputEl=document.getElementById('inputProductImage');
        if(imgInputEl){
            var allImgs=sel.map(function(i){return i.img||'';}).filter(function(x){return x!='';});
            var uniqueImgs=allImgs.filter(function(v,i,a){return a.indexOf(v)===i;});
            imgInputEl.value=uniqueImgs.length>0?JSON.stringify(uniqueImgs):'';
        }
        // Set qty dari total item keranjang yang dipilih
        var totalQtySelected=sel.reduce(function(s,i){return s+i.qty;},0);
        var inputQtyEl=document.getElementById('inputQty');
        var qtyInputBox=document.querySelector('#qtyField input[type="number"]');
        var qtyCartInfo=document.getElementById('qtyCartInfo');
        var qtyCartText=document.getElementById('qtyCartText');
        if(inputQtyEl){inputQtyEl.value=totalQtySelected;}
        if(qtyInputBox){qtyInputBox.style.display='none';qtyInputBox.disabled=true;}
        if(qtyCartInfo){
            qtyCartInfo.style.display='flex';
            var itemLabel=sel.map(function(i){return i.name+(i.qty>1?' x'+i.qty:'');}).join(', ');
            if(qtyCartText)qtyCartText.textContent=totalQtySelected+' item — '+itemLabel;
        }
        // Update summary box
        var summarySubtotal=document.getElementById('summarySubtotal');
        var summaryDiscRow=document.getElementById('summaryDiscRow');
        var summaryDiscAmt=document.getElementById('summaryDiscAmt');
        var summaryDiscLabel=document.getElementById('summaryDiscLabel');
        var summaryTotal=document.getElementById('summaryTotal');
        var ongkir=5000;
        if(summarySubtotal)summarySubtotal.textContent=fmt(oriTot);
        if(summaryDiscRow){
            if(totalDisc>0){
                summaryDiscRow.style.display='flex';
                if(summaryDiscLabel)summaryDiscLabel.textContent='Total Diskon';
                if(summaryDiscAmt)summaryDiscAmt.textContent='- '+fmt(totalDisc);
            } else {summaryDiscRow.style.display='none';}
        }
        if(summaryTotal)summaryTotal.textContent=fmt(selTot+ongkir);
        // Set modal dataset untuk updateOrderSummary (nonaktifkan auto-update qty untuk cart)
        var modal=document.getElementById('orderModal');
        if(modal){modal.dataset.oriPrice=0;modal.dataset.disc=0;modal.dataset.cartMode='1';}
        var isCartEl=document.getElementById('inputIsCart');
        if(isCartEl)isCartEl.value='1';
        var errBox=document.getElementById('orderError');if(errBox)errBox.style.display='none';
        closeCart();
        var orderModal=document.getElementById('orderModal');if(orderModal)orderModal.style.display='flex';
    }
    function openCart(){var s=document.getElementById('cart-sidebar');var o=document.getElementById('cart-overlay');if(s)s.classList.add('open');if(o)o.classList.add('open');document.body.style.overflow='hidden';}
    function closeCart(){var s=document.getElementById('cart-sidebar');var o=document.getElementById('cart-overlay');if(s)s.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}
    function showToast(name,img){
        var ex=document.getElementById('cart-toast');if(ex)ex.remove();
        var t=document.createElement('div');t.id='cart-toast';t.className='cart-toast';
        var imgTag=img?'<img src="'+escHtml(img)+'" class="toast-img">':'<i class="fas fa-shopping-cart"></i>';
        t.innerHTML=imgTag+'<span>'+escHtml(name)+'</span><span style="color:#D6C1A3;font-size:11px;margin-left:4px;">+ Keranjang</span>';
        document.body.appendChild(t);
        setTimeout(function(){t.classList.add('show');},10);
        setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove();},400);},2800);
    }
    function init(){
        load();render();
        var fab=document.getElementById('cart-fab');if(fab)fab.addEventListener('click',openCart);
        var overlay=document.getElementById('cart-overlay');if(overlay)overlay.addEventListener('click',closeCart);
        var closeBtn=document.getElementById('cart-close-btn');if(closeBtn)closeBtn.addEventListener('click',closeCart);
        var chkBtn=document.getElementById('cart-checkout-btn');if(chkBtn)chkBtn.addEventListener('click',checkout);
        var clearBtn=document.getElementById('cart-clear-btn');if(clearBtn)clearBtn.addEventListener('click',function(){
            var sel=selectedItems();
            if(sel.length===0){alert('Pilih item yang ingin dihapus terlebih dahulu.');return;}
            if(confirm('Hapus '+sel.length+' item yang dipilih?'))removeSelected();
        });
        var successOverlay=document.getElementById('successOrder');
        if(successOverlay){var obs=new MutationObserver(function(){if(successOverlay.style.display==='flex'){items=items.filter(function(i){return !i.selected;});save();render();}});obs.observe(successOverlay,{attributes:true,attributeFilter:['style']});}
    }
    return{add:add,addWithQty:addWithQty,remove:remove,setQty:setQty,clear:clear,toggleSelect:toggleSelect,toggleSelectAll:toggleSelectAll,openCart:openCart,closeCart:closeCart,init:init};
})();
document.addEventListener('DOMContentLoaded',function(){NiswaCart.init();});
</script>

<!-- ══ QTY PICKER SCRIPT ══ -->
<script>
(function(){
    var _name='',_price='',_img='',_type='',_disc=0,_minBuy=0,_qty=1,_oriNum=0;

    function parseP(s){var m=(s+'').match(/[\d.]+/g);return m?parseInt(m[0].replace(/\./g,''),10)||0:0;}
    function fmtP(n){return 'Rp '+n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');}

    function refreshUI(){
        var subtotalOri = _oriNum * _qty;
        var discActive  = _disc > 0 && (_minBuy === 0 || subtotalOri >= _minBuy);
        var discAmt     = discActive ? Math.round(subtotalOri * _disc / 100) : 0;
        var subtotal    = subtotalOri - discAmt;

        document.getElementById('qpQtyVal').textContent  = _qty;
        document.getElementById('qpSubtotal').textContent = fmtP(subtotal);

        var priceEl = document.getElementById('qpPrice');
        if(_disc > 0 && discActive){
            var perItem = _oriNum - Math.round(_oriNum * _disc / 100);
            priceEl.textContent = fmtP(perItem);
        } else {
            priceEl.textContent = fmtP(_oriNum);
        }

        document.getElementById('qpAddBtnText').textContent = 'Tambah ' + _qty + ' ke Keranjang';
    }

    window.openQtyPicker = function(name,price,img,type,disc,minBuy){
        _name   = name;
        _price  = price;
        _img    = img;
        _type   = type  || 'Produk';
        _disc   = parseInt(disc)   || 0;
        _minBuy = parseInt(minBuy) || 0;
        _oriNum = parseP(price);
        _qty    = 1;

        document.getElementById('qpImg').src  = img  || '';
        document.getElementById('qpName').textContent = name;

        refreshUI();

        var overlay = document.getElementById('qtyPickerOverlay');
        var sheet   = document.getElementById('qtyPickerSheet');
        overlay.style.display = 'block';
        sheet.style.display   = 'block';
        sheet.getBoundingClientRect();
        overlay.style.opacity = '1';
        sheet.style.opacity   = '1';
        sheet.style.transform = 'translateX(-50%) translateY(0)';
        document.body.style.overflow = 'hidden';
    };

    window.closeQtyPicker = function(){
        var overlay = document.getElementById('qtyPickerOverlay');
        var sheet   = document.getElementById('qtyPickerSheet');
        sheet.style.opacity   = '0';
        sheet.style.transform = 'translateX(-50%) translateY(16px)';
        overlay.style.opacity = '0';
        setTimeout(function(){
            sheet.style.display   = 'none';
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }, 280);
    };

    window.qpChangeQty = function(delta){
        _qty = Math.max(1, Math.min(99, _qty + delta));
        refreshUI();
    };

    window.qpConfirmAdd = function(){
        var btn = document.getElementById('qpAddBtn');
        var txt = document.getElementById('qpAddBtnText');
        btn.style.background = 'linear-gradient(135deg,#6e5549,#b89e85)';
        txt.textContent = '✓ Ditambahkan!';
        setTimeout(function(){
            NiswaCart.addWithQty(_name, _price, _img, _type, _disc, _minBuy, _qty);
            closeQtyPicker();
            btn.style.background = '';
            txt.textContent = 'Tambah ke Keranjang';
        }, 380);
    };
})();
</script>