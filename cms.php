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
   HELPER: Ensure cms_content table exists
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
}

/* ── Upload helper ── */
function handleUpload($fileKey, $destDir = 'image/') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $ext    = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return null;
    $fname  = uniqid('cms_', true) . '.' . $ext;
    $dest   = rtrim($destDir, '/') . '/' . $fname;
    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
    return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest) ? $dest : null;
}

/* ── Content helper ── */
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

/* ══════════════════════════════════════════════
   HANDLE ALL AJAX / POST ACTIONS
══════════════════════════════════════════════ */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ── 1. Save Hero / Teks Utama ── */
if ($action === 'save_hero') {
    setContent($conn, 'hero', 'title',    $_POST['hero_title']    ?? '');
    setContent($conn, 'hero', 'subtitle', $_POST['hero_subtitle'] ?? '');
    setContent($conn, 'hero', 'btn_primary', $_POST['btn_primary'] ?? '');
    setContent($conn, 'hero', 'btn_secondary', $_POST['btn_secondary'] ?? '');
    if ($up = handleUpload('hero_img1')) setContent($conn, 'hero', 'img1', $up);
    if ($up = handleUpload('hero_img2')) setContent($conn, 'hero', 'img2', $up);
    if ($up = handleUpload('hero_img3')) setContent($conn, 'hero', 'img3', $up);
    header('Location: cms.php?tab=hero&saved=1'); exit;
}

/* ── 2. Save Kontak & Maps ── */
if ($action === 'save_kontak') {
    foreach (['salon_name','address','hours','whatsapp','maps_embed','maps_link'] as $k) {
        setContent($conn, 'kontak', $k, $_POST[$k] ?? '');
    }
    header('Location: cms.php?tab=kontak&saved=1'); exit;
}

/* ── 3. Services CRUD ── */
if ($action === 'save_service') {
    $id   = (int)($_POST['service_id'] ?? 0);
    $name = mysqli_real_escape_string($conn, $_POST['service_name'] ?? '');
    $gallery = mysqli_real_escape_string($conn, $_POST['service_gallery'] ?? '');
    $img  = handleUpload('service_image') ?? '';
    if ($id) {
        $imgSql = $img ? ", image='$img'" : '';
        mysqli_query($conn, "UPDATE cms_services SET name='$name'$imgSql, gallery='$gallery' WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO cms_services (name,image,gallery) VALUES ('$name','$img','$gallery')");
    }
    header('Location: cms.php?tab=services&saved=1'); exit;
}
if ($action === 'delete_service' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM cms_services WHERE id=$id");
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
    $id   = (int)($_POST['prod_id'] ?? 0);
    $name = mysqli_real_escape_string($conn, $_POST['prod_name']     ?? '');
    $price= mysqli_real_escape_string($conn, $_POST['prod_price']    ?? '');
    $cat  = mysqli_real_escape_string($conn, $_POST['prod_category'] ?? '');
    $img  = handleUpload('prod_image') ?? '';
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

/* ── Logout ── */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php"); exit;
}

/* ══════════════════════════════════════════════
   LOAD ALL DATA
══════════════════════════════════════════════ */
$activeTab = $_GET['tab'] ?? 'hero';
$saved     = isset($_GET['saved']);

