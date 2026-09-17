@extends('layouts.app')

@section('title', 'Detail Absen Karyawan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Detail Absen Karyawan</h1>
            <p class="text-sm text-gray-500">Melihat detail absensi bulanan per nama karyawan (Tanggal 1 s/d 31)</p>
        </div>

        <!-- Filter Karyawan & Periode -->
        <form method="GET" action="{{ route('attendance.employee-detail') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <select name="employee_id" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 min-w-[200px]" onchange="this.form.submit()">
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ ($selectedEmployee?->id == $emp->id) ? 'selected' : '' }}>
                        {{ $emp->name }} ({{ $emp->employee_id }})
                    </option>
                @endforeach
            </select>
            <select name="month" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('id')->isoFormat('MMMM') }}
                    </option>
                @endfor
            </select>
            <select name="year" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-search mr-1"></i> Tampilkan
            </button>
        </form>
    </div>

    @if($selectedEmployee)
        <!-- Profil Karyawan Ringkasan -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold">Nama Karyawan</p>
                <p class="text-base font-bold text-gray-800 mt-1">{{ $selectedEmployee->name }}</p>
                <p class="text-xs text-gray-500">ID: {{ $selectedEmployee->employee_id }} | {{ $selectedEmployee->position ?? 'Karyawan' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold">Jatah Libur Bulanan</p>
                <p class="text-base font-bold text-blue-700 mt-1">{{ $selectedEmployee->getEffectiveLeaveQuota() }} Hari / Bulan</p>
                <p class="text-xs text-gray-500">Total libur terpakai: {{ $totalLibur }} hari</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold">Akumulasi Keterlambatan</p>
                <p class="text-base font-bold text-red-600 mt-1">{{ $totalLateIn + $totalLateBreakIn }} Menit</p>
                <p class="text-xs text-gray-500">Masuk: {{ $totalLateIn }} mnt | Istirahat: {{ $totalLateBreakIn }} mnt</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold">Metode Pembayaran</p>
                <p class="text-base font-bold text-gray-800 mt-1 uppercase">{{ $selectedEmployee->payment_method ?? 'Transfer' }}</p>
                <p class="text-xs text-gray-500">{{ $selectedEmployee->bank_name ? $selectedEmployee->bank_name . ' - ' . $selectedEmployee->account_number : 'Tunai / Belum ada rekening' }}</p>
            </div>
        </div>

        <!-- Tabel Detail Absen (1 - 31) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-700 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Hari</th>
                        <th class="px-4 py-3 text-center">Masuk</th>
                        <th class="px-4 py-3 text-center">Istirahat</th>
                        <th class="px-4 py-3 text-center">Masuk Istirahat</th>
                        <th class="px-4 py-3 text-center">Pulang</th>
                        <th class="px-4 py-3 text-center">Terlambat Masuk (Mnt)</th>
                        <th class="px-4 py-3 text-center">Terlambat Msk Istirahat (Mnt)</th>
                        <th class="px-4 py-3 text-center">Keterangan</th>
                        <th class="px-4 py-3 text-center">Total Hari Libur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rows as $r)
                        <tr class="hover:bg-blue-50/50 transition {{ strtolower($r['keterangan']) === 'libur' ? 'bg-amber-50/50' : '' }}">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($r['date'])->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-medium">{{ $r['day_name'] }}</td>
                            <td class="px-4 py-3 text-center font-mono font-medium {{ $r['check_in'] !== '-' ? 'text-gray-900' : 'text-gray-400' }}">{{ $r['check_in'] }}</td>
                            <td class="px-4 py-3 text-center font-mono font-medium {{ $r['break_out'] !== '-' ? 'text-gray-900' : 'text-gray-400' }}">{{ $r['break_out'] }}</td>
                            <td class="px-4 py-3 text-center font-mono font-medium {{ $r['break_in'] !== '-' ? 'text-gray-900' : 'text-gray-400' }}">{{ $r['break_in'] }}</td>
                            <td class="px-4 py-3 text-center font-mono font-medium {{ $r['check_out'] !== '-' ? 'text-gray-900' : 'text-gray-400' }}">{{ $r['check_out'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($r['late_in'] > 0)
                                    <span class="text-red-600 font-bold">{{ $r['late_in'] }} mnt</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($r['late_break_in'] > 0)
                                    <span class="text-red-600 font-bold">{{ $r['late_break_in'] }} mnt</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(strtolower($r['keterangan']) === 'hadir')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800">Hadir</span>
                                @elseif(strtolower($r['keterangan']) === 'izin')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800">Izin</span>
                                @elseif(strtolower($r['keterangan']) === 'libur')
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">Libur</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">{{ $r['keterangan'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-gray-700">
                                {{ $r['total_libur_cumulative'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white p-8 rounded-xl shadow-sm text-center text-gray-500">
            Pilih karyawan untuk melihat rincian absensi bulanan.
        </div>
    @endif
</div>
@endsection
