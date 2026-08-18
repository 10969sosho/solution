@extends('layouts.app')

@section('title', 'Detail Pinjaman - ADMS')
@section('page-title', 'Detail Pinjaman')
@section('page-subtitle', 'Histori pembayaran pinjaman')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Info Pinjaman -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 mb-1">Karyawan</p>
            <p class="text-lg font-bold text-gray-800">{{ $loan->employee?->name }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $loan->employee?->employee_id }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500 mb-1">Nominal Pinjaman</p>
            <p class="text-lg font-bold text-gray-800">Rp {{ number_format($loan->principal, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $loan->loan_date->format('d M Y') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 mb-1">Total Terbayar</p>
            <p class="text-lg font-bold text-green-600">Rp {{ number_format($loan->total_paid, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 mb-1">Sisa Bon</p>
            <p class="text-lg font-bold text-red-600">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $loan->status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</p>
        </div>
    </div>

    <!-- Form Pembayaran -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Catat Pembayaran
        </h3>
        <form method="POST" action="{{ route('loans.payments', $loan) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bayar</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal</label>
                <input type="number" name="amount" required step="0.01" min="0.01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Opsional">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Bayar
                </button>
            </div>
        </form>
    </div>

    <!-- Histori Pembayaran -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-history text-blue-600 mr-2"></i>
                Histori Pembayaran
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($loan->payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $payment->payment_date->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $payment->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <p class="text-gray-500">Belum ada pembayaran</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection