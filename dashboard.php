<?php
session_start();

// Proteksi: wajib login DAN harus role admin
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    // User biasa tidak boleh akses dashboard
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/db.php';

// Pastikan tabel bookings ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(120),
    service VARCHAR(150),
    date DATE,
    time TIME,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM bookings WHERE id = $id");
    header('Location: dashboard.php?msg=deleted');
    exit;
}

// Fetch stats — null-safe agar tidak crash jika tabel masih kosong
function safeCount($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return (int)($row['n'] ?? 0);
}
$total       = safeCount($conn, "SELECT COUNT(*) AS n FROM bookings");
$today       = safeCount($conn, "SELECT COUNT(*) AS n FROM bookings WHERE date = CURDATE()");
$week        = safeCount($conn, "SELECT COUNT(*) AS n FROM bookings WHERE YEARWEEK(date,1)=YEARWEEK(CURDATE(),1)");
$newBookings = safeCount($conn, "SELECT COUNT(*) AS n FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Fetch all bookings
$result = mysqli_query($conn, "SELECT * FROM bookings ORDER BY created_at DESC");

// Fetch orders (buat tabel jika belum ada)
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
    product_image VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$ordersResult = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
$totalOrders  = ($ordersResult) ? mysqli_num_rows($ordersResult) : 0;
if ($ordersResult) mysqli_data_seek($ordersResult, 0);

// Mapping product_name+price → image path (dari cms_products, fallback hardcoded)
// Format key: "nama||harga" agar produk dengan nama sama tapi harga beda dibedakan
$productImageMap = []; // key: "nama||harga" => path
$productImageByName = []; // key: "nama" => path (fallback jika harga tidak cocok)

$_pr = mysqli_query($conn, "SELECT name, price, image FROM cms_products WHERE image != '' ORDER BY id");
if ($_pr) {
    while ($_row = mysqli_fetch_assoc($_pr)) {
        $key = $_row['name'] . '||' . $_row['price'];
        $productImageMap[$key] = $_row['image'];
        // Simpan juga per nama (ambil yang pertama sebagai fallback)
        if (!isset($productImageByName[$_row['name']])) {
            $productImageByName[$_row['name']] = $_row['image'];
        }
    }
}

// Fallback hardcoded jika cms_products kosong
$_fallbackImages = [
    'Cat Eye Nails'           => 'image/nail1,22k.jpeg',
    'Cat Eye Nails Pink'      => 'image/WhatsApp Image 2026-05-07 at 10.10.41.jpeg',
    'Cat Eye Coquette Nails'  => 'image/cateyeqouket.jpeg',
    'Butterfly Nails'         => 'image/WhatsApp Image 2026-05-06 at 11.22.27.jpeg',
    'Cat Eye Red Nails'       => 'image/WhatsApp Image 2026-05-06 at 11.05.12 (1).jpeg',
    'Simple Nails'            => 'image/WhatsApp Image 2026-05-07 at 10.09.45.jpeg',
    'Cat Eye Pink Nails'      => 'image/WhatsApp Image 2026-05-06 at 11.05.11.jpeg',
    'Sun Flower'              => 'image/WhatsApp Image 2026-05-07 at 10.05.32.jpeg',
    'Bling bling Nails'       => 'image/WhatsApp Image 2026-05-07 at 10.10.14.jpeg',
    'Elegant Nails'           => 'image/WhatsApp Image 2026-05-06 at 10.21.24.jpeg',
];

// Fungsi bantu: ambil gambar produk berdasarkan nama + harga
function getProductImage($name, $price, $productImageMap, $productImageByName, $fallback) {
    $key = $name . '||' . $price;
    if (isset($productImageMap[$key]))   return $productImageMap[$key];
    if (isset($productImageByName[$name])) return $productImageByName[$name];
    if (isset($fallback[$name]))          return $fallback[$name];
    return null;
}

// Fungsi bantu: kembalikan JSON array gambar dari kolom product_image atau fallback
// product_image bisa: JSON array ["a.jpg","b.jpg"], string biasa "a.jpg", atau NULL
function getProductImagesJson($row, $productImageMap, $productImageByName, $fallback) {
    $stored = $row['product_image'] ?? '';
    if (!empty($stored)) {
        $decoded = json_decode($stored, true);
        if (is_array($decoded) && count($decoded) > 0) {
            return json_encode(array_values(array_unique($decoded)));
        }
        // String biasa (order lama)
        return json_encode([$stored]);
    }
    // Fallback dari CMS / hardcoded
    $img = getProductImage($row['product_name'], $row['product_price'], $productImageMap, $productImageByName, $fallback);
    return $img ? json_encode([$img]) : null;
}

// Tetap sediakan $productImages untuk kompatibilitas (dipakai di beberapa tempat)
$productImages = $productImageByName + $_fallbackImages;

// Auto-add kolom product_image jika belum ada
$_cek_col = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'product_image'");
if ($_cek_col && mysqli_num_rows($_cek_col) === 0) {
    mysqli_query($conn, "ALTER TABLE orders ADD COLUMN product_image VARCHAR(255) DEFAULT NULL");
}

// Handle delete order
if (isset($_GET['delete_order']) && is_numeric($_GET['delete_order'])) {
    $oid = (int)$_GET['delete_order'];
    mysqli_query($conn, "DELETE FROM orders WHERE id = $oid");
    header('Location: dashboard.php?msg=order_deleted');
    exit;
}

// Handle edit booking (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_booking_id'])) {
    $id      = (int)$_POST['edit_booking_id'];
    $name    = mysqli_real_escape_string($conn, trim($_POST['edit_name']));
    $phone   = mysqli_real_escape_string($conn, trim($_POST['edit_phone']));
    $email   = mysqli_real_escape_string($conn, trim($_POST['edit_email']));
    $service = mysqli_real_escape_string($conn, trim($_POST['edit_service']));
    $date    = mysqli_real_escape_string($conn, trim($_POST['edit_date']));
    $time    = mysqli_real_escape_string($conn, trim($_POST['edit_time']));
    mysqli_query($conn, "UPDATE bookings SET name='$name', phone='$phone', email='$email', service='$service', date='$date', time='$time' WHERE id=$id");
    header('Location: dashboard.php?msg=booking_updated');
    exit;
}

