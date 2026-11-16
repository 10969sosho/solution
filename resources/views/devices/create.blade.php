@extends('layouts.app')

@section('title', 'Tambah Mesin Absensi - ADMS')
@section('page-title', 'Tambah Mesin Absensi')
@section('page-subtitle', 'Daftarkan mesin fingerprint baru dan hubungkan ke lokasi')

@section('content')
<div class="max-w-2xl bg-white rounded-xl shadow-sm p-6 space-y-6">
    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('devices.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mesin <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Mesin 1 - Bendo" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number (SN) <span class="text-red-500">*</span></label>
            <input type="text" name="serial_number" value="{{ old('serial_number') }}" placeholder="Contoh: NYU7250300070 (cek menu ADMS mesin)" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
            <p class="text-xs text-gray-500 mt-1">Nomor seri fisik mesin ADMS (contoh: NYU7250300070) agar log scan otomatis terhubung ke nama mesin kustom di atas.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Cabang</label>
            <select name="lokasi_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="">-- Pilih Lokasi (Opsional) --</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ $lokasi->id }}" {{ old('lokasi_id') == $lokasi->id ? 'selected' : '' }}>
                        {{ $lokasi->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Karyawan yang absen di mesin ini otomatis terdata hadir di lokasi ini.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">IP Address Mesin (Opsional)</label>
            <input type="text" name="ip_address" value="{{ old('ip_address') }}" placeholder="Contoh: 192.168.1.201"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="is_active" class="ml-2 block text-sm text-gray-900 font-medium">Mesin Aktif (Menerima log absensi)</label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
            <textarea name="notes" rows="2" placeholder="Catatan letak posisi fisik mesin..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
            <a href="{{ route('devices.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
                Batal
            </a>
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                Simpan Mesin
            </button>
        </div>
    </form>
</div>
@endsection
