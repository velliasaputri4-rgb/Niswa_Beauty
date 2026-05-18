<?php
// tes_wa.php — Cek koneksi Fonnte (tanpa cURL)
// Buka: http://localhost/niswa-beauty/tes_wa.php
// HAPUS FILE INI SETELAH SELESAI TEST

require_once __DIR__ . '/notify.php';

$nomor_test = '628971440805'; // ← ganti nomor WA kamu sendiri untuk test
$pesan_test = "✅ Test notifikasi NISWÀ BEAUTY berhasil! WA notification aktif.";

echo "<h2>CEK KONEKSI FONNTE</h2>";

// 1. Cek allow_url_fopen
echo "<b>1. allow_url_fopen:</b> ";
echo ini_get('allow_url_fopen') ? "✅ Aktif<br>" : "❌ Tidak aktif — tambahkan <code>allow_url_fopen=On</code> di php.ini<br>";

// 2. Cek Token
echo "<b>2. Token:</b> ";
echo FONNTE_TOKEN !== 'ISI_TOKEN_FONNTE_KAMU_DISINI' ? "✅ Sudah diisi<br>" : "❌ Belum diisi di notify.php<br>";

// 3. Kirim pesan test
echo "<b>3. Kirim WA ke $nomor_test:</b><br>";
if (!ini_get('allow_url_fopen')) {
    echo "❌ Skip (allow_url_fopen tidak aktif)<br>";
} elseif (FONNTE_TOKEN === 'ISI_TOKEN_FONNTE_KAMU_DISINI') {
    echo "❌ Skip (token belum diisi)<br>";
} else {
    $nomor = preg_replace('/[^0-9]/', '', $nomor_test);
    if (substr($nomor, 0, 1) === '0') $nomor = '62' . substr($nomor, 1);

    $data = http_build_query(['target' => $nomor, 'message' => $pesan_test]);
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: " . FONNTE_TOKEN . "\r\n" .
                         "Content-Type: application/x-www-form-urlencoded\r\n" .
                         "Content-Length: " . strlen($data) . "\r\n",
            'content' => $data,
            'timeout' => 15,
            'ignore_errors' => true,
        ]
    ];
    $result = @file_get_contents('https://api.fonnte.com/send', false, stream_context_create($opts));

    if ($result === false) {
        echo "❌ Gagal terhubung ke Fonnte. Cek koneksi internet server.<br>";
    } else {
        $decoded = json_decode($result, true);
        echo "<b>Response Fonnte:</b><pre>" . print_r($decoded, true) . "</pre>";
        if (!empty($decoded['status']) && $decoded['status'] === true) {
            echo "✅ <b>BERHASIL! Cek WA kamu.</b>";
        } else {
            echo "❌ Gagal. Lihat response di atas untuk detail error.";
        }
    }
}