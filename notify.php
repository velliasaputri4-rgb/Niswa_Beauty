<?php
// ═══════════════════════════════════════════════════════════════
//  notify.php — NISWÀ BEAUTY
//  Notifikasi WhatsApp otomatis via Fonnte API
//  Digunakan oleh: booking.php & index.php
// ═══════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────
//  KONFIGURASI — ISI SESUAI AKUN FONNTE KAMU
// ──────────────────────────────────────────────────────────────
define('FONNTE_TOKEN',  '6cFhenDoFG7BtihBhJZD');   // Dari dashboard fonnte.com
define('ADMIN_WA',      '628971440805');                   // Nomor WA admin (tanpa +)
define('SALON_NAME',    'NISWÀ BEAUTY');
define('SALON_ADDRESS', 'Jl. Watulumpang, Bangsri, Jepara');

// ──────────────────────────────────────────────────────────────
//  TIMEOUT — Turunkan agar tidak lama nunggu response Fonnte
// ──────────────────────────────────────────────────────────────
define('WA_TIMEOUT', 5);   // detik (sebelumnya 15)


// ═══════════════════════════════════════════════════════════════
//  HELPER — Normalisasi nomor WA
// ═══════════════════════════════════════════════════════════════
function normalizeNomor(string $nomor): string {
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}


// ═══════════════════════════════════════════════════════════════
//  HELPER — Kirim pesan WA via Fonnte (single)
// ═══════════════════════════════════════════════════════════════
function kirimWA(string $nomor, string $pesan): bool {
    // ── DEBUG: catat nomor asli vs hasil normalisasi ke log ──
    // Hapus baris ini setelah masalah nomor terpecahkan
    $nomorAsli = $nomor;
    $nomor = normalizeNomor($nomor);
    error_log("[NISWÀ WA] Nomor asli: '$nomorAsli' → setelah normalize: '$nomor'");

    if (empty($nomor)) return false;
    if (FONNTE_TOKEN === 'ISI_TOKEN_FONNTE_KAMU_DISINI') return false;

    $url     = 'https://api.fonnte.com/send';
    $payload = ['target' => $nomor, 'message' => $pesan];

    // ── cURL (lebih reliable) ──
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST              => true,
            CURLOPT_POSTFIELDS        => $payload,
            CURLOPT_HTTPHEADER        => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT           => WA_TIMEOUT,        // ✅ diperkecil
            CURLOPT_CONNECTTIMEOUT    => 3,                 // ✅ batas koneksi awal
            CURLOPT_SSL_VERIFYPEER    => false,
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Anggap berhasil jika tidak ada error cURL dan HTTP 200
        if ($result !== false && empty($err) && $httpCode === 200) return true;
    }

    // ── Fallback: file_get_contents ──
    if (!ini_get('allow_url_fopen')) return false;
    $data = http_build_query($payload);
    $opts = [
        'http' => [
            'method'         => 'POST',
            'header'         => "Authorization: " . FONNTE_TOKEN . "\r\n" .
                                "Content-Type: application/x-www-form-urlencoded\r\n" .
                                "Content-Length: " . strlen($data) . "\r\n",
            'content'        => $data,
            'timeout'        => WA_TIMEOUT,                // ✅ diperkecil
            'ignore_errors'  => true,
        ],
        'ssl'  => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ];
    $result = @file_get_contents($url, false, stream_context_create($opts));
    return $result !== false;
}


// ═══════════════════════════════════════════════════════════════
//  HELPER — Kirim banyak pesan WA PARALEL via cURL Multi
//  Jauh lebih cepat daripada kirim satu-satu secara berurutan
// ═══════════════════════════════════════════════════════════════
function kirimWAParalel(array $targets): void {
    // $targets = [ ['nomor' => '628xxx', 'pesan' => '...'], ... ]
    if (FONNTE_TOKEN === 'ISI_TOKEN_FONNTE_KAMU_DISINI') return;
    if (!function_exists('curl_multi_init')) {
        // Fallback satu-satu jika curl_multi tidak tersedia
        foreach ($targets as $t) {
            kirimWA($t['nomor'], $t['pesan']);
        }
        return;
    }

    $url   = 'https://api.fonnte.com/send';
    $mh    = curl_multi_init();
    $handles = [];

    foreach ($targets as $i => $t) {
        $nomor = normalizeNomor($t['nomor']);
        if (empty($nomor)) continue;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['target' => $nomor, 'message' => $t['pesan']],
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => WA_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
    }

    // Eksekusi paralel
    $active = null;
    do {
        curl_multi_exec($mh, $active);
        if ($active) curl_multi_select($mh, 0.5);
    } while ($active > 0);

    // Cleanup
    foreach ($handles as $ch) {
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}


// ═══════════════════════════════════════════════════════════════
//  HELPER — Format tanggal ke Bahasa Indonesia
// ═══════════════════════════════════════════════════════════════
function formatTanggalID(string $tanggal): string {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tanggal);
    if (!$ts) return $tanggal;
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}


