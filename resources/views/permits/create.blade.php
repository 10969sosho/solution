@extends('layouts.app')

@section('title', 'Tambah Izin - ADMS')
@section('page-title', 'Tambah Izin')
@section('page-subtitle', 'Catat izin karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-contract text-blue-600 mr-2"></i>
                Form Izin
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Izin < 15 menit = <span class="text-green-600 font-medium">Tanpa Potongan</span>
                | Izin > 30 menit = <span class="text-red-600 font-medium">Potong Gaji</span> (proporsional)
            </p>
        </div>

        <form action="{{ route('permits.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" data-location="{{ $emp->location }}" data-position="{{ $emp->position }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->employee_id }} - {{ $emp->name }}
                    </option>
                    @endforeach
                </select>
                @error('employee_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Izin <span class="text-red-500">*</span></label>
                <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus-border-transparent">
                    <option value="">-- Pilih Tipe Izin --</option>
                    <option value="tidak_masuk" {{ old('category') == 'tidak_masuk' ? 'selected' : '' }}>Tidak Masuk</option>
                    <option value="terlambat" {{ old('category') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="pulang_awal" {{ old('category') == 'pulang_awal' ? 'selected' : '' }}>Pulang Lebih Awal</option>
                </select>
                @error('category') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi <span class="text-red-500">*</span></label>
                    <input type="text" name="location" value="{{ old('location') }}"
                        readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Otomatis dari nama karyawan">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan <span class="text-red-500">*</span></label>
                    <input type="text" name="position" value="{{ old('position') }}"
                        readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Otomatis dari nama karyawan">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Izin <span class="text-red-500">*</span></label>
                <input type="date" name="permit_date" value="{{ old('permit_date') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('permit_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="reason" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Sakit, keluarga, dll">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Potongan</label>
                    <div class="space-y-1">
                        <label class="flex items-center px-3 py-1 rounded border cursor-pointer hover:border-blue-500">
                            <input type="radio" name="deduction_type" value="no_deduction" {{ old('deduction_type') == 'no_deduction' ? 'checked' : '' }}>
                            <span>Tanpa Potongan</span>
                        </label>
                        <label class="flex items-center px-3 py-1 rounded border cursor-pointer hover:border-blue-500">
                            <input type="radio" name="deduction_type" value="salary_deduction" {{ old('deduction_type') == 'salary_deduction' ? 'checked' : '' }}>
                            <span>Potong Gaji</span>
                        </label>
                    </div>
                </div>
                <div id="duration-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durasi Potongan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="deduction_hours" value="{{ old('deduction_hours', 0) }}" min="0"
                            class="w-full px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span>Jam</span>
                        <input type="number" name="deduction_minutes" value="{{ old('deduction_minutes', 0) }}" min="0"
                            class="w-full px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span>Menit</span>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('permits.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const employeeSelect = document.querySelector('select[name="employee_id"]');
        const durationField = document.getElementById('duration-field');
        
        if (employeeSelect) {
            employeeSelect.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const dataLocation = option.getAttribute('data-location');
                const dataPosition = option.getAttribute('data-position');
                
                if (dataLocation) {
                    document.querySelector('input[name="location"]').value = dataLocation;
                }
                if (dataPosition) {
                    document.querySelector('input[name="position"]').value = dataPosition;
                }
                
                const deductionType = document.querySelector('input[name="deduction_type"]:checked');
                if (deductionType && deductionType.value === 'salary_deduction') {
                    durationField.classList.remove('hidden');
                } else {
                    durationField.classList.add('hidden');
                }
            });
        }
        
        const initialDeduction = document.querySelector('input[name="deduction_type"]:checked');
        if (initialDeduction && initialDeduction.value === 'salary_deduction') {
            durationField.classList.remove('hidden');
        }
    });
</script>
@endsection
