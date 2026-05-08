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
        </div>
    </div>
</section>

<!-- SERVICES GRID -->
<section id="layanan" class="services-clean">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="services-title">Layanan Kami</h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2" style="font-size:14px;">Klik layanan untuk melihat contoh hasil</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Haircut', [
                    'image/download (7).jpg'
                ])">
                    <img src="image/download (7).jpg" alt="Haircut">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Haircut</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Coloring', [
                    'image/coloring.jpg'
                ])">
                    <img src="image/coloring.jpg" alt="Coloring">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Coloring</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Nailart', [
                    'image/nailart.jpeg',
                    'image/homenailart.jpeg'
                ])">
                    <img src="image/nailart.jpeg" alt="Nailart">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Nailart</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Hair Treatments', [
                    'image/\u2800 _\u0440\u0430\u0431\u043e\u0447\u0435\u0435 \u043f\u0440\u043e\u0441\u0442\u0440\u0430\u043d\u0441\u0442\u0432\u043e \u043c\u0430\u0441\u0442\u0435\u0440\u0430 \u0440\u0435\u043a\u043e\u043d\u0441\u0442\u0440\u0443\u043a\u0446\u0438\u0438 - \u044d\u0442\u043e \u043d\u0435 \u0432\u0441\u0435\u0433\u0434\u0430 \u043f\u0440\u043e \u044d\u0441\u0442\u0435\u0442\u043a\u0443 \u0432\u043e\u043b\u043e\u0441 , \u0438\u043d\u043e\u0433\u0434\u0430 \u044d\u0442\u043e \u043f\u0440\u043e \u043f\u044b\u043b\u044c, \u0440\u0430\u0437\u043c\u043e\u043a\u0448\u0438\u0435 \u043f\u0430\u043b\u044c\u0446\u044b, \u0438 \u0432\u043e\u043b\u043e\u0441\u044b \u0432 \u0442\u0435\u0445 \u043c\u0435\u0441\u0442\u0430\u0445, \u0433\u0434\u0435 \u0442\u044b \u0438\u0445 \u043d\u0435 \u043e\u0436\u0438\u0434\u0430\u0435\u0448\u044c \u0443\u0432\u0438\u0434\u0435\u0442\u044c \ud83d\ude05.jpg'
                ])">
                    <img src="image/⠀ _рабочее пространство мастера реконструкции - это не всегда про эстетку волос , иногда это про пыль, размокшие пальцы, и волосы в тех местах, где ты их не ожидаешь увидеть .jpg" alt="Hair Treatments">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Hair Treatments</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Foot SPA', [
                    'image/download (8).jpg'
                ])">
                    <img src="image/download (8).jpg" alt="Foot SPA">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Foot SPA</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Henna Series', [
                    'image/henna.jpg'
                ])">
                    <img src="image/henna.jpg" alt="Henna Series">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Henna Series</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Press on Nail', [
                    'image/download (6).jpg'
                ])">
                    <img src="image/download (6).jpg" alt="Press on nail">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Press on nail</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-box" onclick="openServiceGallery('Eye Lash', [
                    'image/eyelash.jpeg'
                ])">
                    <img src="image/eyelash.jpeg" alt="Eye lash">
                    <div class="overlay"><i class="fas fa-images me-1"></i>Eye lash</div>
                    <div class="service-click-hint"><i class="fas fa-eye"></i></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ SERVICE GALLERY MODAL ══ -->
