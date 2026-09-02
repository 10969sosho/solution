<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ADMS Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white flex flex-col shadow-xl">
            <!-- Logo -->
            <div class="p-6 border-b border-blue-700">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-fingerprint text-blue-900 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">ADMS</h1>
                        <p class="text-xs text-blue-200">Attendance System</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-2" x-data="{
                openGroups: {
                    master: @js(request()->is(['employees*', 'golongans*', 'jabatans*', 'lokasis*'])),
                    attendance: @js(request()->is(['schedules*', 'seasonal*', 'settings*'])),
                    reports: @js(request()->is('reports*')),
                    hr: @js(request()->is(['permits*', 'loans*', 'payrolls*']))
                },
                toggleGroup(group) {
                    this.openGroups[group] = !this.openGroups[group];
                }
            }">
                <a href="/" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('/') ? 'bg-blue-700 shadow-lg' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <div>
                    <button type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors" @click="toggleGroup('master')" :aria-expanded="openGroups.master">
                        <span class="flex items-center"><i class="fas fa-database w-5"></i><span class="ml-3">Master Data</span></span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': openGroups.master }"></i>
                    </button>
                    <div x-cloak x-show="openGroups.master" x-transition class="mt-1 space-y-1">
                        <a href="/employees" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('employees*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-users w-5"></i><span class="ml-3">Karyawan</span>
                        </a>
                        <a href="/golongans" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('golongans*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-layer-group w-5"></i><span class="ml-3">Golongan</span>
                        </a>
                        <a href="/jabatans" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('jabatans*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-briefcase w-5"></i><span class="ml-3">Jabatan</span>
                        </a>
                        <a href="/lokasis" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('lokasis*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-map-marker-alt w-5"></i><span class="ml-3">Lokasi</span>
                        </a>
                        <a href="/gajis" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('gajis*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-money-bill-wave w-5"></i><span class="ml-3">Gaji</span>
                        </a>
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors" @click="toggleGroup('attendance')" :aria-expanded="openGroups.attendance">
                        <span class="flex items-center"><i class="fas fa-calendar-check w-5"></i><span class="ml-3">Absensi &amp; Jadwal</span></span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': openGroups.attendance }"></i>
                    </button>
                    <div x-cloak x-show="openGroups.attendance" x-transition class="mt-1 space-y-1">
                        <a href="/schedules" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('schedules*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-clock w-5"></i><span class="ml-3">Jam Kerja Khusus</span>
                        </a>
                        <a href="/seasonal" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('seasonal*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-calendar-alt w-5"></i><span class="ml-3">Jam Kerja Musiman</span>
                        </a>
                        <a href="/settings" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('settings*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-cog w-5"></i><span class="ml-3">Setting Jam Kerja</span>
                        </a>
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors" @click="toggleGroup('reports')" :aria-expanded="openGroups.reports">
                        <span class="flex items-center"><i class="fas fa-chart-pie w-5"></i><span class="ml-3">Laporan</span></span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': openGroups.reports }"></i>
                    </button>
                    <div x-cloak x-show="openGroups.reports" x-transition class="mt-1 space-y-1">
                        <a href="/reports/monthly" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('reports/monthly*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-chart-bar w-5"></i><span class="ml-3">Laporan Bulanan</span>
                        </a>
                        <a href="/loans/laporan" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('loans/laporan*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-file-invoice-dollar w-5"></i><span class="ml-3">Laporan Pinjaman</span>
                        </a>
                        <a href="/reports/summary" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('reports/summary*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-file-alt w-5"></i><span class="ml-3">Rekap Bulanan</span>
                        </a>
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors" @click="toggleGroup('hr')" :aria-expanded="openGroups.hr">
                        <span class="flex items-center"><i class="fas fa-briefcase w-5"></i><span class="ml-3">HR &amp; Keuangan</span></span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': openGroups.hr }"></i>
                    </button>
                    <div x-cloak x-show="openGroups.hr" x-transition class="mt-1 space-y-1">
                        <a href="/permits" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('permits*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-file-contract w-5"></i><span class="ml-3">Manajemen Izin</span>
                        </a>
                        <a href="/loans" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('loans') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-hand-holding-usd w-5"></i><span class="ml-3">Pinjaman / Kasbon</span>
                        </a>
                        @if(auth()->user()?->canManagePayroll())
                        <a href="/payrolls" class="flex items-center px-4 py-2.5 pl-12 rounded-lg hover:bg-blue-700 transition-colors {{ request()->is('payrolls*') ? 'bg-blue-700 shadow-lg' : '' }}">
                            <i class="fas fa-money-check-alt w-5"></i><span class="ml-3">Payroll</span>
                        </a>
                        @endif
                    </div>
                </div>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-blue-700">
                <div class="flex items-center space-x-3 px-4 py-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-blue-200">
                            {{ auth()->user()?->isAdminOperasional() ? 'Admin Operasional' : 'Super Admin' }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="px-4">
                    @csrf
                    <button type="submit" class="w-full mt-2 flex items-center px-4 py-2 bg-blue-700/50 text-blue-100 rounded-lg hover:bg-red-600 transition-colors text-sm">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="ml-3">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-sm text-gray-500">@yield('page-subtitle', 'Monitoring real-time')</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></span>
                            <span>Live</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-800">{{ now()->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ now()->format('H:i:s') }} WIB</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
