@extends('layouts.app')

@section('title', 'Laporan Absensi Rinci - ADMS')
@section('page-title', 'Laporan Absensi Rinci')
@section('page-subtitle', 'Detail absensi karyawan per hari')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.attendanceDetail') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="date" name="start_date" value="{{ $startDate }}" required class="w-full px-4 py-2 border rounded-lg">
            <input type="date" name="end_date" value="{{ $endDate }}" required class="w-full px-4 py-2 border rounded-lg">
            <select name="location" class="w-full px-4 py-2 border rounded-lg"><option value="">Semua lokasi</option>@foreach($locations as $loc)<option value="{{ $loc }}" @selected($location == $loc)>{{ $loc }}</option>@endforeach</select>
            <select name="position" class="w-full px-4 py-2 border rounded-lg"><option value="">Semua jabatan</option>@foreach($positions as $pos)<option value="{{ $pos }}" @selected($position == $pos)>{{ $pos }}</option>@endforeach</select>
            <select name="employee_id" class="w-full px-4 py-2 border rounded-lg"><option value="">Semua karyawan</option>@foreach($employees as $emp)<option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>{{ $emp->name }}</option>@endforeach</select>
            <button class="px-6 py-2 bg-blue-600 text-white rounded-lg">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        @forelse($reportData as $data)
        <div class="border-b border-gray-200 min-w-[1100px]">
            <div class="px-6 py-3 bg-gray-50">
                <h4 class="font-semibold text-gray-800">{{ $data['employee']->name }}</h4>
                <p class="text-sm text-gray-500">{{ $data['employee']->jabatan->name ?? '-' }} | {{ $data['employee']->lokasi->name ?? '-' }}</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100"><tr>
                    <th class="px-4 py-2 text-left">Tanggal</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Masuk</th><th class="px-4 py-2">Istirahat</th><th class="px-4 py-2">Masuk Istirahat</th><th class="px-4 py-2">Pulang</th><th class="px-4 py-2">Total Jam Kerja</th><th class="px-4 py-2">Terlambat Masuk</th><th class="px-4 py-2">Terlambat Istirahat</th><th class="px-4 py-2">Pulang Awal</th>
                </tr></thead>
                <tbody class="divide-y">
                @foreach($data['daily_details'] as $day)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-center">{{ $day['status'] }}</td>
                    <td class="px-4 py-2 text-center">{{ $day['check_in'] }}</td><td class="px-4 py-2 text-center">{{ $day['break_out'] }}</td><td class="px-4 py-2 text-center">{{ $day['break_in'] }}</td><td class="px-4 py-2 text-center">{{ $day['check_out'] }}</td>
                    <td class="px-4 py-2 text-center">{{ $day['work_minutes'] ? number_format($day['work_minutes'] / 60, 1) . ' jam' : '-' }}</td>
                    <td class="px-4 py-2 text-center">{{ $day['late_minutes'] ?: '-' }}{{ $day['late_minutes'] ? ' m' : '' }}</td><td class="px-4 py-2 text-center">{{ $day['late_break_in_minutes'] ?: '-' }}{{ $day['late_break_in_minutes'] ? ' m' : '' }}</td><td class="px-4 py-2 text-center">{{ $day['early_leave_minutes'] ?: '-' }}{{ $day['early_leave_minutes'] ? ' m' : '' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <p class="p-12 text-center text-gray-500">Tidak ada data absensi untuk filter yang dipilih.</p>
        @endforelse
    </div>
</div>
@endsection
