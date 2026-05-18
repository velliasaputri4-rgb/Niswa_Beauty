<?php
// booking.php — NISWÀ BEAUTY Premium Booking Page
// ✅ UPDATED: Notifikasi WhatsApp otomatis ke customer & admin
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = mysqli_connect("localhost", "root", "", "salon_db");
mysqli_set_charset($conn, 'utf8mb4');

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ── Load notify.php helper ─────────────────────────────────────
if (file_exists(__DIR__ . '/notify.php')) {
    require_once __DIR__ . '/notify.php';
}

// ── AUTO-FIX: tambah kolom yang mungkin belum ada di tabel bookings ──
$fixColumns = [
    "user_id"        => "ALTER TABLE bookings ADD COLUMN user_id INT DEFAULT NULL",
    "jumlah_orang"   => "ALTER TABLE bookings ADD COLUMN jumlah_orang INT DEFAULT 1",
    "catatan"        => "ALTER TABLE bookings ADD COLUMN catatan TEXT",
    "jenis_layanan"  => "ALTER TABLE bookings ADD COLUMN jenis_layanan VARCHAR(20) DEFAULT 'datang'",
    "alamat_hs"      => "ALTER TABLE bookings ADD COLUMN alamat_hs TEXT",
];
foreach ($fixColumns as $col => $sql) {
    $cek = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE '$col'");
    if ($cek && mysqli_num_rows($cek) === 0) {
        mysqli_query($conn, $sql);
    }
}

$message    = '';
$error      = '';
$booking_id = null;