// ═══════════════════════════════════════════════════════════════
//  HELPER — Jam kosong tersedia di tanggal tertentu
// ═══════════════════════════════════════════════════════════════
function getAvailableSlots($conn, string $tanggal, string $jamDipesan, array $allSlots): array {
    $tgl = mysqli_real_escape_string($conn, $tanggal);
    $res = mysqli_query($conn,
        "SELECT time FROM bookings WHERE date='$tgl'"
    );
    $booked = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $booked[] = $row['time'];
        }
    }
    return array_values(array_filter($allSlots, function($s) use ($booked, $jamDipesan) {
        return !in_array($s, $booked) && $s !== $jamDipesan;
    }));
}


// ═══════════════════════════════════════════════════════════════
//  BOOKING — Notifikasi ke CUSTOMER & ADMIN sekaligus (PARALEL)
//  ✅ Lebih cepat: keduanya dikirim bersamaan, tidak antri
// ═══════════════════════════════════════════════════════════════
function notifyBooking(array $d, $conn): void {
    $pesanCustomer = buildPesanCustomerBooking($d);
    $pesanAdmin    = buildPesanAdminBooking($d, $conn);

    // Ambil nomor admin dari DB jika tersedia
    $adminNum = ADMIN_WA;
    if ($conn) {
        $r2 = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='kontak' AND `key`='whatsapp' LIMIT 1");
        if ($r2 && $rw = mysqli_fetch_assoc($r2)) {
            $num = preg_replace('/[^0-9]/', '', $rw['value']);
            if (!empty($num)) $adminNum = $num;
        }
    }

    // ✅ Kirim paralel ke customer & admin sekaligus
    kirimWAParalel([
        ['nomor' => $d['whatsapp'], 'pesan' => $pesanCustomer],
        ['nomor' => $adminNum,      'pesan' => $pesanAdmin],
    ]);
}

// ── Builder pesan customer (booking) ──
function buildPesanCustomerBooking(array $d, array $availableSlots = []): string {
    $tgl      = formatTanggalID($d['tanggal']);
    $jenis    = ($d['jenis_layanan'] ?? 'datang') === 'home_service' ? '🏠 Home Service' : '🏪 Datang ke Tempat';
    $alamatHS = !empty($d['alamat_hs']) ? "\n🗺️ *Alamat:* " . $d['alamat_hs'] : '';

    $pesan  = "💅 *KONFIRMASI BOOKING — " . SALON_NAME . "*\n\n";
    $pesan .= "Halo *{$d['nama']}* 👋\n";
    $pesan .= "Booking kamu sudah berhasil terdaftar! 🌸\n\n";
    $pesan .= "📋 *DETAIL BOOKING #{$d['booking_id']}*\n\n";
    $pesan .= "💆 *Layanan:*    {$d['layanan']}\n";
    $pesan .= "📍 *Jenis:*      {$jenis}{$alamatHS}\n";
    $pesan .= "📅 *Tanggal:*   {$tgl}\n";
    $pesan .= "⏰ *Jam:*        {$d['jam']} WIB\n";
    $pesan .= "👥 *Orang:*      {$d['jumlah_orang']} orang\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*   {$d['catatan']}\n";
    }
    $pesan .= "\nJika ada perubahan jadwal, pertanyaan, atau kebutuhan lainnya, jangan ragu untuk menghubungi admin kami kapan saja 🤍\n\n";
    $pesan .= "Terima kasih sudah memilih Niswa Beauty sebagai partner kecantikan kakak 🌸 Kami tidak sabar untuk memberikan pelayanan terbaik dan membuat kakak tampil lebih cantik, percaya diri, dan glowing ✨💅💕\n\n";
    $pesan .= "📍 " . SALON_ADDRESS . "\n";
    $pesan .= "✨ *" . SALON_NAME . "* — Premium Beauty Experience";

    return $pesan;
}

