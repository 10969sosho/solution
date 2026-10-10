@extends('layouts.app')

@section('title', 'Jaga Malam')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Jaga Malam</h1>
            <p class="text-sm text-gray-500">Jadwal dan penugasan karyawan jaga malam harian (Periode: {{ \Carbon\Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM Y') }})</p>
        </div>

        <!-- Filter Bulan/Tahun -->
        <form method="GET" action="{{ route('attendance.night-guard') }}" class="flex items-center gap-2">
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

    <!-- Notifikasi Alert -->
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-lg mr-3"></i>
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-lg mr-3"></i>
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

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
                <p class="text-xs text-green-600 font-medium">Terkonfirmasi</p>
                <p class="text-2xl font-bold text-green-700">{{ $countConfirmed }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-amber-600 font-medium">Draft (Belum Konfirmasi)</p>
                <p class="text-2xl font-bold text-amber-700">{{ $countDraft }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium">Belum Ditentukan</p>
                <p class="text-2xl font-bold text-gray-700">{{ $countEmpty }} Hari</p>
            </div>
            <div class="w-10 h-10 bg-gray-50 text-gray-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-moon"></i>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Tanggal Jaga Malam -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left w-36">Tanggal</th>
                    <th class="px-6 py-3 text-left w-32">Hari</th>
                    <th class="px-6 py-3 text-left">Nama Karyawan</th>
                    <th class="px-6 py-3 text-center w-36">Status</th>
                    <th class="px-6 py-3 text-right w-56">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($days as $item)
                    @php
                        $isConfirmed = ($item['status'] === 'confirmed');
                    @endphp
                    <tr class="hover:bg-blue-50/50 transition {{ $item['is_weekend'] ? 'bg-gray-50/70' : '' }}" x-data="{ editing: {{ $isConfirmed ? 'false' : 'true' }} }">
                        <!-- Tanggal -->
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                        </td>

                        <!-- Hari -->
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                            {{ $item['day_name'] }}
                        </td>

                        <!-- Nama Karyawan (Dropdown) -->
                        <td class="px-6 py-4">
                            <form id="form-{{ $item['day'] }}" method="POST" action="{{ route('attendance.night-guard.update') }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="date" value="{{ $item['date'] }}">

                                <div class="w-full max-w-md">
                                    <!-- Jika sedang mode view (sudah konfirmasi dan tidak sedang diedit) -->
                                    <template x-if="!editing">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-800">
                                                {{ $item['night_guard']?->employee?->name ?? '-' }}
                                            </span>
                                            @if($item['night_guard']?->employee)
                                                <span class="text-xs text-gray-500">
                                                    ({{ $item['night_guard']->employee->position ?? $item['night_guard']->employee->jabatan?->name ?? 'Karyawan' }})
                                                </span>
                                            @endif
                                        </div>
                                    </template>

                                    <!-- Jika mode edit/pilih dropdown -->
                                    <template x-if="editing">
                                        <select name="employee_id" class="w-full rounded-lg border-gray-300 text-xs py-1.5 px-3 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">-- Pilih Karyawan Jaga Malam --</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}" {{ $item['employee_id'] == $emp->id ? 'selected' : '' }}>
                                                    {{ $emp->name }} ({{ $emp->position ?? $emp->department ?? 'Karyawan' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </template>
                                </div>
                            </form>
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            @if($isConfirmed)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1 text-xs"></i> Terkonfirmasi
                                </span>
                            @elseif($item['status'] === 'draft')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    <i class="fas fa-clock mr-1 text-xs"></i> Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                                    <i class="fas fa-minus-circle mr-1 text-xs"></i> Kosong
                                </span>
                            @endif
                        </td>

                        <!-- Aksi (Konfirmasi & Edit) -->
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <!-- Tombol Edit jika sudah terkonfirmasi -->
                                <button type="button" x-show="!editing" @click="editing = true" class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-md text-xs font-medium transition shadow-sm" title="Edit penugasan">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>

                                <!-- Tombol Konfirmasi jika sedang edit -->
                                <button type="submit" form="form-{{ $item['day'] }}" name="action" value="confirm" x-show="editing" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md text-xs font-medium transition shadow-sm" title="Konfirmasi penugasan">
                                    <i class="fas fa-check mr-1"></i> Konfirmasi
                                </button>

                                <!-- Tombol Simpan Draft jika sedang edit -->
                                <button type="submit" form="form-{{ $item['day'] }}" name="action" value="draft" x-show="editing" class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-md text-xs font-medium transition shadow-sm" title="Simpan sebagai draft">
                                    <i class="fas fa-save mr-1"></i> Simpan
                                </button>

                                <!-- Batalkan edit jika sudah pernah konfirmasi -->
                                @if($isConfirmed)
                                    <button type="button" x-show="editing" @click="editing = false" class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-md text-xs font-medium transition" title="Batal ubah">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
