<?php
// components/navbar.php
$current = basename($_SERVER['PHP_SELF']);

/* ── Baca data navbar dari CMS (jika koneksi DB tersedia) ── */
if (!function_exists('getNavbar')) {
    function getNavbar($conn, $key, $default = '') {
        if (!$conn) return $default;
        $k = mysqli_real_escape_string($conn, $key);
        $r = mysqli_query($conn, "SELECT value FROM cms_navbar WHERE section='navbar' AND `key`='$k' LIMIT 1");
        if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
        return $default;
    }
}

/* ── Buat koneksi DB jika belum ada (misalnya navbar di-include dari halaman non-CMS) ── */
if (!isset($conn) || !$conn) {
    $conn = @mysqli_connect("localhost", "root", "", "salon_db");
    if ($conn) mysqli_set_charset($conn, 'utf8mb4');
    $navbarLocalConn = true; // flag bahwa koneksi dibuat di sini
} else {
    $navbarLocalConn = false;
}

/* ── Ambil nilai dari DB, fallback ke default ── */
$nav_brand    = getNavbar($conn, 'brand_name',    'NISWÀ BEAUTY');
$nav_home     = getNavbar($conn, 'menu_home',     'Home');
$nav_services = getNavbar($conn, 'menu_services', 'Services');
$nav_product  = getNavbar($conn, 'menu_product',  'Product');
$nav_about    = getNavbar($conn, 'menu_about',    'About');
$nav_book_btn = getNavbar($conn, 'btn_book_text', 'Book Now');

/* ── Tutup koneksi lokal jika dibuat di sini ── */
if (!empty($navbarLocalConn) && $conn) {
    // Biarkan terbuka — akan dipakai oleh halaman yang include navbar ini
}
?>
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="index.php" style="font-family: 'Poppins', sans-serif; font-weight: 700; letter-spacing: 0.5px; font-size: 18px;">
            <?= htmlspecialchars($nav_brand) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="toggler-icon"><i class="fas fa-bars"></i></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $current=='index.php' ? 'active' : '' ?>" href="index.php"><?= htmlspecialchars($nav_home) ?></a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="<?= $current=='index.php' ? '#layanan' : 'index.php#layanan' ?>" id="servicesLink"><?= htmlspecialchars($nav_services) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $current=='index.php' ? '#produk' : 'index.php#produk' ?>" id="productLink"><?= htmlspecialchars($nav_product) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current=='profil.php' ? 'active' : '' ?>" href="profil.php" id="aboutLink"><?= htmlspecialchars($nav_about) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-user me-1"></i>
                    </a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link btn btn-book" href="booking.php">
                        <i class="fas fa-calendar-check me-2"></i><?= htmlspecialchars($nav_book_btn) ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Helper: smooth scroll ke section jika ada di halaman ini
    function scrollToSection(sectionId, linkEl) {
        if (!linkEl) return;
        linkEl.addEventListener('click', function (e) {
            const target = document.getElementById(sectionId);
            if (target) {
                e.preventDefault();
                const navbarHeight = document.getElementById('mainNav').offsetHeight;
                const offset = target.getBoundingClientRect().top + window.scrollY - navbarHeight;
                window.scrollTo({ top: offset, behavior: 'smooth' });

                // Tutup navbar mobile jika terbuka
                const collapse = document.getElementById('navbarNav');
                if (collapse.classList.contains('show')) {
                    document.querySelector('.navbar-toggler').click();
                }
            }
            // Jika section tidak ada (bukan index.php), biarkan redirect normal ke index.php#...
        });
    }

    scrollToSection('produk',  document.getElementById('productLink'));
    scrollToSection('layanan', document.getElementById('servicesLink'));
    // aboutLink sekarang mengarah ke profil.php, tidak perlu smooth scroll
});
</script>