@extends('layouts.app')

@section('title', 'List Kompilasi Jaga Malam')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">List Kompilasi Jaga Malam</h1>
            <p class="text-sm text-gray-500">
                Kompilasi absensi petugas jaga malam (17:00 - 06:00) per tanggal
                (Periode: {{ \Carbon\Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM Y') }})
            </p>
        </div>

        <!-- Filter Bulan/Tahun -->
        <form method="GET" action="{{ route('attendance.night-guard-compilation') }}" class="flex items-center gap-2">
            <select name="month" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('id')->isoFormat('MMMM') }}
                    </option>
                @endfor
            </select>
            <select name="year" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Hari</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalDays }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">Petugas Jaga Malam</p>
                <p class="text-2xl font-bold text-gray-800">{{ count($employees) }} Orang</p>
            </div>
            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-moon"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-green-600 font-medium">Total Kehadiran (kali)</p>
                <p class="text-2xl font-bold text-green-700">{{ $totalPresent }} / {{ $totalAssigned }}</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    @if(count($employees) === 0)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-amber-500 text-lg mr-3"></i>
                <p class="text-sm text-amber-800">
                    Belum ada karyawan bertugas jaga malam. Buka
                    <a href="{{ route('employees.index') }}" class="underline font-semibold">Data Karyawan</a>,
                    lalu centang <strong>Bertugas Jaga Malam</strong>.
                </p>
            </div>
        </div>
    @endif

    <!-- Tabel Kompilasi Jaga Malam -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-left">Hari</th>
                    <th class="px-4 py-3 text-center">Hadir</th>
                    <th class="px-4 py-3 text-center">Belum Absen</th>
                    <th class="px-4 py-3 text-center">Status Penugasan</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($days as $item)
                    <tr class="hover:bg-blue-50/50 transition {{ $item['is_weekend'] ? 'bg-gray-50/70' : '' }}">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $item['day_name'] }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold {{ $item['present'] > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                {{ $item['present'] }} / {{ count($employees) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item['absent'] > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700" title="{{ implode(', ', $item['absent_names']) }}">
                                    {{ $item['absent'] }} orang
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item['guard'] && $item['guard']->isConfirmed())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1 text-xs"></i> Confirmed
                                </span>
                            @elseif($item['guard'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    <i class="fas fa-file-alt mr-1 text-xs"></i> Draft
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('attendance.night-guard', ['date' => $item['date']]) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-xs font-medium transition shadow-sm">
                                <i class="fas fa-moon mr-1.5"></i> Buka Jaga Malam
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
