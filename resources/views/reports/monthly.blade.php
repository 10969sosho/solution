@extends('layouts.app')

@section('title', 'Laporan Bulanan - ADMS')
@section('page-title', 'Laporan Bulanan')
@section('page-subtitle', 'Laporan absensi rinci per karyawan')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.monthly') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                    </option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees->pluck('location')->filter()->unique()->sort() as $loc)
                    <option value="{{ $loc }}" {{ $location == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees->pluck('position')->filter()->unique()->sort() as $pos)
                    <option value="{{ $pos }}" {{ $position == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->employee_id }}" {{ $selectedEmployee && $selectedEmployee->employee_id == $emp->employee_id ? 'selected' : '' }}>
                        {{ $emp->name }} ({{ $emp->employee_id }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>

    @if($selectedEmployee && $report)
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 mb-1">Hari Hadir</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['days_present'] }}</p>
            <p class="text-xs text-gray-400 mt-2">dari {{ $report['total_days'] }} hari</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 mb-1">Total Jam Kerja</p>
            <p class="text-3xl font-bold text-gray-800">
                {{ intdiv($report['total_work_minutes'], 60) }}<span class="text-lg">j</span>
                {{ $report['total_work_minutes'] % 60 }}<span class="text-lg">m</span>
            </p>
            <p class="text-xs text-gray-400 mt-2">akumulasi</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500 mb-1">Total Terlambat</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_late_minutes'] }}<span class="text-lg">mnt</span></p>
            <p class="text-xs text-gray-400 mt-2">{{ $report['days_late'] }} hari telat</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500 mb-1">Total Pulang Cepat</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_early_leave_minutes'] }}<span class="text-lg">mnt</span></p>
            <p class="text-xs text-gray-400 mt-2">menit</p>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                Rincian Absensi - {{ $selectedEmployee->name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Periode: {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                | Masuk: {{ substr($report['schedule']['check_in_time'], 0, 5) }}
                | Istirahat: {{ substr($report['schedule']['break_out_time'], 0, 5) }} - {{ substr($report['schedule']['break_in_time'], 0, 5) }}
                | Pulang: {{ substr($report['schedule']['check_out_time'], 0, 5) }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masuk</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Keluar Istirahat</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masuk Istirahat</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pulang</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jam Kerja</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terlambat</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pulang Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($report['daily_details'] as $detail)
                    <tr class="hover:bg-gray-50 {{ $detail['is_weekend'] ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">
                            {{ \Carbon\Carbon::parse($detail['date'])->format('d M (D)') }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            {{ isset($detail['check_locks']['check_in']) ? $detail['check_locks']['check_in']['scan_time']->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            {{ isset($detail['check_locks']['break_out']) ? $detail['check_locks']['break_out']['scan_time']->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            {{ isset($detail['check_locks']['break_in']) ? $detail['check_locks']['break_in']['scan_time']->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            {{ isset($detail['check_locks']['check_out']) ? $detail['check_locks']['check_out']['scan_time']->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-800">
                            @if($detail['present'])
                            {{ intdiv($detail['total_work_minutes'], 60) }}j {{ $detail['total_work_minutes'] % 60 }}m
                            @else
                            <span class="text-red-500">Tidak hadir</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($detail['late_minutes'] > 0)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">{{ $detail['late_minutes'] }} mnt</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($detail['early_leave_minutes'] > 0)
                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-medium">{{ $detail['early_leave_minutes'] }} mnt</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Tidak ada data absensi untuk periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-chart-bar text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Pilih karyawan untuk melihat laporan rincian</p>
    </div>
    @endif
</div>
@endsection