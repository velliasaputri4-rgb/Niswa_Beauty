<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$pageTitle  = "NISWÀ BEAUTY — Premium Beauty Experience";
$isLoggedIn = isset($_SESSION['user']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin');
$userName   = $_SESSION['user'] ?? '';

// Koneksi (untuk order produk)
$conn = @mysqli_connect("localhost", "root", "", "salon_db");
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    // Buat tabel orders jika belum ada
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
        created_at DATETIME DEFAULT NOW()
    )");
    // AUTO-FIX: tambah kolom yang mungkin belum ada
    $fixCols = [
        "user_id" => "ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL",
        "catatan"  => "ALTER TABLE orders ADD COLUMN catatan TEXT",
        "total"    => "ALTER TABLE orders ADD COLUMN total VARCHAR(20)",
    ];
    foreach ($fixCols as $col => $sql) {
        $cek = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$col'");
        if ($cek && mysqli_num_rows($cek) === 0) {
            mysqli_query($conn, $sql);
        }
    }
}

// Handle AJAX order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $nama          = trim($_POST['nama']          ?? '');
    $whatsapp      = trim($_POST['whatsapp']      ?? '');
    $alamat        = trim($_POST['alamat']        ?? '');
    $product_name  = trim($_POST['product_name']  ?? '');
    $product_price = trim($_POST['product_price'] ?? '');
    $qty           = max(1, (int)($_POST['qty']   ?? 1));
    $catatan       = trim($_POST['catatan']       ?? '');
    $user_id       = $_SESSION['user_id'] ?? null;

    $harga_num = (int) preg_replace('/[^0-9]/', '', $product_price);
    $total     = 'Rp ' . number_format($harga_num * $qty, 0, ',', '.');

    $errors = [];
    if (empty($nama))     $errors[] = 'Nama wajib diisi.';
    if (empty($whatsapp)) $errors[] = 'WhatsApp wajib diisi.';
    if (empty($alamat))   $errors[] = 'Alamat wajib diisi.';

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    } elseif (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (user_id, nama, whatsapp, alamat, product_name, product_price, qty, total, catatan)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isssssiss",
            $user_id, $nama, $whatsapp, $alamat, $product_name, $product_price, $qty, $total, $catatan
        );
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="Salon kecantikan premium di Semarang. Layanan profesional untuk hair, skin, nail & lebih.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css?v=5.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-cream {
            background: #CBB89D; color: #fff; border: none; border-radius: 50px;
            padding: 12px 32px; font-weight: 600; font-size: 15px; letter-spacing: 0.5px;
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(203,184,157,0.4);
        }
        .btn-cream:hover {
            background: #b8a082; color: #fff;
            transform: translateY(-2px); box-shadow: 0 8px 25px rgba(203,184,157,0.5);
        }

        /* ── Product Section ── */
        .section-product { background: #fdfaf7; padding: 80px 0; }
        .product-section-title { text-align: center; margin-bottom: 40px; }
        .product-section-title h2 { font-weight: 600; font-size: 32px; font-family: 'Playfair Display', serif; }
        .product-section-title span { color: #8B6F5E; }
        .product-section-title p { color: #777; margin-top: 8px; }
        .filter-buttons { text-align: center; margin-bottom: 40px; }
        .filter-buttons button {
            border: none; background: none; margin: 5px 12px;
            font-weight: 500; color: #888; cursor: pointer;
            font-size: 16px; font-family: 'Poppins', sans-serif; transition: 0.2s;
        }
        .filter-buttons button.active { color: #8B6F5E; border-bottom: 2px solid #8B6F5E; }
        .product-card {
            background: #fff; border-radius: 20px; overflow: hidden; transition: 0.3s; cursor: pointer;
        }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(139,111,94,0.15); }
        .product-img { width: 100%; height: 260px; object-fit: cover; }
        .product-info { padding: 15px; }
        .product-name { font-weight: 500; font-family: 'Poppins', sans-serif; }
        .product-price { color: #8B6F5E; font-weight: 600; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 20px; }
        .btn-beli {
            width: 100%; padding: 8px 0; margin-top: 8px;
            background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
            color: #fff; border: none; border-radius: 10px;
            font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
            cursor: pointer; transition: 0.2s;
        }
        .btn-beli:hover { opacity: 0.85; }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div id="loadingScreen">
    <div class="loading-logo">NISWÀ BEAUTY</div>
    <div class="loading-bar-wrap"><div class="loading-bar"></div></div>
    <div class="loading-text">Loading Beauty...</div>
</div>

<?php include 'navbar.php'; ?>

<!-- HERO -->
<section class="hero-slider">
    <div id="heroCarousel" class="carousel slide carousel-fade">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="image/homenailart.jpeg" class="d-block w-100" alt="">
                <div class="carousel-caption">
                    <h1>Temukan Kecantikan Terbaikmu</h1>
                    <p>Layanan premium untuk tampilan terbaik Anda</p>
                    <div class="hero-btn-group">
                        <a href="booking.php" class="hero-btn-primary">
                            <i class="fas fa-calendar-alt"></i> Reservasi Sekarang
                        </a>
                        <a href="#layanan" class="hero-btn-outline">
                            Lihat Layanan <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/rambut.jpeg" class="d-block w-100" alt="">
                <div class="carousel-caption">
                    <h1>Tampil Lebih Percaya Diri</h1>
                    <p>Kecantikan dimulai dari sini</p>
                    <div class="hero-btn-group">
                        <a href="booking.php" class="hero-btn-primary">
                            <i class="fas fa-calendar-alt"></i> Reservasi Sekarang
                        </a>
                        <a href="#layanan" class="hero-btn-outline">
                            Lihat Layanan <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="image/nailart.jpg" class="d-block w-100" alt="">
                <div class="carousel-caption">
                    <h1>Perawatan Profesional</h1>
                    <p>Hair, Lash, Henna dan nail terbaik</p>
                    <div class="hero-btn-group">
                        <a href="booking.php" class="hero-btn-primary">
                            <i class="fas fa-calendar-alt"></i> Reservasi Sekarang
                        </a>
                        <a href="#layanan" class="hero-btn-outline">
                            Lihat Layanan <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Dot Indicators -->
        <div class="carousel-indicators hero-dots">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
    </div>
</section>

<!-- SERVICES GRID -->
<section id="layanan" class="services-clean py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="services-title">Layanan Kami</h2>
            <div class="title-line"></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/download (7).jpg" alt="">
                    <div class="overlay">Haircut</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/coloring.jpg" alt="">
                    <div class="overlay">Coloring</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/nailart.jpeg" alt="">
                    <div class="overlay">Nailart</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/⠀ _рабочее пространство мастера реконструкции - это не всегда про эстетку волос , иногда это про пыль, размокшие пальцы, и волосы в тех местах, где ты их не ожидаешь увидеть 😅.jpg" alt="">
                    <div class="overlay">Hair Treatments</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/download (8).jpg" alt="">
                    <div class="overlay">Foot SPA</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/henna.jpg" alt="">
                    <div class="overlay">Henna Series</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/download (6).jpg" alt="">
                    <div class="overlay">Press on nail</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box">
                    <img src="image/eyelash.jpeg" alt="">
                    <div class="overlay">Eye lash</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DAFTAR HARGA -->
<section id="harga" class="price-list-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="services-title">Daftar <span style="color:#8B6F5E;">Harga</span></h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2">Harga transparan, kualitas terjamin</p>
        </div>

        <?php
        $priceList = [
            'Rambut' => [
                ['name'=>'Creambath',                        'price'=>'Rp 75.000'],
                ['name'=>'Hair Mask',                        'price'=>'Rp 45.000 - 90.000'],
                ['name'=>'Hair Spa',                         'price'=>'Rp 100.000'],
                ['name'=>'Cuci Rambut',                      'price'=>'Rp 25.000'],
                ['name'=>'Cuci + Catok',                     'price'=>'Rp 40.000'],
                ['name'=>'Cuci + Blow',                      'price'=>'Rp 48.000'],
                ['name'=>'Bleaching S',                      'price'=>'Rp 40.000'],
                ['name'=>'Coloring Full',                    'price'=>'Rp 120.000 - 300.000'],
                ['name'=>'Bleaching Peek A Boo',             'price'=>'Rp 200.000 - 350.000'],
                ['name'=>'Bleaching Highlight',              'price'=>'Rp 250.000 - 700.000'],
                ['name'=>'Balayage',                         'price'=>'Rp 250.000 - 700.000'],
                ['name'=>'Bleaching Full',                   'price'=>'Rp 250.000 - 1.200.000'],
                ['name'=>'Down Peim Poni',                   'price'=>'Rp 100.000 - 300.000'],
                ['name'=>'Keriting Klasik',                  'price'=>'Rp 300.000 - 700.000'],
                ['name'=>'Keriting Digital',                 'price'=>'Rp 450.000 - 1.700.000'],
                ['name'=>'Keratin Treatment',                'price'=>'Rp 200.000'],
                ['name'=>'Smoothing Collagen - Lamei',       'price'=>'Rp 280.000'],
                ['name'=>'Smoothing Collagen - L\'Oreal',    'price'=>'Rp 300.000'],
                ['name'=>'Smoothing Sutra - Inaura',         'price'=>'Rp 150.000'],
                ['name'=>'Smoothing Sutra - Matrix',         'price'=>'Rp 175.000'],
                ['name'=>'Smoothing Sutra - Go Street',      'price'=>'Rp 200.000'],
                ['name'=>'Smoothing Sutra - Silky',          'price'=>'Rp 250.000'],
                ['name'=>'Smoothing Keratin - SDB',          'price'=>'Rp 350.000'],
                ['name'=>'Smoothing Keratin - Eljo',         'price'=>'Rp 380.000'],
                ['name'=>'Smoothing Keratin - Gylo',         'price'=>'Rp 400.000'],
                ['name'=>'Smoothing Expres',                 'price'=>'Rp 150.000'],
                ['name'=>'Smoothing Keratin Expres',         'price'=>'Rp 300.000'],
                ['name'=>'Smoothing Crystal',                'price'=>'Rp 300.000'],
            ],
            'Treatment Spa' => [
                ['name'=>'Bundling Manicure & Pedicure',     'price'=>'Rp 100.000'],
                ['name'=>'Manicure / Pedicure',              'price'=>'Rp 60.000'],
                ['name'=>'Hand Spa',                         'price'=>'Rp 80.000'],
                ['name'=>'Foot Spa',                         'price'=>'Rp 100.000'],
                ['name'=>'Callus Treatment',                 'price'=>'Rp 70.000 - 150.000'],
            ],
            'Henna Series' => [
                ['name'=>'Brow Henna',                       'price'=>'Rp 25.000'],
                ['name'=>'Nail Henna Tangan',                'price'=>'Rp 25.000'],
                ['name'=>'Nail Henna Kaki',                  'price'=>'Rp 30.000'],
                ['name'=>'Bundling Meni-Henna',              'price'=>'Rp 75.000'],
                ['name'=>'Henna Fun',                        'price'=>'Rp 25.000 - 100.000'],
            ],
            'Nail Art & Services' => [
                ['name'=>'Press On Nail Basic',              'price'=>'Rp 50.000'],
                ['name'=>'Press On Nail Motif',              'price'=>'Rp 75.000'],
                ['name'=>'Kids Basic Gel',                   'price'=>'Rp 40.000'],
                ['name'=>'Kids Gel + 4 Sticker',             'price'=>'Rp 50.000'],
                ['name'=>'Kids Gel + Full Sticker',          'price'=>'Rp 55.000'],
                ['name'=>'Gel Basic Tangan / Kaki',          'price'=>'Rp 85.000'],
                ['name'=>'Extension',                        'price'=>'Rp 50.000'],
                ['name'=>'Gel French / Cat Eyes',            'price'=>'Rp 105.000'],
                ['name'=>'Remove Gel',                       'price'=>'Rp 50.000'],
                ['name'=>'Gel Ombre / Blush On',             'price'=>'Rp 135.000'],
                ['name'=>'Remove Extension',                 'price'=>'Rp 65.000'],
                ['name'=>'Bundling Nail Art + Extension',    'price'=>'Rp 150.000'],
            ],
            'Brow & Lash' => [
                ['name'=>'Brow Bomb',      'price'=>'Rp 100.000'],
                ['name'=>'Lashlift',       'price'=>'Rp 70.000'],
                ['name'=>'Lashlift Tint',  'price'=>'Rp 90.000'],
            ],
        ];
        $icons = [
            'Rambut'              => 'fas fa-cut',
            'Treatment Spa'       => 'fas fa-spa',
            'Henna Series'        => 'fas fa-leaf',
            'Nail Art & Services' => 'fas fa-hand-sparkles',
            'Brow & Lash'         => 'fas fa-eye',
        ];
        $delay = 0;
        foreach ($priceList as $cat => $items):
            $catId = 'price-' . preg_replace('/[^a-z0-9]/', '-', strtolower($cat));
        ?>
        <div class="price-accordion mb-3" data-aos="fade-up" data-aos-delay="<?= $delay * 80 ?>">
            <button class="price-acc-btn" onclick="togglePrice('<?= $catId ?>', this)" aria-expanded="false">
                <span class="price-acc-left">
                    <span class="price-acc-icon-wrap"><i class="<?= $icons[$cat] ?>"></i></span>
                    <span class="price-acc-label"><?= $cat ?></span>
                    <span class="price-acc-count"><?= count($items) ?> layanan</span>
                </span>
                <span class="price-acc-toggle">
                    <i class="fas fa-chevron-down"></i>
                </span>
            </button>
            <div class="price-acc-body" id="<?= $catId ?>" style="display:none;">
                <table class="price-table">
                    <thead>
                        <tr>
                            <th>Layanan</th>
                            <th class="text-end">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td class="text-end price-cell"><?= $row['price'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $delay++; endforeach; ?>

        <div class="text-center mt-4" data-aos="fade-up">
            <p class="text-muted small mb-3">* Harga dapat berubah sewaktu-waktu. Hubungi kami untuk info terkini.</p>
            <a href="booking.php" class="btn btn-cream">
                <i class="fas fa-calendar-check me-2"></i>Booking Sekarang
            </a>
        </div>
    </div>
</section>

<style>
.price-list-section { background: #fdfaf7; }
.price-accordion {
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(139,111,94,0.08);
    border: 1px solid #f0e8df;
}
.price-acc-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: none;
    padding: 14px 20px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Poppins', sans-serif;
    text-align: left;
}
.price-acc-btn:hover { background: #fdf8f4; }
.price-acc-btn[aria-expanded="true"] { background: #fdf5ef; }
.price-acc-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.price-acc-icon-wrap {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}
.price-acc-label {
    font-weight: 600;
    font-size: 14px;
    color: #3d2b1f;
}
.price-acc-count {
    font-size: 11px;
    color: #aaa;
    background: #f5ede6;
    border-radius: 20px;
    padding: 2px 10px;
    font-weight: 500;
}
.price-acc-toggle {
    color: #8B6F5E;
    font-size: 13px;
    transition: transform 0.3s;
}
.price-acc-btn[aria-expanded="true"] .price-acc-toggle {
    transform: rotate(180deg);
}
.price-acc-body { border-top: 1px solid #f0e8df; }
.price-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
}
.price-table thead tr { background: #faf5f0; border-bottom: 2px solid #f0e8df; }
.price-table th {
    padding: 10px 20px;
    color: #8B6F5E;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.price-table tbody tr { border-bottom: 1px solid #f5ede6; transition: background 0.15s; }
.price-table tbody tr:last-child { border-bottom: none; }
.price-table tbody tr:hover { background: #fdf8f4; }
.price-table td { padding: 11px 20px; color: #444; vertical-align: middle; }
.price-cell { color: #8B6F5E; font-weight: 700; font-size: 13px; white-space: nowrap; }
</style>

<script>
function togglePrice(id, btn) {
    var body = document.getElementById(id);
    var isOpen = btn.getAttribute('aria-expanded') === 'true';
    body.style.display = isOpen ? 'none' : 'block';
    btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
}
</script>

<!-- ══ OUR PRODUCTS ══ -->
<section id="produk" class="section-product">
    <div class="container">
        <div class="product-section-title">
            <h2>Our <span>Products</span></h2>
            <div class="title-line"></div>
            <p>Press On Nails premium untuk tampil cantik instan ✨</p>
        </div>

        <?php
        $products = [
            ["name"=>"Cat Eye Nails",         "price"=>"Rp 22.000", "category"=>"simple",  "img"=>"image/nail1,22k.jpeg"],
            ["name"=>"Cat Eye Nails Pink",     "price"=>"Rp 17.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-07 at 10.10.41.jpeg"],
            ["name"=>"Cat Eye Coquette Nails", "price"=>"Rp 22.000", "category"=>"glam",    "img"=>"image/cateyeqouket.jpeg"],
            ["name"=>"Butterfly Nails",        "price"=>"Rp 25.000", "category"=>"wedding", "img"=>"image/WhatsApp Image 2026-05-06 at 11.22.27.jpeg"],
            ["name"=>"Cat Eye Nails",          "price"=>"Rp 20.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-06 at 11.05.13.jpeg"],
            ["name"=>"Cat Eye Coquette Nails", "price"=>"Rp 22.000", "category"=>"glam",    "img"=>"image/WhatsApp Image 2026-05-06 at 10.21.26.jpeg"],
            ["name"=>"Elegant Nails",          "price"=>"Rp 22.000", "category"=>"glam",    "img"=>"image/WhatsApp Image 2026-05-06 at 10.21.24.jpeg"],
            ["name"=>"Cat Eye Nails",          "price"=>"Rp 20.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-06 at 11.05.12.jpeg"],
            ["name"=>"Cat Eye Red Nails",      "price"=>"Rp 20.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-06 at 11.05.12 (1).jpeg"],
            ["name"=>"Simple Nails",           "price"=>"Rp 17.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-07 at 10.09.45.jpeg"],
            ["name"=>"Cat Eye Pink Nails",     "price"=>"Rp 20.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-06 at 11.05.11.jpeg"],
            ["name"=>"Sun Flower",             "price"=>"Rp 17.000", "category"=>"glam",    "img"=>"image/WhatsApp Image 2026-05-07 at 10.05.32.jpeg"],
            ["name"=>"Bling bling Nails",      "price"=>"Rp 17.000", "category"=>"glam",    "img"=>"image/WhatsApp Image 2026-05-07 at 10.10.14.jpeg"],
            ["name"=>"Elegant Nails",          "price"=>"Rp 17.000", "category"=>"simple",  "img"=>"image/WhatsApp Image 2026-05-07 at 10.06.14.jpeg"],
            ["name"=>"Elegant Nails",          "price"=>"Rp 25.000", "category"=>"wedding", "img"=>"image/WhatsApp Image 2026-05-06 at 11.20.31.jpeg"],
            ["name"=>"Elegant Nails",          "price"=>"Rp 25.000", "category"=>"wedding", "img"=>"image/WhatsApp Image 2026-05-06 at 11.17.28.jpeg"],
        ];
        $prodCategories = [
            'glam'    => ['label'=>'Glam',    'icon'=>'fas fa-gem'],
            'simple'  => ['label'=>'Simple',  'icon'=>'fas fa-hand-dots'],
            'wedding' => ['label'=>'Wedding', 'icon'=>'fas fa-ring'],
        ];
        $grouped = [];
        foreach ($products as $p) { $grouped[$p['category']][] = $p; }
        $pDelay = 0;
        foreach ($prodCategories as $catKey => $catInfo):
            if (empty($grouped[$catKey])) continue;
            $catItems = $grouped[$catKey];
            $catId = 'prod-' . $catKey;
        ?>
        <div class="price-accordion mb-3" data-aos="fade-up" data-aos-delay="<?= $pDelay * 80 ?>">
            <button class="price-acc-btn" onclick="togglePrice('<?= $catId ?>', this)" aria-expanded="false">
                <span class="price-acc-left">
                    <span class="price-acc-icon-wrap"><i class="<?= $catInfo['icon'] ?>"></i></span>
                    <span class="price-acc-label"><?= $catInfo['label'] ?></span>
                    <span class="price-acc-count"><?= count($catItems) ?> produk</span>
                </span>
                <span class="price-acc-toggle"><i class="fas fa-chevron-down"></i></span>
            </button>
            <div class="price-acc-body" id="<?= $catId ?>" style="display:none;">
                <table class="price-table">
                    <thead>
                        <tr>
                            <th style="width:52px;"></th>
                            <th>Nama Produk</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end" style="width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catItems as $p): ?>
                        <tr class="prod-row" style="cursor:pointer;"
                            onclick="showProductPreview('<?= addslashes($p['name']) ?>', '<?= addslashes($p['price']) ?>', '<?= addslashes($p['img']) ?>')"
                            onmouseover="this.style.background='#fdf5ef'" onmouseout="this.style.background=''">
                            <td>
                                <img src="<?= $p['img'] ?>" alt="<?= $p['name'] ?>"
                                    style="width:40px;height:40px;object-fit:cover;border-radius:8px;display:block;">
                            </td>
                            <td style="font-size:13px;"><?= $p['name'] ?></td>
                            <td class="text-end price-cell"><?= $p['price'] ?></td>
                            <td class="text-end">
                                <span style="font-size:11px;color:#aaa;"><i class="fas fa-eye"></i> Lihat</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $pDelay++; endforeach; ?>
    </div>
</section>

<style>
.btn-beli-sm {
    padding: 5px 12px;
    background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
    color: #fff; border: none; border-radius: 8px;
    font-size: 11px; font-weight: 600; font-family: 'Poppins', sans-serif;
    cursor: pointer; transition: 0.2s; white-space: nowrap;
}
.btn-beli-sm:hover { opacity: 0.85; }
</style>

<!-- ══ PRODUCT PREVIEW MODAL ══ -->
<div id="productPreviewModal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.55);align-items:center;justify-content:center;" onclick="if(event.target===this)closeProductPreview()">
    <div style="background:#fff;border-radius:24px;width:92%;max-width:380px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.25);animation:ppFadeIn .25s ease;">
        <div style="position:relative;">
            <img id="ppImg" src="" alt="" style="width:100%;height:280px;object-fit:cover;display:block;">
            <button onclick="closeProductPreview()" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.45);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="padding:20px 22px 22px;">
            <div id="ppName" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:16px;color:#2d1f17;margin-bottom:4px;"></div>
            <div id="ppPrice" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;color:#8B6F5E;margin-bottom:18px;"></div>
            <button id="ppBeli"
                style="width:100%;padding:12px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;font-family:'Poppins',sans-serif;cursor:pointer;">
                <i class="fas fa-shopping-bag me-2"></i>Beli Sekarang
            </button>
        </div>
    </div>
</div>
<style>
@keyframes ppFadeIn { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
</style>
<script>
function showProductPreview(name, price, img) {
    document.getElementById('ppImg').src = img;
    document.getElementById('ppName').textContent = name;
    document.getElementById('ppPrice').textContent = price;
    document.getElementById('ppBeli').onclick = function() {
        closeProductPreview();
        handleBeli(name, price, img);
    };
    document.getElementById('productPreviewModal').style.display = 'flex';
}
function closeProductPreview() {
    document.getElementById('productPreviewModal').style.display = 'none';
}
</script>

<!-- WHY CHOOSE US -->
<!-- LOKASI & ULASAN -->
<section class="location-reviews py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-5">
                <div class="section-label" data-aos="fade-up"><span>Temukan Kami</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="200">Lokasi &amp; Ulasan</h2>
            </div>
        </div>
        <div class="row g-4 align-items-stretch">
            <!-- MAP -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="map-wrapper">
                    <div class="map-info-bar">
                        <div class="map-info-left">
                            <div class="map-salon-name"><i class="fas fa-map-marker-alt"></i> NISWÀ BEAUTY</div>
                            <div class="map-salon-addr">Jl. Watulumpang, Bangsri, Jepara</div>
                        </div>
                        <a href="https://maps.app.goo.gl/czQHcN15FMvfFZy76" target="_blank" class="map-open-btn">
                            Buka Maps <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0!2d110.7708502!3d-6.5253308!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7123c39ad21875%3A0xd77e4fd098899e2c!2sNISWA%20BEAUTY%20Nail%20%26%20Foot%20Spa!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid"
                        width="100%" height="100%" style="border:0;border-radius:0 0 20px 20px;flex:1;min-height:400px;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <!-- REVIEWS -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="reviews-wrapper">
                    <!-- Rating Summary -->
                    <div class="review-summary">
                        <div class="review-score">5.0</div>
                        <div class="review-summary-right">
                            <div class="review-stars-big">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <div class="review-count">Berdasarkan ulasan Google Maps</div>
                            <a href="https://maps.app.goo.gl/czQHcN15FMvfFZy76" target="_blank" class="review-write-btn">
                                <i class="fab fa-google"></i> Tulis Ulasan
                            </a>
                        </div>
                    </div>

                    <!-- Review Cards -->
                    <div class="review-list">
                        <div class="review-item">
                            <div class="review-top">
                                <div class="reviewer-avatar" style="background:linear-gradient(135deg,#f9a8d4,#f472b6);">N</div>
                                <div class="reviewer-info">
                                    <div class="reviewer-name">Ninda Ayu</div>
                                    <div class="reviewer-stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="review-date">1 bulan lalu</div>
                            </div>
                            <p class="review-text">"Nail art-nya bagus banget, hasilnya rapi dan tahan lama! Mbak-mbaknya ramah dan sabar. Foot spa-nya juga bikin kaki lega banget. Pasti balik lagi! 💅"</p>
                        </div>

                        <div class="review-item">
                            <div class="review-top">
                                <div class="reviewer-avatar" style="background:linear-gradient(135deg,#6ee7b7,#34d399);">R</div>
                                <div class="reviewer-info">
                                    <div class="reviewer-name">Rizka Amalia</div>
                                    <div class="reviewer-stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="review-date">2 bulan lalu</div>
                            </div>
                            <p class="review-text">"Lashlift-nya keren banget, mata jadi keliatan lebih segar dan melek. Tempatnya bersih dan nyaman, harga juga worth it. Recommended banget buat yang di Jepara! ✨"</p>
                        </div>

                        <div class="review-item">
                            <div class="review-top">
                                <div class="reviewer-avatar" style="background:linear-gradient(135deg,#fde68a,#f59e0b);">S</div>
                                <div class="reviewer-info">
                                    <div class="reviewer-name">Siti Maryam</div>
                                    <div class="reviewer-stars">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="review-date">3 bulan lalu</div>
                            </div>
                            <p class="review-text">"Callus treatment-nya top banget, kaki jadi mulus dan lembut. Pelayanan cepat dan tidak mengecewakan. Sudah langganan di sini dari lama dan selalu puas! 🌸"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include 'footer.php'; ?>

<button id="backToTop"><i class="fas fa-chevron-up"></i></button>

<!-- ══ ORDER MODAL ══ -->
<div id="orderModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:90%;max-width:480px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto;">
        <div style="background:linear-gradient(135deg,#8B6F5E,#D6C1A3);padding:20px 24px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;">
            <div>
                <h5 style="color:#fff;margin:0;font-weight:700;">Form Pembelian</h5>
                <small style="color:rgba(255,255,255,0.8);" id="modalProductName"></small>
            </div>
            <button onclick="closeOrder()" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:16px;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:24px;">
            <div style="display:flex;align-items:center;gap:12px;background:#faf7f2;border-radius:12px;padding:12px;margin-bottom:20px;">
                <img id="modalProductImg" src="" style="width:60px;height:60px;object-fit:cover;border-radius:10px;">
                <div>
                    <div style="font-weight:600;font-size:14px;" id="modalProductNameInner"></div>
                    <div style="color:#8B6F5E;font-weight:700;" id="modalProductPrice"></div>
                </div>
            </div>
            <form method="POST" id="orderForm">
                <input type="hidden" name="order" value="1">
                <input type="hidden" name="product_name" id="inputProductName">
                <input type="hidden" name="product_price" id="inputProductPrice">
                <div id="orderError" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;margin-bottom:14px;"></div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Nama Lengkap</label>
                    <input type="text" name="nama" required placeholder="Nama Anda"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Nomor WhatsApp</label>
                    <input type="tel" name="whatsapp" required placeholder="08xxxxxxxxxx"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Alamat Pengiriman</label>
                    <textarea name="alamat" required placeholder="Alamat lengkap..." rows="2"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;resize:none;"></textarea>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Jumlah</label>
                    <input type="number" name="qty" min="1" max="10" value="1"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;">
                </div>
                <div style="margin-bottom:18px;">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block;">Catatan (opsional)</label>
                    <textarea name="catatan" placeholder="Warna, ukuran, atau permintaan khusus..." rows="2"
                        style="width:100%;padding:10px 14px;border:1.5px solid #e8e0d8;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;outline:none;resize:none;"></textarea>
                </div>
                <button type="submit"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;font-family:Poppins,sans-serif;cursor:pointer;">
                    <i class="fas fa-shopping-bag me-2"></i>Konfirmasi Pesanan
                </button>
            </form>
        </div>
    </div>
</div>

<div id="successOrder" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:90%;max-width:360px;padding:32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <i class="fas fa-check-circle" style="font-size:56px;color:#10b981;margin-bottom:16px;display:block;"></i>
        <h5 style="font-weight:700;margin-bottom:8px;">Pesanan Berhasil!</h5>
        <p style="color:#666;font-size:13px;margin-bottom:20px;">Terima kasih! Tim kami akan menghubungi Anda via WhatsApp segera.</p>
        <button onclick="document.getElementById('successOrder').style.display='none'"
            style="background:linear-gradient(135deg,#8B6F5E,#D6C1A3);color:#fff;border:none;border-radius:30px;padding:10px 28px;font-weight:600;font-family:Poppins,sans-serif;cursor:pointer;">
            Tutup
        </button>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js?v=5.0"></script>
<script>AOS.init();</script>
<script src="script.js"></script>

<!-- Hero Carousel: inisialisasi manual agar auto-slide tidak mati -->
<script>
(function () {
    function startCarousel() {
        var el = document.getElementById('heroCarousel');
        if (!el) return;

        var old = bootstrap.Carousel.getInstance(el);
        if (old) old.dispose();

        var carousel = new bootstrap.Carousel(el, {
            interval: 3000,
            ride: 'carousel',
            wrap: true,
            pause: false
        });
        carousel.cycle();

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) carousel.cycle();
        });
        window.addEventListener('focus', function () {
            carousel.cycle();
        });
    }

    if (document.readyState === 'complete') {
        startCarousel();
    } else {
        window.addEventListener('load', startCarousel);
    }
})();
</script>

<!-- Product filter -->
<script>
document.querySelectorAll(".filter-buttons button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-buttons button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const filter = btn.dataset.filter;
        document.querySelectorAll(".product-card").forEach(card => {
            card.style.display = (filter === "all" || card.dataset.category === filter) ? "block" : "none";
        });
    });
});
</script>

<!-- Order modal -->
<script>
function handleBeli(name, price, img) {
    document.getElementById('modalProductName').textContent      = name;
    document.getElementById('modalProductNameInner').textContent = name;
    document.getElementById('modalProductPrice').textContent     = price;
    document.getElementById('modalProductImg').src               = img;
    document.getElementById('orderForm').reset();
    document.getElementById('inputProductName').value  = name;
    document.getElementById('inputProductPrice').value = price;
    document.getElementById('orderError').style.display = 'none';
    document.getElementById('orderModal').style.display = 'flex';
}
function closeOrder() {
    document.getElementById('orderModal').style.display = 'none';
}
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrder();
});

// AJAX submit — tanpa reload halaman
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var errBox = document.getElementById('orderError');
    errBox.style.display = 'none';

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(this)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            closeOrder();
            document.getElementById('successOrder').style.display = 'flex';
            document.getElementById('orderForm').reset();
        } else {
            errBox.innerHTML = data.message;
            errBox.style.display = 'block';
        }
    })
    .catch(function() {
        errBox.innerHTML = 'Terjadi kesalahan. Silakan coba lagi.';
        errBox.style.display = 'block';
    });
});
</script>

</body>
</html>