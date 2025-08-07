@extends('layouts.app')

@section('content')
<div class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">
            Zarządzaj swoim portfolio <span class="highlight">krypto</span>
        </h1>
        <p class="hero-description">
            Pierwszy tracker skupiony na polskim rynku krypto z AI-analizą trendów i lokalnymi insightami
        </p>
        
        <div class="hero-actions">
            <a href="{{ route('register') }}" class="btn btn-primary btn-large">
                Rozpocznij za darmo
            </a>
            <div class="pricing-info">
                <span class="free-text">Darmowy plan</span>
                <span class="premium-text">Premium 19 PLN/mies.</span>
            </div>
        </div>
    </div>
    
    <div class="features-preview">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Portfolio Tracker</h3>
            <p>Real-time tracking wartości w PLN</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔔</div>
            <h3>Alerty cenowe</h3>
            <p>Push + email notifications</p>
        </div>
        
        <div class="feature-card premium">
            <div class="feature-icon">🤖</div>
            <h3>AI Analiza trendów</h3>
            <p>Polski sentiment analysis</p>
            <span class="premium-label">Premium</span>
        </div>
    </div>
</div>
@endsection