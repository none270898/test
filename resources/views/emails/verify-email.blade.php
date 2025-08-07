@extends('emails.layout')

@section('title', 'Potwierdź swój email')

@section('header-subtitle', 'Potwierdź swoje konto')

@section('content')
    <div class="greeting">
        Cześć {{ $user->name }}! 👋
    </div>
    
    <div class="content">
        <p>Dziękujemy za rejestrację w CryptoNote.pl!</p>
        
        <p>Aby aktywować swoje konto i rozpocząć zarządzanie portfolio krypto, kliknij poniższy przycisk:</p>
        
        <div class="button-container">
            <a href="{{ $verificationUrl }}" class="button">
                ✅ Potwierdź adres email
            </a>
        </div>
        
        <div class="crypto-stats">
            <div class="crypto-icon">🚀</div>
            <p><strong>Co Cię czeka w CryptoNote.pl:</strong></p>
            <p>📊 Real-time tracking w PLN • 🔔 Alerty cenowe • 🤖 AI analiza trendów (Premium)</p>
        </div>
        
        <div class="warning-box">
            <p><strong>Ważne:</strong> Link weryfikacyjny jest ważny przez 60 minut. Po tym czasie możesz poprosić o nowy link.</p>
        </div>
        
        <div class="footer-text">
            Jeśli nie możesz kliknąć przycisku, skopiuj i wklej poniższy link do przeglądarki:
        </div>
        
        <div class="link-alternative">
            {{ $verificationUrl }}
        </div>
    </div>
@endsection