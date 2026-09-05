@extends('layouts.app')

@section('title', 'Manajemen Izin - ADMS')
@section('page-title', 'Manajemen Izin')
@section('page-subtitle', 'Pencatatan izin karyawan & proses batch')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Perlu Diproses Section -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Perlu Diproses (Belum Ada Izin)
            </h3>
            <p class="text-sm text-gray-500 mt-1">Data absensi yang terdeteksi terlambat/pulang awal namun belum dicatat izinnya</p>
        </div>

        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <form method="GET" action="{{ route('permits.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="issue_start_date" value="{{ request('issue_start_date', now()->subDays(7)->toDateString()) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="issue_end_date" value="{{ request('issue_end_date', now()->toDateString()) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="selectAllIssues()" class="w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        <i class="fas fa-check-double mr-2"></i>Pilih Semua
                    </button>
                </div>
            </form>
        </div>

        @if(count($pendingIssues) > 0)
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-600">{{ count($pendingIssues) }} data perlu diproses</span>
                <button onclick="bulkProcessSelected()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium" id="bulkProcessBtn" disabled>
                    <i class="fas fa-paper-plane mr-2"></i>Proses Terpilih
                </button>
            </div>

            <form id="bulkPermitForm" action="{{ route('permits.storeBulk') }}" method="POST">
                @csrf
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-10">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Masuk</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Keluar</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terlambat</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pulang Awal</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipe Izin</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Potongan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($pendingIssues as $index => $issue)
                            <tr class="hover:bg-gray-50 issue-row" data-index="{{ $index }}">
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="permits[{{ $index }}][selected]" class="issue-checkbox rounded border-gray-300" onchange="updateBulkButton()">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="permits[{{ $index }}][employee_id]" value="{{ $issue['employee']->id }}">
                                    <div class="font-medium text-gray-800 text-sm">{{ $issue['employee']->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $issue['employee']->jabatan->name ?? '-' }} | {{ $issue['employee']->golongan->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="permits[{{ $index }}][permit_date]" value="{{ $issue['date'] }}">
                                    <span class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($issue['date'])->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700">{{ $issue['check_in'] }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-700">{{ $issue['check_out'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($issue['late_minutes'] > 0)
                                    <span class="text-sm font-medium text-red-600">{{ $issue['late_minutes'] }} m</span>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($issue['early_leave_minutes'] > 0)
                                    <span class="text-sm font-medium text-orange-600">{{ $issue['early_leave_minutes'] }} m</span>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <select name="permits[{{ $index }}][category]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="terlambat" {{ $issue['late_minutes'] > 0 ? 'selected' : '' }}>Terlambat</option>
                                        <option value="pulang_awal" {{ $issue['early_leave_minutes'] > 0 ? 'selected' : '' }}>Pulang Awal</option>
                                    </select>
                                    <input type="hidden" name="permits[{{ $index }}][late_type]" value="masuk_kerja">
                                    <input type="hidden" name="permits[{{ $index }}][late_minutes]" value="{{ max($issue['late_minutes'], $issue['early_leave_minutes']) }}">
                                    <input type="hidden" name="permits[{{ $index }}][late_fine_amount]" value="0">
                                </td>
                                <td class="px-4 py-3">
                                    <select name="permits[{{ $index }}][deduction_type]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="salary_deduction">Potong Gaji</option>
                                        <option value="no_deduction">Izin (Tanpa Potong)</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        @else
        <div class="px-6 py-8 text-center">
            <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada data yang perlu diproses untuk rentang tanggal ini</p>
        </div>
        @endif
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('permits.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Izin</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    <option value="tidak_masuk" {{ request('category') == 'tidak_masuk' ? 'selected' : '' }}>Tidak Masuk</option>
                    <option value="terlambat" {{ request('category') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="pulang_awal" {{ request('category') == 'pulang_awal' ? 'selected' : '' }}>Pulang Lebih Awal</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar Izin -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-contract text-blue-600 mr-2"></i>
                Daftar Izin
            </h3>
            <a href="{{ route('permits.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Izin
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Durasi</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terlambat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Denda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($permits as $permit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $permit->employee?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $permit->permit_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            @if($permit->category)
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($permit->category === 'tidak_masuk') bg-red-100 text-red-800
                                @elseif($permit->category === 'terlambat') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $permit->category === 'tidak_masuk' ? 'Tidak Masuk' : ($permit->category === 'terlambat' ? 'Terlambat' : 'Pulang Awal') }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ substr($permit->start_time, 0, 5) }} - {{ substr($permit->end_time, 0, 5) }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $permit->duration_minutes }} mnt</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">
                            @if($permit->category === 'terlambat' && $permit->late_minutes)
                                {{ $permit->late_minutes }} mnt
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-red-600">
                            @if($permit->late_fine_amount && $permit->late_fine_amount > 0)
                                Rp {{ number_format($permit->late_fine_amount, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ $permit->reason }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($permit->status === 'approved') bg-green-100 text-green-800
                                @elseif($permit->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($permit->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                @if($permit->status === 'pending')
                                <form action="{{ route('permits.status', $permit) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Setujui"><i class="fas fa-check"></i></button>
                                </form>
                                <form action="{{ route('permits.status', $permit) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="Tolak"><i class="fas fa-times"></i></button>
                                </form>
                                @endif
                                <form action="{{ route('permits.destroy', $permit) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus izin ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <i class="fas fa-file-contract text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data izin</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $permits->withQueryString()->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.issue-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
        updateBulkButton();
    }

    function selectAllIssues() {
        const checkboxes = document.querySelectorAll('.issue-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
        });
        document.getElementById('selectAllCheckbox').checked = true;
        updateBulkButton();
    }

    function updateBulkButton() {
        const checked = document.querySelectorAll('.issue-checkbox:checked').length;
        const btn = document.getElementById('bulkProcessBtn');
        if (btn) {
            btn.disabled = checked === 0;
            btn.textContent = checked > 0 ? `Proses ${checked} Terpilih` : 'Proses Terpilih';
        }
    }

    function bulkProcessSelected() {
        const form = document.getElementById('bulkPermitForm');
        const checkboxes = document.querySelectorAll('.issue-checkbox:checked');

        if (checkboxes.length === 0) {
            alert('Pilih minimal satu data untuk diproses');
            return;
        }

        // Uncheck unchecked items' parent rows (hide inputs)
        const allRows = document.querySelectorAll('.issue-row');
        allRows.forEach(row => {
            const checkbox = row.querySelector('.issue-checkbox');
            const inputs = row.querySelectorAll('input[type="hidden"]');
            if (!checkbox.checked) {
                inputs.forEach(input => input.disabled = true);
            }
        });

        // Show confirmation
        const deductionTypes = [];
        document.querySelectorAll('.issue-checkbox:checked').forEach(cb => {
            const row = cb.closest('.issue-row');
            const deductionSelect = row.querySelector('select[name*="deduction_type"]');
            deductionTypes.push(deductionSelect.value);
        });

        const hasDeduction = deductionTypes.some(t => t === 'salary_deduction');
        const message = hasDeduction
            ? 'Beberapa izin akan memotong gaji. Lanjutkan?'
            : 'Semua izin tanpa potong gaji. Lanjutkan?';

        if (confirm(message)) {
            form.submit();
        } else {
            // Re-enable all inputs
            allRows.forEach(row => {
                row.querySelectorAll('input[type="hidden"]').forEach(input => input.disabled = false);
            });
        }
    }
</script>
@endpush
@endsection
