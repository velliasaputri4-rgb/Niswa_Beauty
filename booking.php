<?php
// booking.php — NISWÀ BEAUTY Premium Booking Page
session_start();

// ── Role Check ─────────────────────────────────────────────
// Admin boleh mengakses halaman booking sebagai tampilan web biasa
// Guest diizinkan mengakses halaman booking tanpa login
// ───────────────────────────────────────────────────────────

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = mysqli_connect("localhost", "root", "", "salon_db");
mysqli_set_charset($conn, 'utf8mb4');

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ── AUTO-FIX: tambah kolom yang mungkin belum ada di tabel bookings ──
$fixColumns = [
    "user_id"     => "ALTER TABLE bookings ADD COLUMN user_id INT DEFAULT NULL",
    "jumlah_orang"=> "ALTER TABLE bookings ADD COLUMN jumlah_orang INT DEFAULT 1",
    "catatan"     => "ALTER TABLE bookings ADD COLUMN catatan TEXT",
];
foreach ($fixColumns as $col => $sql) {
    $cek = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE '$col'");
    if ($cek && mysqli_num_rows($cek) === 0) {
        mysqli_query($conn, $sql);
    }
}
// ─────────────────────────────────────────────────────────────────────

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = $_SESSION['user_id'] ?? null; // NULL untuk tamu (guest)
    $nama         = trim($_POST['nama']         ?? '');
    $whatsapp     = trim($_POST['whatsapp']     ?? '');
    $email        = trim($_POST['email']        ?? '');
    $layanan      = trim($_POST['layanan']      ?? '');
    $tanggal      = trim($_POST['tanggal']      ?? '');
    $jam          = trim($_POST['jam']          ?? '');
    $jumlah_orang = (int)($_POST['jumlah_orang'] ?? 1);
    $catatan      = trim($_POST['catatan']      ?? '');

    $errors = [];
    if (empty($nama))     $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($whatsapp)) $errors[] = 'Nomor WhatsApp wajib diisi.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (empty($layanan))  $errors[] = 'Layanan wajib dipilih.';
    if (empty($tanggal))  $errors[] = 'Tanggal wajib dipilih.';
    if (empty($jam))      $errors[] = 'Jam wajib dipilih.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO bookings (user_id, name, phone, email, service, date, time, jumlah_orang, catatan, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        mysqli_stmt_bind_param($stmt, "issssssis",
            $user_id, $nama, $whatsapp, $email, $layanan,
            $tanggal, $jam, $jumlah_orang, $catatan
        );

        if (mysqli_stmt_execute($stmt)) {
            $booking_id = mysqli_stmt_insert_id($stmt);
            $message    = "Booking berhasil! ID: #" . $booking_id;
        } else {
            $error = "Gagal menyimpan booking: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = implode('<br>', $errors);
    }
}

// Pre-fill dari POST (setelah error) atau dari session jika sudah login
$prefillNama  = $_POST['nama']  ?? $_SESSION['user']       ?? '';
$prefillEmail = $_POST['email'] ?? $_SESSION['user_email'] ?? '';
$isLoggedIn   = isset($_SESSION['user']) && ($_SESSION['user_role'] ?? '') !== 'admin';

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
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if ($isLoggedIn): ?>
<!-- Bar info user yang sedang login -->
<div id="user-greeting-bar" style="
    background: linear-gradient(135deg,#8B6F5E,#D6C1A3);
    padding: 11px 40px;
    text-align: center;
    position: fixed;
    top: 80px;
    left: 0;
    right: 0;
    width: 100%;
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
        var hero = document.querySelector('.premium-booking-hero');
        if (hero) hero.style.marginTop = (navHeight + bar.offsetHeight) + 'px';
    }
    window.addEventListener('load', setBarPosition);
    window.addEventListener('resize', setBarPosition);
    var lastScroll = 0;
    window.addEventListener('scroll', function() {
        var cur = window.pageYOffset || document.documentElement.scrollTop;
        if (cur > 60 && cur > lastScroll) {
            bar.style.opacity = '0';
            bar.style.transform = 'translateY(-100%)';
            bar.style.pointerEvents = 'none';
        } else {
            bar.style.opacity = '1';
            bar.style.transform = 'translateY(0)';
            bar.style.pointerEvents = 'auto';
        }
        lastScroll = cur <= 0 ? 0 : cur;
    }, { passive: true });
})();
</script>
<?php endif; ?>

<!-- Hero -->
<section class="premium-booking-hero" style="margin-top:40px;">
    <div class="hero-overlay"></div>
    <div class="container position-relative">
        <div class="section-label mt-4 mb-3"><span>Reservation Excellence</span></div>
        <h1 class="display-2 mb-4">Booking<br><span class="gradient-text">Appointment</span></h1>
        <p class="lead text-white-90 max-w-lg">Pesan jadwal perawatan Anda dengan mudah dan cepat bersama tim profesional NISWÀ BEAUTY.</p>
    </div>
</section>

