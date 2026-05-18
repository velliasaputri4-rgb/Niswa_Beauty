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


// ═══════════════════════════════════════════════════════════════
//  HELPER — Kirim pesan WA via Fonnte
// ═══════════════════════════════════════════════════════════════
function kirimWA(string $nomor, string $pesan): bool {
    // Normalkan nomor (08xxx → 628xxx)
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    if (empty($nomor)) return false;

    // Jika token belum diisi, skip
    if (FONNTE_TOKEN === 'ISI_TOKEN_FONNTE_KAMU_DISINI') return false;

    $url     = 'https://api.fonnte.com/send';
    $payload = ['target' => $nomor, 'message' => $pesan];

    // ── Coba cURL dulu (lebih reliable di semua hosting) ──
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);
        if ($result !== false && empty($err)) return true;
    }

    // ── Fallback: file_get_contents ──
    $data = http_build_query($payload);
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: " . FONNTE_TOKEN . "\r\n" .
                         "Content-Type: application/x-www-form-urlencoded\r\n" .
                         "Content-Length: " . strlen($data) . "\r\n",
            'content' => $data,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ];
    $result = @file_get_contents($url, false, stream_context_create($opts));
    return $result !== false;
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
    // Kembalikan slot yang belum dipesan dan bukan jam yang baru dipilih
    return array_values(array_filter($allSlots, function($s) use ($booked, $jamDipesan) {
        return !in_array($s, $booked) && $s !== $jamDipesan;
    }));
}


// ═══════════════════════════════════════════════════════════════
//  BOOKING — Notifikasi ke CUSTOMER
// ═══════════════════════════════════════════════════════════════
function notifyCustomerBooking(array $d, array $availableSlots = []): void {
    $tgl      = formatTanggalID($d['tanggal']);
    $jenis    = ($d['jenis_layanan'] ?? 'datang') === 'home_service' ? '🏠 Home Service' : '🏪 Datang ke Tempat';
    $alamatHS = !empty($d['alamat_hs']) ? "\n🗺️ *Alamat:* " . $d['alamat_hs'] : '';

    $pesan  = "💅 *KONFIRMASI BOOKING — " . SALON_NAME . "*\n\n";
    $pesan .= "Halo *{$d['nama']}* 👋\n";
    $pesan .= "Booking kamu sudah berhasil terdaftar! 🌸\n\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "📋 *DETAIL BOOKING #{$d['booking_id']}*\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "💆 *Layanan:*    {$d['layanan']}\n";
    $pesan .= "📍 *Jenis:*      {$jenis}{$alamatHS}\n";
    $pesan .= "📅 *Tanggal:*   {$tgl}\n";
    $pesan .= "⏰ *Jam:*        {$d['jam']} WIB\n";
    $pesan .= "👥 *Orang:*      {$d['jumlah_orang']} orang\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*   {$d['catatan']}\n";
    }
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    // Tampilkan jam kosong lain di tanggal yang sama
    if (!empty($availableSlots)) {
        $pesan .= "🕐 *Jam Lain Tersedia ({$tgl}):*\n";
        foreach ($availableSlots as $sl) {
            $pesan .= "   • {$sl} WIB\n";
        }
        $pesan .= "\n";
        $pesan .= "⚠️ Jika jam yang kamu pilih ternyata penuh, tim kami akan konfirmasi dan menawarkan salah satu jam di atas. 😊\n\n";
    } else {
        $pesan .= "⚠️ *Status:* Menunggu konfirmasi tim kami\n\n";
    }

    $pesan .= "Tim kami akan menghubungi kamu dalam *< 1 jam*.\n\n";
    $pesan .= "📍 " . SALON_ADDRESS . "\n";
    $pesan .= "✨ *" . SALON_NAME . "* — Premium Beauty Experience";

    kirimWA($d['whatsapp'], $pesan);
}


// ═══════════════════════════════════════════════════════════════
//  BOOKING — Notifikasi ke ADMIN
// ═══════════════════════════════════════════════════════════════
function notifyAdminBooking(array $d, $conn): void {
    $tgl      = formatTanggalID($d['tanggal']);
    $jenis    = ($d['jenis_layanan'] ?? 'datang') === 'home_service' ? '🏠 Home Service' : '🏪 Datang ke Tempat';
    $alamatHS = !empty($d['alamat_hs']) ? "\n🗺️ *Alamat HS:* " . $d['alamat_hs'] : '';

    // Ambil jam kosong untuk info admin
    $raw = '';
    $r = mysqli_query($conn, "SELECT value FROM cms_booking_page WHERE section='booking' AND `key`='time_slots' LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) $raw = $row['value'];
    if (empty($raw)) $raw = "09:00\n10:00\n11:00\n13:00\n14:00\n15:00\n16:00\n17:00\n18:00\n19:00\n20:00";
    $allSlots = array_values(array_filter(array_map('trim', explode("\n", $raw))));
    $available = getAvailableSlots($conn, $d['tanggal'], $d['jam'], $allSlots);

    $pesan  = "📅 *BOOKING BARU — " . SALON_NAME . "*\n\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "📋 *Booking ID:* #{$d['booking_id']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "👤 *Nama:*     {$d['nama']}\n";
    $pesan .= "📱 *WA:*       {$d['whatsapp']}\n";
    $pesan .= "📧 *Email:*    {$d['email']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "💆 *Layanan:*  {$d['layanan']}\n";
    $pesan .= "📍 *Jenis:*    {$jenis}{$alamatHS}\n";
    $pesan .= "📅 *Tanggal:*  {$tgl}\n";
    $pesan .= "⏰ *Jam:*      {$d['jam']} WIB\n";
    $pesan .= "👥 *Orang:*    {$d['jumlah_orang']} orang\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*  {$d['catatan']}\n";
    }
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";

    // Info jam kosong untuk memudahkan admin konfirmasi
    if (!empty($available)) {
        $pesan .= "\n✅ *Jam Kosong di {$tgl}:*\n";
        foreach ($available as $sl) {
            $pesan .= "   • {$sl} WIB\n";
        }
        $pesan .= "\nJika jam *{$d['jam']}* penuh, tawarkan salah satu jam di atas ke customer.\n";
    } else {
        $pesan .= "\n⚠️ Semua slot di {$tgl} penuh. Hubungi customer untuk reschedule.\n";
    }

    $pesan .= "\n⏰ Dikirim: " . date('d/m/Y H:i') . " WIB\n";
    $pesan .= "Segera konfirmasi ke customer! 💬";

    // Ambil nomor admin dari DB jika tersedia
    $adminNum = ADMIN_WA;
    if ($conn) {
        $r2 = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='kontak' AND `key`='whatsapp' LIMIT 1");
        if ($r2 && $rw = mysqli_fetch_assoc($r2)) {
            $num = preg_replace('/[^0-9]/', '', $rw['value']);
            if (!empty($num)) $adminNum = $num;
        }
    }

    kirimWA($adminNum, $pesan);
}


