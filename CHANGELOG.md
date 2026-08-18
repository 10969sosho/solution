# CHANGELOG

Semua perubahan signifikan pada ADMS HRIS dicatat di sini.

## [Unreleased]

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
