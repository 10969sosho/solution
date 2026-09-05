@extends('layouts.app')

@section('title', 'Laporan Absensi Rinci - ADMS')
@section('page-title', 'Laporan Absensi Rinci')
@section('page-subtitle', 'Detail absensi karyawan per hari dengan aksi proses izin')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('reports.attendanceDetail') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" value="{{ $startDate }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" value="{{ $endDate }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc }}" {{ $location == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($positions as $pos)
                    <option value="{{ $pos }}" {{ $position == $pos ? 'selected' : '' }}>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                Detail Absensi
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            @forelse($reportData as $data)
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $data['employee']->name }}</h4>
                        <p class="text-sm text-gray-500">{{ $data['employee']->jabatan->name ?? '-' }} | {{ $data['employee']->golongan->name ?? '-' }} | {{ $data['employee']->lokasi->name ?? '-' }}</p>
                    </div>
                    @if($data['total_late_minutes'] > 0 || $data['total_early_leave_minutes'] > 0)
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Perlu Diproses
                    </span>
                    @endif
                </div>
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Masuk</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Keluar Istirahat</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Masuk Istirahat</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Keluar</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Kerja (jam)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Terlambat</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Pulang Awal</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($data['daily_details'] as $day)
                        @php
                            $hasLate = $day['late_minutes'] > 0;
                            $hasEarlyLeave = $day['early_leave_minutes'] > 0;
                            $hasIzin = $day['izin_no_deduction'] > 0 || $day['izin_salary_deduction'] > 0;
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $hasLate ? 'bg-red-50/50' : '' }}">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($hasLate)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Terlambat {{ $day['late_minutes'] }} menit">
                                        <i class="fas fa-clock mr-1"></i>Telat
                                    </span>
                                @elseif($hasEarlyLeave)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800" title="Pulang awal {{ $day['early_leave_minutes'] }} menit">
                                        <i class="fas fa-arrow-left mr-1"></i>Pulang Awal
                                    </span>
                                @elseif($day['check_in'] !== '-' || $day['check_out'] !== '-')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Hadir
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        <i class="fas fa-minus mr-1"></i>Absen
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['check_in'] !== '-' ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['check_in'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['break_out'] !== '-' ? 'text-gray-700' : 'text-gray-400' }}">
                                {{ $day['break_out'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['break_in'] !== '-' ? 'text-gray-700' : 'text-gray-400' }}">
                                {{ $day['break_in'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm {{ $day['check_out'] !== '-' ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $day['check_out'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium {{ $day['work_minutes'] > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                                {{ $day['work_minutes'] > 0 ? number_format($day['work_minutes'] / 60, 1) : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium {{ $hasLate ? 'text-red-600' : 'text-gray-400' }}">
                                {{ $hasLate ? $day['late_minutes'] . ' m' : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium {{ $hasEarlyLeave ? 'text-orange-600' : 'text-gray-400' }}">
                                {{ $hasEarlyLeave ? $day['early_leave_minutes'] . ' m' : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($hasLate || $hasEarlyLeave)
                                <button onclick="openProsesModal({{ $data['employee']->id }}, '{{ $day['date'] }}', {{ $day['late_minutes'] }}, {{ $day['early_leave_minutes'] }}, '{{ $data['employee']->golongan_id }}', '{{ $day['check_in'] }}', '{{ $day['check_out'] }}')"
                                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium">
                                    <i class="fas fa-edit mr-1"></i>Proses
                                </button>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr class="font-semibold">
                            <td colspan="5" class="px-4 py-2 text-sm text-gray-700">Total</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-800">
                                {{ number_format($data['total_work_minutes'] / 60, 1) }} jam
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-red-600">
                                {{ $data['total_late_minutes'] ? $data['total_late_minutes'] . ' m' : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-orange-600">
                                {{ $data['total_early_leave_minutes'] ? $data['total_early_leave_minutes'] . ' m' : '-' }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Tidak ada data absensi untuk filter yang dipilih</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Proses Izin -->
<div id="prosesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-contract text-blue-600 mr-2"></i>
                Proses Izin Karyawan
            </h3>
        </div>
        <form action="{{ route('permits.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="source" value="attendance_report">
            <input type="hidden" name="employee_id" id="modal_employee_id">
            <input type="hidden" name="permit_date" id="modal_permit_date">

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><strong>Tanggal:</strong> <span id="modal_date_display"></span></div>
                    <div><strong>Karyawan ID:</strong> <span id="modal_emp_id_display"></span></div>
                    <div><strong>Terlambat:</strong> <span id="modal_late_display" class="text-red-600"></span></div>
                    <div><strong>Pulang Awal:</strong> <span id="modal_early_display" class="text-orange-600"></span></div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Izin <span class="text-red-500">*</span></label>
                <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="modal_category_select">
                    <option value="terlambat">Terlambat</option>
                    <option value="pulang_awal">Pulang Lebih Awal</option>
                </select>
            </div>

            <div id="modal-late-type-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Keterlambatan <span class="text-red-500">*</span></label>
                <select name="late_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="masuk_kerja">Masuk Kerja</option>
                    <option value="setelah_istirahat">Setelah Istirahat</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" id="modal_start_time" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" id="modal_end_time" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Menit Terlambat <span class="text-red-500">*</span></label>
                <input type="number" name="late_minutes" id="modal_late_minutes" min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div id="modal-late-fine-display" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Denda Terlambat:</span>
                    <span id="modal-late-fine-amount" class="text-lg font-bold text-red-600">Rp 0</span>
                </div>
                <input type="hidden" name="late_fine_amount" id="modal_late_fine_amount" value="0">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Potongan <span class="text-red-500">*</span></label>
                <select name="deduction_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="salary_deduction">Potong Gaji (Sesuai Ketentuan)</option>
                    <option value="no_deduction">Tidak Potong Gaji (Izin)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="2" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Alasan keterlambatan / pulang awal"></textarea>
            </div>

            <input type="hidden" name="location" id="modal_location" value="">
            <input type="hidden" name="position" id="modal_position" value="">

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan Izin
                </button>
                <button type="button" onclick="closeProsesModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const potonganData = @json($potonganTerlamats);

    function formatRupiah(amount) {
        return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function calculateModalLateFine() {
        const golonganId = document.getElementById('modal_golongan_id')?.value;
        const lateMinutes = parseInt(document.getElementById('modal_late_minutes').value) || 0;
        const lateType = document.querySelector('#prosesModal select[name="late_type"]')?.value || 'masuk_kerja';

        if (!golonganId || lateMinutes <= 0) {
            document.getElementById('modal_late_fine_amount').value = 0;
            document.getElementById('modal-late-fine-amount').textContent = formatRupiah(0);
            return;
        }

        const matched = potonganData.find(function(p) {
            return p.golongan_id == golonganId &&
                p.type === lateType &&
                lateMinutes >= p.min_minutes &&
                (p.max_minutes === null || lateMinutes <= p.max_minutes);
        });

        const fine = matched ? parseInt(matched.amount) : 0;
        document.getElementById('modal_late_fine_amount').value = fine;
        document.getElementById('modal-late-fine-amount').textContent = formatRupiah(fine);
    }

    function openProsesModal(employeeId, date, lateMinutes, earlyLeaveMinutes, golonganId, checkIn, checkOut) {
        const modal = document.getElementById('prosesModal');
        const modalEmployeeId = document.getElementById('modal_employee_id');
        const modalPermitDate = document.getElementById('modal_permit_date');
        const modalDateDisplay = document.getElementById('modal_date_display');
        const modalEmpIdDisplay = document.getElementById('modal_emp_id_display');
        const modalLateDisplay = document.getElementById('modal_late_display');
        const modalEarlyDisplay = document.getElementById('modal_early_display');
        const modalLateMinutes = document.getElementById('modal_late_minutes');
        const modalCategorySelect = document.getElementById('modal_category_select');

        // Store golongan_id for fine calculation
        let golonganInput = document.getElementById('modal_golongan_id');
        if (!golonganInput) {
            golonganInput = document.createElement('input');
            golonganInput.type = 'hidden';
            golonganInput.id = 'modal_golongan_id';
            modal.querySelector('form').appendChild(golonganInput);
        }
        golonganInput.value = golonganId;

        modalEmployeeId.value = employeeId;
        modalPermitDate.value = date;
        modalDateDisplay.textContent = new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        modalEmpIdDisplay.textContent = employeeId;
        modalLateDisplay.textContent = lateMinutes > 0 ? lateMinutes + ' menit' : '-';
        modalEarlyDisplay.textContent = earlyLeaveMinutes > 0 ? earlyLeaveMinutes + ' menit' : '-';

        if (lateMinutes > 0) {
            modalCategorySelect.value = 'terlambat';
            modalLateMinutes.value = lateMinutes;
            modalLateMinutes.max = lateMinutes;
        } else if (earlyLeaveMinutes > 0) {
            modalCategorySelect.value = 'pulang_awal';
            modalLateMinutes.value = earlyLeaveMinutes;
            modalLateMinutes.max = earlyLeaveMinutes;
        }

        // Set times
        document.getElementById('modal_start_time').value = checkIn !== '-' ? checkIn : '07:00';
        document.getElementById('modal_end_time').value = checkOut !== '-' ? checkOut : '17:00';

        calculateModalLateFine();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeProsesModal() {
        const modal = document.getElementById('prosesModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('modal_late_minutes')?.addEventListener('input', calculateModalLateFine);
        document.querySelector('#prosesModal select[name="late_type"]')?.addEventListener('change', calculateModalLateFine);

        // Close modal on outside click
        document.getElementById('prosesModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeProsesModal();
        });
    });
</script>
@endpush
@endsection
