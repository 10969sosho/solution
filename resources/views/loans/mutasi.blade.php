@extends('layouts.app')

@section('title', 'Mutasi Pinjaman - ADMS')
@section('page-title', 'Mutasi Pinjaman')
@section('page-subtitle', 'Laporan mutasi pinjaman per karyawan')

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('loans.mutasi') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->employee_id }}" {{ $employeeId == $emp->employee_id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
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
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-exchange-alt text-blue-600 mr-2"></i>
                Mutasi Pinjaman
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Pinjam</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Histori Bayar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Bayar</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sisa Bon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($mutasi as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">
                            {{ $row['name'] }}
                            <div class="text-xs text-gray-500">ID: {{ $row['employee_id'] }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $row['loan_date'] }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($row['principal'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @forelse($row['payments'] as $p)
                            <div class="text-xs text-gray-600">{{ $p->payment_date->format('d M Y') }}: Rp {{ number_format($p->amount, 0, ',', '.') }}</div>
                            @empty
                            <span class="text-xs text-gray-400">Belum ada bayar</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($row['total_paid'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-800">Rp {{ number_format($row['remaining'], 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($row['status'] === 'active') bg-yellow-100 text-yellow-800 @else bg-green-100 text-green-800 @endif">
                                {{ $row['status'] === 'active' ? 'Belum Lunas' : 'Lunas' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-exchange-alt text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Tidak ada data mutasi pinjaman</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection