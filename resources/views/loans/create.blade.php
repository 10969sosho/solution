@extends('layouts.app')

@section('title', 'Tambah Pinjaman - ADMS')
@section('page-title', 'Tambah Pinjaman')
@section('page-subtitle', 'Catat pinjaman / kasbon karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-hand-holding-usd text-blue-600 mr-2"></i>
                Form Pinjaman
            </h3>
        </div>

        <form action="{{ route('loans.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employeesWithTotals as $empData)
                    <option value="{{ $empData->employee->id }}" 
                        data-location="{{ $empData->employee->location }}" 
                        data-position="{{ $empData->employee->position }}"
                        data-previous="{{ $empData->previous_loans_total }}"
                        data-all="{{ $empData->all_loans_total }}"
                        {{ old('employee_id') == $empData->employee->id ? 'selected' : '' }}>
                        {{ $empData->employee->employee_id }} - {{ $empData->employee->name }}
                    </option>
                    @endforeach
                </select>
                @error('employee_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <input type="text" name="location" value="{{ old('location') }}"
                        readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Otomatis dari nama karyawan">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                    <input type="text" name="position" value="{{ old('position') }}"
                        readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Otomatis dari nama karyawan">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Pinjaman Yang Lalu</label>
                <input type="number" name="previous_loans_total" value="{{ old('previous_loans_total', $employeesWithTotals->first()?->previous_loans_total ?? 0) }}"
                    readonly class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-100 focus:ring-0"
                    placeholder="Total pinjaman sebelumnya karyawan ini">
                @error('previous_loans_total') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Semua Pinjaman</label>
                <input type="number" name="all_loans_total" value="{{ old('all_loans_total', $employeesWithTotals->first()?->all_loans_total ?? 0) }}"
                    readonly class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-100 focus:ring-0"
                    placeholder="Total pinjaman di sistem">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam <span class="text-red-500">*</span></label>
                    <input type="date" name="loan_date" value="{{ old('loan_date') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Pinjaman <span class="text-red-500">*</span></label>
                    <input type="number" name="principal" value="{{ old('principal') }}" required step="0.01" min="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Contoh: 1000000">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <input type="text" name="description" value="{{ old('description') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Kasbon kebutuhan keluarga">
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('loans.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const employeeSelect = document.querySelector('select[name="employee_id"]');
        
        if (employeeSelect) {
            employeeSelect.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const dataLocation = option.getAttribute('data-location');
                const dataPosition = option.getAttribute('data-position');
                const dataPrevious = option.getAttribute('data-previous');
                const dataAll = option.getAttribute('data-all');
                
                if (dataLocation) {
                    document.querySelector('input[name="location"]').value = dataLocation;
                }
                if (dataPosition) {
                    document.querySelector('input[name="position"]').value = dataPosition;
                }
                if (dataPrevious !== null) {
                    document.querySelector('input[name="previous_loans_total"]').value = dataPrevious;
                }
                if (dataAll !== null) {
                    document.querySelector('input[name="all_loans_total"]').value = dataAll;
                }
            });
            
            // Trigger change on load to set initial values
            employeeSelect.dispatchEvent(new Event('change'));
        }
    });
</script>