<!-- Booking Form -->
<section class="premium-booking-section py-5">
    <div class="container">

        <div class="row justify-content-center g-5">
            <!-- Form Utama -->
            <div class="col-lg-8" data-aos="fade-up">
                <div class="premium-booking-card">
                    <div class="card-header text-center">
                        <h3>Form Booking</h3>
                        <p>Semua kolom wajib diisi dengan benar untuk proses cepat</p>
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
                                    <option value="">Pilih layanan</option>
                                    <?php
                                    $layanans = [
                                        // Rambut
                                        'Creambath',
                                        'Hair Mask',
                                        'Hair Spa',
                                        'Cuci Rambut',
                                        'Cuci + Catok',
                                        'Cuci + Blow',
                                        'Bleaching S',
                                        'Coloring Full',
                                        'Bleaching Peek A Boo',
                                        'Bleaching Highlight',
                                        'Balayage',
                                        'Bleaching Full',
                                        'Down Peim Poni',
                                        'Keriting Klasik',
                                        'Keriting Digital',
                                        'Keratin Treatment',
                                        'Smoothing Collagen - Lamei',
                                        'Smoothing Collagen - L\'Oreal',
                                        'Smoothing Sutra - Inaura',
                                        'Smoothing Sutra - Matrix',
                                        'Smoothing Sutra - Go Street',
                                        'Smoothing Sutra - Silky',
                                        'Smoothing Keratin - SDB',
                                        'Smoothing Keratin - Eljo',
                                        'Smoothing Keratin - Gylo',
                                        'Smoothing Expres',
                                        'Smoothing Keratin Expres',
                                        'Smoothing Crystal',
                                        // Treatment Spa
                                        'Bundling Manicure & Pedicure',
                                        'Manicure / Pedicure',
                                        'Hand Spa',
                                        'Foot Spa',
                                        'Callus Treatment',
                                        // Henna Series
                                        'Brow Henna',
                                        'Nail Henna Tangan',
                                        'Nail Henna Kaki',
                                        'Bundling Meni-Henna',
                                        'Henna Fun',
                                        // Nail Art & Services
                                        'Press On Nail Basic',
                                        'Press On Nail Motif',
                                        'Kids Basic Gel',
                                        'Kids Gel + 4 Sticker',
                                        'Kids Gel + Full Sticker',
                                        'Gel Basic Tangan / Kaki',
                                        'Extension',
                                        'Gel French / Cat Eyes',
                                        'Remove Gel',
                                        'Gel Ombre / Blush On',
                                        'Remove Extension',
                                        'Bundling Nail Art + Extension',
                                        // Brow & Lash
                                        'Brow Bomb',
                                        'Lashlift',
                                        'Lashlift Tint',
                                    ];
                                    foreach ($layanans as $l):
                                    ?>
                                    <option <?= ($_POST['layanan']??'')===$l ? 'selected' : '' ?>><?= $l ?></option>
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
                                <label class="form-label"><i class="fas fa-clock me-2 text-pink"></i>Jam Booking</label>
                                <select class="form-select" name="jam" required>
                                    <option value="">Pilih jam</option>
                                    <?php
                                    $jams = ['09:00','10:00','11:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];
                                    foreach ($jams as $j):
                                    ?>
                                    <option value="<?= $j ?>" <?= ($_POST['jam']??'')===$j ? 'selected' : '' ?>><?= $j ?> WIB</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                               <!-- Jumlah Orang -->
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-users me-2 text-pink"></i>Jumlah Orang</label>
                                <select class="form-select" name="jumlah_orang">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_POST['jumlah_orang']??1)==$i ? 'selected' : '' ?>><?= $i ?> Orang</option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Catatan -->
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-sticky-note me-2 text-pink"></i>Catatan Tambahan</label>
                                <textarea class="form-control" name="catatan" rows="3"
                                          placeholder="Alergi, preferensi khusus, dll"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                            </div>

                            <!-- Submit -->
                            <div class="col-12">
                                <button type="submit" class="btn-submit-premium w-100">
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
                            <div>Jl. Lkr.<br>Bangsri, Jepara, Jawa Tengah</div>
                        </div>
                        <div class="info-item">
                            <i class="fab fa-whatsapp text-success"></i>
                            <div><strong>+62 882 006 903 068</strong></div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>Senin - Minggu<br>09:00 - 20:00</div>
                        </div>
                    </div>
                    <div class="sidebar-card">
                        <h5><i class="fas fa-star me-2 text-gold"></i>Kenapa Booking Disini?</h5>
                        <ul class="perks-list">
                            <li><i class="fas fa-bolt text-success me-2"></i>Fast Response &lt;1 jam</li>
                            <li><i class="fas fa-user-md text-pink me-2"></i>Beautician Profesional</li>
                            <li><i class="fas fa-couch text-gold me-2"></i>Tempat Nyaman Mewah</li>
                            <li><i class="fas fa-leaf me-2"></i>Produk Premium Import</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center p-3">
                <div class="success-icon mb-2">
                    <i class="fas fa-check-circle" style="font-size:48px;color:#10b981;"></i>
                </div>
                <h5 class="mb-1">Booking Berhasil!</h5>
                <p class="text-success mb-2" style="font-size:13px;">Terima kasih! Tim kami akan menghubungi via WhatsApp dalam 30 menit.</p>
                <div class="mb-3">
                    <strong style="font-size:13px;">Detail Booking:</strong><br>
                    <small class="text-muted">
                        Layanan: <span id="modalService"></span><br>
                        Tanggal: <span id="modalDate"></span> | Jam: <span id="modalTime"></span>
                    </small>
                </div>
                <a href="https://wa.me/628971440805" target="_blank" class="btn btn-success btn-sm me-1">
                    <i class="fab fa-whatsapp me-1"></i>Chat Konfirmasi
                </a>
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
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
    altFormat: "d F Y"
});

<?php if ($message): ?>
const successModal = new bootstrap.Modal(document.getElementById('successModal'));
successModal.show();
document.getElementById('modalService').textContent = '<?= htmlspecialchars($_POST['layanan'] ?? '') ?>';
document.getElementById('modalDate').textContent    = '<?= htmlspecialchars($_POST['tanggal'] ?? '') ?>';
document.getElementById('modalTime').textContent    = '<?= htmlspecialchars($_POST['jam']     ?? '') ?>';
<?php endif; ?>

document.getElementById('premiumBookingForm').addEventListener('submit', function() {
    const btn = this.querySelector('.btn-submit-premium');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    btn.disabled  = true;
});
</script>
</body>
</html>