@extends('layouts.app')

@section('title', 'Slip Gaji - ' . ($payroll->employee?->name ?? 'Karyawan'))

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <!-- Header Action Bar (Tidak Tercetak saat Print) -->
    <div class="flex items-center justify-between mb-6 print:hidden">
        <a href="{{ route('payrolls.index', ['year' => $payroll->period_year, 'month' => $payroll->period_month]) }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Payroll
        </a>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg shadow transition flex items-center">
                <i class="fas fa-print mr-2"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    @php
        $emp = $payroll->employee;
    @endphp

    <!-- Slip Gaji Kertas Putih -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 print:p-0 print:border-none print:shadow-none text-gray-800">
        <!-- Kop Surat -->
        <div class="border-b-2 border-gray-800 pb-4 mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-wide text-gray-900 uppercase">SLIP GAJI KARYAWAN</h1>
                <p class="text-sm font-semibold text-gray-600">
                    Periode: {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->locale('id')->isoFormat('MMMM Y') }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 text-xs font-bold uppercase rounded-md {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-gray-100 text-gray-700' }}">
                    STATUS: {{ strtoupper($payroll->status) }}
                </span>
                <p class="text-xs text-gray-500 mt-1">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Identitas Karyawan & Pembayaran -->
        <div class="grid grid-cols-2 gap-6 mb-8 text-xs">
            <div class="space-y-1.5">
                <div class="flex"><span class="w-32 text-gray-500">Nama Karyawan:</span><span class="font-bold text-gray-900">{{ $emp->name }}</span></div>
                <div class="flex"><span class="w-32 text-gray-500">ID Karyawan:</span><span class="font-mono font-medium">{{ $emp->employee_id }}</span></div>
                <div class="flex"><span class="w-32 text-gray-500">Jabatan / Golongan:</span><span>{{ $emp->position ?? '-' }} / {{ $emp->golongan?->name ?? '-' }}</span></div>
                <div class="flex"><span class="w-32 text-gray-500">Departemen:</span><span>{{ $emp->department ?? '-' }}</span></div>
            </div>
            <div class="space-y-1.5">
                <div class="flex">
                    <span class="w-36 text-gray-500">Metode Pembayaran:</span>
                    <span class="font-bold uppercase {{ strtolower($payroll->payment_method) === 'cash' ? 'text-amber-700' : 'text-blue-700' }}">
                        {{ $payroll->payment_method ?? $emp->payment_method ?? 'Transfer' }}
                    </span>
                </div>
                @if(strtolower($payroll->payment_method ?? $emp->payment_method) !== 'cash' && $emp->account_number)
                    <div class="flex"><span class="w-36 text-gray-500">Bank &amp; No. Rek:</span><span class="font-mono font-bold">{{ $emp->bank_name ?? 'Bank' }} - {{ $emp->account_number }}</span></div>
                    <div class="flex"><span class="w-36 text-gray-500">Atas Nama:</span><span>{{ $emp->account_holder ?? $emp->name }}</span></div>
                @endif
                <div class="flex"><span class="w-36 text-gray-500">Jatah Libur:</span><span>{{ $emp->getEffectiveLeaveQuota() }} hari/bln (Terpakai: {{ $payroll->total_hari_libur }} hr)</span></div>
            </div>
        </div>

        <!-- Rincian Penghasilan & Potongan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-xs">
            <!-- Kolom Penghasilan & Tambahan -->
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50">
                <h3 class="font-bold text-sm text-gray-900 border-b border-gray-200 pb-2 mb-3 text-emerald-800">
                    A. PENGHASILAN &amp; PENAMBAHAN
                </h3>
                <div class="space-y-2.5">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gaji Pokok:</span>
                        <span class="font-mono font-bold text-gray-900">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</span>
                    </div>
                    @if($payroll->izin_tambah_gaji > 0)
                        <div class="flex justify-between text-green-700 font-medium">
                            <span>Izin Tambah Gaji:</span>
                            <span class="font-mono">+Rp {{ number_format($payroll->izin_tambah_gaji, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($payroll->nominal_lembur > 0)
                        <div class="flex justify-between text-blue-700 font-medium">
                            <span>Nominal Lembur ({{ $payroll->total_lembur_minutes }} mnt):</span>
                            <span class="font-mono">+Rp {{ number_format($payroll->nominal_lembur, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($payroll->bonus_libur > 0)
                        <div class="flex justify-between text-emerald-700 font-bold">
                            <span>Bonus Libur (Sisa Jatah Libur):</span>
                            <span class="font-mono">+Rp {{ number_format($payroll->bonus_libur, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($payroll->uang_jaga_malam > 0)
                        <div class="flex justify-between text-indigo-700 font-medium">
                            <span>Uang Jaga Malam:</span>
                            <span class="font-mono">+Rp {{ number_format($payroll->uang_jaga_malam, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Pemotongan -->
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50">
                <h3 class="font-bold text-sm text-gray-900 border-b border-gray-200 pb-2 mb-3 text-red-800">
                    B. PEMOTONGAN GAJI
                </h3>
                <div class="space-y-2.5">
                    <div class="flex justify-between text-red-600">
                        <span>Potongan Keterlambatan:</span>
                        <span class="font-mono font-bold">-Rp {{ number_format($payroll->total_potongan_terlambat, 0, ',', '.') }}</span>
                    </div>
                    @if($payroll->izin_potong_gaji > 0)
                        <div class="flex justify-between text-red-600">
                            <span>Izin Potong Gaji:</span>
                            <span class="font-mono">-Rp {{ number_format($payroll->izin_potong_gaji, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($payroll->potongan_masuk > 0)
                        <div class="flex justify-between text-amber-700">
                            <span>Potongan Masuk (Libur > Kuota {{ $payroll->hari_potong_masuk }} hr):</span>
                            <span class="font-mono font-semibold">-Rp {{ number_format($payroll->potongan_masuk, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($payroll->pinjaman_deduction > 0)
                        <div class="flex justify-between text-gray-800 font-medium">
                            <span>Potongan Pinjaman / Kasbon:</span>
                            <span class="font-mono font-bold">-Rp {{ number_format($payroll->pinjaman_deduction, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Grand Total Bar -->
        <div class="bg-gray-900 text-white rounded-xl p-5 mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wider text-gray-400 font-bold">TOTAL GAJI BERSIH (GRAND TOTAL)</p>
                <p class="text-[10px] text-gray-400 font-mono mt-0.5">Rumus: Gaji Pokok - Denda - Izin - Pinjaman - Pot. Masuk + Bonus + Jaga Malam + Lembur</p>
            </div>
            <div class="text-right">
                <span class="text-2xl font-black font-mono tracking-tight text-emerald-400">
                    Rp {{ number_format($payroll->grand_total, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-2 gap-8 text-center text-xs mt-12 pt-6 border-t border-gray-200">
            <div>
                <p class="text-gray-500 mb-16">Penerima,</p>
                <p class="font-bold text-gray-900 uppercase">({{ $emp->name }})</p>
            </div>
            <div>
                <p class="text-gray-500 mb-16">Bagian Keuangan / HRD,</p>
                <p class="font-bold text-gray-900 uppercase">({{ auth()->user()->name ?? 'Finance' }})</p>
            </div>
        </div>
    </div>
</div>
@endsection
