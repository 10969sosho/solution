@extends('layouts.app')

@section('title', 'Master Bonus Libur - ADMS')
@section('page-title', 'Master Bonus Libur')
@section('page-subtitle', 'Pengaturan bonus untuk jatah libur yang tidak diambil')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-gradient-to-r from-emerald-700 to-teal-600 rounded-xl shadow-sm p-6 text-white">
        <h3 class="text-lg font-semibold"><i class="fas fa-gift mr-2"></i>Bonus Libur</h3>
        <p class="text-sm text-emerald-100 mt-1">
            Karyawan mendapat bonus dari jatah libur yang tidak diambil pada bulan tersebut.
            Jatah libur per karyawan diatur di Data Karyawan, sedangkan libur/cuti yang diambil dicatat di Manajemen Izin.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-sliders-h text-emerald-600 mr-2"></i>Variabel Perhitungan
                </h3>
            </div>
            <form action="{{ route('bonus-libur.update') }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Variable A - Batas Gaji <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center">
                        <span class="px-4 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-sm text-gray-600">Rp</span>
                        <input type="number" name="batas_gaji" value="{{ old('batas_gaji', (float) $setting->batas_gaji) }}" min="0" step="1" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Gaji di bawah nilai ini memakai Variable B & C. Gaji sama/lebih memakai Variable D.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Variable B - Bonus 1 Hari <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center">
                            <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-sm text-gray-600">Rp</span>
                            <input type="number" name="bonus_satu_hari" value="{{ old('bonus_satu_hari', (float) $setting->bonus_satu_hari) }}" min="0" step="1" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Untuk gaji di bawah Variable A.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Variable C - Bonus ½ Hari <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center">
                            <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-sm text-gray-600">Rp</span>
                            <input type="number" name="bonus_setengah_hari" value="{{ old('bonus_setengah_hari', (float) $setting->bonus_setengah_hari) }}" min="0" step="1" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Untuk gaji di bawah Variable A.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Variable D - Pengali Bonus <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="pengali_bonus" value="{{ old('pengali_bonus', (float) $setting->pengali_bonus) }}" min="0" step="0.01" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Rumus: Gaji Pokok ÷ 30 × Variable D. Contoh pengali 1,5 berarti 1 hari = gaji pokok ÷ 30 × 1,5.
                    </p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                    <p class="font-semibold mb-2">Ketentuan tetap:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Gaji &lt; Rp {{ number_format((float) $setting->batas_gaji, 0, ',', '.') }}: bonus = (hari penuh × Variable B) + (½ hari × Variable C).</li>
                        <li>Gaji ≥ Rp {{ number_format((float) $setting->batas_gaji, 0, ',', '.') }}: bonus = sisa jatah libur × (Gaji Pokok ÷ 30 × Variable D).</li>
                        <li>Bonus dihitung otomatis saat Generate Payroll.</li>
                    </ul>
                </div>

                <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calculator text-blue-600 mr-2"></i>Estimasi Bonus per Karyawan
                </h3>
                <p class="text-xs text-gray-500 mt-1">Estimasi jika seluruh jatah libur tidak diambil (belum dikurangi libur yang tercatat).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jatah Libur</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bonus / Hari</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estimasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($employees as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-800">{{ $row['employee']->name }}</div>
                                <div class="text-xs text-gray-500">{{ $row['employee']->employee_id }}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">Rp {{ number_format((float) $row['employee']->salary, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-700">{{ rtrim(rtrim(number_format($row['jatah'], 1, ',', '.'), '0'), ',') }} hari</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">Rp {{ number_format($row['per_day'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-emerald-700">Rp {{ number_format($row['estimasi'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada karyawan aktif</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