// Hero
$hero = [
    'title'         => getContent($conn,'hero','title',        'Temukan Kecantikan Terbaikmu'),
    'subtitle'      => getContent($conn,'hero','subtitle',     'Layanan premium untuk tampilan terbaik Anda'),
    'btn_primary'   => getContent($conn,'hero','btn_primary',  'Reservasi Sekarang'),
    'btn_secondary' => getContent($conn,'hero','btn_secondary','Lihat Layanan'),
    'img1'          => getContent($conn,'hero','img1',         'image/homenailart.jpeg'),
    'img2'          => getContent($conn,'hero','img2',         ''),
    'img3'          => getContent($conn,'hero','img3',         ''),
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

// Services
$servicesRows = $conn ? mysqli_query($conn, "SELECT * FROM cms_services ORDER BY sort_order, id") : null;

// Prices
$pricesRows = $conn ? mysqli_query($conn, "SELECT * FROM cms_prices ORDER BY category, sort_order, id") : null;
$pricesCats = [];
if ($pricesRows) {
    while ($r = mysqli_fetch_assoc($pricesRows)) $pricesCats[$r['category']][] = $r;
    mysqli_data_seek($pricesRows, 0);
}

// Products
$productsRows = $conn ? mysqli_query($conn, "SELECT * FROM cms_products ORDER BY sort_order, id") : null;

// Testimoni
$testiRows = $conn ? mysqli_query($conn, "SELECT * FROM cms_testimonials ORDER BY sort_order, id") : null;

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS Dashboard — NISWÀ BEAUTY</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --gold:       #D6C1A3;
    --gold-dark:  #b8a082;
    --primary:    #8B6F5E;
    --dark:       #0f0610;
    --sidebar-w:  260px;
    --cream:      #FAF7F2;
    --white:      #ffffff;
    --border:     #EDE5D8;
    --text:       #2d2d2d;
    --text-mid:   #666;
    --text-light: #999;
    --success:    #10b981;
    --danger:     #ef4444;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--text); }

/* ── SIDEBAR ── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--dark);
    min-height: 100vh;
    position: fixed; left: 0; top: 0;
    display: flex; flex-direction: column;
    z-index: 200;
    transition: transform .3s;
}
.sidebar-brand {
    padding: 24px 22px 20px;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.sidebar-brand .logo {
    font-family: 'Playfair Display', serif;
    font-size: 19px; color: #fff; font-weight: 700;
    display: flex; align-items: center; gap: 10px;
}
.sidebar-brand .logo i { color: var(--gold); font-size: 18px; }
.sidebar-brand .tagline { font-size: 9px; color: #4a3040; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; padding-left: 28px; }
.sidebar-label {
    font-size: 9px; letter-spacing: 2px; text-transform: uppercase;
    color: #3a2535; padding: 18px 22px 6px; font-weight: 700;
}
.nav-list { list-style: none; padding: 0 10px; flex: 1; }
.nav-list li { margin-bottom: 2px; }
.nav-list a {
    display: flex; align-items: center; gap: 11px;
    padding: 11px 14px; color: #8a7880;
    text-decoration: none; border-radius: 10px;
    font-size: 13.5px; transition: all .2s;
}
.nav-list a:hover { background: rgba(214,193,163,.1); color: var(--gold); }
.nav-list a.active { background: rgba(214,193,163,.15); color: var(--gold); }
.nav-list a i { width: 17px; text-align: center; font-size: 14px; }
.nav-list a .badge-new {
    margin-left: auto; background: var(--primary); color: #fff;
    font-size: 9px; padding: 2px 7px; border-radius: 20px; font-weight: 700;
}
.sidebar-footer {
    padding: 14px 18px;
    border-top: 1px solid rgba(255,255,255,.05);
    display: flex; align-items: center; gap: 10px;
}
.sidebar-footer .av {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), #5A4A42);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0;
}
.sidebar-footer .info .name { color: var(--gold); font-size: 13px; font-weight: 600; }
.sidebar-footer .info .role { color: #4a3040; font-size: 11px; }
.sidebar-footer .logout-btn {
    margin-left: auto;
    background: rgba(239,68,68,.12); border: none; color: #ef4444;
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px; transition: .2s; text-decoration: none;
}
.sidebar-footer .logout-btn:hover { background: rgba(239,68,68,.22); }

/* ── MAIN ── */
.main-wrap { margin-left: var(--sidebar-w); min-height: 100vh; }
.topbar {
    background: #fff; padding: 16px 32px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
}
.topbar-title { font-size: 18px; font-weight: 700; color: var(--text); }
.topbar-title span { color: var(--primary); }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.topbar-user { font-size: 13px; color: var(--text-mid); }
.mobile-menu-btn {
    display: none; background: none; border: none; cursor: pointer;
    color: var(--text); font-size: 20px; padding: 4px;
}
.content { padding: 28px 32px; }

/* ── TOAST ── */
.toast-bar {
    position: fixed; top: 20px; right: 24px; z-index: 9999;
    background: var(--success); color: #fff;
    padding: 12px 22px; border-radius: 12px;
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-weight: 600;
    box-shadow: 0 8px 28px rgba(16,185,129,.35);
    animation: toastIn .4s ease;
}
@keyframes toastIn { from { opacity:0; transform:translateY(-16px); } to { opacity:1; transform:translateY(0); } }

/* ── CARDS ── */
.cms-card {
    background: #fff; border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 2px 12px rgba(139,111,94,.06);
    overflow: hidden; margin-bottom: 24px;
}
.cms-card-header {
    padding: 16px 22px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg,rgba(139,111,94,.05),transparent);
}
.cms-card-header i { color: var(--primary); font-size: 16px; }
.cms-card-header h3 { font-size: 15px; font-weight: 700; margin: 0; }
.cms-card-header .ms-auto { margin-left: auto; }
.cms-card-body { padding: 22px; }

