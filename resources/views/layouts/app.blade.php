<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.init({
            appId: "{{ config('services.onesignal.app_id') }}",
            safari_web_id: "web.onesignal.auto.{{ config('services.onesignal.app_id') }}",
            notifyButton: {
                enable: false, // Wyłączamy domyślny przycisk
            },
            allowLocalhostAsSecureOrigin: true, // Dla developmentu
        });

        // Sprawdź czy użytkownik jest zalogowany
        @auth
        // Tag użytkownika dla targetowanych notyfikacji
        OneSignal.User.addTag("user_id", "{{ auth()->id() }}");
        OneSignal.User.addTag("user_email", "{{ auth()->user()->email }}");

        console.log('OneSignal initialized for user {{ auth()->id() }}');
    @endauth

    // Event listeners
    OneSignal.Notifications.addEventListener('click', function(event) {
        console.log('OneSignal notification clicked:', event);

        // Redirect to dashboard if it's a price alert
        if (event.notification.additionalData && event.notification.additionalData.type === 'price_alert') {
            window.location.href = '{{ route('dashboard') }}';
        }
    });

    OneSignal.User.PushSubscription.addEventListener('change', function(event) {
        console.log('OneSignal subscription changed:', event);
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
                    CryptoNote.pl
                </a>

                <div class="nav-menu">
                    @guest
                        <a href="{{ route('login') }}" class="nav-link">Logowanie</a>
                        <a href="{{ route('register') }}" class="nav-button">Rejestracja</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        @if (auth()->user()->isPremium())
                            <span class="premium-badge">Premium</span>
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