<div id="serviceGalleryModal" aria-hidden="true">
    <div class="sgm-backdrop" onclick="closeServiceGallery()"></div>
    <div class="sgm-dialog" role="dialog" aria-modal="true">
        <!-- Header -->
        <div class="sgm-header">
            <div class="sgm-title-wrap">
                <i class="fas fa-images sgm-title-icon"></i>
                <span id="sgmTitle">Layanan</span>
            </div>
            <button class="sgm-close" onclick="closeServiceGallery()" aria-label="Tutup">&times;</button>
        </div>

        <!-- Main image viewer -->
        <div class="sgm-main">
            <button class="sgm-arrow sgm-arrow-left" id="sgmPrev" onclick="sgmNav(-1)" aria-label="Sebelumnya">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="sgm-img-wrap">
                <img id="sgmMainImg" src="" alt="" class="sgm-main-img">
                <div class="sgm-counter" id="sgmCounter">1 / 1</div>
            </div>
            <button class="sgm-arrow sgm-arrow-right" id="sgmNext" onclick="sgmNav(1)" aria-label="Berikutnya">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Thumbnail strip -->
        <div class="sgm-thumbs" id="sgmThumbs"></div>

        <!-- CTA -->
        <div class="sgm-footer">
            <a href="booking.php" class="sgm-book-btn">
                <i class="fas fa-calendar-alt me-2"></i>Reservasi Layanan Ini
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    var modal    = document.getElementById('serviceGalleryModal');
    var mainImg  = document.getElementById('sgmMainImg');
    var titleEl  = document.getElementById('sgmTitle');
    var counter  = document.getElementById('sgmCounter');
    var thumbWrap= document.getElementById('sgmThumbs');
    var prevBtn  = document.getElementById('sgmPrev');
    var nextBtn  = document.getElementById('sgmNext');

    var _photos  = [];
    var _cur     = 0;

    window.openServiceGallery = function(name, photos) {
        _photos = photos;
        _cur    = 0;
        titleEl.textContent = name;

        // Build thumbnails
        thumbWrap.innerHTML = '';
        photos.forEach(function(src, i) {
            var t = document.createElement('img');
            t.src = src;
            t.alt = name + ' ' + (i + 1);
            t.className = 'sgm-thumb' + (i === 0 ? ' active' : '');
            t.onclick = function() { sgmGoTo(i); };
            thumbWrap.appendChild(t);
        });

        updateView();
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        // hide arrows if only 1 photo
        prevBtn.style.display = photos.length > 1 ? '' : 'none';
        nextBtn.style.display = photos.length > 1 ? '' : 'none';
        thumbWrap.style.display = photos.length > 1 ? '' : 'none';
    };

    window.closeServiceGallery = function() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    };

    window.sgmNav = function(dir) {
        sgmGoTo((_cur + dir + _photos.length) % _photos.length);
    };

    function sgmGoTo(idx) {
        _cur = idx;
        updateView();
    }

    function updateView() {
        // fade transition
        mainImg.style.opacity = '0';
        setTimeout(function() {
            mainImg.src = _photos[_cur];
            mainImg.style.opacity = '1';
        }, 120);

        counter.textContent = (_cur + 1) + ' / ' + _photos.length;

        // update thumbs
        var thumbs = thumbWrap.querySelectorAll('.sgm-thumb');
        thumbs.forEach(function(t, i) {
            t.classList.toggle('active', i === _cur);
        });
    }

    // Keyboard nav
    document.addEventListener('keydown', function(e) {
        if (!modal.classList.contains('is-open')) return;
        if (e.key === 'Escape')      closeServiceGallery();
        if (e.key === 'ArrowLeft')   sgmNav(-1);
        if (e.key === 'ArrowRight')  sgmNav(1);
    });

    // Swipe support (mobile)
    var touchX = null;
    modal.addEventListener('touchstart', function(e) { touchX = e.touches[0].clientX; }, {passive:true});
    modal.addEventListener('touchend', function(e) {
        if (touchX === null) return;
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 50) sgmNav(dx < 0 ? 1 : -1);
        touchX = null;
    }, {passive:true});
})();
</script>

