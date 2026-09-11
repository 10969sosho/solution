# DOKUMENTASI REVISI SISTEM PAYROLL & ATTENDANCE
Dokumen ini disusun berdasarkan catatan revisi briefing klien dan dikelompokkan secara modular untuk memudahkan implementasi teknis, pengujian, serta pemeliharaan sistem.

---

## DAFTAR MODUL SISTEM

1. [MODUL 1: MASTER DATA & KONFIGURASI FLEKSIBEL](#modul-1-master-data--konfigurasi-fleksibel)
2. [MODUL 2: LOGIKA & ATURAN CHECKLOCK (ABSENSI)](#modul-2-logika--aturan-checklock-absensi)
3. [MODUL 3: DETAIL ABSEN KARYAWAN (BULANAN PER KARYAWAN)](#modul-3-detail-absen-karyawan-bulanan-per-karyawan)
4. [MODUL 4: ABSEN DAILY (HARIAN SELURUH KARYAWAN) & DETEKSI ANOMALI](#modul-4-absen-daily-harian-seluruh-karyawan--deteksi-anomali)
5. [MODUL 5: LIST KOMPILASI HARIAN (WORKFLOW VALIDASI)](#modul-5-list-kompilasi-harian-workflow-validasi)
6. [MODUL 6: REKAPITULASI BULANAN](#modul-6-rekapitulasi-bulanan)
7. [MODUL 7: LAPORAN TAHUNAN & SEMESTERAN](#modul-7-laporan-tahunan--semesteran)
8. [MODUL 8: USER MANAGEMENT & PRIVASI GAJI OWNER](#modul-8-user-management--privasi-gaji-owner)
9. [MODUL 9: DATA REKENING & METODE PEMBAYARAN](#modul-9-data-rekening--metode-pembayaran)
10. [MODUL 10: PINJAMAN KARYAWAN & PEMOTONGAN GAJI](#modul-10-pinjaman-karyawan--pemotongan-gaji)
11. [MODUL 11: PENGGAJIAN (PAYROLL) & CETAK SLIP GAJI](#modul-11-penggajian-payroll--cetak-slip-gaji)

---

## MODUL 1: MASTER DATA & KONFIGURASI FLEKSIBEL

### 1.1 Master Data Pokok
- Master data yang sudah ada dipertahankan (Golongan, Jabatan, Lokasi, Karyawan, Master Potongan Terlambat).
- Tidak ada penghapusan data master yang sudah berjalan.

### 1.2 Fleksibilitas Parameter (Tidak Hardcode Kaku)
Seluruh parameter perhitungan harus fleksibel dan dapat dikonfigurasi melalui database/setting, antara lain:
- Ambang batas range checklock.
- Ketentuan jatah libur per tipe/jabatan/golongan.
- Parameter pengali bonus libur dan potongan masuk.
- Ambang batas minimal lembur.
- Nominal tunjangan jaga malam.

---

## MODUL 2: LOGIKA & ATURAN CHECKLOCK (ABSENSI)

### 2.1 Pola 4 Urutan Checklock
Sistem membaca data dari mesin absensi berdasarkan urutan checklock dan range jam berikut:
1. **Masuk**:
   - Jadwal Standar: 07:00 / 08:00
   - Range Jam: 06:00 – 09:00
2. **Istirahat**:
   - Jadwal Standar: 11:30 (Khusus Jumat) / 12:00
   - Range Jam: 11:00 – 12:29
3. **Masuk Istirahat**:
   - Jadwal Standar: 13:00
   - Range Jam: 12:30 – 13:30
4. **Pulang**:
   - Jadwal Standar: 16:00 / 17:00
   - Range Jam: 15:30 – 20:00 (batas akhir 20:00 untuk menampung lembur)

Jika data checklock terdeteksi sesuai urutan dan range jam di atas, sistem otomatis memberi label:
`Masuk` → `Istirahat` → `Masuk Istirahat` → `Pulang`.

### 2.2 Perhitungan Keterlambatan
- **Terlambat Masuk**: Selisih menit antara jam tap Masuk dengan jadwal masuk kerja (setelah toleransi jika ada).
- **Terlambat Masuk Istirahat**: Selisih menit antara jam tap Masuk Istirahat dengan jadwal masuk istirahat (13:00).
- Keterlambatan dicatat dalam **satuan menit**.

### 2.3 Perhitungan Lembur
- Lembur dihitung jika jam checklock pulang **lebih dari 1 jam** setelah jadwal pulang karyawan.
- **Contoh**:
  - Jadwal pulang: 16:00.
  - Lembur baru mulai terhitung jika checklock >= 17:00.
  - Pulang sebelum 17:00 belum dihitung lembur.

### 2.4 Aturan Jatah Libur & Potongan Masuk
- **Jatah Libur per Bulan**:
  - Admin & Mandor: 2 hari.
  - Jaga Malam & Anak Gudang / Anak Kandang (AGK): 1 hari.
- **Sisa Jatah Libur**:
  - Jika jatah libur tidak diambil atau hanya diambil sebagian, sisa hari dicairkan menjadi **Bonus Libur**.
- **Kelebihan Hari Libur**:
  - Jika karyawan libur melebihi jatah yang ditentukan, dikenakan **Potongan Masuk**.
  - **Rumus Potongan Masuk**:
    Potong Masuk (Rp) = (Gaji Pokok / 30) * Total Hari Potong Masuk

---

## MODUL 3: DETAIL ABSEN KARYAWAN (BULANAN PER KARYAWAN)

Menu untuk melihat riwayat absensi 1 orang karyawan selama 1 bulan penuh (tanggal 1 s/d 30/31).

### Kolom Data yang Ditampilkan:
- **Tanggal (Y)**: Baris tanggal 1 sampai 30/31 sesuai jumlah hari bulan terpilih.
- **Index Kolom (X)**:
  1. Masuk (jam)
  2. Istirahat (jam)
  3. Masuk Istirahat (jam)
  4. Pulang (jam)
  5. Terlambat Masuk (Total Menit)
  6. Terlambat Masuk Istirahat (Total Menit)
  7. Keterangan (Libur / Izin / Hadir Normal / Anomali)
  8. Total Hari Libur (kumulatif)

---

## MODUL 4: ABSEN DAILY (HARIAN SELURUH KARYAWAN) & DETEKSI ANOMALI

Menu untuk mengelola data absensi seluruh karyawan pada tanggal tertentu.

### 4.1 Kolom Data:
- **Nama Karyawan (Y)**: Seluruh karyawan aktif.
- **Data Absensi (X)**:
  1. Masuk
  2. Istirahat
  3. Masuk Istirahat
  4. Pulang
  5. Terlambat Masuk (Menit)
  6. Terlambat Masuk Istirahat (Menit)
  7. Keterangan
  8. Nominal Izin
  9. Action FIX

### 4.2 Deteksi Absen Anomali (Highlight Kuning / Stabilo)
Sistem otomatis menandai baris dengan warna kuning jika ditemukan anomali:
- Checklock di luar range jam yang ditentukan.
- Karyawan hanya melakukan checklock sebagian (misal hanya 2 kali).
- Urutan checklock tidak sesuai logika alur.
- Data tidak memenuhi ketentuan 4 kali checklock normal.

### 4.3 Penanganan Izin oleh HRD
- Jika karyawan berstatus izin setelah dicek HRD:
  - Kolom **Keterangan** diset menjadi `Izin`.
  - HRD memilih jenis penyesuaian:
    - `Potong Gaji` (nominal dimasukkan ke Nominal Izin sebagai pengurang gaji).
    - `Tambah Gaji` (nominal dimasukkan ke Nominal Izin sebagai penambah gaji).
- **Aturan Pengecualian Denda**:
  - Jika terdapat **Nominal Izin**, maka keterlambatan pada hari tersebut **diabaikan** dan tidak dihitung ke dalam akumulasi potongan keterlambatan penggajian.

### 4.4 Verifikasi & Status Harian
- **Action FIX per Baris**: HRD memverifikasi tiap baris karyawan.
- **Status Kompilasi Tanggal** (di bagian bawah):
  - `Draft`: Data baru terisi / belum final.
  - `Lock`: Data dikunci dari perubahan otomatis / scan baru.
  - `Fix`: Data sudah diverifikasi penuh dan siap diproses ke rekapitulasi.

---

## MODUL 5: LIST KOMPILASI HARIAN (WORKFLOW VALIDASI)

Menu daftar seluruh tanggal dalam 1 bulan kalender beserta status validasinya.

### 5.1 Informasi & Aksi:
- Menampilkan tanggal 1 s/d akhir bulan (misal: 1 Agustus `Draft`, 2 Agustus `Lock`, 3 Agustus `Fix`).
- Klik tanggal -> otomatis membuka halaman **Absen Daily** tanggal tersebut.
- **Prasyarat Rekap Bulanan**:
  - Seluruh tanggal dalam bulan berjalan **wajib berstatus FIX** sebelum data dapat ditarik ke Rekap Bulanan.

---

## MODUL 6: REKAPITULASI BULANAN

Menu rekapitulasi bulanan seluruh karyawan untuk periode 1 bulan kalender.

### 6.1 Data yang Ditampilkan:
1. Nama Karyawan
2. Total Menit Terlambat Masuk
3. Total Menit Terlambat Masuk Istirahat
4. Nominal Potongan Terlambat Masuk
5. Nominal Potongan Terlambat Masuk Istirahat
6. Nominal Izin (Potong / Tambah)
7. Total Hari Libur
8. Total Hari Potong Masuk
9. Nominal Potong Masuk
10. Total Durasi Lembur
11. Nominal Lembur
12. Total Bonus Libur
13. Uang Jaga Malam

### 6.2 Aturan Akumulasi Menit Keterlambatan
- Keterlambatan dihitung dari **akumulasi total menit selama 1 bulan**, BUKAN nominal per hari.
- **Contoh**:
  - Tgl 1 = 5 menit, Tgl 2 = 10 menit, Tgl 3 = 5 menit -> Total = 20 menit.
  - Nominal denda dihitung berdasarkan bracket akumulasi 20 menit pada Master Potongan Terlambat.
  - Ketentuan yang sama berlaku untuk Terlambat Masuk Istirahat.
- Data yang sudah valid di Rekap Bulanan dapat ditarik langsung ke modul Penggajian.

---

## MODUL 7: LAPORAN TAHUNAN & SEMESTERAN

Menu pelaporan tahunan karyawan:
- Fokus utama: Memonitor pencatatan hari libur karyawan per semester:
  - **Semester 1**: Januari – Juni (Total hari libur per karyawan).
  - **Semester 2**: Juli – Desember (Total hari libur per karyawan).
- Dilengkapi ringkasan kehadiran tahunan untuk evaluasi HRD.

---

## MODUL 8: USER MANAGEMENT & PRIVASI GAJI OWNER

### 8.1 Golongan Khusus (Confidential / Owner-Only)
- Disediakan flag / penanda `is_confidential` (atau golongan khusus) pada Master Golongan / Karyawan.
- Karyawan yang berada di bawah golongan khusus ini gajinya bersifat rahasia.
- **Batasan Akses**:
  - Hanya user dengan role **Owner / Super Admin** yang dapat melihat, mengedit, dan mengelola informasi gaji karyawan golongan khusus tersebut.
  - User admin operasional / HRD standar tidak dapat melihat nominal gaji golongan khusus (masked / hidden).

---

## MODUL 9: DATA REKENING & METODE PEMBAYARAN

Pencatatan data pembayaran gaji pada profil karyawan:
- Nama Bank & Nomor Rekening Karyawan.
- Nama Pemilik Rekening.
- Opsi Metode Pembayaran: `Transfer Bank` atau `Tunai (Cash)`.
- Informasi ini muncul pada rekap penggajian dan slip gaji.

---

## MODUL 10: PINJAMAN KARYAWAN & PEMOTONGAN GAJI

### 10.1 Data Pinjaman:
- Nominal Total Pinjaman.
- Keterangan & Kesepakatan Cicilan (misal: "Dicicil 3x @ Rp 500.000").
- Riwayat cicilan yang telah dibayarkan dan sisa saldo.

### 10.2 Pemotongan Manual Saat Penggajian:
- Nominal potongan pinjaman pada bulan berjalan diinput secara **manual / editable** saat proses pembuatan payroll, sesuai kesepakatan bulan tersebut.

---

## MODUL 11: PENGGAJIAN (PAYROLL) & CETAK SLIP GAJI

### 11.1 Kolom Data Penggajian:
- Total Izin
- Gaji Pokok
- Total Nominal Terlambat (Akumulasi Masuk + Masuk Istirahat)
- Nominal Izin Tambah Gaji
- Nominal Izin Potong Gaji
- Total Lembur & Nominal Lembur
- Uang Jaga Malam
- Bonus Libur
- Potongan Masuk
- Pengurangan Pinjaman (Input Manual)
- Total Gaji / Grand Total Gaji
- Aksi: Download PDF / Print Slip Gaji

### 11.2 Formula Bonus Libur (Pencairan Sisa Jatah Libur)
- Jika Gaji Pokok >= Rp 2.250.000:
  Bonus Libur = Gaji Harian * 1.5 * Sisa Hari Libur
  (Gaji Harian = Gaji Pokok / 30)
- Jika Gaji Pokok < Rp 2.250.000:
  - 1 Hari = Rp 75.000
  - 1/2 Hari = Rp 50.000

### 11.3 Tunjangan Jaga Malam
- Karyawan dengan tugas Jaga Malam berhak atas tunjangan/bonus jaga malam.
- Khusus Mandor yang merangkap Jaga Malam: Bonus tetap **Rp 600.000 / bulan**.

### 11.4 Formula Grand Total Gaji
GRAND TOTAL = GAJI POKOK
            - POTONGAN TERLAMBAT
            - IZIN POTONG GAJI
            + IZIN TAMBAH GAJI
            - PINJAMAN
            - POTONGAN MASUK
            + BONUSAN
            + UANG JAGA MALAM
            + NOMINAL LEMBUR
