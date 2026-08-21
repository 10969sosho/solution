@extends('layouts.app')

@section('title', 'Bayar Pinjaman - ADMS')
@section('page-title', 'Bayar Pinjaman')
@section('page-subtitle', 'Catat pembayaran pinjaman karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                Catat Pembayaran Pinjaman
            </h3>
        </div>

        <form method="POST" action="{{ route('loans.payments', $loan) }}" class="p-6 space-y-4">
            @csrf

            <input type="hidden" name="loan_id" value="{{ $loan->id }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" value="{{ $loan->employee?->name }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                    <input type="text" name="position" value="{{ $loan->employee?->position }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        readonly>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <input type="text" name="location" value="{{ $loan->employee?->location }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sisa Pinjaman Sebelumnya</label>
                    <input type="number" name="previous_balance" value="{{ $loan->remaining_balance }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        readonly>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bayar</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('payment_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Bayar</label>
                <input type="number" name="amount" required step="0.01" min="0.01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sisa Pinjaman Akhir</label>
                <input type="number" name="remaining_after" value=""
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    readonly>
                <small class="text-xs text-gray-500">Dihitung otomatis setelah pembayaran</small>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Catat Bayar
                </button>
                <a href="{{ route('loans.show', $loan) }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.querySelector('input[name="amount"]');
    const remainingAfter = document.querySelector('input[name="remaining_after"]');
    const previousBalance = {{ $loan->remaining_balance }};

    if (amountInput && remainingAfter) {
        amountInput.addEventListener('input', function() {
            const paid = parseFloat(this.value) || 0;
            const sisa = Math.max(0, previousBalance - paid);
            remainingAfter.value = sisa;
        });
    }
});
</script>
@endpush