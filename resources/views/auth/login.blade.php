<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - ADMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-6">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="flex flex-col items-center mb-8">
                    <div class="w-16 h-16 bg-blue-900 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <i class="fas fa-fingerprint text-white text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">ADMS</h1>
                    <p class="text-sm text-gray-500">Attendance System - Masuk untuk melanjutkan</p>
                </div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                                   class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="password" name="password" required
                                   class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                            Ingat saya
                        </label>
                    </div>
                    <button type="submit"
                            class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/30">
                        <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-blue-200 text-sm mt-6">ADMS Attendance System &copy; {{ now()->year }}</p>
    </div>
</body>
</html>