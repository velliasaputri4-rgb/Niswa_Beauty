<?php
session_start();

$conn = @mysqli_connect("localhost", "root", "", "salon_db");
if ($conn) mysqli_set_charset($conn, 'utf8mb4');

function getProfil($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_profil WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function getContent($conn, $section, $key, $default = '') {
    if (!$conn) return $default;
    $s = mysqli_real_escape_string($conn, $section);
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='$s' AND `key`='$k' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) return $row['value'];
    return $default;
}
function esc($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

/* ── Load Data ── */
$profil = [
    'owner_name'    => getProfil($conn,'profil','owner_name',    'Niswa'),
    'owner_tagline' => getProfil($conn,'profil','owner_tagline', '"Kecantikan adalah kepercayaan diri yang paling murni."'),
    'owner_bio1'    => getProfil($conn,'profil','owner_bio1',
        'Pendiri Niswa Beauty memulai perjalanan usahanya dari jasa henna keliling dengan nama Niswa Henna. Dengan penuh semangat dan ketekunan, layanan dilakukan dari rumah ke rumah untuk memenuhi kebutuhan pelanggan di sekitar Jepara.' . "\n\n" .
        'Pada tahun 2018, dunia kecantikan khususnya nail art dan fake nails mulai berkembang pesat. Melihat peluang tersebut, pendiri mulai mempelajari dan mengembangkan layanan nail art menggunakan perlengkapan sederhana seperti nail polish. Berawal dari daerah Lebak Pakis Aji, hasil karya yang teliti dan pelayanan yang baik membuat nama Niswa mulai dikenal masyarakat.' . "\n\n" .
        'Perjalanan usaha semakin berkembang ketika mendapat dukungan dan inspirasi dari salah satu teman di Dubai dalam pengembangan dunia kecantikan. Memasuki tahun 2019, usaha mulai berjalan lebih lancar setelah mendapatkan supplier lokal dan pelanggan dari luar daerah seperti Kudus dan Tanjung, Semarang.'
    ),
    'owner_bio2'    => getProfil($conn,'profil','owner_bio2',
        'Tahun 2020–2021 menjadi masa penuh perjuangan sekaligus perkembangan. Pendiri mulai dikenal oleh beberapa publik figur lokal di Jepara yang menggunakan jasa nail art Niswa. Bahkan pada masa awal, beberapa layanan diberikan secara gratis sebagai bentuk belajar dan membangun relasi. Dukungan teman-teman menjadi salah satu alasan usaha ini terus bertahan dan berkembang.' . "\n\n" .
        'Pada tahun 2022, perjalanan usaha sempat mengalami ujian ketika pendiri mengalami keguguran sehingga mulai membatasi pekerjaan dengan lokasi yang terlalu jauh. Namun semangat untuk terus berkembang tidak berhenti. Di masa tersebut, usaha seserahan berkembang pesat dan menjadi salah satu layanan yang diminati pelanggan.' . "\n\n" .
        'Saat merintis sendiri, jam kerja dimulai dari pukul 10 pagi hingga 9 malam dengan jumlah pelanggan yang bisa mencapai lebih dari 7 orang per hari. Hingga kini, pendiri Niswa Beauty terus belajar dan berkembang, terutama dalam bidang media sosial, pelayanan, dan branding, dengan tetap mempertahankan sikap rendah hati dalam membangun usaha sendiri.'
    ),
    'store_name'    => getProfil($conn,'profil','store_name',    'NISWÀ BEAUTY'),
    'store_tagline' => getProfil($conn,'profil','store_tagline', '"Premium Beauty Experience di Jantung Jepara"'),
    'store_bio1'    => getProfil($conn,'profil','store_bio1',
        'Niswa Beauty merupakan usaha di bidang kecantikan yang berawal dari layanan henna sederhana bernama Niswa Henna. Seiring berkembangnya tren kecantikan pada tahun 2018, usaha ini mulai merambah ke layanan nail art dan fake nails untuk memenuhi kebutuhan pelanggan, khususnya calon pengantin.' . "\n\n" .
        'Dengan kualitas pelayanan dan hasil karya yang terus berkembang, Niswa mulai dikenal oleh masyarakat sekitar hingga mendapatkan pelanggan dari luar daerah pada tahun 2019. Perkembangan usaha semakin baik setelah memiliki supplier lokal dan jaringan pelanggan yang lebih luas.' . "\n\n" .
        'Pada tahun 2020, Niswa Beauty membuka studio kecil pertama di rumah daerah Tengguli. Tidak hanya melayani nail art, usaha ini juga menyediakan layanan wedding, gift, dan seserahan. Seiring waktu, layanan nail art menjadi semakin diminati dan dikenal oleh berbagai kalangan di Jepara.'
    ),
    'store_bio2'    => getProfil($conn,'profil','store_bio2',
        'Tanggal 15 Juli 2023 menjadi tonggak penting dengan resmi berdirinya Niswa Beauty bersama dua orang tim pertama. Sejak saat itu, usaha berkembang lebih profesional dengan pelayanan yang semakin lengkap dan terstruktur. Beberapa kerja sama dari luar kota hingga tawaran bergabung dengan brand kecantikan besar pernah datang, namun Niswa Beauty memilih untuk tetap berkembang secara mandiri.'
    ),
    'store_image'   => getProfil($conn,'profil','store_image',   'image/WhatsApp Image 2026-05-08 at 10.02.50.jpeg'),
    'tech_text'     => getProfil($conn,'profil','tech_text',
        'Niswà Beauty juga terus mengikuti perkembangan zaman. Berawal dari promosi sederhana melalui Story WhatsApp, kini hadir lebih luas lewat Instagram dan TikTok — termasuk penggunaan sistem pembayaran digital QRIS sejak awal tahun 2025. Hingga saat ini, Niswà Beauty terus berkembang untuk memberikan pengalaman kecantikan terbaik bagi setiap pelanggan.'
    ),
];

$salonName = getContent($conn,'kontak','salon_name','NISWÀ BEAUTY');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — <?= esc($salonName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
/* ── Layout Wrapper ── */
        .profil-page-body {
            background: linear-gradient(180deg, #fdfaf7 0%, #f5ede4 40%, #fdfaf7 100%);
            padding: 110px 0 100px;
        }

        /* ── Section Labels ── */
        .pp-section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f5ede4, #EADBC8);
            border: 1px solid rgba(214,193,163,0.5);
            color: #8B6F5E;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 18px;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 14px;
        }

        /* ══ OWNER SECTION — Simple ══ */
        .owner-section {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(139,111,94,0.10);
            border: 1px solid rgba(214,193,163,0.25);
            background: #fff;
        }

        /* ── Baris atas: avatar+identitas kiri | stats kanan ── */
        .owner-top-row {
            display: flex;
            align-items: center;
            padding: 44px 48px;
            gap: 48px;
            border-bottom: 1px solid rgba(214,193,163,0.18);
        }

        /* Avatar */
        .owner-avatar-ring {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #D6C1A3, #8B6F5E);
            padding: 3px;
            flex-shrink: 0;
        }
        .owner-avatar-inner {
            width: 100%; height: 100%; border-radius: 50%;
            background: #4a3a32;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 30px; color: #D6C1A3; font-weight: 700;
        }

        /* Identitas tengah */
        .owner-id-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .owner-crown-label {
            display: inline-flex; align-items: center; gap: 5px;
            color: #C4A882;
            font-size: 10px; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 2px;
        }
        .owner-name-hero {
            font-family: 'Playfair Display', serif;
            font-size: clamp(28px, 3.5vw, 40px);
            font-weight: 700; color: #2d1f17; line-height: 1.1; margin: 0;
        }
        .owner-tagline-hero {
            color: #A08B7A;
            font-style: italic; font-size: 13.5px;
            font-family: 'Playfair Display', serif;
            margin: 0;
        }

        /* Stats kanan — 4 item horizontal */
        .owner-stats-panel {
            display: flex;
            gap: 0;
            flex-shrink: 0;
            border: 1px solid rgba(214,193,163,0.2);
            border-radius: 16px;
            overflow: hidden;
            background: #fdfaf7;
        }
        .owner-stat-item {
            padding: 22px 28px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            gap: 4px;
            border-right: 1px solid rgba(214,193,163,0.18);
            min-width: 100px;
            transition: background 0.3s, transform 0.3s, box-shadow 0.3s;
            cursor: default;
        }
        .owner-stat-item:last-child { border-right: none; }
        .owner-stat-item:hover {
            background: linear-gradient(135deg, #f5ede4, #EADBC8);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(139,111,94,0.13);
        }
        .owner-stat-item:hover .owner-stat-icon { color: #8B6F5E; }
        .owner-stat-item:hover .owner-stat-num { color: #2d1f17; }
        .owner-stat-icon { font-size: 16px; color: #C4A882; }
        .owner-stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 700;
            color: #5A4A42; line-height: 1; margin: 0;
        }
        .owner-stat-label {
            font-family: 'Poppins', sans-serif;
            font-size: 9px; font-weight: 600;
            color: #bbb; letter-spacing: 1px; text-transform: uppercase;
            margin: 0;
        }

        /* ── Kutipan bio singkat full width ── */
        .owner-quote-bar {
            background: linear-gradient(135deg, #8B6F5E, #a68570);
            padding: 28px 52px;
            display: flex; align-items: center; gap: 20px;
        }
        .owner-quote-bar i {
            font-size: 28px; color: rgba(255,255,255,0.3); flex-shrink: 0;
        }
        .owner-quote-bar p {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-style: italic; font-size: 16px;
            color: rgba(255,255,255,0.92); line-height: 1.65;
        }

        /* ── Timeline horizontal strip ── */
        .owner-timeline-strip {
            background: #fdfaf7;
            border-top: 1px solid rgba(214,193,163,0.2);
            display: grid;
            grid-template-columns: repeat(5, 1fr);
        }
        .tl-step {
            padding: 28px 24px;
            border-right: 1px solid rgba(214,193,163,0.18);
            position: relative;
            transition: background 0.2s;
        }
        .tl-step:last-child { border-right: none; }
        .tl-step:hover { background: #f5ede4; }
        .tl-step::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8B6F5E, #D6C1A3);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .tl-step:hover::before { opacity: 1; }
        .tl-year {
            font-family: 'Playfair Display', serif;
            font-size: 20px; font-weight: 700;
            color: #8B6F5E; display: block; margin-bottom: 4px;
        }
        .tl-title {
            font-family: 'Poppins', sans-serif;
            font-size: 11px; font-weight: 700;
            color: #2d1f17; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .tl-text {
            font-family: 'Poppins', sans-serif;
            font-size: 12.5px; color: #999; line-height: 1.6;
        }

        /* ── Bio paragraf bawah ── */
        .owner-bio-panel {
            background: #fff;
            border-top: 1px solid rgba(214,193,163,0.2);
            padding: 44px 52px 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .owner-bio {
            color: #5A4A42; line-height: 1.95;
            font-size: 15px; font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        @media (max-width: 991px) {
            .owner-top-row { flex-wrap: wrap; padding: 32px 28px; gap: 28px; }
            .owner-stats-panel { width: 100%; }
            .owner-stat-item { flex: 1; min-width: 80px; padding: 18px 12px; }
            .owner-timeline-strip { grid-template-columns: repeat(3, 1fr); }
            .tl-step:nth-child(3) { border-right: none; }
            .tl-step:nth-child(4) { border-top: 1px solid rgba(214,193,163,0.18); }
            .owner-bio-panel { grid-template-columns: 1fr; gap: 20px; padding: 32px 28px; }
        }
        @media (max-width: 575px) {
            .owner-top-row { padding: 28px 20px; gap: 20px; }
            .owner-avatar-ring { width: 68px; height: 68px; }
            .owner-avatar-inner { font-size: 26px; }
            .owner-stat-num { font-size: 20px; }
            .owner-stat-item { padding: 16px 8px; }
            .owner-quote-bar { padding: 22px 24px; gap: 14px; }
            .owner-quote-bar p { font-size: 14px; }
            .owner-timeline-strip { grid-template-columns: 1fr 1fr; }
            .tl-step:nth-child(2) { border-right: none; }
            .tl-step:nth-child(3) { border-right: 1px solid rgba(214,193,163,0.18); border-top: 1px solid rgba(214,193,163,0.18); }
            .tl-step:nth-child(4) { border-top: 1px solid rgba(214,193,163,0.18); }
            .tl-step:nth-child(5) { grid-column: 1 / -1; border-right: none; border-top: 1px solid rgba(214,193,163,0.18); }
        }

        /* ── Store Section ── */
        .store-section {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 50px rgba(139,111,94,0.10);
            border: 1px solid rgba(214,193,163,0.25);
        }
        .store-img-col {
            position: relative;
        }
        .store-img-col img {
            width: 100%;
            height: 100%;
            min-height: 500px;
            object-fit: cover;
            display: block;
        }
        .store-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(90,74,66,0.6) 0%, transparent 50%);
        }
        .store-img-badge {
            position: absolute;
            bottom: 28px; left: 28px;
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 14px 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .store-img-badge .badge-date {
            font-size: 11px;
            font-weight: 600;
            color: #8B6F5E;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 2px;
        }
        .store-img-badge .badge-title {
            font-size: 14px;
            font-weight: 700;
            color: #2d1f17;
            font-family: 'Playfair Display', serif;
        }
        .store-content-col {
            padding: 52px 48px;
        }
        .store-name {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 700;
            color: #2d1f17;
            margin-bottom: 6px;
        }
        .store-tagline {
            color: #8B6F5E;
            font-style: italic;
            font-size: 15px;
            font-family: 'Playfair Display', serif;
            margin-bottom: 24px;
        }
        .store-bio {
            color: #5A4A42;
            line-height: 1.9;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 16px;
        }
        .store-values {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 32px;
        }
        .store-value-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fdfaf7;
            border: 1px solid rgba(214,193,163,0.3);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #5A4A42;
            font-family: 'Poppins', sans-serif;
            transition: background 0.3s, border-color 0.3s, transform 0.3s, box-shadow 0.3s;
            cursor: default;
        }
        .store-value-item:hover {
            background: linear-gradient(135deg, #f5ede4, #EADBC8);
            border-color: rgba(139,111,94,0.35);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(139,111,94,0.12);
        }
        .store-value-item i {
            color: #8B6F5E;
            font-size: 14px;
            width: 18px;
            text-align: center;
            transition: transform 0.3s;
        }
        .store-value-item:hover i {
            transform: scale(1.2);
        }

        /* ── Tech Section ── */
        .tech-card {
            background: linear-gradient(135deg, #5A4A42 0%, #8B6F5E 100%);
            border-radius: 28px;
            padding: 56px 60px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .tech-card::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 280px; height: 280px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .tech-card::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 220px; height: 220px;
            background: rgba(214,193,163,0.1);
            border-radius: 50%;
        }
        .tech-card .pp-section-label {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
            color: #EADBC8;
        }
        .tech-card h3 {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .tech-card p {
            color: rgba(255,255,255,0.85);
            font-size: 15px;
            line-height: 1.9;
            font-family: 'Poppins', sans-serif;
            max-width: 680px;
            margin: 0 auto 36px;
        }
        .tech-icons-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 28px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .tech-icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.85);
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }
        .tech-icon-item .icon-circle {
            width: 56px; height: 56px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            backdrop-filter: blur(6px);
            transition: all 0.3s;
        }
        .tech-icon-item:hover .icon-circle {
            background: rgba(255,255,255,0.22);
            transform: translateY(-4px);
        }

        /* ── Back Button ── */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #8B6F5E;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            padding: 10px 0;
            transition: gap 0.2s;
        }
        .back-btn:hover { color: #5A4A42; gap: 12px; }

        /* ── Divider ── */
        .pp-divider {
            border: none;
            border-top: 1px solid rgba(214,193,163,0.3);
            margin: 60px 0;
        }

        @media (max-width: 767px) {
            .store-content-col { padding: 32px 24px; }
            .tech-card { padding: 40px 28px; }
            .store-values { grid-template-columns: 1fr; }
            .store-img-col img { min-height: 280px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>


<!-- ── MAIN CONTENT ── -->
<div class="profil-page-body">
    <div class="container">

        <!-- ══ PEMILIK ══ -->
        <div class="pp-section-label mb-3" data-aos="fade-up"><i class="fas fa-user"></i>Tentang Pemilik</div>
        <div class="owner-section mb-5" data-aos="fade-up" data-aos-delay="100">

            <!-- Baris atas: avatar + identitas + stats -->
            <div class="owner-top-row">
                <div class="owner-avatar-ring">
                    <div class="owner-avatar-inner">N</div>
                </div>
                <div class="owner-id-panel">
                    <div class="owner-crown-label"><i class="fas fa-crown"></i> Pendiri & Pemilik</div>
                    <div class="owner-name-hero"><?= esc($profil['owner_name']) ?></div>
                    <div class="owner-tagline-hero"><?= esc($profil['owner_tagline']) ?></div>
                </div>
                <div class="owner-stats-panel">
                    <div class="owner-stat-item">
                        <div class="owner-stat-icon"><i class="fas fa-spa"></i></div>
                        <div class="owner-stat-num">7+</div>
                        <div class="owner-stat-label">Tahun Berkarya</div>
                    </div>
                    <div class="owner-stat-item">
                        <div class="owner-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="owner-stat-num">7+</div>
                        <div class="owner-stat-label">Pelanggan / Hari</div>
                    </div>
                    <div class="owner-stat-item">
                        <div class="owner-stat-icon"><i class="fas fa-store"></i></div>
                        <div class="owner-stat-num">2023</div>
                        <div class="owner-stat-label">Berdiri Resmi</div>
                    </div>
                    <div class="owner-stat-item">
                        <div class="owner-stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="owner-stat-num">Jepara</div>
                        <div class="owner-stat-label">Kota Berkarya</div>
                    </div>
                </div>
            </div>

            <!-- Kutipan bio singkat -->
            <div class="owner-quote-bar">
                <i class="fas fa-quote-left"></i>
                <p>Dari henna keliling door-to-door, hingga memimpin salon kecantikan yang dikenal se-Jepara — inilah perjalanan Niswa yang penuh semangat dan ketekunan.</p>
            </div>

            <!-- Timeline horizontal 5 kolom -->
            <div class="owner-timeline-strip">
                <div class="tl-step">
                    <span class="tl-year">Awal</span>
                    <div class="tl-title">Niswa Henna</div>
                    <div class="tl-text">Henna keliling rumah ke rumah di sekitar Jepara.</div>
                </div>
                <div class="tl-step">
                    <span class="tl-year">2018</span>
                    <div class="tl-title">Nail Art</div>
                    <div class="tl-text">Mulai nail art & fake nails dengan peralatan sederhana.</div>
                </div>
                <div class="tl-step">
                    <span class="tl-year">2019</span>
                    <div class="tl-title">Meluas</div>
                    <div class="tl-text">Supplier lokal, pelanggan dari Kudus, Tanjung & Semarang.</div>
                </div>
                <div class="tl-step">
                    <span class="tl-year">2020–21</span>
                    <div class="tl-title">Publik Figur</div>
                    <div class="tl-text">Dikenal publik figur lokal. Lebih dari 7 pelanggan/hari.</div>
                </div>
                <div class="tl-step">
                    <span class="tl-year">2022</span>
                    <div class="tl-title">Bertumbuh</div>
                    <div class="tl-text">Layanan seserahan & wedding berkembang pesat.</div>
                </div>
            </div>

            <!-- Bio paragraf bawah -->
            <div class="owner-bio-panel">
                <p class="owner-bio"><?= nl2br(esc($profil['owner_bio1'])) ?></p>
                <p class="owner-bio"><?= nl2br(esc($profil['owner_bio2'])) ?></p>
            </div>

        </div>

        <hr class="pp-divider">

        <!-- ══ TOKO ══ -->
        <div class="pp-section-label mb-3" data-aos="fade-up"><i class="fas fa-store"></i>Tentang Toko</div>
        <div class="store-section mb-5" data-aos="fade-up" data-aos-delay="100">
            <div class="row g-0">
                <div class="col-lg-5 store-img-col">
                    <img src="<?= esc($profil['store_image']) ?>" alt="<?= esc($profil['store_name']) ?>">
                    <div class="store-img-overlay"></div>
                    <div class="store-img-badge">
                        <div class="badge-date"><i class="fas fa-calendar-check me-1"></i>Est. 15 Juli 2023</div>
                        <div class="badge-title">NISWÀ BEAUTY</div>
                    </div>
                </div>
                <div class="col-lg-7 store-content-col">
                    <div class="store-name"><?= esc($profil['store_name']) ?></div>
                    <div class="store-tagline"><?= esc($profil['store_tagline']) ?></div>
                    <p class="store-bio"><?= nl2br(esc($profil['store_bio1'])) ?></p>
                    <p class="store-bio"><?= nl2br(esc($profil['store_bio2'])) ?></p>
                    <div class="store-values">
                        <div class="store-value-item"><i class="fas fa-heart"></i>Pelayanan Tulus</div>
                        <div class="store-value-item"><i class="fas fa-shield-alt"></i>Produk Aman & Halal</div>
                        <div class="store-value-item"><i class="fas fa-star"></i>Kualitas Premium</div>
                        <div class="store-value-item"><i class="fas fa-smile"></i>Kepuasan Pelanggan</div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="pp-divider">

        <!-- ══ TEKNOLOGI ══ -->
        <div class="tech-card" data-aos="fade-up">
            <div class="pp-section-label"><i class="fas fa-history"></i>Perjalanan Teknologi</div>
            <h3>Dari Analog ke Digital</h3>
            <p><?= nl2br(esc($profil['tech_text'])) ?></p>
            <div class="tech-icons-row">
                <div class="tech-icon-item">
                    <div class="icon-circle"><i class="fab fa-whatsapp"></i></div>
                    <span>WhatsApp Story</span>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle" style="font-size:14px;color:#D6C1A3;">→</div>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle"><i class="fab fa-instagram"></i></div>
                    <span>Instagram</span>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle" style="font-size:14px;color:#D6C1A3;">→</div>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle"><i class="fab fa-tiktok"></i></div>
                    <span>TikTok</span>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle" style="font-size:14px;color:#D6C1A3;">→</div>
                </div>
                <div class="tech-icon-item">
                    <div class="icon-circle"><i class="fas fa-qrcode"></i></div>
                    <span>QRIS 2025</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ── FOOTER CTA ── -->
<section style="background:#fdfaf7;padding:60px 0;text-align:center;border-top:1px solid rgba(214,193,163,0.2);">
    <div class="container">
        <p style="font-family:'Playfair Display',serif;font-size:22px;color:#2d1f17;margin-bottom:8px;">
            Ingin merasakan sentuhan kecantikan Niswà?
        </p>
        <p style="color:#8B6F5E;font-size:14px;margin-bottom:28px;font-family:'Poppins',sans-serif;">
            Booking sekarang dan rasakan perbedaannya
        </p>
        <a href="booking.php"
           style="display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;text-decoration:none;padding:14px 36px;border-radius:50px;font-weight:600;font-family:'Poppins',sans-serif;font-size:15px;box-shadow:0 8px 30px rgba(139,111,94,0.3);transition:all 0.3s;">
            <i class="fas fa-calendar-check"></i> Book Now
        </a>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>AOS.init({ once: true, duration: 700 });</script>
<script src="script.js"></script>

<?php include 'footer.php'; ?>

</body>
</html>