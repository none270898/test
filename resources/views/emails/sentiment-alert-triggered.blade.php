@extends('emails.layout')

@section('title', 'Smart Alert')

@section('header-subtitle', 'Wykryto ' . $alertType . ' sentiment')

@section('content')
    <div class="greeting">
        Cześć {{ $user->name }}! {{ $emoji }}
    </div>
    
    <div class="content">
        <p>Twój Smart Alert wykrył {{ $alertType }} sentiment dla <strong>{{ $crypto->name }}</strong>!</p>
        
        <div class="crypto-stats">
            <div class="crypto-icon">{{ $emoji }}</div>
            <h3>{{ $crypto->name }} ({{ strtoupper($crypto->symbol) }})</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0;">
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: {{ $sentiment > 0 ? '#10b981' : ($sentiment < 0 ? '#ef4444' : '#6b7280') }};">
                        {{ $sentiment > 0 ? '+' : '' }}{{ number_format($sentiment, 2) }}
                    </div>
                    <div style="font-size: 0.9rem; color: #64748b;">AI Sentiment</div>
                </div>
                
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #1e293b;">{{ $mentions }}</div>
                    <div style="font-size: 0.9rem; color: #64748b;">Wzmianek</div>
                </div>
            </div>
            
            @if(abs($change) > 0.1)
            <p style="text-align: center; color: #64748b;">
                Zmiana 24h: <span style="color: {{ $change > 0 ? '#10b981' : '#ef4444' }}; font-weight: 600;">
                    {{ $change > 0 ? '+' : '' }}{{ number_format($change, 2) }}
                </span>
            </p>
            @endif
        </div>
        
        <div class="button-container">
            <a href="{{ route('dashboard') }}" class="button">
                📊 Zobacz szczegóły
            </a>
        </div>
        
        <div class="warning-box">
            <p><strong>Smart Alert Premium:</strong> To zaawansowany alert kombinujący sentiment + aktywność społeczności. Możesz wyłączyć powiadomienia w ustawieniach watchlist.</p>
        </div>
        
        <div class="footer-text">
            <p><strong>Dlaczego otrzymujesz tę wiadomość?</strong></p>
            <p>{{ $crypto->name }} jest w Twojej watchlist z włączonymi Smart Alerts (Premium). AI wykrył {{ $alertType }} sentiment w polskich społecznościach krypto.</p>
        </div>
    </div>
@endsection