@extends('emails.layout')

@section('title', 'Resetuj hasło')

@section('header-subtitle', 'Reset hasła do konta')

@section('content')
    <div class="greeting">
        Cześć {{ $user->name }}!
    </div>
    
    <div class="content">
        <p>Otrzymaliśmy prośbę o reset hasła dla Twojego konta w CryptoNote.pl.</p>
        
        <p>Aby ustawić nowe hasło, kliknij poniższy przycisk:</p>
        
        <div class="button-container">
            <a href="{{ $resetUrl }}" class="button">
                🔐 Ustaw nowe hasło
            </a>
        </div>
        
        <div class="warning-box">
            <p><strong>Bezpieczeństwo przede wszystkim:</strong> Link jest ważny przez {{ $count }} minut. Jeśli to nie Ty prosiłeś o reset hasła, zignoruj tę wiadomość.</p>
        </div>
        
        <div class="crypto-stats">
            <div class="crypto-icon">🛡️</div>
            <p><strong>Twoje portfolio jest bezpieczne</strong></p>
            <p>Twoje dane krypto pozostają chronione podczas całego procesu resetowania hasła.</p>
        </div>
        
        <div class="footer-text">
            Jeśli nie możesz kliknąć przycisku, skopiuj i wklej poniższy link do przeglądarki:
        </div>
        
        <div class="link-alternative">
            {{ $resetUrl }}
        </div>
    </div>
@endsection