// Data untuk WA — akan diisi setelah booking sukses
$wa_customer_url = '';
$wa_admin_url    = '';
$available_slots_js = '[]';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = $_SESSION['user_id'] ?? null;
    $nama         = trim($_POST['nama']          ?? '');
    $whatsapp     = trim($_POST['whatsapp']      ?? '');
    $email        = trim($_POST['email']         ?? '');
    $layanan      = trim($_POST['layanan']       ?? '');
    $tanggal      = trim($_POST['tanggal']       ?? '');
    $jam          = trim($_POST['jam']           ?? '');
    $jumlah_orang = (int)($_POST['jumlah_orang'] ?? 1);
    $catatan      = trim($_POST['catatan']       ?? '');
    $jenis_layanan = trim($_POST['jenis_layanan']  ?? 'datang');
    $alamat_hs     = trim($_POST['alamat_hs']      ?? '');
    if (!in_array($jenis_layanan, ['datang', 'home_service'])) $jenis_layanan = 'datang';

    $errors = [];
    if (empty($nama))     $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($whatsapp)) $errors[] = 'Nomor WhatsApp wajib diisi.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (empty($layanan))  $errors[] = 'Layanan wajib dipilih.';
    if (empty($tanggal))  $errors[] = 'Tanggal wajib dipilih.';
    if (empty($jam))      $errors[] = 'Jam wajib dipilih.';
    if ($jenis_layanan === 'home_service' && empty($alamat_hs)) $errors[] = 'Alamat lengkap wajib diisi untuk Home Service.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO bookings (user_id, name, phone, email, service, date, time, jumlah_orang, catatan, jenis_layanan, alamat_hs, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        mysqli_stmt_bind_param($stmt, "issssssisss",
            $user_id, $nama, $whatsapp, $email, $layanan,
            $tanggal, $jam, $jumlah_orang, $catatan, $jenis_layanan, $alamat_hs
        );

        if (mysqli_stmt_execute($stmt)) {
            $booking_id = mysqli_stmt_insert_id($stmt);
            $message    = "Booking berhasil! ID: #" . $booking_id;

            // ── Ambil time_slots dari CMS untuk cek ketersediaan ──
            $raw_slots = '';
            $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='time_slots' LIMIT 1");
            if ($r && $row = mysqli_fetch_assoc($r)) $raw_slots = $row['value'];
            if (empty($raw_slots)) $raw_slots = "09:00\n10:00\n11:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00\n20:00";
            $all_slots = array_values(array_filter(array_map('trim', explode("\n", $raw_slots))));

            // ── Cek jam tersedia di tanggal yang sama ──
            $available_slots = [];
            if (function_exists('getAvailableSlots')) {
                $available_slots = getAvailableSlots($conn, $tanggal, $jam, $all_slots);
            }

            $booking_data = [
                'booking_id'   => $booking_id,
                'nama'         => $nama,
                'whatsapp'     => $whatsapp,
                'email'        => $email,
                'layanan'      => $layanan,
                'tanggal'      => $tanggal,
                'jam'          => $jam,
                'jumlah_orang' => $jumlah_orang,
                'catatan'      => $catatan,
                'jenis_layanan' => $jenis_layanan,
                'alamat_hs'    => $alamat_hs,
            ];

            // ── Notifikasi ke customer ──
            if (function_exists('notifyCustomerBooking')) {
                notifyCustomerBooking($booking_data, $available_slots);
            }

            // ── Notifikasi ke admin ──
            if (function_exists('notifyAdminBooking')) {
                notifyAdminBooking($booking_data, $conn);
            }

            // ── Siapkan URL WA langsung untuk tombol ──
            // Customer
            $cust_num = preg_replace('/[^0-9]/', '', $whatsapp);
            if (substr($cust_num, 0, 1) === '0') $cust_num = '62' . substr($cust_num, 1);

            $bulan_id = ['','Januari','Februari','Maret','April','Mei','Juni',
                         'Juli','Agustus','September','Oktober','November','Desember'];
            $ts = strtotime($tanggal);
            $tgl_fmt = $ts ? date('d', $ts).' '.$bulan_id[(int)date('m',$ts)].' '.date('Y',$ts) : $tanggal;

            $pesan_cust  = "💅 *KONFIRMASI BOOKING - NISWÀ BEAUTY*\n\n";
            $pesan_cust .= "Halo *{$nama}* 👋\n";
            $pesan_cust .= "Booking Anda telah berhasil terdaftar! 🌸\n\n";
            $pesan_cust .= "━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_cust .= "📋 *DETAIL BOOKING #{$booking_id}*\n";
            $pesan_cust .= "━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_cust .= "💆 *Layanan:* {$layanan}\n";
            $pesan_cust .= "📅 *Tanggal:* {$tgl_fmt}\n";
            $pesan_cust .= "⏰ *Jam:* {$jam} WIB\n";
            $label_jenis = ($jenis_layanan === 'home_service') ? '🏠 Home Service' : '🏪 Datang ke Tempat';
            $pesan_cust .= "📍 *Jenis Layanan:* {$label_jenis}\n";
            if ($jenis_layanan === 'home_service' && !empty($alamat_hs)) $pesan_cust .= "🗺️ *Alamat:* {$alamat_hs}\n";
            $pesan_cust .= "👥 *Jumlah Orang:* {$jumlah_orang} orang\n";
            if (!empty($catatan)) $pesan_cust .= "📝 *Catatan:* {$catatan}\n";
            $pesan_cust .= "━━━━━━━━━━━━━━━━━━━━\n\n";

            if (!empty($available_slots)) {
                $pesan_cust .= "🕐 *Jam Lain Tersedia ({$tgl_fmt}):*\n";
                foreach ($available_slots as $sl) $pesan_cust .= "   • {$sl} WIB\n";
                $pesan_cust .= "\n";
            }

            $pesan_cust .= "⚠️ *Status:* Menunggu konfirmasi tim kami\n\n";
            $pesan_cust .= "Tim kami akan menghubungi Anda dalam *< 1 jam*. Jika jam yang dipilih tidak tersedia, kami akan menawarkan jam alternatif. 😊\n\n";
            $pesan_cust .= "📍 Jl. Watulumpang, Bangsri, Jepara\n\n";
            $pesan_cust .= "✨ *Niswà Beauty* — Premium Beauty Experience";

            $wa_customer_url = 'https://wa.me/' . $cust_num . '?text=' . rawurlencode($pesan_cust);

            // Admin
            $admin_num = preg_replace('/[^0-9]/', '', '');
            $r2 = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='kontak' AND `key`='whatsapp' LIMIT 1");
            if ($r2 && $rw2 = mysqli_fetch_assoc($r2)) $admin_num = preg_replace('/[^0-9]/', '', $rw2['value']);
            if (empty($admin_num)) $admin_num = '6289714408805';

            $pesan_admin  = "📅 *BOOKING BARU - NISWÀ BEAUTY*\n\n";
            $pesan_admin .= "━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_admin .= "📋 *Booking ID:* #{$booking_id}\n";
            $pesan_admin .= "━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_admin .= "👤 *Nama:* {$nama}\n";
            $pesan_admin .= "📱 *WA:* {$whatsapp}\n";
            $pesan_admin .= "📧 *Email:* {$email}\n";
            $pesan_admin .= "💆 *Layanan:* {$layanan}\n";
            $pesan_admin .= "📅 *Tanggal:* {$tgl_fmt}\n";
            $pesan_admin .= "⏰ *Jam:* {$jam} WIB\n";
            $pesan_admin .= "📍 *Jenis:* " . ($jenis_layanan === 'home_service' ? '🏠 Home Service' : '🏪 Datang ke Tempat') . "\n";
            if ($jenis_layanan === 'home_service' && !empty($alamat_hs)) $pesan_admin .= "🗺️ *Alamat HS:* {$alamat_hs}\n";
            $pesan_admin .= "👥 *Jumlah:* {$jumlah_orang} orang\n";
            if (!empty($catatan)) $pesan_admin .= "📝 *Catatan:* {$catatan}\n";
            $pesan_admin .= "━━━━━━━━━━━━━━━━━━━━\n";
            $pesan_admin .= "⏰ " . date('d/m/Y H:i') . " WIB\n\n";
            $pesan_admin .= "Segera konfirmasi ke customer! 💬";

            $wa_admin_url = 'https://wa.me/' . $admin_num . '?text=' . rawurlencode($pesan_admin);

            // Kirim data slot ke JS
            $available_slots_js = json_encode(array_values($available_slots));

        } else {
            $error = "Gagal menyimpan booking: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = implode('<br>', $errors);
    }
}

