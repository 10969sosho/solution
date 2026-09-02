@extends('layouts.app')

@section('title', 'Laporan Absensi Rinci - ADMS')
@section('page-title', 'Laporan Absensi Rinci')
@section('page-subtitle', 'Detail absensi karyawan per hari')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.attendanceDetail') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" value="{{ $startDate }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" value="{{ $endDate }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc }}" {{ $location == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ $position == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                Detail Absensi
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            @forelse($reportData as $data)
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-gray-50">
                    <h4 class="font-semibold text-gray-800">{{ $data['employee']->name }}</h4>
                    <p class="text-sm text-gray-500">{{ $data['employee']->position }} | {{ $data['employee']->location }}</p>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Masuk</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Keluar Istirahat</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Masuk Istirahat</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Keluar</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Izin (mnt)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Potong Gaji (mnt)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Kerja (jam)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Pulang Awal (mnt)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Terlambat (mnt)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($data['daily_details'] as $day)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['check_in'] !== '-' ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['check_in'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['break_out'] !== '-' ? 'text-gray-700' : 'text-gray-400' }}">
                                {{ $day['break_out'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['break_in'] !== '-' ? 'text-gray-700' : 'text-gray-400' }}">
                                {{ $day['break_in'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['check_out'] !== '-' ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['check_out'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['izin_no_deduction'] > 0 ? 'text-blue-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['izin_no_deduction'] > 0 ? $day['izin_no_deduction'] : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['izin_salary_deduction'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['izin_salary_deduction'] > 0 ? $day['izin_salary_deduction'] : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium {{ $day['work_minutes'] > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                                {{ $day['work_minutes'] > 0 ? number_format($day['work_minutes'] / 60, 1) : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['early_leave_minutes'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['early_leave_minutes'] > 0 ? $day['early_leave_minutes'] : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['late_minutes'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['late_minutes'] > 0 ? $day['late_minutes'] : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr class="font-semibold">
                            <td colspan="5" class="px-4 py-2 text-sm text-gray-700">Total</td>
                            <td class="px-4 py-2 text-center text-sm text-blue-600">
                                {{ collect($data['daily_details'])->sum('izin_no_deduction') ?: '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-red-600">
                                {{ collect($data['daily_details'])->sum('izin_salary_deduction') ?: '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-800">
                                {{ number_format($data['total_work_minutes'] / 60, 1) }} jam
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-orange-600">
                                {{ $data['total_early_leave_minutes'] ?: '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-red-600">
                                {{ $data['total_late_minutes'] ?: '-' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Tidak ada data absensi untuk filter yang dipilih</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
