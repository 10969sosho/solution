@extends('layouts.app')

@section('title', 'Master Mesin Absensi - ADMS')
@section('page-title', 'Master Mesin Absensi')
@section('page-subtitle', 'Manajemen mesin fingerprint dan pemetaan lokasi cabang')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
        <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-fingerprint text-blue-600 mr-2"></i>
                    Daftar Mesin Absensi
                </h3>
                <p class="text-xs text-gray-500 mt-1">Setiap log kehadiran dari mesin akan otomatis terhubung ke lokasi yang ditentukan.</p>
            </div>
            <a href="{{ route('devices.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center text-sm font-medium">
                <i class="fas fa-plus mr-2"></i>Tambah Mesin
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama Mesin</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Serial Number (SN)</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lokasi Cabang</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                                <i class="fas fa-desktop"></i>
                            </span>
                            <div>
                                {{ $device->name }}
                                @if($device->notes)
                                <div class="text-xs text-gray-400 font-normal">{{ $device->notes }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-700 bg-gray-50/50 rounded">
                            {{ $device->serial_number }}
                        </td>
                        <td class="px-6 py-4">
                            @if($device->lokasi)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $device->lokasi->name }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Belum Terhubung
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                            {{ $device->ip_address ?: '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($device->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>Non-Aktif
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('devices.edit', $device) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <form action="{{ route('devices.destroy', $device) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus mesin ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium ml-2">
                                    <i class="fas fa-trash mr-1"></i>Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                            Belum ada mesin absensi yang didaftarkan. Klik tombol <strong>Tambah Mesin</strong> di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
