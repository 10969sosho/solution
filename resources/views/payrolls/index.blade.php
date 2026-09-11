@extends('layouts.app')

@section('title', 'Payroll & Penggajian')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Penggajian &amp; Slip Gaji</h1>
            <p class="text-sm text-gray-500">
                Formula Grand Total Fleksibel (Periode: {{ \Carbon\Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM Y') }})
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Bulan & Tahun -->
            <form method="GET" action="{{ route('payrolls.index') }}" class="flex items-center gap-2">
                <select name="month" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </form>

            <!-- Tombol Generate / Hitung Ulang -->
            <form action="{{ route('payrolls.generate') }}" method="POST" onsubmit="return confirm('Generate ulang seluruh payroll periode ini?')">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                    <i class="fas fa-sync-alt mr-1"></i> Hitung Ulang Payroll
                </button>
            </form>
        </div>
    </div>

    <!-- Rumus Banner (Briefing Section 14) -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 mb-6 text-xs text-blue-900 leading-relaxed font-mono">
        <strong>Rumus Grand Total:</strong> Gaji Pokok - Pot. Terlambat - Izin Potong Gaji + Izin Tambah Gaji - Pinjaman - Pot. Masuk + Bonus Libur + Uang Jaga Malam + Nominal Lembur
    </div>

    <!-- Tabel Penggajian Lengkap -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase">
                <tr>
                    <th class="px-3 py-3 text-left">Nama Karyawan</th>
                    <th class="px-2 py-3 text-center">Izin</th>
                    <th class="px-3 py-3 text-right">Gaji Pokok</th>
                    <th class="px-3 py-3 text-right">Pot. Telat</th>
                    <th class="px-2 py-3 text-right">Izin (+/-)</th>
                    <th class="px-2 py-3 text-center">Lembur</th>
                    <th class="px-3 py-3 text-right">Nom. Lembur</th>
                    <th class="px-3 py-3 text-right">Jaga Malam</th>
                    <th class="px-3 py-3 text-right">Bonus Libur</th>
                    <th class="px-3 py-3 text-right">Pot. Masuk</th>
                    <th class="px-4 py-3 text-center">Pot. Pinjaman (Manual)</th>
                    <th class="px-4 py-3 text-right font-bold text-gray-900">Grand Total</th>
                    <th class="px-2 py-3 text-center">Metode</th>
                    <th class="px-3 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payrolls as $payroll)
                    @php
                        $emp = $payroll->employee;
                        $isConfidential = $emp?->isConfidential();
                        $canSee = auth()->user()->isSuperAdmin() || !$isConfidential;
                    @endphp
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="px-3 py-3 font-medium text-gray-900">
                            <div>{{ $emp->name }}</div>
                            <div class="text-[10px] text-gray-500">
                                {{ $emp->position ?? $emp->department ?? 'Karyawan' }}
                                @if($isConfidential)
                                    <span class="ml-1 text-[9px] px-1.5 py-0.2 rounded bg-purple-100 text-purple-700 font-semibold">Khusus</span>
                                @endif
                            </div>
                        </td>

                        <!-- Total Izin -->
                        <td class="px-2 py-3 text-center">
                            {{ $payroll->total_izin_count }} hr
                        </td>

                        <!-- Gaji Pokok -->
                        <td class="px-3 py-3 text-right font-mono">
                            {{ $canSee ? 'Rp ' . number_format($payroll->base_salary, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Pot. Terlambat -->
                        <td class="px-3 py-3 text-right font-mono text-red-600">
                            {{ $canSee ? '-Rp ' . number_format($payroll->total_potongan_terlambat, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Izin (+ / -) -->
                        <td class="px-2 py-3 text-right font-mono">
                            @if($canSee)
                                @if($payroll->izin_tambah_gaji > 0)
                                    <span class="text-green-600 font-semibold">+{{ number_format($payroll->izin_tambah_gaji, 0, ',', '.') }}</span>
                                @elseif($payroll->izin_potong_gaji > 0)
                                    <span class="text-red-600 font-semibold">-{{ number_format($payroll->izin_potong_gaji, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            @else
                                ••••••
                            @endif
                        </td>

                        <!-- Lembur (menit) -->
                        <td class="px-2 py-3 text-center text-blue-600 font-semibold">
                            {{ $payroll->total_lembur_minutes }} mnt
                        </td>

                        <!-- Nominal Lembur -->
                        <td class="px-3 py-3 text-right font-mono text-blue-700">
                            {{ $canSee ? 'Rp ' . number_format($payroll->nominal_lembur, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Uang Jaga Malam -->
                        <td class="px-3 py-3 text-right font-mono text-indigo-700">
                            {{ $canSee ? 'Rp ' . number_format($payroll->uang_jaga_malam, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Bonus Libur -->
                        <td class="px-3 py-3 text-right font-mono text-emerald-700 font-bold">
                            {{ $canSee ? '+Rp ' . number_format($payroll->bonus_libur, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Pot. Masuk (Kelebihan Libur) -->
                        <td class="px-3 py-3 text-right font-mono text-amber-700">
                            {{ $canSee ? '-Rp ' . number_format($payroll->potongan_masuk, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Input Manual Potongan Pinjaman -->
                        <td class="px-4 py-2 text-center">
                            @if($payroll->status !== 'paid')
                                <form action="{{ route('payrolls.updateLoan', $payroll->id) }}" method="POST" class="flex items-center justify-center gap-1">
                                    @csrf
                                    <input type="number" name="pinjaman_deduction" value="{{ (int) $payroll->pinjaman_deduction }}" class="w-24 text-xs font-mono rounded border-gray-300 py-1 px-1.5 focus:ring-blue-500 focus:border-blue-500 text-right">
                                    <button type="submit" class="px-2 py-1 bg-gray-200 hover:bg-blue-600 hover:text-white rounded text-[10px] font-semibold transition" title="Simpan potongan pinjaman">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            @else
                                <span class="font-mono text-gray-700">-Rp {{ number_format($payroll->pinjaman_deduction, 0, ',', '.') }}</span>
                            @endif
                        </td>

                        <!-- Grand Total Gaji -->
                        <td class="px-4 py-3 text-right font-mono font-extrabold text-sm text-gray-900 bg-gray-50/80">
                            {{ $canSee ? 'Rp ' . number_format($payroll->grand_total, 0, ',', '.') : '••••••' }}
                        </td>

                        <!-- Metode Pembayaran -->
                        <td class="px-2 py-3 text-center">
                            @if(strtolower($payroll->payment_method) === 'cash')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">TUNAI</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">TRANSFER</span>
                            @endif
                        </td>

                        <!-- Action -->
                        <td class="px-3 py-3 text-center space-x-1 whitespace-nowrap">
                            <a href="{{ route('payrolls.show', $payroll->id) }}" class="inline-flex items-center px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-xs font-semibold transition">
                                <i class="fas fa-file-invoice mr-1"></i> Slip
                            </a>
                            @if($payroll->status !== 'paid' && auth()->user()->isSuperAdmin())
                                <form action="{{ route('payrolls.paid', $payroll->id) }}" method="POST" class="inline" onsubmit="return confirm('Tandai gaji ini sudah dibayarkan?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold transition" title="Tandai Paid">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-4 py-8 text-center text-gray-400">
                            Belum ada payroll untuk periode ini. Klik tombol <strong>Hitung Ulang Payroll</strong> di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