/* ── FORM ELEMENTS ── */
.form-group { margin-bottom: 18px; }
label { font-size: 12px; font-weight: 600; color: var(--text-mid); text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: 6px; }
input[type=text], input[type=url], input[type=tel], textarea, select {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: 10px; font-size: 13.5px;
    font-family: 'Poppins', sans-serif;
    background: #fff; color: var(--text);
    outline: none; transition: border-color .2s;
}
input[type=text]:focus, input[type=url]:focus, input[type=tel]:focus, textarea:focus, select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(139,111,94,.12);
}
textarea { resize: vertical; min-height: 80px; }

/* ── BUTTONS ── */
.btn-primary-cms {
    background: linear-gradient(135deg, var(--primary), var(--gold));
    color: #fff; border: none; border-radius: 10px;
    padding: 10px 22px; font-size: 13.5px; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: all .25s; box-shadow: 0 4px 14px rgba(139,111,94,.25);
}
.btn-primary-cms:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(139,111,94,.35); }
.btn-danger-cms {
    background: rgba(239,68,68,.1); color: var(--danger);
    border: 1.5px solid rgba(239,68,68,.2); border-radius: 8px;
    padding: 6px 14px; font-size: 12px; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all .2s; text-decoration: none;
}
.btn-danger-cms:hover { background: rgba(239,68,68,.18); color: var(--danger); }
.btn-edit-cms {
    background: rgba(139,111,94,.1); color: var(--primary);
    border: 1.5px solid rgba(139,111,94,.2); border-radius: 8px;
    padding: 6px 14px; font-size: 12px; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all .2s;
}
.btn-edit-cms:hover { background: rgba(139,111,94,.18); }
.btn-sm-add {
    background: var(--cream); border: 1.5px dashed var(--border);
    color: var(--primary); border-radius: 10px;
    padding: 9px 18px; font-size: 13px; font-weight: 600;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: all .2s;
}
.btn-sm-add:hover { border-color: var(--primary); background: rgba(139,111,94,.06); }

/* ── TABLE ── */
.cms-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cms-table thead tr { background: #faf5f0; }
.cms-table th { padding: 11px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--primary); border-bottom: 2px solid var(--border); text-align: left; }
.cms-table td { padding: 12px 14px; border-bottom: 1px solid #f5ede6; vertical-align: middle; }
.cms-table tbody tr:hover { background: #fdf8f4; }
.cms-table tbody tr:last-child td { border-bottom: none; }
.actions-cell { display: flex; gap: 6px; align-items: center; }

/* ── IMAGE PREVIEW ── */
.img-preview {
    width: 54px; height: 54px; object-fit: cover;
    border-radius: 8px; border: 1.5px solid var(--border);
}
.img-upload-box {
    border: 2px dashed var(--border); border-radius: 12px;
    padding: 20px; text-align: center; cursor: pointer;
    transition: all .2s; background: #fdfaf7;
}
.img-upload-box:hover { border-color: var(--primary); background: rgba(139,111,94,.04); }
.img-upload-box i { font-size: 26px; color: var(--gold-dark); margin-bottom: 8px; display: block; }
.img-upload-box p { font-size: 12px; color: var(--text-mid); margin: 0; }
input[type=file] { display: none; }

/* ── MODAL ── */
.cms-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 5000;
    align-items: center; justify-content: center;
    padding: 20px;
}
.cms-modal-overlay.open { display: flex; }
.cms-modal {
    background: #fff; border-radius: 18px;
    width: 100%; max-width: 540px;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 24px 60px rgba(0,0,0,.25);
    animation: modalIn .25s ease;
}
@keyframes modalIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.cms-modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, var(--primary), var(--gold));
}
.cms-modal-header h4 { color: #fff; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.cms-modal-close {
    background: rgba(255,255,255,.2); border: none; color: #fff;
    width: 32px; height: 32px; border-radius: 50%; font-size: 18px;
    cursor: pointer; line-height: 1; transition: .2s;
}
.cms-modal-close:hover { background: rgba(255,255,255,.35); }
.cms-modal-body { padding: 24px; }
.cms-modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

