@extends('layouts.app')

@section('title', 'List Kompilasi Harian')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">List Kompilasi Harian</h1>
            <p class="text-sm text-gray-500">Daftar seluruh tanggal dan status verifikasi harian (Draft, Lock, Fix)</p>
        </div>

        <!-- Filter Bulan/Tahun -->
        <form method="GET" action="{{ route('attendance.compilation') }}" class="flex items-center gap-2">
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

    <!-- Ringkasan Status -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                <p class="text-xs text-green-600 font-medium">Status FIX</p>
                <p class="text-2xl font-bold text-green-700">{{ $countFix }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-amber-600 font-medium">Status LOCK</p>
                <p class="text-2xl font-bold text-amber-700">{{ $countLock }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-lock"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">Status DRAFT</p>
                <p class="text-2xl font-bold text-gray-700">{{ $countDraft }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-gray-50 text-gray-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-pencil-alt"></i>
            </div>
        </div>
    </div>

    @if(!$allFixed)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-amber-500 text-lg mr-3"></i>
                <p class="text-sm text-amber-800">
                    <strong>Perhatian:</strong> Seluruh tanggal dalam bulan ini wajib berstatus <strong>FIX</strong> sebelum data absensi dapat ditarik untuk Rekap Bulanan &amp; Penggajian. (Masih tersisa {{ $totalDays - $countFix }} hari yang belum FIX).
                </p>
            </div>
        </div>
    @else
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-lg mr-3"></i>
                <p class="text-sm text-green-800">
                    <strong>Lengkap!</strong> Seluruh tanggal bulan ini sudah berstatus <strong>FIX</strong>. Siap ditarik ke Rekap Bulanan.
                </p>
            </div>
            <a href="{{ route('attendance.monthly-recap', ['year' => $year, 'month' => $month]) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg shadow transition">
                Buka Rekap Bulanan <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    @endif

    <!-- Tabel Daftar Tanggal -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Tanggal</th>
                    <th class="px-6 py-3 text-left">Hari</th>
                    <th class="px-6 py-3 text-center">Status</th>
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
                            @if($item['is_weekend'])
                                <span class="ml-1 text-xs text-red-500 font-medium">(Weekend)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item['status'] === 'fix')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1 text-xs"></i> FIX
                                </span>
                            @elseif($item['status'] === 'lock')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    <i class="fas fa-lock mr-1 text-xs"></i> LOCK
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    <i class="fas fa-file-alt mr-1 text-xs"></i> DRAFT
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('attendance.daily', ['date' => $item['date']]) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-medium transition shadow-sm">
                                <i class="fas fa-sign-in-alt mr-1.5"></i> Masuk Absen Daily
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
