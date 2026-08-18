<?php

return [

    'work_days_per_month' => 22,

    /*
     * Denda keterlambatan bertingkat (Rp per menit).
     * Akumulasi menit terlambat dalam sebulan dihitung bertingkat:
     * - 0-30 menit : Rp 1.000/menit
     * - 31-60 menit: Rp 1.500/menit
     * - > 60 menit : Rp 2.000/menit
     */
    'late_fine' => [
        'tiers' => [
            ['max_minutes' => 30, 'per_minute' => 1000],
            ['max_minutes' => 60, 'per_minute' => 1500],
            ['max_minutes' => null, 'per_minute' => 2000],
        ],
    ],

    /*
     * Pemotongan hari libur / ketidakhadiran.
     * Basis harian = gaji pokok / jumlah hari kerja per bulan.
     */
    'absence_deduction' => [
        'enabled' => true,
    ],

    /*
     * Bonus kehadiran berdasarkan tier gaji.
     * Diberikan jika karyawan tidak mengambil hak jatah liburnya (tidak ada izin disetujui) pada bulan tersebut.
     */
    'attendance_bonus' => [
        'enabled' => true,
        'by_tier' => [
            'A' => 300000,
            'B' => 200000,
            'C' => 100000,
        ],
        'default' => 150000,
    ],

    /*
     * THR: diferensiasi masa kerja.
     * Masa kerja >= 60 bulan (5 tahun): THR penuh = 1x gaji pokok.
     * Masa kerja < 60 bulan: proporsional (masa kerja / 12), maksimal 1x gaji pokok.
     */
    'thr' => [
        'long_service_months' => 60,
        'long_service_rate' => 1.0,
    ],
];