@extends('layouts.app')

@section('title', 'Manajemen Izin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Izin</h1>
            <p class="text-sm text-gray-500">
                Rekap izin potong / tidak potong gaji per bulan &amp; input nominal potongan izin
                (Periode: {{ \Carbon\Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM Y') }})
            </p>
        </div>

        <!-- Filter Bulan/Tahun (default bulan berjalan) -->
        <form method="GET" action="{{ route('permits.management') }}" class="flex items-center gap-2">
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

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
        <div class="flex items-start text-xs text-amber-900 leading-relaxed">
            <i class="fas fa-info-circle text-amber-500 mt-0.5 mr-2"></i>
            <p>
                Tandai izin karyawan lewat <strong>Absen Daily &rarr; Kelola Izin</strong> (potong gaji / tidak potong).
                Nominal potongan izin diinput di halaman ini per bulan dan otomatis ditarik ke
                <strong>Rekap Bulanan</strong> serta komponen <strong>potongan izin</strong> saat generate penggajian periode tersebut.
                Karyawan dengan izin <strong>tidak potong</strong> tidak dikenakan potongan terlambat otomatis.
            </p>
        </div>
    </div>

    <!-- Tabel Manajemen Izin -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase">
                <tr>
                    <th class="px-4 py-3 text-left w-12">No</th>
                    <th class="px-4 py-3 text-left">Nama Karyawan</th>
                    <th class="px-4 py-3 text-left">Golongan / Jabatan</th>
                    <th class="px-3 py-3 text-center">Izin Potong Gaji (hari)</th>
                    <th class="px-3 py-3 text-center">Izin Tidak Potong (hari)</th>
                    <th class="px-4 py-3 text-left">Nominal Potongan Izin (Rp)</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $index => $item)
                    @php
                        $emp = $item['employee'];
                        $deduction = $item['deduction'];
                    @endphp
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <div>{{ $emp->name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $emp->position ?? 'Karyawan' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $emp->golongan?->name ?? '-' }}{{ $emp->jabatan?->name ? ' / ' . $emp->jabatan->name : '' }}
                        </td>
                        <td class="px-3 py-3 text-center font-bold {{ $item['potong_days'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['potong_days'] }} hari
                        </td>
                        <td class="px-3 py-3 text-center font-bold {{ $item['tidak_potong_days'] > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $item['tidak_potong_days'] }} hari
                        </td>
                        <td class="px-4 py-3">
                            <form id="deduction-form-{{ $emp->id }}" action="{{ route('permits.management.store') }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="number" name="amount" min="0" step="1"
                                    value="{{ old('amount', $deduction?->amount ? rtrim(rtrim(number_format((float) $deduction->amount, 2, '.', ''), '0'), '.') : 0) }}"
                                    class="w-40 rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0">
                                @if($deduction)
                                    <span class="text-[10px] text-gray-400 whitespace-nowrap">tersimpan</span>
                                @endif
                            </form>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="submit" form="deduction-form-{{ $emp->id }}"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada karyawan aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
