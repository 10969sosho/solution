@extends('layouts.app')

@section('title', 'Rekap Bulanan - ADMS')
@section('page-title', 'Rekap Bulanan')
@section('page-subtitle', 'Rekap absensi bulanan semua karyawan')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.summary') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-table text-blue-600 mr-2"></i>
                Rekap Absensi - {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Jam Masuk: {{ $workSetting->check_in_time }} | Jam Keluar: {{ $workSetting->check_out_time }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departemen</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hari Kerja</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Jam</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Telat</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lembur</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hari Telat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($summary as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-800">{{ $row['name'] }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $row['employee_id'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $row['department'] ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-lg font-bold text-gray-800">{{ $row['total_days'] }}</span>
                            <span class="text-xs text-gray-500">hari</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-lg font-bold text-green-600">{{ $row['total_hours'] }}</span>
                            <span class="text-xs text-gray-500">jam</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($row['total_late_minutes'] > 0)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm font-medium">{{ $row['total_late_minutes'] }} mnt</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($row['total_overtime_minutes'] > 0)
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">{{ $row['total_overtime_minutes'] }} mnt</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($row['days_late'] > 0)
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm font-medium">{{ $row['days_late'] }} hari</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Tidak ada data karyawan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
