# Dokumentasi Lengkap Proyek: ADMS Server (Attendance Data Management System)

## 1. Pendahuluan

**ADMS Server** adalah sistem backend yang dirancang untuk menerima, memproses, dan mengelola data absensi secara real-time dari mesin fingerprint. Dibangun menggunakan framework Laravel 12, sistem ini menyediakan API endpoint yang aman dan andal, serta dashboard sederhana untuk monitoring data.

Tujuan utama proyek ini adalah menyediakan solusi terpusat untuk pengumpulan data absensi yang efisien, yang dapat diintegrasikan lebih lanjut dengan sistem lain seperti HRIS atau payroll.

---

## 2. Fitur Utama

- Endpoint ADMS iClock: menerima data dari mesin di `/iclock/cdata` (GET/POST).
- Endpoint command mesin: `/iclock/getrequest` (GET) untuk request perintah mesin.
- Parsing format text: menerima body plain text (bukan JSON) seperti `PIN Date Time Status WorkCode`.
- Respon sederhana: mesin menerima respon `OK` (text/plain).
- Logging ADMS: setiap request mentah dicatat di channel log khusus `adms`.
- Rate limiting: membatasi request berlebih (100 request/menit).
- Dashboard monitoring: endpoint `/attendance/latest` untuk memantau data terbaru (filter & pagination).
- Endpoint internal (opsional): `/api/attendance` tetap ada untuk kebutuhan aplikasi internal (JSON + API Key).

---

## 3. Persyaratan Sistem

- **Server**:
    - PHP 8.1+
    - Web Server (Nginx atau Apache direkomendasikan)
    - Composer 2.x
- **Database**:
    - SQLite (default untuk development)
    - MySQL 5.7+ atau PostgreSQL 10+ (direkomendasikan untuk production)

---

## 4. Instalasi dan Setup

### Langkah-langkah Instalasi:

1.  **Clone Repository**
    ```bash
    git clone [URL_REPOSITORY_ANDA]
    cd adms-server
    ```

2.  **Install Dependencies**
    Gunakan Composer untuk menginstal semua package yang dibutuhkan.
    ```bash
    composer install
    ```

3.  **Konfigurasi Environment**
    Salin file `.env.example` menjadi `.env` dan generate application key.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Setup Database**
    Untuk development, cukup buat file database SQLite.
    ```bash
    touch database/database.sqlite
    ```
    Jalankan migrasi untuk membuat tabel `attendance_logs`.
    ```bash
    php artisan migrate
    ```

5.  **Jalankan Server Development**
    Server akan berjalan di `http://localhost:8000`.
    ```bash
    php artisan serve --host=0.0.0.0 --port=8000
    ```

---

## 5. Konfigurasi

Konfigurasi utama proyek terdapat di dalam file `.env`.

### Variabel Penting:

-   `APP_NAME`: Nama aplikasi (Contoh: "ADMS Server").
-   `APP_URL`: URL utama aplikasi (Contoh: `http://localhost:8000`).
-   `DB_CONNECTION`: Jenis koneksi database (`sqlite`, `mysql`, `pgsql`).
-   `LOG_LEVEL`: Level logging (`debug`, `info`, `error`).
-   `ADMS_API_KEY`: (Opsional) Kunci untuk endpoint internal `/api/attendance` (bukan untuk mesin).

### Contoh Konfigurasi `.env`:
```env
APP_NAME="ADMS Server"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=sqlite

# (Opsional) Kunci API untuk endpoint internal /api/attendance
ADMS_API_KEY=adms-secret-key-2024
```

---

## 6. Dokumentasi API

### 6.1 Endpoint ADMS iClock (untuk mesin fingerprint)

**Base URL**
```
http://server-domain
```

#### Endpoint A: Terima data absensi (cdata)
- **URL**: `/iclock/cdata`
- **Method**: `GET/POST`
- **Auth**: tidak menggunakan API Key (mesin tidak mengirim custom header)
- **CSRF**: dikecualikan untuk `/iclock/*`
- **Rate limit**: 100 request/menit

**Query Parameters (umum pada mesin)**
- `SN`: serial number mesin
- `table`: tipe data, contoh `ATTLOG`

**Body Request (plain text, contoh)**
```text
1001 2026-03-06 08:00:11 0 1
1002 2026-03-06 08:05:22 0 1
```

**Response (wajib sederhana)**
```text
OK
```

#### Endpoint B: Ambil perintah (getrequest)
- **URL**: `/iclock/getrequest`
- **Method**: `GET`

**Response awal**
```text
OK
```

### 6.2 Endpoint internal (opsional, untuk aplikasi non-mesin)

#### Endpoint: Submit data absensi (JSON)
- **URL**: `/api/attendance`
- **Method**: `POST`
- **Auth**: `X-API-Key` (untuk aplikasi internal saja)

**Request Body (JSON)**
```json
{
  "machine_sn": "ZK001234567",
  "user_id": "EMP001",
  "scan_time": "2026-03-05 15:30:00",
  "status": "check_in",
  "raw_data": {
    "temperature": 36.5,
    "verification_mode": "fingerprint"
  }
}
```

### 6.3 Dashboard monitoring

#### Endpoint: Lihat data absensi terbaru
- **URL**: `/attendance/latest`
- **Method**: `GET`