// ═══════════════════════════════════════════════════════════════
//  ORDER PRODUK — Notifikasi ke CUSTOMER
// ═══════════════════════════════════════════════════════════════
function notifyCustomerOrder(array $d): void {
    $metode = strtoupper($d['payment_method'] ?? 'COD');

    $pesan  = "🛍️ *KONFIRMASI PESANAN — " . SALON_NAME . "*\n\n";
    $pesan .= "Halo *{$d['nama']}* 👋\n";
    $pesan .= "Pesanan kamu sudah kami terima! ✨\n\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "📦 *DETAIL PESANAN #{$d['order_id']}*\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "🛒 *Produk:*   {$d['product_name']}\n";
    $pesan .= "🔢 *Qty:*      {$d['qty']} pcs\n";
    $pesan .= "💰 *Harga:*    {$d['product_price']}\n";
    $pesan .= "🚚 *Ongkir:*   Rp 5.000 (Jepara)\n";
    $pesan .= "💳 *Total:*    *{$d['total']}*\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "🚚 *Pengiriman ke:*\n";
    $pesan .= "{$d['alamat']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "💳 *Pembayaran:* {$metode}\n";
    if ($metode === 'COD') {
        $pesan .= "   _(Bayar saat barang sampai)_\n";
    }
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:*  {$d['catatan']}\n";
    }
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $pesan .= "⚠️ *Status:* Menunggu konfirmasi\n\n";
    $pesan .= "Tim kami akan menghubungi kamu dalam *< 1 jam* untuk konfirmasi pengiriman. 😊\n\n";
    $pesan .= "📍 " . SALON_ADDRESS . "\n";
    $pesan .= "✨ *" . SALON_NAME . "* — Premium Beauty Experience";

    kirimWA($d['whatsapp'], $pesan);
}


// ═══════════════════════════════════════════════════════════════
//  ORDER PRODUK — Notifikasi ke ADMIN
// ═══════════════════════════════════════════════════════════════
function notifyAdminOrder(array $d): void {
    $metode = strtoupper($d['payment_method'] ?? 'COD');

    $pesan  = "🛍️ *ORDER BARU — " . SALON_NAME . "*\n\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "📦 *Order ID:* #{$d['order_id']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "👤 *Nama:*     {$d['nama']}\n";
    $pesan .= "📱 *WA:*       {$d['whatsapp']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "🛒 *Produk:*   {$d['product_name']}\n";
    $pesan .= "🔢 *Qty:*      {$d['qty']} pcs\n";
    $pesan .= "💰 *Harga:*    {$d['product_price']}\n";
    $pesan .= "🚚 *Ongkir:*   Rp 5.000 (Jepara)\n";
    $pesan .= "💳 *Total:*    *{$d['total']}*\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "🚚 *Kirim ke:*\n";
    $pesan .= "{$d['alamat']}\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "💳 *Metode Bayar:* {$metode}\n";
    if (!empty($d['catatan'])) {
        $pesan .= "📝 *Catatan:* {$d['catatan']}\n";
    }
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= "⏰ Dikirim: " . date('d/m/Y H:i') . " WIB\n\n";
    $pesan .= "Segera proses dan konfirmasi ke customer! 💬";

    kirimWA(ADMIN_WA, $pesan);
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
    echo "Token  : " . FONNTE_TOKEN . "\n";
    echo "Target : " . $nomor . "\n";
    echo "cURL   : " . (function_exists('curl_init') ? '✅ Available' : '❌ Not found') . "\n";
    echo "fopen  : " . (ini_get('allow_url_fopen') ? '✅ On' : '❌ Off') . "\n\n";

    // Test kirim
    $ok = kirimWA($nomor, "🧪 Test notifikasi dari *" . SALON_NAME . "*\nJika pesan ini masuk, berarti sistem WA sudah berjalan! ✅");
    echo "Hasil  : " . ($ok ? "✅ Terkirim (cek WA)" : "❌ Gagal") . "\n";

    // Coba langsung cURL dan tampilkan response mentah
    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['target' => $nomor, 'message' => 'test'],
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
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