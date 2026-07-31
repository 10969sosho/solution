@extends('layouts.app')

@section('title', 'Laporan Bulanan - ADMS')
@section('page-title', 'Laporan Bulanan')
@section('page-subtitle', 'Laporan absensi bulanan per karyawan')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.monthly') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <p class="text-sm text-gray-500 mb-1">Total Hari Kerja</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_days'] }}</p>
            <p class="text-xs text-gray-400 mt-2">hari</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 mb-1">Total Jam Kerja</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_hours'] }}</p>
            <p class="text-xs text-gray-400 mt-2">jam</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500 mb-1">Total Telat</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_late_minutes'] }}</p>
            <p class="text-xs text-gray-400 mt-2">menit ({{ $report['days_late'] }} hari)</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 mb-1">Total Lembur</p>
            <p class="text-3xl font-bold text-gray-800">{{ $report['total_overtime_minutes'] }}</p>
            <p class="text-xs text-gray-400 mt-2">menit</p>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                Detail Absensi - {{ $selectedEmployee->name }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Periode: {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                | Jam Masuk: {{ $workSetting->check_in_time }}
                | Jam Keluar: {{ $workSetting->check_out_time }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Keluar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Kerja</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lembur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($report['daily_details'] as $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">
                            {{ \Carbon\Carbon::parse($detail['date'])->format('d M Y (D)') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $detail['check_in'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $detail['check_out'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $detail['work_hours'] }} jam</td>
                        <td class="px-6 py-4">
                            @if($detail['late_minutes'] > 0)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">{{ $detail['late_minutes'] }} menit</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($detail['overtime_minutes'] > 0)
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">{{ $detail['overtime_minutes'] }} menit</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
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
        <p class="text-gray-500 text-lg">Pilih karyawan untuk melihat laporan bulanan</p>
    </div>
    @endif
</div>
@endsection