// ── Builder pesan admin (booking) ──
function buildPesanAdminBooking(array $d, $conn): string {
    $tgl      = formatTanggalID($d['tanggal']);
    $jenis    = ($d['jenis_layanan'] ?? 'datang') === 'home_service' ? '🏠 Home Service' : '🏪 Datang ke Tempat';
    $alamatHS = !empty($d['alamat_hs']) ? "\n🗺️ *Alamat HS:* " . $d['alamat_hs'] : '';

    $raw = '';
    $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='time_slots' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) $raw = $row['value'];
    if (empty($raw)) $raw = "09:00\n10:00\n11:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00\n20:00";
    $allSlots  = array_values(array_filter(array_map('trim', explode("\n", $raw))));
    $available = getAvailableSlots($conn, $d['tanggal'], $d['jam'], $allSlots);

    $pesan  = "📅 *BOOKING BARU — " . SALON_NAME . "*\n\n";
    $pesan .= "📋 *Booking ID:* #{$d['booking_id']}\n\n";
    $pesan .= "👤 *Nama:*     {$d['nama']}\n";
    $pesan .= "📱 *WA:*       {$d['whatsapp']}\n";
    $pesan .= "📧 *Email:*    {$d['email']}\n\n";
    $pesan .= "💆 *Layanan:*  {$d['layanan']}\n";
    $pesan .= "📍 *Jenis:*    {$jenis}{$alamatHS}\n";
    $pesan .= "📅 *Tanggal:*  {$tgl}\n";
    $pesan .= "⏰ *Jam:*      {$d['jam']} WIB\n";
    $pesan .= "👥 *Orang:*    {$d['jumlah_orang']} orang\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*  {$d['catatan']}\n";
    }
    $pesan .= "\n⏰ Dikirim: " . date('d/m/Y H:i') . " WIB\n";
    $pesan .= "Segera konfirmasi ke customer! 💬";

    return $pesan;
}


// ═══════════════════════════════════════════════════════════════
//  FUNGSI LAMA — Tetap ada supaya booking.php tidak perlu diubah
//  Sekarang memanggil notifyBooking() yang paralel
// ═══════════════════════════════════════════════════════════════
function notifyCustomerBooking(array $d, array $availableSlots = []): void {
    kirimWA($d['whatsapp'], buildPesanCustomerBooking($d, $availableSlots));
}

function notifyAdminBooking(array $d, $conn): void {
    // Ambil nomor admin dari DB jika tersedia
    $adminNum = ADMIN_WA;
    if ($conn) {
        $r2 = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='kontak' AND `key`='whatsapp' LIMIT 1");
        if ($r2 && $rw = mysqli_fetch_assoc($r2)) {
            $num = preg_replace('/[^0-9]/', '', $rw['value']);
            if (!empty($num)) $adminNum = $num;
        }
    }
    kirimWA($adminNum, buildPesanAdminBooking($d, $conn));
}


// ═══════════════════════════════════════════════════════════════
//  ORDER PRODUK — Notifikasi ke CUSTOMER & ADMIN (PARALEL)
// ═══════════════════════════════════════════════════════════════
function notifyOrder(array $d): void {
    kirimWAParalel([
        ['nomor' => $d['whatsapp'], 'pesan' => buildPesanCustomerOrder($d)],
        ['nomor' => ADMIN_WA,       'pesan' => buildPesanAdminOrder($d)],
    ]);
}

