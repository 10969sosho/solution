# ADMS Server Troubleshooting Guide

## Server Setup Issues

### 1. Server Not Responding
**Problem**: Server tidak dapat diakses dari mesin fingerprint

**Solution**:
1. Cek server status:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Cek firewall:
   ```bash
   # Ubuntu/Debian
   sudo ufw allow 8000
   
   # CentOS/RHEL
   sudo firewall-cmd --permanent --add-port=8000/tcp
   sudo firewall-cmd --reload
   ```

3. Test konektivitas dari mesin:
   ```bash
   ping server-ip-address
   telnet server-ip-address 8000
   ```

### 2. Database Connection Error
**Problem**: Error "no such table: attendance_logs"

**Solution**:
1. Jalankan migration:
   ```bash
   php artisan migrate
   ```

2. Cek database connection:
   ```bash
   php artisan migrate:status
   ```

3. Reset database jika perlu:
   ```bash
   php artisan migrate:fresh
   ```

## API Issues

### 1. 419 Page Expired Error
**Problem**: Response 419 saat mengirim data

**Solution**:
1. Pastikan menggunakan endpoint API yang benar: `/api/attendance`
2. Cek CSRF configuration di `bootstrap/app.php`
3. Gunakan header `Content-Type: application/json`

### 2. 401 Unauthorized Error
**Problem**: Response 401 "Invalid API key"

**Solution**:
1. Cek API key di header atau parameter:
   ```bash
   curl -H "X-API-Key: adms-secret-key-2024" ...
   # atau
   curl -d "api_key=adms-secret-key-2024" ...
   ```

2. Cek environment variable:
   ```bash
   grep ADMS_API_KEY .env
   ```

3. Restart server setelah perubahan:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

### 3. 422 Validation Error
**Problem**: Response 422 dengan validation errors

**Solution**:
1. Cek required fields:
   - `machine_sn` (string, max 50)
   - `user_id` (string, max 50)
   - `scan_time` (valid datetime format)
   - `status` (string, max 20)

2. Valid format datetime:
   ```
   Y-m-d H:i:s (e.g., 2026-03-05 15:30:00)
   ```

3. Example valid request:
   ```json
   {
     "machine_sn": "ZK001234567",
     "user_id": "EMP001",
     "scan_time": "2026-03-05 15:30:00",
     "status": "check_in"
   }
   ```

### 4. 429 Too Many Requests
**Problem**: Response 429 rate limit exceeded

**Solution**:
1. Kurangi frekuensi request (max 100 per menit)
2. Implementasi retry dengan backoff
3. Cek rate limit configuration di middleware

## Data Issues

### 1. Data Not Appearing in Dashboard
**Problem**: Data tidak muncul di `/attendance/latest`

**Solution**:
1. Cek data di database:
   ```sql
   SELECT * FROM attendance_logs ORDER BY created_at DESC LIMIT 10;
   ```

2. Cek log file:
   ```bash
   tail -f storage/logs/adms-2026-03-05.log
   ```

3. Cek filter parameters di dashboard

### 2. Duplicate Data
**Problem**: Data absensi duplikat

**Solution**:
1. Implementasi unique constraint di database
2. Cek existing data sebelum insert:
   ```php
   $existing = AttendanceLog::where('machine_sn', $request->machine_sn)
     ->where('user_id', $request->user_id)
     ->where('scan_time', $request->scan_time)
     ->first();
   ```

3. Tambahkan validasi duplikasi di controller

## Mesin Fingerprint Configuration

### 1. Connection Test Failed
**Problem**: Mesin tidak dapat connect ke server

**Solution**:
1. Cek IP address dan port server
2. Pastikan server dapat diakses dari mesin
3. Cek firewall dan security settings
4. Test dengan ping dan telnet

### 2. Data Not Pushing
**Problem**: Mesin tidak mengirim data ke server

**Solution**:
1. Cek ADMS settings di mesin:
   - Server URL: `http://server-ip:8000/api/attendance`
   - Push interval: real-time atau 5 menit
   - Enable "Push Attendance Log to Server"

2. Cek log di mesin untuk error messages
3. Manual test dengan scan fingerprint

## Performance Issues

### 1. Slow Response Time
**Problem**: Response time > 3 detik

**Solution**:
1. Cek server resources (CPU, memory)
2. Optimize database queries
3. Add database indexes
4. Enable caching jika perlu

### 2. High Memory Usage
**Problem**: Server menggunakan memory tinggi

**Solution**:
1. Monitor memory usage:
   ```bash
   htop
   ```

2. Optimize PHP memory limit
3. Restart server secara berkala

## Logging and Debugging

### 1. Enable Debug Mode
**Problem**: Tidak cukup informasi untuk debugging

**Solution**:
1. Set APP_DEBUG=true di .env
2. Cek log files:
   ```bash
   tail -f storage/logs/laravel.log
   tail -f storage/logs/adms-2026-03-05.log
   ```

3. Tambahkan logging di controller:
   ```php
   Log::channel('adms')->debug('Debug message', ['data' => $data]);
   ```

### 2. Database Query Logging
**Problem**: Ingin melihat SQL queries

**Solution**:
1. Enable query log:
   ```php
   DB::enableQueryLog();
   // Your queries
   $queries = DB::getQueryLog();
   ```

2. Cek slow queries di log

## Security Issues

### 1. API Key Compromised
**Problem**: API key bocor atau tidak aman

**Solution**:
1. Generate new API key:
   ```bash
   php artisan tinker
   >>> Str::random(32);
   ```

2. Update .env file
3. Update configuration di mesin fingerprint
4. Restart server

### 2. IP Whitelisting
**Problem**: Ingin membatasi akses berdasarkan IP

**Solution**:
1. Tambahkan IP validation di middleware
2. Implementasi IP whitelist di server
3. Gunakan firewall untuk IP filtering

## Backup and Recovery

### 1. Database Backup
```bash
# SQLite backup
cp database/database.sqlite database/database-backup.sqlite

# MySQL backup (jika menggunakan MySQL)
mysqldump -u username -p database_name > backup.sql
```

### 2. Configuration Backup
```bash
cp .env .env.backup
cp -r config config-backup
```

### 3. Log Files Backup
```bash
tar -czf logs-backup.tar.gz storage/logs/
```

## Common Commands Reference

### Server Management
```bash
# Start server
php artisan serve --host=0.0.0.0 --port=8000

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check status
php artisan migrate:status
php artisan route:list
```

### Database Management
```bash
# Run migrations
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Fresh migration
php artisan migrate:fresh
```

### Testing
```bash
# Test API endpoint
curl -X POST http://localhost:8000/api/attendance \
  -H "Content-Type: application/json" \
  -H "X-API-Key: adms-secret-key-2024" \
  -d '{"machine_sn":"ZK001","user_id":"EMP001","scan_time":"2026-03-05 15:30:00","status":"check_in"}'

# Test dashboard
curl -X GET "http://localhost:8000/attendance/latest"
```

## Support
Untuk bantuan tambahan:
1. Cek log files untuk error details
2. Review documentation
3. Test dengan curl commands
4. Monitor server performance