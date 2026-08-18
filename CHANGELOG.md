# CHANGELOG

Semua perubahan signifikan pada ADMS HRIS dicatat di sini.

## [Unreleased]

### 2026-08-18 — Fase 4–5: Manajemen Izin & Pinjaman/Kasbon

**Ditambahkan:**
- Tabel `permits` — izin/alfa karyawan dengan durasi & status (pending/approved/rejected).
- `PermitController` + views (`/permits`) — otomatis menentukan tipe: izin > 30 menit = `salary_deduction`, ≤ 30 menit = `no_deduction`.
- Tabel `loans` & `loan_payments` — pinjaman/kasbon dengan track rekam pembayaran.
- `LoanController` + views (`/loans`, `/loans/create`, `/loans/{loan}`, `/loans/mutasi`) — sisa bon dihitung otomatis; status pinjaman otomatis menjadi `paid` saat lunas.
- Relasi `loans()` di `Employee`, menu sidebar **Manajemen Izin** & **Pinjaman/Kasbon**.

**Testing:** 17 test pass.

### 2026-08-18 — Fase 6: Payroll & THR

**Ditambahkan:**
- Tabel `payrolls` (base_salary, late_deduction, loan_deduction, absence_deduction, attendance_bonus, net_salary, breakdown JSON, status draft/paid).
- `config/payroll.php` — aturan tarif:
  - Denda keterlambatan **bertingkat**: 0–30 mnt Rp 1.000/mnt, 31–60 mnt Rp 1.500/mnt, > 60 mnt Rp 2.000/mnt.
  - Pemotongan ketidakhadiran: gaji pokok / 22 hari kerja.
  - Bonus kehadiran per tier (A: 300rb, B: 200rb, C: 100rb, default 150rb) — diberikan jika **tidak** ada izin disetujui pada bulan tersebut.
- `PayrollService` — hitung per karyawan, generate massal (dalam transaksi), mark-paid.
- `PayrollController` + views (`/payrolls`) dan halaman **Kalkulasi THR** (`/payrolls/thr`).
- **THR dengan diferensiasi masa kerja**: masa kerja ≥ 5 tahun = THR penuh (1× gaji pokok); < 5 tahun = proporsional (bulan kerja tahun berjalan / 12).
- Payroll berstatus `paid` tidak dapat digenerate ulang.

**Testing:** 26 test pass.

### 2026-08-18 — Fase 7: Hak Akses & Autentikasi

**Ditambahkan:**
- Kolom `role` di tabel `users` (`super_admin` / `admin_operasional`).
- Autentikasi login (halaman `/login`, logout) — seluruh halaman admin kini memerlukan login.
- `CheckRole` middleware + `config/hrms.php` (daftar role & jabatan operasional).
- `AuthController`, `User::isSuperAdmin()`, `isAdminOperasional()`, `canManagePayroll()`.
- Default akun via seeder: `admin@adms.test` / `password` (Super Admin).
- Pembatasan **Admin Operasional**:
  - Hanya dapat melihat/mengelola karyawan **Gudang/Kandang** (jabatan operasional).
  - **Tidak** dapat mengakses **Payroll/THR** (rekam gaji) → 403.
  - **Tidak** dapat mengisi/melihat gaji pokok & tier gaji (field disembunyikan, nilai diabaikan).
  - Laporan dibatasi hanya karyawan operasional.
  - Menu Payroll disembunyikan di sidebar.

**Testing:** 36 test pass (80 assertions).

### 2026-08-18 — Fase 1–3: Master Data, Pengolahan Absensi, Laporan

**Ditambahkan:**
- Kolom baru di `employees`: `location`, `salary`, `salary_tier`.
- Tabel `employee_schedules` — jam kerja khusus per orang (check_in, break_out, break_in, check_out, toleransi).
- Tabel `seasonal_schedules` — jam kerja musiman (delta masuk/pulang + force check-in + rentang tanggal).
- Kolom `break_out_time` & `break_in_time` di `work_settings`.
- Menu **Jam Kerja Khusus** (`/schedules`) dan **Jam Kerja Musiman** (`/seasonal`) beserta CRUD.
- Service `AttendanceProcessingService`:
  - Klasifikasi 4 check-lock (Masuk, Keluar Istirahat, Masuk Istirahat, Pulang).
  - Deteksi otomatis keterlambatan & pulang cepat.
  - Anti-manipulasi: scan masuk istirahat < 12:45 dianggap tidak sah (di-skip).
  - Dukungan jadwal khusus per karyawan & penyesuaian musiman.
- Laporan bulanan rinci & rekap global dengan filter lokasi, jabatan, dan nama karyawan.

**Diubah:**
- `ReportController` & laporan menggunakan mesin penghitung baru (4 check-lock).
- Form karyawan & setting jam kerja kini mendukung field baru.
- `tests/TestCase` menggunakan `RefreshDatabase`.
- `.env` diarahkan ke SQLite untuk development lokal.

**Testing:**
- 11 test pass (unit service absensi + feature laporan).

**Catatan fase berikutnya (rencana):**
- Fase 4: Manajemen Izin.
- Fase 5: Pinjaman / Kasbon.
- Fase 6: Payroll (deduksi & bonus).
- Fase 7: Hak akses (Super Admin vs Admin Operasional).