// ── AJAX: cek jam tersedia untuk tanggal tertentu ──────────────
if (isset($_GET['check_slots']) && !empty($_GET['tanggal'])) {
    header('Content-Type: application/json');
    $tgl = trim($_GET['tanggal']);

    // Ambil time_slots dari CMS
    $raw_slots = '';
    $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='time_slots' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) $raw_slots = $row['value'];
    if (empty($raw_slots)) $raw_slots = "09:00\n10:00\n11:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00\n20:00";
    $all_slots = array_values(array_filter(array_map('trim', explode("\n", $raw_slots))));

    // Ambil jam yang sudah dipesan
    $tgl_esc = mysqli_real_escape_string($conn, $tgl);
    $res = mysqli_query($conn,
        "SELECT time, COUNT(*) as cnt FROM bookings
         WHERE date = '{$tgl_esc}'
         GROUP BY time"
    );
    $booked = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $booked[$row['time']] = (int)$row['cnt'];
        }
    }

    $slot_status = [];
    foreach ($all_slots as $sl) {
        $slot_status[] = [
            'time'    => $sl,
            'booked'  => isset($booked[$sl]) ? $booked[$sl] : 0,
            'available' => !isset($booked[$sl]),
        ];
    }

    echo json_encode(['slots' => $slot_status, 'date' => $tgl]);
    exit;
}

// Pre-fill dari POST atau session
$prefillNama  = $_POST['nama']  ?? $_SESSION['user']       ?? '';
$prefillEmail = $_POST['email'] ?? $_SESSION['user_email'] ?? '';
$isLoggedIn   = isset($_SESSION['user']) && ($_SESSION['user_role'] ?? '') !== 'admin';

// ── Load data dari CMS ──────────────────────────────────────────
function bkGet($conn, $key, $default = '') {
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
$bk = [
    'page_title'        => bkGet($conn, 'page_title',        'Form Booking'),
    'page_subtitle'     => bkGet($conn, 'page_subtitle',     'Isi form di bawah untuk memesan jadwal kecantikan Anda'),
    'form_title'        => bkGet($conn, 'form_title',        'Form Booking'),
    'services_list'     => bkGet($conn, 'services_list',     ''),
    'time_slots'        => bkGet($conn, 'time_slots',        "09:00\n10:00\n11:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00\n20:00"),
    'sidebar_address'   => bkGet($conn, 'sidebar_address',   'Jl. Lkr. Bangsri, Jepara, Jawa Tengah'),
    'sidebar_whatsapp'  => bkGet($conn, 'sidebar_whatsapp',  '+62 882 006 903 068'),
    'sidebar_hours'     => bkGet($conn, 'sidebar_hours',     'Senin - Minggu, 09:00 - 20:00'),
    'perk_1'            => bkGet($conn, 'perk_1',            'Fast Response <1 jam'),
    'perk_2'            => bkGet($conn, 'perk_2',            'Beautician Profesional'),
    'perk_3'            => bkGet($conn, 'perk_3',            'Tempat Nyaman Mewah'),
    'perk_4'            => bkGet($conn, 'perk_4',            'Produk Premium Import'),
    'max_jumlah_orang'  => bkGet($conn, 'max_jumlah_orang',  '10'),
    'show_catatan'      => bkGet($conn, 'show_catatan',      '1'),
    'show_jumlah_orang' => bkGet($conn, 'show_jumlah_orang', '1'),
];

function parseServicesGrouped($raw) {
    if (empty(trim($raw))) {
        return [
            'Henna Series'       => ['Brow Henna','Nail Henna Tangan','Nail Henna Kaki','Bundling Meni-Henna','Henna Fun'],
            'Treatment Spa'      => ['Bundling Manicure & Pedicure','Manicure / Pedicure','Hand Spa','Foot Spa','Callus Treatment'],
            'Brow & Lash'        => ['Brow Bomb','Lashlift','Lashlift Tint'],
            'Rambut'             => ['Creambath','Hair Mask','Hair Spa','Cuci,Catok,Blow','Bleaching S','Coloring Full','Bleaching','Balayage','Down Peim Poni','Keriting Klasik','Keriting Digital','Keratin Treatment','Smoothing'],
            'Nail Art & Services'=> ['Press On Nail Basic','Press On Nail Motif','Kids Basic Gel','Kids Gel + 4 Sticker','Kids Gel + Full Sticker','Gel Basic Tangan / Kaki','Extension','Gel French / Cat Eyes','Remove Gel','Gel Ombre / Blush On','Remove Extension','Bundling Nail Art + Extension'],
        ];
    }
    $groups = [];
    $currentGroup = '__ungrouped__';
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (substr($line, -1) === ':') {
            $currentGroup = rtrim($line, ':');
            if (!isset($groups[$currentGroup])) $groups[$currentGroup] = [];
        } else {
            $groups[$currentGroup][] = $line;
        }
    }
    if (count($groups) === 1 && isset($groups['__ungrouped__'])) {
        return ['Layanan' => $groups['__ungrouped__']];
    }
    unset($groups['__ungrouped__']);
    return $groups;
}

