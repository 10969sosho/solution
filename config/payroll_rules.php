<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Range Jam Checklock (ADMS Attendance Rules)
    |--------------------------------------------------------------------------
    | Urutan 4 Checklock:
    | 1. Masuk           : 06:00 - 09:00 (Jadwal 07:00 / 08:00)
    | 2. Istirahat       : 11:00 - 12:29 (Jadwal 11:30 Jumat / 12:00)
    | 3. Masuk Istirahat : 12:30 - 13:30 (Jadwal 13:00)
    | 4. Pulang          : 15:30 - 20:00 (Jadwal 16:00 / 17:00, s/d 20:00 untuk lembur)
    */
    'checklock_ranges' => [
        'masuk' => [
            'start' => '06:00:00',
            'end' => '09:00:00',
        ],
        'istirahat' => [
            'start' => '11:00:00',
            'end' => '12:29:59',
        ],
        'masuk_istirahat' => [
            'start' => '12:30:00',
            'end' => '13:30:00',
        ],
        'pulang' => [
            'start' => '15:30:00',
            'end' => '20:00:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Minimal Lembur (Menit)
    |--------------------------------------------------------------------------
    | Lembur dihitung jika jam pulang lebih dari 1 jam (60 menit) setelah jadwal pulang.
    */
    'overtime_threshold_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Jatah Libur Bulanan Karyawan
    |--------------------------------------------------------------------------
    */
    'default_leave_quota' => [
        'admin_mandor' => 2,
        'jaga_malam_agk' => 1,
        'default' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Formula Bonus Libur (Pencairan Sisa Jatah Libur)
    |--------------------------------------------------------------------------
    | Jika gaji >= Rp 2.250.000: Bonus = (Gaji Pokok / 30) * 1.5 * Sisa Libur
    | Jika gaji <  Rp 2.250.000: 1 hari = Rp 75.000, 0.5 hari = Rp 50.000
    */
    'bonus_libur' => [
        'salary_threshold' => 2250000,
        'high_salary_multiplier' => 1.5,
        'low_salary_per_day' => 75000,
        'low_salary_half_day' => 50000,
        'days_in_month_divider' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uang Jaga Malam
    |--------------------------------------------------------------------------
    | Mandor yang bertugas sebagai Jaga Malam mendapatkan bonus Rp 600.000 / bulan.
    */
    'uang_jaga_malam' => [
        'mandor_bonus' => 600000,
        'default_bonus' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rumus Potongan Masuk (Kelebihan Libur)
    |--------------------------------------------------------------------------
    | Potong Masuk = (Gaji Pokok / 30) * Total Hari Potong Masuk
    */
    'potongan_masuk' => [
        'divider' => 30,
    ],
];
