@extends('emails.layout')

@section('title', 'Alert cenowy')

@section('header-subtitle', 'Twój alert cenowy został uruchomiony')

@section('content')
    <div class="greeting">
        Cześć {{ $user->name }}! 🚨
    </div>
    
    <div class="content">
        <p>Twój alert cenowy został uruchomiony!</p>
        
        <div class="crypto-stats">
            <div class="crypto-icon">
                @if($alert->type === 'above')
                    📈
                @else
                    📉
                @endif
            </div>
            <h3>{{ $crypto->name }} ({{ strtoupper($crypto->symbol) }})</h3>
            <p><strong>Cena {{ $direction }} {{ number_format($alert->target_price, 2) }} {{ $currency }}</strong></p>
            <p>Aktualna cena: <strong>{{ number_format($currentPrice, 2) }} {{ $currency }}</strong></p>
        </div>
        
        <div class="button-container">
            <a href="{{ route('dashboard') }}" class="button">
                📊 Zobacz portfolio
            </a>
        </div>
        
        <div class="warning-box">
            <p><strong>Informacja:</strong> Alert został automatycznie dezaktywowany po uruchomieniu. Możesz utworzyć nowy alert w panelu zarządzania.</p>
        </div>
        
        <div class="footer-text">
            <p><strong>Dlaczego otrzymujesz tę wiadomość?</strong></p>
            <p>Utworzyłeś alert cenowy dla {{ $crypto->name }} z warunkiem: {{ $alert->type === 'above' ? 'powyżej' : 'poniżej' }} {{ number_format($alert->target_price, 2) }} {{ $currency }}.</p>
        </div>
    </div>
@endsection