**Query Parameters**
- `date` (opsional): `Y-m-d`
- `user_id` (opsional)
- `machine_sn` (opsional)
- `per_page` (opsional, default 50)

---

## 7. Struktur Database

### Tabel: `attendance_logs`

| Kolom        | Tipe          | Atribut                               | Deskripsi                               |
|--------------|---------------|---------------------------------------|-----------------------------------------|
| `id`         | `bigint`      | `primary key`, `auto-increment`       | ID unik untuk setiap log                |
| `machine_sn` | `varchar(50)` | `index`                               | Serial number mesin absensi             |
| `user_id`    | `varchar(50)` | `index`                               | ID unik karyawan                        |
| `scan_time`  | `timestamp`   | `index`                               | Waktu saat scan dilakukan               |
| `status`     | `varchar(20)` |                                       | Status absensi (e.g., `check_in`)       |
| `raw_data`   | `json`        | `nullable`                            | Data mentah tambahan dari mesin         |
| `ip_address` | `varchar(45)` | `nullable`                            | Alamat IP mesin pengirim                |
| `user_agent` | `varchar(255)`| `nullable`                            | User agent dari request                 |
| `created_at` | `timestamp`   |                                       | Waktu record dibuat di database         |
| `updated_at` | `timestamp`   |                                       | Waktu record terakhir diupdate          |

---

## 8. Logging dan Monitoring

-   **Log Channel Khusus**: Semua aktivitas terkait ADMS dicatat di `storage/logs/adms-YYYY-MM-DD.log`.
-   **Informasi yang Dicatat**: IP, User Agent, Method, URL, Headers, dan Payload.
-   **Monitoring**: Dashboard di `/attendance/latest` dapat digunakan untuk memantau data yang masuk secara real-time.

---

## 9. Panduan Troubleshooting

### Masalah Umum:

1.  **Mesin tidak bisa kirim header X-API-Key**:
    -   **Penjelasan**: mesin fingerprint standar tidak mendukung custom header.
    -   **Solusi**: gunakan endpoint mesin `/iclock/cdata` (tanpa API Key). Endpoint `/api/attendance` hanya untuk aplikasi internal.

2.  **419 Page Expired**:
    -   **Penyebab**: CSRF protection masih aktif pada path yang dipanggil.
    -   **Solusi**: pastikan `/iclock/*` dan `/api/*` dikecualikan dari CSRF protection di `bootstrap/app.php`.

3.  **422 Unprocessable Entity**:
    -   **Penyebab**: validasi gagal pada endpoint internal `/api/attendance` (JSON).
    -   **Solusi**: periksa payload JSON dan format `scan_time` (`Y-m-d H:i:s`).

4.  **500 Internal Server Error**:
    -   **Penyebab**: Kesalahan pada logika server, koneksi database, atau lainnya.
    -   **Solusi**: Periksa file log `storage/logs/laravel.log` dan `storage/logs/adms-....log` untuk melihat detail error.

5.  **Data Tidak Masuk**:
    -   **Penyebab**: Mesin tidak dapat terhubung ke server atau ada kesalahan konfigurasi di mesin.
    -   **Solusi**:
        -   Lakukan "Test Connection" dari menu ADMS di mesin.
        -   Pastikan path mesin mengarah ke `/iclock/cdata`.
        -   Cek firewall (port 80/443 untuk production).

---

## 10. Testing

### Contoh Perintah `curl` untuk Testing:

**1. Test request standar mesin (iClock /iclock/cdata):**
```bash
curl -X POST "http://localhost:8000/iclock/cdata?SN=SOL12345&table=ATTLOG" \
  -H "Content-Type: text/plain" \
  -d "1001 2026-03-06 08:00:11 0 1"
```

**2. Test endpoint getrequest:**
```bash
curl -X GET "http://localhost:8000/iclock/getrequest"
```

**3. (Opsional) Test endpoint internal JSON (/api/attendance):**
```bash
curl -X POST http://localhost:8000/api/attendance \
  -H "Content-Type: application/json" \
  -H "X-API-Key: adms-secret-key-2024" \
  -d '{
    "machine_sn": "ZK001234567",
    "user_id": "EMP001",
    "scan_time": "2026-03-05 19:30:00",
    "status": "check_in"
  }'
```

**4. Test dashboard monitoring:**
```bash
curl -X GET "http://localhost:8000/attendance/latest"
```

**5. Test dashboard dengan filter:**
```bash
curl -X GET "http://localhost:8000/attendance/latest?user_id=EMP001&date=2026-03-05"
```

---

## 11. Rencana Pengembangan Selanjutnya

-   **Notifikasi Real-time**: Mengirim notifikasi ke departemen HR (via Email, Slack, atau WhatsApp) saat ada data absensi masuk.
-   **Laporan Bulanan**: Fitur untuk men-generate laporan absensi bulanan per karyawan dalam format PDF atau Excel.
-   **Integrasi Payroll**: Menghubungkan data absensi dengan sistem penggajian untuk otomatisasi perhitungan gaji.
-   **Aplikasi Mobile**: Membuat aplikasi mobile untuk karyawan agar dapat melihat riwayat absensi mereka.
-   **Advanced Analytics**: Dashboard analitik yang lebih canggih untuk memvisualisasikan tren kehadiran, keterlambatan, dll.
