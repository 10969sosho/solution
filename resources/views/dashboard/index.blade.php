@extends('layouts.app')

@section('title', 'Dashboard - ADMS')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Monitoring kehadiran karyawan real-time')

@section('content')
<div x-data="dashboard()" x-init="init()" class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Hari Ini -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Absensi Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.total_today">0</p>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fas fa-calendar-day"></i> {{ now()->format('d M Y') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Mesin -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Mesin Aktif</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.total_machines">0</p>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fas fa-server"></i> Terhubung
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-fingerprint text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Karyawan -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Karyawan</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.total_users">0</p>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fas fa-users"></i> Terdaftar
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Scan Terakhir -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Scan Terakhir</p>
                    <p class="text-xl font-bold text-gray-800" x-text="stats.latest_time">-</p>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fas fa-user"></i> <span x-text="stats.latest_user">-</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Table -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-stream text-blue-600 mr-2"></i>
                    Absensi Real-Time
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    <span class="inline-flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full pulse-dot mr-2"></span>
                        Auto-refresh setiap 3 detik
                    </span>
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500">
                    <i class="fas fa-sync-alt mr-1"></i>
                    <span x-text="lastUpdate">-</span>
                </span>
                <button @click="fetchData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-user mr-2"></i>User ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-clock mr-2"></i>Waktu Scan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-server mr-2"></i>Mesin
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-2"></i>Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-history mr-2"></i>Waktu Masuk
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-3"></i>
                                <p class="text-gray-500">Memuat data...</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && logs.length === 0">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">Belum ada data absensi</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-gray-50 transition-colors fade-in">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <span class="font-medium text-gray-800" x-text="log.user_id"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700" x-text="log.scan_time"></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-mono" x-text="log.machine_sn"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': log.status === '0',
                                          'bg-red-100 text-red-800': log.status === '1',
                                          'bg-yellow-100 text-yellow-800': log.status === '2',
                                          'bg-blue-100 text-blue-800': log.status === '3',
                                          'bg-purple-100 text-purple-800': log.status === '4',
                                          'bg-indigo-100 text-indigo-800': log.status === '5',
                                          'bg-gray-100 text-gray-800': log.status === '255'
                                      }"
                                      x-text="log.status_label"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="log.created_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboard() {
    return {
        loading: false,
        logs: [],
        stats: {
            total_today: 0,
            total_machines: 0,
            total_users: 0,
            latest_time: '-',
            latest_user: '-'
        },
        lastUpdate: '-',
        refreshInterval: null,

        async init() {
            await this.fetchData();
            this.refreshInterval = setInterval(() => {
                this.fetchData();
            }, 3000);
        },

        async fetchData() {
            this.loading = this.logs.length === 0;
            
            try {
                const response = await fetch('/dashboard/data');
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.logs = result.data;
                    this.updateStats();
                    this.lastUpdate = new Date().toLocaleTimeString('id-ID');
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateStats() {
            const today = new Date().toLocaleDateString('en-CA');
            this.stats.total_today = this.logs.filter(log => {
                const scanDate = log.scan_time.split(' ')[0].split('/').reverse().join('-');
                return scanDate === today;
            }).length;
            
            this.stats.total_machines = [...new Set(this.logs.map(log => log.machine_sn))].length;
            this.stats.total_users = [...new Set(this.logs.map(log => log.user_id))].length;
            
            if (this.logs.length > 0) {
                this.stats.latest_time = this.logs[0].scan_time.split(' ')[1];
                this.stats.latest_user = this.logs[0].user_id;
            }
        }
    }
}
</script>
@endpush
@endsection
