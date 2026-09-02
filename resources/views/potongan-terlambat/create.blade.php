@extends('layouts.app')

@section('title', 'Tambah Potongan Terlambat - ADMS')
@section('page-title', 'Tambah Potongan Terlambat')
@section('page-subtitle', 'Tambah data potongan denda keterlambatan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clock text-blue-600 mr-2"></i>
                Form Potongan Terlambat
            </h3>
        </div>

        <form action="{{ route('potongan-terlambat.store') }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Golongan <span class="text-red-500">*</span></label>
                <select name="golongan_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Pilih Golongan --</option>
                    @foreach($golongans as $golongan)
                    <option value="{{ $golongan->id }}" {{ old('golongan_id') == $golongan->id ? 'selected' : '' }}>
                        {{ $golongan->name }}
                    </option>
                    @endforeach
                </select>
                @error('golongan_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="masuk_kerja" {{ old('type') == 'masuk_kerja' ? 'selected' : '' }}>Masuk Kerja</option>
                    <option value="setelah_istirahat" {{ old('type') == 'setelah_istirahat' ? 'selected' : '' }}>Setelah Istirahat</option>
                </select>
                @error('type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Menit Minimal <span class="text-red-500">*</span></label>
                    <input type="number" name="min_minutes" value="{{ old('min_minutes') }}" required min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Contoh: 0">
                    @error('min_minutes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Menit Maksimal</label>
                    <input type="number" name="max_minutes" value="{{ old('max_minutes') }}" min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Kosongkan jika tidak terbatas">
                    @error('max_minutes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Denda (Rp) <span class="text-red-500">*</span></label>
                <input type="text" id="amount" name="amount" value="{{ number_format(old('amount', 0), 0, ',', '.') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: 10.000">
                <input type="hidden" name="amount" id="amount_hidden" value="{{ old('amount', 0) }}">
                @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
                <a href="{{ route('potongan-terlambat.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const amountInput = document.getElementById('amount');
    const amountHidden = document.getElementById('amount_hidden');

    amountInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if (value) {
            value = parseInt(value);
            e.target.value = value.toLocaleString('id-ID');
            amountHidden.value = value;
        } else {
            e.target.value = '';
            amountHidden.value = 0;
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        let value = amountInput.value.replace(/[^0-9]/g, '');
        amountHidden.value = value || 0;
    });
</script>
@endpush