/* ── CATEGORY BADGE ── */
.cat-badge {
    display: inline-block; padding: 3px 10px;
    background: rgba(139,111,94,.1); color: var(--primary);
    border-radius: 20px; font-size: 11px; font-weight: 600;
}

/* ── PREVIEW THUMBNAIL ── */
.prev-thumb {
    width: 100%; max-height: 200px; object-fit: cover;
    border-radius: 10px; display: none; margin-top: 10px;
    border: 1.5px solid var(--border);
}

/* ── GRID 2 COL ── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }

/* ── MOBILE ── */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-wrap { margin-left: 0; }
    .mobile-menu-btn { display: block; }
    .content { padding: 18px 16px; }
    .topbar { padding: 14px 18px; }
    .cms-table { font-size: 12px; }
    .sidebar-overlay { display: block; }
}
.sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 190;
}
.sidebar-overlay.open { display: block; }

/* ── SECTION TABS ── */
.tab-nav {
    display: flex; gap: 4px; flex-wrap: wrap;
    margin-bottom: 24px;
    background: #fff; padding: 6px;
    border-radius: 12px; border: 1px solid var(--border);
}
.tab-nav a {
    padding: 9px 16px; border-radius: 9px;
    font-size: 13px; font-weight: 600; color: var(--text-mid);
    text-decoration: none; display: flex; align-items: center; gap: 7px;
    transition: all .2s;
}
.tab-nav a:hover { color: var(--primary); background: rgba(139,111,94,.07); }
.tab-nav a.active {
    background: linear-gradient(135deg, var(--primary), var(--gold-dark));
    color: #fff; box-shadow: 0 4px 14px rgba(139,111,94,.28);
}

/* ── EMPTY STATE ── */
.empty-cms {
    text-align: center; padding: 50px 20px;
    color: var(--text-light);
}
.empty-cms i { font-size: 42px; color: var(--border); margin-bottom: 12px; display: block; }
.empty-cms p { font-size: 14px; }