<!-- DAFTAR HARGA -->
<section id="harga" class="price-list-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="services-title">Daftar <span style="color:#8B6F5E;">Harga</span></h2>
            <div class="title-line"></div>
            <p class="text-muted mt-2">Harga transparan, kualitas terjamin</p>
        </div>

        <div class="price-cards-grid">
        <?php
        $priceList = [
            'Rambut' => [
                ['name'=>'Creambath',                        'price'=>'Rp 75.000'],
                ['name'=>'Hair Mask',                        'price'=>'Rp 45.000 - 90.000'],
                ['name'=>'Hair Spa',                         'price'=>'Rp 100.000'],
                ['name'=>'Cuci,Catok,Blow',                     'price'=>'Rp 25.000 - 50.000'],
                ['name'=>'Bleaching S',                      'price'=>'Rp 40.000'],
                ['name'=>'Coloring Full',                    'price'=>'Rp 120.000 - 300.000'],
                ['name'=>'Bleaching',             'price'=>'Rp 200.000 - 1.200.000'],
                ['name'=>'Balayage',                         'price'=>'Rp 250.000 - 700.000'],
                ['name'=>'Down Peim Poni',                   'price'=>'Rp 100.000 - 300.000'],
                ['name'=>'Keriting Klasik',                  'price'=>'Rp 300.000 - 700.000'],
                ['name'=>'Keriting Digital',                 'price'=>'Rp 450.000 - 1.700.000'],
                ['name'=>'Keratin Treatment',                'price'=>'Rp 200.000'],
                ['name'=>'Smoothing',       'price'=>'Rp 200.000 - 400.000'],
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
            'Henna Series' => [
                ['name'=>'Brow Henna',                       'price'=>'Rp 25.000'],
                ['name'=>'Nail Henna Tangan',                'price'=>'Rp 25.000'],
                ['name'=>'Nail Henna Kaki',                  'price'=>'Rp 30.000'],
                ['name'=>'Bundling Meni-Henna',              'price'=>'Rp 75.000'],
                ['name'=>'Henna Fun',                        'price'=>'Rp 25.000 - 100.000'],
            ],
            'Treatment Spa' => [
                ['name'=>'Bundling Manicure & Pedicure',     'price'=>'Rp 100.000'],
                ['name'=>'Manicure / Pedicure',              'price'=>'Rp 60.000'],
                ['name'=>'Hand Spa',                         'price'=>'Rp 80.000'],
                ['name'=>'Foot Spa',                         'price'=>'Rp 100.000'],
                ['name'=>'Callus Treatment',                 'price'=>'Rp 70.000 - 150.000'],
            ],
            'Brow & Lash' => [
                ['name'=>'Brow Bomb',      'price'=>'Rp 100.000'],
                ['name'=>'Lashlift',       'price'=>'Rp 70.000'],
                ['name'=>'Lashlift Tint',  'price'=>'Rp 90.000'],
            ],
        ];
        $delay = 0;
        foreach ($priceList as $cat => $items):
        ?>
        <div class="price-card" data-aos="fade-up" data-aos-delay="<?= $delay * 80 ?>">
            <div class="price-card-header">
                <span class="price-card-label"><?= $cat ?></span>
                <span class="price-acc-count"><?= count($items) ?> layanan</span>
            </div>
            <div class="price-acc-body">
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
        </div><!-- /.price-cards-grid -->

        <div class="text-center mt-4" data-aos="fade-up">
            <p class="text-muted small mb-3">* Harga dapat berubah sewaktu-waktu. Hubungi kami untuk info terkini.</p>
        </div>
    </div>
</section>

<style>
/* ══ DAFTAR HARGA — MODERN CARD ══ */
.price-list-section { background: linear-gradient(180deg, #fdfaf7 0%, #f5ede4 100%); }

.price-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.price-card {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(139,111,94,0.12);
    border: 1px solid rgba(214,193,163,0.3);
    background: #fff;
    transition: transform 0.32s cubic-bezier(0.4,0,0.2,1),
                box-shadow 0.32s cubic-bezier(0.4,0,0.2,1);
}
.price-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 48px rgba(139,111,94,0.22);
}

.price-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
    padding: 16px 20px;
}

.price-card-label {
    font-weight: 700;
    font-size: 15px;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    letter-spacing: 0.2px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

.price-acc-count {
    font-size: 11px;
    color: rgba(255,255,255,0.9);
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 3px 12px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
}

.price-acc-body { border-top: 1px solid rgba(139,111,94,0.12); }

.price-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 13.5px;
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

<!-- ══ PRESS ON NAIL COLLECTION ══ -->
<section id="produk" class="section-product">
    <div class="container">
        <div class="product-section-title" data-aos="fade-up">
            <h2>Press On Nail <span>Collection</span></h2>
            <div class="title-line"></div>
            <p>Press On Nails premium untuk tampil cantik instan <i class="fa-solid fa-sparkles" style="color:#D6C1A3;font-size:0.9em;"></i></p>
        </div>

        <!-- Filter buttons -->
        <div class="filter-buttons" data-aos="fade-up" data-aos-delay="100">
            <button class="active" data-filter="all">Semua</button>
            <button data-filter="simple">Simple</button>
            <button data-filter="glam">Glam</button>
            <button data-filter="wedding">Wedding</button>
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
        ?>

        <div class="product-grid" data-aos="fade-up" data-aos-delay="150">
        <?php foreach ($products as $i => $p): ?>
            <div class="product-card" data-category="<?= $p['category'] ?>">
                <div class="product-card-img-wrap">
                    <img src="<?= $p['img'] ?>" alt="<?= $p['name'] ?>" class="product-img" loading="lazy">
                    <div class="product-card-overlay">
                        <button class="btn-preview" onclick="showProductPreview('<?= addslashes($p['name']) ?>', '<?= addslashes($p['price']) ?>', '<?= addslashes($p['img']) ?>')">
                            <i class="fas fa-eye"></i> Lihat
                        </button>
                    </div>
                    <span class="product-badge-cat"><?= ucfirst($p['category']) ?></span>
                </div>
                <div class="product-info">
                    <div class="product-name"><?= $p['name'] ?></div>
                    <div class="product-price"><?= $p['price'] ?></div>
                    <button class="btn-beli" onclick="handleBeli('<?= addslashes($p['name']) ?>','<?= addslashes($p['price']) ?>','<?= addslashes($p['img']) ?>')">
                        <i class="fas fa-shopping-bag me-1"></i> Beli Sekarang
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
/* ══ PRODUCT SECTION — MODERN CARDS ══ */
.section-product { background: linear-gradient(180deg, #f5ede4 0%, #fdfaf7 100%); padding: 80px 0; }

.product-section-title { text-align: center; margin-bottom: 32px; }
.product-section-title h2 { font-weight: 700; font-size: 34px; font-family: 'Playfair Display', serif; color: #2d1f17; }
.product-section-title span { color: #8B6F5E; }
.product-section-title p { color: #888; margin-top: 10px; font-size: 15px; }

.filter-buttons { text-align: center; margin-bottom: 40px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; }
.filter-buttons button {
    border: 1.5px solid #e8ddd4;
    background: #fff;
    padding: 8px 22px;
    border-radius: 50px;
    font-weight: 500;
    color: #8a7060;
    cursor: pointer;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: all 0.25s;
    box-shadow: 0 2px 8px rgba(139,111,94,0.06);
}
.filter-buttons button:hover { border-color: #8B6F5E; color: #8B6F5E; background: #fdf8f4; }
.filter-buttons button.active {
    background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 6px 20px rgba(139,111,94,0.30);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}

.product-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(139,111,94,0.10);
    border: 1px solid rgba(214,193,163,0.3);
    transition: transform 0.32s cubic-bezier(0.4,0,0.2,1),
                box-shadow 0.32s cubic-bezier(0.4,0,0.2,1);
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(139,111,94,0.22);
}

.product-card-img-wrap {
    position: relative;
    overflow: hidden;
}
.product-img {
    width: 100%; height: 240px; object-fit: cover;
    display: block;
    transition: transform 0.45s cubic-bezier(0.4,0,0.2,1);
}
.product-card:hover .product-img { transform: scale(1.07); }

.product-card-overlay {
    position: absolute;
    inset: 0;
    background: rgba(45,31,23,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    backdrop-filter: blur(2px);
}
.product-card:hover .product-card-overlay { opacity: 1; }

.btn-preview {
    background: rgba(255,255,255,0.92);
    color: #5A4A42;
    border: none;
    border-radius: 50px;
    padding: 9px 22px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    transform: translateY(8px);
    transition: transform 0.3s, background 0.2s;
}
.product-card:hover .btn-preview { transform: translateY(0); }
.btn-preview:hover { background: #fff; }

.product-badge-cat {
    position: absolute;
    top: 12px; left: 12px;
    background: rgba(255,255,255,0.88);
    color: #5A4A42;
    font-size: 10px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 50px;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.6);
}

.product-info { padding: 16px; }
.product-name {
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    color: #2d1f17;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.product-price {
    color: #8B6F5E;
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 10px;
}
.btn-beli {
    width: 100%;
    padding: 9px 0;
    background: linear-gradient(135deg, #8B6F5E, #D6C1A3);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 4px 14px rgba(139,111,94,0.25);
    letter-spacing: 0.2px;
}
.btn-beli:hover {
    box-shadow: 0 8px 24px rgba(139,111,94,0.40);
    transform: translateY(-1px);
}

@media (max-width: 576px) {
    .product-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .product-img { height: 180px; }
    .price-cards-grid { grid-template-columns: 1fr; }
}
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

<!-- ══ SECTION LOKASI ══ -->
<section id="lokasi" class="location-section">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span>Temukan Kami</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150">Lokasi <span style="color:var(--cream-accent);">Kami</span></h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="250" style="font-size:15px;">Kunjungi kami langsung untuk pengalaman kecantikan terbaik</p>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-delay="300">
            <div class="map-wrapper map-fullwidth" style="border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(139,111,94,0.18);border:1px solid #EDE5D8;">
                <div class="map-info-bar" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:linear-gradient(135deg,#5A4A42,#8B6F5E);padding:18px 28px;">
                    <div class="map-info-left">
                        <div class="map-salon-name"><i class="fas fa-map-marker-alt"></i> NISWÀ BEAUTY</div>
                        <div class="map-salon-addr">Jl. Watulumpang, Bangsri, Jepara</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                        <div style="color:rgba(255,255,255,0.85);font-size:13px;font-family:'Poppins',sans-serif;">
                            <i class="fas fa-clock me-1" style="color:#D6C1A3;"></i> Senin – Minggu, 08.00 – 20.00
                        </div>
                        <a href="https://maps.app.goo.gl/czQHcN15FMvfFZy76" target="_blank" class="map-open-btn">
                            <i class="fas fa-directions"></i> Petunjuk Arah
                        </a>
                    </div>
                </div>
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.0!2d110.7708502!3d-6.5253308!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7123c39ad21875%3A0xd77e4fd098899e2c!2sNISWA%20BEAUTY%20Nail%20%26%20Foot%20Spa!5e0!3m2!1sid!2sid!4v1715000000000!5m2!1sid!2sid"
                    width="100%" height="420" style="border:0;display:block;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>


<!-- ══ SECTION TESTIMONI PELANGGAN ══ -->
<section id="testimoni" class="testimoni-section py-5">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <div class="section-label" data-aos="fade-up"><span>Kata Mereka</span></div>
                <h2 class="section-title" data-aos="fade-up" data-aos-delay="150">Testimoni <span style="color:var(--cream-accent);">Pelanggan</span></h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="250" style="font-size:15px;">Kepercayaan pelanggan adalah kebanggaan kami</p>

                <!-- Rating Summary -->
                <div class="testimoni-rating-badge" data-aos="fade-up" data-aos-delay="350">
                    <div class="testimoni-score">5.0</div>
                    <div class="testimoni-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <div class="testimoni-rating-label">Berdasarkan ulasan Google Maps</div>
                    <a href="https://maps.app.goo.gl/czQHcN15FMvfFZy76" target="_blank" class="review-write-btn ms-3">
                        <i class="fab fa-google"></i> Tulis Ulasan
                    </a>
                </div>
            </div>
        </div>

        <!-- Carousel Testimoni -->
        <div class="testimoni-carousel-wrapper" data-aos="fade-up" data-aos-delay="200">
            <div class="testimoni-track" id="testimoniTrack">

                <!-- Card 1 -->
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"Nail art-nya bagus banget, hasilnya rapi dan tahan lama! Mbak-mbaknya ramah dan sabar. Foot spa-nya juga bikin kaki lega banget. Pasti balik lagi! <i class="fa-solid fa-hand-sparkles" style="color:#8B6F5E;font-size:0.9em;"></i>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:linear-gradient(135deg,#f9a8d4,#f472b6);">N</div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name">Ninda Ayu</div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag">Nail Art & Foot Spa</div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"Lashlift-nya keren banget, mata jadi keliatan lebih segar dan melek. Tempatnya bersih dan nyaman, harga juga worth it. Recommended banget buat yang di Jepara! <i class="fa-solid fa-sparkles" style="color:#D6C1A3;font-size:0.9em;"></i>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:linear-gradient(135deg,#6ee7b7,#34d399);">R</div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name">Rizka Amalia</div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag">Lashlift</div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"Callus treatment-nya top banget, kaki jadi mulus dan lembut. Pelayanan cepat dan tidak mengecewakan. Sudah langganan di sini dari lama dan selalu puas! <i class="fa-solid fa-fan" style="color:#f9a8d4;font-size:0.9em;"></i>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:linear-gradient(135deg,#fde68a,#f59e0b);">S</div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name">Siti Maryam</div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag">Callus Treatment</div>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"Smoothing-nya hasilnya halus banget dan tahan lama! Stafnya profesional dan ramah. Tempatnya cozy, betah deh berlama-lama di sini. Recommended! <i class="fa-solid fa-star" style="color:#f59e0b;font-size:0.9em;"></i>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:linear-gradient(135deg,#a78bfa,#7c3aed);">D</div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name">Dian Pertiwi</div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag">Smoothing</div>
                    </div>
                </div>

                <!-- Card 5 -->
                <div class="testimoni-card">
                    <div class="testimoni-quote-icon"><i class="fas fa-quote-left"></i></div>
                    <p class="testimoni-text">"Henna series-nya cantik banget, detail dan presisi! Mbak-mbaknya sabar banget ngerjainnya. Harganya juga sangat terjangkau untuk kualitas segini. Bakal balik lagi! <i class="fa-solid fa-leaf" style="color:#16a34a;font-size:0.9em;"></i>"</p>
                    <div class="testimoni-footer">
                        <div class="testimoni-avatar" style="background:linear-gradient(135deg,#86efac,#16a34a);">F</div>
                        <div class="testimoni-user-info">
                            <div class="testimoni-name">Fatimah Zahra</div>
                            <div class="testimoni-stars-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="testimoni-service-tag">Henna Series</div>
                    </div>
                </div>

            </div><!-- end .testimoni-track -->

            <!-- Nav Buttons -->
            <button class="testimoni-nav testimoni-prev" id="testimoniPrev" aria-label="Sebelumnya">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="testimoni-nav testimoni-next" id="testimoniNext" aria-label="Berikutnya">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Dots -->
        <div class="testimoni-dots" id="testimoniDots"></div>
    </div>
</section>

<script>
(function() {
    var track      = document.getElementById('testimoniTrack');
    var prevBtn    = document.getElementById('testimoniPrev');
    var nextBtn    = document.getElementById('testimoniNext');
    var dotsWrap   = document.getElementById('testimoniDots');
    if (!track) return;

    var cards       = Array.from(track.querySelectorAll('.testimoni-card'));
    var current     = 0;
    var autoTimer   = null;
    var perView     = getPerView();

    function getPerView() {
        return window.innerWidth >= 992 ? 3 : window.innerWidth >= 600 ? 2 : 1;
    }

    var totalSlides = Math.max(1, cards.length - perView + 1);

    // Build dots
    dotsWrap.innerHTML = '';
    for (var i = 0; i < totalSlides; i++) {
        var dot = document.createElement('span');
        dot.className = 'testimoni-dot' + (i === 0 ? ' active' : '');
        dot.dataset.i = i;
        dot.addEventListener('click', function() { goTo(+this.dataset.i); });
        dotsWrap.appendChild(dot);
    }

    function goTo(idx) {
        current = Math.max(0, Math.min(idx, totalSlides - 1));
        var pct = current * (100 / perView);
        track.style.transform = 'translateX(-' + pct + '%)';
        dotsWrap.querySelectorAll('.testimoni-dot').forEach(function(d, i) {
            d.classList.toggle('active', i === current);
        });
        cards.forEach(function(c, i) {
            c.classList.toggle('is-active', i >= current && i < current + perView);
        });
    }

    function startAuto() {
        autoTimer = setInterval(function() {
            goTo(current + 1 < totalSlides ? current + 1 : 0);
        }, 4500);
    }
    function stopAuto() { clearInterval(autoTimer); }

    prevBtn.addEventListener('click', function() { stopAuto(); goTo(current - 1 >= 0 ? current - 1 : totalSlides - 1); startAuto(); });
    nextBtn.addEventListener('click', function() { stopAuto(); goTo(current + 1 < totalSlides ? current + 1 : 0); startAuto(); });

    window.addEventListener('resize', function() {
        perView     = getPerView();
        totalSlides = Math.max(1, cards.length - perView + 1);
        current     = 0;
        goTo(0);
    });

    goTo(0);
    startAuto();
})();
</script>


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
            const show = filter === "all" || card.dataset.category === filter;
            card.style.display = show ? "" : "none";
            if (show) {
                card.style.animation = "cardFadeIn 0.3s ease forwards";
            }
        });
    });
});
</script>
<style>
@keyframes cardFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

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