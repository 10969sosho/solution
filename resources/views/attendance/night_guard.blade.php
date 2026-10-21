@extends('layouts.app')

@section('title', 'Jaga Malam')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Jaga Malam</h1>
            <p class="text-sm text-gray-500">
                Absensi petugas jaga malam (17:00 - 06:00). Penugasan &amp; nominal uang jaga malam diatur di
                <a href="{{ route('employees.index') }}" class="text-blue-600 hover:underline">Data Karyawan</a>.
            </p>
        </div>

        <!-- Filter Tanggal -->
        <form method="GET" action="{{ route('attendance.night-guard') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $dateStr }}"
                class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-1"></i> Tampilkan
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">Petugas Terdaftar</p>
                <p class="text-2xl font-bold text-gray-800">{{ count($employees) }} orang</p>
            </div>
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-green-600 font-medium">Sudah Absen</p>
                <p class="text-2xl font-bold text-green-700">{{ $presentCount }} orang</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-red-500 font-medium">Belum Absen</p>
                <p class="text-2xl font-bold text-red-600">{{ max(0, count($employees) - $presentCount) }} orang</p>
            </div>
            <div class="w-10 h-10 bg-red-50 text-red-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>

    <!-- Tabel Absensi Jaga Malam -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Nama Karyawan</th>
                    <th class="px-3 py-3 text-right">Uang Jaga Malam</th>
                    <th class="px-3 py-3 text-center">Masuk</th>
                    <th class="px-3 py-3 text-center">Pulang</th>
                    <th class="px-3 py-3 text-center">Kerja (jam)</th>
                    <th class="px-3 py-3 text-center">Terlambat</th>
                    <th class="px-3 py-3 text-center">Pulang Awal</th>
                    <th class="px-3 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $index => $row)
                    @php
                        $isEmpty = ! $row['present'];
                    @endphp
                    <tr class="{{ $isEmpty ? 'bg-red-100 hover:bg-red-200/80' : 'hover:bg-blue-50/50' }}">
                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <div>{{ $row['employee']->name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $row['employee']->position ?? $row['employee']->department ?? 'Karyawan' }}</div>
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-gray-700">
                            Rp {{ number_format((float) $row['employee']->uang_jaga_malam, 0, ',', '.') }}
                        </td>
                        <td class="px-3 py-3 text-center font-mono {{ $row['check_in'] !== '-' ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $row['check_in'] }}
                        </td>
                        <td class="px-3 py-3 text-center font-mono {{ $row['check_out'] !== '-' ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $row['check_out'] }}
                        </td>
                        <td class="px-3 py-3 text-center text-gray-700">
                            {{ $row['work_minutes'] > 0 ? number_format($row['work_minutes'] / 60, 1) : '-' }}
                        </td>
                        <td class="px-3 py-3 text-center {{ $row['late_minutes'] > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">
                            {{ $row['late_minutes'] > 0 ? $row['late_minutes'] . ' mnt' : '-' }}
                        </td>
                        <td class="px-3 py-3 text-center {{ $row['early_leave_minutes'] > 0 ? 'text-orange-600 font-bold' : 'text-gray-400' }}">
                            {{ $row['early_leave_minutes'] > 0 ? $row['early_leave_minutes'] . ' mnt' : '-' }}
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if($isEmpty)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white">Absen Kosong</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Hadir</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                            Belum ada karyawan yang ditugaskan jaga malam.
                            Buka <a href="{{ route('employees.index') }}" class="text-blue-600 hover:underline">Data Karyawan</a>,
                            lalu centang <strong>Bertugas Jaga Malam</strong> dan isi nominal uang jaga malam.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