/* ── COLOR SWATCHES ── */
.color-swatches { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
.swatch {
    width: 28px; height: 28px; border-radius: 50%;
    cursor: pointer; border: 2px solid transparent;
    transition: .2s;
}
.swatch:hover, .swatch.selected { border-color: var(--text); transform: scale(1.15); }
</style>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo"><i class="fa-solid fa-spa"></i> NISWÀ BEAUTY</div>
        <div class="tagline">CMS Panel Admin</div>
    </div>

    <div class="sidebar-label">Kelola Konten</div>
    <ul class="nav-list">
        <li><a href="cms.php?tab=hero"      class="<?= $activeTab==='hero'      ? 'active':'' ?>"><i class="fa-solid fa-image"></i> Hero & Teks Utama</a></li>
        <li><a href="cms.php?tab=services"  class="<?= $activeTab==='services'  ? 'active':'' ?>"><i class="fa-solid fa-scissors"></i> Daftar Layanan</a></li>
        <li><a href="cms.php?tab=prices"    class="<?= $activeTab==='prices'    ? 'active':'' ?>"><i class="fa-solid fa-tag"></i> Daftar Harga</a></li>
        <li><a href="cms.php?tab=products"  class="<?= $activeTab==='products'  ? 'active':'' ?>"><i class="fa-solid fa-box-open"></i> Produk</a></li>
        <li><a href="cms.php?tab=testimoni" class="<?= $activeTab==='testimoni' ? 'active':'' ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a></li>
        <li><a href="cms.php?tab=kontak"    class="<?= $activeTab==='kontak'    ? 'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Kontak & Maps</a></li>
    </ul>

    <div class="sidebar-label">Manajemen</div>
    <ul class="nav-list">
        <li><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Data Booking & Order</a></li>
        <li><a href="index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website</a></li>
    </ul>

    <div class="sidebar-footer">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div class="info">
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Admin</div>
        </div>
        <a href="cms.php?logout=1" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</aside>

<!-- ══ MAIN WRAP ══ -->
<div class="main-wrap">

    <!-- Topbar -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <button class="mobile-menu-btn" onclick="openSidebar()"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">Panel <span>CMS</span></div>
        </div>
        <div class="topbar-right">
            <span class="topbar-user"><i class="fa-regular fa-circle-user" style="margin-right:5px;"></i><?= htmlspecialchars($_SESSION['user']) ?></span>
            <a href="cms.php?logout=1" style="color:var(--danger);font-size:13px;text-decoration:none;" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>

    <!-- Toast -->
    <?php if ($saved): ?>
    <div class="toast-bar" id="toastBar">
        <i class="fa-solid fa-circle-check"></i> Perubahan berhasil disimpan!
    </div>
    <script>setTimeout(()=>{const t=document.getElementById('toastBar');if(t)t.style.display='none';},3500);</script>
    <?php endif; ?>

    <!-- Content -->
    <div class="content">

        <!-- Tab Nav -->
        <div class="tab-nav">
            <a href="cms.php?tab=hero"      class="<?= $activeTab==='hero'      ? 'active':'' ?>"><i class="fa-solid fa-image"></i> Hero</a>
            <a href="cms.php?tab=services"  class="<?= $activeTab==='services'  ? 'active':'' ?>"><i class="fa-solid fa-scissors"></i> Layanan</a>
            <a href="cms.php?tab=prices"    class="<?= $activeTab==='prices'    ? 'active':'' ?>"><i class="fa-solid fa-tag"></i> Harga</a>
            <a href="cms.php?tab=products"  class="<?= $activeTab==='products'  ? 'active':'' ?>"><i class="fa-solid fa-box-open"></i> Produk</a>
            <a href="cms.php?tab=testimoni" class="<?= $activeTab==='testimoni' ? 'active':'' ?>"><i class="fa-solid fa-comment-dots"></i> Testimoni</a>
            <a href="cms.php?tab=kontak"    class="<?= $activeTab==='kontak'    ? 'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Kontak</a>
        </div>

<?php /* ══════════════════════ TAB: HERO ══════════════════════ */ ?>
<?php if ($activeTab === 'hero'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-image"></i>
                <h3>Hero & Teks Utama</h3>
            </div>
            <div class="cms-card-body">
                <form method="POST" action="cms.php?action=save_hero" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-heading" style="margin-right:5px;"></i>Judul Hero</label>
                            <input type="text" name="hero_title" value="<?= htmlspecialchars($hero['title']) ?>" placeholder="Judul utama hero">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-align-left" style="margin-right:5px;"></i>Subjudul Hero</label>
                            <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($hero['subtitle']) ?>" placeholder="Teks di bawah judul">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-hand-pointer" style="margin-right:5px;"></i>Teks Tombol Utama</label>
                            <input type="text" name="btn_primary" value="<?= htmlspecialchars($hero['btn_primary']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-hand-pointer" style="margin-right:5px;"></i>Teks Tombol Kedua</label>
                            <input type="text" name="btn_secondary" value="<?= htmlspecialchars($hero['btn_secondary']) ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="margin-top:8px;">
                        <?php foreach ([1,2,3] as $n):
                            $imgVal = $hero["img$n"]; ?>
                        <div class="form-group">
                            <label><i class="fa-regular fa-image" style="margin-right:5px;"></i>Gambar Slider <?= $n ?></label>
                            <label class="img-upload-box" for="hero_img<?= $n ?>_input">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Klik untuk upload gambar<br><small style="color:var(--text-light);">JPG, PNG, WEBP</small></p>
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

<?php /* ══════════════════════ TAB: SERVICES ══════════════════════ */ ?>
<?php elseif ($activeTab === 'services'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-scissors"></i>
                <h3>Daftar Layanan</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openModal('modalService')">
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
                            <th>Foto</th>
                            <th>Nama Layanan</th>
                            <th>Galeri (paths)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($servicesRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?php if($row['image']): ?><img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview"><?php else: ?><span style="color:var(--text-light);font-size:11px;">Belum ada</span><?php endif; ?></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td style="font-size:11px;color:var(--text-light);max-width:200px;word-break:break-all;"><?= htmlspecialchars($row['gallery']) ?></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditService(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=services&action=delete_service&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus layanan ini?')">
                                    <i class="fa-solid fa-trash"></i> Hapus
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
                    <p>Belum ada layanan. Klik "Tambah Layanan" untuk mulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Tambah/Edit Layanan -->
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
                            <input type="text" name="service_name" id="serviceName" placeholder="cth: Nail Art, Haircut..." required>
                        </div>
                        <div class="form-group">
                            <label>Foto Utama Layanan</label>
                            <label class="img-upload-box" for="svcImgInput">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p>Upload foto layanan</p>
                            </label>
                            <input type="file" id="svcImgInput" name="service_image" accept="image/*" onchange="previewImg(this,'svcPreview')">
                            <img src="" class="prev-thumb" id="svcPreview">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-regular fa-images" style="margin-right:5px;"></i>Path Galeri <small style="text-transform:none;color:var(--text-light);">(pisahkan dengan koma)</small></label>
                            <textarea name="service_gallery" id="serviceGallery" placeholder="image/foto1.jpg, image/foto2.jpg"></textarea>
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalService')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ══════════════════════ TAB: PRICES ══════════════════════ */ ?>
<?php elseif ($activeTab === 'prices'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-tag"></i>
                <h3>Daftar Harga</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openModal('modalPrice')">
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
                            <th>#</th>
                            <th>Kategori</th>
                            <th>Nama Layanan</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($pricesRows)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td style="color:var(--primary);font-weight:700;"><?= htmlspecialchars($row['price']) ?></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditPrice(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <a href="cms.php?tab=prices&action=delete_price&id=<?= $row['id'] ?>" class="btn-danger-cms" onclick="return confirm('Hapus item harga ini?')">
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
                    <i class="fa-solid fa-tag"></i>
                    <p>Belum ada daftar harga. Klik "Tambah Item" untuk mulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Tambah/Edit Harga -->
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
                            <input type="text" name="price_cat" id="priceCat" placeholder="cth: Hair Care, Nail Art..." required>
                        </div>
                        <div class="form-group">
                            <label>Nama Layanan</label>
                            <input type="text" name="price_name" id="priceName" placeholder="cth: Smoothing Rambut Pendek" required>
                        </div>
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="text" name="price_val" id="priceVal" placeholder="cth: Rp 150.000" required>
                        </div>
                    </div>
                    <div class="cms-modal-footer">
                        <button type="button" class="btn-edit-cms" onclick="closeModal('modalPrice')">Batal</button>
                        <button type="submit" class="btn-primary-cms"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

<?php /* ══════════════════════ TAB: PRODUCTS ══════════════════════ */ ?>
<?php elseif ($activeTab === 'products'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-box-open"></i>
                <h3>Produk (Press On Nail Collection)</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openModal('modalProduct')">
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
                        <td><?php if($row['image']): ?><img src="<?= htmlspecialchars($row['image']) ?>" class="img-preview"><?php else: ?><span style="color:var(--text-light);font-size:11px;">—</span><?php endif; ?></td>
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
                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-box-open"></i>
                    <p>Belum ada produk. Klik "Tambah Produk" untuk mulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Tambah/Edit Produk -->
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
                                <p>Upload foto produk</p>
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

<?php /* ══════════════════════ TAB: TESTIMONI ══════════════════════ */ ?>
<?php elseif ($activeTab === 'testimoni'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-comment-dots"></i>
                <h3>Testimoni Pelanggan</h3>
                <div class="ms-auto">
                    <button class="btn-sm-add" onclick="openModal('modalTesti')">
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
                            <th>Teks Ulasan</th>
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
                        <td style="font-size:12px;color:var(--text-mid);max-width:220px;"><?= htmlspecialchars(substr($row['text'],0,80)) ?>...</td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn-edit-cms" onclick='openEditTesti(<?= json_encode($row) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
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
                <?php else: ?>
                <div class="empty-cms">
                    <i class="fa-solid fa-comment-dots"></i>
                    <p>Belum ada testimoni. Klik "Tambah Testimoni" untuk mulai.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal: Tambah/Edit Testimoni -->
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
                            <textarea name="testi_text" id="testiText" placeholder="Tulis ulasan pelanggan..." rows="4" required></textarea>
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

<?php /* ══════════════════════ TAB: KONTAK ══════════════════════ */ ?>
<?php elseif ($activeTab === 'kontak'): ?>
        <div class="cms-card">
            <div class="cms-card-header">
                <i class="fa-solid fa-location-dot"></i>
                <h3>Kontak & Maps</h3>
            </div>
            <div class="cms-card-body">
                <form method="POST" action="cms.php?action=save_kontak">
                    <div class="grid-2">
                        <div class="form-group">
                            <label><i class="fa-solid fa-store" style="margin-right:5px;"></i>Nama Salon</label>
                            <input type="text" name="salon_name" value="<?= htmlspecialchars($kontak['salon_name']) ?>" placeholder="NISWÀ BEAUTY">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-brands fa-whatsapp" style="margin-right:5px;"></i>Nomor WhatsApp <small style="text-transform:none;color:var(--text-light);">(tanpa +, awali 62)</small></label>
                            <input type="tel" name="whatsapp" value="<?= htmlspecialchars($kontak['whatsapp']) ?>" placeholder="62812345678">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-map-pin" style="margin-right:5px;"></i>Alamat Lengkap</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($kontak['address']) ?>" placeholder="Jl. Watulumpang, Bangsri, Jepara">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-regular fa-clock" style="margin-right:5px;"></i>Jam Operasional</label>
                        <input type="text" name="hours" value="<?= htmlspecialchars($kontak['hours']) ?>" placeholder="Senin – Minggu, 08.00 – 20.00">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-link" style="margin-right:5px;"></i>Link Google Maps (untuk tombol Petunjuk Arah)</label>
                        <input type="url" name="maps_link" value="<?= htmlspecialchars($kontak['maps_link']) ?>" placeholder="https://maps.app.goo.gl/...">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-code" style="margin-right:5px;"></i>Embed URL Google Maps <small style="text-transform:none;color:var(--text-light);">(src dari iframe embed)</small></label>
                        <textarea name="maps_embed" rows="3" placeholder="https://www.google.com/maps/embed?pb=..."><?= htmlspecialchars($kontak['maps_embed']) ?></textarea>
                        <small style="font-size:11px;color:var(--text-light);margin-top:4px;display:block;">
                            <i class="fa-solid fa-circle-info"></i> Buka Google Maps → Share → Embed a map → Salin URL dari atribut <code>src</code>
                        </small>
                    </div>

                    <?php if ($kontak['maps_embed']): ?>
                    <div class="form-group">
                        <label>Preview Maps</label>
                        <div style="border-radius:12px;overflow:hidden;border:1.5px solid var(--border);">
                            <iframe src="<?= htmlspecialchars($kontak['maps_embed']) ?>" width="100%" height="240" style="border:0;display:block;" loading="lazy"></iframe>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary-cms">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

<?php endif; ?>

    </div><!-- /.content -->
</div><!-- /.main-wrap -->

<script>
/* ── Sidebar Mobile ── */
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); document.body.style.overflow=''; }

/* ── Modal ── */
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.cms-modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('open');
            document.body.style.overflow='';
        }
    });
});

/* ── Image Preview ── */
function previewImg(input, previewId) {
    var prev = document.getElementById(previewId);
    if (!prev) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { prev.src = e.target.result; prev.style.display='block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ── Edit Service ── */
function openEditService(row) {
    document.getElementById('serviceModalTitle').textContent = 'Edit Layanan';
    document.getElementById('serviceId').value   = row.id;
    document.getElementById('serviceName').value = row.name;
    document.getElementById('serviceGallery').value = row.gallery || '';
    var prev = document.getElementById('svcPreview');
    if (row.image) { prev.src = row.image; prev.style.display = 'block'; }
    openModal('modalService');
}

/* ── Edit Price ── */
function openEditPrice(row) {
    document.getElementById('priceModalTitle').textContent = 'Edit Item Harga';
    document.getElementById('priceId').value  = row.id;
    document.getElementById('priceCat').value = row.category;
    document.getElementById('priceName').value= row.name;
    document.getElementById('priceVal').value = row.price;
    openModal('modalPrice');
}

/* ── Edit Product ── */
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

/* ── Edit Testimoni ── */
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

/* ── Color Swatch ── */
function selectSwatch(el) {
    document.querySelectorAll('.swatch').forEach(function(s) { s.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('testiColor').value = el.dataset.color;
}
// Select first swatch by default
(function() {
    var first = document.querySelector('.swatch');
    if (first) first.classList.add('selected');
})();
</script>

</body>
</html>