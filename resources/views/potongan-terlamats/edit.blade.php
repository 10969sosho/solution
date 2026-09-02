@extends('layouts.app')

@section('title', 'Edit Potongan Terlambat - ADMS')
@section('page-title', 'Edit Potongan Terlambat')
@section('page-subtitle', 'Ubah data potongan denda keterlambatan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clock text-blue-600 mr-2"></i>
                Ubah Potongan Terlambat
            </h3>
        </div>

        <form action="{{ route('potongan-terlamats.update', $potongan) }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Golongan <span class="text-red-500">*</span></label>
                <select name="golongan_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Pilih Golongan --</option>
                    <option value="gudang_kandang" {{ old('golongan_type', $potongan->golongan_type) == 'gudang_kandang' ? 'selected' : '' }}>Gudang & Kandang</option>
                    <option value="mandor_admin" {{ old('golongan_type', $potongan->golongan_type) == 'mandor_admin' ? 'selected' : '' }}>Mandor & Admin</option>
                </select>
                @error('golongan_type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Menit Minimal <span class="text-red-500">*</span></label>
                    <input type="number" name="min_minutes" value="{{ old('min_minutes', $potongan->min_minutes) }}" required min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Contoh: 0">
                    @error('min_minutes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Menit Maksimal</label>
                    <input type="number" name="max_minutes" value="{{ old('max_minutes', $potongan->max_minutes) }}" min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Kosongkan jika tidak terbatas">
                    @error('max_minutes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Denda (Rp) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" value="{{ old('amount', $potongan->amount) }}" required min="0" step="1000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: 10000">
                @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('potongan-terlamats.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