// Handle edit order (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order_id'])) {
    $id    = (int)$_POST['edit_order_id'];
    $nama  = mysqli_real_escape_string($conn, trim($_POST['edit_nama']));
    $wa    = mysqli_real_escape_string($conn, trim($_POST['edit_whatsapp']));
    $alamat= mysqli_real_escape_string($conn, trim($_POST['edit_alamat']));
    $pname = mysqli_real_escape_string($conn, trim($_POST['edit_product_name']));
    $price = mysqli_real_escape_string($conn, trim($_POST['edit_product_price']));
    $qty   = (int)$_POST['edit_qty'];
    $total = mysqli_real_escape_string($conn, trim($_POST['edit_total']));
    $cat   = mysqli_real_escape_string($conn, trim($_POST['edit_catatan']));

    // Handle upload gambar produk
    $uploadedImg = null;
    if (!empty($_FILES['edit_product_image']['name']) && $_FILES['edit_product_image']['error'] === UPLOAD_ERR_OK) {
        $ext   = strtolower(pathinfo($_FILES['edit_product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $uploadDir = 'image/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName = 'order_prod_' . $id . '_' . time() . '.' . $ext;
            $dest    = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['edit_product_image']['tmp_name'], $dest)) {
                $uploadedImg = mysqli_real_escape_string($conn, $dest);
            }
        }
    }

    if ($id > 0) {
        if ($uploadedImg) {
            mysqli_query($conn, "UPDATE orders SET nama='$nama', whatsapp='$wa', alamat='$alamat', product_name='$pname', product_price='$price', qty=$qty, total='$total', catatan='$cat', product_image='$uploadedImg' WHERE id=$id");
        } else {
            mysqli_query($conn, "UPDATE orders SET nama='$nama', whatsapp='$wa', alamat='$alamat', product_name='$pname', product_price='$price', qty=$qty, total='$total', catatan='$cat' WHERE id=$id");
        }
        header('Location: dashboard.php?msg=order_updated');
    } else {
        mysqli_query($conn, "INSERT INTO orders (nama,whatsapp,alamat,product_name,product_price,qty,total,catatan) VALUES ('$nama','$wa','$alamat','$pname','$price',$qty,'$total','$cat')");
        header('Location: dashboard.php?msg=order_updated');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — NISWÀ BEAUTY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #D6C1A3;
            --gold-dark: #b8a082;
            --dark: #0f0610;
            --cream: #FAF7F2;
            --text-dark: #2d2d2d;
            --text-mid: #5a5a5a;
            --text-light: #999;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: var(--cream); }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: var(--dark);
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            padding: 28px 0 100px;
            z-index: 100;
        }
        .sidebar-brand {
            color: white;
            font-size: 18px;
            font-weight: 700;
            padding: 0 24px 24px;
            border-bottom: 1px solid #1e0d1a;
            margin-bottom: 16px;
        }
        .sidebar-brand i { color: var(--gold); }
        .sidebar-brand small { display: block; font-size: 10px; color: #5a4050; letter-spacing: 1px; margin-top: 4px; font-weight: 400; }
        .sidebar-section { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #3a2030; padding: 14px 24px 6px; font-weight: 700; }
        .sidebar-nav { list-style: none; padding: 0 12px; }
        .sidebar-nav li { margin-bottom: 2px; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; color: #8b7880;
            text-decoration: none; border-radius: 10px;
            font-size: 14px; transition: 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(180,148,110,0.12); color: var(--gold); }
        .sidebar-nav a i { width: 16px; text-align: center; }
        .sidebar-user {
            position: fixed; bottom: 0; left: 0; width: 250px;
            padding: 14px 18px; border-top: 1px solid #1e0d1a;
            display: flex; align-items: center; gap: 10px;
            background: var(--dark);
        }
        .sidebar-user .av {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #5A4A42);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;
        }
        .sidebar-user .name { color: var(--gold); font-size: 13px; font-weight: 600; }
        .sidebar-user .role { color: #5a4050; font-size: 11px; }

        /* MAIN */
        .main { margin-left: 250px; padding: 36px 36px; min-height: 100vh; }

        /* ===== MOBILE TOPBAR ===== */
        .mobile-topbar {
            display: none;
            position: fixed; top: 0; left: 0; right: 0;
            background: var(--dark); z-index: 200;
            padding: 14px 18px;
            align-items: center; justify-content: space-between;
        }
        .mobile-topbar .brand { color: white; font-weight: 700; font-size: 16px; }
        .mobile-topbar .brand i { color: var(--gold); }
        .mobile-hamburger {
            background: none; border: none; cursor: pointer;
            display: flex; flex-direction: column; gap: 5px; padding: 4px;
        }
        .mobile-hamburger span {
            display: block; width: 22px; height: 2px;
            background: var(--gold); border-radius: 2px;
            transition: 0.3s;
        }

        /* ===== MOBILE DRAWER ===== */
        .mobile-drawer-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.55); z-index: 300;
        }
        .mobile-drawer-overlay.open { display: block; }
        .mobile-drawer {
            position: fixed; top: 0; left: -270px; bottom: 0;
            width: 260px; background: var(--dark);
            z-index: 310; padding: 28px 0 80px;
            transition: left 0.3s ease; overflow-y: auto;
        }
        .mobile-drawer.open { left: 0; }
        .mobile-drawer .sidebar-brand { padding: 0 22px 22px; border-bottom: 1px solid #1e0d1a; margin-bottom: 14px; }
        .mobile-drawer .sidebar-section { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #3a2030; padding: 14px 22px 6px; font-weight: 700; }
        .mobile-drawer .sidebar-nav { list-style: none; padding: 0 10px; }
        .mobile-drawer .sidebar-nav li { margin-bottom: 2px; }
        .mobile-drawer .sidebar-nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; color: #8b7880;
            text-decoration: none; border-radius: 10px;
            font-size: 14px; transition: 0.2s;
        }
        .mobile-drawer .sidebar-nav a:hover { background: rgba(180,148,110,0.12); color: var(--gold); }
        .mobile-drawer .sidebar-nav a i { width: 16px; text-align: center; }
        .mobile-drawer-user {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 14px 18px; border-top: 1px solid #1e0d1a;
            display: flex; align-items: center; gap: 10px;
            background: var(--dark);
        }
        .mobile-drawer-user .av {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #5A4A42);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;
        }
        .mobile-drawer-user .name { color: var(--gold); font-size: 13px; font-weight: 600; }
        .mobile-drawer-user .role { color: #5a4050; font-size: 11px; }

        /* ===== MOBILE TABLE → CARD VIEW ===== */
        @media(max-width:991px){
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 80px 14px 24px; }
            .mobile-topbar { display: flex; }

            /* Stat cards: 2 per row tapi lebih proporsional */
            .stat-card .num { font-size: 26px; }
            .stat-card .lbl { font-size: 12px; }
            .stat-card { padding: 16px 14px; }
            .stat-card .big-icon { font-size: 36px; }

            /* Page header */
            .page-header h2 { font-size: 18px; }
            .page-header p { font-size: 12px; }
            .btn-new { padding: 8px 16px; font-size: 12px; }

            /* Search box full width */
            .table-top { flex-direction: column; align-items: flex-start; gap: 10px; }
            .search-wrap { width: 100%; }
            .search-box { width: 100%; }

            /* Hide regular table, show card view */
            .desktop-table { display: none !important; }
            .mobile-cards { display: block !important; }
        }

        @media(min-width:992px){
            .mobile-cards { display: none !important; }
            .desktop-table { display: block !important; }
        }

        /* ===== MOBILE CARD STYLES ===== */
        .mobile-cards { padding: 12px 16px 16px; }
        .m-card {
            background: #fdfaf7; border: 1px solid #f0ebe3;
            border-radius: 14px; padding: 14px 16px; margin-bottom: 12px;
        }
        .m-card-header {
            display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
        }
        .m-card-header .num-badge {
            background: var(--dark); color: var(--gold);
            font-size: 11px; font-weight: 700;
            border-radius: 6px; padding: 2px 8px; flex-shrink: 0;
        }
        .m-card-header .m-name { font-weight: 700; font-size: 14px; color: var(--text-dark); }
        .m-card-rows { display: flex; flex-direction: column; gap: 6px; }
        .m-card-row {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 12.5px;
        }
        .m-card-row .lbl {
            color: var(--text-light); font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            min-width: 72px; flex-shrink: 0; padding-top: 1px;
        }
        .m-card-row .val { color: var(--text-dark); flex: 1; word-break: break-word; }
        .m-card-footer { margin-top: 12px; display: flex; justify-content: flex-end; }
        .m-product-thumb {
            width: 36px; height: 36px; border-radius: 8px;
            object-fit: cover; cursor: pointer; border: 1px solid #e8e0d8;
        }

        /* HEADER */
        .page-header { margin-bottom: 28px; }
        .page-header h2 { font-size: 22px; font-weight: 700; color: var(--text-dark); }
        .page-header p { font-size: 13px; color: var(--text-mid); margin-top: 4px; }

        /* STAT CARDS */
        .stat-card {
            background: white; border-radius: 16px;
            padding: 22px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: relative; overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 12px 32px rgba(0,0,0,0.13);
            background: linear-gradient(135deg, #fff9f4, #fff);
        }
        .stat-card:hover .big-icon {
            opacity: 0.15;
            transform: translateY(-50%) scale(1.15);
        }
        .stat-card:hover .num {
            letter-spacing: 1px;
        }
        .stat-card .big-icon {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            font-size: 48px; opacity: 0.07;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .stat-card .num { font-size: 32px; font-weight: 700; line-height: 1; transition: letter-spacing 0.25s ease; }
        .stat-card .lbl { font-size: 13px; color: var(--text-mid); margin-top: 6px; }

        /* TABLE WRAPPER */
        .table-wrap {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            overflow: hidden; margin-top: 28px;
        }
        .table-top {
            padding: 18px 22px; border-bottom: 1px solid #f0ebe3;
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .table-top h5 { font-size: 15px; font-weight: 700; color: var(--text-dark); margin: 0; }
        .search-wrap {
            position: relative; display: flex; align-items: center;
        }
        .search-icon {
            position: absolute; left: 13px;
            color: var(--gold-dark); font-size: 13px; pointer-events: none;
        }
        .search-box {
            border: 1.5px solid #e8e0d8; border-radius: 30px;
            padding: 8px 16px 8px 34px; font-size: 13px; outline: none;
            font-family: 'Poppins', sans-serif; width: 220px;
        }
        .search-box:focus { border-color: var(--gold); }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #faf7f2; padding: 12px 14px;
            font-size: 12px; font-weight: 600; color: var(--text-mid);
            text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 1px solid #f0ebe3; white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid #faf7f2; transition: 0.15s; }
        tbody tr:hover { background: #fdf9f5; }
        tbody td { padding: 13px 14px; font-size: 13px; color: var(--text-dark); vertical-align: middle; }
        .avatar-cell {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #5A4A42);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 12px; flex-shrink: 0;
        }
        .badge-svc {
            background: #f5efe8; color: #5A4A42;
            padding: 4px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 500; white-space: nowrap;
        }
        .btn-del {
            background: #fff0f0; color: #e11d48;
            border: 1px solid #fecdd3; border-radius: 8px;
            padding: 6px 10px; font-size: 13px; font-weight: 500;
            text-decoration: none; transition: 0.2s; white-space: nowrap;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-del:hover { background: #e11d48; color: white; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }

        /* ALERT */
        .alert-del {
            background: #fff0f0; border: 1px solid #fecdd3; color: #e11d48;
            border-radius: 12px; padding: 12px 18px; margin-bottom: 20px;
            font-size: 14px; display: flex; align-items: center; gap: 8px;
        }
        .btn-new {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: white; border: none; border-radius: 30px;
            padding: 10px 22px; font-size: 13px; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-new:hover { color: white; opacity: 0.9; }
        .btn-edit {
            background: #eff6ff; color: #2563eb;
            border: 1px solid #bfdbfe; border-radius: 8px;
            padding: 6px 10px; font-size: 13px; font-weight: 500;
            text-decoration: none; transition: 0.2s; white-space: nowrap;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-edit:hover { background: #2563eb; color: white; }
        /* MODAL */
        .modal-overlay {
            display:none; position:fixed; inset:0; z-index:9000;
            background:rgba(0,0,0,0.55); align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box {
            background:#fff; border-radius:20px; width:95%; max-width:480px;
            max-height:90vh; overflow-y:auto;
            box-shadow:0 24px 60px rgba(0,0,0,0.25); animation:ppIn .22s ease;
        }
        .modal-header {
            padding:20px 24px 16px; border-bottom:1px solid #f0ebe3;
            display:flex; align-items:center; justify-content:space-between;
        }
        .modal-header h6 { font-size:16px; font-weight:700; color:var(--text-dark); margin:0; }
        .modal-close {
            background:none; border:none; font-size:20px; color:var(--text-light);
            cursor:pointer; line-height:1; padding:0;
        }
        .modal-body { padding:20px 24px; }
        .modal-footer { padding:14px 24px 20px; display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #f0ebe3; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:var(--text-mid); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.4px; }
        .form-group input, .form-group textarea, .form-group select {
            width:100%; border:1.5px solid #e8e0d8; border-radius:10px;
            padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
            outline:none; transition:border 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color:var(--gold); }
        .form-group textarea { resize:vertical; min-height:70px; }
        .btn-save {
            background:linear-gradient(135deg,var(--gold),var(--gold-dark));
            color:white; border:none; border-radius:30px;
            padding:10px 24px; font-size:13px; font-weight:600;
            cursor:pointer; font-family:'Poppins',sans-serif;
        }
        .btn-save:hover { opacity:0.9; }
        .btn-cancel {
            background:#f5f5f5; color:var(--text-mid); border:none; border-radius:30px;
            padding:10px 20px; font-size:13px; font-weight:500;
            cursor:pointer; font-family:'Poppins',sans-serif;
        }
    </style>
</head>
<body>

<!-- MOBILE TOPBAR -->
<div class="mobile-topbar">
    <div class="brand"><i class="fas fa-spa me-2"></i>NISWÀ BEAUTY</div>
    <button class="mobile-hamburger" onclick="openDrawer()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</div>

<!-- MOBILE DRAWER OVERLAY -->
<div class="mobile-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

<!-- MOBILE DRAWER -->
<div class="mobile-drawer" id="mobileDrawer">
    <div class="sidebar-brand">
        <i class="fas fa-spa me-2"></i>NISWÀ BEAUTY
        <small>ADMIN PANEL</small>
    </div>
    <div class="sidebar-section">Menu</div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="#orders" onclick="closeDrawer();setTimeout(()=>document.getElementById('orders').scrollIntoView({behavior:'smooth'}),300);return false;"><i class="fas fa-shopping-bag"></i> Data Pembelian</a></li>
        <li><a href="booking.php" target="_blank"><i class="fas fa-calendar-plus"></i> Form Booking</a></li>
    </ul>
    <div class="sidebar-section">Akun</div>
    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php?logout=1" onclick="return confirm('Yakin ingin logout?')" style="color:#e11d48;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
    <div class="sidebar-section">CMS</div>
    <ul class="sidebar-nav">
        <li><a href="cms.php" style="color:var(--gold);"><i class="fas fa-pencil-alt"></i> Panel CMS</a></li>
        <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Website</a></li>
    </ul>
    <div class="mobile-drawer-user">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>
</div>

<!-- SIDEBAR (desktop) -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-spa me-2"></i>NISWÀ BEAUTY
        <small>ADMIN PANEL</small>
    </div>
    <div class="sidebar-section">Menu</div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="#orders" onclick="document.getElementById('orders').scrollIntoView({behavior:'smooth'});return false;"><i class="fas fa-shopping-bag"></i> Data Pembelian</a></li>
        <li><a href="booking.php" target="_blank"><i class="fas fa-calendar-plus"></i> Form Booking</a></li>
    </ul>
    <div class="sidebar-section">Akun</div>
    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php?logout=1"
               onclick="return confirm('Yakin ingin logout?')"
               style="color:#e11d48;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
    
        <div class="sidebar-section">CMS</div>
        <ul class="sidebar-nav">
            <li><a href="cms.php" style="color:var(--gold);"><i class="fas fa-pencil-alt"></i> Panel CMS</a></li>
            <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Website</a></li>
        </ul>
        <div class="sidebar-user">
        <div class="av"><?= strtoupper(substr($_SESSION['user'], 0, 1)) ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($_SESSION['user']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h2><i class="fas fa-th-large me-2" style="color:var(--gold);"></i>Dashboard</h2>
            <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong> — <?= date('d F Y') ?></p>
        </div>

    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert-del"><i class="fas fa-trash-alt"></i> Data booking berhasil dihapus.</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'booking_updated'): ?>
    <div class="alert-del" style="background:#f0fdf4;border-color:#bbf7d0;color:#059669;"><i class="fas fa-check-circle"></i> Data booking berhasil diupdate.</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'order_updated'): ?>
    <div class="alert-del" style="background:#f0fdf4;border-color:#bbf7d0;color:#059669;"><i class="fas fa-check-circle"></i> Data pesanan berhasil diupdate.</div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="row g-3">
        <?php
        $cards = [
            ['icon'=>'fa-calendar-check', 'num'=>$total,       'lbl'=>'Total Booking',    'color'=>'#5A4A42'],
            ['icon'=>'fa-calendar-day',   'num'=>$today,       'lbl'=>'Hari Ini',         'color'=>'#b8860b'],
            ['icon'=>'fa-calendar-week',  'num'=>$week,        'lbl'=>'Minggu Ini',       'color'=>'#7c3aed'],
            ['icon'=>'fa-bell',           'num'=>$newBookings, 'lbl'=>'24 Jam Terakhir',  'color'=>'#0369a1'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-6 col-lg-2">
            <div class="stat-card">
                <div class="big-icon" style="color:<?= $c['color'] ?>;"><i class="fas <?= $c['icon'] ?>"></i></div>
                <div class="num" style="color:<?= $c['color'] ?>;"><?= $c['num'] ?></div>
                <div class="lbl"><?= $c['lbl'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <div class="table-top">
            <h5><i class="fas fa-list me-2" style="color:var(--gold);"></i>Data Semua Booking</h5>
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-box" id="searchInput" placeholder="Cari nama, layanan...">
            </div>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="desktop-table" style="overflow-x:auto;">
        <table id="bookingTable">
            <thead>
                <tr>
                    <th>#</th><th>Nama</th><th>No. HP</th><th>Email</th>
                    <th>Layanan</th><th>Tanggal</th><th>Jam</th><th>Dibuat</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; mysqli_data_seek($result,0); while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><strong><?= $no++ ?></strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="avatar-cell"><?= strtoupper(substr($row['name'],0,1)) ?></div>
                        <span style="font-weight:600;"><?= htmlspecialchars($row['name']) ?></span>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td style="color:var(--text-mid);font-size:12px;"><?= htmlspecialchars($row['email']) ?></td>
                <td><span class="badge-svc"><?= htmlspecialchars($row['service']) ?></span></td>
                <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                <td><?= date('H:i', strtotime($row['time'])) ?> WIB</td>
                <td style="font-size:12px;color:var(--text-light);"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="#" onclick="openEditBooking(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['name'])) ?>','<?= addslashes(htmlspecialchars($row['phone'])) ?>','<?= addslashes(htmlspecialchars($row['email'])) ?>','<?= addslashes(htmlspecialchars($row['service'])) ?>','<?= $row['date'] ?>','<?= substr($row['time'],0,5) ?>');return false;"
                       class="btn-edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <a href="dashboard.php?delete=<?= $row['id'] ?>"
                       onclick="return confirm('Hapus booking ini?')"
                       class="btn-del">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>

        <!-- MOBILE CARD VIEW: BOOKING -->
        <div class="mobile-cards">
        <?php $no=1; mysqli_data_seek($result,0); while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="m-card">
            <div class="m-card-header">
                <div class="avatar-cell"><?= strtoupper(substr($row['name'],0,1)) ?></div>
                <div>
                    <div class="m-name"><?= htmlspecialchars($row['name']) ?></div>
                    <span class="badge-svc" style="font-size:11px;"><?= htmlspecialchars($row['service']) ?></span>
                </div>
                <span class="num-badge ms-auto">#<?= $no++ ?></span>
            </div>
            <div class="m-card-rows">
                <div class="m-card-row"><span class="lbl">No. HP</span><span class="val"><?= htmlspecialchars($row['phone']) ?></span></div>
                <div class="m-card-row"><span class="lbl">Email</span><span class="val" style="font-size:12px;color:var(--text-mid);"><?= htmlspecialchars($row['email']) ?></span></div>
                <div class="m-card-row"><span class="lbl">Tanggal</span><span class="val"><?= date('d M Y', strtotime($row['date'])) ?></span></div>
                <div class="m-card-row"><span class="lbl">Jam</span><span class="val"><?= date('H:i', strtotime($row['time'])) ?> WIB</span></div>
                <div class="m-card-row"><span class="lbl">Dibuat</span><span class="val" style="font-size:12px;color:var(--text-light);"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></span></div>
            </div>
            <div class="m-card-footer">
                <a href="#" onclick="openEditBooking(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['name'])) ?>','<?= addslashes(htmlspecialchars($row['phone'])) ?>','<?= addslashes(htmlspecialchars($row['email'])) ?>','<?= addslashes(htmlspecialchars($row['service'])) ?>','<?= $row['date'] ?>','<?= substr($row['time'],0,5) ?>');return false;" class="btn-edit">
                    <i class="fas fa-pen"></i>
                </a>
                <a href="dashboard.php?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus booking ini?')" class="btn-del">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>Belum ada data booking.</p>
        </div>
        <?php endif; ?>
    </div>


    <!-- ORDERS TABLE -->
    <div class="table-wrap" id="orders" style="margin-top:36px;">
        <div class="table-top">
            <h5><i class="fas fa-shopping-bag me-2" style="color:var(--gold);"></i>Data Pembelian Produk</h5>
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-box" id="searchOrders" placeholder="Cari nama, produk...">
            </div>
        </div>

        <?php if ($ordersResult && mysqli_num_rows($ordersResult) > 0): ?>
        <div class="desktop-table" style="overflow-x:auto;">
        <table id="ordersTable">
            <thead>
                <tr>
                    <th>#</th>
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
            <?php $no=1; while ($row = mysqli_fetch_assoc($ordersResult)): ?>
            <tr>
                <td><strong><?= $no++ ?></strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="avatar-cell"><?= strtoupper(substr($row['nama'],0,1)) ?></div>
                        <span style="font-weight:600;"><?= htmlspecialchars($row['nama']) ?></span>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['whatsapp']) ?></td>
                <?php $pname=$row['product_name']; $pimgsJson=getProductImagesJson($row,$productImageMap,$productImageByName,$_fallbackImages); $pimgFirst=($pimgsJson ? json_decode($pimgsJson,true)[0] : null); ?>
                <td>
                    <?php if($pimgsJson): ?>
                    <span class="badge-svc prod-preview-btn" style="cursor:pointer;border-bottom:1px dashed #8B6F5E;"
                          data-name="<?= htmlspecialchars($pname, ENT_QUOTES) ?>"
                          data-imgs="<?= htmlspecialchars($pimgsJson, ENT_QUOTES) ?>"
                          data-price="<?= htmlspecialchars($row['product_price'], ENT_QUOTES) ?>">
                        <i class="fas fa-image me-1" style="font-size:10px;opacity:0.7;"></i><?= htmlspecialchars($pname) ?>
                    </span>
                    <?php else: ?>
                    <span class="badge-svc"><?= htmlspecialchars($pname) ?></span>
                    <?php endif; ?>
                </td>
                <td style="color:#8B6F5E;font-weight:600;"><?= htmlspecialchars($row['product_price']) ?></td>
                <td style="text-align:center;font-weight:600;"><?= (int)$row['qty'] ?></td>
                <td style="color:#059669;font-weight:700;"><?= htmlspecialchars($row['total']) ?></td>
                <td style="font-size:12px;color:var(--text-mid);max-width:140px;"><?= htmlspecialchars($row['alamat']) ?></td>
                <td style="font-size:12px;color:var(--text-light);"><?= htmlspecialchars($row['catatan'] ?: '-') ?></td>
                <td style="font-size:12px;color:var(--text-light);white-space:nowrap;"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="#" onclick="openEditOrder(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['nama'])) ?>','<?= addslashes(htmlspecialchars($row['whatsapp'])) ?>','<?= addslashes(htmlspecialchars($row['alamat'])) ?>','<?= addslashes(htmlspecialchars($row['product_name'])) ?>','<?= addslashes(htmlspecialchars($row['product_price'])) ?>',<?= (int)$row['qty'] ?>,'<?= addslashes(htmlspecialchars($row['total'])) ?>','<?= addslashes(htmlspecialchars($row['catatan'])) ?>','<?= addslashes($pimgFirst ?? '') ?>');return false;"
                       class="btn-edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <a href="dashboard.php?delete_order=<?= $row['id'] ?>"
                       onclick="return confirm('Hapus data pembelian ini?')"
                       class="btn-del">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>

        <!-- MOBILE CARD VIEW: ORDERS -->
        <div class="mobile-cards">
        <?php $no=1; mysqli_data_seek($ordersResult,0); while ($row = mysqli_fetch_assoc($ordersResult)): ?>
        <?php $pname=$row['product_name']; $pimgsJson=getProductImagesJson($row,$productImageMap,$productImageByName,$_fallbackImages); $pimgFirst=($pimgsJson ? json_decode($pimgsJson,true)[0] : null); ?>
        <div class="m-card">
            <div class="m-card-header">
                <div class="avatar-cell"><?= strtoupper(substr($row['nama'],0,1)) ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="m-name"><?= htmlspecialchars($row['nama']) ?></div>
                    <span class="badge-svc" style="font-size:11px;"><?= htmlspecialchars($pname) ?></span>
                </div>
                <?php if($pimgFirst): ?>
                <img src="<?= htmlspecialchars($pimgFirst) ?>" class="m-product-thumb prod-preview-btn"
                     data-name="<?= htmlspecialchars($pname, ENT_QUOTES) ?>"
                     data-imgs="<?= htmlspecialchars($pimgsJson, ENT_QUOTES) ?>"
                     data-price="<?= htmlspecialchars($row['product_price'], ENT_QUOTES) ?>"
                     alt="<?= htmlspecialchars($pname) ?>">
                <?php endif; ?>
                <span class="num-badge">#<?= $no++ ?></span>
            </div>
            <div class="m-card-rows">
                <div class="m-card-row"><span class="lbl">WhatsApp</span><span class="val"><?= htmlspecialchars($row['whatsapp']) ?></span></div>
                <div class="m-card-row"><span class="lbl">Harga</span><span class="val" style="color:#8B6F5E;font-weight:600;"><?= htmlspecialchars($row['product_price']) ?></span></div>
                <div class="m-card-row"><span class="lbl">Qty</span><span class="val" style="font-weight:600;"><?= (int)$row['qty'] ?></span></div>
                <div class="m-card-row"><span class="lbl">Total</span><span class="val" style="color:#059669;font-weight:700;"><?= htmlspecialchars($row['total']) ?></span></div>
                <div class="m-card-row"><span class="lbl">Alamat</span><span class="val" style="font-size:12px;"><?= htmlspecialchars($row['alamat']) ?></span></div>
                <?php if($row['catatan']): ?>
                <div class="m-card-row"><span class="lbl">Catatan</span><span class="val" style="font-size:12px;color:var(--text-light);"><?= htmlspecialchars($row['catatan']) ?></span></div>
                <?php endif; ?>
                <div class="m-card-row"><span class="lbl">Tanggal</span><span class="val" style="font-size:12px;color:var(--text-light);"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></span></div>
            </div>
            <div class="m-card-footer">
                <a href="#" onclick="openEditOrder(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['nama'])) ?>','<?= addslashes(htmlspecialchars($row['whatsapp'])) ?>','<?= addslashes(htmlspecialchars($row['alamat'])) ?>','<?= addslashes(htmlspecialchars($row['product_name'])) ?>','<?= addslashes(htmlspecialchars($row['product_price'])) ?>',<?= (int)$row['qty'] ?>,'<?= addslashes(htmlspecialchars($row['total'])) ?>','<?= addslashes(htmlspecialchars($row['catatan'])) ?>','<?= addslashes($pimgFirst ?? '') ?>');return false;" class="btn-edit">
                    <i class="fas fa-pen"></i>
                </a>
                <a href="dashboard.php?delete_order=<?= $row['id'] ?>" onclick="return confirm('Hapus data pembelian ini?')" class="btn-del">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <p>Belum ada data pembelian produk.</p>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- PRODUCT PREVIEW MODAL -->
<div id="adminProdModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;" onclick="if(event.target===this)closeProdPreview()">
    <div style="background:#fff;border-radius:22px;width:92%;max-width:380px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.3);animation:ppIn .22s ease;">
        <!-- Slideshow wrapper -->
        <div style="position:relative;background:#111;overflow:hidden;" id="apmSlideWrap">
            <div id="apmSlideTrack" style="display:flex;transition:transform .35s cubic-bezier(0.4,0,0.2,1);"></div>
            <!-- Prev/Next -->
            <button id="apmPrev" onclick="apmSlide(-1)" style="display:none;position:absolute;left:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.45);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:15px;cursor:pointer;z-index:2;line-height:1;">&#8249;</button>
            <button id="apmNext" onclick="apmSlide(1)"  style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.45);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:15px;cursor:pointer;z-index:2;line-height:1;">&#8250;</button>
            <!-- Dots -->
            <div id="apmDots" style="position:absolute;bottom:10px;left:0;right:0;display:flex;justify-content:center;gap:5px;"></div>
            <!-- Counter badge -->
            <div id="apmCounter" style="display:none;position:absolute;top:10px;left:10px;background:rgba(0,0,0,0.5);color:#fff;font-size:11px;font-family:'Poppins',sans-serif;font-weight:600;padding:3px 9px;border-radius:20px;"></div>
            <button onclick="closeProdPreview()" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.5);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:16px;cursor:pointer;line-height:1;z-index:3;">&times;</button>
        </div>
        <div style="padding:16px 20px 18px;">
            <div id="apmName" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:15px;color:#2d1f17;margin-bottom:4px;"></div>
            <div id="apmPrice" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:17px;color:#8B6F5E;"></div>
        </div>
    </div>
</div>
<style>
@keyframes ppIn { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
.apm-dot { width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.45);cursor:pointer;transition:background .2s; }
.apm-dot.active { background:#fff; }
</style>

<script>
function openDrawer() {
    document.getElementById('mobileDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeDrawer() {
    document.getElementById('mobileDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
</script>

<script>
var _apmImgs=[], _apmIdx=0;
function apmRender(){
    var track=document.getElementById('apmSlideTrack');
    var wrap=document.getElementById('apmSlideWrap');
    var dotsEl=document.getElementById('apmDots');
    var counter=document.getElementById('apmCounter');
    var prev=document.getElementById('apmPrev');
    var next=document.getElementById('apmNext');
    var n=_apmImgs.length;
    track.innerHTML='';
    _apmImgs.forEach(function(src){
        var slide=document.createElement('div');
        slide.style.cssText='flex:0 0 100%;width:100%;height:260px;';
        var img=document.createElement('img');
        img.src=src; img.alt='';
        img.style.cssText='width:100%;height:260px;object-fit:cover;display:block;';
        slide.appendChild(img);
        track.appendChild(slide);
    });
    if(n>1){ prev.style.display='block'; next.style.display='block'; counter.style.display='block'; }
    else    { prev.style.display='none';  next.style.display='none';  counter.style.display='none'; }
    dotsEl.innerHTML='';
    if(n>1){
        _apmImgs.forEach(function(_,i){
            var d=document.createElement('span');
            d.className='apm-dot'+(i===0?' active':'');
            d.onclick=function(){apmGoTo(i);};
            dotsEl.appendChild(d);
        });
    }
    apmGoTo(0);
}
function apmGoTo(idx){
    var n=_apmImgs.length;
    _apmIdx=(idx+n)%n;
    document.getElementById('apmSlideTrack').style.transform='translateX(-'+(_apmIdx*100)+'%)';
    document.querySelectorAll('.apm-dot').forEach(function(d,i){d.classList.toggle('active',i===_apmIdx);});
    var counter=document.getElementById('apmCounter');
    if(counter)counter.textContent=(_apmIdx+1)+' / '+n;
}
function apmSlide(dir){ apmGoTo(_apmIdx+dir); }

function showProdPreview(name, imgsJson, price) {
    try { var parsed=JSON.parse(imgsJson); _apmImgs=Array.isArray(parsed)?parsed:[parsed]; }
    catch(e){ _apmImgs=imgsJson?[imgsJson]:[]; }
    _apmImgs=_apmImgs.filter(function(x){return x&&x!='';});
    if(_apmImgs.length===0) return;
    document.getElementById('apmName').textContent=name;
    document.getElementById('apmPrice').textContent=price;
    apmRender();
    document.getElementById('adminProdModal').style.display='flex';
}
function closeProdPreview() {
    document.getElementById('adminProdModal').style.display='none';
}

// Event delegation — handles both badge-svc and img thumbnail
document.addEventListener('click', function(e){
    var el=e.target.closest('.prod-preview-btn');
    if(el){
        e.preventDefault();
        showProdPreview(el.dataset.name, el.dataset.imgs, el.dataset.price);
    }
});
</script>

<script>
// Search filter booking
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#bookingTable tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});
// Search filter orders
const searchOrders = document.getElementById('searchOrders');
if (searchOrders) {
    searchOrders.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#ordersTable tbody tr').forEach(tr => {
            tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>

<!-- EDIT BOOKING MODAL -->
<div class="modal-overlay" id="editBookingModal">
  <div class="modal-box">
    <div class="modal-header">
      <h6><i class="fas fa-pen me-2" style="color:var(--gold);"></i>Edit Booking</h6>
      <button class="modal-close" onclick="closeEditBooking()">&times;</button>
    </div>
    <form method="POST" action="dashboard.php">
      <div class="modal-body">
        <input type="hidden" name="edit_booking_id" id="eb_id">
        <div class="row g-2">
          <div class="col-12">
            <div class="form-group"><label>Nama</label><input type="text" name="edit_name" id="eb_name" required></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>No. HP</label><input type="text" name="edit_phone" id="eb_phone"></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>Email</label><input type="email" name="edit_email" id="eb_email"></div>
          </div>
          <div class="col-12">
            <div class="form-group"><label>Layanan</label><input type="text" name="edit_service" id="eb_service"></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>Tanggal</label><input type="date" name="edit_date" id="eb_date"></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>Jam</label><input type="time" name="edit_time" id="eb_time"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEditBooking()">Batal</button>
        <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT ORDER MODAL -->
<div class="modal-overlay" id="editOrderModal">
  <div class="modal-box">
    <div class="modal-header">
      <h6><i class="fas fa-pen me-2" style="color:var(--gold);"></i>Edit Pesanan</h6>
      <button class="modal-close" onclick="closeEditOrder()">&times;</button>
    </div>
    <form method="POST" action="dashboard.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="edit_order_id" id="eo_id">
        <div class="row g-2">
          <div class="col-6">
            <div class="form-group"><label>Nama</label><input type="text" name="edit_nama" id="eo_nama" required></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>WhatsApp</label><input type="text" name="edit_whatsapp" id="eo_whatsapp"></div>
          </div>
          <div class="col-12">
            <div class="form-group"><label>Alamat</label><textarea name="edit_alamat" id="eo_alamat"></textarea></div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label>Foto Produk</label>
              <!-- Preview gambar saat ini / baru -->
              <div id="eo_product_preview" style="display:none;margin-bottom:8px;position:relative;">
                <img id="eo_product_img" src="" alt=""
                  style="width:100%;max-height:180px;object-fit:cover;border-radius:12px;border:1.5px solid #e8e0d8;display:block;">
                <span id="eo_img_badge" style="display:none;position:absolute;top:8px;left:8px;background:#059669;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:0.5px;">BARU</span>
              </div>
              <div id="eo_no_img" style="display:none;padding:12px;background:#faf7f2;border-radius:12px;border:1.5px dashed #e0d8ce;text-align:center;font-size:12px;color:var(--text-light);margin-bottom:8px;">
                <i class="fas fa-image" style="font-size:24px;display:block;margin-bottom:4px;opacity:0.4;"></i>
                Tidak ada foto untuk produk ini
              </div>
              <!-- Tombol upload -->
              <label for="eo_upload_input" style="
                  display:inline-flex;align-items:center;gap:6px;cursor:pointer;
                  background:#f5efe8;color:#8B6F5E;border:1.5px dashed #d4b896;
                  border-radius:10px;padding:8px 16px;font-size:12px;font-weight:600;
                  transition:0.2s;width:100%;justify-content:center;">
                <i class="fas fa-upload"></i> Ganti Foto Produk
              </label>
              <input type="file" id="eo_upload_input" name="edit_product_image"
                accept="image/jpeg,image/png,image/webp,image/gif"
                style="display:none;">
              <p id="eo_upload_hint" style="font-size:11px;color:var(--text-light);margin-top:5px;margin-bottom:0;text-align:center;">
                Format: JPG, PNG, WEBP · Maks 5MB · Kosongkan jika tidak ingin ganti foto
              </p>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group"><label>Nama Produk</label><input type="text" name="edit_product_name" id="eo_product_name"></div>
          </div>
          <div class="col-6">
            <div class="form-group"><label>Harga</label><input type="text" name="edit_product_price" id="eo_product_price"></div>
          </div>
          <div class="col-3">
            <div class="form-group"><label>Qty</label><input type="number" min="1" name="edit_qty" id="eo_qty"></div>
          </div>
          <div class="col-3">
            <div class="form-group"><label>Total</label><input type="text" name="edit_total" id="eo_total"></div>
          </div>
          <div class="col-12">
            <div class="form-group"><label>Catatan</label><textarea name="edit_catatan" id="eo_catatan"></textarea></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEditOrder()">Batal</button>
        <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i>Simpan</button>
      </div>
    </form>
  </div>
</div>


<script>
// Edit Booking
function openEditBooking(id, name, phone, email, service, date, time) {
    document.getElementById('eb_id').value = id;
    document.getElementById('eb_name').value = name;
    document.getElementById('eb_phone').value = phone;
    document.getElementById('eb_email').value = email;
    document.getElementById('eb_service').value = service;
    document.getElementById('eb_date').value = date;
    document.getElementById('eb_time').value = time;
    document.getElementById('editBookingModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditBooking() {
    document.getElementById('editBookingModal').classList.remove('open');
    document.body.style.overflow = '';
}

// Edit Order
function openEditOrder(id, nama, wa, alamat, pname, price, qty, total, catatan, pimg) {
    document.getElementById('eo_id').value = id;
    document.getElementById('eo_nama').value = nama;
    document.getElementById('eo_whatsapp').value = wa;
    document.getElementById('eo_alamat').value = alamat;
    document.getElementById('eo_product_name').value = pname;
    document.getElementById('eo_product_price').value = price;
    document.getElementById('eo_qty').value = qty;
    document.getElementById('eo_total').value = total;
    document.getElementById('eo_catatan').value = catatan;
    // Reset input file
    document.getElementById('eo_upload_input').value = '';
    document.getElementById('eo_img_badge').style.display = 'none';
    // Tampilkan gambar produk
    const preview = document.getElementById('eo_product_preview');
    const noImg   = document.getElementById('eo_no_img');
    const img     = document.getElementById('eo_product_img');
    if (pimg) {
        img.src = pimg;
        preview.style.display = 'block';
        noImg.style.display   = 'none';
    } else {
        preview.style.display = 'none';
        noImg.style.display   = 'block';
    }
    document.getElementById('editOrderModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

// Preview live saat pilih file upload
document.getElementById('eo_upload_input').addEventListener('change', function() {
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal 5MB.');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const img     = document.getElementById('eo_product_img');
        const preview = document.getElementById('eo_product_preview');
        const noImg   = document.getElementById('eo_no_img');
        const badge   = document.getElementById('eo_img_badge');
        img.src = e.target.result;
        preview.style.display = 'block';
        noImg.style.display   = 'none';
        badge.style.display   = 'inline-block';
    };
    reader.readAsDataURL(file);
});
function closeEditOrder() {
    document.getElementById('editOrderModal').classList.remove('open');
    document.body.style.overflow = '';
}

// Close modal on overlay click
['editBookingModal','editOrderModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});
</script>
</body>
</html>