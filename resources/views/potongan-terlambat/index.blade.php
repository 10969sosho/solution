@extends('layouts.app')

@section('title', 'Master Potongan Terlambat - ADMS')
@section('page-title', 'Master Potongan Terlambat')
@section('page-subtitle', 'Kelola potongan denda keterlambatan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-clock text-blue-600 mr-2"></i>
                Daftar Potongan Terlambat
            </h3>
            <a href="{{ route('potongan-terlambat.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Potongan
            </a>
        </div>

        <div class="p-6">
            @forelse($grouped as $golonganId => $items)
            <div class="mb-6 last:mb-0">
                <h4 class="text-md font-semibold text-gray-700 mb-3">
                    <i class="fas fa-users text-gray-500 mr-2"></i>{{ $items->first()->golongan->name ?? '-' }}
                </h4>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Range Waktu</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Denda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($items as $potongan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        @if($potongan->type === 'masuk_kerja') bg-blue-100 text-blue-800
                                        @else bg-green-100 text-green-800 @endif">
                                        {{ $potongan->type_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $potongan->range_label }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">
                                    @if($potongan->amount > 0)
                                        Rp {{ number_format($potongan->amount, 0, ',', '.') }}
                                    @else
                                        <span class="text-green-600">Tidak ada potongan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('potongan-terlambat.edit', $potongan) }}" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('potongan-terlambat.destroy', $potongan) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus potongan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center">
                                    <p class="text-gray-500">Belum ada data potongan</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <i class="fas fa-clock text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Belum ada data potongan terlambat</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
