@extends('layouts.app')

@section('title', 'Rekap Bulanan Absensi & Kompensasi')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rekap Bulanan Absensi &amp; Kompensasi</h1>
            <p class="text-sm text-gray-500">
                Akumulasi keterlambatan sebulan, perhitungan libur, lembur, dan bonusan (Periode: {{ \Carbon\Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM Y') }})
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('attendance.monthly-recap') }}" class="flex items-center gap-2">
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

            @if(auth()->user()?->isSuperAdmin())
                <form action="{{ route('payrolls.generate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-sm transition flex items-center {{ !$allFixed ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$allFixed ? 'disabled title="Semua tanggal harus berstatus FIX terlebih dahulu"' : '' }}>
                        <i class="fas fa-money-check-alt mr-2"></i> Tarik ke Penggajian
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Banner Validasi Status FIX -->
    @if(!$allFixed)
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-xl flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-amber-500 text-xl mr-3"></i>
                <div>
                    <h4 class="text-sm font-bold text-amber-900">Validasi Kompilasi Harian Belum Lengkap</h4>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Baru {{ $fixedDatesCount }} dari {{ $totalDays }} hari yang berstatus <strong>FIX</strong>. Seluruh tanggal harus Fix sebelum data dapat ditarik ke Penggajian.
                    </p>
                </div>
            </div>
            <a href="{{ route('attendance.compilation', ['year' => $year, 'month' => $month]) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                Lihat List Kompilasi <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    @else
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r-xl flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-emerald-500 text-xl mr-3"></i>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900">Kompilasi Harian Lengkap &amp; Tervalidasi</h4>
                    <p class="text-xs text-emerald-800 mt-0.5">
                        Seluruh tanggal dalam periode ini telah berstatus <strong>FIX</strong>. Data siap digunakan untuk perhitungan penggajian bulanan.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabel Rekap Bulanan -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-100 text-gray-700 font-semibold uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Karyawan</th>
                    <th class="px-3 py-3 text-center">Telat Masuk</th>
                    <th class="px-3 py-3 text-center">Telat Istirahat</th>
                    <th class="px-3 py-3 text-right">Pot. Telat Masuk</th>
                    <th class="px-3 py-3 text-right">Pot. Telat Istirahat</th>
                    <th class="px-3 py-3 text-right">Nominal Izin</th>
                    <th class="px-3 py-3 text-center">Total Libur</th>
                    <th class="px-3 py-3 text-center">Hari Pot. Masuk</th>
                    <th class="px-3 py-3 text-right">Nominal Pot. Masuk</th>
                    <th class="px-3 py-3 text-center">Total Lembur</th>
                    <th class="px-3 py-3 text-right">Nominal Lembur</th>
                    <th class="px-3 py-3 text-right">Bonus Libur</th>
                    <th class="px-3 py-3 text-right">Uang Jaga Malam</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recap as $item)
                    @php $emp = $item['employee']; @endphp
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <div>{{ $emp->name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $emp->position ?? 'Karyawan' }} (Jatah: {{ $item['leave_quota'] }} hr)</div>
                        </td>

                        <!-- Telat Masuk (Total Menit) -->
                        <td class="px-3 py-3 text-center font-semibold {{ $item['total_late_in_minutes'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['total_late_in_minutes'] }} mnt
                        </td>

                        <!-- Telat Istirahat (Total Menit) -->
                        <td class="px-3 py-3 text-center font-semibold {{ $item['total_late_break_in_minutes'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['total_late_break_in_minutes'] }} mnt
                        </td>

                        <!-- Nominal Potongan Masuk -->
                        <td class="px-3 py-3 text-right font-mono text-gray-700">
                            Rp {{ number_format($item['nominal_potongan_masuk_kerja'], 0, ',', '.') }}
                        </td>

                        <!-- Nominal Potongan Istirahat -->
                        <td class="px-3 py-3 text-right font-mono text-gray-700">
                            Rp {{ number_format($item['nominal_potongan_setelah_istirahat'], 0, ',', '.') }}
                        </td>

                        <!-- Nominal Izin (+ / -) -->
                        <td class="px-3 py-3 text-right font-mono">
                            @if($item['nominal_izin_tambah'] > 0)
                                <span class="text-green-600 font-bold">+Rp {{ number_format($item['nominal_izin_tambah'], 0, ',', '.') }}</span>
                            @elseif($item['nominal_izin_potong'] > 0)
                                <span class="text-red-600 font-bold">-Rp {{ number_format($item['nominal_izin_potong'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <!-- Total Hari Libur -->
                        <td class="px-3 py-3 text-center font-bold text-gray-800">
                            {{ $item['total_libur_days'] }} hr
                        </td>

                        <!-- Hari Potong Masuk -->
                        <td class="px-3 py-3 text-center font-bold {{ $item['hari_potong_masuk'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['hari_potong_masuk'] }} hr
                        </td>

                        <!-- Nominal Potong Masuk -->
                        <td class="px-3 py-3 text-right font-mono {{ $item['nominal_potong_masuk'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                            Rp {{ number_format($item['nominal_potong_masuk'], 0, ',', '.') }}
                        </td>

                        <!-- Total Lembur (menit) -->
                        <td class="px-3 py-3 text-center font-semibold text-blue-600">
                            {{ $item['total_overtime_minutes'] }} mnt
                        </td>

                        <!-- Nominal Lembur -->
                        <td class="px-3 py-3 text-right font-mono text-blue-700 font-semibold">
                            Rp {{ number_format($item['nominal_lembur'], 0, ',', '.') }}
                        </td>

                        <!-- Bonus Libur -->
                        <td class="px-3 py-3 text-right font-mono text-emerald-700 font-bold">
                            Rp {{ number_format($item['bonus_libur'], 0, ',', '.') }}
                        </td>

                        <!-- Uang Jaga Malam -->
                        <td class="px-3 py-3 text-right font-mono text-indigo-700 font-semibold">
                            Rp {{ number_format($item['uang_jaga_malam'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="px-4 py-8 text-center text-gray-400">Tidak ada data rekap untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
