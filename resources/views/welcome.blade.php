@extends('layouts.app')

@section('title', 'Strona Główna')

@section('content')
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">
                Zarządzaj swoim portfolio kryptowalut
                <span class="highlight">z AI analizą trendów</span>
            </h1>
            <p class="hero-subtitle">
                Pierwszy polski tracker kryptowalut z analizą lokalnego sentimentu. 
                Śledź ceny, otrzymuj alerty i analizuj trendy na polskim rynku crypto.
            </p>
            <div class="hero-actions">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-large">
                        Rozpocznij za darmo
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-large">
                        Logowanie
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-large">
                        Przejdź do Dashboard
                    </a>
                @endguest
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="crypto-cards">
                <div class="crypto-card">
                    <span class="crypto-symbol">BTC</span>
                    <span class="crypto-trend up">+5.2%</span>
                </div>
                <div class="crypto-card">
                    <span class="crypto-symbol">ETH</span>
                    <span class="crypto-trend down">-2.1%</span>
                </div>
                <div class="crypto-card">
                    <span class="crypto-symbol">ADA</span>
                    <span class="crypto-trend up">+8.7%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2 class="section-title">Funkcje CryptoNote.pl</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Portfolio Tracker</h3>
                <p>Śledź wartość swojego portfolio w czasie rzeczywistym z cenami w PLN</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Alerty Cenowe</h3>
                <p>Otrzymuj powiadomienia email i push gdy ceny osiągną twoje cele</p>
            </div>
            <div class="feature-card premium">
                <div class="feature-icon">🤖</div>
                <h3>AI Analiza Trendów</h3>
                <p>Sentiment analysis polskich źródeł - Reddit, Twitter, fora krypto</p>
                <span class="premium-badge">Premium</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🇵🇱</div>
                <h3>Polski Rynek</h3>
                <p>Pierwszy tracker skupiony na polskim rynku krypto z lokalnymi insightami</p>
            </div>
        </div>
    </div>
</div>

<div class="pricing-section">
    <div class="container">
        <h2 class="section-title">Plany cenowe</h2>
        <div class="pricing-grid">
            <div class="pricing-card">
                <h3>Free</h3>
                <div class="price">0 PLN</div>
                <ul class="features-list">
                    <li>✅ Portfolio tracker</li>
                    <li>✅ Alerty cenowe</li>
                    <li>✅ Podstawowe wykresy</li>
                    <li>❌ AI analiza trendów</li>
                    <li>❌ Zaawansowane alerty</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-secondary">Rozpocznij</a>
            </div>
            <div class="pricing-card featured">
                <h3>Premium</h3>
                <div class="price">19 PLN<span>/miesiąc</span></div>
                <ul class="features-list">
                    <li>✅ Wszystko z Free</li>
                    <li>✅ AI analiza trendów</li>
                    <li>✅ Sentiment analysis</li>
                    <li>✅ Zaawansowane alerty</li>
                    <li>✅ Raporty tygodniowe</li>
                    <li>✅ Priorytetowe wsparcie</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-primary">Wybierz Premium</a>
            </div>
        </div>
    </div>
</div>
@endsection