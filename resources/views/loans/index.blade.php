@extends('layouts.app')

@section('title', 'Pinjaman / Kasbon - ADMS')
@section('page-title', 'Pinjaman / Kasbon')
@section('page-subtitle', 'Manajemen pinjaman karyawan')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('loans.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                </select>
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
                <i class="fas fa-hand-holding-usd text-blue-600 mr-2"></i>
                Daftar Pinjaman
            </h3>
            <div class="flex space-x-2">
                <a href="{{ route('loans.mutasi') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-exchange-alt mr-2"></i>Mutasi
                </a>
                <a href="{{ route('loans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tambah Pinjaman
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Pinjam</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Terbayar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sisa Bon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $loan->employee?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $loan->loan_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($loan->principal, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($loan->total_paid, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-800">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($loan->status === 'active') bg-yellow-100 text-yellow-800 @else bg-green-100 text-green-800 @endif">
                                {{ $loan->status === 'active' ? 'Belum Lunas' : 'Lunas' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('loans.show', $loan) }}" class="text-blue-600 hover:text-blue-800" title="Bayar / Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus pinjaman ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-hand-holding-usd text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Belum ada data pinjaman</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $loans->links() }}
        </div>
    </div>
</div>
@endsection