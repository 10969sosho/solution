@extends('layouts.app')

@section('title', 'Tambah Gaji - ADMS')
@section('page-title', 'Tambah Gaji')
@section('page-subtitle', 'Data gaji pokok karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i>
                Form Gaji
            </h3>
        </div>

        <form action="{{ route('gajis.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Gaji <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Gaji Pokok 2 Juta">
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal <span class="text-red-500">*</span></label>
                <input type="number" name="amount" required step="0.01" min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: 2000000">
                @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('gajis.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
