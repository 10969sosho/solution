@extends('layouts.app')

@section('title', 'Manajemen Izin - ADMS')
@section('page-title', 'Manajemen Izin')
@section('page-subtitle', 'Pencatatan izin karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('permits.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Izin</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    <option value="tidak_masuk" {{ request('category') == 'tidak_masuk' ? 'selected' : '' }}>Tidak Masuk</option>
                    <option value="terlambat" {{ request('category') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="pulang_awal" {{ request('category') == 'pulang_awal' ? 'selected' : '' }}>Pulang Lebih Awal</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-contract text-blue-600 mr-2"></i>
                Daftar Izin
            </h3>
            <a href="{{ route('permits.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tambah Izin
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Durasi</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terlambat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Denda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($permits as $permit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $permit->employee?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $permit->permit_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            @if($permit->category)
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($permit->category === 'tidak_masuk') bg-red-100 text-red-800
                                @elseif($permit->category === 'terlambat') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $permit->category === 'tidak_masuk' ? 'Tidak Masuk' : ($permit->category === 'terlambat' ? 'Terlambat' : 'Pulang Awal') }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ substr($permit->start_time, 0, 5) }} - {{ substr($permit->end_time, 0, 5) }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $permit->duration_minutes }} mnt</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-700">
                            @if($permit->category === 'terlambat' && $permit->late_minutes)
                                {{ $permit->late_minutes }} mnt
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-red-600">
                            @if($permit->late_fine_amount && $permit->late_fine_amount > 0)
                                Rp {{ number_format($permit->late_fine_amount, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">{{ $permit->reason }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($permit->status === 'approved') bg-green-100 text-green-800
                                @elseif($permit->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($permit->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                @if($permit->status === 'pending')
                                <form action="{{ route('permits.status', $permit) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Setujui"><i class="fas fa-check"></i></button>
                                </form>
                                <form action="{{ route('permits.status', $permit) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="Tolak"><i class="fas fa-times"></i></button>
                                </form>
                                @endif
                                <form action="{{ route('permits.destroy', $permit) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus izin ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <i class="fas fa-file-contract text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data izin</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $permits->links() }}
        </div>
    </div>
</div>
@endsection