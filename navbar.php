<?php
// components/navbar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="index.php" style="font-family: 'Poppins', sans-serif; font-weight: 700; letter-spacing: 0.5px; font-size: 18px;">
            NISWÀ BEAUTY
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="toggler-icon"><i class="fas fa-bars"></i></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $current=='index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="<?= $current=='index.php' ? '#layanan' : 'index.php#layanan' ?>" id="servicesLink">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $current=='index.php' ? '#produk' : 'index.php#produk' ?>" id="productLink">Product</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $current=='index.php' ? '#about' : 'index.php#about' ?>" id="aboutLink">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-user me-1"></i>
                    </a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link btn btn-book" href="booking.php">
                        <i class="fas fa-calendar-check me-2"></i>Book Now
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
    scrollToSection('about',   document.getElementById('aboutLink'));
});
</script>