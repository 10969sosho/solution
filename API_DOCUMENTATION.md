# ADMS Server API Documentation

## Overview
Server ADMS berbasis Laravel untuk menerima data absensi dari mesin fingerprint.

## Base URL
```
http://server-domain/api
```

## Authentication
Setiap request harus menyertakan API key melalui header `X-API-Key` atau parameter `api_key`.

### API Key
```
adms-secret-key-2024
```

## Endpoints

### 1. Submit Attendance Data
**POST** `/attendance`

Menerima data absensi dari mesin fingerprint.

#### Request Headers
```
Content-Type: application/json
X-API-Key: adms-secret-key-2024
```

#### Request Body
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

#### Field Description
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| machine_sn | string | Yes | Serial number mesin fingerprint |
| user_id | string | Yes | ID karyawan |
| scan_time | datetime | Yes | Waktu scan (format: Y-m-d H:i:s) |
| status | string | Yes | Status kehadiran (check_in/check_out) |
| raw_data | object | No | Data tambahan dari mesin |

#### Response Success (201)
```json
{
  "status": "success",
  "message": "Attendance data received successfully",
  "data": {
    "id": 1,
    "machine_sn": "ZK001234567",
    "user_id": "EMP001",
    "scan_time": "2026-03-05T15:30:00.000000Z",
    "status": "check_in"
  }
}
```

#### Response Error (422 - Validation Error)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "machine_sn": ["The machine sn field is required."],
    "user_id": ["The user id field is required."]
  }
}
```

#### Response Error (401 - Unauthorized)
```json
{
  "status": "error",
  "message": "Unauthorized: Invalid API key"
}
```

#### Response Error (500 - Server Error)
```json
{
  "status": "error",
  "message": "Internal server error",
  "error": "Error message"
}
```

### 2. Get Latest Attendance Data
**GET** `/attendance/latest`

Mendapatkan data absensi terbaru.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | date | No | Filter berdasarkan tanggal (Y-m-d) |
| user_id | string | No | Filter berdasarkan user ID |
| machine_sn | string | No | Filter berdasarkan serial number mesin |
| per_page | integer | No | Jumlah data per halaman (default: 50) |

#### Response Success (200)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "machine_sn": "ZK001234567",
        "user_id": "EMP001",
        "scan_time": "2026-03-05T15:30:00.000000Z",
        "status": "check_in",
        "raw_data": {
          "temperature": 36.5,
          "verification_mode": "fingerprint"
        },
        "ip_address": "127.0.0.1",
        "user_agent": "curl/8.7.1",
        "created_at": "2026-03-05T15:02:12.000000Z",
        "updated_at": "2026-03-05T15:02:12.000000Z"
      }
    ],
    "total": 1,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  }
}
```

## Rate Limiting
- Maksimum 100 request per menit per IP
- Response 429 Too Many Requests jika limit terlampaui

## Response Time
- Target response time: < 3 detik
- Data tersimpan dalam database: < 10 detik setelah scan

## Error Codes
| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

## Logging
Semua request dicatat dalam file log khusus:
```
storage/logs/adms-YYYY-MM-DD.log
```

## Testing Commands

### Test Success Request
```bash
curl -X POST http://localhost:8000/api/attendance \
  -H "Content-Type: application/json" \
  -H "X-API-Key: adms-secret-key-2024" \
  -d '{
    "machine_sn": "ZK001234567",
    "user_id": "EMP001",
    "scan_time": "2026-03-05 15:30:00",
    "status": "check_in",
    "raw_data": {
      "temperature": 36.5,
      "verification_mode": "fingerprint"
    }
  }'
```

### Test Dashboard
```bash
curl -X GET "http://localhost:8000/attendance/latest"
```

### Test dengan Filter
curl -X GET "http://localhost:8000/attendance/latest?user_id=EMP001&date=2026-03-05"
```

## Configuration

### Environment Variables
```env
ADMS_API_KEY=adms-secret-key-2024
```

### Database Table Structure
- **Table**: `attendance_logs`
- **Columns**:
  - `id` (bigint, primary key)
  - `machine_sn` (varchar 50, index)
  - `user_id` (varchar 50, index)
  - `scan_time` (timestamp, index)
  - `status` (varchar 20)
  - `raw_data` (json, nullable)
  - `ip_address` (varchar 45, nullable)
  - `user_agent` (varchar 255, nullable)
  - `created_at` (timestamp)
  - `updated_at` (timestamp)

## Performance Criteria
- Response time: < 3 detik
- Data processing: < 10 detik
- Error rate: < 1%
- Capacity: 50 scan per menit minimum

## Troubleshooting

### Common Issues
1. **419 Page Expired**: Pastikan menggunakan endpoint API yang benar
2. **401 Unauthorized**: Cek API key yang digunakan
3. **422 Validation Error**: Validasi input data sesuai format
4. **500 Internal Error**: Cek log file untuk detail error

### Log Files
- Application log: `storage/logs/laravel.log`
- ADMS log: `storage/logs/adms-YYYY-MM-DD.log`

## Next Steps
Setelah implementasi berhasil, sistem dapat dikembangkan dengan:
- Notifikasi real-time ke HR
- Generate laporan bulanan
- Integrasi dengan payroll system
- Mobile app untuk karyawan