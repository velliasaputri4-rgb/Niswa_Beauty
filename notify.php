<?php
/**
 * notify.php — NISWÀ BEAUTY
 * Helper notifikasi WhatsApp otomatis ke customer & admin
 * ─────────────────────────────────────────────────────────
 * Cara kerja:
 *   - Menyimpan pesan WA ke session agar bisa dibuka oleh JS setelah redirect
 *   - Mendukung notifikasi ORDER (pembelian produk) dan BOOKING (reservasi)
 *   - Nomor admin diambil dari CMS / fallback hardcoded
 */

if (!function_exists('getAdminWhatsapp')) {
    function getAdminWhatsapp($conn = null) {
        // Coba ambil dari CMS
        if ($conn) {
            $r = mysqli_query($conn, "SELECT value FROM cms_content WHERE section='kontak' AND `key`='whatsapp' LIMIT 1");
            if ($r && $row = mysqli_fetch_assoc($r)) {
                $num = preg_replace('/[^0-9]/', '', $row['value']);
                if (strlen($num) >= 10) return $num;
            }
        }
        return '6289714408805'; // Fallback default
    }
}

/* ══════════════════════════════════════════════════════
   NOTIFIKASI ORDER (Pembelian Produk)
══════════════════════════════════════════════════════ */

/**
 * Notifikasi ke CUSTOMER saat order berhasil
 * Menyimpan data ke session agar JS bisa auto-buka WA
 */
if (!function_exists('notifyCustomerOrder')) {
    function notifyCustomerOrder($data) {
        $order_id      = $data['order_id']       ?? '';
        $nama          = $data['nama']            ?? '';
        $whatsapp      = $data['whatsapp']        ?? '';
        $product_name  = $data['product_name']    ?? '';
        $product_price = $data['product_price']   ?? '';
        $qty           = $data['qty']             ?? 1;
        $total         = $data['total']           ?? '';
        $alamat        = $data['alamat']          ?? '';
        $catatan       = $data['catatan']         ?? '';
        $payment       = $data['payment_method']  ?? 'COD';

        // Bersihkan nomor WA customer (hapus karakter non-angka, ubah 08xx → 628xx)
        $custNum = preg_replace('/[^0-9]/', '', $whatsapp);
        if (substr($custNum, 0, 1) === '0') {
            $custNum = '62' . substr($custNum, 1);
        }

        $pesan  = "🛍️ *KONFIRMASI PESANAN - NISWÀ BEAUTY*\n\n";
        $pesan .= "Halo *{$nama}* 👋\n";
        $pesan .= "Terima kasih sudah memesan di *Niswà Beauty*! Pesanan Anda telah kami terima dan sedang diproses. 🌸\n\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "📋 *DETAIL PESANAN #{$order_id}*\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "📦 *Produk:* {$product_name}\n";
        $pesan .= "🔢 *Jumlah:* {$qty} pcs\n";
        $pesan .= "💰 *Total:* {$total}\n";
        $pesan .= "💵 *Pembayaran:* {$payment}\n";
        $pesan .= "📍 *Alamat:* {$alamat}\n";
        if (!empty($catatan)) {
            $pesan .= "📝 *Catatan:* {$catatan}\n";
        }
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $pesan .= "⏳ Tim kami akan segera menghubungi Anda untuk konfirmasi pengiriman dan ongkos kirim.\n\n";
        $pesan .= "Jika ada pertanyaan, jangan ragu untuk membalas pesan ini ya! 😊\n\n";
        $pesan .= "✨ *Niswà Beauty* — Premium Beauty Experience";

        // Simpan ke session untuk dibuka oleh JS
        if (!isset($_SESSION)) session_start();
        $_SESSION['wa_customer_order'] = [
            'number'  => $custNum,
            'message' => $pesan,
            'type'    => 'order',
        ];

        return true;
    }
}

/**
 * Notifikasi ke ADMIN saat order baru masuk
 */
