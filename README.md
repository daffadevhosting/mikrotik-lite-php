# mikrotik-lite-php

📡 Mikrotik API client super ringan untuk PHP.  
Tidak perlu Composer, tidak ribet, cukup `require` dan langsung jalan!

```php -S localhost:1111```

## 🚀 Fitur

🔒 Support dual login: challenge-based (lama) dan direct login (baru)

🔁 Auto detect saat koneksi

✅ Kompatibel RouterOS v6 dan v7


- Koneksi langsung ke API Mikrotik (port 8728)
- Kirim perintah & baca respons (`/ip/hotspot/user/print`, dll)
- Ringan, tanpa dependensi eksternal

## 💻 Contoh Penggunaan

```php
require_once __DIR__ . '/../src/RouterOSClient.php';

use MikroTikLite\RouterOSClient;

$api = new RouterOSClient('192.168.88.1', 'admin', '');
$api->connect();

$response = $api->send('/ip/hotspot/user/print');
print_r($response);
```

## 📂 Struktur

- `src/` → Kode utama library
- `examples/` → Contoh file penggunaan
- `RouterOSClient.php` → Kelas utama komunikasi API

## 🛠️ Roadmap

- [x] Koneksi via socket
- [x] Login challenge-response
- [x] Kirim command dasar
- [x] Parse data `!re` → array terstruktur
- [x] Command helper: `add()`, `remove()`, `enable()`
- [x] Handle error `!trap`
- [x] Test Connect `!trap`

## 📄 License

MIT — bebas dipakai, ubah, dan dibagikan.
