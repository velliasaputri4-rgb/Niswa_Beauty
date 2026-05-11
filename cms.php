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
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cms_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150),
        price VARCHAR(60),
        category VARCHAR(60),
        image VARCHAR(255),
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
    if ($cntPrc === 0) {
        $defaultPrcSeed = [
            ['Henna Series',       'Brow Henna',                      'Rp 25.000'],
            ['Henna Series',       'Nail Henna Tangan',                'Rp 25.000'],
            ['Henna Series',       'Nail Henna Kaki',                  'Rp 30.000'],
            ['Henna Series',       'Bundling Meni-Henna',              'Rp 75.000'],
            ['Henna Series',       'Henna Fun',                        'Rp 25.000 - 100.000'],
            ['Treatment Spa',      'Bundling Manicure & Pedicure',     'Rp 100.000'],
            ['Treatment Spa',      'Manicure / Pedicure',              'Rp 60.000'],
            ['Treatment Spa',      'Hand Spa',                         'Rp 80.000'],
            ['Treatment Spa',      'Foot Spa',                         'Rp 100.000'],
            ['Treatment Spa',      'Callus Treatment',                 'Rp 70.000 - 150.000'],
            ['Brow & Lash',        'Brow Bomb',                        'Rp 100.000'],
            ['Brow & Lash',        'Lashlift',                         'Rp 70.000'],
            ['Brow & Lash',        'Lashlift Tint',                    'Rp 90.000'],
            ['Rambut',             'Creambath',                        'Rp 75.000'],
            ['Rambut',             'Hair Mask',                        'Rp 45.000 - 90.000'],
            ['Rambut',             'Hair Spa',                         'Rp 100.000'],
            ['Rambut',             'Cuci,Catok,Blow',                  'Rp 25.000 - 50.000'],
            ['Rambut',             'Bleaching S',                      'Rp 40.000'],
            ['Rambut',             'Coloring Full',                    'Rp 120.000 - 300.000'],
            ['Rambut',             'Bleaching',                        'Rp 200.000 - 1.200.000'],
            ['Rambut',             'Balayage',                         'Rp 250.000 - 700.000'],
            ['Rambut',             'Down Peim Poni',                   'Rp 100.000 - 300.000'],
            ['Rambut',             'Keriting Klasik',                  'Rp 300.000 - 700.000'],
            ['Rambut',             'Keriting Digital',                 'Rp 450.000 - 1.700.000'],
            ['Rambut',             'Keratin Treatment',                'Rp 200.000'],
            ['Rambut',             'Smoothing',                        'Rp 200.000 - 400.000'],
            ['Nail Art & Services','Press On Nail Basic',              'Rp 50.000'],
            ['Nail Art & Services','Press On Nail Motif',              'Rp 75.000'],
            ['Nail Art & Services','Kids Basic Gel',                   'Rp 40.000'],
            ['Nail Art & Services','Kids Gel + 4 Sticker',             'Rp 50.000'],
            ['Nail Art & Services','Kids Gel + Full Sticker',          'Rp 55.000'],
            ['Nail Art & Services','Gel Basic Tangan / Kaki',          'Rp 85.000'],
            ['Nail Art & Services','Extension',                        'Rp 50.000'],
            ['Nail Art & Services','Gel French / Cat Eyes',            'Rp 105.000'],
            ['Nail Art & Services','Remove Gel',                       'Rp 50.000'],
            ['Nail Art & Services','Gel Ombre / Blush On',             'Rp 135.000'],
            ['Nail Art & Services','Remove Extension',                 'Rp 65.000'],
            ['Nail Art & Services','Bundling Nail Art + Extension',    'Rp 150.000'],
        ];
        $so = 1;
        foreach ($defaultPrcSeed as [$cat,$nm,$pr]) {
            $cat=mysqli_real_escape_string($conn,$cat); $nm=mysqli_real_escape_string($conn,$nm); $pr=mysqli_real_escape_string($conn,$pr);
            mysqli_query($conn,"INSERT INTO cms_prices (category,name,price,sort_order) VALUES ('$cat','$nm','$pr',$so)");
            $so++;
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

/* ── 2. Kontak & Maps ── */
if ($action === 'save_kontak') {
    foreach (['salon_name','address','hours','whatsapp','maps_embed','maps_link'] as $k)
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
    if ($id) {
        mysqli_query($conn, "UPDATE cms_prices SET category='$cat', name='$name', price='$price' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_prices (category,name,price) VALUES ('$cat','$name','$price')");
    }
    header('Location: cms.php?tab=prices&saved=1'); exit;
}
if ($action === 'delete_price' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM cms_prices WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=prices'); exit;
}

/* ── 5. Products CRUD ── */
if ($action === 'save_product') {
    $id    = (int)($_POST['prod_id']       ?? 0);
    $name  = mysqli_real_escape_string($conn, $_POST['prod_name']     ?? '');
    $price = mysqli_real_escape_string($conn, $_POST['prod_price']    ?? '');
    $cat   = mysqli_real_escape_string($conn, $_POST['prod_category'] ?? '');
    $img   = handleUpload('prod_image') ?? '';
    if ($id) {
        $imgSql = $img ? ", image='$img'" : '';
        mysqli_query($conn, "UPDATE cms_products SET name='$name', price='$price', category='$cat'$imgSql WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_products (name,price,category,image) VALUES ('$name','$price','$cat','$img')");
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
               'store_image','tech_text'];
    foreach ($fields as $f) {
        if ($f === 'store_image') {
            if ($up = handleUpload('store_image_file')) setProfil($conn, 'profil', $f, $up);
        } else {
            setProfil($conn, 'profil', $f, $_POST[$f] ?? '');
        }
    }
    header('Location: cms.php?tab=profil&saved=1'); exit;
}

/* ── 8. Booking CRUD ── */
if ($action === 'save_booking') {
    $id           = (int)($_POST['booking_id'] ?? 0);
    $name         = mysqli_real_escape_string($conn, $_POST['bk_name']    ?? '');
    $phone        = mysqli_real_escape_string($conn, $_POST['bk_phone']   ?? '');
    $email        = mysqli_real_escape_string($conn, $_POST['bk_email']   ?? '');
    $service      = mysqli_real_escape_string($conn, $_POST['bk_service'] ?? '');
    $date         = mysqli_real_escape_string($conn, $_POST['bk_date']    ?? '');
    $time         = mysqli_real_escape_string($conn, $_POST['bk_time']    ?? '');
    $jumlah       = (int)($_POST['bk_jumlah'] ?? 1);
    $catatan      = mysqli_real_escape_string($conn, $_POST['bk_catatan'] ?? '');
    if ($id) {
        mysqli_query($conn, "UPDATE bookings SET name='$name', phone='$phone', email='$email', service='$service', date='$date', time='$time', jumlah_orang=$jumlah, catatan='$catatan' WHERE id=$id");
    }
    header('Location: cms.php?tab=bookings&saved=1'); exit;
}
if ($action === 'delete_booking' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM bookings WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=bookings'); exit;
}

/* ── 9. Orders CRUD ── */
if ($action === 'save_order') {
    $id      = (int)($_POST['order_id']    ?? 0);
    $nama    = mysqli_real_escape_string($conn, $_POST['or_nama']    ?? '');
    $wa      = mysqli_real_escape_string($conn, $_POST['or_wa']      ?? '');
    $product = mysqli_real_escape_string($conn, $_POST['or_product'] ?? '');
    $price   = mysqli_real_escape_string($conn, $_POST['or_price']   ?? '');
    $qty     = (int)($_POST['or_qty']     ?? 1);
    $total   = mysqli_real_escape_string($conn, $_POST['or_total']   ?? '');
    $alamat  = mysqli_real_escape_string($conn, $_POST['or_alamat']  ?? '');
    $catatan = mysqli_real_escape_string($conn, $_POST['or_catatan'] ?? '');
    if ($id) {
        mysqli_query($conn, "UPDATE orders SET nama='$nama', whatsapp='$wa', product_name='$product', product_price='$price', qty=$qty, total='$total', alamat='$alamat', catatan='$catatan' WHERE id=$id");
    }
    header('Location: cms.php?tab=orders&saved=1'); exit;
}
if ($action === 'delete_order' && isset($_GET['id'])) {
    mysqli_query($conn, "DELETE FROM orders WHERE id=".(int)$_GET['id']);
    header('Location: cms.php?tab=orders'); exit;
}

/* ── Logout ── */
if (isset($_GET['logout'])) {
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
               'copyright_text'];
    foreach ($fields as $f) setFooter($conn, $f, $_POST[$f] ?? '');
    header('Location: cms.php?tab=footer&saved=1'); exit;
}

/* ── 12. Booking Page Content ── */
if ($action === 'save_booking_page') {
    $fields = ['page_title','page_subtitle','form_title','success_message',
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
    'tech_text'     => getProfil($conn,'profil','tech_text',    'Niswà Beauty juga terus mengikuti perkembangan zaman. Berawal dari promosi sederhana melalui Story WhatsApp, kini hadir lebih luas lewat Instagram dan TikTok — termasuk penggunaan sistem pembayaran digital QRIS sejak awal tahun 2025.'),
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
];

// Booking Page
$booking_page = [
    'page_title'      => getBookingPage($conn, 'page_title',      'Reservasi Online'),
    'page_subtitle'   => getBookingPage($conn, 'page_subtitle',   'Isi form di bawah untuk memesan jadwal kecantikan Anda'),
    'form_title'      => getBookingPage($conn, 'form_title',      'Form Booking'),
    'success_message' => getBookingPage($conn, 'success_message', 'Booking berhasil! Kami akan menghubungi Anda via WhatsApp.'),
    'services_list'   => getBookingPage($conn, 'services_list',   "Nail Art\nHaircut\nColoring\nFoot SPA\nHenna Series\nPress on Nail\nEye Lash\nHair Treatment"),
    'time_slots'      => getBookingPage($conn, 'time_slots',      "09:00\n10:00\n11:00\n12:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00"),
];

// Static default price list (displayed when DB is empty, mirroring website)
$defaultPriceList = [
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
        ['name'=>'Smoothing','price'=>'Rp 200.000 - 400.000'],
        ['name'=>'Keratin Treatment','price'=>'Rp 200.000'],
        ['name'=>'Coloring Full','price'=>'Rp 120.000 - 300.000'],
    ],
    'Nail Art & Services' => [
        ['name'=>'Gel Basic Tangan / Kaki','price'=>'Rp 85.000'],
        ['name'=>'Press On Nail Basic','price'=>'Rp 50.000'],
        ['name'=>'Press On Nail Motif','price'=>'Rp 75.000'],
        ['name'=>'Gel French / Cat Eyes','price'=>'Rp 105.000'],
        ['name'=>'Remove Gel','price'=>'Rp 50.000'],
    ],
];

// DB rows
$servicesRows  = $conn ? mysqli_query($conn, "SELECT * FROM cms_services ORDER BY sort_order, id") : null;
$pricesRows    = $conn ? mysqli_query($conn, "SELECT * FROM cms_prices ORDER BY category, sort_order, id") : null;
$productsRows  = $conn ? mysqli_query($conn, "SELECT * FROM cms_products ORDER BY sort_order, id") : null;
$testiRows     = $conn ? mysqli_query($conn, "SELECT * FROM cms_testimonials ORDER BY sort_order, id") : null;
$bookingsRows  = $conn ? mysqli_query($conn, "SELECT * FROM bookings ORDER BY created_at DESC LIMIT 50") : null;
$ordersRows    = $conn ? mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 50") : null;

// Stats
$totalBookings = $conn && mysqli_query($conn, "SELECT COUNT(*) c FROM bookings") ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings"))['c'] ?? 0) : 0;
$totalOrders   = $conn && mysqli_query($conn, "SELECT COUNT(*) c FROM orders")   ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c']   ?? 0) : 0;
$totalProducts = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_products"))['c'] ?? 0) : 0;
$totalServices = $conn ? (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM cms_services"))['c'] ?? 0) : 0;
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
    width:var(--sidebar-w);background:var(--dark);
    min-height:100vh;position:fixed;left:0;top:0;
    display:flex;flex-direction:column;z-index:200;
    transition:transform .3s;border-right:1px solid rgba(255,255,255,.04);
}
.sidebar-brand{padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,.06);}
.sidebar-brand .logo{font-family:'Playfair Display',serif;font-size:18px;color:#fff;font-weight:700;display:flex;align-items:center;gap:10px;}
.sidebar-brand .logo i{color:var(--gold);}
.sidebar-brand .tagline{font-size:9px;color:#3a2535;letter-spacing:2.5px;text-transform:uppercase;margin-top:4px;padding-left:28px;font-weight:600;}
.sidebar-lbl{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:#3a2535;padding:16px 20px 5px;font-weight:700;}
.nav-list{list-style:none;padding:0 8px;flex:1;}
.nav-list li{margin-bottom:1px;}
.nav-list a{
    display:flex;align-items:center;gap:11px;padding:10px 14px;
    color:#7a6870;text-decoration:none;border-radius:10px;
    font-size:13px;font-weight:500;transition:all .2s;
}
.nav-list a:hover{background:rgba(214,193,163,.1);color:var(--gold);}
.nav-list a.active{background:rgba(214,193,163,.15);color:var(--gold);}
.nav-list a i{width:17px;text-align:center;font-size:14px;flex-shrink:0;}
.sidebar-footer{
    padding:14px 16px;border-top:1px solid rgba(255,255,255,.05);
    display:flex;align-items:center;gap:10px;
}
.sidebar-footer .av{
    width:36px;height:36px;border-radius:50%;
    background:linear-gradient(135deg,var(--gold),#5A4A42);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:700;font-size:14px;flex-shrink:0;
}
.sidebar-footer .info .name{color:var(--gold);font-size:13px;font-weight:600;}
.sidebar-footer .info .role{color:#4a3040;font-size:10px;}
.sidebar-footer .logout-btn{
    margin-left:auto;background:rgba(239,68,68,.12);border:none;
    color:#ef4444;width:30px;height:30px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:13px;transition:.2s;text-decoration:none;
}
.sidebar-footer .logout-btn:hover{background:rgba(239,68,68,.25);}

/* ━━ MAIN ━━ */
.main-wrap{margin-left:var(--sidebar-w);min-height:100vh;}
.topbar{
    background:#fff;padding:14px 28px;
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
    position:sticky;top:0;z-index:100;
}
.topbar-title{font-size:17px;font-weight:700;color:var(--text);}
.topbar-title span{color:var(--primary);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.mobile-menu-btn{display:none;background:none;border:none;cursor:pointer;color:var(--text);font-size:20px;padding:4px;}
.content{padding:24px 28px;}

/* ━━ TOAST ━━ */
.toast-bar{
    position:fixed;top:18px;right:22px;z-index:9999;
    background:var(--success);color:#fff;
    padding:11px 20px;border-radius:12px;
    display:flex;align-items:center;gap:9px;
    font-size:13.5px;font-weight:600;
    box-shadow:0 8px 28px rgba(16,185,129,.35);
    animation:toastIn .4s ease;
}
@keyframes toastIn{from{opacity:0;transform:translateY(-16px);}to{opacity:1;transform:translateY(0);}}

/* ━━ STAT CARDS ━━ */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{
    background:#fff;border-radius:16px;padding:20px;
    border:1px solid var(--border);
    box-shadow:0 2px 10px rgba(139,111,94,.06);
    display:flex;align-items:center;gap:16px;
}
.stat-icon{
    width:50px;height:50px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;flex-shrink:0;
}
.stat-icon.gold{background:rgba(214,193,163,.2);color:var(--primary);}
.stat-icon.green{background:rgba(16,185,129,.12);color:#10b981;}
.stat-icon.rose{background:rgba(244,114,182,.12);color:#f472b6;}
.stat-icon.blue{background:rgba(59,130,246,.12);color:#3b82f6;}
.stat-num{font-size:26px;font-weight:700;color:var(--text);font-family:'Playfair Display',serif;line-height:1;}
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

/* ━━ BOOKING/ORDER TABLE ━━ */
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.status-new{background:rgba(59,130,246,.1);color:#3b82f6;}
.status-done{background:rgba(16,185,129,.1);color:#10b981;}

/* ━━ MOBILE ━━ */
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);}
    .sidebar.open{transform:translateX(0);}
    .main-wrap{margin-left:0;}
    .mobile-menu-btn{display:block;}
    .content{padding:12px;}
    .topbar{padding:11px 14px;}
    .topbar-title{font-size:14px;}
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
    /* Hero tab: single column on mobile */
    div[style*="grid-template-columns:1fr 380px"]{display:block!important;}
    div[style*="grid-template-columns:1fr 380px"] > div:last-child{margin-top:16px;}
    /* Prices tab: single column on mobile */
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
    .topbar-right span{display:none;}
}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:190;}
.sidebar-overlay.open{display:block;}

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

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo"><i class="fa-solid fa-spa"></i> NISWÀ BEAUTY</div>
        <div class="tagline">CMS Admin Panel</div>
    </div>

    <div class="sidebar-lbl">Kelola Konten</div>
    <ul class="nav-list">
        <li><a href="cms.php?tab=hero"      class="<?= $activeTab==='hero'      ? 'active':'' ?>"><i class="fa-solid fa-image"></i> Hero & Slider</a></li>
        <li><a href="cms.php?tab=services"  class="<?= $activeTab==='services'  ? 'active':'' ?>"><i class="fa-solid fa-scissors"></i> Layanan</a></li>
        <li><a href="cms.php?tab=prices"    class="<?= $activeTab==='prices'    ? 'active':'' ?>"><i class="fa-solid fa-tag"></i> Daftar Harga</a></li>
        <li><a href="cms.php?tab=products"  class="<?= $activeTab==='products'  ? 'active':'' ?>"><i class="fa-solid fa-box-open"></i> Produk</a></li>
        <li><a href="cms.php?tab=testimoni" class="<?= $activeTab==='testimoni' ? 'active':'' ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a></li>
        <li><a href="cms.php?tab=profil"    class="<?= $activeTab==='profil'    ? 'active':'' ?>"><i class="fa-solid fa-store"></i> Profil Toko</a></li>
        <li><a href="cms.php?tab=kontak"    class="<?= $activeTab==='kontak'    ? 'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Kontak & Maps</a></li>
        <li><a href="cms.php?tab=navbar"    class="<?= $activeTab==='navbar'    ? 'active':'' ?>"><i class="fa-solid fa-bars"></i> Navbar</a></li>
        <li><a href="cms.php?tab=footer"    class="<?= $activeTab==='footer'    ? 'active':'' ?>"><i class="fa-solid fa-grip-lines"></i> Footer</a></li>
        <li><a href="cms.php?tab=booking_page" class="<?= $activeTab==='booking_page' ? 'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Halaman Booking</a></li>
    </ul>

    <div class="sidebar-lbl">Manajemen</div>
    <ul class="nav-list">
        <li><a href="cms.php?tab=bookings"  class="<?= $activeTab==='bookings'  ? 'active':'' ?>"><i class="fa-solid fa-calendar-check"></i> Data Booking</a></li>
        <li><a href="cms.php?tab=orders"    class="<?= $activeTab==='orders'    ? 'active':'' ?>"><i class="fa-solid fa-bag-shopping"></i> Data Order Produk</a></li>
        <li><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard Lama</a></li>
        <li><a href="index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website</a></li>
    </ul>

    <div class="sidebar-footer">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div class="info">
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Administrator</div>
        </div>
        <a href="cms.php?logout=1" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main-wrap">
    <!-- Topbar -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <button class="mobile-menu-btn" onclick="openSidebar()"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Panel <span>CMS</span> — NISWÀ BEAUTY</div>
        </div>
        <div class="topbar-right">
            <span style="font-size:12px;color:var(--text-mid);"><i class="fa-regular fa-circle-user" style="margin-right:4px;"></i><?= htmlspecialchars($_SESSION['user']) ?></span>
            <a href="index.php" target="_blank" style="font-size:12px;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:5px;background:rgba(139,111,94,.08);padding:6px 12px;border-radius:8px;">
                <i class="fa-solid fa-eye"></i> Website
            </a>
            <a href="cms.php?logout=1" style="color:var(--danger);font-size:13px;text-decoration:none;" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
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
                <div class="stat-icon gold"><i class="fa-solid fa-calendar-check"></i></div>
                <div><div class="stat-num"><?= $totalBookings ?></div><div class="stat-lbl">Total Booking</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-bag-shopping"></i></div>
                <div><div class="stat-num"><?= $totalOrders ?></div><div class="stat-lbl">Total Order Produk</div></div>
            </div>
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
            <a href="cms.php?tab=bookings"     class="<?= $activeTab==='bookings'     ? 'active':'' ?>"><i class="fa-solid fa-calendar-check"></i> Data Booking</a>
            <a href="cms.php?tab=orders"       class="<?= $activeTab==='orders'       ? 'active':'' ?>"><i class="fa-solid fa-bag-shopping"></i> Orders</a>
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
                                <img src="<?= htmlspecialchars($imgVal) ?>" class="prev-thumb" id="prev_hero<?= $n ?>" style="display:block;">
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
                <div class="ms-auto" style="display:flex;gap:8px;">
                    <div style="font-size:11.5px;color:var(--text-lt);display:flex;align-items:center;gap:5px;">
                        <i class="fa-solid fa-circle-info"></i> Layanan tampil di section "Layanan Kami"
                    </div>
                    <button class="btn-sm-add" onclick="openAddService()">
                        <i class="fa-solid fa-plus"></i> Tambah Layanan
                    </button>
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($servicesRows && mysqli_num_rows($servicesRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table">
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
                    <div class="cms-card-body" style="padding:0;">
                        <?php if ($pricesRows && mysqli_num_rows($pricesRows) > 0):
                              mysqli_data_seek($pricesRows, 0); ?>
                        <div style="overflow-x:auto;">
                        <table class="cms-table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Nama</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = mysqli_fetch_assoc($pricesRows)): ?>
                            <tr>
                                <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['price']) ?></td>
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
                            <td><?= htmlspecialchars($item['name']) ?></td>
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
                <form method="POST" action="cms.php?action=save_price">
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
            <div class="cms-card-body" style="padding:0;">
                <?php if ($productsRows && mysqli_num_rows($productsRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Foto</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($productsRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?php if($row['image']): ?><img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview"><?php else: ?><span style="color:var(--text-lt);">—</span><?php endif; ?></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                        <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['price']) ?></td>
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
                        <i class="fa-solid fa-history" style="margin-right:5px;color:var(--primary);"></i>Teks Sejarah Teknologi
                    </div>
                    <div class="form-group">
                        <label>Teks Narasi Teknologi</label>
                        <textarea name="tech_text" rows="4" placeholder="Cerita perkembangan teknologi/platform salon..."><?= htmlspecialchars($profil['tech_text']) ?></textarea>
                    </div>

                    <!-- Value items display info -->
                    <div style="background:var(--cream);border-radius:12px;padding:16px;margin-bottom:18px;border:1px solid var(--border);">
                        <div style="font-size:12px;font-weight:600;color:var(--text-mid);margin-bottom:10px;"><i class="fa-solid fa-heart" style="margin-right:5px;color:var(--primary);"></i>Value Items (tampil otomatis di website)</div>
                        <div class="profil-value-items">
                            <div class="profil-value-item"><i class="fa-solid fa-heart"></i><span>Pelayanan Tulus</span></div>
                            <div class="profil-value-item"><i class="fa-solid fa-shield-alt"></i><span>Produk Aman & Halal</span></div>
                            <div class="profil-value-item"><i class="fa-solid fa-star"></i><span>Kualitas Premium</span></div>
                            <div class="profil-value-item"><i class="fa-solid fa-smile"></i><span>Kepuasan Pelanggan</span></div>
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
                                <input type="tel" name="whatsapp" value="<?= htmlspecialchars($kontak['whatsapp']) ?>" placeholder="62812345678">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-map-pin" style="margin-right:4px;"></i>Alamat Lengkap</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($kontak['address']) ?>" placeholder="Jl. ...">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-regular fa-clock" style="margin-right:4px;"></i>Jam Operasional</label>
                            <input type="text" name="hours" value="<?= htmlspecialchars($kontak['hours']) ?>" placeholder="Senin – Minggu, 08.00 – 20.00">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-link" style="margin-right:4px;"></i>Link Google Maps (tombol Petunjuk Arah)</label>
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

            <!-- Maps Preview + Info Bar -->
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-lt);margin-bottom:10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Preview Lokasi (tampilan website)
                </div>
                <!-- Info bar preview -->
                <div style="background:linear-gradient(135deg,#5A4A42,#8B6F5E);border-radius:12px 12px 0 0;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <div>
                        <div style="color:#fff;font-weight:700;font-size:14px;"><i class="fa-solid fa-map-marker-alt" style="margin-right:6px;"></i><?= htmlspecialchars($kontak['salon_name']) ?></div>
                        <div style="color:rgba(255,255,255,.75);font-size:12px;margin-top:3px;"><?= htmlspecialchars($kontak['address']) ?></div>
                    </div>
                    <div style="color:rgba(255,255,255,.8);font-size:12px;">
                        <i class="fa-solid fa-clock" style="color:var(--gold);margin-right:4px;"></i><?= htmlspecialchars($kontak['hours']) ?>
                    </div>
                </div>
                <?php if ($kontak['maps_embed']): ?>
                <div style="border-radius:0 0 12px 12px;overflow:hidden;border:1.5px solid var(--border);">
                    <iframe src="<?= htmlspecialchars($kontak['maps_embed']) ?>" width="100%" height="300" style="border:0;display:block;" loading="lazy"></iframe>
                </div>
                <?php else: ?>
                <div style="height:200px;background:var(--cream);border:1.5px solid var(--border);border-radius:0 0 12px 12px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;">
                    <i class="fa-solid fa-map" style="font-size:32px;color:var(--border);"></i>
                    <span style="font-size:13px;color:var(--text-lt);">Belum ada embed maps</span>
                </div>
                <?php endif; ?>

                <!-- WhatsApp info -->
                <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px;margin-top:14px;">
                    <div style="font-size:12px;font-weight:600;color:var(--text-mid);margin-bottom:8px;"><i class="fa-brands fa-whatsapp" style="color:#25d366;margin-right:5px;"></i>Info WhatsApp</div>
                    <div style="font-size:13px;color:var(--text);">
                        Nomor: <strong>+<?= htmlspecialchars($kontak['whatsapp']) ?></strong>
                    </div>
                    <div style="font-size:12px;color:var(--text-lt);margin-top:4px;">Link: https://wa.me/<?= htmlspecialchars($kontak['whatsapp']) ?></div>
                </div>
            </div>
        </div>

<?php /* ════════ TAB: BOOKINGS ════════ */ ?>
<?php elseif ($activeTab === 'bookings'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-calendar-check"></i>
                <h3>Data Booking Terbaru</h3>
                <div class="ms-auto" style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:var(--text-lt);">50 data terbaru</span>
                    <span style="font-size:11px;color:var(--text-lt);background:rgba(139,111,94,.08);padding:4px 10px;border-radius:8px;">
                        <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Klik Edit untuk ubah data booking
                    </span>
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($bookingsRows && mysqli_num_rows($bookingsRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Nama</th>
                            <th>WhatsApp</th>
                            <th>Email</th>
                            <th>Layanan</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Jml</th>
                            <th>Catatan</th>
                            <th>Dipesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($bookingsRows)): ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$row['phone'] ?? '') ?>" target="_blank"
                               style="color:#25d366;text-decoration:none;font-weight:600;font-size:12px;">
                                <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($row['phone'] ?? '') ?>
                            </a>
                        </td>
                        <td style="font-size:12px;"><?= htmlspecialchars($row['email'] ?? '') ?></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['service'] ?? '') ?></span></td>
                        <td><?= htmlspecialchars($row['date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['time'] ?? '') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($row['jumlah_orang'] ?? 1) ?></td>
                        <td style="font-size:12px;color:var(--text-mid);max-width:130px;"><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>
                        <td style="font-size:11px;color:var(--text-lt);"><?= date('d/m/Y H:i', strtotime($row['created_at'] ?? 'now')) ?></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditBooking(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=bookings&action=delete_booking&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus data booking #<?= $row['id'] ?> atas nama <?= addslashes(htmlspecialchars($row['name'] ?? '')) ?>?')">
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
                    <i class="fa-solid fa-calendar-check"></i>
                    <p>Belum ada data booking.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Edit Booking -->
        <div class="cms-modal-overlay" id="modalBooking">
            <div class="cms-modal" style="max-width:640px;">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-calendar-check"></i> Edit Data Booking</h4>
                    <button class="cms-modal-close" onclick="closeModal('modalBooking')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_booking">
                    <input type="hidden" name="booking_id" id="bookingEditId" value="0">
                    <div class="cms-modal-body">
                        <div class="grid-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-user" style="margin-right:4px;"></i>Nama Pelanggan</label>
                                <input type="text" name="bk_name" id="bkName" required placeholder="Nama lengkap">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-brands fa-whatsapp" style="margin-right:4px;"></i>WhatsApp</label>
                                <input type="text" name="bk_phone" id="bkPhone" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-envelope" style="margin-right:4px;"></i>Email</label>
                                <input type="text" name="bk_email" id="bkEmail" placeholder="email@contoh.com">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-spa" style="margin-right:4px;"></i>Layanan</label>
                                <input type="text" name="bk_service" id="bkService" placeholder="Nama layanan">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar" style="margin-right:4px;"></i>Tanggal</label>
                                <input type="date" name="bk_date" id="bkDate">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-clock" style="margin-right:4px;"></i>Jam</label>
                                <select name="bk_time" id="bkTime">
                                    <?php foreach (['09:00','10:00','11:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'] as $j): ?>
                                    <option value="<?= $j ?>"><?= $j ?> WIB</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-users" style="margin-right:4px;"></i>Jumlah Orang</label>
                                <select name="bk_jumlah" id="bkJumlah">
                                    <?php for ($i=1;$i<=10;$i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> Orang</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-sticky-note" style="margin-right:4px;"></i>Catatan</label>
                            <textarea name="bk_catatan" id="bkCatatan" rows="3" placeholder="Catatan tambahan..."></textarea>
                        </div>
                        <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);border-radius:10px;padding:10px 14px;font-size:12px;color:#b91c1c;display:flex;align-items:center;gap:8px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Perubahan data booking akan langsung tersimpan dan terlihat di website.
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalBooking')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ════════ TAB: ORDERS ════════ */ ?>
<?php elseif ($activeTab === 'orders'): ?>

        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-bag-shopping"></i>
                <h3>Data Order Produk Terbaru</h3>
                <div class="ms-auto" style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:var(--text-lt);">50 data terbaru</span>
                    <span style="font-size:11px;color:var(--text-lt);background:rgba(139,111,94,.08);padding:4px 10px;border-radius:8px;">
                        <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Klik Edit untuk ubah data order
                    </span>
                </div>
            </div>
            <div class="cms-card-body" style="padding:0;">
                <?php if ($ordersRows && mysqli_num_rows($ordersRows) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Nama</th>
                            <th>WhatsApp</th>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Alamat</th>
                            <th>Catatan</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($ordersRows)): ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td><?= htmlspecialchars($row['nama'] ?? '') ?></td>
                        <td>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$row['whatsapp'] ?? '') ?>" target="_blank"
                               style="color:#25d366;text-decoration:none;font-weight:600;font-size:12px;">
                                <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($row['whatsapp'] ?? '') ?>
                            </a>
                        </td>
                        <td><strong><?= htmlspecialchars($row['product_name'] ?? '') ?></strong></td>
                        <td style="color:var(--primary);font-weight:600;"><?= htmlspecialchars($row['product_price'] ?? '') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($row['qty'] ?? 1) ?></td>
                        <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['total'] ?? '') ?></td>
                        <td style="font-size:12px;max-width:130px;"><?= htmlspecialchars($row['alamat'] ?? '') ?></td>
                        <td style="font-size:12px;color:var(--text-mid);"><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>
                        <td style="font-size:11px;color:var(--text-lt);"><?= date('d/m/Y H:i', strtotime($row['created_at'] ?? 'now')) ?></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditOrder(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=orders&action=delete_order&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus data order #<?= $row['id'] ?>?')">
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
                    <i class="fa-solid fa-bag-shopping"></i>
                    <p>Belum ada data order produk.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Edit Order -->
        <div class="cms-modal-overlay" id="modalOrder">
            <div class="cms-modal" style="max-width:640px;">
                <div class="cms-modal-header">
                    <h4><i class="fa-solid fa-bag-shopping"></i> Edit Data Order</h4>
                    <button class="cms-modal-close" onclick="closeModal('modalOrder')">&times;</button>
                </div>
                <form method="POST" action="cms.php?action=save_order">
                    <input type="hidden" name="order_id" id="orderEditId" value="0">
                    <div class="cms-modal-body">
                        <div class="grid-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-user" style="margin-right:4px;"></i>Nama Pelanggan</label>
                                <input type="text" name="or_nama" id="orNama" required placeholder="Nama lengkap">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-brands fa-whatsapp" style="margin-right:4px;"></i>WhatsApp</label>
                                <input type="text" name="or_wa" id="orWa" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-box" style="margin-right:4px;"></i>Nama Produk</label>
                                <input type="text" name="or_product" id="orProduct" placeholder="Nama produk">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-tag" style="margin-right:4px;"></i>Harga Satuan</label>
                                <input type="text" name="or_price" id="orPrice" placeholder="Rp 0">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-hashtag" style="margin-right:4px;"></i>Qty</label>
                                <input type="number" name="or_qty" id="orQty" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-money-bill" style="margin-right:4px;"></i>Total</label>
                                <input type="text" name="or_total" id="orTotal" placeholder="Rp 0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-map-pin" style="margin-right:4px;"></i>Alamat Pengiriman</label>
                            <textarea name="or_alamat" id="orAlamat" rows="2" placeholder="Alamat lengkap..."></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-sticky-note" style="margin-right:4px;"></i>Catatan</label>
                            <textarea name="or_catatan" id="orCatatan" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                        <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);border-radius:10px;padding:10px 14px;font-size:12px;color:#b91c1c;display:flex;align-items:center;gap:8px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Perubahan data order akan langsung tersimpan ke database.
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalOrder')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                    </div>
                </form>
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
                        <div style="background:#150d10;border-radius:10px;padding:20px 24px;">
                            <div style="display:flex;flex-wrap:wrap;gap:24px;justify-content:space-around;">
                                <!-- Brand col -->
                                <div style="text-align:center;min-width:160px;">
                                    <div style="color:#D6C1A3;font-family:'Playfair Display',serif;font-weight:700;font-size:15px;margin-bottom:6px;"><?= htmlspecialchars($footer_data['brand_name']) ?></div>
                                    <div style="color:#4a3040;font-size:11px;margin-bottom:10px;"><?= htmlspecialchars($footer_data['brand_desc']) ?></div>
                                    <div style="display:flex;gap:8px;justify-content:center;">
                                        <span style="background:rgba(214,193,163,.12);color:#D6C1A3;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-brands fa-instagram"></i></span>
                                        <span style="background:rgba(214,193,163,.12);color:#D6C1A3;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-brands fa-tiktok"></i></span>
                                        <span style="background:rgba(214,193,163,.12);color:#D6C1A3;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;"><i class="fa-brands fa-whatsapp"></i></span>
                                    </div>
                                </div>
                                <!-- Contact col -->
                                <div style="min-width:180px;">
                                    <div style="color:#D6C1A3;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Contact Us</div>
                                    <div style="color:#4a3040;font-size:11px;line-height:1.9;">
                                        <div><i class="fa-solid fa-map-marker-alt" style="margin-right:5px;color:#8B6F5E;"></i><?= htmlspecialchars($footer_data['address']) ?></div>
                                        <div><i class="fa-solid fa-phone-alt" style="margin-right:5px;color:#8B6F5E;"></i><?= htmlspecialchars($footer_data['phone']) ?></div>
                                        <div><i class="fa-solid fa-envelope" style="margin-right:5px;color:#8B6F5E;"></i><?= htmlspecialchars($footer_data['email']) ?></div>
                                        <div><i class="fa-solid fa-clock" style="margin-right:5px;color:#8B6F5E;"></i><?= htmlspecialchars($footer_data['hours']) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div style="border-top:1px solid rgba(255,255,255,.05);margin-top:16px;padding-top:12px;text-align:center;color:#3a2535;font-size:10px;">
                                &copy; <?= date('Y') ?> <?= htmlspecialchars($footer_data['copyright_text']) ?>
                            </div>
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

                        <!-- Pesan Sukses -->
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                            <i class="fa-solid fa-check-circle" style="margin-right:5px;color:var(--primary);"></i>Pesan Konfirmasi
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-check-circle" style="margin-right:4px;"></i>Pesan Sukses Booking</label>
                            <textarea name="success_message" rows="2" placeholder="Booking berhasil! Kami akan menghubungi Anda via WhatsApp."><?= htmlspecialchars($booking_page['success_message']) ?></textarea>
                            <small style="font-size:11px;color:var(--text-lt);margin-top:4px;display:block;">Muncul di modal popup setelah pelanggan berhasil booking.</small>
                        </div>

                        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">

                        <!-- Daftar Layanan -->
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mid);margin-bottom:14px;">
                            <i class="fa-solid fa-list-check" style="margin-right:5px;color:var(--primary);"></i>Opsi Dropdown Form
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-spa" style="margin-right:4px;"></i>Daftar Layanan (dropdown pilihan layanan)</label>
                            <textarea name="services_list" rows="10" placeholder="Nail Art&#10;Haircut&#10;Coloring&#10;..."><?= htmlspecialchars($booking_page['services_list']) ?></textarea>
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
                            <!-- Pesan sukses preview -->
                            <div style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:10px 14px;font-size:12px;color:#065f46;display:flex;align-items:flex-start;gap:8px;">
                                <i class="fa-solid fa-check-circle" style="margin-top:2px;color:#10b981;"></i>
                                <span><?= htmlspecialchars($booking_page['success_message']) ?></span>
                            </div>
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

<?php endif; ?>

    </div><!-- /.content -->
</div><!-- /.main-wrap -->

<script>
/* ━━ Sidebar Mobile ━━ */
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); document.body.style.overflow=''; }

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
    openModal('modalPrice');
}

function openEditProduct(row) {
    document.getElementById('prodModalTitle').textContent = 'Edit Produk';
    document.getElementById('prodId').value       = row.id;
    document.getElementById('prodName').value     = row.name;
    document.getElementById('prodPrice').value    = row.price;
    document.getElementById('prodCategory').value = row.category;
    var prev = document.getElementById('prodPreview');
    if (row.image) { prev.src = row.image; prev.style.display='block'; }
    openModal('modalProduct');
}

function openEditBooking(row) {
    document.getElementById('bookingEditId').value = row.id;
    document.getElementById('bkName').value    = row.name    || '';
    document.getElementById('bkPhone').value   = row.phone   || '';
    document.getElementById('bkEmail').value   = row.email   || '';
    document.getElementById('bkService').value = row.service || '';
    document.getElementById('bkDate').value    = row.date    || '';
    document.getElementById('bkTime').value    = row.time    || '';
    document.getElementById('bkJumlah').value  = row.jumlah_orang || 1;
    document.getElementById('bkCatatan').value = row.catatan || '';
    openModal('modalBooking');
}

function openEditOrder(row) {
    document.getElementById('orderEditId').value = row.id;
    document.getElementById('orNama').value    = row.nama          || '';
    document.getElementById('orWa').value      = row.whatsapp      || '';
    document.getElementById('orProduct').value = row.product_name  || '';
    document.getElementById('orPrice').value   = row.product_price || '';
    document.getElementById('orQty').value     = row.qty           || 1;
    document.getElementById('orTotal').value   = row.total         || '';
    document.getElementById('orAlamat').value  = row.alamat        || '';
    document.getElementById('orCatatan').value = row.catatan       || '';
    openModal('modalOrder');
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
    openModal('modalPrice');
}
function openAddProduct() {
    document.getElementById('prodModalTitle').textContent = 'Tambah Produk';
    document.getElementById('prodId').value       = '0';
    document.getElementById('prodName').value     = '';
    document.getElementById('prodPrice').value    = '';
    document.getElementById('prodCategory').value = 'simple';
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
</script>

</body>
</html>