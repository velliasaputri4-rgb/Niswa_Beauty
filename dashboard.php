<?php
session_start();

// Proteksi: wajib login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Koneksi langsung (tanpa require)
$conn = mysqli_connect("localhost", "root", "", "salon_db");
mysqli_set_charset($conn, 'utf8mb4');

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

// Fetch stats
$total       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings"))['n'];
$today       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings WHERE date = CURDATE()"))['n'];
$week        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings WHERE YEARWEEK(date,1)=YEARWEEK(CURDATE(),1)"))['n'];
$newBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"))['n'];

// Fetch all bookings
$result = mysqli_query($conn, "SELECT * FROM bookings ORDER BY created_at DESC");

// Fetch orders (buat tabel jika belum ada)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
$ordersResult = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
$totalOrders  = ($ordersResult) ? mysqli_num_rows($ordersResult) : 0;
if ($ordersResult) mysqli_data_seek($ordersResult, 0);

// Mapping product_name → image path
$productImages = [
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

// Handle delete order
if (isset($_GET['delete_order']) && is_numeric($_GET['delete_order'])) {
    $oid = (int)$_GET['delete_order'];
    mysqli_query($conn, "DELETE FROM orders WHERE id = $oid");
    header('Location: dashboard.php?msg=order_deleted');
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
        }
        .stat-card .big-icon {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            font-size: 48px; opacity: 0.07;
        }
        .stat-card .num { font-size: 32px; font-weight: 700; line-height: 1; }
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
        .search-box {
            border: 1.5px solid #e8e0d8; border-radius: 30px;
            padding: 8px 16px; font-size: 13px; outline: none;
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
            padding: 6px 12px; font-size: 12px; font-weight: 500;
            text-decoration: none; transition: 0.2s; white-space: nowrap;
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
        <a href="booking.php" class="btn-new"><i class="fas fa-plus"></i> Booking Baru</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert-del"><i class="fas fa-trash-alt"></i> Data booking berhasil dihapus.</div>
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
            <input type="text" class="search-box" id="searchInput" placeholder="🔍 Cari nama, layanan...">
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
                    <a href="dashboard.php?delete=<?= $row['id'] ?>"
                       onclick="return confirm('Hapus booking ini?')"
                       class="btn-del">
                        <i class="fas fa-trash-alt me-1"></i>Hapus
                    </a>
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
                <a href="dashboard.php?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus booking ini?')" class="btn-del">
                    <i class="fas fa-trash-alt me-1"></i>Hapus
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
            <input type="text" class="search-box" id="searchOrders" placeholder="🔍 Cari nama, produk...">
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
                <?php $pname=$row['product_name']; $pimg=$productImages[$pname]??null; ?>
                <td>
                    <?php if($pimg): ?>
                    <span class="badge-svc" style="cursor:pointer;border-bottom:1px dashed #8B6F5E;" onclick="showProdPreview('<?= addslashes(htmlspecialchars($pname)) ?>','<?= addslashes($pimg) ?>','<?= addslashes(htmlspecialchars($row['product_price'])) ?>')">
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
                    <a href="dashboard.php?delete_order=<?= $row['id'] ?>"
                       onclick="return confirm('Hapus data pembelian ini?')"
                       class="btn-del">
                        <i class="fas fa-trash-alt me-1"></i>Hapus
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>

        <!-- MOBILE CARD VIEW: ORDERS -->
        <div class="mobile-cards">
        <?php $no=1; mysqli_data_seek($ordersResult,0); while ($row = mysqli_fetch_assoc($ordersResult)): ?>
        <?php $pname=$row['product_name']; $pimg=$productImages[$pname]??null; ?>
        <div class="m-card">
            <div class="m-card-header">
                <div class="avatar-cell"><?= strtoupper(substr($row['nama'],0,1)) ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="m-name"><?= htmlspecialchars($row['nama']) ?></div>
                    <span class="badge-svc" style="font-size:11px;"><?= htmlspecialchars($pname) ?></span>
                </div>
                <?php if($pimg): ?>
                <img src="<?= htmlspecialchars($pimg) ?>" class="m-product-thumb"
                     onclick="showProdPreview('<?= addslashes(htmlspecialchars($pname)) ?>','<?= addslashes($pimg) ?>','<?= addslashes(htmlspecialchars($row['product_price'])) ?>')"
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
                <a href="dashboard.php?delete_order=<?= $row['id'] ?>" onclick="return confirm('Hapus data pembelian ini?')" class="btn-del">
                    <i class="fas fa-trash-alt me-1"></i>Hapus
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
    <div style="background:#fff;border-radius:22px;width:92%;max-width:360px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.3);animation:ppIn .22s ease;">
        <div style="position:relative;">
            <img id="apmImg" src="" alt="" style="width:100%;height:260px;object-fit:cover;display:block;">
            <button onclick="closeProdPreview()" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.5);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:16px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="padding:18px 20px 20px;">
            <div id="apmName" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:15px;color:#2d1f17;margin-bottom:4px;"></div>
            <div id="apmPrice" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:17px;color:#8B6F5E;"></div>
        </div>
    </div>
</div>
<style>
@keyframes ppIn { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
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
function showProdPreview(name, img, price) {
    document.getElementById('apmImg').src = img;
    document.getElementById('apmName').textContent = name;
    document.getElementById('apmPrice').textContent = price;
    document.getElementById('adminProdModal').style.display = 'flex';
}
function closeProdPreview() {
    document.getElementById('adminProdModal').style.display = 'none';
}
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
    })
}
</script>
</body>
</html>