<?php

return [

    /*
     * Role pengguna sistem.
     * super_admin: akses penuh (owner/pemilik usaha).
     * admin_operasional: hanya mengelola karyawan operasional (Gudang/Kandang),
     * tidak boleh melihat rekam gaji / payroll / laporan karyawan non-operasional.
     */
    'roles' => [
        'super_admin',
        'admin_operasional',
    ],

    /*
     * Jabatan yang termasuk kategori operasional.
     */
    'operational_positions' => [
        'Gudang',
        'Kandang',
    ],
];