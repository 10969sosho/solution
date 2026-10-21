@extends('layouts.app')

@section('title', 'Potong Pinjaman - ADMS')
@section('page-title', 'Potong Pinjaman')
@section('page-subtitle', 'Catat pembayaran pinjaman yang dipotong melalui gaji')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800">
        <i class="fas fa-info-circle mr-2"></i>
        Nominal yang dicatat di sini akan otomatis menjadi potongan <strong>Cicilan Pinjaman / Bon</strong> pada Payroll
        sesuai tanggal potong. Tidak perlu lagi mengisi potongan pinjaman manual di baris Payroll.
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="GET" action="{{ route('loans.potong') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua --</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
            <div class="flex items-end justify-end">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-scissors text-orange-500 mr-2"></i>Pinjaman Aktif
            </h3>
            <p class="text-sm text-gray-500 mt-1">Isi nominal potong, lalu simpan. Bisa dicicil lebih dari sekali dalam bulan yang sama.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Pinjam</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sisa Bon</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Potong</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal Potong</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-800">{{ $loan->employee?->name }}</div>
                            <div class="text-xs text-gray-500">{{ $loan->employee?->employee_id }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $loan->loan_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-800">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</td>
                        <td colspan="4" class="px-4 py-3">
                            <form action="{{ route('loans.payments', $loan) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                                @csrf
                                <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="amount" min="1" max="{{ (float) $loan->remaining_balance }}" step="1" required
                                    placeholder="Nominal" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <input type="text" name="notes" placeholder="Catatan (opsional)"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium">
                                    <i class="fas fa-save mr-1"></i>Simpan Potong
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
                            <p class="text-gray-500">Tidak ada pinjaman aktif</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