if (!function_exists('notifyAdminOrder')) {
    function notifyAdminOrder($data, $conn = null) {
        $order_id      = $data['order_id']       ?? '';
        $nama          = $data['nama']            ?? '';
        $whatsapp      = $data['whatsapp']        ?? '';
        $product_name  = $data['product_name']    ?? '';
        $product_price = $data['product_price']   ?? '';
        $qty           = $data['qty']             ?? 1;
        $total         = $data['total']           ?? '';
        $alamat        = $data['alamat']          ?? '';
        $catatan       = $data['catatan']         ?? '';
        $payment       = $data['payment_method']  ?? 'COD';

        $adminNum = getAdminWhatsapp($conn);

        $pesan  = "🔔 *ORDER BARU MASUK - NISWÀ BEAUTY*\n\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "📋 *Order ID:* #{$order_id}\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "👤 *Nama:* {$nama}\n";
        $pesan .= "📱 *WhatsApp:* {$whatsapp}\n";
        $pesan .= "📍 *Alamat:* {$alamat}\n";
        $pesan .= "📦 *Produk:* {$product_name}\n";
        $pesan .= "🔢 *Qty:* {$qty}\n";
        $pesan .= "💰 *Total:* {$total}\n";
        $pesan .= "💵 *Bayar:* {$payment}\n";
        if (!empty($catatan)) {
            $pesan .= "📝 *Catatan:* {$catatan}\n";
        }
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "⏰ " . date('d/m/Y H:i') . " WIB\n\n";
        $pesan .= "Segera proses pesanan ini! 🚀";

        // Simpan ke session untuk dibuka oleh JS (admin notification)
        if (!isset($_SESSION)) session_start();
        $_SESSION['wa_admin_order'] = [
            'number'  => $adminNum,
            'message' => $pesan,
            'type'    => 'order_admin',
        ];

        return true;
    }
}

/* ══════════════════════════════════════════════════════
   NOTIFIKASI BOOKING
══════════════════════════════════════════════════════ */

/**
 * Notifikasi ke CUSTOMER saat booking berhasil
 * Menyertakan konfirmasi jam + info jam yang tersedia
 */
if (!function_exists('notifyCustomerBooking')) {
    function notifyCustomerBooking($data, $available_slots = []) {
        $booking_id    = $data['booking_id']    ?? '';
        $nama          = $data['nama']          ?? '';
        $whatsapp      = $data['whatsapp']      ?? '';
        $layanan       = $data['layanan']       ?? '';
        $tanggal       = $data['tanggal']       ?? '';
        $jam           = $data['jam']           ?? '';
        $jumlah_orang  = $data['jumlah_orang']  ?? 1;
        $catatan       = $data['catatan']       ?? '';

        // Format tanggal Indonesia
        $tgl_format = $tanggal;
        if (!empty($tanggal)) {
            $bulan_id = ['','Januari','Februari','Maret','April','Mei','Juni',
                         'Juli','Agustus','September','Oktober','November','Desember'];
            $ts = strtotime($tanggal);
            if ($ts) {
                $tgl_format = date('d', $ts) . ' ' . $bulan_id[(int)date('m', $ts)] . ' ' . date('Y', $ts);
            }
        }

        // Bersihkan nomor WA customer
        $custNum = preg_replace('/[^0-9]/', '', $whatsapp);
        if (substr($custNum, 0, 1) === '0') {
            $custNum = '62' . substr($custNum, 1);
        }

        $pesan  = "💅 *KONFIRMASI BOOKING - NISWÀ BEAUTY*\n\n";
        $pesan .= "Halo *{$nama}* 👋\n";
        $pesan .= "Booking Anda telah berhasil terdaftar! 🌸\n\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "📋 *DETAIL BOOKING #{$booking_id}*\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "💆 *Layanan:* {$layanan}\n";
        $pesan .= "📅 *Tanggal:* {$tgl_format}\n";
        $pesan .= "⏰ *Jam:* {$jam} WIB\n";
        $pesan .= "👥 *Jumlah Orang:* {$jumlah_orang} orang\n";
        if (!empty($catatan)) {
            $pesan .= "📝 *Catatan:* {$catatan}\n";
        }
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Tampilkan jam tersedia di hari yang sama jika ada
        if (!empty($available_slots)) {
            $pesan .= "🕐 *Jam Lain yang Tersedia di Tanggal Tersebut:*\n";
            foreach ($available_slots as $slot) {
                $pesan .= "   • {$slot} WIB\n";
            }
            $pesan .= "\n";
        }

        $pesan .= "⚠️ *Status:* Menunggu konfirmasi dari tim kami\n\n";
        $pesan .= "Tim kami akan menghubungi Anda dalam waktu *< 1 jam* untuk konfirmasi jadwal. Jika jam yang dipilih tidak tersedia, kami akan menawarkan jam alternatif.\n\n";
        $pesan .= "📍 *Lokasi:* Jl. Watulumpang, Bangsri, Jepara\n\n";
        $pesan .= "Sampai jumpa di Niswà Beauty! ✨\n";
        $pesan .= "✨ *Niswà Beauty* — Premium Beauty Experience";

        // Simpan ke session
        if (!isset($_SESSION)) session_start();
        $_SESSION['wa_customer_booking'] = [
            'number'  => $custNum,
            'message' => $pesan,
            'type'    => 'booking',
        ];

        return true;
    }
}

/**
 * Notifikasi ke ADMIN saat booking baru
 */
if (!function_exists('notifyAdminBooking')) {
    function notifyAdminBooking($data, $conn = null) {
        $booking_id    = $data['booking_id']    ?? '';
        $nama          = $data['nama']          ?? '';
        $whatsapp      = $data['whatsapp']      ?? '';
        $email         = $data['email']         ?? '';
        $layanan       = $data['layanan']       ?? '';
        $tanggal       = $data['tanggal']       ?? '';
        $jam           = $data['jam']           ?? '';
        $jumlah_orang  = $data['jumlah_orang']  ?? 1;
        $catatan       = $data['catatan']       ?? '';

        $adminNum = getAdminWhatsapp($conn);

        // Format tanggal
        $tgl_format = $tanggal;
        if (!empty($tanggal)) {
            $bulan_id = ['','Januari','Februari','Maret','April','Mei','Juni',
                         'Juli','Agustus','September','Oktober','November','Desember'];
            $ts = strtotime($tanggal);
            if ($ts) {
                $tgl_format = date('d', $ts) . ' ' . $bulan_id[(int)date('m', $ts)] . ' ' . date('Y', $ts);
            }
        }

        $pesan  = "📅 *BOOKING BARU - NISWÀ BEAUTY*\n\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "📋 *Booking ID:* #{$booking_id}\n";
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "👤 *Nama:* {$nama}\n";
        $pesan .= "📱 *WhatsApp:* {$whatsapp}\n";
        $pesan .= "📧 *Email:* {$email}\n";
        $pesan .= "💆 *Layanan:* {$layanan}\n";
        $pesan .= "📅 *Tanggal:* {$tgl_format}\n";
        $pesan .= "⏰ *Jam:* {$jam} WIB\n";
        $pesan .= "👥 *Jumlah:* {$jumlah_orang} orang\n";
        if (!empty($catatan)) {
            $pesan .= "📝 *Catatan:* {$catatan}\n";
        }
        $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
        $pesan .= "⏰ Masuk: " . date('d/m/Y H:i') . " WIB\n\n";
        $pesan .= "Segera konfirmasi booking ini ke customer! 💬";

        if (!isset($_SESSION)) session_start();
        $_SESSION['wa_admin_booking'] = [
            'number'  => $adminNum,
            'message' => $pesan,
            'type'    => 'booking_admin',
        ];

        return true;
    }
}

/**
 * Cek slot jam yang TERSEDIA di tanggal tertentu
 * Mengembalikan array jam yang belum dipesan
 */
if (!function_exists('getAvailableSlots')) {
    function getAvailableSlots($conn, $tanggal, $jam_dipilih, $all_slots = []) {
        if (!$conn || empty($tanggal)) return $all_slots;

        // Ambil semua jam yang sudah dipesan di tanggal tersebut
        $tgl_esc = mysqli_real_escape_string($conn, $tanggal);
        $result = mysqli_query($conn,
            "SELECT time FROM bookings WHERE date = '{$tgl_esc}' ORDER BY time"
        );

        $booked = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $booked[] = $row['time'];
            }
        }

        // Filter: jam tersedia = semua slot dikurangi yang sudah dipesan & jam yang dipilih
        $available = [];
        foreach ($all_slots as $slot) {
            if (!in_array($slot, $booked) && $slot !== $jam_dipilih) {
                $available[] = $slot;
            }
        }

        return $available;
    }
}