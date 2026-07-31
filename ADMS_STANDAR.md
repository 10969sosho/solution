# Dokumentasi ADMS Standar iClock

Sistem ini telah diperbarui untuk mendukung protokol standar mesin fingerprint (ADMS/iClock).

## 1. Endpoints Utama

| Endpoint | Method | Fungsi |
|----------|--------|--------|
| `/iclock/cdata` | `GET/POST` | Menerima data transaksi (absensi) dan data mesin |
| `/iclock/getrequest` | `GET` | Mesin mengambil perintah dari server |

## 2. Format Data ADMS

### Request `cdata` (POST)
Mesin mengirim data dalam format text plain melalui body request, dengan informasi tambahan di query parameters.

**Query Parameters:**
- `SN`: Serial Number mesin
- `table`: Jenis data (misal: `ATTLOG` untuk data absensi)

**Body Request (Plain Text):**
```text
1001 2026-03-06 08:00:11 0 1
1002 2026-03-06 08:05:22 0 1
```
*Format: PIN [spasi] DateTime [spasi] Status [spasi] WorkCode*

### Response
Server harus memberikan respon teks sederhana:
```text
OK
```

## 3. Konfigurasi Mesin

Untuk menghubungkan mesin ke server ini, gunakan pengaturan berikut di menu ADMS mesin:

- **Server Address**: `laravel.digitalblitar.com` (atau IP server Anda)
- **Port**: `80` (atau port web server Anda)
- **Path**: `/iclock/cdata` (Opsional, tergantung model mesin)

## 4. Keamanan

Endpoints ADMS ini sengaja dikonfigurasi **tanpa API Key** karena mesin fingerprint standar tidak mendukung pengiriman custom HTTP headers.
- **CSRF Protection**: Telah dimatikan khusus untuk path `/iclock/*`.
- **Rekomendasi**: Gunakan IP Whitelisting di sisi web server (Nginx/Apache) untuk membatasi akses hanya dari IP mesin yang terdaftar.

## 5. Logging & Debugging

Semua data mentah yang masuk dari mesin dicatat di:
`storage/logs/adms-YYYY-MM-DD.log`

Contoh isi log:
```json
{
    "sn": "SOL12345",
    "table": "ATTLOG",
    "body": "1001 2026-03-06 08:00:11 0 1",
    "ip": "127.0.0.1"
}
```

## 6. Testing Manual (cURL)

Gunakan perintah ini untuk mensimulasikan mesin mengirim data:

```bash
curl -X POST "http://localhost:8000/iclock/cdata?SN=SOL12345&table=ATTLOG" \
  -H "Content-Type: text/plain" \
  -d "1001 2026-03-06 08:00:11 0 1"
```

## 7. Troubleshooting

- **Respon bukan OK**: Pastikan controller mengembalikan `response("OK", 200)`.
- **Data tidak masuk DB**: Cek log di `storage/logs/adms-...` untuk melihat apakah ada error saat parsing teks.
- **Koneksi Gagal**: Pastikan port 80/8000 terbuka di firewall server.