$layanansGrouped = parseServicesGrouped($bk['services_list']);
$timeSlots       = array_values(array_filter(array_map('trim', explode("\n", $bk['time_slots']))));
$maxJumlah       = max(1, (int)($bk['max_jumlah_orang'] ?: 10));

$pageTitle = "Booking Appointment — NISWÀ BEAUTY";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
    /* ── Slot availability indicators ── */
    .slot-indicator {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }
    .slot-available   { background: #10b981; }
    .slot-booked      { background: #ef4444; }

    select option.booked-slot { color: #aaa; }

    /* ── WA Notif Modal ── */
    #waNotifModal {
        display: none;
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.65);
        align-items: center; justify-content: center;
        padding: 16px;
        backdrop-filter: blur(5px);
    }
    #waNotifModal.show { display: flex; }
    .wa-modal-box {
        background: #fff;
        border-radius: 24px;
        width: 90%; max-width: 420px;
        overflow: hidden;
        box-shadow: 0 24px 64px rgba(0,0,0,0.25);
        animation: popIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes popIn {
        from { opacity:0; transform: scale(0.85) translateY(20px); }
        to   { opacity:1; transform: scale(1)    translateY(0); }
    }
    .wa-modal-header {
        background: linear-gradient(135deg, #25d366, #128c4a);
        padding: 28px 24px 22px;
        text-align: center;
        color: #fff;
    }
    .wa-modal-header .check-icon {
        width: 70px; height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px;
        border: 3px solid rgba(255,255,255,0.4);
    }
    .wa-modal-body { padding: 22px 24px; }

    .wa-detail-box {
        background: #f8fffe;
        border: 1.5px solid #a7f3d0;
        border-radius: 14px;
        padding: 14px 16px;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        margin-bottom: 14px;
    }
    .wa-detail-row {
        display: flex; justify-content: space-between;
        margin-bottom: 6px; color: #555;
    }
    .wa-detail-row:last-child { margin-bottom: 0; }
    .wa-detail-row strong { color: #1a1a2e; }

    .wa-slots-box {
        background: #fff8e7;
        border: 1.5px dashed #f59e0b;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 12.5px;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 14px;
    }
    .wa-slots-title {
        font-weight: 700; color: #b45309; font-size: 12px;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 7px;
        display: flex; align-items: center; gap: 6px;
    }
    .wa-slots-grid {
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .wa-slot-badge {
        background: #10b981; color: #fff;
        padding: 3px 10px; border-radius: 20px;
        font-size: 11.5px; font-weight: 600;
    }

    .btn-wa-customer {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%;
        background: linear-gradient(135deg, #25d366, #128c4a);
        color: #fff; font-weight: 700; font-size: 14px;
        border-radius: 12px; padding: 13px;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 10px;
        box-shadow: 0 4px 16px rgba(37,211,102,0.3);
        transition: all .2s;
    }
    .btn-wa-customer:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37,211,102,0.45);
        color: #fff;
    }
    .btn-wa-admin {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%;
        background: #f0fdf4;
        color: #128c4a; font-weight: 600; font-size: 13px;
        border: 1.5px solid #a7f3d0;
        border-radius: 12px; padding: 10px;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 10px;
        transition: all .2s;
    }
    .btn-wa-admin:hover {
        background: #dcfce7; color: #128c4a;
    }
    .btn-close-modal {
        width: 100%;
        background: #f5ede6; color: #8B6F5E;
        border: none; border-radius: 12px;
        padding: 11px; font-weight: 600;
        font-family: 'Poppins', sans-serif;
        font-size: 13px; cursor: pointer;
        transition: background .2s;
    }
    .btn-close-modal:hover { background: #ede0d4; }

    /* ── Slot status pada select ── */
    .slot-hint {
        font-size: 11px; color: #10b981;
        display: flex; align-items: center; gap: 5px;
        margin-top: 5px;
    }
    .slot-hint.loading { color: #f59e0b; }
    .slot-hint.warn    { color: #ef4444; }

    /* ── Jenis Layanan Toggle ── */
    .jenis-layanan-wrap {
        display: flex; gap: 12px;
    }
    .jenis-layanan-card {
        flex: 1; cursor: pointer;
        border: 2px solid #e5d5cc;
        border-radius: 14px;
        padding: 14px 10px;
        text-align: center;
        transition: all .25s;
        background: #fff;
        font-family: 'Poppins', sans-serif;
    }
    .jenis-layanan-card input[type=radio] { display: none; }
    .jenis-layanan-card .jl-icon { font-size: 26px; display: block; margin-bottom: 5px; }
    .jenis-layanan-card .jl-label { font-size: 12.5px; font-weight: 600; color: #5a3e35; display: block; }
    .jenis-layanan-card .jl-desc  { font-size: 10.5px; color: #999; margin-top: 3px; display: block; }
    .jenis-layanan-card:hover { border-color: #c9a08a; background: #fdf6f2; }
    .jenis-layanan-card.selected {
        border-color: #8B6F5E;
        background: linear-gradient(135deg,#fdf2ec,#fff7f3);
        box-shadow: 0 4px 14px rgba(139,111,94,0.18);
    }
    .jenis-layanan-card.selected .jl-label { color: #6d3a28; }
    .home-service-badge-wrap {
        text-align: center; margin-top: 6px;
    }
    .home-service-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff3cd; border: 1px solid #ffc107;
        border-radius: 20px; font-size: 11px; color: #856404;
        padding: 6px 16px; font-weight: 600;
    }
    #alamatHomeService {
        display: none;
        margin-top: 14px;
        animation: fadeDown .3s ease;
    }
    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if ($isLoggedIn): ?>
<div id="user-greeting-bar" style="
    background: linear-gradient(135deg,#8B6F5E,#D6C1A3);
    padding: 11px 40px;
    text-align: center;
    position: fixed;
    top: 80px; left: 0; right: 0; width: 100%;
    z-index: 998;
    box-shadow: 0 3px 12px rgba(139,111,94,0.3);
    transition: opacity 0.3s ease, transform 0.3s ease;
">
    <span style="color:#fff;font-size:14px;font-weight:500;display:inline-flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:center;">
        <span style="display:inline-flex;align-items:center;gap:7px;">
            <i class="fas fa-user-circle" style="font-size:18px;"></i>
            Hai, <strong style="font-size:15px;"><?= htmlspecialchars($_SESSION['user']) ?></strong>! 👋
        </span>
        <span style="opacity:0.5;font-size:16px;">|</span>
        <a href="logout.php" style="color:#fff;text-decoration:none;font-size:12px;display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.2);padding:4px 14px;border-radius:20px;transition:background 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.35)'"
           onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </span>
</div>
<script>
(function() {
    var bar = document.getElementById('user-greeting-bar');
    function setBarPosition() {
        var navbar = document.querySelector('nav, .navbar, header, #navbar, [class*="navbar"]');
        var navHeight = navbar ? navbar.offsetHeight : 80;
        bar.style.top = navHeight + 'px';
    }
    window.addEventListener('load', setBarPosition);
    window.addEventListener('resize', setBarPosition);
    var lastScroll = 0;
    window.addEventListener('scroll', function() {
        var cur = window.pageYOffset || document.documentElement.scrollTop;
        if (cur > 60 && cur > lastScroll) {
            bar.style.opacity = '0'; bar.style.transform = 'translateY(-100%)'; bar.style.pointerEvents = 'none';
        } else {
            bar.style.opacity = '1'; bar.style.transform = 'translateY(0)'; bar.style.pointerEvents = 'auto';
        }
        lastScroll = cur <= 0 ? 0 : cur;
    }, { passive: true });
})();
</script>
<?php endif; ?>

<!-- Booking Form -->
<section class="premium-booking-section py-5" style="margin-top:100px;">
    <div class="container">
        <div class="row justify-content-center g-5">

            <!-- Form Utama -->
            <div class="col-lg-8" data-aos="fade-up">
                <div class="premium-booking-card">
                    <div class="card-header text-center">
                        <h3><?= htmlspecialchars($bk['form_title']) ?></h3>
                        <p><?= htmlspecialchars($bk['page_subtitle']) ?></p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger mx-4 mt-4">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="premiumBookingForm" class="booking-form">
                        <div class="row g-4">

                            <!-- Nama -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-user me-2 text-pink"></i>Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="nama" required
                                           placeholder="Nama lengkap Anda"
                                           value="<?= htmlspecialchars($prefillNama) ?>">
                                </div>
                            </div>

                            <!-- WhatsApp -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="fab fa-whatsapp me-2 text-success"></i>Nomor WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                    <input type="tel" class="form-control" name="whatsapp" required
                                           placeholder="08xxxxxxxxxx"
                                           value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-envelope me-2 text-pink"></i>Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" required
                                           placeholder="email@contoh.com"
                                           value="<?= htmlspecialchars($prefillEmail) ?>">
                                </div>
                            </div>

                            <!-- Layanan -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-spa me-2 text-pink"></i>Pilih Layanan</label>
                                <select class="form-select" name="layanan" required>
                                    <option value="">— Pilih Layanan —</option>
                                    <?php foreach ($layanansGrouped as $grup => $items): ?>
                                    <optgroup label="<?= htmlspecialchars($grup) ?>">
                                        <?php foreach ($items as $l): ?>
                                        <option value="<?= htmlspecialchars($l) ?>" <?= ($_POST['layanan']??'')===$l ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($l) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tanggal -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-calendar me-2 text-pink"></i>Tanggal Booking</label>
                                <input type="date" class="form-control" name="tanggal" id="bookingDate" required
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($_POST['tanggal'] ?? '') ?>">
                            </div>

                            <!-- Jam -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-clock me-2 text-pink"></i>Jam Booking
                                </label>
                                <select class="form-select" name="jam" id="jamSelect" required>
                                    <option value="">Pilih tanggal dulu...</option>
                                </select>
                                <div id="slotHint" class="slot-hint" style="display:none;">
                                    <i class="fas fa-circle-notch fa-spin"></i>
                                    <span id="slotHintText">Mengecek ketersediaan...</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:12px;margin-top:7px;font-size:11.5px;color:#888;font-family:'Poppins',sans-serif;">
                                    <span><span class="slot-indicator slot-available"></span>Tersedia</span>
                                    <span><span class="slot-indicator slot-booked"></span>Penuh</span>
                                </div>
                            </div>


                            <!-- Jenis Layanan -->
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-2 text-pink"></i>Jenis Layanan</label>
                                <div class="jenis-layanan-wrap">
                                    <label class="jenis-layanan-card <?= (($_POST['jenis_layanan']??'datang')==='datang') ? 'selected' : '' ?>" onclick="pilihJenis(this,'datang')">
                                        <input type="radio" name="jenis_layanan" value="datang" <?= (($_POST['jenis_layanan']??'datang')==='datang') ? 'checked' : '' ?>>
                                        <span class="jl-icon">🏪</span>
                                        <span class="jl-label">Datang ke Tempat</span>
                                        <span class="jl-desc">Kunjungi salon kami</span>
                                    </label>
                                    <label class="jenis-layanan-card <?= (($_POST['jenis_layanan']??'')==='home_service') ? 'selected' : '' ?>" onclick="pilihJenis(this,'home_service')">
                                        <input type="radio" name="jenis_layanan" value="home_service" <?= (($_POST['jenis_layanan']??'')==='home_service') ? 'checked' : '' ?>>
                                        <span class="jl-icon">🏠</span>
                                        <span class="jl-label">Home Service</span>
                                        <span class="jl-desc">Beautician ke lokasi Anda</span>
                                    </label>
                                </div>
                                <!-- Alamat Home Service -->
                                <div id="alamatHomeService" style="display:<?= (($_POST['jenis_layanan']??'')==='home_service') ? 'block' : 'none' ?>">
                                    <div class="home-service-badge-wrap"><span class="home-service-badge"><i class="fas fa-info-circle"></i> Biaya tambahan home service berlaku sesuai jarak</span></div>
                                    <div class="input-group mt-2">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="alamat_hs" id="alamatHsInput" rows="2"
                                                  style="text-align:center; padding-top: 18px;"
                                                  placeholder="Tulis alamat lengkap Anda (jalan, RT/RW, kelurahan, kota)"><?= htmlspecialchars($_POST['alamat_hs'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Jumlah Orang -->
                            <?php if ($bk['show_jumlah_orang'] != '0'): ?>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-users me-2 text-pink"></i>Jumlah Orang</label>
                                <select class="form-select" name="jumlah_orang">
                                    <?php for ($i = 1; $i <= $maxJumlah; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_POST['jumlah_orang']??1)==$i ? 'selected' : '' ?>><?= $i ?> Orang</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- Catatan -->
                            <?php if ($bk['show_catatan'] != '0'): ?>
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-sticky-note me-2 text-pink"></i>Catatan <span class="text-muted" style="font-size:12px;">(opsional)</span></label>
                                <textarea class="form-control" name="catatan" rows="2" placeholder="Contoh: alergi tertentu, request khusus, dsb."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                            </div>
                            <?php endif; ?>

                            <!-- Info WA notif -->
                            <div class="col-12">
                                <div style="background:#f0fff8;border:1.5px solid #a7f3d0;border-radius:12px;padding:12px 16px;display:flex;align-items:flex-start;gap:10px;font-family:'Poppins',sans-serif;font-size:12.5px;color:#065f46;">
                                    <i class="fab fa-whatsapp" style="font-size:18px;color:#25d366;margin-top:1px;flex-shrink:0;"></i>
                                    <span>Setelah booking berhasil, tim kami akan <strong>menghubungi Anda</strong> dalam waktu &lt;1 jam untuk konfirmasi jadwal.</span>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="col-12">
                                <button type="submit" class="btn-submit-premium w-100" id="btnSubmitBooking">
                                    <i class="fas fa-paper-plane me-2"></i>Konfirmasi Booking
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4" data-aos="fade-left">
                <div class="booking-sidebar">
                    <div class="sidebar-card">
                        <h5><i class="fas fa-info-circle me-2 text-gold"></i>Informasi Salon</h5>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt text-pink"></i>
                            <div><?= nl2br(htmlspecialchars($bk['sidebar_address'])) ?></div>
                        </div>
                        <div class="info-item">
                            <i class="fab fa-whatsapp text-success"></i>
                            <div><strong><?= htmlspecialchars($bk['sidebar_whatsapp']) ?></strong></div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div><?= nl2br(htmlspecialchars($bk['sidebar_hours'])) ?></div>
                        </div>
                    </div>
                    <div class="sidebar-card">
                        <h5><i class="fas fa-star me-2 text-gold"></i>Kenapa Booking Disini?</h5>
                        <ul class="perks-list">
                            <?php
                            $perkIcons = ['fa-bolt text-success','fa-user-md text-pink','fa-couch text-gold','fa-leaf'];
                            foreach (['perk_1','perk_2','perk_3','perk_4'] as $i => $pk):
                                if (empty($bk[$pk])) continue;
                            ?>
                            <li><i class="fas <?= $perkIcons[$i] ?> me-2"></i><?= htmlspecialchars($bk[$pk]) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     MODAL NOTIFIKASI WHATSAPP — muncul setelah booking sukses
══════════════════════════════════════════════════════════ -->
<div id="waNotifModal" class="<?= $message ? 'show' : '' ?>">
    <div class="wa-modal-box">
        <!-- Header -->
        <div class="wa-modal-header">
            <div class="check-icon">
                <i class="fas fa-check" style="font-size:32px;"></i>
            </div>
            <h5 style="font-weight:800;font-size:20px;margin:0 0 5px;font-family:'Playfair Display',serif;">Booking Berhasil! 🎉</h5>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;font-family:'Poppins',sans-serif;">
                Tim kami akan segera menghubungi Anda
            </p>
        </div>

        <!-- Body -->
        <div class="wa-modal-body">

            <!-- Detail booking -->
            <div class="wa-detail-box">
                <div class="wa-detail-row">
                    <span>📋 ID Booking</span>
                    <strong>#<?= $booking_id ?? '' ?></strong>
                </div>
                <div class="wa-detail-row">
                    <span>💆 Layanan</span>
                    <strong><?= htmlspecialchars($_POST['layanan'] ?? '') ?></strong>
                </div>
                <div class="wa-detail-row">
                    <span>📅 Tanggal</span>
                    <strong id="modalDateFmt"><?= htmlspecialchars($_POST['tanggal'] ?? '') ?></strong>
                </div>
                <div class="wa-detail-row">
                    <span>⏰ Jam</span>
                    <strong><?= htmlspecialchars($_POST['jam'] ?? '') ?> WIB</strong>
                </div>
                <div class="wa-detail-row">
                    <span>📍 Jenis</span>
                    <strong><?= (($_POST['jenis_layanan']??'datang')==='home_service') ? '🏠 Home Service' : '🏪 Datang ke Tempat' ?></strong>
                </div>
                <?php if (($_POST['jenis_layanan']??'')==='home_service' && !empty($_POST['alamat_hs'])): ?>
                <div class="wa-detail-row">
                    <span>🗺️ Alamat</span>
                    <strong style="font-size:11px;text-align:right;max-width:60%;"><?= htmlspecialchars($_POST['alamat_hs'] ?? '') ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <!-- Slot tersedia -->
            <?php
            $avail_decoded = json_decode($available_slots_js, true);
            if (!empty($avail_decoded)):
            ?>
            <div class="wa-slots-box">
                <div class="wa-slots-title">
                    <i class="fas fa-clock"></i>
                    Jam Lain Tersedia di Tanggal Ini
                </div>
                <div class="wa-slots-grid">
                    <?php foreach ($avail_decoded as $sl): ?>
                    <span class="wa-slot-badge"><?= htmlspecialchars($sl) ?> WIB</span>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:11px;color:#92400e;margin-top:8px;">
                    <i class="fas fa-info-circle me-1"></i>
                    Jika jam yang kamu pilih penuh, tim kami akan konfirmasi dengan salah satu jam di atas.
                </div>
            </div>
            <?php endif; ?>

            <!-- Tutup -->
            <button class="btn-close-modal" onclick="closeWaModal()">
                <i class="fas fa-times me-2"></i>Tutup
            </button>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<button id="backToTop"><i class="fas fa-chevron-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="script.js"></script>

<script>
AOS.init({ duration: 800, once: true });

flatpickr('#bookingDate', {
    dateFormat: "Y-m-d",
    minDate: "today",
    altInput: true,
    altFormat: "d F Y",
    onChange: function(selectedDates, dateStr) {
        loadSlots(dateStr);
    }
});

// ── Format tanggal di modal ──────────────────────────────────
(function(){
    var el = document.getElementById('modalDateFmt');
    if (!el || !el.textContent.trim()) return;
    var raw = el.textContent.trim();
    var ts  = new Date(raw + 'T00:00:00');
    if (isNaN(ts)) return;
    var bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                 'Juli','Agustus','September','Oktober','November','Desember'];
    el.textContent = ts.getDate() + ' ' + bulan[ts.getMonth()] + ' ' + ts.getFullYear();
})();

// ── Load slot ketersediaan dari server ───────────────────────
var slotCache = {};

function loadSlots(tanggal) {
    var select = document.getElementById('jamSelect');
    var hint   = document.getElementById('slotHint');
    var hintTxt= document.getElementById('slotHintText');

    if (!tanggal) {
        select.innerHTML = '<option value="">Pilih tanggal dulu...</option>';
        hint.style.display = 'none';
        return;
    }

    if (slotCache[tanggal]) {
        renderSlots(slotCache[tanggal], select);
        return;
    }

    // Show loading
    select.innerHTML = '<option value="">Mengecek ketersediaan...</option>';
    select.disabled  = true;
    hint.style.display  = 'flex';
    hint.className      = 'slot-hint loading';
    hintTxt.textContent = 'Mengecek jam tersedia...';

    fetch('booking.php?check_slots=1&tanggal=' + encodeURIComponent(tanggal))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            slotCache[tanggal] = data.slots;
            renderSlots(data.slots, select);
            select.disabled = false;

            var avail = data.slots.filter(function(s){ return s.available; });
            hint.className = avail.length > 0 ? 'slot-hint' : 'slot-hint warn';
            hintTxt.textContent = avail.length > 0
                ? avail.length + ' jam tersedia di tanggal ini'
                : 'Semua slot penuh! Pilih tanggal lain.';
            hint.style.display = 'flex';
        })
        .catch(function() {
            // Fallback: tampilkan semua slot tanpa status
            renderFallbackSlots(select);
            select.disabled = false;
            hint.style.display = 'none';
        });
}

function renderSlots(slots, select) {
    var prevVal = select.value;
    select.innerHTML = '<option value="">— Pilih Jam —</option>';
    slots.forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s.time;
        if (s.available) {
            opt.textContent = s.time + ' WIB  ✅ Tersedia';
        } else {
            opt.textContent = s.time + ' WIB  ❌ Penuh';
            opt.disabled    = true;
            opt.classList.add('booked-slot');
        }
        if (s.time === prevVal && s.available) opt.selected = true;
        select.appendChild(opt);
    });
}

function renderFallbackSlots(select) {
    var slots = <?= json_encode(array_values($timeSlots)) ?>;
    select.innerHTML = '<option value="">— Pilih Jam —</option>';
    slots.forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s;
        opt.textContent = s + ' WIB';
        select.appendChild(opt);
    });
}

// Inisialisasi: jika tanggal sudah ada (setelah error POST), load slot
(function(){
    var tgl = document.getElementById('bookingDate').value;
    if (tgl) {
        loadSlots(tgl);
    } else {
        // Default: render semua slot tanpa status
        renderFallbackSlots(document.getElementById('jamSelect'));
    }

    // Pre-select jam yang dipilih sebelumnya jika ada
    var prevJam = '<?= htmlspecialchars($_POST['jam'] ?? '') ?>';
    if (prevJam && tgl) {
        // Akan di-set otomatis oleh renderSlots setelah load
        document.getElementById('jamSelect').dataset.preselectJam = prevJam;
    }
})();

// ── Submit loading state ────────────────────────────────────
document.getElementById('premiumBookingForm').addEventListener('submit', function() {
    var btn = this.querySelector('.btn-submit-premium, #btnSubmitBooking');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        btn.disabled  = true;
    }
});

// ── Close modal ─────────────────────────────────────────────

// ── Jenis Layanan Toggle ────────────────────────────────
function pilihJenis(card, val) {
    document.querySelectorAll(".jenis-layanan-card").forEach(function(c) {
        c.classList.remove("selected");
    });
    card.classList.add("selected");
    card.querySelector("input[type=radio]").checked = true;
    var box = document.getElementById("alamatHomeService");
    var inp = document.getElementById("alamatHsInput");
    if (val === "home_service") {
        box.style.display = "block";
        if (inp) inp.required = true;
    } else {
        box.style.display = "none";
        if (inp) { inp.required = false; inp.value = ""; }
    }
}

function closeWaModal() {
    document.getElementById('waNotifModal').classList.remove('show');
}

// ── Auto-buka modal jika booking sukses ─────────────────────
<?php if ($message): ?>
window.addEventListener('load', function() {
    document.getElementById('waNotifModal').classList.add('show');
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
<?php endif; ?>

// Klik di luar modal = tutup
document.getElementById('waNotifModal').addEventListener('click', function(e) {
    if (e.target === this) closeWaModal();
});
</script>
</body>
</html>