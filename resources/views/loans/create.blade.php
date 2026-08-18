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
                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->employee_id }} - {{ $emp->name }}
                    </option>
                    @endforeach
                </select>
                @error('employee_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pinjam <span class="text-red-500">*</span></label>
                    <input type="date" name="loan_date" value="{{ old('loan_date') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Pinjaman <span class="text-red-500">*</span></label>
                    <input type="number" name="principal" value="{{ old('principal') }}" required step="0.01" min="0.01"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Contoh: 1000000">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <input type="text" name="description" value="{{ old('description') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
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
@endsection