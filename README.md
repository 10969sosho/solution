# ADMS Server - Attendance Data Management System

Server Laravel untuk menerima dan mengelola data absensi dari mesin fingerprint.

## 🚀 Fitur Utama

- ✅ **API Endpoint** untuk menerima data absensi dari mesin fingerprint
- ✅ **Authentication** dengan API key untuk keamanan
- ✅ **Validasi Data** untuk memastikan data yang diterima valid
- ✅ **Logging System** khusus untuk monitoring dan debugging
- ✅ **Rate Limiting** untuk mencegah spam (100 request/menit)
- ✅ **Dashboard Monitoring** untuk melihat data absensi terbaru
- ✅ **Filter & Search** berdasarkan tanggal, user ID, dan mesin
- ✅ **Pagination** untuk performance optimal
- ✅ **Error Handling** yang komprehensif

## 📋 Persyaratan Sistem

- PHP 8.1+
- Laravel 12.0+
- SQLite/MySQL/PostgreSQL
- Composer

## 🔧 Instalasi

### 1. Clone Repository
```bash
git clone [repository-url]
cd adms-server
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
# SQLite (default)
touch database/database.sqlite

# Jalankan migration
php artisan migrate
```

### 5. Start Server
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## 📡 ADMS iClock Endpoints (Standard Mesin)

Endpoint ini digunakan oleh mesin fingerprint standar (ZKTeco/Solution) untuk mengirim data:

### 1. Submit Data Absensi (cdata)
```http
POST /iclock/cdata?SN=SOL12345&table=ATTLOG
Content-Type: text/plain

1001 2026-03-06 08:00:11 0 1
```

### 2. Get Machine Commands
```http
GET /iclock/getrequest
```

### 3. Response Standar
Server akan selalu merespon dengan:
```text
OK
```

## 📡 API Endpoints (Custom/Internal)

Endpoint ini dapat digunakan oleh aplikasi internal:

### Submit Attendance Data (JSON)
```http
POST /api/attendance
Content-Type: application/json
X-API-Key: adms-secret-key-2024

{
  "machine_sn": "ZK001234567",
  "user_id": "EMP001",
  "scan_time": "2026-03-05 15:30:00",
  "status": "check_in"
}
```

### Get Latest Attendance Data
```http
GET /attendance/latest?user_id=EMP001&date=2026-03-05
```

## 🔑 Konfigurasi

### Environment Variables
```env
APP_NAME="ADMS Server"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite

# Logging
LOG_LEVEL=debug

# ADMS Configuration
ADMS_API_KEY=adms-secret-key-2024
```

### API Key Configuration
API key dapat diubah di file `.env`:
```env
ADMS_API_KEY=your-secret-api-key-here
```

## 📊 Testing

### Test API Endpoint
```bash
# Test success request
curl -X POST http://localhost:8000/api/attendance \
  -H "Content-Type: application/json" \
  -H "X-API-Key: adms-secret-key-2024" \
  -d '{
    "machine_sn": "ZK001234567",
    "user_id": "EMP001",
    "scan_time": "2026-03-05 15:30:00",
    "status": "check_in"
  }'

# Test dashboard
curl -X GET "http://localhost:8000/attendance/latest"
```

### Test dengan Invalid API Key
```bash
curl -X POST http://localhost:8000/api/attendance \
  -H "Content-Type: application/json" \
  -H "X-API-Key: wrong-key" \
  -d '{"machine_sn":"ZK001","user_id":"EMP001","scan_time":"2026-03-05 15:30:00","status":"check_in"}'
```

## 📁 Struktur Database

### Tabel `attendance_logs`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| machine_sn | varchar(50) | Serial number mesin |
| user_id | varchar(50) | ID karyawan |
| scan_time | timestamp | Waktu scan |
| status | varchar(20) | Status (check_in/check_out) |
| raw_data | json | Data tambahan |
| ip_address | varchar(45) | IP address mesin |
| user_agent | varchar(255) | User agent |
| created_at | timestamp | Waktu pembuatan |
| updated_at | timestamp | Waktu update |

## 📝 Logging

### Log Files
- **Application Log**: `storage/logs/laravel.log`
- **ADMS Log**: `storage/logs/adms-YYYY-MM-DD.log`

### Monitoring
Semua request dicatat dengan detail:
- IP address pengirim
- User agent
- Request payload
- Response status
- Error messages (jika ada)

## 🎯 Performance Criteria

- ✅ **Response Time**: < 3 detik
- ✅ **Data Processing**: < 10 detik
- ✅ **Error Rate**: < 1%
- ✅ **Capacity**: 50+ scan per menit
- ✅ **Uptime**: 99.9%

## 🔒 Security Features

- API Key Authentication
- Rate Limiting (100 requests/minute)
- Input Validation
- SQL Injection Protection
- XSS Protection
- CSRF Protection (excluded untuk API)

## 🚨 Error Handling

### HTTP Status Codes
| Code | Deskripsi |
|------|-----------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

### Error Response Format
```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## 🔧 Troubleshooting

### Common Issues

1. **419 Page Expired**: Pastikan menggunakan endpoint API yang benar
2. **401 Unauthorized**: Cek API key configuration
3. **422 Validation Error**: Validasi input data
4. **500 Internal Error**: Cek log files untuk details

### Log Analysis
```bash
# Monitor real-time logs
tail -f storage/logs/adms-2026-03-05.log

# Check recent errors
grep -i "error" storage/logs/laravel.log
```

## 📚 Documentation

- [API Documentation](API_DOCUMENTATION.md) - Complete API reference
- [Troubleshooting Guide](TROUBLESHOOTING.md) - Common issues and solutions

## 🚀 Deployment

### Production Setup
1. Setup web server (Nginx/Apache)
2. Configure SSL certificate
3. Setup database (MySQL/PostgreSQL recommended)
4. Configure environment variables
5. Setup monitoring and alerting

### Server Configuration
```bash
# Example Nginx configuration
server {
    listen 80;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 📈 Monitoring

### Health Check
```bash
curl -X GET http://localhost:8000/up
```

### Metrics to Monitor
- Response time
- Error rate
- Database performance
- Memory usage
- Disk space

## 🔮 Next Steps

Setelah implementasi berhasil, sistem dapat dikembangkan dengan:

- 📱 **Mobile App** untuk karyawan
- 📊 **Real-time Notifications** ke HR
- 📈 **Monthly Reports Generation**
- 💰 **Payroll System Integration**
- 🎯 **Advanced Analytics**
- 🔐 **Multi-factor Authentication**
- 🌐 **Multi-language Support**

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

This project is licensed under the MIT License.

## 📞 Support

Untuk bantuan dan pertanyaan:
1. Cek [troubleshooting guide](TROUBLESHOOTING.md)
2. Review log files
3. Test dengan curl commands
4. Hubungi development team

---

**ADMS Server** - Making attendance management simple and efficient! 🎯