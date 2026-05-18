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
    'value_item_1'  => getProfil($conn,'profil','value_item_1',  'Pelayanan Tulus'),
    'value_item_2'  => getProfil($conn,'profil','value_item_2',  'Produk Aman & Halal'),
    'value_item_3'  => getProfil($conn,'profil','value_item_3',  'Kualitas Premium'),
    'value_item_4'  => getProfil($conn,'profil','value_item_4',  'Kepuasan Pelanggan'),
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ══ DESIGN TOKENS ══ */
        :root {
            --cream:      #FAF7F2;
            --warm-white: #FFFCF8;
            --nude-50:    #F5EDE3;
            --nude-100:   #EAD9C8;
            --nude-200:   #D6BFA6;
            --nude-300:   #C4A882;
            --bronze:     #A07850;
            --mocha:      #7A5C40;
            --espresso:   #4A3020;
            --ink:        #1E130A;
            --gold:       #C9A96E;
            --gold-light: #E8D5A8;

            --serif: 'Cormorant Garamond', Georgia, serif;
            --sans:  'DM Sans', sans-serif;

            --radius-sm:  10px;
            --radius-md:  20px;
            --radius-lg:  32px;
            --radius-xl:  48px;

            --shadow-soft: 0 2px 20px rgba(74,48,32,.07);
            --shadow-med:  0 8px 40px rgba(74,48,32,.11);
            --shadow-deep: 0 20px 80px rgba(74,48,32,.16);
        }

        /* ══ BASE ══ */
        *, *::before, *::after { box-sizing: border-box; }

        .profil-page-body {
            background: var(--cream);
            padding: 120px 0 80px;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Subtle background texture */
        .profil-page-body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 900px 600px at 10% 20%, rgba(201,169,110,.08) 0%, transparent 70%),
                radial-gradient(ellipse 700px 500px at 90% 80%, rgba(160,120,80,.07) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .profil-page-body > .container { position: relative; z-index: 1; }

        /* ══ SECTION PILL ══ */
        .pp-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg, var(--warm-white), var(--nude-50));
            border: 1px solid var(--nude-200);
            color: var(--bronze);
            font-family: var(--sans);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            padding: 7px 20px;
            border-radius: 50px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-soft);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .pp-pill:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-med);
        }
        .pp-pill i { font-size: 9px; color: var(--gold); }

        /* ══ THIN DIVIDER ══ */
        .pp-rule {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--nude-200), transparent);
            margin: 60px 0;
        }

        /* ════════════════════════════════════
           OWNER CARD
        ════════════════════════════════════ */
        .owner-card {
            background: var(--warm-white);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(214,191,166,.35);
            box-shadow: var(--shadow-deep);
            overflow: hidden;
        }

        /* — Hero header strip — */
        .owner-hero {
            position: relative;
            background: linear-gradient(135deg, var(--espresso) 0%, var(--mocha) 45%, var(--bronze) 100%);
            padding: 52px 56px 48px;
            display: flex;
            align-items: center;
            gap: 40px;
            overflow: hidden;
        }
        .owner-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,169,110,.18) 0%, transparent 70%);
        }
        .owner-hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: 30%;
            width: 400px; height: 200px;
            border-radius: 50%;
            background: radial-gradient(ellipse, rgba(255,252,248,.05) 0%, transparent 70%);
        }

        /* Avatar */
        .owner-avatar {
            position: relative;
            width: 96px; height: 96px;
            flex-shrink: 0;
            z-index: 1;
        }
        .owner-avatar-circle {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            display: flex; align-items: center; justify-content: center;
            font-family: var(--serif);
            font-size: 38px; font-weight: 700;
            color: var(--espresso);
            border: 3px solid rgba(255,252,248,.25);
            transition: transform .4s cubic-bezier(.34,1.56,.64,1), box-shadow .3s;
            cursor: default;
        }
        .owner-avatar-circle:hover {
            transform: scale(1.08) rotate(4deg);
            box-shadow: 0 0 0 6px rgba(201,169,110,.25);
        }
        .owner-avatar-badge {
            position: absolute;
            bottom: -2px; right: -4px;
            background: var(--gold);
            color: var(--espresso);
            border-radius: 50%;
            width: 26px; height: 26px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            border: 2px solid var(--warm-white);
        }

        /* Identity text */
        .owner-identity { flex: 1; z-index: 1; }
        .owner-role-tag {
            font-family: var(--sans);
            font-size: 13px; font-weight: 600;
            letter-spacing: 3px;
            color: var(--gold-light);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .owner-name {
            font-family: var(--serif);
            font-size: clamp(38px, 4.5vw, 59px);
            font-weight: 600;
            color: var(--warm-white);
            line-height: 1.0;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .owner-quote-inline {
            font-family: var(--serif);
            font-style: italic;
            font-size: 18px;
            color: rgba(250,247,242,.95);
            line-height: 1.5;
        }

        /* Stats row */
        .owner-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            z-index: 1;
            flex-shrink: 0;
        }
        .owner-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 22px 16px;
            border-left: 1px solid rgba(255,252,248,.10);
            text-align: center;
            transition: background .25s;
            cursor: default;
        }
        .owner-stat:hover { background: rgba(255,252,248,.06); }
        .owner-stat-icon {
            font-size: 14px;
            color: var(--gold-light);
            margin-bottom: 4px;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1);
        }
        .owner-stat:hover .owner-stat-icon { transform: scale(1.3) rotate(-8deg); }
        .owner-stat-val {
            font-family: var(--serif);
            font-size: 27px; font-weight: 700;
            color: var(--warm-white);
            line-height: 1;
        }
        .owner-stat-lbl {
            font-family: var(--sans);
            font-size: 12px; font-weight: 500;
            color: rgba(255,252,248,.80);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Pull quote bar */
        .owner-pull-quote {
            background: linear-gradient(90deg, var(--nude-50), var(--warm-white));
            border-left: 4px solid var(--gold);
            padding: 28px 52px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .owner-pull-quote .pq-mark {
            font-family: var(--serif);
            font-size: 74px;
            line-height: .6;
            color: var(--nude-200);
            flex-shrink: 0;
            align-self: flex-start;
            margin-top: -4px;
        }
        .owner-pull-quote p {
            margin: 0;
            font-family: var(--serif);
            font-size: 21px;
            font-style: italic;
            color: var(--espresso);
            line-height: 1.7;
        }

        /* Timeline */
        .owner-timeline {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            background: var(--cream);
            border-top: 1px solid rgba(214,191,166,.3);
        }
        .tl-node {
            padding: 32px 24px 28px;
            border-right: 1px solid rgba(214,191,166,.25);
            position: relative;
            transition: background .2s, transform .2s;
            cursor: default;
        }
        .tl-node:last-child { border-right: none; }
        .tl-node::after {
            content: '';
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--bronze), var(--gold));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }
        .tl-node:hover::after { transform: scaleX(1); }
        .tl-node:hover { background: var(--nude-50); }
        .tl-node-year {
            font-family: var(--serif);
            font-size: 27px;
            font-weight: 600;
            color: var(--bronze);
            margin-bottom: 6px;
            display: block;
        }
        .tl-node-title {
            font-family: var(--sans);
            font-size: 14px;
            font-weight: 600;
            color: var(--espresso);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .tl-node-text {
            font-family: var(--sans);
            font-size: 14px;
            color: var(--espresso);
            line-height: 1.65;
            opacity: 1;
        }

        /* Bio columns */
        .owner-bio-block {
            background: var(--warm-white);
            border-top: 1px solid rgba(214,191,166,.25);
            padding: 52px 56px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: start;
        }
        .owner-bio-text {
            font-family: var(--sans);
            font-size: 17px;
            color: var(--espresso);
            line-height: 2.0;
            margin: 0;
        }
        .owner-bio-text + .owner-bio-text { color: var(--espresso); }

        /* ════════════════════════════════════
           STORE SECTION
        ════════════════════════════════════ */
        .store-card {
            background: var(--warm-white);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(214,191,166,.35);
            box-shadow: var(--shadow-deep);
            overflow: hidden;
        }

        /* Two-column layout */
        .store-layout {
            display: grid;
            grid-template-columns: 420px 1fr;
            min-height: 600px;
        }

        /* Image column */
        .store-img-wrap {
            position: relative;
            overflow: hidden;
        }
        .store-img-wrap img {
            width: 100%;
            height: 100%;
            min-height: 580px;
            object-fit: cover;
            display: block;
            transition: transform .6s ease;
        }
        .store-img-wrap:hover img { transform: scale(1.04); }
        .store-img-grad {
            position: absolute; inset: 0;
            background: linear-gradient(
                to top,
                rgba(30,19,10,.75) 0%,
                rgba(30,19,10,.2) 40%,
                transparent 70%
            );
        }
        /* Floating badge */
        .store-float-badge {
            position: absolute;
            bottom: 32px; left: 28px;
            right: 28px;
            background: rgba(250,247,242,.96);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-md);
            padding: 18px 22px;
            border: 1px solid rgba(201,169,110,.3);
            box-shadow: 0 12px 40px rgba(30,19,10,.2);
        }
        .store-badge-est {
            font-family: var(--sans);
            font-size: 13px;
            font-weight: 600;
            color: var(--bronze);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .store-badge-name {
            font-family: var(--serif);
            font-size: 24px;
            font-weight: 600;
            color: var(--espresso);
            letter-spacing: 1px;
        }
        /* Decorative corner ornament */
        .store-img-wrap::before {
            content: '✦';
            position: absolute;
            top: 24px; right: 24px;
            font-size: 23px;
            color: rgba(250,247,242,.5);
            z-index: 1;
        }

        /* Content column */
        .store-content {
            padding: 52px 52px 52px 56px;
            display: flex;
            flex-direction: column;
        }
        .store-eyebrow {
            font-family: var(--sans);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .store-name-head {
            font-family: var(--serif);
            font-size: clamp(29px, 3vw, 41px);
            font-weight: 600;
            color: var(--espresso);
            line-height: 1.1;
            margin-bottom: 10px;
            letter-spacing: .5px;
        }
        .store-tagline-text {
            font-family: var(--serif);
            font-style: italic;
            font-size: 18px;
            color: var(--bronze);
            margin-bottom: 32px;
        }
        /* Thin accent line */
        .store-accent-line {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--bronze));
            border-radius: 2px;
            margin-bottom: 28px;
        }
        .store-bio-para {
            font-family: var(--sans);
            font-size: 17px;
            color: var(--espresso);
            line-height: 1.95;
            margin-bottom: 14px;
        }
        .store-bio-para:last-of-type { color: var(--espresso); }

        /* Values grid */
        .store-values {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: auto;
            padding-top: 32px;
        }
        .store-val-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--cream);
            border: 1px solid rgba(214,191,166,.4);
            border-radius: var(--radius-sm);
            padding: 13px 16px;
            font-family: var(--sans);
            font-size: 15px;
            font-weight: 500;
            color: var(--espresso);
            transition: background .25s, transform .25s, box-shadow .25s;
            cursor: default;
        }
        .store-val-chip:hover {
            background: var(--nude-50);
            transform: translateY(-3px);
            box-shadow: var(--shadow-med);
            border-color: var(--nude-200);
        }
        .store-val-chip i {
            color: var(--gold);
            font-size: 14px;
            width: 16px;
            text-align: center;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1);
        }
        .store-val-chip:hover i { transform: scale(1.35) rotate(-10deg); }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 1100px) {
            .store-layout { grid-template-columns: 1fr; }
            .store-img-wrap img { min-height: 360px; }
            .store-content { padding: 40px 36px; }
        }
        @media (max-width: 991px) {
            .owner-hero { padding: 36px 32px; gap: 24px; flex-wrap: wrap; }
            .owner-stats-row { grid-template-columns: repeat(4, 1fr); width: 100%; }
            .owner-bio-block { grid-template-columns: 1fr; padding: 36px 32px; gap: 24px; }
            .owner-timeline { grid-template-columns: repeat(3, 1fr); }
            .tl-node:nth-child(3) { border-right: none; }
            .tl-node:nth-child(4) { border-top: 1px solid rgba(214,191,166,.25); }
            .owner-pull-quote { padding: 24px 32px; }
        }
        @media (max-width: 640px) {
            .profil-page-body { padding: 100px 0 60px; }
            .owner-hero { padding: 28px 22px; }
            .owner-stats-row { grid-template-columns: repeat(2, 1fr); }
            .owner-stat:nth-child(2) { border-left: 1px solid rgba(255,252,248,.10); }
            .owner-stat:nth-child(3) { border-top: 1px solid rgba(255,252,248,.10); }
            .owner-timeline { grid-template-columns: 1fr 1fr; }
            .tl-node:nth-child(2) { border-right: none; }
            .tl-node:nth-child(3) { border-top: 1px solid rgba(214,191,166,.25); border-right: 1px solid rgba(214,191,166,.25); }
            .tl-node:nth-child(5) { grid-column: 1 / -1; border-right: none; border-top: 1px solid rgba(214,191,166,.25); }
            .owner-bio-block { padding: 28px 22px; }
            .store-val-chip { font-size: 13px; padding: 11px 12px; }
            .store-values { grid-template-columns: 1fr; }
            .owner-pull-quote { padding: 22px 22px; }
            .owner-pull-quote .pq-mark { font-size: 50px; }
            .owner-pull-quote p { font-size: 15px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- ── MAIN CONTENT ── -->
<div class="profil-page-body">
    <div class="container">

        <!-- ════ PEMILIK ════ -->
        <div class="pp-pill" data-aos="fade-up">
            <i class="fas fa-feather-alt"></i>Tentang Pemilik
        </div>

        <div class="owner-card mb-5" data-aos="fade-up" data-aos-delay="80">

            <!-- Hero Header -->
            <div class="owner-hero">
                <div class="owner-avatar">
                    <div class="owner-avatar-circle"><?= mb_substr(esc($profil['owner_name']), 0, 1) ?></div>
                    <div class="owner-avatar-badge"><i class="fas fa-crown"></i></div>
                </div>
                <div class="owner-identity">
                    <div class="owner-role-tag"><i class="fas fa-spa" style="margin-right:5px;opacity:.7"></i>Pendiri & Pemilik</div>
                    <div class="owner-name"><?= esc($profil['owner_name']) ?></div>
                    <div class="owner-quote-inline"><?= esc($profil['owner_tagline']) ?></div>
                </div>
                <div class="owner-stats-row">
                    <div class="owner-stat">
                        <div class="owner-stat-icon"><i class="fas fa-spa"></i></div>
                        <div class="owner-stat-val">7+</div>
                        <div class="owner-stat-lbl">Tahun Berkarya</div>
                    </div>
                    <div class="owner-stat">
                        <div class="owner-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="owner-stat-val">7+</div>
                        <div class="owner-stat-lbl">Klien / Hari</div>
                    </div>
                    <div class="owner-stat">
                        <div class="owner-stat-icon"><i class="fas fa-store"></i></div>
                        <div class="owner-stat-val">2023</div>
                        <div class="owner-stat-lbl">Berdiri Resmi</div>
                    </div>
                    <div class="owner-stat">
                        <div class="owner-stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="owner-stat-val">Jepara</div>
                        <div class="owner-stat-lbl">Kota Berkarya</div>
                    </div>
                </div>
            </div>

            <!-- Pull Quote -->
            <div class="owner-pull-quote">
                <span class="pq-mark">&ldquo;</span>
                <p>Dari henna keliling door-to-door, hingga memimpin salon kecantikan yang dikenal se-Jepara — inilah perjalanan Niswa yang penuh semangat dan ketekunan.</p>
            </div>

            <!-- Timeline Strip -->
            <div class="owner-timeline">
                <div class="tl-node">
                    <span class="tl-node-year">Awal</span>
                    <div class="tl-node-title">Niswa Henna</div>
                    <div class="tl-node-text">Henna keliling rumah ke rumah di sekitar Jepara.</div>
                </div>
                <div class="tl-node">
                    <span class="tl-node-year">2018</span>
                    <div class="tl-node-title">Nail Art</div>
                    <div class="tl-node-text">Mulai nail art & fake nails dengan peralatan sederhana.</div>
                </div>
                <div class="tl-node">
                    <span class="tl-node-year">2019</span>
                    <div class="tl-node-title">Meluas</div>
                    <div class="tl-node-text">Supplier lokal, pelanggan dari Kudus, Tanjung & Semarang.</div>
                </div>
                <div class="tl-node">
                    <span class="tl-node-year">2020–21</span>
                    <div class="tl-node-title">Publik Figur</div>
                    <div class="tl-node-text">Dikenal publik figur lokal. Lebih dari 7 pelanggan/hari.</div>
                </div>
                <div class="tl-node">
                    <span class="tl-node-year">2022</span>
                    <div class="tl-node-title">Bertumbuh</div>
                    <div class="tl-node-text">Layanan seserahan & wedding berkembang pesat.</div>
                </div>
            </div>

            <!-- Bio Block -->
            <div class="owner-bio-block">
                <p class="owner-bio-text"><?= nl2br(esc($profil['owner_bio1'])) ?></p>
                <p class="owner-bio-text"><?= nl2br(esc($profil['owner_bio2'])) ?></p>
            </div>

        </div>

        <hr class="pp-rule">

        <!-- ════ TOKO ════ -->
        <div class="pp-pill" data-aos="fade-up">
            <i class="fas fa-store"></i>Tentang Toko
        </div>

        <div class="store-card mb-5" data-aos="fade-up" data-aos-delay="80">
            <div class="store-layout">

                <!-- Image -->
                <div class="store-img-wrap">
                    <img src="<?= esc($profil['store_image']) ?>" alt="<?= esc($profil['store_name']) ?>">
                    <div class="store-img-grad"></div>
                    <div class="store-float-badge">
                        <div class="store-badge-est"><i class="fas fa-calendar-check" style="margin-right:5px"></i>Est. 15 Juli 2023</div>
                        <div class="store-badge-name">NISWÀ BEAUTY</div>
                    </div>
                </div>

                <!-- Content -->
                <div class="store-content">
                    <div class="store-eyebrow">Beauty Studio · Jepara</div>
                    <div class="store-name-head"><?= esc($profil['store_name']) ?></div>
                    <div class="store-tagline-text"><?= esc($profil['store_tagline']) ?></div>
                    <div class="store-accent-line"></div>
                    <p class="store-bio-para"><?= nl2br(esc($profil['store_bio1'])) ?></p>
                    <p class="store-bio-para"><?= nl2br(esc($profil['store_bio2'])) ?></p>
                    <div class="store-values">
                        <div class="store-val-chip"><i class="fas fa-heart"></i><?= esc($profil['value_item_1']) ?></div>
                        <div class="store-val-chip"><i class="fas fa-shield-alt"></i><?= esc($profil['value_item_2']) ?></div>
                        <div class="store-val-chip"><i class="fas fa-star"></i><?= esc($profil['value_item_3']) ?></div>
                        <div class="store-val-chip"><i class="fas fa-smile"></i><?= esc($profil['value_item_4']) ?></div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>AOS.init({ once: true, duration: 800, easing: 'ease-out-cubic' });</script>
<script src="script.js"></script>

<?php include 'footer.php'; ?>

</body>
</html>