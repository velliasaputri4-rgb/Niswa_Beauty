<?php
session_start();

/* ── Auth Guard ── */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

/* ── DB Connection ── */
$conn = mysqli_connect("localhost", "root", "", "salon_db");
if ($conn) mysqli_set_charset($conn, 'utf8mb4');

/* ══════════════════════════════════════════════
   ENSURE ALL TABLES EXIST
══════════════════════════════════════════════ */
if ($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(80) NOT NULL,
        `key` VARCHAR(120) NOT NULL,
        value LONGTEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_section_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120),
        image VARCHAR(255),
        gallery TEXT,
        sort_order INT DEFAULT 0,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(120),
        name VARCHAR(200),
        price VARCHAR(60),
        description TEXT DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // AUTO-ADD kolom description jika tabel lama belum punya
    $chkDesc = mysqli_query($conn, "SHOW COLUMNS FROM cms_prices LIKE 'description'");
    if ($chkDesc && mysqli_num_rows($chkDesc) === 0) {
        mysqli_query($conn, "ALTER TABLE cms_prices ADD COLUMN description TEXT DEFAULT NULL AFTER price");
    }
    // AUTO-ADD kolom image jika tabel lama belum punya
    $chkImg = mysqli_query($conn, "SHOW COLUMNS FROM cms_prices LIKE 'image'");
    if ($chkImg && mysqli_num_rows($chkImg) === 0) {
        mysqli_query($conn, "ALTER TABLE cms_prices ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description");
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150),
        price VARCHAR(60),
        category VARCHAR(60),
        image VARCHAR(255),
        sort_order INT DEFAULT 0,
        discount_pct TINYINT UNSIGNED DEFAULT 0,
        min_purchase INT UNSIGNED DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tambah kolom diskon jika belum ada (untuk database lama)
    $chkDisc = mysqli_query($conn, "SHOW COLUMNS FROM cms_products LIKE 'discount_pct'");
    if (mysqli_num_rows($chkDisc) === 0) {
        mysqli_query($conn, "ALTER TABLE cms_products ADD COLUMN discount_pct TINYINT UNSIGNED DEFAULT 0 AFTER sort_order");
        mysqli_query($conn, "ALTER TABLE cms_products ADD COLUMN min_purchase INT UNSIGNED DEFAULT 0 AFTER discount_pct");
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        service_tag VARCHAR(100),
        text TEXT,
        avatar_color VARCHAR(120) DEFAULT 'linear-gradient(135deg,#f9a8d4,#f472b6)',
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_profil (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(80) NOT NULL,
        `key` VARCHAR(120) NOT NULL,
        value LONGTEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_hero_slides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(255),
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_navbar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(80) NOT NULL,
        `key` VARCHAR(120) NOT NULL,
        value LONGTEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_nav_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_footer (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(80) NOT NULL,
        `key` VARCHAR(120) NOT NULL,
        value LONGTEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_foot_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_booking_page (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section VARCHAR(80) NOT NULL,
        `key` VARCHAR(120) NOT NULL,
        value LONGTEXT,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uk_bkpg_sec_key (section, `key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ── AUTO-SEED: isi default jika tabel masih kosong ── */
    $cntSvc = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_services"))['c'] ?? 0);
    if ($cntSvc === 0) {
        $defaultSvcSeed = [
            ['Haircut',       'image/download (9).jpg',                          'image/download (9).jpg,image/I LOVE HAIRSTYLE __.jpg,image/Long layers cutting_ (1).jpg,image/download (10).jpg'],
            ['Coloring',      'image/WhatsApp Image 2026-05-08 at 11.00.07.jpeg','image/WhatsApp Image 2026-05-08 at 11.00.07.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.07 (1).jpeg,image/WhatsApp Image 2026-05-08 at 11.00.08.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11.jpeg,image/WhatsApp Image 2026-05-08 at 11.00.11 (1).jpeg'],
            ['Nailart',       'image/Fall nails brown nails inspo.jpg',          'image/Fall nails brown nails inspo.jpg,image/download (11).jpg,image/download (12).jpg,image/download (13).jpg,image/download (14).jpg'],
            ['Hair Treatment','image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg','image/WhatsApp Image 2026-05-08 at 22.08.31.jpeg,image/Keratin Hair Transformation 💫 Before & After.jpg'],
            ['Foot SPA',      'image/download (8).jpg',                          'image/download (8).jpg,image/footspa.jpeg'],
            ['Henna Series',  'image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg','image/WhatsApp Image 2026-05-08 at 11.03.43.jpeg,image/WhatsApp Image 2026-05-08 at 11.05.55.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (1).jpeg,image/WhatsApp Image 2026-05-08 at 22.06.29 (2).jpeg'],
            ['Press on Nail', 'image/download (6).jpg',                          'image/download (6).jpg,image/download (15).jpg'],
            ['Eye Lash',      'image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg','image/WhatsApp Image 2026-05-08 at 22.14.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.29.jpeg,image/WhatsApp Image 2026-05-08 at 22.16.30.jpeg'],
        ];
        $so = 1;
        foreach ($defaultSvcSeed as [$n,$img,$gal]) {
            $n=mysqli_real_escape_string($conn,$n); $img=mysqli_real_escape_string($conn,$img); $gal=mysqli_real_escape_string($conn,$gal);
            mysqli_query($conn,"INSERT INTO cms_services (name,image,gallery,sort_order) VALUES ('$n','$img','$gal',$so)");
            $so++;
        }
    }

    $cntPrc = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_prices"))['c'] ?? 0);
    // [category, name, price, description]  — deskripsi selaras dengan priceDescriptions di index.php
    $defaultPrcSeed = [
        ['Henna Series',       'Brow Henna',                      'Rp 25.000',             'Pewarnaan alis dengan henna alami yang tahan lama. Mengisi alis tipis dan memberikan tampilan tegas, natural, dan rapi.'],
        ['Henna Series',       'Nail Henna Tangan',               'Rp 25.000',             'Motif henna indah di kuku & tangan menggunakan bahan alami. Cocok untuk acara formal maupun casual.'],
        ['Henna Series',       'Nail Henna Kaki',                 'Rp 30.000',             'Desain henna elegan di area kuku dan kaki. Bahan aman, cocok untuk semua usia.'],
        ['Henna Series',       'Bundling Meni-Henna',             'Rp 75.000',             'Paket hemat manicure lengkap + nail henna. Dua layanan kecantikan dalam satu sesi.'],
        ['Henna Series',       'Henna Fun',                       'Rp 25.000 - 100.000',   'Henna dekoratif di tangan dengan berbagai motif pilihan. Semakin kompleks motif, semakin artistik hasilnya.'],
        ['Treatment Spa',      'Bundling Manicure & Pedicure',    'Rp 100.000',            'Paket lengkap perawatan tangan & kaki: scrub, masker, pemotongan kuku, dan finishing oil.'],
        ['Treatment Spa',      'Manicure / Pedicure',             'Rp 60.000',             'Perawatan kuku dan kulit tangan atau kaki dengan teknik profesional. Kulit lebih lembut, kuku lebih sehat.'],
        ['Treatment Spa',      'Hand Spa',                        'Rp 80.000',             'Perawatan intensif tangan: scrub eksfoliasi, masker pelembap, dan pijat relaksasi. Tangan terasa lembut & cerah.'],
        ['Treatment Spa',      'Foot Spa',                        'Rp 100.000',            'Terapi kaki lengkap mulai dari perendaman, scrub, masker, hingga pijat refleksi. Cocok setelah hari panjang.'],
        ['Treatment Spa',      'Callus Treatment',                'Rp 70.000 - 150.000',   'Pengangkatan kapalan dan kulit keras di telapak kaki secara profesional. Makin tebal kalus, makin intensif perawatannya.'],
        ['Brow & Lash',        'Brow Bomb',                       'Rp 100.000',            'Perawatan alis all-in-one: lifting, tinting, dan setting. Alis tampak tebal, tegas, dan terbentuk sempurna tanpa makeup.'],
        ['Brow & Lash',        'Lashlift',                        'Rp 70.000',             'Keriting bulu mata permanen tanpa sambungan. Mata terlihat lebih besar dan terbuka secara alami hingga 6–8 minggu.'],
        ['Brow & Lash',        'Lashlift Tint',                   'Rp 90.000',             'Lashlift plus pewarnaan bulu mata agar lebih gelap dan dramatis. Tanpa maskara pun sudah memukau.'],
        ['Rambut',             'Creambath',                       'Rp 75.000',             'Perawatan rambut dengan krim nutrisi, pijat kepala, dan uap hangat. Rambut lebih lebat, lembut, dan berkilau.'],
        ['Rambut',             'Hair Mask',                       'Rp 45.000 - 90.000',    'Masker rambut intensif sesuai jenis rambut. Menutrisi dari dalam, mengurangi frizz, dan mengembalikan kilau alami.'],
        ['Rambut',             'Hair Spa',                        'Rp 100.000',            'Spa rambut lengkap: shampo, kondisioner, masker, uap, dan pijat. Solusi untuk rambut rusak & kering.'],
        ['Rambut',             'Cuci,Catok,Blow',                 'Rp 25.000 - 50.000',    'Cuci rambut + blow dry atau catok sesuai selera. Rambut bersih, rapi, dan siap tampil.'],
        ['Rambut',             'Bleaching S',                     'Rp 40.000',             'Bleaching parsial (highlight/poni) untuk mencerahkan area tertentu. Cocok untuk warna pastel atau ombre.'],
        ['Rambut',             'Coloring Full',                   'Rp 120.000 - 300.000',  'Pewarnaan rambut penuh dari akar hingga ujung. Pilihan warna beragam, hasil merata dan tahan lama.'],
        ['Rambut',             'Bleaching',                       'Rp 200.000 - 1.200.000','Bleaching full atau intensif untuk mengangkat pigmen rambut. Harga tergantung panjang dan ketebalan rambut.'],
        ['Rambut',             'Balayage',                        'Rp 250.000 - 700.000',  'Teknik pewarnaan gradasi tangan bebas yang menghasilkan tampilan natural sun-kissed. Setiap hasil unik dan personal.'],
        ['Rambut',             'Down Peim Poni',                  'Rp 100.000 - 300.000',  'Pelurus poni dengan teknik perm down. Poni turun rapi tahan lama tanpa perlu di-styling setiap hari.'],
        ['Rambut',             'Keriting Klasik',                 'Rp 300.000 - 700.000',  'Keriting permanen dengan batang spiral klasik. Cocok untuk tampilan volume dan berkarakter.'],
        ['Rambut',             'Keriting Digital',                'Rp 450.000 - 1.700.000','Keriting digital dengan alat pemanas modern. Hasil lebih bergelombang lembut, tahan lama, dan terlihat natural.'],
        ['Rambut',             'Keratin Treatment',               'Rp 200.000',            'Perawatan keratin untuk melembutkan dan meluruskan rambut secara alami. Mengurangi frizz & mudah diatur.'],
        ['Rambut',             'Smoothing',                       'Rp 200.000 - 400.000',  'Pelurusan rambut semi-permanen yang membuat rambut lurus, halus, dan mudah di-styling. Tahan 3–6 bulan.'],
        ['Nail Art & Services','Press On Nail Basic',             'Rp 50.000',             'Press on nail siap pakai dengan desain simpel dan elegan. Mudah dipasang sendiri, tahan beberapa hari.'],
        ['Nail Art & Services','Press On Nail Motif',             'Rp 75.000',             'Press on nail dengan motif artistik dan detail lebih kompleks. Cocok untuk event spesial.'],
        ['Nail Art & Services','Kids Basic Gel',                  'Rp 40.000',             'Gel kuku aman khusus anak-anak. Warna solid lembut yang tahan lama dan tidak berbau menyengat.'],
        ['Nail Art & Services','Kids Gel + 4 Sticker',            'Rp 50.000',             'Gel warna + 4 stiker kuku pilihan anak. Tampilan lucu dan menggemaskan.'],
        ['Nail Art & Services','Kids Gel + Full Sticker',         'Rp 55.000',             'Gel warna + stiker kuku penuh di semua jari. Seru untuk tampilan spesial si kecil.'],
        ['Nail Art & Services','Gel Basic Tangan / Kaki',         'Rp 85.000',             'Gel warna solid untuk tangan atau kaki dengan hasil rapi dan tahan lama. Cocok untuk tampilan sehari-hari maupun acara spesial.'],
        ['Nail Art & Services','Extension',                       'Rp 50.000',             'Perpanjangan kuku menggunakan bahan gel berkualitas. Kuku tampak lebih panjang dan elegan secara instan.'],
        ['Nail Art & Services','Gel French / Cat Eyes',           'Rp 105.000',            'Gel dengan desain French classic atau efek cat eye yang memukau. Hasil bersih, presisi, dan tahan lama.'],
        ['Nail Art & Services','Remove Gel',                      'Rp 50.000',             'Pembersihan gel kuku secara aman tanpa merusak kuku asli. Proses cepat dan nyaman menggunakan teknik profesional.'],
        ['Nail Art & Services','Gel Ombre / Blush On',            'Rp 135.000',            'Gradasi warna lembut ombre atau efek blush on di kuku. Tampilan feminin, romantis, dan cocok untuk berbagai kesempatan.'],
        ['Nail Art & Services','Remove Extension',                'Rp 65.000',             'Pelepasan extension kuku secara aman dan menyeluruh. Kuku asli tetap terjaga kesehatannya setelah proses pengangkatan.'],
        ['Nail Art & Services','Bundling Nail Art + Extension',   'Rp 150.000',            'Paket hemat: extension kuku plus nail art desain pilihan. Dua layanan premium dalam satu sesi yang efisien.'],
    ];
    if ($cntPrc === 0) {
        $so = 1;
        foreach ($defaultPrcSeed as [$cat,$nm,$pr,$desc]) {
            $cat=mysqli_real_escape_string($conn,$cat); $nm=mysqli_real_escape_string($conn,$nm);
            $pr=mysqli_real_escape_string($conn,$pr);   $desc=mysqli_real_escape_string($conn,$desc);
            mysqli_query($conn,"INSERT INTO cms_prices (category,name,price,description,sort_order) VALUES ('$cat','$nm','$pr','$desc',$so)");
            $so++;
        }
    }

    // AUTO-PATCH: isi deskripsi yang masih NULL/kosong di DB dengan deskripsi default
    $descMap = [];
    foreach ($defaultPrcSeed as [$cat,$nm,$pr,$desc]) { $descMap[$nm] = $desc; }
    $emptyDescRows = mysqli_query($conn, "SELECT id, name FROM cms_prices WHERE (description IS NULL OR description = '')");
    if ($emptyDescRows) {
        while ($edr = mysqli_fetch_assoc($emptyDescRows)) {
            if (!empty($descMap[$edr['name']])) {
                $d = mysqli_real_escape_string($conn, $descMap[$edr['name']]);
                mysqli_query($conn, "UPDATE cms_prices SET description='$d' WHERE id=".(int)$edr['id']);
            }
        }
    }

    $cntProd = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_products"))['c'] ?? 0);
    if ($cntProd === 0) {
        $defaultProdSeed = [
            ['Cat Eye Nails',         'Rp 22.000','simple', 'image/nail1,22k.jpeg'],
            ['Cat Eye Nails Pink',    'Rp 17.000','simple', 'image/WhatsApp Image 2026-05-07 at 10.10.41.jpeg'],
            ['Cat Eye Coquette Nails','Rp 22.000','glam',   'image/cateyeqouket.jpeg'],
            ['Butterfly Nails',       'Rp 25.000','wedding','image/WhatsApp Image 2026-05-06 at 11.22.27.jpeg'],
            ['Cat Eye Nails',         'Rp 20.000','simple', 'image/WhatsApp Image 2026-05-06 at 11.05.13.jpeg'],
            ['Cat Eye Coquette Nails','Rp 22.000','glam',   'image/WhatsApp Image 2026-05-06 at 10.21.26.jpeg'],
            ['Elegant Nails',         'Rp 22.000','glam',   'image/WhatsApp Image 2026-05-06 at 10.21.24.jpeg'],
            ['Cat Eye Nails',         'Rp 20.000','simple', 'image/WhatsApp Image 2026-05-06 at 11.05.12.jpeg'],
            ['Cat Eye Red Nails',     'Rp 20.000','simple', 'image/WhatsApp Image 2026-05-06 at 11.05.12 (1).jpeg'],
            ['Simple Nails',          'Rp 17.000','simple', 'image/WhatsApp Image 2026-05-07 at 10.09.45.jpeg'],
            ['Cat Eye Pink Nails',    'Rp 20.000','simple', 'image/WhatsApp Image 2026-05-06 at 11.05.11.jpeg'],
            ['Sun Flower',            'Rp 17.000','glam',   'image/WhatsApp Image 2026-05-07 at 10.05.32.jpeg'],
            ['Bling bling Nails',     'Rp 17.000','glam',   'image/WhatsApp Image 2026-05-07 at 10.10.14.jpeg'],
            ['Elegant Nails',         'Rp 17.000','simple', 'image/WhatsApp Image 2026-05-07 at 10.06.14.jpeg'],
            ['Elegant Nails',         'Rp 25.000','wedding','image/WhatsApp Image 2026-05-06 at 11.20.31.jpeg'],
            ['Elegant Nails',         'Rp 25.000','wedding','image/WhatsApp Image 2026-05-06 at 11.17.28.jpeg'],
        ];
        $so = 1;
        foreach ($defaultProdSeed as [$nm,$pr,$cat,$img]) {
            $nm=mysqli_real_escape_string($conn,$nm); $pr=mysqli_real_escape_string($conn,$pr);
            $cat=mysqli_real_escape_string($conn,$cat); $img=mysqli_real_escape_string($conn,$img);
            mysqli_query($conn,"INSERT INTO cms_products (name,price,category,image,sort_order) VALUES ('$nm','$pr','$cat','$img',$so)");
            $so++;
        }
    }

    $cntTesti = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_testimonials"))['c'] ?? 0);
    if ($cntTesti === 0) {
        $defaultTestiSeed = [
            ['Ninda Ayu',    'Nail Art & Foot Spa', 'Nail art-nya bagus banget, hasilnya rapi dan tahan lama! Mbak-mbaknya ramah dan sabar. Foot spa-nya juga bikin kaki lega banget. Pasti balik lagi! 🌟',   'linear-gradient(135deg,#f9a8d4,#f472b6)'],
            ['Rizka Amalia', 'Lashlift',            'Lashlift-nya keren banget, mata jadi keliatan lebih segar dan melek. Tempatnya bersih dan nyaman, harga juga worth it. Recommended banget!',             'linear-gradient(135deg,#6ee7b7,#34d399)'],
            ['Siti Maryam',  'Callus Treatment',    'Callus treatment-nya top banget, kaki jadi mulus dan lembut. Pelayanan cepat dan tidak mengecewakan. Sudah langganan di sini dari lama dan selalu puas!', 'linear-gradient(135deg,#fde68a,#f59e0b)'],
            ['Dian Pertiwi', 'Smoothing',           'Smoothing-nya hasilnya halus banget dan tahan lama! Stafnya profesional dan ramah. Tempatnya cozy, betah deh berlama-lama di sini. Recommended!',        'linear-gradient(135deg,#a78bfa,#7c3aed)'],
            ['Fatimah Zahra','Henna Series',        'Henna series-nya cantik banget, detail dan presisi! Mbak-mbaknya sabar banget ngerjainnya. Harganya juga sangat terjangkau untuk kualitas segini.',       'linear-gradient(135deg,#86efac,#16a34a)'],
        ];
        $so = 1;
        foreach ($defaultTestiSeed as [$nm,$tag,$txt,$clr]) {
            $nm=mysqli_real_escape_string($conn,$nm); $tag=mysqli_real_escape_string($conn,$tag);
            $txt=mysqli_real_escape_string($conn,$txt); $clr=mysqli_real_escape_string($conn,$clr);
            mysqli_query($conn,"INSERT INTO cms_testimonials (name,service_tag,text,avatar_color,sort_order) VALUES ('$nm','$tag','$txt','$clr',$so)");
            $so++;
        }
    }
}

/* ── Upload helper ── */
function handleUpload($fileKey, $destDir = 'image/') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $ext     = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return null;
    $fname  = uniqid('cms_', true) . '.' . $ext;
    $dest   = rtrim($destDir, '/') . '/' . $fname;
    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
    return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest) ? $dest : null;
}

/* ── Content helpers ── */
function getContent($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function setContent($conn, $section, $key, $value) {
    if (!$conn) return;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO cms_content (section,`key`,value) VALUES ('$s','$k','$v')
                         ON DUPLICATE KEY UPDATE value='$v', updated_at=NOW()");
}
function getProfil($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_profil WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function setProfil($conn, $section, $key, $value) {
    if (!$conn) return;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO cms_profil (section,`key`,value) VALUES ('$s','$k','$v')
                         ON DUPLICATE KEY UPDATE value='$v', updated_at=NOW()");
}
function getNavbar($conn, $key, $default = '') {
    if (!$conn) return $default;
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_navbar WHERE section='navbar' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function setNavbar($conn, $key, $value) {
    if (!$conn) return;
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO cms_navbar (section,`key`,value) VALUES ('navbar','$k','$v')
                         ON DUPLICATE KEY UPDATE value='$v', updated_at=NOW()");
}
function getFooter($conn, $key, $default = '') {
    if (!$conn) return $default;
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_footer WHERE section='footer' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function setFooter($conn, $key, $value) {
    if (!$conn) return;
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO cms_footer (section,`key`,value) VALUES ('footer','$k','$v')
                         ON DUPLICATE KEY UPDATE value='$v', updated_at=NOW()");
}
function getBookingPage($conn, $key, $default = '') {
    if (!$conn) return $default;
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function setBookingPage($conn, $key, $value) {
    if (!$conn) return;
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO cms_booking_page (section,`key`,value) VALUES ('booking','$k','$v')
                         ON DUPLICATE KEY UPDATE value='$v', updated_at=NOW()");
}

/* ══════════════════════════════════════════════
   HANDLE ALL ACTIONS
══════════════════════════════════════════════ */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ── 1. Hero ── */
if ($action === 'save_hero') {
    setContent($conn, 'hero', 'title',         $_POST['hero_title']         ?? '');
    setContent($conn, 'hero', 'subtitle',      $_POST['hero_subtitle']      ?? '');
    setContent($conn, 'hero', 'btn_primary',   $_POST['btn_primary']        ?? '');
    setContent($conn, 'hero', 'btn_secondary', $_POST['btn_secondary']      ?? '');
    if ($up = handleUpload('hero_img1')) setContent($conn, 'hero', 'img1', $up);
    if ($up = handleUpload('hero_img2')) setContent($conn, 'hero', 'img2', $up);
    if ($up = handleUpload('hero_img3')) setContent($conn, 'hero', 'img3', $up);
    header('Location: cms.php?tab=hero&saved=1'); exit;
}
if ($action === 'delete_hero_img' && isset($_GET['n'])) {
    $n = (int)$_GET['n'];
    if ($n >= 1 && $n <= 3) setContent($conn, 'hero', "img$n", '');
    header('Location: cms.php?tab=hero&saved=1'); exit;
}

/* ── 2. Kontak & Maps ── */
if ($action === 'save_kontak') {
    foreach (['salon_name','address','hours','whatsapp','email','maps_embed','maps_link'] as $k)
        setContent($conn, 'kontak', $k, $_POST[$k] ?? '');
    header('Location: cms.php?tab=kontak&saved=1'); exit;
}

/* ── 3. Services CRUD ── */
if ($action === 'save_service') {
    $id      = (int)($_POST['service_id'] ?? 0);
    $name    = mysqli_real_escape_string($conn, $_POST['service_name']    ?? '');
    $gallery = mysqli_real_escape_string($conn, $_POST['service_gallery'] ?? '');
    $img     = handleUpload('service_image') ?? '';
    if ($id) {
        $imgSql = $img ? ", image='$img'" : '';
        mysqli_query($conn, "UPDATE cms_services SET name='$name'$imgSql, gallery='$gallery' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_services (name,image,gallery) VALUES ('$name','$img','$gallery')");
    }
    header('Location: cms.php?tab=services&saved=1'); exit;
}
if ($action === 'delete_service' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM cms_services WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=services'); exit;
}

/* ── 4. Prices CRUD ── */
if ($action === 'save_price') {
    $id    = (int)($_POST['price_id'] ?? 0);
    $cat   = mysqli_real_escape_string($conn, $_POST['price_cat']  ?? '');
    $name  = mysqli_real_escape_string($conn, $_POST['price_name'] ?? '');
    $price = mysqli_real_escape_string($conn, $_POST['price_val']  ?? '');
    $desc  = mysqli_real_escape_string($conn, $_POST['price_desc'] ?? '');
    $img   = handleUpload('price_image') ?? '';
    if ($id) {
        $imgSql = $img ? ", image='$img'" : '';
        mysqli_query($conn, "UPDATE cms_prices SET category='$cat', name='$name', price='$price', description='$desc'$imgSql WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_prices (category,name,price,description,image) VALUES ('$cat','$name','$price','$desc','$img')");
    }
    header('Location: cms.php?tab=prices&saved=1'); exit;
}
if ($action === 'delete_price' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM cms_prices WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=prices'); exit;
}

/* ── 5. Products CRUD ── */
if ($action === 'save_product') {
    $id           = (int)($_POST['prod_id']        ?? 0);
    $name         = mysqli_real_escape_string($conn, $_POST['prod_name']     ?? '');
    $price        = mysqli_real_escape_string($conn, $_POST['prod_price']    ?? '');
    $cat          = mysqli_real_escape_string($conn, $_POST['prod_category'] ?? '');
    $disc_pct     = max(0, min(100, (int)($_POST['prod_discount_pct'] ?? 0)));
    $min_purchase = max(0, (int)($_POST['prod_min_purchase'] ?? 0));
    $img          = handleUpload('prod_image') ?? '';
    if ($id) {
        $imgSql = $img ? ", image='$img'" : '';
        mysqli_query($conn, "UPDATE cms_products SET name='$name', price='$price', category='$cat', discount_pct=$disc_pct, min_purchase=$min_purchase$imgSql WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_products (name,price,category,image,discount_pct,min_purchase) VALUES ('$name','$price','$cat','$img',$disc_pct,$min_purchase)");
    }
    header('Location: cms.php?tab=products&saved=1'); exit;
}
if ($action === 'delete_product' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM cms_products WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=products'); exit;
}

/* ── 6. Testimoni CRUD ── */
if ($action === 'save_testi') {
    $id      = (int)($_POST['testi_id']      ?? 0);
    $name    = mysqli_real_escape_string($conn, $_POST['testi_name']    ?? '');
    $service = mysqli_real_escape_string($conn, $_POST['testi_service'] ?? '');
    $text    = mysqli_real_escape_string($conn, $_POST['testi_text']    ?? '');
    $color   = mysqli_real_escape_string($conn, $_POST['testi_color']   ?? 'linear-gradient(135deg,#f9a8d4,#f472b6)');
    if ($id) {
        mysqli_query($conn, "UPDATE cms_testimonials SET name='$name', service_tag='$service', text='$text', avatar_color='$color' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_testimonials (name,service_tag,text,avatar_color) VALUES ('$name','$service','$text','$color')");
    }
    header('Location: cms.php?tab=testimoni&saved=1'); exit;
}
if ($action === 'delete_testi' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM cms_testimonials WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=testimoni'); exit;
}

/* ── 7. Profil Toko ── */
if ($action === 'save_profil') {
    $fields = ['owner_name','owner_tagline','owner_bio1','owner_bio2',
               'store_name','store_tagline','store_bio1','store_bio2',
               'store_image','value_item_1','value_item_2','value_item_3','value_item_4'];
    foreach ($fields as $f) {
        if ($f === 'store_image') {
            if ($up = handleUpload('store_image_file')) setProfil($conn, 'profil', $f, $up);
        } else {
            setProfil($conn, 'profil', $f, $_POST[$f] ?? '');
        }
    }
    header('Location: cms.php?tab=profil&saved=1'); exit;
}

/* ── Logout ── */
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header("Location: login.php"); exit;
}

/* ── 10. Navbar ── */
if ($action === 'save_navbar') {
    $fields = ['brand_name','btn_book_text','menu_home','menu_services','menu_product','menu_about','menu_booking'];
    foreach ($fields as $f) setNavbar($conn, $f, $_POST[$f] ?? '');
    header('Location: cms.php?tab=navbar&saved=1'); exit;
}

/* ── 11. Footer ── */
if ($action === 'save_footer') {
    $fields = ['brand_name','brand_desc',
               'instagram_url','tiktok_url','whatsapp_url',
               'address','phone','email','hours',
               'copyright_text',
               'link_home','link_services','link_product','link_about','link_booking'];
    foreach ($fields as $f) setFooter($conn, $f, $_POST[$f] ?? '');
    header('Location: cms.php?tab=footer&saved=1'); exit;
}

/* ── 12. Booking Page Content ── */
if ($action === 'save_booking_page') {
    $fields = ['page_title','page_subtitle','form_title',
               'services_list','time_slots'];
    foreach ($fields as $f) setBookingPage($conn, $f, $_POST[$f] ?? '');
    header('Location: cms.php?tab=booking_page&saved=1'); exit;
}

/* ══════════════════════════════════════════════
   LOAD ALL DATA
══════════════════════════════════════════════ */
$activeTab = $_GET['tab'] ?? 'hero';
$saved     = isset($_GET['saved']);

// Hero
$hero = [
    'title'         => getContent($conn,'hero','title',         'Temukan Kecantikan Terbaikmu'),
    'subtitle'      => getContent($conn,'hero','subtitle',      'Layanan premium untuk tampilan terbaik Anda'),
    'btn_primary'   => getContent($conn,'hero','btn_primary',   'Reservasi Sekarang'),
    'btn_secondary' => getContent($conn,'hero','btn_secondary', 'Lihat Layanan'),
    'img1'          => getContent($conn,'hero','img1',          'image/homenailart.jpeg'),
    'img2'          => getContent($conn,'hero','img2',          ''),
    'img3'          => getContent($conn,'hero','img3',          ''),
];

// Kontak
$kontak = [
    'salon_name' => getContent($conn,'kontak','salon_name','NISWÀ BEAUTY'),
    'address'    => getContent($conn,'kontak','address',   'Jl. Watulumpang, Bangsri, Jepara'),
    'hours'      => getContent($conn,'kontak','hours',     'Senin – Minggu, 08.00 – 20.00'),
    'whatsapp'   => getContent($conn,'kontak','whatsapp',  '62812345678'),
    'email'      => getContent($conn,'kontak','email',     'niswabeauty15@gmail.com'),
    'maps_embed' => getContent($conn,'kontak','maps_embed','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0!2d110.7708502!3d-6.5253308!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7123c39ad21875%3A0xd77e4fd098899e2c!2sNISWA%20BEAUTY%20Nail%20%26%20Foot%20Spa!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid'),
    'maps_link'  => getContent($conn,'kontak','maps_link', 'https://maps.app.goo.gl/czQHcN15FMvfFZy76'),
];

// Profil
$profil = [
    'owner_name'    => getProfil($conn,'profil','owner_name',   'Niswa'),
    'owner_tagline' => getProfil($conn,'profil','owner_tagline','"Kecantikan adalah kepercayaan diri yang paling murni."'),
    'owner_bio1'    => getProfil($conn,'profil','owner_bio1',   'Pendiri Niswa Beauty memulai perjalanan usahanya dari jasa henna keliling dengan nama Niswa Henna. Dengan penuh semangat dan ketekunan, layanan dilakukan dari rumah ke rumah untuk memenuhi kebutuhan pelanggan di sekitar Jepara.'),
    'owner_bio2'    => getProfil($conn,'profil','owner_bio2',   'Tahun 2020–2021 menjadi masa penuh perjuangan sekaligus perkembangan. Pendiri mulai dikenal oleh beberapa publik figur lokal di Jepara.'),
    'store_name'    => getProfil($conn,'profil','store_name',   'NISWÀ BEAUTY'),
    'store_tagline' => getProfil($conn,'profil','store_tagline','"Premium Beauty Experience di Jantung Jepara"'),
    'store_bio1'    => getProfil($conn,'profil','store_bio1',   'Niswa Beauty merupakan usaha di bidang kecantikan yang berawal dari layanan henna sederhana bernama Niswa Henna.'),
    'store_bio2'    => getProfil($conn,'profil','store_bio2',   'Tanggal 15 Juli 2023 menjadi tonggak penting dengan resmi berdirinya Niswa Beauty bersama dua orang tim pertama.'),
    'store_image'   => getProfil($conn,'profil','store_image',  'image/WhatsApp Image 2026-05-08 at 10.02.50.jpeg'),
    'value_item_1'  => getProfil($conn,'profil','value_item_1', 'Pelayanan Tulus'),
    'value_item_2'  => getProfil($conn,'profil','value_item_2', 'Produk Aman & Halal'),
    'value_item_3'  => getProfil($conn,'profil','value_item_3', 'Kualitas Premium'),
    'value_item_4'  => getProfil($conn,'profil','value_item_4', 'Kepuasan Pelanggan'),
];

// Navbar
$navbar = [
    'brand_name'    => getNavbar($conn, 'brand_name',    'NISWÀ BEAUTY'),
    'btn_book_text' => getNavbar($conn, 'btn_book_text', 'Book Now'),
    'menu_home'     => getNavbar($conn, 'menu_home',     'Home'),
    'menu_services' => getNavbar($conn, 'menu_services', 'Services'),
    'menu_product'  => getNavbar($conn, 'menu_product',  'Product'),
    'menu_about'    => getNavbar($conn, 'menu_about',    'About'),
    'menu_booking'  => getNavbar($conn, 'menu_booking',  'Booking'),
];

// Footer
$footer_data = [
    'brand_name'    => getFooter($conn, 'brand_name',    'NISWÀ BEAUTY'),
    'brand_desc'    => getFooter($conn, 'brand_desc',    'Kecantikan bertemu kemewahan, dengan sentuhan profesional.'),
    'instagram_url' => getFooter($conn, 'instagram_url', 'https://www.instagram.com/niswanail?igsh=MXJtYW1kenhuN3VpNA=='),
    'tiktok_url'    => getFooter($conn, 'tiktok_url',    'https://www.tiktok.com/@niswabeauty?_r=1&_t=ZS-96BG9fNdy7Q'),
    'whatsapp_url'  => getFooter($conn, 'whatsapp_url',  'https://wa.me/0882006903068'),
    'address'       => getFooter($conn, 'address',       'Bangsri, Jepara, Jawa Tengah'),
    'phone'         => getFooter($conn, 'phone',         '+62 882-0069-03068'),
    'email'         => getFooter($conn, 'email',         'niswabeauty15@gmail.com'),
    'hours'         => getFooter($conn, 'hours',         'Senin – Sabtu: 09:00 – 20:00'),
    'copyright_text'=> getFooter($conn, 'copyright_text','NISWÀ BEAUTY. All rights reserved.'),
    'link_home'     => getFooter($conn, 'link_home',     '#home'),
    'link_services' => getFooter($conn, 'link_services', '#services'),
    'link_product'  => getFooter($conn, 'link_product',  '#product'),
    'link_about'    => getFooter($conn, 'link_about',    '#about'),
    'link_booking'  => getFooter($conn, 'link_booking',  'booking.php'),
];

// Booking Page
$booking_page = [
    'page_title'      => getBookingPage($conn, 'page_title',      'Reservasi Online'),
    'page_subtitle'   => getBookingPage($conn, 'page_subtitle',   'Isi form di bawah untuk memesan jadwal kecantikan Anda'),
    'form_title'      => getBookingPage($conn, 'form_title',      'Form Booking'),
    'services_list'   => getBookingPage($conn, 'services_list',   "Brow Henna\nNail Henna Tangan\nNail Henna Kaki\nBundling Meni-Henna\nHenna Fun\nBundling Manicure & Pedicure\nManicure / Pedicure\nHand Spa\nFoot Spa\nCallus Treatment\nBrow Bomb\nLashlift\nLashlift Tint\nCreambath\nHair Mask\nHair Spa\nCuci,Catok,Blow\nBleaching S\nColoring Full\nBleaching\nBalayage\nDown Peim Poni\nKeriting Klasik\nKeriting Digital\nKeratin Treatment\nSmoothing\nPress On Nail Basic\nPress On Nail Motif\nKids Basic Gel\nKids Gel + 4 Sticker\nKids Gel + Full Sticker\nGel Basic Tangan / Kaki\nExtension\nGel French / Cat Eyes\nRemove Gel\nGel Ombre / Blush On\nRemove Extension\nBundling Nail Art + Extension"),
    'time_slots'      => getBookingPage($conn, 'time_slots',      "09:00\n10:00\n11:00\n12:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00"),
];

// Auto-update services_list jika masih berisi data lama
$_newServices = "Brow Henna\nNail Henna Tangan\nNail Henna Kaki\nBundling Meni-Henna\nHenna Fun\nBundling Manicure & Pedicure\nManicure / Pedicure\nHand Spa\nFoot Spa\nCallus Treatment\nBrow Bomb\nLashlift\nLashlift Tint\nCreambath\nHair Mask\nHair Spa\nCuci,Catok,Blow\nBleaching S\nColoring Full\nBleaching\nBalayage\nDown Peim Poni\nKeriting Klasik\nKeriting Digital\nKeratin Treatment\nSmoothing\nPress On Nail Basic\nPress On Nail Motif\nKids Basic Gel\nKids Gel + 4 Sticker\nKids Gel + Full Sticker\nGel Basic Tangan / Kaki\nExtension\nGel French / Cat Eyes\nRemove Gel\nGel Ombre / Blush On\nRemove Extension\nBundling Nail Art + Extension";
$_oldKeywords = ['Nail Art', 'Haircut', 'Eye Lash', 'Hair Treatment', 'Press on Nail'];
$_isOld = false;
foreach ($_oldKeywords as $_kw) { if (strpos($booking_page['services_list'], $_kw) !== false) { $_isOld = true; break; } }
if ($_isOld && $conn) { setBookingPage($conn, 'services_list', $_newServices); $booking_page['services_list'] = $_newServices; }

// Static default price list (displayed when DB is empty, mirroring website)
// description = sama persis dengan priceDescriptions di index.php
$defaultPriceList = [
    'Henna Series' => [
        ['name'=>'Brow Henna',        'price'=>'Rp 25.000',        'description'=>'Pewarnaan alis dengan henna alami yang tahan lama. Mengisi alis tipis dan memberikan tampilan tegas, natural, dan rapi.'],
        ['name'=>'Nail Henna Tangan', 'price'=>'Rp 25.000',        'description'=>'Motif henna indah di kuku & tangan menggunakan bahan alami. Cocok untuk acara formal maupun casual.'],
        ['name'=>'Nail Henna Kaki',   'price'=>'Rp 30.000',        'description'=>'Desain henna elegan di area kuku dan kaki. Bahan aman, cocok untuk semua usia.'],
        ['name'=>'Bundling Meni-Henna','price'=>'Rp 75.000',       'description'=>'Paket hemat manicure lengkap + nail henna. Dua layanan kecantikan dalam satu sesi.'],
        ['name'=>'Henna Fun',         'price'=>'Rp 25.000 - 100.000','description'=>'Henna dekoratif di tangan dengan berbagai motif pilihan. Semakin kompleks motif, semakin artistik hasilnya.'],
    ],
    'Treatment Spa' => [
        ['name'=>'Bundling Manicure & Pedicure','price'=>'Rp 100.000',      'description'=>'Paket lengkap perawatan tangan & kaki: scrub, masker, pemotongan kuku, dan finishing oil.'],
        ['name'=>'Manicure / Pedicure',         'price'=>'Rp 60.000',       'description'=>'Perawatan kuku dan kulit tangan atau kaki dengan teknik profesional. Kulit lebih lembut, kuku lebih sehat.'],
        ['name'=>'Hand Spa',                    'price'=>'Rp 80.000',       'description'=>'Perawatan intensif tangan: scrub eksfoliasi, masker pelembap, dan pijat relaksasi. Tangan terasa lembut & cerah.'],
        ['name'=>'Foot Spa',                    'price'=>'Rp 100.000',      'description'=>'Terapi kaki lengkap mulai dari perendaman, scrub, masker, hingga pijat refleksi. Cocok setelah hari panjang.'],
        ['name'=>'Callus Treatment',            'price'=>'Rp 70.000 - 150.000','description'=>'Pengangkatan kapalan dan kulit keras di telapak kaki secara profesional. Makin tebal kalus, makin intensif perawatannya.'],
    ],
    'Brow & Lash' => [
        ['name'=>'Brow Bomb',    'price'=>'Rp 100.000','description'=>'Perawatan alis all-in-one: lifting, tinting, dan setting. Alis tampak tebal, tegas, dan terbentuk sempurna tanpa makeup.'],
        ['name'=>'Lashlift',     'price'=>'Rp 70.000', 'description'=>'Keriting bulu mata permanen tanpa sambungan. Mata terlihat lebih besar dan terbuka secara alami hingga 6–8 minggu.'],
        ['name'=>'Lashlift Tint','price'=>'Rp 90.000', 'description'=>'Lashlift plus pewarnaan bulu mata agar lebih gelap dan dramatis. Tanpa maskara pun sudah memukau.'],
    ],
    'Rambut' => [
        ['name'=>'Creambath',       'price'=>'Rp 75.000',           'description'=>'Perawatan rambut dengan krim nutrisi, pijat kepala, dan uap hangat. Rambut lebih lebat, lembut, dan berkilau.'],
        ['name'=>'Hair Mask',       'price'=>'Rp 45.000 - 90.000',  'description'=>'Masker rambut intensif sesuai jenis rambut. Menutrisi dari dalam, mengurangi frizz, dan mengembalikan kilau alami.'],
        ['name'=>'Hair Spa',        'price'=>'Rp 100.000',           'description'=>'Spa rambut lengkap: shampo, kondisioner, masker, uap, dan pijat. Solusi untuk rambut rusak & kering.'],
        ['name'=>'Cuci,Catok,Blow', 'price'=>'Rp 25.000 - 50.000',  'description'=>'Cuci rambut + blow dry atau catok sesuai selera. Rambut bersih, rapi, dan siap tampil.'],
        ['name'=>'Bleaching S',     'price'=>'Rp 40.000',            'description'=>'Bleaching parsial (highlight/poni) untuk mencerahkan area tertentu. Cocok untuk warna pastel atau ombre.'],
        ['name'=>'Coloring Full',   'price'=>'Rp 120.000 - 300.000', 'description'=>'Pewarnaan rambut penuh dari akar hingga ujung. Pilihan warna beragam, hasil merata dan tahan lama.'],
        ['name'=>'Bleaching',       'price'=>'Rp 200.000 - 1.200.000','description'=>'Bleaching full atau intensif untuk mengangkat pigmen rambut. Harga tergantung panjang dan ketebalan rambut.'],
        ['name'=>'Balayage',        'price'=>'Rp 250.000 - 700.000', 'description'=>'Teknik pewarnaan gradasi tangan bebas yang menghasilkan tampilan natural sun-kissed. Setiap hasil unik dan personal.'],
        ['name'=>'Down Peim Poni',  'price'=>'Rp 100.000 - 300.000', 'description'=>'Pelurus poni dengan teknik perm down. Poni turun rapi tahan lama tanpa perlu di-styling setiap hari.'],
        ['name'=>'Keriting Klasik', 'price'=>'Rp 300.000 - 700.000', 'description'=>'Keriting permanen dengan batang spiral klasik. Cocok untuk tampilan volume dan berkarakter.'],
        ['name'=>'Keriting Digital','price'=>'Rp 450.000 - 1.700.000','description'=>'Keriting digital dengan alat pemanas modern. Hasil lebih bergelombang lembut, tahan lama, dan terlihat natural.'],
        ['name'=>'Keratin Treatment','price'=>'Rp 200.000',          'description'=>'Perawatan keratin untuk melembutkan dan meluruskan rambut secara alami. Mengurangi frizz & mudah diatur.'],
        ['name'=>'Smoothing',       'price'=>'Rp 200.000 - 400.000', 'description'=>'Pelurusan rambut semi-permanen yang membuat rambut lurus, halus, dan mudah di-styling. Tahan 3–6 bulan.'],
    ],
    'Nail Art & Services' => [
        ['name'=>'Press On Nail Basic',          'price'=>'Rp 50.000',  'description'=>'Press on nail siap pakai dengan desain simpel dan elegan. Mudah dipasang sendiri, tahan beberapa hari.'],
        ['name'=>'Press On Nail Motif',          'price'=>'Rp 75.000',  'description'=>'Press on nail dengan motif artistik dan detail lebih kompleks. Cocok untuk event spesial.'],
        ['name'=>'Kids Basic Gel',               'price'=>'Rp 40.000',  'description'=>'Gel kuku aman khusus anak-anak. Warna solid lembut yang tahan lama dan tidak berbau menyengat.'],
        ['name'=>'Kids Gel + 4 Sticker',         'price'=>'Rp 50.000',  'description'=>'Gel warna + 4 stiker kuku pilihan anak. Tampilan lucu dan menggemaskan.'],
        ['name'=>'Kids Gel + Full Sticker',      'price'=>'Rp 55.000',  'description'=>'Gel warna + stiker kuku penuh di semua jari. Seru untuk tampilan spesial si kecil.'],
        ['name'=>'Gel Basic Tangan / Kaki',      'price'=>'Rp 85.000',  'description'=>'Gel warna solid untuk tangan atau kaki dengan hasil rapi dan tahan lama. Cocok untuk tampilan sehari-hari maupun acara spesial.'],
        ['name'=>'Extension',                    'price'=>'Rp 50.000',  'description'=>'Perpanjangan kuku menggunakan bahan gel berkualitas. Kuku tampak lebih panjang dan elegan secara instan.'],
        ['name'=>'Gel French / Cat Eyes',        'price'=>'Rp 105.000', 'description'=>'Gel dengan desain French classic atau efek cat eye yang memukau. Hasil bersih, presisi, dan tahan lama.'],
        ['name'=>'Remove Gel',                   'price'=>'Rp 50.000',  'description'=>'Pembersihan gel kuku secara aman tanpa merusak kuku asli. Proses cepat dan nyaman menggunakan teknik profesional.'],
        ['name'=>'Gel Ombre / Blush On',         'price'=>'Rp 135.000', 'description'=>'Gradasi warna lembut ombre atau efek blush on di kuku. Tampilan feminin, romantis, dan cocok untuk berbagai kesempatan.'],
        ['name'=>'Remove Extension',             'price'=>'Rp 65.000',  'description'=>'Pelepasan extension kuku secara aman dan menyeluruh. Kuku asli tetap terjaga kesehatannya setelah proses pengangkatan.'],
        ['name'=>'Bundling Nail Art + Extension','price'=>'Rp 150.000', 'description'=>'Paket hemat: extension kuku plus nail art desain pilihan. Dua layanan premium dalam satu sesi yang efisien.'],
    ],
];

// DB rows
$servicesRows  = $conn ? mysqli_query($conn, "SELECT * FROM cms_services ORDER BY sort_order, id") : null;
$pricesRows    = $conn ? mysqli_query($conn, "SELECT * FROM cms_prices ORDER BY category, sort_order, id") : null;
$productsRows  = $conn ? mysqli_query($conn, "SELECT * FROM cms_products ORDER BY sort_order, id") : null;
$testiRows     = $conn ? mysqli_query($conn, "SELECT * FROM cms_testimonials ORDER BY sort_order, id") : null;
// Stats
$totalProducts = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_products"))['c'] ?? 0) : 0;
$totalServices = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_services"))['c'] ?? 0) : 0;
$totalOrders   = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'] ?? 0) : 0;
$newOrders     = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS Dashboard — NISWÀ BEAUTY</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --gold:      #D6C1A3;
    --gold-dk:   #b8a082;
    --primary:   #8B6F5E;
    --primary-dk:#5A4A42;
    --dark:      #150d10;
    --sidebar-w: 268px;
    --cream:     #FAF7F2;
    --white:     #ffffff;
    --border:    #EDE5D8;
    --text:      #2d2d2d;
    --text-mid:  #666;
    --text-lt:   #999;
    --success:   #10b981;
    --danger:    #ef4444;
    --warn:      #f59e0b;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--cream);color:var(--text);min-height:100vh;}

/* ━━ SIDEBAR ━━ */
.sidebar{
    width:250px;background:var(--dark);
    height:100vh;position:fixed;left:0;top:0;
    padding:28px 0 0;z-index:100;
    display:flex;flex-direction:column;
    overflow:hidden;
}
.sidebar-nav-wrap{
    flex:1;overflow-y:auto;overflow-x:hidden;
    padding-bottom:12px;
}
.sidebar-nav-wrap::-webkit-scrollbar{width:4px;}
.sidebar-nav-wrap::-webkit-scrollbar-track{background:transparent;}
.sidebar-nav-wrap::-webkit-scrollbar-thumb{background:rgba(214,193,163,.2);border-radius:4px;}
.sidebar-nav-wrap::-webkit-scrollbar-thumb:hover{background:rgba(214,193,163,.4);}
.sidebar-brand{
    color:#fff;font-size:18px;font-weight:700;
    padding:0 24px 24px;border-bottom:1px solid #1e0d1a;margin-bottom:16px;
}
.sidebar-brand i{color:var(--gold);}
.sidebar-brand small{display:block;font-size:10px;color:#5a4050;letter-spacing:1px;margin-top:4px;font-weight:400;}
.sidebar-section{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:#3a2030;padding:14px 24px 6px;font-weight:700;}
.nav-list{list-style:none;padding:0 12px;}
.nav-list li{margin-bottom:2px;}
.nav-list a{
    display:flex;align-items:center;gap:10px;padding:11px 14px;
    color:#8b7880;text-decoration:none;border-radius:10px;
    font-size:14px;transition:.2s;
}
.nav-list a:hover,.nav-list a.active{background:rgba(180,148,110,.12);color:var(--gold);}
.nav-list a i{width:16px;text-align:center;}
.sidebar-user{
    flex-shrink:0;
    padding:14px 18px;border-top:1px solid #1e0d1a;
    display:flex;align-items:center;gap:10px;
    background:var(--dark);
}
.sidebar-user .av{
    width:34px;height:34px;border-radius:50%;
    background:linear-gradient(135deg,var(--gold),#5A4A42);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:700;font-size:14px;flex-shrink:0;
}
.sidebar-user .name{color:var(--gold);font-size:13px;font-weight:600;}
.sidebar-user .role{color:#5a4050;font-size:11px;}

/* ━━ MOBILE TOPBAR ━━ */
.mobile-topbar{
    display:none;position:fixed;top:0;left:0;right:0;
    background:var(--dark);z-index:200;
    padding:14px 18px;
    align-items:center;justify-content:space-between;
}
.mobile-topbar .brand{color:#fff;font-weight:700;font-size:16px;}
.mobile-topbar .brand i{color:var(--gold);}
.mobile-hamburger{
    background:none;border:none;cursor:pointer;
    display:flex;flex-direction:column;gap:5px;padding:4px;
}
.mobile-hamburger span{
    display:block;width:22px;height:2px;
    background:var(--gold);border-radius:2px;transition:.3s;
}

/* ━━ MOBILE DRAWER ━━ */
.mobile-drawer-overlay{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.55);z-index:300;
}
.mobile-drawer-overlay.open{display:block;}
.mobile-drawer{
    position:fixed;top:0;left:-270px;bottom:0;
    width:260px;background:var(--dark);
    z-index:310;padding:28px 0 80px;
    transition:left .3s ease;overflow-y:auto;
}
.mobile-drawer.open{left:0;}
.mobile-drawer .sidebar-brand{padding:0 22px 22px;border-bottom:1px solid #1e0d1a;margin-bottom:14px;}
.mobile-drawer .sidebar-section{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:#3a2030;padding:14px 22px 6px;font-weight:700;}
.mobile-drawer .nav-list{list-style:none;padding:0 10px;}
.mobile-drawer .nav-list li{margin-bottom:2px;}
.mobile-drawer .nav-list a{
    display:flex;align-items:center;gap:10px;padding:11px 14px;
    color:#8b7880;text-decoration:none;border-radius:10px;
    font-size:14px;transition:.2s;
}
.mobile-drawer .nav-list a:hover{background:rgba(180,148,110,.12);color:var(--gold);}
.mobile-drawer .nav-list a i{width:16px;text-align:center;}
.mobile-drawer-user{
    position:absolute;bottom:0;left:0;right:0;
    padding:14px 18px;border-top:1px solid #1e0d1a;
    display:flex;align-items:center;gap:10px;
    background:var(--dark);
}
.mobile-drawer-user .av{
    width:34px;height:34px;border-radius:50%;
    background:linear-gradient(135deg,var(--gold),#5A4A42);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:700;font-size:14px;flex-shrink:0;
}
.mobile-drawer-user .name{color:var(--gold);font-size:13px;font-weight:600;}
.mobile-drawer-user .role{color:#5a4050;font-size:11px;}

/* ━━ MAIN ━━ */
.main-wrap{margin-left:250px;min-height:100vh;overflow-x:hidden;}
.topbar{
    background:#fff;padding:14px 28px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
    position:sticky;top:0;z-index:500;
    isolation:isolate;
}
.topbar-title{font-size:17px;font-weight:700;color:var(--text);}
.topbar-title span{color:var(--primary);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-btn{
    font-size:12px;text-decoration:none;display:flex;align-items:center;gap:5px;
    padding:6px 12px;border-radius:8px;font-weight:600;
    transition:all .22s ease;
}
.topbar-btn-dashboard{
    color:#5A4A42;background:rgba(139,111,94,.08);
}
.topbar-btn-dashboard:hover{
    background:rgba(139,111,94,.22);color:var(--primary-dk);
    transform:translateX(-2px);
    box-shadow:0 3px 10px rgba(139,111,94,.18);
}
.topbar-btn-website{
    color:var(--primary);background:rgba(139,111,94,.08);
}
.topbar-btn-website:hover{
    background:rgba(139,111,94,.22);color:var(--primary-dk);
    box-shadow:0 3px 10px rgba(139,111,94,.18);
}
.topbar-btn-logout{
    color:var(--danger);font-size:16px;text-decoration:none;
    display:flex;align-items:center;justify-content:center;
    width:34px;height:34px;border-radius:8px;
    transition:all .22s ease;
}
.topbar-btn-logout:hover{
    background:rgba(239,68,68,.1);
    color:#c81e1e;
    transform:scale(1.15) rotate(8deg);
    box-shadow:0 3px 10px rgba(239,68,68,.18);
}
.mobile-menu-btn{display:none;background:none;border:none;cursor:pointer;color:var(--text);font-size:20px;padding:4px;}
.content{padding:24px 28px;}

/* ━━ TOAST ━━ */
.toast-bar{
    position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;
    background:var(--success);color:#fff;
    padding:11px 24px;border-radius:12px;
    display:flex;align-items:center;gap:9px;
    font-size:13.5px;font-weight:600;
    box-shadow:0 8px 28px rgba(16,185,129,.35);
    animation:toastIn .4s ease;
    white-space:nowrap;
}
@keyframes toastIn{from{opacity:0;transform:translateX(-50%) translateY(16px);}to{opacity:1;transform:translateX(-50%) translateY(0);}}

/* ━━ STAT CARDS ━━ */
.stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:24px;}
.stat-card{
    background:#fff;border-radius:16px;padding:20px;
    border:1px solid var(--border);
    box-shadow:0 2px 10px rgba(139,111,94,.06);
    display:flex;align-items:center;gap:16px;
    cursor:default;
    transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease, background .25s ease;
}
.stat-card:hover{
    transform:translateY(-4px) scale(1.02);
    box-shadow:0 12px 32px rgba(139,111,94,.18);
    border-color:var(--gold);
    background:linear-gradient(135deg,#fffdf9,#fdf5ec);
}
.stat-card:hover .stat-icon{ transform:scale(1.12); }
.stat-card:hover .stat-icon.rose{ background:rgba(244,114,182,.22); }
.stat-card:hover .stat-icon.blue{ background:rgba(59,130,246,.22); }
.stat-card:hover .stat-num{ color:var(--primary); }
.stat-icon{
    width:50px;height:50px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;flex-shrink:0;
    transition:transform .25s ease, background .25s ease;
}
.stat-icon.gold{background:rgba(214,193,163,.2);color:var(--primary);}
.stat-icon.green{background:rgba(16,185,129,.12);color:#10b981;}
.stat-icon.rose{background:rgba(244,114,182,.12);color:#f472b6;}
.stat-icon.blue{background:rgba(59,130,246,.12);color:#3b82f6;}
.stat-num{font-size:26px;font-weight:700;color:var(--text);font-family:'Playfair Display',serif;line-height:1;transition:color .25s ease;}
.stat-lbl{font-size:12px;color:var(--text-lt);margin-top:3px;}

/* ━━ CARDS ━━ */
.cms-card{
    background:#fff;border-radius:16px;
    border:1px solid var(--border);
    box-shadow:0 2px 12px rgba(139,111,94,.06);
    overflow:hidden;margin-bottom:22px;
}
.cms-card-header{
    padding:15px 20px;border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:10px;
    background:linear-gradient(135deg,rgba(139,111,94,.05),transparent);
}
.cms-card-header i{color:var(--primary);font-size:15px;}
.cms-card-header h3{font-size:14.5px;font-weight:700;margin:0;}
.cms-card-header .ms-auto{margin-left:auto;}
.cms-card-body{padding:20px;}

/* ━━ FORM ━━ */
.form-group{margin-bottom:16px;}
label{font-size:11.5px;font-weight:600;color:var(--text-mid);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;}
input[type=text],input[type=url],input[type=tel],textarea,select{
    width:100%;padding:10px 13px;
    border:1.5px solid var(--border);
    border-radius:10px;font-size:13.5px;
    font-family:'Poppins',sans-serif;
    background:#fff;color:var(--text);
    outline:none;transition:border-color .2s;
}
input[type=text]:focus,input[type=url]:focus,input[type=tel]:focus,textarea:focus,select:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(139,111,94,.12);
}
textarea{resize:vertical;min-height:80px;}

/* ━━ BUTTONS ━━ */
.btn-primary-cms{
    background:linear-gradient(135deg,var(--primary),var(--gold));
    color:#fff;border:none;border-radius:10px;
    padding:10px 22px;font-size:13px;font-weight:600;
    font-family:'Poppins',sans-serif;cursor:pointer;
    display:inline-flex;align-items:center;gap:7px;
    transition:all .25s;box-shadow:0 4px 14px rgba(139,111,94,.25);
}
.btn-primary-cms:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(139,111,94,.35);}
.btn-danger-cms{
    background:rgba(239,68,68,.1);color:var(--danger);
    border:1.5px solid rgba(239,68,68,.2);border-radius:8px;
    padding:5px 12px;font-size:12px;font-weight:600;
    font-family:'Poppins',sans-serif;cursor:pointer;
    display:inline-flex;align-items:center;gap:5px;
    transition:all .2s;text-decoration:none;
}
.btn-danger-cms:hover{background:rgba(239,68,68,.18);color:var(--danger);}
.btn-edit-cms{
    background:rgba(139,111,94,.1);color:var(--primary);
    border:1.5px solid rgba(139,111,94,.2);border-radius:8px;
    padding:5px 12px;font-size:12px;font-weight:600;
    font-family:'Poppins',sans-serif;cursor:pointer;
    display:inline-flex;align-items:center;gap:5px;
    transition:all .2s;
}
.btn-edit-cms:hover{background:rgba(139,111,94,.18);}
.btn-sm-add{
    background:var(--cream);border:1.5px dashed var(--border);
    color:var(--primary);border-radius:10px;
    padding:8px 16px;font-size:12.5px;font-weight:600;
    font-family:'Poppins',sans-serif;cursor:pointer;
    display:inline-flex;align-items:center;gap:7px;
    transition:all .2s;
}
.btn-sm-add:hover{border-color:var(--primary);background:rgba(139,111,94,.06);}

/* ━━ TABLE ━━ */
.cms-table{width:100%;border-collapse:collapse;font-size:13px;}
.cms-table thead tr{background:#faf5f0;}
.cms-table th{padding:10px 13px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primary);border-bottom:2px solid var(--border);text-align:left;}
.cms-table td{padding:11px 13px;border-bottom:1px solid #f5ede6;vertical-align:middle;}
.cms-table tbody tr:hover{background:#fdf8f4;}
.cms-table tbody tr:last-child td{border-bottom:none;}
.actions-cell{display:flex;gap:5px;align-items:center;}

/* ━━ IMAGE ━━ */
.img-preview{width:52px;height:52px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border);}
.img-upload-box{
    border:2px dashed var(--border);border-radius:12px;
    padding:18px;text-align:center;cursor:pointer;
    transition:all .2s;background:#fdfaf7;display:block;
}
.img-upload-box:hover{border-color:var(--primary);background:rgba(139,111,94,.04);}
.img-upload-box i{font-size:24px;color:var(--gold-dk);margin-bottom:7px;display:block;}
.img-upload-box p{font-size:12px;color:var(--text-mid);margin:0;}
input[type=file]{display:none;}
.prev-thumb{width:100%;max-height:180px;object-fit:cover;border-radius:10px;display:none;margin-top:10px;border:1.5px solid var(--border);}

/* ━━ MODAL ━━ */
.cms-modal-overlay{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.5);z-index:5000;
    align-items:center;justify-content:center;padding:20px;
}
.cms-modal-overlay.open{display:flex;}
.cms-modal{
    background:#fff;border-radius:18px;
    width:100%;max-width:560px;
    max-height:92vh;overflow-y:auto;
    box-shadow:0 24px 60px rgba(0,0,0,.25);
    animation:modalIn .25s ease;
}
@keyframes modalIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.cms-modal-header{
    padding:16px 22px;border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
    background:linear-gradient(135deg,var(--primary),var(--gold));
    position:sticky;top:0;
}
.cms-modal-header h4{color:#fff;font-size:15px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.cms-modal-close{background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:18px;cursor:pointer;line-height:1;transition:.2s;}
.cms-modal-close:hover{background:rgba(255,255,255,.35);}
.cms-modal-body{padding:22px;}
.cms-modal-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;}

/* ━━ MISC ━━ */
.cat-badge{display:inline-block;padding:3px 10px;background:rgba(139,111,94,.1);color:var(--primary);border-radius:20px;font-size:11px;font-weight:600;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
@media(max-width:600px){.grid-2,.grid-3{grid-template-columns:1fr;}}
.empty-cms{text-align:center;padding:50px 20px;color:var(--text-lt);}
.empty-cms i{font-size:40px;color:var(--border);margin-bottom:12px;display:block;}
.empty-cms p{font-size:14px;}
.color-swatches{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
.swatch{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2.5px solid transparent;transition:.2s;}
.swatch:hover,.swatch.selected{border-color:#2d1f17;transform:scale(1.2);}

/* ━━ TAB NAV ━━ */
.tab-nav{
    display:flex;gap:3px;flex-wrap:wrap;
    margin-bottom:22px;background:#fff;padding:5px;
    border-radius:12px;border:1px solid var(--border);
    position:relative;z-index:1;
}
.tab-nav a{
    padding:8px 14px;border-radius:9px;font-size:12.5px;font-weight:600;
    color:var(--text-mid);text-decoration:none;
    display:flex;align-items:center;gap:6px;transition:all .2s;
}
.tab-nav a:hover{color:var(--primary);background:rgba(139,111,94,.07);}
.tab-nav a.active{background:linear-gradient(135deg,var(--primary),var(--gold-dk));color:#fff;box-shadow:0 4px 14px rgba(139,111,94,.28);}

/* ━━ PRICE PREVIEW ━━ */
.price-preview-card{
    background:#fff;border-radius:16px;overflow:hidden;
    border:1px solid rgba(214,193,163,.3);
    box-shadow:0 4px 18px rgba(139,111,94,.1);
}
.price-preview-header{
    display:flex;align-items:center;justify-content:space-between;
    background:linear-gradient(135deg,#8B6F5E,#D6C1A3);padding:14px 18px;
}
.price-preview-header span{color:#fff;font-weight:700;font-size:14px;font-family:'Poppins',sans-serif;}
.price-preview-header .badge{font-size:10px;color:rgba(255,255,255,.9);background:rgba(255,255,255,.2);border-radius:20px;padding:3px 10px;}
.price-preview-table{width:100%;border-collapse:collapse;font-size:13px;}
.price-preview-table th{padding:8px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#8B6F5E;background:#faf5f0;border-bottom:1px solid #f0e8df;}
.price-preview-table td{padding:9px 16px;border-bottom:1px solid #f5ede6;color:#444;}
.price-preview-table td:last-child{color:#8B6F5E;font-weight:700;text-align:right;}
.price-preview-table tr:last-child td{border-bottom:none;}

/* ━━ PRODUCT PREVIEW ━━ */
.product-preview-card{
    background:#fff;border-radius:16px;overflow:hidden;
    border:1px solid rgba(214,193,163,.3);
    box-shadow:0 4px 18px rgba(139,111,94,.1);
    transition:transform .3s;
}
.product-preview-card:hover{transform:translateY(-5px);}
.product-preview-img{width:100%;height:180px;object-fit:cover;display:block;}
.product-preview-body{padding:12px;}
.product-preview-name{font-weight:600;font-size:13px;color:#2d1f17;}
.product-preview-price{color:#8B6F5E;font-weight:700;font-size:14px;margin-top:2px;}

/* ━━ TESTIMONI PREVIEW ━━ */
.testi-preview-card{
    background:#fff;border-radius:16px;padding:20px;
    border:1px solid rgba(214,193,163,.3);
    box-shadow:0 4px 18px rgba(139,111,94,.1);
}
.testi-quote-icon{color:rgba(139,111,94,.2);font-size:20px;margin-bottom:10px;}
.testi-preview-text{font-size:13px;color:#555;line-height:1.7;margin-bottom:14px;}
.testi-preview-footer{display:flex;align-items:center;gap:12px;}
.testi-preview-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0;}
.testi-preview-name{font-weight:700;font-size:13px;color:#2d1f17;}
.testi-preview-tag{font-size:10px;background:rgba(139,111,94,.1);color:#8B6F5E;padding:2px 9px;border-radius:20px;font-weight:600;margin-left:auto;}

/* ━━ MOBILE ━━ */
@media(max-width:991px){
    .sidebar{display:none;}
    .main-wrap{margin-left:0;padding-top:60px;}
    .mobile-topbar{display:flex;}
    .topbar{display:none;}
    .content{padding:12px;}
    .stat-grid{grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px;}
    .stat-card{padding:14px;gap:10px;}
    .stat-icon{width:40px;height:40px;font-size:16px;border-radius:10px;}
    .stat-num{font-size:20px;}
    .stat-lbl{font-size:10px;}
    .cms-table{font-size:12px;}
    .tab-nav{overflow-x:auto;flex-wrap:nowrap;-webkit-overflow-scrolling:touch;padding-bottom:6px;gap:2px;}
    .tab-nav::-webkit-scrollbar{height:3px;}
    .tab-nav::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}
    .tab-nav a{white-space:nowrap;padding:7px 11px;font-size:11.5px;flex-shrink:0;}
    div[style*="grid-template-columns:1fr 380px"]{display:block!important;}
    div[style*="grid-template-columns:1fr 380px"] > div:last-child{margin-top:16px;}
    div[style*="grid-template-columns:1fr 1fr"]{display:block!important;}
    div[style*="grid-template-columns:1fr 1fr"] > div + div{margin-top:16px;}
    .cms-card-header{flex-wrap:wrap;gap:8px;}
    .cms-card-header .ms-auto{width:100%;margin-left:0!important;display:flex;justify-content:flex-end;}
    .cms-modal{border-radius:14px;}
    .cms-modal-body{padding:16px;}
    .actions-cell{flex-wrap:wrap;}
    .btn-edit-cms,.btn-danger-cms{font-size:11px;padding:4px 9px;}
}
@media(max-width:420px){
    .stat-grid{grid-template-columns:1fr 1fr;}
    .grid-2,.grid-3{grid-template-columns:1fr!important;}
}

/* ━━ HERO PREVIEW ━━ */
.hero-preview{
    border-radius:16px;overflow:hidden;
    border:1px solid var(--border);
    position:relative;background:#1a1a1a;
}
.hero-preview img{width:100%;height:260px;object-fit:cover;display:block;opacity:.65;}
.hero-preview-text{
    position:absolute;inset:0;display:flex;flex-direction:column;
    align-items:center;justify-content:center;text-align:center;
    padding:20px;
}
.hero-preview-title{font-family:'Playfair Display',serif;font-size:24px;color:#fff;font-weight:700;text-shadow:0 2px 12px rgba(0,0,0,.5);}
.hero-preview-sub{color:rgba(255,255,255,.85);font-size:13px;margin-top:8px;}
.hero-preview-btns{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;justify-content:center;}
.hero-preview-btn-p{background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:30px;padding:9px 20px;font-size:12px;font-weight:600;cursor:default;}
.hero-preview-btn-s{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.7);border-radius:30px;padding:9px 20px;font-size:12px;font-weight:600;cursor:default;}

/* ━━ PROFIL SECTION ━━ */
.profil-value-items{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;}
.profil-value-item{
    display:flex;align-items:center;gap:8px;background:#fdfaf7;
    border:1px solid rgba(214,193,163,.4);border-radius:10px;
    padding:10px 14px;font-family:'Poppins',sans-serif;
    font-size:12.5px;font-weight:500;color:#5A4A42;
}
.profil-value-item i{color:#8B6F5E;font-size:14px;}
</style>
</head>
<body>

<!-- MOBILE TOPBAR -->
<div class="mobile-topbar">
    <div class="brand"><i class="fas fa-spa me-2"></i>NISWÀ BEAUTY</div>
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="dashboard.php" style="color:var(--gold);font-size:13px;text-decoration:none;padding:6px 10px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;gap:5px;font-weight:600;"><i class="fa-solid fa-arrow-left"></i></a>
        <a href="index.php" target="_blank" style="color:var(--gold);font-size:13px;text-decoration:none;padding:6px 10px;background:rgba(255,255,255,.08);border-radius:8px;display:flex;align-items:center;gap:5px;font-weight:600;"><i class="fa-solid fa-eye"></i></a>
        <form method="GET" action="cms.php" style="margin:0;" onsubmit="return confirm('Yakin ingin logout?')">
            <input type="hidden" name="logout" value="1">
            <button type="submit" style="background:rgba(255,255,255,.08);border:none;cursor:pointer;color:#e11d48;font-size:13px;padding:6px 10px;border-radius:8px;display:flex;align-items:center;"><i class="fa-solid fa-right-from-bracket"></i></button>
        </form>
        <button class="mobile-hamburger" onclick="openDrawer()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</div>

<!-- MOBILE DRAWER OVERLAY -->
<div class="mobile-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

<!-- MOBILE DRAWER -->
<div class="mobile-drawer" id="mobileDrawer">
    <div class="sidebar-brand">
        <i class="fas fa-spa me-2"></i>NISWÀ BEAUTY
        <small>CMS ADMIN PANEL</small>
    </div>
    <ul class="nav-list" style="margin-top:8px;">
        <li><a href="cms.php?tab=hero"         onclick="closeDrawer()"><i class="fa-solid fa-image"></i> Hero & Slider</a></li>
        <li><a href="cms.php?tab=services"     onclick="closeDrawer()"><i class="fa-solid fa-scissors"></i> Layanan</a></li>
        <li><a href="cms.php?tab=prices"       onclick="closeDrawer()"><i class="fa-solid fa-tag"></i> Daftar Harga</a></li>
        <li><a href="cms.php?tab=products"     onclick="closeDrawer()"><i class="fa-solid fa-box-open"></i> Produk</a></li>
        <li><a href="cms.php?tab=testimoni"    onclick="closeDrawer()"><i class="fa-solid fa-comment-dots"></i> Testimoni</a></li>
        <li><a href="cms.php?tab=profil"       onclick="closeDrawer()"><i class="fa-solid fa-store"></i> Profil Toko</a></li>
        <li><a href="cms.php?tab=kontak"       onclick="closeDrawer()"><i class="fa-solid fa-location-dot"></i> Kontak & Maps</a></li>
        <li><a href="cms.php?tab=navbar"       onclick="closeDrawer()"><i class="fa-solid fa-bars"></i> Navbar</a></li>
        <li><a href="cms.php?tab=footer"       onclick="closeDrawer()"><i class="fa-solid fa-grip-lines"></i> Footer</a></li>
        <li><a href="cms.php?tab=booking_page" onclick="closeDrawer()"><i class="fa-solid fa-calendar-alt"></i> Halaman Booking</a></li>
        <li><a href="cms.php?tab=orders" onclick="closeDrawer()"><i class="fa-solid fa-receipt"></i> Pesanan Masuk</a></li>
    </ul>
    <div class="mobile-drawer-user">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>
</div>

<!-- ══ SIDEBAR (desktop) ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-spa me-2"></i>NISWÀ BEAUTY
        <small>CMS ADMIN PANEL</small>
    </div>
    <div class="sidebar-nav-wrap">
    <ul class="nav-list" style="margin-top:8px;">
        <li><a href="cms.php?tab=hero"         class="<?= $activeTab==='hero'         ? 'active':'' ?>"><i class="fa-solid fa-image"></i> Hero & Slider</a></li>
        <li><a href="cms.php?tab=services"     class="<?= $activeTab==='services'     ? 'active':'' ?>"><i class="fa-solid fa-scissors"></i> Layanan</a></li>
        <li><a href="cms.php?tab=prices"       class="<?= $activeTab==='prices'       ? 'active':'' ?>"><i class="fa-solid fa-tag"></i> Daftar Harga</a></li>
        <li><a href="cms.php?tab=products"     class="<?= $activeTab==='products'     ? 'active':'' ?>"><i class="fa-solid fa-box-open"></i> Produk</a></li>
        <li><a href="cms.php?tab=testimoni"    class="<?= $activeTab==='testimoni'    ? 'active':'' ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a></li>
        <li><a href="cms.php?tab=profil"       class="<?= $activeTab==='profil'       ? 'active':'' ?>"><i class="fa-solid fa-store"></i> Profil Toko</a></li>
        <li><a href="cms.php?tab=kontak"       class="<?= $activeTab==='kontak'       ? 'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Kontak & Maps</a></li>
        <li><a href="cms.php?tab=navbar"       class="<?= $activeTab==='navbar'       ? 'active':'' ?>"><i class="fa-solid fa-bars"></i> Navbar</a></li>
        <li><a href="cms.php?tab=footer"       class="<?= $activeTab==='footer'       ? 'active':'' ?>"><i class="fa-solid fa-grip-lines"></i> Footer</a></li>
        <li><a href="cms.php?tab=booking_page" class="<?= $activeTab==='booking_page' ? 'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Halaman Booking</a></li>
        <li><a href="cms.php?tab=orders"         class="<?= $activeTab==='orders'         ? 'active':'' ?>"><i class="fa-solid fa-receipt"></i> Pesanan Masuk</a></li>
    </ul>
    </div><!-- /.sidebar-nav-wrap -->
    <div class="sidebar-user">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main-wrap">
    <!-- Topbar -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <button class="mobile-menu-btn" onclick="openDrawer()"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Panel <span>CMS</span> — NISWÀ BEAUTY</div>
        </div>
        <div class="topbar-right">
            <span style="font-size:12px;color:var(--text-mid);"><i class="fa-regular fa-circle-user" style="margin-right:4px;"></i><?= htmlspecialchars($_SESSION['user']) ?></span>
            <a href="dashboard.php" class="topbar-btn topbar-btn-dashboard">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <a href="index.php" target="_blank" class="topbar-btn topbar-btn-website">
                <i class="fa-solid fa-eye"></i> Website
            </a>
            <form method="GET" action="cms.php" style="margin:0;" onsubmit="return confirm('Yakin ingin logout?')">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="topbar-btn-logout" title="Logout" style="background:none;border:none;cursor:pointer;"><i class="fa-solid fa-right-from-bracket"></i></button>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <?php if ($saved): ?>
    <div class="toast-bar" id="toastBar">
        <i class="fa-solid fa-circle-check"></i> Perubahan berhasil disimpan!
    </div>
    <script>setTimeout(()=>{const t=document.getElementById('toastBar');if(t)t.style.opacity='0';},3200);</script>
    <?php endif; ?>

    <div class="content">

        <!-- Stat Cards (always shown) -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon rose"><i class="fa-solid fa-box-open"></i></div>
                <div><div class="stat-num"><?= $totalProducts ?></div><div class="stat-lbl">Produk Aktif</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-scissors"></i></div>
                <div><div class="stat-num"><?= $totalServices ?></div><div class="stat-lbl">Layanan Terdaftar</div></div>
            </div>
        </div>

        <!-- Tab Nav -->
        <div class="tab-nav">
            <a href="cms.php?tab=hero"         class="<?= $activeTab==='hero'         ? 'active':'' ?>"><i class="fa-solid fa-image"></i> Hero</a>
            <a href="cms.php?tab=services"     class="<?= $activeTab==='services'     ? 'active':'' ?>"><i class="fa-solid fa-scissors"></i> Layanan</a>
            <a href="cms.php?tab=prices"       class="<?= $activeTab==='prices'       ? 'active':'' ?>"><i class="fa-solid fa-tag"></i> Harga</a>
            <a href="cms.php?tab=products"     class="<?= $activeTab==='products'     ? 'active':'' ?>"><i class="fa-solid fa-box-open"></i> Produk</a>
            <a href="cms.php?tab=testimoni"    class="<?= $activeTab==='testimoni'    ? 'active':'' ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a>
            <a href="cms.php?tab=profil"       class="<?= $activeTab==='profil'       ? 'active':'' ?>"><i class="fa-solid fa-store"></i> Profil</a>
            <a href="cms.php?tab=kontak"       class="<?= $activeTab==='kontak'       ? 'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Kontak</a>
            <a href="cms.php?tab=navbar"       class="<?= $activeTab==='navbar'       ? 'active':'' ?>"><i class="fa-solid fa-bars"></i> Navbar</a>
            <a href="cms.php?tab=footer"       class="<?= $activeTab==='footer'       ? 'active':'' ?>"><i class="fa-solid fa-grip-lines"></i> Footer</a>
            <a href="cms.php?tab=booking_page" class="<?= $activeTab==='booking_page' ? 'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Booking</a>
            <a href="cms.php?tab=orders"       class="<?= $activeTab==='orders'       ? 'active':'' ?>"><i class="fa-solid fa-receipt"></i> Pesanan</a>
        </div>

<?php /* ════════ TAB: HERO ════════ */ ?>
<?php if ($activeTab === 'hero'): ?>

        <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">
            <div class="cms-card">
                <div class="cms-card-header">
                    <i class="fa-solid fa-image"></i>
                    <h3>Hero & Slider Utama</h3>
                </div>
                <div class="cms-card-body">
                    <form method="POST" action="cms.php?action=save_hero" enctype="multipart/form-data" id="heroForm">
                        <div class="grid-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-heading" style="margin-right:4px;"></i>Judul Hero</label>
                                <input type="text" name="hero_title" id="heroTitle" value="<?= htmlspecialchars($hero['title']) ?>" placeholder="Judul utama" oninput="updateHeroPreview()">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-align-left" style="margin-right:4px;"></i>Subjudul</label>
                                <input type="text" name="hero_subtitle" id="heroSubtitle" value="<?= htmlspecialchars($hero['subtitle']) ?>" placeholder="Teks di bawah judul" oninput="updateHeroPreview()">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-hand-pointer" style="margin-right:4px;"></i>Tombol Utama</label>
                                <input type="text" name="btn_primary" id="heroBtnP" value="<?= htmlspecialchars($hero['btn_primary']) ?>" oninput="updateHeroPreview()">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-hand-pointer" style="margin-right:4px;"></i>Tombol Kedua</label>
                                <input type="text" name="btn_secondary" id="heroBtnS" value="<?= htmlspecialchars($hero['btn_secondary']) ?>" oninput="updateHeroPreview()">
                            </div>
                        </div>

                        <div class="grid-3" style="margin-top:8px;">
                            <?php foreach ([1,2,3] as $n):
                                $imgVal = $hero["img$n"]; ?>
                            <div class="form-group">
                                <label><i class="fa-regular fa-image" style="margin-right:4px;"></i>Gambar Slider <?= $n ?></label>
                                <label class="img-upload-box" for="hero_img<?= $n ?>_input">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <p>Upload gambar<?= $n === 1 ? '<br><small style="color:var(--text-lt);">Gambar utama</small>' : '' ?></p>
                                </label>
                                <input type="file" id="hero_img<?= $n ?>_input" name="hero_img<?= $n ?>" accept="image/*" onchange="previewImg(this,'prev_hero<?= $n ?>')">
                                <?php if ($imgVal): ?>
                                <div style="position:relative;margin-top:10px;">
                                    <img src="<?= htmlspecialchars($imgVal) ?>" class="prev-thumb" id="prev_hero<?= $n ?>" style="display:block;margin-top:0;">
                                    <a href="cms.php?action=delete_hero_img&n=<?= $n ?>" onclick="return confirm('Hapus foto ini?')"
                                       style="position:absolute;top:6px;right:6px;background:rgba(239,68,68,.9);color:#fff;border-radius:6px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;" title="Hapus foto">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <img src="" class="prev-thumb" id="prev_hero<?= $n ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn-primary-cms">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Hero Live Preview -->
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Live
                </div>
                <div class="hero-preview">
                    <?php if ($hero['img1']): ?>
                    <img src="<?= htmlspecialchars($hero['img1']) ?>" alt="" id="heroPreviewImg">
                    <?php else: ?>
                    <div style="height:260px;background:linear-gradient(135deg,#5A4A42,#8B6F5E);"></div>
                    <?php endif; ?>
                    <div class="hero-preview-text">
                        <div class="hero-preview-title" id="previewTitle"><?= htmlspecialchars($hero['title']) ?></div>
                        <div class="hero-preview-sub" id="previewSub"><?= htmlspecialchars($hero['subtitle']) ?></div>
                        <div class="hero-preview-btns">
                            <div class="hero-preview-btn-p" id="previewBtnP"><i class="fa-solid fa-calendar-alt" style="margin-right:6px;"></i><?= htmlspecialchars($hero['btn_primary']) ?></div>
                            <div class="hero-preview-btn-s" id="previewBtnS"><?= htmlspecialchars($hero['btn_secondary']) ?> <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i></div>
                        </div>
                    </div>
                </div>
                <div style="font-size:11px;color:var(--text-lt);margin-top:8px;text-align:center;">
                    <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Preview berubah realtime saat Anda mengetik
                </div>
            </div>
        </div>

<?php /* ════════ TAB: SERVICES ════════ */ ?>
<?php elseif ($activeTab === 'services'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-scissors"></i>
                <h3>Daftar Layanan</h3>
                <div class="ms-auto" style="display:flex;gap:8px;align-items:center;">
                    <div style="font-size:11.5px;color:var(--text-lt);display:flex;align-items:center;gap:5px;">
                        <i class="fa-solid fa-circle-info"></i> Layanan tampil di section "Layanan Kami"
                    </div>
                    <button class="btn-sm-add" onclick="openAddService()">
                        <i class="fa-solid fa-plus"></i> Tambah Layanan
                    </button>
                </div>
            </div>
            <!-- Search Services -->
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--cream);">
                <div style="position:relative;max-width:320px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-lt);font-size:13px;"></i>
                    <input type="text" id="searchServices" placeholder="Cari layanan..." oninput="filterTable('searchServices','servicesTable')"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text-dk);background:#fff;outline:none;">
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($servicesRows && mysqli_num_rows($servicesRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table" id="servicesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Preview</th>
                            <th>Nama Layanan</th>
                            <th>Galeri (jumlah foto)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($servicesRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if ($row['image']): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview">
                            <?php else: ?>
                            <div style="width:52px;height:52px;background:var(--cream);border-radius:8px;border:1.5px dashed var(--border);display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid fa-image" style="color:var(--border);"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td>
                            <?php
                            $gcount = $row['gallery'] ? count(array_filter(array_map('trim', explode(',', $row['gallery'])))) : 0;
                            ?>
                            <span class="cat-badge"><?= $gcount ?> foto</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditService(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=services&action=delete_service&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus layanan ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-scissors"></i>
                    <p>Belum ada layanan. Klik "Tambah Layanan" untuk mulai.<br>
                    <small style="color:var(--text-lt);">Layanan default dari website (Haircut, Coloring, dll) masih ditampilkan dari kode statis.</small></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Default services info -->
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-circle-info"></i>
                <h3>Layanan Default Website</h3>
            </div>
            <div class="cms-card-body">
                <p style="font-size:13px;color:var(--text-mid);margin-bottom:14px;">Layanan berikut sudah ada di website secara default. Untuk mengedit, tambahkan di atas dengan nama yang sama dan foto baru.</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php foreach (['Haircut','Coloring','Nailart','Hair Treatment','Foot SPA','Henna Series','Press on Nail','Eye Lash'] as $svc): ?>
                    <span class="cat-badge"><i class="fa-solid fa-check" style="margin-right:4px;color:var(--success);"></i><?= $svc ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Modal: Service -->
        <div class="cms-modal-overlay" id="modalService">
            <div class="cms-modal">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-scissors"></i> <span id="serviceModalTitle">Tambah Layanan</span></h4>
                    <button class="cms-modal-close" onclick="closeModal('modalService')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_service" enctype="multipart/form-data">
                    <input type="hidden" name="service_id" id="serviceId" value="0">
                    <div class="cms-modal-body">
                        <div class="form-group">
                            <label>Nama Layanan</label>
                            <input type="text" name="service_name" id="serviceName" placeholder="cth: Nail Art, Haircut, Foot Spa..." required>
                        </div>
                        <div class="form-group">
                            <label>Foto Utama Layanan</label>
                            <label class="img-upload-box" for="svcImgInput">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Klik untuk upload foto layanan<br><small style="color:var(--text-lt);">JPG, PNG, WEBP</small></p>
                            </label>
                            <input type="file" id="svcImgInput" name="service_image" accept="image/*" onchange="previewImg(this,'svcPreview')">
                            <img src="" class="prev-thumb" id="svcPreview">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-regular fa-images" style="margin-right:4px;"></i>Path Galeri <small style="text-transform:none;color:var(--text-lt);">(pisahkan dengan koma)</small></label>
                            <textarea name="service_gallery" id="serviceGallery" placeholder="image/foto1.jpg, image/foto2.jpg, image/foto3.jpg" rows="3"></textarea>
                            <small style="font-size:11px;color:var(--text-lt);"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Path relatif dari folder website. Akan tampil di gallery modal saat pengunjung klik layanan.</small>
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalService')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: PRICES ════════ */ ?>
<?php elseif ($activeTab === 'prices'): ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">
            <!-- Edit Panel -->
            <div>
                <div class="cms-card">
                    <div class="cms-card-header">
                        <i class="fa-solid fa-tag"></i>
                        <h3>Kelola Daftar Harga</h3>
                        <div class="ms-auto">
                            <button class="btn-sm-add" onclick="openAddPrice()">
                                <i class="fa-solid fa-plus"></i> Tambah Item
                            </button>
                        </div>
                    </div>
                    <!-- Search Prices -->
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--cream);">
                        <div style="position:relative;">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-lt);font-size:13px;"></i>
                            <input type="text" id="searchPrices" placeholder="Cari kategori / nama layanan / harga..." oninput="filterTable('searchPrices','pricesTable')"
                                style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text-dk);background:#fff;outline:none;box-sizing:border-box;">
                        </div>
                    </div>
                    <div class="cms-card-body" style="padding:0;">
                        <?php if ($pricesRows && mysqli_num_rows($pricesRows) > 0):
                              mysqli_data_seek($pricesRows, 0); ?>
                        <div style="overflow-x:auto;">
                        <table class="cms-table" id="pricesTable">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Harga</th>
                                    <th>Deskripsi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = mysqli_fetch_assoc($pricesRows)): ?>
                            <tr>
                                <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td><?php if(!empty($row['image'])): ?><img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview"><?php else: ?><span style="color:var(--text-lt);font-size:12px;">—</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['price']) ?></td>
                                <td style="font-size:12px;color:var(--text-mid);max-width:180px;"><?= htmlspecialchars(substr($row['description'] ?? '', 0, 60)) ?><?= strlen($row['description'] ?? '') > 60 ? '…' : '' ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn-edit-cms" onclick='openEditPrice(<?= json_encode($row) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <a href="cms.php?tab=prices&action=delete_price&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus item ini?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-cms" style="padding:30px 20px;">
                            <i class="fa-solid fa-tag"></i>
                            <p>Belum ada harga di database.<br><small>Harga default dari website tetap tampil.</small></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Preview Panel (mirroring website) -->
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview (tampilan website)
                </div>
                <?php
                // Show DB prices if any, else show defaults
                $displayPrices = [];
                if ($pricesRows && mysqli_num_rows($pricesRows) > 0) {
                    mysqli_data_seek($pricesRows, 0);
                    while ($r = mysqli_fetch_assoc($pricesRows)) $displayPrices[$r['category']][] = $r;
                } else {
                    foreach ($defaultPriceList as $cat => $items)
                        foreach ($items as $item)
                            $displayPrices[$cat][] = $item;
                }
                // Urutkan kategori sesuai catOrder di index.php
                $catOrder = ["Brow & Lash","Treatment Spa","Henna Series","Nail Art & Services","Rambut"];
                $sortedDisplay = [];
                foreach ($catOrder as $c) { if (isset($displayPrices[$c])) $sortedDisplay[$c] = $displayPrices[$c]; }
                foreach ($displayPrices as $c => $v) { if (!isset($sortedDisplay[$c])) $sortedDisplay[$c] = $v; }
                $displayPrices = $sortedDisplay;
                foreach ($displayPrices as $cat => $items): ?>
                <div class="price-preview-card" style="margin-bottom:12px;">
                    <div class="price-preview-header">
                        <span><?= htmlspecialchars($cat) ?></span>
                        <span class="badge"><?= count($items) ?> layanan</span>
                    </div>
                    <table class="price-preview-table">
                        <thead><tr><th>Layanan</th><th>Harga</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td style="display:flex;align-items:center;gap:8px;">
                                <?php if(!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid var(--border);">
                                <?php else: ?>
                                <span style="width:32px;height:32px;background:var(--cream);border-radius:6px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border);"><i class="fa-solid fa-image" style="font-size:11px;color:var(--border);"></i></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['name']) ?>
                            </td>
                            <td><?= htmlspecialchars($item['price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modal: Price -->
        <div class="cms-modal-overlay" id="modalPrice">
            <div class="cms-modal">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-tag"></i> <span id="priceModalTitle">Tambah Item Harga</span></h4>
                    <button class="cms-modal-close" onclick="closeModal('modalPrice')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_price" enctype="multipart/form-data">
                    <input type="hidden" name="price_id" id="priceId" value="0">
                    <div class="cms-modal-body">
                        <div class="form-group">
                            <label>Kategori</label>
                            <input type="text" name="price_cat" id="priceCat" placeholder="cth: Henna Series, Rambut, Nail Art..." required list="catSuggestions">
                            <datalist id="catSuggestions">
                                <?php foreach (array_keys($defaultPriceList) as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label>Nama Layanan</label>
                            <input type="text" name="price_name" id="priceName" placeholder="cth: Smoothing Rambut Pendek" required>
                        </div>
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="text" name="price_val" id="priceVal" placeholder="cth: Rp 150.000 atau Rp 100.000 - 300.000" required>
                        </div>
                        <div class="form-group">
                            <label>Foto Layanan <small style="text-transform:none;color:var(--text-lt);">(opsional — tampil di popup website saat baris diklik)</small></label>
                            <label class="img-upload-box" for="priceImgInput">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Klik untuk upload foto<br><small style="color:var(--text-lt);">JPG, PNG, WEBP</small></p>
                            </label>
                            <input type="file" id="priceImgInput" name="price_image" accept="image/*" onchange="previewImg(this,'pricePreview')">
                            <img src="" class="prev-thumb" id="pricePreview">
                        </div>
                        <div class="form-group">
                            <label>Deskripsi <small style="text-transform:none;color:var(--text-lt);">(opsional — tampil saat baris diklik di website)</small></label>
                            <textarea name="price_desc" id="priceDesc" rows="3" placeholder="cth: Termasuk creambath, blow dry, dan vitamin rambut. Durasi ±60 menit."></textarea>
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalPrice')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: PRODUCTS ════════ */ ?>
<?php elseif ($activeTab === 'products'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-box-open"></i>
                <h3>Produk Press On Nail Collection</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openAddProduct()">
                        <i class="fa-solid fa-plus"></i> Tambah Produk
                    </button>
                </div>
            </div>
            <!-- Search + Filter Products -->
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--cream);display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <div style="position:relative;flex:1;min-width:180px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-lt);font-size:13px;"></i>
                    <input type="text" id="searchProducts" placeholder="Cari nama produk / harga..." oninput="filterProducts()"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text-dk);background:#fff;outline:none;box-sizing:border-box;">
                </div>
                <select id="filterProductCat" onchange="filterProducts()"
                    style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;color:var(--text-dk);background:#fff;outline:none;cursor:pointer;">
                    <option value="">Semua Kategori</option>
                    <option value="simple">Simple</option>
                    <option value="glam">Glam</option>
                    <option value="wedding">Wedding</option>
                </select>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($productsRows && mysqli_num_rows($productsRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Foto</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Diskon</th>
                            <th>Min. Beli</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($productsRows)): ?>
                    <tr data-cat="<?= htmlspecialchars($row['category']) ?>">
                        <td><?= $no++ ?></td>
                        <td><?php if($row['image']): ?><img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview"><?php else: ?><span style="color:var(--text-lt);">—</span><?php endif; ?></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                        <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['price']) ?></td>
                        <td style="text-align:center;">
                            <?php if (!empty($row['discount_pct']) && $row['discount_pct'] > 0): ?>
                                <span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:20px;font-size:12px;font-weight:700;"><?= (int)$row['discount_pct'] ?>%</span>
                            <?php else: ?>
                                <span style="color:var(--text-lt);">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;font-size:13px;">
                            <?php if (!empty($row['min_purchase']) && $row['min_purchase'] > 0): ?>
                                <span style="color:#7c5c50;">Rp <?= number_format((int)$row['min_purchase'],0,',','.') ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-lt);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditProduct(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=products&action=delete_product&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus produk ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>

                <!-- Product Card Preview Grid -->
                <div style="padding:20px;border-top:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:14px;">
                        <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Tampilan Website
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;">
                    <?php
                    if ($productsRows) { mysqli_data_seek($productsRows, 0); }
                    while ($row = mysqli_fetch_assoc($productsRows)): ?>
                    <div class="product-preview-card">
                        <?php if ($row['image']): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" class="product-preview-img" alt="">
                        <?php else: ?>
                        <div class="product-preview-img" style="background:var(--cream);display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-image" style="font-size:28px;color:var(--border);"></i>
                        </div>
                        <?php endif; ?>
                        <div class="product-preview-body">
                            <div class="product-preview-name"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="product-preview-price"><?= htmlspecialchars($row['price']) ?></div>
                            <?php if (!empty($row['discount_pct']) && $row['discount_pct'] > 0): ?>
                            <div style="margin-top:4px;">
                                <span style="background:#e74c3c;color:#fff;padding:1px 7px;border-radius:20px;font-size:10px;font-weight:700;">DISKON <?= (int)$row['discount_pct'] ?>%</span>
                                <?php if (!empty($row['min_purchase']) && $row['min_purchase'] > 0): ?>
                                <span style="color:var(--text-lt);font-size:10px;display:block;margin-top:2px;">Min. beli Rp <?= number_format((int)$row['min_purchase'],0,',','.') ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <span class="cat-badge" style="font-size:10px;margin-top:5px;display:inline-block;"><?= htmlspecialchars($row['category']) ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    </div>
                </div>

                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Belum ada produk di database.<br><small style="color:var(--text-lt);">Produk default dari website tetap tampil.</small></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Product -->
        <div class="cms-modal-overlay" id="modalProduct">
            <div class="cms-modal">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-box-open"></i> <span id="prodModalTitle">Tambah Produk</span></h4>
                    <button class="cms-modal-close" onclick="closeModal('modalProduct')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_product" enctype="multipart/form-data">
                    <input type="hidden" name="prod_id" id="prodId" value="0">
                    <div class="cms-modal-body">
                        <div class="form-group">
                            <label>Nama Produk</label>
                            <input type="text" name="prod_name" id="prodName" placeholder="cth: Cat Eye Nails" required>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Harga</label>
                                <input type="text" name="prod_price" id="prodPrice" placeholder="cth: Rp 22.000" required>
                            </div>
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="prod_category" id="prodCategory">
                                    <option value="simple">Simple</option>
                                    <option value="glam">Glam</option>
                                    <option value="wedding">Wedding</option>
                                </select>
                            </div>
                        </div>
                        <!-- ── FITUR DISKON ── -->
                        <div style="background:linear-gradient(135deg,#fff5f5,#fff0f0);border:1.5px solid #f5c6c6;border-radius:10px;padding:14px 16px;margin-bottom:12px;box-sizing:border-box;width:100%;overflow:hidden;">
                            <div style="font-size:12px;font-weight:700;color:#c0392b;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
                                <i class="fa-solid fa-tag" style="margin-right:5px;"></i>Pengaturan Diskon
                            </div>
                            <table style="width:100%;border-collapse:separate;border-spacing:10px 0;table-layout:fixed;">
                                <tr>
                                    <td style="width:40%;padding:0;vertical-align:top;">
                                        <label style="font-size:12px;font-weight:600;color:#7c5c50;display:block;margin-bottom:5px;">Diskon (%)</label>
                                        <div style="display:flex;align-items:center;border:1.5px solid #e0d5cf;border-radius:8px;background:#fff;overflow:hidden;">
                                            <input type="number" name="prod_discount_pct" id="prodDiscountPct"
                                                min="0" max="100" step="1" value="0" placeholder="0"
                                                style="flex:1;min-width:0;border:none;outline:none;padding:8px 6px 8px 10px;font-size:14px;font-family:inherit;background:transparent;"
                                                oninput="updateDiscountPreview()">
                                            <span style="padding:0 10px;color:#aaa;font-size:13px;font-weight:700;flex-shrink:0;">%</span>
                                        </div>
                                        <small style="color:#aaa;font-size:11px;">0 = tidak ada diskon</small>
                                    </td>
                                    <td style="width:60%;padding:0;vertical-align:top;">
                                        <label style="font-size:12px;font-weight:600;color:#7c5c50;display:block;margin-bottom:5px;">Minimal Pembelian (Rp)</label>
                                        <input type="number" name="prod_min_purchase" id="prodMinPurchase"
                                            min="0" step="1000" value="0" placeholder="0"
                                            style="width:100%;box-sizing:border-box;border:1.5px solid #e0d5cf;border-radius:8px;padding:8px 10px;font-size:14px;font-family:inherit;outline:none;background:#fff;"
                                            oninput="updateDiscountPreview()">
                                        <small style="color:#aaa;font-size:11px;">0 = tanpa syarat minimum</small>
                                    </td>
                                </tr>
                            </table>
                            <div id="discountPreview" style="display:none;margin-top:10px;padding:8px 12px;background:#fff;border-radius:8px;border:1px dashed #e74c3c;font-size:12px;color:#c0392b;"></div>
                        </div>
                        <div class="form-group">
                            <label>Foto Produk</label>
                            <label class="img-upload-box" for="prodImgInput">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Upload foto produk<br><small style="color:var(--text-lt);">Rasio 3:4 disarankan</small></p>
                            </label>
                            <input type="file" id="prodImgInput" name="prod_image" accept="image/*" onchange="previewImg(this,'prodPreview')">
                            <img src="" class="prev-thumb" id="prodPreview">
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalProduct')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: TESTIMONI ════════ */ ?>
<?php elseif ($activeTab === 'testimoni'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-comment-dots"></i>
                <h3>Testimoni Pelanggan</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openAddTesti()">
                        <i class="fa-solid fa-plus"></i> Tambah Testimoni
                    </button>
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($testiRows && mysqli_num_rows($testiRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Avatar</th>
                            <th>Nama</th>
                            <th>Layanan</th>
                            <th>Ulasan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($testiRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= htmlspecialchars($row['avatar_color']) ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;">
                                <?= strtoupper(substr($row['name'],0,1)) ?>
                            </div>
                        </td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['service_tag']) ?></span></td>
                        <td style="font-size:12px;color:var(--text-mid);max-width:200px;"><?= htmlspecialchars(substr($row['text'],0,70)) ?>...</td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditTesti(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="cms.php?tab=testimoni&action=delete_testi&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus testimoni ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>

                <!-- Card Preview -->
                <div style="padding:20px;border-top:1px solid var(--border);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:14px;">
                        <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Tampilan Website
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
                    <?php
                    mysqli_data_seek($testiRows, 0);
                    while ($row = mysqli_fetch_assoc($testiRows)): ?>
                    <div class="testi-preview-card">
                        <div class="testi-quote-icon"><i class="fa-solid fa-quote-left"></i></div>
                        <div class="testi-preview-text">"<?= htmlspecialchars($row['text']) ?>"</div>
                        <div class="testi-preview-footer">
                            <div class="testi-preview-avatar" style="background:<?= htmlspecialchars($row['avatar_color']) ?>;">
                                <?= strtoupper(substr($row['name'],0,1)) ?>
                            </div>
                            <div>
                                <div class="testi-preview-name"><?= htmlspecialchars($row['name']) ?></div>
                                <div style="font-size:10px;color:var(--warn);">★★★★★</div>
                            </div>
                            <span class="testi-preview-tag"><?= htmlspecialchars($row['service_tag']) ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    </div>
                </div>

                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-comment-dots"></i>
                    <p>Belum ada testimoni. Tambahkan testimoni pelanggan baru.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Testimoni -->
        <div class="cms-modal-overlay" id="modalTesti">
            <div class="cms-modal">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-comment-dots"></i> <span id="testiModalTitle">Tambah Testimoni</span></h4>
                    <button class="cms-modal-close" onclick="closeModal('modalTesti')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_testi">
                    <input type="hidden" name="testi_id" id="testiId" value="0">
                    <div class="cms-modal-body">
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <input type="text" name="testi_name" id="testiName" placeholder="cth: Ninda Ayu" required>
                            </div>
                            <div class="form-group">
                                <label>Tag Layanan</label>
                                <input type="text" name="testi_service" id="testiService" placeholder="cth: Nail Art & Foot Spa">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Teks Ulasan</label>
                            <textarea name="testi_text" id="testiText" placeholder="Tulis ulasan pelanggan... (tanpa tanda kutip)" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Warna Avatar</label>
                            <div class="color-swatches" id="colorSwatches">
                                <?php
                                $swatchColors = [
                                    'linear-gradient(135deg,#f9a8d4,#f472b6)',
                                    'linear-gradient(135deg,#6ee7b7,#34d399)',
                                    'linear-gradient(135deg,#fde68a,#f59e0b)',
                                    'linear-gradient(135deg,#a78bfa,#7c3aed)',
                                    'linear-gradient(135deg,#86efac,#16a34a)',
                                    'linear-gradient(135deg,#93c5fd,#3b82f6)',
                                    'linear-gradient(135deg,#fca5a5,#ef4444)',
                                    'linear-gradient(135deg,#D6C1A3,#8B6F5E)',
                                ];
                                foreach ($swatchColors as $sc): ?>
                                <div class="swatch" style="background:<?= $sc ?>;" data-color="<?= htmlspecialchars($sc) ?>" onclick="selectSwatch(this)"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="testi_color" id="testiColor" value="<?= htmlspecialchars($swatchColors[0]) ?>">
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalTesti')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: PROFIL TOKO ════════ */ ?>
<?php elseif ($activeTab === 'profil'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-crown"></i>
                <h3>Profil Pemilik</h3>
            </div>
            <div class="cms-card-body">
                <form method="POST" action="cms.php?action=save_profil" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-user" style="margin-right:4px;"></i>Nama Pemilik</label>
                            <input type="text" name="owner_name" value="<?= htmlspecialchars($profil['owner_name']) ?>" placeholder="Niswa">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-quote-left" style="margin-right:4px;"></i>Tagline Pemilik</label>
                            <input type="text" name="owner_tagline" value="<?= htmlspecialchars($profil['owner_tagline']) ?>" placeholder='"Kecantikan adalah..."'>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Biografi Pemilik (Paragraf 1)</label>
                        <textarea name="owner_bio1" rows="5" placeholder="Cerita awal mula perjalanan pemilik..."><?= htmlspecialchars($profil['owner_bio1']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Biografi Pemilik (Paragraf 2)</label>
                        <textarea name="owner_bio2" rows="5" placeholder="Lanjutan perjalanan..."><?= htmlspecialchars($profil['owner_bio2']) ?></textarea>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:16px;">
                        <i class="fa-solid fa-store" style="margin-right:5px;color:var(--primary);"></i>Tentang Toko
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nama Toko</label>
                            <input type="text" name="store_name" value="<?= htmlspecialchars($profil['store_name']) ?>" placeholder="NISWÀ BEAUTY">
                        </div>
                        <div class="form-group">
                            <label>Tagline Toko</label>
                            <input type="text" name="store_tagline" value="<?= htmlspecialchars($profil['store_tagline']) ?>" placeholder='"Premium Beauty..."'>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Biografi Toko (Paragraf 1)</label>
                        <textarea name="store_bio1" rows="4"><?= htmlspecialchars($profil['store_bio1']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Biografi Toko (Paragraf 2)</label>
                        <textarea name="store_bio2" rows="4"><?= htmlspecialchars($profil['store_bio2']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Toko</label>
                        <label class="img-upload-box" for="storeImgInput">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Upload foto toko / salon</p>
                        </label>
                        <input type="file" id="storeImgInput" name="store_image_file" accept="image/*" onchange="previewImg(this,'storeImgPreview')">
                        <?php if ($profil['store_image']): ?>
                        <img src="<?= htmlspecialchars($profil['store_image']) ?>" class="prev-thumb" id="storeImgPreview" style="display:block;">
                        <?php else: ?>
                        <img src="" class="prev-thumb" id="storeImgPreview">
                        <?php endif; ?>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:16px;">
                        <i class="fa-solid fa-heart" style="margin-right:5px;color:var(--primary);"></i>Value Items Toko
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-heart" style="margin-right:4px;color:var(--primary);"></i>Value 1</label>
                            <input type="text" name="value_item_1" value="<?= htmlspecialchars($profil['value_item_1'] ?? 'Pelayanan Tulus') ?>" placeholder="Pelayanan Tulus">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-shield-alt" style="margin-right:4px;color:var(--primary);"></i>Value 2</label>
                            <input type="text" name="value_item_2" value="<?= htmlspecialchars($profil['value_item_2'] ?? 'Produk Aman & Halal') ?>" placeholder="Produk Aman & Halal">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-star" style="margin-right:4px;color:var(--primary);"></i>Value 3</label>
                            <input type="text" name="value_item_3" value="<?= htmlspecialchars($profil['value_item_3'] ?? 'Kualitas Premium') ?>" placeholder="Kualitas Premium">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-smile" style="margin-right:4px;color:var(--primary);"></i>Value 4</label>
                            <input type="text" name="value_item_4" value="<?= htmlspecialchars($profil['value_item_4'] ?? 'Kepuasan Pelanggan') ?>" placeholder="Kepuasan Pelanggan">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-cms">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Semua Perubahan
                    </button>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: KONTAK ════════ */ ?>
<?php elseif ($activeTab === 'kontak'): ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

            <!-- Form Edit -->
            <div class="cms-card">
                <div class="cms-card-header">
                    <i class="fa-solid fa-location-dot"></i>
                    <h3>Kontak & Lokasi</h3>
                </div>
                <div class="cms-card-body">
                    <form method="POST" action="cms.php?action=save_kontak">
                        <div class="grid-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-store" style="margin-right:4px;"></i>Nama Salon</label>
                                <input type="text" name="salon_name" value="<?= htmlspecialchars($kontak['salon_name']) ?>" placeholder="NISWÀ BEAUTY">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-brands fa-whatsapp" style="margin-right:4px;"></i>WhatsApp <small style="text-transform:none;color:var(--text-lt);">(awali 62)</small></label>
                                <input type="tel" name="whatsapp" value="<?= htmlspecialchars($kontak['whatsapp']) ?>" placeholder="6288200690xxxx">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-map-pin" style="margin-right:4px;"></i>Alamat Lengkap</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($kontak['address']) ?>" placeholder="Jl. Watulumpang, Bangsri, Jepara">
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label><i class="fa-regular fa-clock" style="margin-right:4px;"></i>Jam Operasional</label>
                                <input type="text" name="hours" value="<?= htmlspecialchars($kontak['hours']) ?>" placeholder="Senin – Minggu, 08.00 – 20.00">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-envelope" style="margin-right:4px;"></i>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($kontak['email']) ?>" placeholder="niswabeauty15@gmail.com">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-link" style="margin-right:4px;"></i>Link Google Maps <small style="text-transform:none;color:var(--text-lt);">(tombol Petunjuk Arah)</small></label>
                            <input type="url" name="maps_link" value="<?= htmlspecialchars($kontak['maps_link']) ?>" placeholder="https://maps.app.goo.gl/...">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-code" style="margin-right:4px;"></i>Embed URL Maps <small style="text-transform:none;color:var(--text-lt);">(src iframe)</small></label>
                            <textarea name="maps_embed" rows="3" placeholder="https://www.google.com/maps/embed?pb=..."><?= htmlspecialchars($kontak['maps_embed']) ?></textarea>
                            <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Google Maps → Share → Embed a map → Salin URL dari atribut <code>src</code></small>
                        </div>
                        <button type="submit" class="btn-primary-cms">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview (sesuai tampilan website: maps kiri + info kanan) -->
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Lokasi (tampilan website)
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;border-radius:14px;overflow:hidden;border:1.5px solid var(--border);box-shadow:0 4px 18px rgba(139,111,94,0.10);min-height:320px;">
                    <!-- Kiri: Maps -->
                    <div style="position:relative;min-height:280px;">
                        <?php if ($kontak['maps_embed']): ?>
                        <iframe src="<?= htmlspecialchars($kontak['maps_embed']) ?>" width="100%" height="100%" style="border:0;display:block;position:absolute;inset:0;" loading="lazy"></iframe>
                        <?php else: ?>
                        <div style="position:absolute;inset:0;background:var(--cream);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;">
                            <i class="fa-solid fa-map" style="font-size:28px;color:var(--border);"></i>
                            <span style="font-size:12px;color:var(--text-lt);">Belum ada embed maps</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Kanan: Info -->
                    <div style="background:linear-gradient(160deg,#5A4A42 0%,#8B6F5E 100%);padding:20px 18px;display:flex;flex-direction:column;justify-content:center;gap:14px;">
                        <!-- Brand -->
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-spa" style="font-size:22px;color:#D6C1A3;flex-shrink:0;"></i>
                            <div>
                                <div style="font-family:'Playfair Display',serif;font-size:14px;font-weight:700;color:#fff;line-height:1.2;"><?= htmlspecialchars($kontak['salon_name']) ?></div>
                                <div style="font-size:10px;color:rgba(255,255,255,0.6);margin-top:2px;">Premium Beauty Experience</div>
                            </div>
                        </div>
                        <div style="border-top:1px solid rgba(255,255,255,0.15);"></div>
                        <!-- Detail items -->
                        <?php
                        $infoItems = [
                            ['fa-map-marker-alt', 'Alamat',          $kontak['address']],
                            ['fa-clock',          'Jam Operasional', $kontak['hours']],
                            ['fa-brands fa-whatsapp', 'WhatsApp',    '+' . $kontak['whatsapp']],
                            ['fa-envelope',       'Email',           $kontak['email']],
                        ];
                        foreach ($infoItems as [$ic,$lbl,$val]): ?>
                        <div style="display:flex;align-items:flex-start;gap:10px;">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="<?= strpos($ic,'fa-brands')===false ? 'fa-solid ' : '' ?><?= $ic ?>" style="color:#D6C1A3;font-size:12px;"></i>
                            </div>
                            <div>
                                <div style="font-size:9px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.7px;font-weight:600;"><?= $lbl ?></div>
                                <div style="font-size:11.5px;color:#fff;font-weight:500;margin-top:1px;line-height:1.35;"><?= htmlspecialchars($val ?: '—') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div style="border-top:1px solid rgba(255,255,255,0.15);"></div>
                        <!-- Action buttons -->
                        <div style="display:flex;gap:7px;">
                            <span style="flex:1;text-align:center;background:linear-gradient(135deg,#D6C1A3,#c4a882);color:#3a2e28;border-radius:50px;padding:7px 10px;font-size:11px;font-weight:700;">
                                <i class="fa-solid fa-directions"></i> Petunjuk Arah
                            </span>
                            <span style="flex:1;text-align:center;background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:50px;padding:7px 10px;font-size:11px;font-weight:600;">
                                <i class="fa-brands fa-whatsapp"></i> Chat Sekarang
                            </span>
                        </div>
                    </div>
                </div>
                <!-- WA link info -->
                <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-top:12px;font-size:12px;color:var(--text-mid);">
                    <i class="fa-brands fa-whatsapp" style="color:#25d366;margin-right:5px;"></i>
                    Link WA aktif: <strong>https://wa.me/<?= htmlspecialchars($kontak['whatsapp']) ?></strong>
                </div>
            </div>

        </div>

<?php /* ════════ TAB: NAVBAR ════════ */ ?>
<?php elseif ($activeTab === 'navbar'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-bars"></i>
                <h3>Edit Navbar</h3>
            </div>
            <div class="cms-card-body">
                <form method="POST" action="cms.php?action=save_navbar">

                    <!-- Brand -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-store" style="margin-right:5px;color:var(--primary);"></i>Brand
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-signature" style="margin-right:4px;"></i>Nama Brand (Navbar)</label>
                        <input type="text" name="brand_name" value="<?= htmlspecialchars($navbar['brand_name']) ?>" placeholder="NISWÀ BEAUTY">
                        <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Tampil di pojok kiri navbar sebagai logo teks.</small>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <!-- Menu Links -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-list" style="margin-right:5px;color:var(--primary);"></i>Label Menu
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-house" style="margin-right:4px;"></i>Menu Home</label>
                            <input type="text" name="menu_home" value="<?= htmlspecialchars($navbar['menu_home']) ?>" placeholder="Home">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-spa" style="margin-right:4px;"></i>Menu Services</label>
                            <input type="text" name="menu_services" value="<?= htmlspecialchars($navbar['menu_services']) ?>" placeholder="Services">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-box" style="margin-right:4px;"></i>Menu Product</label>
                            <input type="text" name="menu_product" value="<?= htmlspecialchars($navbar['menu_product']) ?>" placeholder="Product">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Menu About</label>
                            <input type="text" name="menu_about" value="<?= htmlspecialchars($navbar['menu_about']) ?>" placeholder="About">
                        </div>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <!-- Tombol Book Now -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-calendar-check" style="margin-right:5px;color:var(--primary);"></i>Tombol CTA
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-calendar-check" style="margin-right:4px;"></i>Teks Tombol "Book Now"</label>
                        <input type="text" name="btn_book_text" value="<?= htmlspecialchars($navbar['btn_book_text']) ?>" placeholder="Book Now">
                        <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Tombol utama di ujung kanan navbar yang mengarah ke halaman booking.</small>
                    </div>

                    <!-- Preview -->
                    <div style="background:var(--cream);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:12px;">
                            <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Navbar
                        </div>
                        <div style="background:#150d10;border-radius:10px;padding:14px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                            <div style="color:#D6C1A3;font-family:'Playfair Display',serif;font-weight:700;font-size:15px;"><?= htmlspecialchars($navbar['brand_name']) ?></div>
                            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                                <span style="color:#7a6870;font-size:12px;"><?= htmlspecialchars($navbar['menu_home']) ?></span>
                                <span style="color:#7a6870;font-size:12px;"><?= htmlspecialchars($navbar['menu_services']) ?></span>
                                <span style="color:#7a6870;font-size:12px;"><?= htmlspecialchars($navbar['menu_product']) ?></span>
                                <span style="color:#7a6870;font-size:12px;"><?= htmlspecialchars($navbar['menu_about']) ?></span>
                                <span style="color:#7a6870;font-size:11px;"><i class="fa-solid fa-user"></i></span>
                                <span style="background:linear-gradient(135deg,#D6C1A3,#8B6F5E);color:#fff;font-size:11px;padding:6px 14px;border-radius:20px;font-weight:600;">
                                    <i class="fa-solid fa-calendar-check" style="margin-right:4px;"></i><?= htmlspecialchars($navbar['btn_book_text']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-cms">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Navbar
                    </button>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: FOOTER ════════ */ ?>
<?php elseif ($activeTab === 'footer'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-grip-lines"></i>
                <h3>Edit Footer</h3>
            </div>
            <div class="cms-card-body">
                <form method="POST" action="cms.php?action=save_footer">

                    <!-- Brand Section -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-store" style="margin-right:5px;color:var(--primary);"></i>Brand & Deskripsi
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-signature" style="margin-right:4px;"></i>Nama Brand (Footer)</label>
                            <input type="text" name="brand_name" value="<?= htmlspecialchars($footer_data['brand_name']) ?>" placeholder="NISWÀ BEAUTY">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-copyright" style="margin-right:4px;"></i>Teks Copyright</label>
                            <input type="text" name="copyright_text" value="<?= htmlspecialchars($footer_data['copyright_text']) ?>" placeholder="NISWÀ BEAUTY. All rights reserved.">
                            <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;">Tahun otomatis ditambahkan di depan teks ini.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-quote-left" style="margin-right:4px;"></i>Deskripsi Brand</label>
                        <textarea name="brand_desc" rows="2" placeholder="Kecantikan bertemu kemewahan..."><?= htmlspecialchars($footer_data['brand_desc']) ?></textarea>
                        <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;">Tagline pendek yang muncul di bawah nama brand di footer.</small>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <!-- Social Media -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-share-nodes" style="margin-right:5px;color:var(--primary);"></i>Link Media Sosial
                    </div>
                    <div class="form-group">
                        <label><i class="fa-brands fa-instagram" style="margin-right:4px;color:#e1306c;"></i>URL Instagram</label>
                        <input type="url" name="instagram_url" value="<?= htmlspecialchars($footer_data['instagram_url']) ?>" placeholder="https://www.instagram.com/niswanail...">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-brands fa-tiktok" style="margin-right:4px;"></i>URL TikTok</label>
                        <input type="url" name="tiktok_url" value="<?= htmlspecialchars($footer_data['tiktok_url']) ?>" placeholder="https://www.tiktok.com/@niswabeauty...">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-brands fa-whatsapp" style="margin-right:4px;color:#25d366;"></i>URL WhatsApp <small style="text-transform:none;color:var(--text-lt);">(format: https://wa.me/628xxx)</small></label>
                        <input type="url" name="whatsapp_url" value="<?= htmlspecialchars($footer_data['whatsapp_url']) ?>" placeholder="https://wa.me/628820069...">
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <!-- Quick Links -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-link" style="margin-right:5px;color:var(--primary);"></i>Quick Links (Navigasi Footer)
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-house" style="margin-right:4px;"></i>Link Home</label>
                            <input type="text" name="link_home" value="<?= htmlspecialchars($footer_data['link_home']) ?>" placeholder="#home">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-scissors" style="margin-right:4px;"></i>Link Services</label>
                            <input type="text" name="link_services" value="<?= htmlspecialchars($footer_data['link_services']) ?>" placeholder="#services">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-box-open" style="margin-right:4px;"></i>Link Product</label>
                            <input type="text" name="link_product" value="<?= htmlspecialchars($footer_data['link_product']) ?>" placeholder="#product">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Link About</label>
                            <input type="text" name="link_about" value="<?= htmlspecialchars($footer_data['link_about']) ?>" placeholder="#about">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-calendar-check" style="margin-right:4px;"></i>Link Booking</label>
                            <input type="text" name="link_booking" value="<?= htmlspecialchars($footer_data['link_booking']) ?>" placeholder="booking.php">
                        </div>
                    </div>

                    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                    <!-- Kontak Footer -->
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                        <i class="fa-solid fa-address-book" style="margin-right:5px;color:var(--primary);"></i>Info Kontak (Contact Us)
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-map-marker-alt" style="margin-right:4px;"></i>Alamat</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($footer_data['address']) ?>" placeholder="Bangsri, Jepara, Jawa Tengah">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-phone-alt" style="margin-right:4px;"></i>Nomor Telepon / WA</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($footer_data['phone']) ?>" placeholder="+62 882-0069-03068">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-envelope" style="margin-right:4px;"></i>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($footer_data['email']) ?>" placeholder="niswabeauty15@gmail.com">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-clock" style="margin-right:4px;"></i>Jam Operasional</label>
                            <input type="text" name="hours" value="<?= htmlspecialchars($footer_data['hours']) ?>" placeholder="Senin – Sabtu: 09:00 – 20:00">
                        </div>
                    </div>

                    <!-- Preview Footer -->
                    <div style="background:var(--cream);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:12px;">
                            <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Footer
                        </div>
                        <!-- footer-top -->
                        <div style="background:#150d10;border-radius:10px 10px 0 0;padding:28px 24px 20px;">
                            <div style="display:flex;flex-wrap:wrap;gap:28px;justify-content:space-around;align-items:flex-start;">

                                <!-- Brand col (center aligned like real footer) -->
                                <div style="text-align:center;min-width:160px;flex:1;">
                                    <div style="color:#D6C1A3;font-family:'Playfair Display',serif;font-weight:700;font-size:16px;letter-spacing:.5px;margin-bottom:8px;"><?= htmlspecialchars($footer_data['brand_name']) ?></div>
                                    <p style="color:#a08090;font-size:11.5px;line-height:1.6;margin-bottom:12px;"><?= htmlspecialchars($footer_data['brand_desc']) ?></p>
                                    <div style="display:flex;gap:8px;justify-content:center;">
                                        <a href="<?= htmlspecialchars($footer_data['instagram_url']) ?>" target="_blank" rel="noopener"
                                           style="background:rgba(214,193,163,.12);color:#D6C1A3;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:.2s;">
                                            <i class="fa-brands fa-instagram"></i>
                                        </a>
                                        <a href="<?= htmlspecialchars($footer_data['tiktok_url']) ?>" target="_blank" rel="noopener"
                                           style="background:rgba(214,193,163,.12);color:#D6C1A3;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:.2s;">
                                            <i class="fa-brands fa-tiktok"></i>
                                        </a>
                                        <a href="<?= htmlspecialchars($footer_data['whatsapp_url']) ?>" target="_blank" rel="noopener"
                                           style="background:rgba(214,193,163,.12);color:#D6C1A3;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:.2s;">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Quick Links col (center aligned, with chevron like real footer) -->
                                <div style="text-align:center;min-width:140px;flex:1;">
                                    <h6 style="color:#D6C1A3;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;font-family:'Poppins',sans-serif;">Quick Links</h6>
                                    <ul style="list-style:none;padding:0;margin:0;display:inline-block;text-align:left;">
                                        <li style="margin-bottom:5px;"><a href="<?= htmlspecialchars($footer_data['link_home']) ?>" style="color:#a08090;font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:9px;margin-right:5px;color:#8B6F5E;"></i>Home</a></li>
                                        <li style="margin-bottom:5px;"><a href="<?= htmlspecialchars($footer_data['link_services']) ?>" style="color:#a08090;font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:9px;margin-right:5px;color:#8B6F5E;"></i>Services</a></li>
                                        <li style="margin-bottom:5px;"><a href="<?= htmlspecialchars($footer_data['link_product']) ?>" style="color:#a08090;font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:9px;margin-right:5px;color:#8B6F5E;"></i>Products</a></li>
                                        <li style="margin-bottom:5px;"><a href="<?= htmlspecialchars($footer_data['link_about']) ?>" style="color:#a08090;font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:9px;margin-right:5px;color:#8B6F5E;"></i>About</a></li>
                                        <li style="margin-bottom:5px;"><a href="<?= htmlspecialchars($footer_data['link_booking']) ?>" style="color:#a08090;font-size:12px;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:9px;margin-right:5px;color:#8B6F5E;"></i>Booking</a></li>
                                    </ul>
                                </div>

                                <!-- Contact Us col (center aligned like real footer) -->
                                <div style="text-align:center;min-width:180px;flex:1;">
                                    <h6 style="color:#D6C1A3;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;font-family:'Poppins',sans-serif;">Contact Us</h6>
                                    <ul style="list-style:none;padding:0;margin:0;text-align:left;display:inline-block;">
                                        <li style="display:flex;gap:8px;align-items:flex-start;margin-bottom:7px;color:#a08090;font-size:11.5px;">
                                            <i class="fas fa-map-marker-alt" style="color:#8B6F5E;margin-top:2px;flex-shrink:0;"></i>
                                            <span><?= htmlspecialchars($footer_data['address']) ?></span>
                                        </li>
                                        <li style="display:flex;gap:8px;align-items:center;margin-bottom:7px;color:#a08090;font-size:11.5px;">
                                            <i class="fas fa-phone-alt" style="color:#8B6F5E;flex-shrink:0;"></i>
                                            <span><?= htmlspecialchars($footer_data['phone']) ?></span>
                                        </li>
                                        <li style="display:flex;gap:8px;align-items:center;margin-bottom:7px;color:#a08090;font-size:11.5px;">
                                            <i class="fas fa-envelope" style="color:#8B6F5E;flex-shrink:0;"></i>
                                            <span><?= htmlspecialchars($footer_data['email']) ?></span>
                                        </li>
                                        <li style="display:flex;gap:8px;align-items:center;margin-bottom:0;color:#a08090;font-size:11.5px;">
                                            <i class="fas fa-clock" style="color:#8B6F5E;flex-shrink:0;"></i>
                                            <span><?= htmlspecialchars($footer_data['hours']) ?></span>
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                        <!-- footer-bottom -->
                        <div style="background:#0e0810;border-radius:0 0 10px 10px;padding:12px 24px;text-align:center;">
                            <p style="color:#5a4050;font-size:11px;margin:0;">
                                &copy; <?= date('Y') ?> <?= htmlspecialchars($footer_data['copyright_text']) ?>

                            </p>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-cms">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Footer
                    </button>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: BOOKING PAGE ════════ */ ?>
<?php elseif ($activeTab === 'booking_page'): ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

            <!-- Form Edit Konten Halaman Booking -->
            <div class="cms-card">
                <div class="cms-card-header">
                    <i class="fa-solid fa-calendar-alt"></i>
                    <h3>Konten Halaman Booking</h3>
                </div>
                <div class="cms-card-body">
                    <form method="POST" action="cms.php?action=save_booking_page">

                        <!-- Judul & Subtitle -->
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                            <i class="fa-solid fa-heading" style="margin-right:5px;color:var(--primary);"></i>Judul Halaman
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-heading" style="margin-right:4px;"></i>Judul Utama Halaman Booking</label>
                            <input type="text" name="page_title" value="<?= htmlspecialchars($booking_page['page_title']) ?>" placeholder="Reservasi Online">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-align-left" style="margin-right:4px;"></i>Subtitle / Deskripsi Singkat</label>
                            <input type="text" name="page_subtitle" value="<?= htmlspecialchars($booking_page['page_subtitle']) ?>" placeholder="Isi form di bawah untuk memesan jadwal...">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-file-alt" style="margin-right:4px;"></i>Judul Form Booking</label>
                            <input type="text" name="form_title" value="<?= htmlspecialchars($booking_page['form_title']) ?>" placeholder="Form Booking">
                        </div>

                        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                        <!-- Daftar Layanan -->
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                            <i class="fa-solid fa-list-check" style="margin-right:5px;color:var(--primary);"></i>Opsi Dropdown Form
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-spa" style="margin-right:4px;"></i>Daftar Layanan (dropdown pilihan layanan)</label>
                            <textarea name="services_list" rows="10" placeholder="Brow Henna&#10;Nail Henna Tangan&#10;Foot Spa&#10;..."><?= htmlspecialchars($booking_page['services_list']) ?></textarea>
                            <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Satu layanan per baris. Akan tampil sebagai opsi di dropdown pilih layanan pada form booking.</small>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-clock" style="margin-right:4px;"></i>Slot Jam Booking (dropdown pilihan jam)</label>
                            <textarea name="time_slots" rows="6" placeholder="09:00&#10;10:00&#10;11:00&#10;..."><?= htmlspecialchars($booking_page['time_slots']) ?></textarea>
                            <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;"><i class="fa-solid fa-circle-info" style="margin-right:3px;"></i>Format 24 jam (HH:MM). Satu slot per baris. Akan tampil di dropdown jam pada form booking.</small>
                        </div>

                        <button type="submit" class="btn-primary-cms">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Halaman Booking
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Panel -->
            <div>
                <!-- Preview Judul -->
                <div class="cms-card" style="margin-bottom:16px;">
                    <div class="cms-card-header">
                        <i class="fa-solid fa-eye"></i>
                        <h3>Preview Halaman Booking</h3>
                    </div>
                    <div class="cms-card-body" style="padding:0;overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#5A4A42,#8B6F5E);padding:28px 24px;text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-size:22px;color:#fff;font-weight:700;margin-bottom:8px;">
                                <?= htmlspecialchars($booking_page['page_title']) ?>
                            </div>
                            <div style="color:rgba(255,255,255,.75);font-size:13px;">
                                <?= htmlspecialchars($booking_page['page_subtitle']) ?>
                            </div>
                        </div>
                        <!-- Form preview -->
                        <div style="padding:18px 20px;">
                            <div style="font-size:13px;font-weight:700;color:var(--primary);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border);">
                                <i class="fa-solid fa-calendar-check" style="margin-right:6px;"></i><?= htmlspecialchars($booking_page['form_title']) ?>
                            </div>
                            <!-- Layanan preview -->
                            <div style="margin-bottom:12px;">
                                <div style="font-size:11px;font-weight:600;color:var(--text-mid);margin-bottom:5px;">Pilih Layanan</div>
                                <select style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:12px;background:#fff;color:var(--text);">
                                    <option value="">Pilih layanan...</option>
                                    <?php foreach (array_filter(array_map('trim', explode("\n", $booking_page['services_list']))) as $svc): ?>
                                    <option><?= htmlspecialchars($svc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Jam preview -->
                            <div style="margin-bottom:12px;">
                                <div style="font-size:11px;font-weight:600;color:var(--text-mid);margin-bottom:5px;">Pilih Jam</div>
                                <select style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:12px;background:#fff;color:var(--text);">
                                    <option value="">Pilih jam...</option>
                                    <?php foreach (array_filter(array_map('trim', explode("\n", $booking_page['time_slots']))) as $ts): ?>
                                    <option><?= htmlspecialchars($ts) ?> WIB</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    </div>
                </div>

                <!-- Info layanan yang tersimpan -->
                <div class="cms-card">
                    <div class="cms-card-header">
                        <i class="fa-solid fa-list-check"></i>
                        <h3>Ringkasan Layanan & Slot Jam</h3>
                    </div>
                    <div class="cms-card-body">
                        <?php
                        $svcList = array_filter(array_map('trim', explode("\n", $booking_page['services_list'])));
                        $tsList  = array_filter(array_map('trim', explode("\n", $booking_page['time_slots'])));
                        ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div>
                                <div style="font-size:11px;font-weight:700;color:var(--text-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                                    <i class="fa-solid fa-spa" style="margin-right:4px;color:var(--primary);"></i>Layanan (<?= count($svcList) ?>)
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <?php foreach ($svcList as $sv): ?>
                                    <span class="cat-badge" style="font-size:10px;"><?= htmlspecialchars($sv) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:11px;font-weight:700;color:var(--text-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                                    <i class="fa-solid fa-clock" style="margin-right:4px;color:var(--primary);"></i>Slot Jam (<?= count($tsList) ?>)
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    <?php foreach ($tsList as $ts): ?>
                                    <span style="background:rgba(139,111,94,.08);color:var(--primary);font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($ts) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php /* ════════ TAB: ORDERS ════════ */ ?>
<?php elseif ($activeTab === 'orders'): ?>

<?php
// Handle delete order
if (isset($_GET['action']) && $_GET['action'] === 'delete_order' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM orders WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=orders&saved=1'); exit;
}
// Load orders
$ordersRows = $conn ? mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC") : null;
$totalOrdersCount = $ordersRows ? mysqli_num_rows($ordersRows) : 0;
?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-receipt"></i>
                <h3>Pesanan Masuk</h3>
                <div class="ms-auto" style="display:flex;gap:8px;align-items:center;">
                    <span style="font-size:12px;color:var(--text-lt);">Total: <strong style="color:var(--primary);"><?= $totalOrdersCount ?> pesanan</strong></span>
                </div>
            </div>
            <!-- Search Orders -->
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--cream);display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <div style="position:relative;flex:1;min-width:200px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-lt);font-size:13px;"></i>
                    <input type="text" id="searchOrders" placeholder="Cari nama, WA, produk..." oninput="filterTable('searchOrders','ordersTable')"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;background:#fff;outline:none;box-sizing:border-box;">
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($ordersRows && mysqli_num_rows($ordersRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Waktu</th>
                            <th>Nama</th>
                            <th>WhatsApp</th>
                            <th>Produk / Layanan</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Alamat</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($ordersRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="font-size:11px;color:var(--text-lt);white-space:nowrap;">
                            <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                            <span style="color:#aaa;"><?= date('H:i', strtotime($row['created_at'])) ?></span>
                        </td>
                        <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$row['whatsapp']) ?>" target="_blank"
                               style="color:#25d366;font-weight:600;font-size:12px;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($row['whatsapp']) ?>
                            </a>
                        </td>
                        <td style="max-width:180px;font-size:12px;">
                            <?= htmlspecialchars(substr($row['product_name'],0,60)) ?><?= strlen($row['product_name'])>60?'…':'' ?>
                            <div style="font-size:11px;color:var(--text-lt);margin-top:2px;"><?= htmlspecialchars($row['product_price']) ?></div>
                        </td>
                        <td style="text-align:center;font-weight:700;color:var(--primary);"><?= (int)($row['qty']??1) ?></td>
                        <td style="color:#ee4d2d;font-weight:800;font-size:13px;white-space:nowrap;">
                            <?= htmlspecialchars($row['total'] ?: $row['product_price']) ?>
                        </td>
                        <td style="max-width:140px;font-size:12px;color:var(--text-mid);">
                            <?= htmlspecialchars(substr($row['alamat'],0,60)) ?><?= strlen($row['alamat'])>60?'…':'' ?>
                        </td>
                        <td style="max-width:120px;font-size:12px;color:var(--text-lt);">
                            <?= !empty($row['catatan']) ? htmlspecialchars(substr($row['catatan'],0,40)) : '—' ?>
                        </td>
                        <td>
                            <div class="actions-cell" style="flex-wrap:nowrap;">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$row['whatsapp']) ?>?text=<?= urlencode('Halo '.$row['nama'].', pesanan Anda ('.$row['product_name'].' x'.($row['qty']??1).') sudah kami terima. Total: '.($row['total']?:$row['product_price']).'. Terima kasih sudah memesan di NISWÀ BEAUTY! 💕') ?>"
                                   target="_blank" class="btn-edit-cms" title="Konfirmasi via WA">
                                    <i class="fa-brands fa-whatsapp" style="color:#25d366;"></i>
                                </a>
                                <a href="cms.php?tab=orders&action=delete_order&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus pesanan ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-receipt"></i>
                    <p>Belum ada pesanan masuk.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

<?php endif; ?>
</div><!-- /.main-wrap -->

<script>
/* ━━ Mobile Drawer ━━ */
function openDrawer()  { document.getElementById('mobileDrawer').classList.add('open'); document.getElementById('drawerOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeDrawer() { document.getElementById('mobileDrawer').classList.remove('open'); document.getElementById('drawerOverlay').classList.remove('open'); document.body.style.overflow=''; }

/* ━━ Modal ━━ */
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.cms-modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) {
        if (e.target === o) { o.classList.remove('open'); document.body.style.overflow=''; }
    });
});

/* ━━ Image Preview ━━ */
function previewImg(input, previewId) {
    var prev = document.getElementById(previewId);
    if (!prev) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { prev.src = e.target.result; prev.style.display='block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ━━ Hero Live Preview ━━ */
function updateHeroPreview() {
    var t  = document.getElementById('heroTitle');
    var s  = document.getElementById('heroSubtitle');
    var bp = document.getElementById('heroBtnP');
    var bs = document.getElementById('heroBtnS');
    if (t)  document.getElementById('previewTitle').textContent  = t.value;
    if (s)  document.getElementById('previewSub').textContent    = s.value;
    if (bp) document.getElementById('previewBtnP').textContent   = bp.value;
    if (bs) document.getElementById('previewBtnS').textContent   = bs.value + ' →';
}

/* ━━ Edit Handlers ━━ */
function openEditService(row) {
    document.getElementById('serviceModalTitle').textContent = 'Edit Layanan';
    document.getElementById('serviceId').value    = row.id;
    document.getElementById('serviceName').value  = row.name;
    document.getElementById('serviceGallery').value = row.gallery || '';
    var prev = document.getElementById('svcPreview');
    if (row.image) { prev.src = row.image; prev.style.display='block'; }
    openModal('modalService');
}

function openEditPrice(row) {
    document.getElementById('priceModalTitle').textContent = 'Edit Item Harga';
    document.getElementById('priceId').value   = row.id;
    document.getElementById('priceCat').value  = row.category;
    document.getElementById('priceName').value = row.name;
    document.getElementById('priceVal').value  = row.price;
    document.getElementById('priceDesc').value = row.description || '';
    var prev = document.getElementById('pricePreview');
    if (prev) {
        if (row.image) { prev.src = row.image; prev.style.display = 'block'; }
        else { prev.src = ''; prev.style.display = 'none'; }
    }
    openModal('modalPrice');
}

function openEditProduct(row) {
    document.getElementById('prodModalTitle').textContent = 'Edit Produk';
    document.getElementById('prodId').value           = row.id;
    document.getElementById('prodName').value         = row.name;
    document.getElementById('prodPrice').value        = row.price;
    document.getElementById('prodCategory').value     = row.category;
    document.getElementById('prodDiscountPct').value  = row.discount_pct   || 0;
    document.getElementById('prodMinPurchase').value  = row.min_purchase   || 0;
    updateDiscountPreview();
    var prev = document.getElementById('prodPreview');
    if (row.image) { prev.src = row.image; prev.style.display='block'; }
    openModal('modalProduct');
}

function openEditTesti(row) {
    document.getElementById('testiModalTitle').textContent = 'Edit Testimoni';
    document.getElementById('testiId').value      = row.id;
    document.getElementById('testiName').value    = row.name;
    document.getElementById('testiService').value = row.service_tag;
    document.getElementById('testiText').value    = row.text;
    document.getElementById('testiColor').value   = row.avatar_color;
    document.querySelectorAll('.swatch').forEach(function(s) {
        s.classList.toggle('selected', s.dataset.color === row.avatar_color);
    });
    openModal('modalTesti');
}

/* ━━ Open-for-ADD helpers (reset form before opening) ━━ */
function openAddService() {
    document.getElementById('serviceModalTitle').textContent = 'Tambah Layanan';
    document.getElementById('serviceId').value   = '0';
    document.getElementById('serviceName').value = '';
    document.getElementById('serviceGallery').value = '';
    var prev = document.getElementById('svcPreview');
    if (prev) { prev.src=''; prev.style.display='none'; }
    openModal('modalService');
}
function openAddPrice() {
    document.getElementById('priceModalTitle').textContent = 'Tambah Item Harga';
    document.getElementById('priceId').value   = '0';
    document.getElementById('priceCat').value  = '';
    document.getElementById('priceName').value = '';
    document.getElementById('priceVal').value  = '';
    document.getElementById('priceDesc').value = '';
    var prev = document.getElementById('pricePreview');
    if (prev) { prev.src = ''; prev.style.display = 'none'; }
    openModal('modalPrice');
}
function updateDiscountPreview() {
    var pct = parseInt(document.getElementById('prodDiscountPct').value) || 0;
    var min = parseInt(document.getElementById('prodMinPurchase').value) || 0;
    var box = document.getElementById('discountPreview');
    if (pct > 0) {
        var txt = '<i class="fa-solid fa-tag" style="margin-right:4px;"></i>Diskon <strong>' + pct + '%</strong>';
        if (min > 0) {
            txt += ' · Min. beli <strong>Rp ' + min.toLocaleString('id-ID') + '</strong>';
        } else {
            txt += ' · Tanpa minimal pembelian';
        }
        box.innerHTML = txt;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

function openAddProduct() {
    document.getElementById('prodModalTitle').textContent = 'Tambah Produk';
    document.getElementById('prodId').value           = '0';
    document.getElementById('prodName').value         = '';
    document.getElementById('prodPrice').value        = '';
    document.getElementById('prodCategory').value     = 'simple';
    document.getElementById('prodDiscountPct').value  = '0';
    document.getElementById('prodMinPurchase').value  = '0';
    updateDiscountPreview();
    var prev = document.getElementById('prodPreview');
    if (prev) { prev.src=''; prev.style.display='none'; }
    openModal('modalProduct');
}
function openAddTesti() {
    document.getElementById('testiModalTitle').textContent = 'Tambah Testimoni';
    document.getElementById('testiId').value      = '0';
    document.getElementById('testiName').value    = '';
    document.getElementById('testiService').value = '';
    document.getElementById('testiText').value    = '';
    var first = document.querySelector('.swatch');
    if (first) {
        document.querySelectorAll('.swatch').forEach(function(s){s.classList.remove('selected');});
        first.classList.add('selected');
        document.getElementById('testiColor').value = first.dataset.color;
    }
    openModal('modalTesti');
}
function selectSwatch(el) {
    document.querySelectorAll('.swatch').forEach(function(s) { s.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('testiColor').value = el.dataset.color;
}
(function() {
    var first = document.querySelector('.swatch');
    if (first) first.classList.add('selected');
})();

/* ━━ Search / Filter Tables ━━ */
function filterTable(inputId, tableId) {
    var query = document.getElementById(inputId).value.toLowerCase().trim();
    var rows   = document.querySelectorAll('#' + tableId + ' tbody tr');
    var found  = 0;
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var show = query === '' || text.indexOf(query) !== -1;
        row.style.display = show ? '' : 'none';
        if (show) found++;
    });
    // Show/hide "no result" row
    var noRow = document.getElementById(tableId + '_noResult');
    if (!noRow) {
        noRow = document.createElement('tr');
        noRow.id = tableId + '_noResult';
        noRow.innerHTML = '<td colspan="10" style="text-align:center;padding:20px;color:var(--text-lt);font-size:13px;"><i class="fa-solid fa-search" style="margin-right:6px;"></i>Tidak ada hasil yang cocok</td>';
        var tbody = document.querySelector('#' + tableId + ' tbody');
        if (tbody) tbody.appendChild(noRow);
    }
    noRow.style.display = (found === 0 && query !== '') ? '' : 'none';
}

function filterProducts() {
    var query = (document.getElementById('searchProducts').value || '').toLowerCase().trim();
    var cat   = (document.getElementById('filterProductCat').value || '').toLowerCase();
    var rows  = document.querySelectorAll('#productsTable tbody tr');
    var found = 0;
    rows.forEach(function(row) {
        var text    = row.textContent.toLowerCase();
        var rowCat  = (row.getAttribute('data-cat') || '').toLowerCase();
        var matchQ  = query === '' || text.indexOf(query) !== -1;
        var matchC  = cat === '' || rowCat === cat;
        var show    = matchQ && matchC;
        row.style.display = show ? '' : 'none';
        if (show) found++;
    });
    var noRow = document.getElementById('productsTable_noResult');
    if (!noRow) {
        noRow = document.createElement('tr');
        noRow.id = 'productsTable_noResult';
        noRow.innerHTML = '<td colspan="10" style="text-align:center;padding:20px;color:var(--text-lt);font-size:13px;"><i class="fa-solid fa-search" style="margin-right:6px;"></i>Tidak ada produk yang cocok</td>';
        var tbody = document.querySelector('#productsTable tbody');
        if (tbody) tbody.appendChild(noRow);
    }
    noRow.style.display = (found === 0 && (query !== '' || cat !== '')) ? '' : 'none';
}
</script>

</body>
</html>