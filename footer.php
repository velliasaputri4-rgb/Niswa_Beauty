<?php
// components/footer.php
// Load footer data from CMS if not already loaded
if (!isset($footer_data) && isset($conn)) {
    if (!function_exists('getFooter')) {
        function getFooter($conn, $key, $default = '') {
            if (!$conn) return $default;
            $k = mysqli_real_escape_string($conn, $key);
            $r = mysqli_query($conn, "SELECT value FROM cms_footer WHERE section='footer' AND `key`='$k' LIMIT 1");
            if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
            return $default;
        }
    }
    $footer_data = [
        'brand_name'       => getFooter($conn, 'brand_name',       'NISWÀ BEAUTY'),
        'brand_desc'       => getFooter($conn, 'brand_desc',       'Kecantikan bertemu kemewahan, dengan sentuhan profesional.'),
        'instagram_url'    => getFooter($conn, 'instagram_url',    'https://www.instagram.com/niswanail?igsh=MXJtYW1kenhuN3VpNA=='),
        'tiktok_url'       => getFooter($conn, 'tiktok_url',       'https://www.tiktok.com/@niswabeauty?_r=1&_t=ZS-96BG9fNdy7Q'),
        'whatsapp_url'     => getFooter($conn, 'whatsapp_url',     'https://wa.me/0882006903068'),
        'address'          => getFooter($conn, 'address',          'Bangsri, Jepara, Jawa Tengah'),
        'phone'            => getFooter($conn, 'phone',            '+62 882-0069-03068'),
        'email'            => getFooter($conn, 'email',            'niswabeauty15@gmail.com'),
        'hours'            => getFooter($conn, 'hours',            'Senin – Sabtu: 09:00 – 20:00'),
        'copyright_text'   => getFooter($conn, 'copyright_text',   'NISWÀ BEAUTY. All rights reserved.'),
        'ql_home_label'    => getFooter($conn, 'ql_home_label',    'Home'),
        'ql_home_url'      => getFooter($conn, 'ql_home_url',      'index.php'),
        'ql_products_label'=> getFooter($conn, 'ql_products_label','Products'),
        'ql_products_url'  => getFooter($conn, 'ql_products_url',  'index.php#produk'),
        'ql_services_label'=> getFooter($conn, 'ql_services_label','Services'),
        'ql_services_url'  => getFooter($conn, 'ql_services_url',  'index.php#layanan'),
        'ql_about_label'   => getFooter($conn, 'ql_about_label',   'About'),
        'ql_about_url'     => getFooter($conn, 'ql_about_url',     'profil.php'),
        'ql_booking_label' => getFooter($conn, 'ql_booking_label', 'Booking'),
        'ql_booking_url'   => getFooter($conn, 'ql_booking_url',   'booking.php'),
    ];
}
// Fallback defaults jika $footer_data belum terisi
if (!isset($footer_data)) {
    $footer_data = [
        'brand_name'       => 'NISWÀ BEAUTY',
        'brand_desc'       => 'Kecantikan bertemu kemewahan, dengan sentuhan profesional.',
        'instagram_url'    => 'https://www.instagram.com/niswanail?igsh=MXJtYW1kenhuN3VpNA==',
        'tiktok_url'       => 'https://www.tiktok.com/@niswabeauty?_r=1&_t=ZS-96BG9fNdy7Q',
        'whatsapp_url'     => 'https://wa.me/0882006903068',
        'address'          => 'Bangsri, Jepara, Jawa Tengah',
        'phone'            => '+62 882-0069-03068',
        'email'            => 'niswabeauty15@gmail.com',
        'hours'            => 'Senin – Sabtu: 09:00 – 20:00',
        'copyright_text'   => 'NISWÀ BEAUTY. All rights reserved.',
        'ql_home_label'    => 'Home',
        'ql_home_url'      => 'index.php',
        'ql_products_label'=> 'Products',
        'ql_products_url'  => 'index.php#produk',
        'ql_services_label'=> 'Services',
        'ql_services_url'  => 'index.php#layanan',
        'ql_about_label'   => 'About',
        'ql_about_url'     => 'profil.php',
        'ql_booking_label' => 'Booking',
        'ql_booking_url'   => 'booking.php',
    ];
}
?>
<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row g-3 justify-content-center text-center">

                <!-- Brand -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up">
                    <div class="footer-brand"><?= htmlspecialchars($footer_data['brand_name']) ?></div>
                    <p class="footer-desc"><?= htmlspecialchars($footer_data['brand_desc']) ?></p>
                    <div class="footer-socials">
                        <a href="<?= htmlspecialchars($footer_data['instagram_url']) ?>" target="_blank" rel="noopener" class="social-btn"><i class="fab fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($footer_data['tiktok_url']) ?>" target="_blank" rel="noopener" class="social-btn"><i class="fab fa-tiktok"></i></a>
                        <a href="<?= htmlspecialchars($footer_data['whatsapp_url']) ?>" target="_blank" rel="noopener" class="social-btn"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <h6 class="footer-title">Quick Links</h6>
                    <ul class="footer-links" style="display:inline-block;text-align:left;">
                        <li><a href="<?= htmlspecialchars($footer_data['ql_home_url']) ?>"><i class="fas fa-chevron-right me-1"></i> <?= htmlspecialchars($footer_data['ql_home_label']) ?></a></li>
                        <li><a href="<?= htmlspecialchars($footer_data['ql_products_url']) ?>"><i class="fas fa-chevron-right me-1"></i> <?= htmlspecialchars($footer_data['ql_products_label']) ?></a></li>
                        <li><a href="<?= htmlspecialchars($footer_data['ql_services_url']) ?>"><i class="fas fa-chevron-right me-1"></i> <?= htmlspecialchars($footer_data['ql_services_label']) ?></a></li>
                        <li><a href="<?= htmlspecialchars($footer_data['ql_about_url']) ?>"><i class="fas fa-chevron-right me-1"></i> <?= htmlspecialchars($footer_data['ql_about_label']) ?></a></li>
                        <li><a href="<?= htmlspecialchars($footer_data['ql_booking_url']) ?>"><i class="fas fa-chevron-right me-1"></i> <?= htmlspecialchars($footer_data['ql_booking_label']) ?></a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <h6 class="footer-title">Contact Us</h6>
                    <ul class="footer-contact" style="text-align:left;">
                        <li><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($footer_data['address']) ?></span></li>
                        <li><i class="fas fa-phone-alt"></i><span><?= htmlspecialchars($footer_data['phone']) ?></span></li>
                        <li><i class="fas fa-envelope"></i><span><?= htmlspecialchars($footer_data['email']) ?></span></li>
                        <li><i class="fas fa-clock"></i><span><?= htmlspecialchars($footer_data['hours']) ?></span></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($footer_data['copyright_text']) ?> &nbsp;·&nbsp; Crafted with <i class="fas fa-heart text-pink"></i> for beauty lovers</p>
        </div>
    </div>
</footer>