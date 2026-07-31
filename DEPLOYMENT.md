# DEPLOYMENT GUIDE - ADMS Server

## Status Deploy

✅ **Server**: `payroll.3putraperkasa.com`  
✅ **Repository**: `/alurelab/repositories/solution`  
✅ **Web Root**: `/alurelab/payroll.3putraperkasa.com`  
✅ **Database**: `alurelab_adms_payroll` (MySQL/MariaDB)  
✅ **Status**: LIVE & TESTED

---

## Server Specs

- **OS**: AlmaLinux/Rocky 9 (cPanel)
- **Web Server**: LiteSpeed (Apache compatible)
- **PHP**: 8.4.23
- **Database**: MariaDB 10.11.18
- **User**: alurelab

---

## Database Credentials

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=alurelab_adms_payroll
DB_USERNAME=alurelab_adms
DB_PASSWORD=Adms@2026Payroll
```

---

## API Endpoints

### 1. iClock Protocol (untuk mesin fingerprint)

**Ping/Health Check:**
```
GET https://payroll.3putraperkasa.com/iclock
Response: OK
```

**Push Attendance Data:**
```
POST https://payroll.3putraperkasa.com/iclock/cdata?SN={SERIAL_NUMBER}&table=ATTLOG
Content-Type: text/plain

{PIN} {YYYY-MM-DD} {HH:MM:SS} {STATUS} {WORKCODE}
```

Example:
```bash
curl -X POST "https://payroll.3putraperkasa.com/iclock/cdata?SN=SOL12345&table=ATTLOG" \
  -H "Content-Type: text/plain" \
  -d "1001 2026-03-06 08:00:11 0 1"
```

**Get Pending Commands:**
```
GET https://payroll.3putraperkasa.com/iclock/getrequest?SN={SERIAL_NUMBER}
Response: OK
```

### 2. Internal API (untuk aplikasi internal)

**Submit Attendance (JSON):**
```bash
POST https://payroll.3putraperkasa.com/api/attendance
Headers:
  X-API-Key: adms-secret-key-2024
  Content-Type: application/json

{
  "machine_sn": "SOL12345",
  "user_id": "1001",
  "scan_time": "2026-03-06 08:00:11",
  "status": "check_in",
  "raw_data": {
    "temperature": 36.5,
    "verification_mode": "fingerprint"
  }
}
```

**Get Latest Attendance (Dashboard):**
```bash
GET https://payroll.3putraperkasa.com/attendance/latest?date=2026-03-06&per_page=50
```

---

## Cara Setting Mesin Fingerprint

### Langkah 1: Masuk ke Menu ADMS
Di mesin fingerprint (ZKTeco/Solution), masuk ke menu:
```
Menu → Comm. → Server → ADMS
```

### Langkah 2: Konfigurasi Server
Isi setting berikut:

| Parameter | Value |
|-----------|-------|
| **Server Type** | ADMS / HTTP |
| **Server URL** | `https://payroll.3putraperkasa.com/iclock` |
| **Push Interval** | `1` (detik, untuk real-time) |
| **Heartbeat** | `60` (detik) |

### Langkah 3: Test Koneksi
Setelah save, mesin akan otomatis ping ke server. Anda bisa verify dengan:

```bash
# Cek dari server
curl https://payroll.3putraperkasa.com/iclock
# Response: OK

# Cek log real-time
ssh alurelab "tail -f ~/repositories/solution/storage/logs/adms-*.log"
```

### Langkah 4: Test Scan Jari
Minta karyawan scan jari, lalu cek data masuk:

```bash
curl -s https://payroll.3putraperkasa.com/attendance/latest | jq '.data.data[] | {user_id, scan_time, machine_sn}'
```

---

## Deploy Script

Untuk update code di masa depan, gunakan:

```bash
ssh alurelab "~/deploy-solution.sh"
```

Script ini akan:
1. Pull latest code dari GitHub
2. Install dependencies (composer)
3. Run migrations
4. Optimize Laravel
5. Copy public assets

---

## Monitoring & Troubleshooting

### Cek Log Real-time
```bash
ssh alurelab "tail -f ~/repositories/solution/storage/logs/adms-*.log"
```

### Cek Database
```bash
ssh alurelab "mysql -u alurelab_adms -p'Adms@2026Payroll' alurelab_adms_payroll -e 'SELECT * FROM attendance_logs ORDER BY created_at DESC LIMIT 10;'"
```

### Cek Error Log
```bash
ssh alurelab "tail -f ~/logs/php.error.log"
```

### Clear Cache (jika ada masalah)
```bash
ssh alurelab "cd ~/repositories/solution && php artisan optimize:clear"
```

---

## Struktur Data Attendance Log

Format data yang dikirim mesin fingerprint:
```
{PIN} {YYYY-MM-DD} {HH:MM:SS} {STATUS} {WORKCODE}
```

Contoh:
```
1001 2026-03-06 08:00:11 0 1
1002 2026-03-06 08:05:22 0 1
```

**Keterangan:**
- `PIN`: ID karyawan (user_id)
- `YYYY-MM-DD HH:MM:SS`: Waktu scan
- `STATUS`: Status kehadiran (0 = check-in, 1 = check-out, dll)
- `WORKCODE`: Kode kerja (opsional)

---

## Security Notes

1. **API Key**: `adms-secret-key-2024` (untuk internal API)
2. **iClock Endpoints**: Tidak pakai auth (karena mesin fingerprint tidak bisa kirim custom header)
3. **HTTPS**: Sudah enabled dengan SSL dari cPanel
4. **CSRF**: Disabled untuk `/iclock/*`, `/api/*`, `/cdata`, `/getrequest`

---

## Test Data

Saya sudah test dengan data dummy:
```bash
curl -X POST "https://payroll.3putraperkasa.com/iclock/cdata?SN=SOL12345&table=ATTLOG" \
  -H "Content-Type: text/plain" \
  -d "1001 2026-03-06 08:00:11 0 1
1002 2026-03-06 08:05:22 0 1"
```

Data sudah masuk dan bisa diakses via:
```bash
curl https://payroll.3putraperkasa.com/attendance/latest
```

---

## Next Steps

1. ✅ Server deployed & tested
2. ✅ Database created & migrated
3. ✅ API endpoints working
4. ⏳ **Setting mesin fingerprint** (ikuti panduan di atas)
5. ⏳ **Test dengan data real dari karyawan**
6. ⏳ **Integrasi dengan sistem payroll** (jika diperlukan)

---

**Created**: 2026-03-06  
**Last Updated**: 2026-03-06
