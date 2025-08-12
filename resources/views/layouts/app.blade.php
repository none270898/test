<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "2b01ec1e-7c15-40ea-965e-a42901074970",
      safari_web_id: "web.onesignal.auto.458f9dc8-6677-4788-b5a2-9d66a9d7a179",
      notifyButton: {
        enable: true,
      },
    });
  });
</script>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CryptoNote.pl') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>

<body>
    <div id="app">
        <nav class="navbar">
            <div class="nav-container">
                <a href="{{ route('home') }}" class="nav-brand">
                    <span class="brand-icon">₿</span>
                    <span class="brand-text">CryptoNote.pl</span>
                </a>
                
                <div class="nav-menu">
                    @guest
                        <a href="{{ route('login') }}" class="nav-link">Logowanie</a>
                        <a href="{{ route('register') }}" class="nav-button">Rejestracja</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        @if(auth()->user()->isPremium())
                            <span class="premium-badge">Premium</span>
                            <a href="{{ route('payment.billing') }}" class="nav-link">Rozliczenia</a>
                        @else
                            <a href="{{ route('premium.upgrade') }}" class="nav-button premium-upgrade">
                                🚀 Premium
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="nav-link logout-btn">Wyloguj</button>
                        </form>
                    @endguest
                </div>
            </div>
        </nav>

        <main class="main-content">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>

</html>
