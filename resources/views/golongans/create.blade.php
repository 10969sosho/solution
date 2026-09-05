@extends('layouts.app')

@section('title', 'Tambah Golongan - ADMS')
@section('page-title', 'Tambah Golongan')
@section('page-subtitle', 'Buat golongan baru untuk karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-layer-group text-blue-600 mr-2"></i>
                Form Golongan
            </h3>
        </div>

        <form action="{{ route('golongans.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Golongan <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Admin, Mandor, Anak Gudang">
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Golongan untuk staff admin kantor, jam kerja 08:00-17:00">{{ old('description') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Keterangan tentang golongan ini (jam kerja, aturan, dll)</p>
                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Penting:</strong> Setelah membuat golongan, jangan lupa untuk mengatur <strong>Setting Jam Kerja</strong> sesuai golongan ini agar sistem dapat mendeteksi keterlambatan.
                </p>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('golongans.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
