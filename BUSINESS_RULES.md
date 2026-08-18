# BUSINESS RULES — ADMS HRIS (Bu Vania)

Dokumen ini berisi aturan bisnis sistem HRIS (Absensi, Izin, Pinjaman, Payroll).
Menjadi referensi utama logika aplikasi.

## 1. Master Data & Pengaturan Jam Kerja

### 1.1 Master Jam Kerja Khusus (Per Orang)
- Karyawan tertentu dapat memiliki jadwal khusus (misal penjaga gerbang istirahat 10:45-11:45 agar standby saat istirahat umum).
- Prioritas jadwal: **Jam Kerja Khusus Karyawan** > **Setting Global**.
- Jadwal khusus yang aktif & sudah berlaku (`effective_from`) akan dipakai untuk menghitung absensi.
- Hanya satu jadwal khusus aktif per karyawan; menyimpan jadwal baru yang aktif otomatis menonaktifkan yang lama.

### 1.2 Master Jam Kerja Musiman (Bulk Update)
Diterapkan otomatis pada semua karyawan untuk rentang tanggal tertentu, memodifikasi jam masuk/pulang:
- **Puasa**: masuk tetap, pulang maju 30 menit (`check_out_delta = -30`).
- **Iduladha**: semua masuk pukul 08.00 (`force_check_in = 08:00`), pulang tetap.
- **Lebaran H1**: masuk 09.00 (`force_check_in = 09:00`), pulang maju 30 menit.
- **Lebaran H2–H5**: masuk normal, pulang maju 30 menit.
- Prioritas: jika ada jadwal musiman yang tanggalnya mencakup hari tersebut, penyesuaian diterapkan di atas jadwal normal.

### 1.3 Master Data Karyawan
- Menyimpan: Lokasi, Jabatan, Status (Aktif/Keluar), Tanggal Masuk Kerja, Gaji Pokok, Tier Gaji.
- `join_date` (tanggal masuk) dipakai untuk kalkulasi **THR**, khususnya diferensiasi masa kerja **> 5 tahun**.

## 2. Pengolahan Absensi & Proteksi Anti-Kecurangan

### 2.1 Deteksi Otomatis
- Keterlambatan & pulang cepat dikalkulasi otomatis oleh sistem dari data mentah mesin. Tidak ada input manual.

### 2.2 Check-Lock (4 Titik Absen)
Setiap hari diproses menjadi 4 check-lock:
1. **Masuk** (`check_in`) — scan pertama hari itu.
2. **Keluar Istirahat** (`break_out`) — scan paling dekat dengan jam keluar istirahat.
3. **Masuk Istirahat** (`break_in`) — scan pertama **≥ 12:45** (batas minimal = jam masuk istirahat 13:00 − 15 menit).
4. **Pulang** (`check_out`) — scan terakhir setelah `break_in`.

### 2.3 Restriksi Anti-Manipulasi Jam Istirahat
- Absen masuk setelah istirahat (13.00) **hanya sah paling awal 12.45**.
- Scan yang terlalu awal (misal 12.30) **tidak pernah** dihitung sebagai `break_in` dan ditandai **tidak sah** (di-skip), sehingga tidak dapat memanipulasi jam kerja.
- Scan yang tidak masuk slot mana pun dicatat sebagai `ignored_scans`.

### 2.4 Integritas Data Absensi
- Laporan absensi dihasilkan **otomatis** oleh sistem dari log mentah mesin.
- Tidak ada fitur edit/hapus laporan absensi untuk Staff/Admin.

## 3. Pelaporan Absensi

### 3.1 Filter Laporan
- Rentang tanggal (tahun & bulan), **Lokasi**, **Jabatan**, dan **Nama Karyawan**.

### 3.2 Laporan Rincian (Individual)
- Kolom: Tanggal, Nama, 4x Check-Lock, Total Jam Kerja, Rekap Keterlambatan, Pulang Cepat.

### 3.3 Laporan Ringkasan (Global/Bulanan)
- Akumulasi: Total Hari Hadir, Total Jam Kerja, Total Jam Terlambat, Hari Telat, Pulang Cepat.
- Dapat difilter per lokasi / jabatan.

## 4. Manajemen Perizinan (Menu Izin)

- **Izin Tanpa Potongan**: izin mendadak/singkat (< 15 menit, misal ban gembos) — tidak memotong gaji.
- **Izin Potong Gaji**: izin durasi lama (> 30 menit) — potongan proporsional, lebih ringan dari denda keterlambatan tanpa izin.
- **Akumulasi Keterlambatan**: menit terlambat tanpa izin diakumulasikan per bulan dan dikenakan denda pemotongan bertingkat.

## 5. Manajemen Pinjaman (Kasbon)

- Pencatatan pinjaman: Nama, Tanggal Pinjam, Nominal, dengan **perhitungan otomatis sisa bon**.
- Pembayaran & mutasi: menampilkan sisa bon periode lalu otomatis, menyimpan histori bayar bon, dan laporan mutasi pinjaman per karyawan.

## 6. Skema Penggajian (Payroll)

### 6.1 Komponen Pemotongan Gaji (Deductions)
- Cicilan Pinjaman / Bon.
- Denda akumulasi keterlambatan.
- Pemotongan hari libur / ketidakhadiran.

### 6.2 Bonus Kehadiran (Incentives)
- Bonus tambahan jika karyawan **tidak mengambil hak jatah liburnya**.
- Nominal menyesuaikan **tier gaji**.

## 7. Hak Akses (User Roles)

| Role | Hak Akses |
| :--- | :--- |
| **Super Admin / Owner** | Full access ke seluruh menu, data karyawan, laporan, dan konfigurasi. |
| **Admin Operasional** | Melihat/mengedit data & gaji karyawan tingkat operasional (Gudang, Kandang, dll). **TIDAK DAPAT** mengakses rekam gaji & laporan milik Admin dan Mandor. |