// ── Builder pesan customer (order) ──
function buildPesanCustomerOrder(array $d): string {
    $metode = strtoupper($d['payment_method'] ?? 'COD');

    $pesan  = "🛍️ *KONFIRMASI PESANAN — " . SALON_NAME . "*\n\n";
    $pesan .= "Halo *{$d['nama']}* 👋\n";
    $pesan .= "Pesanan kamu sudah kami terima! ✨\n\n";
    $pesan .= "📦 *DETAIL PESANAN #{$d['order_id']}*\n\n";
    $pesan .= "🛒 *Produk:*   {$d['product_name']}\n";
    $pesan .= "🔢 *Qty:*      {$d['qty']} pcs\n";
    $pesan .= "💰 *Harga:*    {$d['product_price']}\n";
    $pesan .= "🚚 *Ongkir:*   Rp 5.000 (Jepara)\n";
    $pesan .= "💳 *Total:*    *{$d['total']}*\n\n";
    $pesan .= "🚚 *Pengiriman ke:*\n";
    $pesan .= "{$d['alamat']}\n\n";
    $pesan .= "💳 *Pembayaran:* {$metode}\n";
    if ($metode === 'COD') {
        $pesan .= "   _(Bayar saat barang sampai)_\n";
    }
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*  {$d['catatan']}\n";
    }
    $pesan .= "\nJika ada pertanyaan, perubahan pesanan, atau kendala lainnya, kakak bisa langsung menghubungi admin kami kapan saja 🤍\n\n";
    $pesan .= "Terima kasih telah mempercayai Niswa Beauty 🌸 Semoga produk yang kakak pesan bisa membuat penampilan semakin cantik, elegan, dan percaya diri ✨💅💕\n\n";
    $pesan .= "📍 " . SALON_ADDRESS . "\n";
    $pesan .= "✨ *" . SALON_NAME . "* — Premium Beauty Experience";

    return $pesan;
}

// ── Builder pesan admin (order) ──
function buildPesanAdminOrder(array $d): string {
    $metode = strtoupper($d['payment_method'] ?? 'COD');

    $pesan  = "🛍️ *ORDER BARU — " . SALON_NAME . "*\n\n";
    $pesan .= "📦 *Order ID:* #{$d['order_id']}\n\n";
    $pesan .= "👤 *Nama:*     {$d['nama']}\n";
    $pesan .= "📱 *WA:*       {$d['whatsapp']}\n\n";
    $pesan .= "🛒 *Produk:*   {$d['product_name']}\n";
    $pesan .= "🔢 *Qty:*      {$d['qty']} pcs\n";
    $pesan .= "💰 *Harga:*    {$d['product_price']}\n";
    $pesan .= "🚚 *Ongkir:*   Rp 5.000 (Jepara)\n";
    $pesan .= "💳 *Total:*    *{$d['total']}*\n\n";
    $pesan .= "🚚 *Kirim ke:*\n";
    $pesan .= "{$d['alamat']}\n\n";
    $pesan .= "💳 *Metode Bayar:* {$metode}\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:* {$d['catatan']}\n";
    }
    $pesan .= "\n⏰ Dikirim: " . date('d/m/Y H:i') . " WIB\n\n";
    $pesan .= "Segera proses dan konfirmasi ke customer! 💬";

    return $pesan;
}

// ── Fungsi lama (backward-compatible) ──
function notifyCustomerOrder(array $d): void {
    kirimWA($d['whatsapp'], buildPesanCustomerOrder($d));
}
function notifyAdminOrder(array $d): void {
    kirimWA(ADMIN_WA, buildPesanAdminOrder($d));
}


// ═══════════════════════════════════════════════════════════════
//  DEBUG — Test koneksi Fonnte (panggil dari browser sekali)
//  Hapus atau komentari setelah berhasil!
//  Akses: notify.php?test=1&nomor=628xxxxxxxxx
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['test']) && $_GET['test'] === '1') {
    $nomor = $_GET['nomor'] ?? ADMIN_WA;
    echo "<pre style='font-family:monospace;font-size:14px;padding:20px;'>";
    echo "=== NISWÀ BEAUTY — Fonnte Debug ===\n\n";
    echo "Token     : " . FONNTE_TOKEN . "\n";
    echo "Target    : " . $nomor . "\n";
    echo "Timeout   : " . WA_TIMEOUT . " detik\n";
    echo "cURL      : " . (function_exists('curl_init') ? '✅ Available' : '❌ Not found') . "\n";
    echo "cURL Multi: " . (function_exists('curl_multi_init') ? '✅ Available (paralel aktif)' : '❌ Not found') . "\n";
    echo "fopen     : " . (ini_get('allow_url_fopen') ? '✅ On' : '❌ Off') . "\n\n";

    $ok = kirimWA($nomor, "🧪 Test notifikasi dari *" . SALON_NAME . "*\nJika pesan ini masuk, berarti sistem WA sudah berjalan! ✅");
    echo "Hasil     : " . ($ok ? "✅ Terkirim (cek WA)" : "❌ Gagal") . "\n";

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['target' => $nomor, 'message' => 'test'],
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => WA_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        echo "\nResponse Fonnte:\n" . ($raw ?: "(kosong)") . "\n";
        if ($err) echo "cURL Error: " . $err . "\n";
    }
    echo "</pre>";
    exit;
}