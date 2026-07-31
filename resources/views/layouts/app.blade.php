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
            <nav class="flex-1 p-4 space-y-2">
                <a href="/" class="flex items-center px-4 py-3 rounded-lg bg-blue-700 text-white shadow-lg">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="/attendance/latest" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-table w-5"></i>
                    <span class="ml-3">Data Absensi</span>
                </a>
                <a href="/machines" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-server w-5"></i>
                    <span class="ml-3">Mesin</span>
                </a>
                <a href="/users" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Karyawan</span>
                </a>
                <a href="/reports" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Laporan</span>
                </a>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-blue-700">
                <div class="flex items-center space-x-3 px-4 py-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">Admin</p>
                        <p class="text-xs text-blue-200">Administrator</p>
                    </div>
                </div>
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
