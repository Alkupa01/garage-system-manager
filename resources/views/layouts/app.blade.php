<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Sewa Garasi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-slate-900 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg tracking-wide">GARAGE SYSTEM</a>

            @auth
                <div class="flex items-center gap-5 text-sm">
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.rentals.index') }}" class="hover:text-amber-400 {{ request()->routeIs('admin.rentals.*') ? 'text-amber-400 font-semibold' : '' }}">Transaksi</a>
                        <a href="{{ route('admin.garages.index') }}" class="hover:text-amber-400 {{ request()->routeIs('admin.garages.*') ? 'text-amber-400 font-semibold' : '' }}">Unit Garasi</a>
                        <a href="{{ route('admin.tenants.index') }}" class="hover:text-amber-400 {{ request()->routeIs('admin.tenants.*') ? 'text-amber-400 font-semibold' : '' }}">Verifikasi Penyewa</a>
                        <a href="{{ route('admin.reports.index') }}" class="hover:text-amber-400 {{ request()->routeIs('admin.reports.*') ? 'text-amber-400 font-semibold' : '' }}">Laporan</a>
                    @else
                        <a href="{{ route('tenant.rentals.index') }}" class="hover:text-amber-400 {{ request()->routeIs('tenant.rentals.*') ? 'text-amber-400 font-semibold' : '' }}">Sewa Garasi</a>
                        <a href="{{ route('tenant.vehicles.index') }}" class="hover:text-amber-400 {{ request()->routeIs('tenant.vehicles.*') ? 'text-amber-400 font-semibold' : '' }}">Kendaraan Saya</a>
                        <a href="{{ route('tenant.profile') }}" class="hover:text-amber-400 {{ request()->routeIs('tenant.profile*') ? 'text-amber-400 font-semibold' : '' }}">Profil & KTP</a>
                    @endif

                    <span class="text-slate-400">|</span>
                    <span class="text-xs text-slate-300">{{ auth()->user()->name }} ({{ strtoupper(auth()->user()->role) }})</span>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs font-medium">Keluar</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="max-w-7xl w-full mx-auto px-4 py-6 flex-1">
        @if(session('success'))
            <div class="mb-4 p-3 rounded bg-emerald-100 border border-emerald-400 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 border border-red-400 text-red-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>