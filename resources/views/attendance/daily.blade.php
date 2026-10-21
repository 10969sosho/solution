@extends('layouts.app')

@section('title', 'Absen Daily - ' . \Carbon\Carbon::parse($dateStr)->format('d M Y'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    <!-- Header & Navigasi Tanggal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('attendance.compilation', ['year' => \Carbon\Carbon::parse($dateStr)->year, 'month' => \Carbon\Carbon::parse($dateStr)->month]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke List Kompilasi
                </a>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">
                Absen Daily: {{ \Carbon\Carbon::parse($dateStr)->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </h1>
            <p class="text-xs text-gray-500">Urutan Checklock: Masuk (06:00-09:00) &rarr; Istirahat (11:00-12:29) &rarr; Masuk Istirahat (12:30-13:30) &rarr; Pulang (15:30-20:00)</p>
        </div>

        <!-- Filter Tanggal & Status Saat Ini -->
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('attendance.daily') }}" class="flex flex-wrap items-center gap-2">
                <input type="date" name="date" value="{{ $dateStr }}" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                <select name="filter" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Semua data</option>
                    <option value="anomaly" {{ $filter === 'anomaly' ? 'selected' : '' }}>Anomali</option>
                    <option value="normal" {{ $filter === 'normal' ? 'selected' : '' }}>Normal</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <div class="border-l border-gray-300 pl-3">
                <span class="text-xs font-semibold text-gray-500 uppercase block">Status Tanggal:</span>
                @if($compilation->status === 'fix')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-300">
                        <i class="fas fa-check-double mr-1"></i> FIX
                    </span>
                @elseif($compilation->status === 'lock')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fas fa-lock mr-1"></i> LOCK
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-300">
                        <i class="fas fa-edit mr-1"></i> DRAFT
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert Anomali Penjelasan -->
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <span class="inline-block w-4 h-4 bg-red-300 border border-red-500 rounded mr-2"></span>
            </div>
            <div class="text-xs text-red-900 leading-relaxed">
                <strong>Penanda Absen Kosong / Bolong (Stabilo Merah):</strong> Baris berwarna merah menandakan tidak ada absen masuk/pulang atau checklock tidak lengkap. Kelola jam lewat tombol <strong>Kelola Jam</strong> atau catat izin lewat <strong>Kelola Izin</strong>.
            </div>
        </div>
    </div>

    <!-- Tabel Absensi Daily -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto mb-6">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase">
                <tr>
                    <th class="px-4 py-3 text-left w-12">#</th>
                    <th class="px-4 py-3 text-left">Nama Karyawan</th>
                    <th class="px-3 py-3 text-center">Masuk</th>
                    <th class="px-3 py-3 text-center">Istirahat</th>
                    <th class="px-3 py-3 text-center">Msk Istirahat</th>
                    <th class="px-3 py-3 text-center">Pulang</th>
                    <th class="px-3 py-3 text-center">Telat Masuk</th>
                    <th class="px-3 py-3 text-center">Telat Msk Istirahat</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($dailyRecords as $index => $item)
                    @php
                        $emp = $item['employee'];
                        $att = $item['attendance'];
                        $isAnomaly = $att?->is_anomaly ?? false;
                        $isIgnored = $att?->isLateIgnored() ?? false;
                        $isEmptyAttendance = $att && (! $att->check_in || ! $att->check_out);
                        $isBolongAttendance = $att && $att->check_in && $att->check_out
                            && (! $att->break_out || ! $att->break_in);
                        $editedFields = is_array($att?->edited_fields) ? $att->edited_fields : [];
                    @endphp
                    <tr class="transition {{ ($isEmptyAttendance || $isBolongAttendance) ? 'bg-red-100 hover:bg-red-200/80' : ($isAnomaly ? 'bg-yellow-100 hover:bg-yellow-200/80' : 'hover:bg-blue-50/50') }}">
                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <div>{{ $emp->name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $emp->position ?? $emp->department ?? 'Karyawan' }}</div>
                            @if($isAnomaly && $att?->anomaly_reason)
                                <div class="text-[10px] text-amber-700 font-semibold mt-0.5">
                                    <i class="fas fa-exclamation-circle"></i> {{ $att->anomaly_reason }}
                                </div>
                            @endif
                        </td>

                        <!-- Masuk -->
                        <td class="px-3 py-3 text-center font-mono font-medium {{ $att?->check_in ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $att?->check_in ? substr($att->check_in, 0, 5) : '-' }}
                            @if(!empty($editedFields['check_in']))<span class="block text-[9px] font-bold text-orange-600"><i class="fas fa-pen"></i> diedit</span>@endif
                        </td>

                        <!-- Istirahat -->
                        <td class="px-3 py-3 text-center font-mono font-medium {{ $att?->break_out ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $att?->break_out ? substr($att->break_out, 0, 5) : '-' }}
                            @if(!empty($editedFields['break_out']))<span class="block text-[9px] font-bold text-orange-600"><i class="fas fa-pen"></i> diedit</span>@endif
                        </td>

                        <!-- Masuk Istirahat -->
                        <td class="px-3 py-3 text-center font-mono font-medium {{ $att?->break_in ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $att?->break_in ? substr($att->break_in, 0, 5) : '-' }}
                            @if(!empty($editedFields['break_in']))<span class="block text-[9px] font-bold text-orange-600"><i class="fas fa-pen"></i> diedit</span>@endif
                        </td>

                        <!-- Pulang -->
                        <td class="px-3 py-3 text-center font-mono font-medium {{ $att?->check_out ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $att?->check_out ? substr($att->check_out, 0, 5) : '-' }}
                            @if(!empty($editedFields['check_out']))<span class="block text-[9px] font-bold text-orange-600"><i class="fas fa-pen"></i> diedit</span>@endif
                        </td>

                        <!-- Telat Masuk -->
                        <td class="px-3 py-3 text-center">
                            @if(($att?->late_check_in_minutes ?? 0) > 0)
                                <span class="{{ $isIgnored ? 'line-through text-gray-400' : 'text-red-600 font-bold' }}">
                                    {{ $att->late_check_in_minutes }} mnt
                                </span>
                                @if($isIgnored)
                                    <span class="block text-[9px] text-green-600 font-semibold">(diabaikan)</span>
                                @endif
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>

                        <!-- Telat Masuk Istirahat -->
                        <td class="px-3 py-3 text-center">
                            @if(($att?->late_break_in_minutes ?? 0) > 0)
                                <span class="{{ $isIgnored ? 'line-through text-gray-400' : 'text-red-600 font-bold' }}">
                                    {{ $att->late_break_in_minutes }} mnt
                                </span>
                                @if($isIgnored)
                                    <span class="block text-[9px] text-green-600 font-semibold">(diabaikan)</span>
                                @endif
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3 text-left">
                            @if($isEmptyAttendance)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-600 text-white" title="Absen masuk atau pulang tidak ada">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Absen Kosong
                                </span>
                            @elseif($isBolongAttendance)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white" title="Scan istirahat tidak lengkap">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Absen Bolong
                                </span>
                            @endif
                            <div class="font-medium text-gray-800 capitalize">{{ $att?->keterangan ?? 'Hadir' }}</div>
                        </td>

                        <!-- Action: Kelola Jam + Kelola Izin -->
                        @if($att)
                            @php
                                $employeePermits = $permitsByEmployee->get($emp->id, collect());
                            @endphp
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- Tombol Kelola Jam -->
                                    <button type="button" onclick="openEditModal('time-modal-{{ $att->id }}')" class="px-2.5 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200" title="Kelola Jam">
                                        <i class="fas fa-clock mr-1"></i> Kelola Jam
                                    </button>

                                    <!-- Tombol Kelola Izin -->
                                    <button type="button" onclick="openPermitModal('permit-modal-{{ $att->id }}')" class="px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200">
                                        <i class="fas fa-file-signature mr-1"></i> Kelola Izin
                                        @if($employeePermits->isNotEmpty())<span class="ml-1 rounded-full bg-blue-600 px-1.5 text-white">{{ $employeePermits->count() }}</span>@endif
                                    </button>
                                </div>

                                <!-- Modal Kelola Jam -->
                                <div id="time-modal-{{ $att->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4" onclick="closeEditModal(event, 'time-modal-{{ $att->id }}')">
                                    <div class="w-full max-w-lg rounded-xl bg-white p-5 text-left shadow-xl" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-between border-b pb-3">
                                            <div>
                                                <h3 class="font-semibold text-gray-800">Kelola Jam</h3>
                                                <p class="text-xs text-gray-500">{{ $emp->name }} · {{ \Carbon\Carbon::parse($dateStr)->format('d/m/Y') }}</p>
                                            </div>
                                            <button type="button" onclick="closeEditModal(null, 'time-modal-{{ $att->id }}')" class="text-xl text-gray-400 hover:text-gray-700">&times;</button>
                                        </div>

                                        <form action="{{ route('attendance.daily.save', $att->id) }}" method="POST" class="space-y-4 pt-4">
                                            @csrf
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jam Masuk</label>
                                                    <input type="time" name="check_in" value="{{ $att->check_in ? substr($att->check_in, 0, 5) : '' }}" class="w-full rounded border-gray-300 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jam Istirahat (Keluar)</label>
                                                    <input type="time" name="break_out" value="{{ $att->break_out ? substr($att->break_out, 0, 5) : '' }}" class="w-full rounded border-gray-300 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jam Masuk Istirahat</label>
                                                    <input type="time" name="break_in" value="{{ $att->break_in ? substr($att->break_in, 0, 5) : '' }}" class="w-full rounded border-gray-300 text-xs">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jam Pulang</label>
                                                    <input type="time" name="check_out" value="{{ $att->check_out ? substr($att->check_out, 0, 5) : '' }}" class="w-full rounded border-gray-300 text-xs">
                                                </div>
                                            </div>

                                            <div class="rounded border border-orange-200 bg-orange-50 p-3 text-[11px] text-orange-800">
                                                Jam yang diubah akan ditandai <span class="font-bold text-orange-600">diedit</span> di tabel (bukan jam asli mesin absen).
                                            </div>

                                            <div class="flex justify-end gap-2 border-t pt-3">
                                                <button type="button" onclick="closeEditModal(null, 'time-modal-{{ $att->id }}')" class="rounded bg-gray-100 px-4 py-2 text-xs text-gray-700">Batal</button>
                                                <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-xs font-semibold text-white">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Modal Kelola Izin -->
                                <div id="permit-modal-{{ $att->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4" onclick="closePermitModal(event, 'permit-modal-{{ $att->id }}')">
                                    <div class="w-full max-w-2xl rounded-xl bg-white p-5 text-left shadow-xl" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-between border-b pb-3">
                                            <div><h3 class="font-semibold text-gray-800">Kelola Izin</h3><p class="text-xs text-gray-500">{{ $emp->name }} · {{ \Carbon\Carbon::parse($dateStr)->format('d/m/Y') }}</p></div>
                                            <button type="button" onclick="closePermitModal(null, 'permit-modal-{{ $att->id }}')" class="text-xl text-gray-400 hover:text-gray-700">&times;</button>
                                        </div>
                                        <div class="my-4 space-y-2">
                                            @forelse($employeePermits as $permit)
                                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-xs">
                                                    <span>{{ ucfirst(str_replace('_', ' ', $permit->category)) }} · {{ $permit->type === 'libur' ? 'Libur (' . ($permit->leave_duration === 'setengah_hari' ? 'Setengah hari' : '1 hari') . ')' : ($permit->type === 'no_deduction' ? 'Tidak potong · ' . $permit->duration_minutes . ' menit' : 'Potong gaji · Rp ' . number_format($permit->nominal, 0, ',', '.')) }}</span>
                                                    <span class="text-gray-500">{{ $permit->reason }}</span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-gray-500">Belum ada izin pada tanggal ini.</p>
                                            @endforelse
                                        </div>
                                        <form action="{{ route('permits.store') }}" method="POST" class="space-y-3 border-t pt-4">
                                            @csrf
                                            <input type="hidden" name="source" value="attendance_daily"><input type="hidden" name="employee_id" value="{{ $emp->id }}"><input type="hidden" name="permit_date" value="{{ $dateStr }}">
                                            <div id="permit-rows-permit-modal-{{ $att->id }}" data-next="0" class="space-y-3"></div>
                                            <button type="button" onclick="addPermitRow('permit-modal-{{ $att->id }}')" class="w-full rounded border border-dashed border-blue-300 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                                <i class="fas fa-plus mr-1"></i> Tambah Izin Lagi
                                            </button>
                                            <div class="flex justify-end gap-2"><button type="button" onclick="closePermitModal(null, 'permit-modal-{{ $att->id }}')" class="rounded bg-gray-100 px-4 py-2 text-sm text-gray-700">Batal</button><button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Simpan Izin</button></div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        @else
                            <td class="px-3 py-3 text-center text-gray-400">Belum ada record</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">Tidak ada data karyawan aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Status Bar & Aksi Bawah: Draft, Lock, Fix -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <span class="text-xs text-gray-500">Kompilasi Harian Tanggal {{ \Carbon\Carbon::parse($dateStr)->format('d/m/Y') }}:</span>
            <p class="text-sm font-semibold text-gray-800">
                Pilih status untuk menyimpan dan memverifikasi data harian sebelum ditarik ke Rekap Bulanan.
            </p>
        </div>

        <form action="{{ route('attendance.daily.status') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="date" value="{{ $dateStr }}">

            <button type="submit" name="status" value="draft" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $compilation->status === 'draft' ? 'bg-gray-800 text-white shadow-md ring-2 ring-gray-400' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' }}">
                <i class="fas fa-pencil-alt mr-1"></i> Set DRAFT
            </button>

            <button type="submit" name="status" value="lock" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $compilation->status === 'lock' ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-400' : 'bg-amber-50 hover:bg-amber-100 text-amber-800' }}">
                <i class="fas fa-lock mr-1"></i> Set LOCK
            </button>

            <button type="submit" name="status" value="fix" class="px-5 py-2 rounded-lg text-xs font-bold transition {{ $compilation->status === 'fix' ? 'bg-green-600 text-white shadow-md ring-2 ring-green-400' : 'bg-green-50 hover:bg-green-100 text-green-800' }}">
                <i class="fas fa-check-double mr-1"></i> FIX SEMUA &amp; SIMPAN
            </button>
        </form>
    </div>
</div>

<template id="permit-row-template">
    <div class="permit-row rounded-lg border border-gray-200 p-3 space-y-2" data-permit-row>
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-semibold uppercase text-gray-400" data-row-label>Izin</span>
            <button type="button" class="text-xs text-red-500 hover:underline" onclick="removePermitRow(this)">Hapus</button>
        </div>
        <input type="hidden" name="__START_TIME__" value="08:00">
        <input type="hidden" name="__END_TIME__" value="08:15">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <label class="text-xs font-medium text-gray-700">Tipe izin
                <select name="__CATEGORY__" onchange="toggleCategoryType(this)" required class="mt-1 w-full rounded border-gray-300 text-sm">
                    <option value="terlambat">Terlambat</option>
                    <option value="pulang_awal">Pulang awal</option>
                    <option value="tidak_masuk">Tidak masuk</option>
                    <option value="libur">Libur</option>
                </select>
            </label>
            <label class="text-xs font-medium text-gray-700">Keterangan izin
                <select name="__TYPE__" required onchange="togglePermitRowFields(this)" class="mt-1 w-full rounded border-gray-300 text-sm">
                    <option value="salary_deduction">Potong gaji</option>
                    <option value="no_deduction">Tidak potong</option>
                    <option value="libur" class="hidden">Libur</option>
                </select>
            </label>
            <label class="hidden text-xs font-medium text-gray-700" data-permit-leave-duration>Pilihan Libur
                <select name="__LEAVE_DURATION__" class="mt-1 w-full rounded border-gray-300 text-sm">
                    <option value="1_hari">1 Hari (Kurangi 1 kuota libur)</option>
                    <option value="setengah_hari">Setengah Hari (Kurangi 0.5 kuota libur)</option>
                </select>
            </label>
            <label class="hidden text-xs font-medium text-gray-700" data-permit-minutes>Menit izin
                <input type="number" name="__MINUTES__" min="1" placeholder="Masukkan jumlah menit" class="mt-1 w-full rounded border-gray-300 text-sm">
            </label>
            <input type="text" name="__REASON__" placeholder="Alasan izin / keterangan" required class="rounded border-gray-300 text-sm md:col-span-2">
        </div>
    </div>
</template>

<script>
    function openEditModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeEditModal(event, id) {
        if (event && event.target !== event.currentTarget) return;
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openPermitModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const wrap = document.getElementById('permit-rows-' + id);
        if (wrap && wrap.childElementCount === 0) addPermitRow(id);
    }

    function addPermitRow(modalId) {
        const wrap = document.getElementById('permit-rows-' + modalId);
        const tpl = document.getElementById('permit-row-template');
        const i = parseInt(wrap.dataset.next || '0', 10);
        wrap.dataset.next = String(i + 1);
        const p = 'permits[' + i + ']';
        let html = tpl.innerHTML
            .replaceAll('__START_TIME__', p + '[start_time]')
            .replaceAll('__END_TIME__', p + '[end_time]')
            .replaceAll('__CATEGORY__', p + '[category]')
            .replaceAll('__TYPE__', p + '[type]')
            .replaceAll('__LEAVE_DURATION__', p + '[leave_duration]')
            .replaceAll('__MINUTES__', p + '[permit_minutes]')
            .replaceAll('__REASON__', p + '[reason]');
        wrap.insertAdjacentHTML('beforeend', html);
        const row = wrap.lastElementChild;
        row.querySelector('[data-row-label]').textContent = 'Izin #' + (i + 1);
        toggleCategoryType(row.querySelector('select[name$="[category]"]'));
    }

    function removePermitRow(btn) {
        btn.closest('[data-permit-row]').remove();
    }

    function toggleCategoryType(categorySelect) {
        const row = categorySelect.closest('[data-permit-row]');
        if (!row) return;
        const typeSelect = row.querySelector('select[name$="[type]"]');
        const liburOpt = typeSelect.querySelector('option[value="libur"]');
        const salaryOpt = typeSelect.querySelector('option[value="salary_deduction"]');
        const noDeductOpt = typeSelect.querySelector('option[value="no_deduction"]');

        if (categorySelect.value === 'libur') {
            liburOpt.classList.remove('hidden');
            salaryOpt.classList.add('hidden');
            noDeductOpt.classList.add('hidden');
            typeSelect.value = 'libur';
        } else {
            liburOpt.classList.add('hidden');
            salaryOpt.classList.remove('hidden');
            noDeductOpt.classList.remove('hidden');
            if (typeSelect.value === 'libur') {
                typeSelect.value = 'salary_deduction';
            }
        }
        togglePermitRowFields(typeSelect);
    }

    function togglePermitRowFields(select) {
        const row = select.closest('[data-permit-row]');
        if (!row) return;
        const leaveDuration = row.querySelector('[data-permit-leave-duration]');
        const minutes = row.querySelector('[data-permit-minutes]');

        const isLibur = select.value === 'libur';
        const isNoDeduction = select.value === 'no_deduction';

        if (leaveDuration) leaveDuration.classList.toggle('hidden', !isLibur);
        if (minutes) {
            minutes.classList.toggle('hidden', !isNoDeduction);
            const minInput = minutes.querySelector('input');
            if (minInput) minInput.required = isNoDeduction;
        }
    }

    function closePermitModal(event, id) {
        if (event && event.target !== event.currentTarget) return;
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
