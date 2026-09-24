@extends('layouts.app')

@section('title', 'Laporan Absen Ringkasan - ADMS')
@section('page-title', 'Laporan Absen Ringkasan')
@section('page-subtitle', 'Rekap izin, terlambat, dan pulang awal karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.attendanceSummary') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
                <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                Ringkasan Absensi
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Izin Tanpa Potongan (mnt)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terlambat / Potong Gaji (mnt)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pulang Lebih Awal (mnt)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masuk Terlambat - Izin (mnt)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masuk Terlambat - Potong Gaji (mnt)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($summary as $index => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $item['employee']->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $item['employee']->position }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $item['employee']->location }}</td>
                        <td class="px-6 py-4 text-center text-sm {{ $item['total_izin_no_deduction'] > 0 ? 'text-blue-600 font-medium' : 'text-gray-400' }}">
                            {{ $item['total_izin_no_deduction'] > 0 ? $item['total_izin_no_deduction'] : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm {{ $item['total_izin_salary_deduction'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ $item['total_izin_salary_deduction'] > 0 ? $item['total_izin_salary_deduction'] : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm {{ $item['total_izin_pulang_awal'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                            {{ $item['total_izin_pulang_awal'] > 0 ? $item['total_izin_pulang_awal'] : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm {{ $item['total_izin_terlambat'] > 0 ? 'text-yellow-600 font-medium' : 'text-gray-400' }}">
                            {{ $item['total_izin_terlambat'] > 0 ? $item['total_izin_terlambat'] : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm {{ $item['total_late_minutes'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ $item['total_late_minutes'] > 0 ? $item['total_late_minutes'] : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <i class="fas fa-clipboard-check text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Tidak ada data untuk filter yang dipilih</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($summary) > 0)
                @php $summaryCollect = collect($summary); @endphp
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr class="font-semibold">
                        <td colspan="4" class="px-6 py-3 text-sm text-gray-700">Total</td>
                        <td class="px-6 py-3 text-center text-sm text-blue-600">
                            {{ $summaryCollect->sum('total_izin_no_deduction') ?: '-' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm text-red-600">
                            {{ $summaryCollect->sum('total_izin_salary_deduction') ?: '-' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm text-orange-600">
                            {{ $summaryCollect->sum('total_izin_pulang_awal') ?: '-' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm text-yellow-600">
                            {{ $summaryCollect->sum('total_izin_terlambat') ?: '-' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm text-red-600">
                            {{ $summaryCollect->sum('total_late_minutes') ?: '-' }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
