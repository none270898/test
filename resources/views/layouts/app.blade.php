<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="onesignal-app-id" content="{{ config('services.onesignal.app_id') }}">

    <title>{{ config('app.name', 'CryptoNote.pl') }} - @yield('title', 'Portfolio Kryptowalut')</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar">
            <div class="container">
                <div class="navbar-brand">
                    <a href="{{ route('home') }}" class="brand-link">
                        <span class="brand-icon">₿</span>
                        CryptoNote.pl
                    </a>
                </div>

                <div class="navbar-menu">
                    @auth
                        <div class="navbar-nav">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('portfolio.index') }}" class="nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}">
                                Portfolio
                            </a>
                            <a href="{{ route('alerts.index') }}" class="nav-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                                Alerty
                            </a>
                            @if(auth()->user()->isPremium())
                                <a href="{{ route('trends.index') }}" class="nav-link {{ request()->routeIs('trends.*') ? 'active' : '' }}">
                                    Trendy AI
                                </a>
                            @endif
                            <a href="{{ route('subscription.index') }}" class="nav-link {{ request()->routeIs('subscription.*') ? 'active' : '' }}">
                                @if(auth()->user()->isPremium())
                                    <span class="premium-badge">Premium</span>
                                @else
                                    Upgrade
                                @endif
                            </a>
                        </div>

                        <div class="navbar-user">
                            <div class="user-dropdown">
                                <span class="user-name">{{ auth()->user()->name }}</span>
                                <div class="dropdown-menu">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Wyloguj</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="navbar-nav">
                            <a href="{{ route('login') }}" class="nav-link">Logowanie</a>
                            <a href="{{ route('register') }}" class="nav-link btn-primary">Rejestracja</a>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; {{ date('Y') }} CryptoNote.pl - Portfolio Kryptowalut</p>
            </div>
        </footer>
    </div>

    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
</body>
</html>
