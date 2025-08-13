@extends('layouts.app')

@section('content')
<div class="payment-result-container">
    <div class="result-card success">
        <div class="result-icon">🎉</div>
        <h1>Witaj w Premium!</h1>
        <p>Twoja płatność została pomyślnie przetworzona. Premium został aktywowany!</p>
        
        <div class="premium-activated">
            <div class="activation-details">
                <h3>✅ Aktywowano właśnie:</h3>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">🤖</span>
                        <span>AI Sentiment Analysis</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">∞</span>
                        <span>Unlimited Portfolio & Alerts</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📊</span>
                        <span>Advanced Analytics & Charts</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔔</span>
                        <span>Smart Notifications</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="payment-summary">
            <h3>📋 Podsumowanie płatności:</h3>
            <div class="summary-details">
                <div class="summary-row">
                    <span>Plan:</span>
                    <span><strong>CryptoNote Premium</strong></span>
                </div>
                <div class="summary-row">
                    <span>Cena:</span>
                    <span><strong>19,00 PLN / miesiąc</strong></span>
                </div>
                <div class="summary-row">
                    <span>Następna płatność:</span>
                    <span><strong>{{ now()->addMonth()->format('d.m.Y') }}</strong></span>
                </div>
                <div class="summary-row">
                    <span>ID transakcji:</span>
                    <span class="transaction-id">{{ $session->id ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="next-steps">
            <h3>🚀 Następne kroki:</h3>
            <div class="steps-grid">
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4>Dodaj kryptowaluty</h4>
                        <p>Rozbuduj portfolio bez limitów</p>
                    </div>
                </div>
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4>Ustaw AI Alerts</h4>
                        <p>Kombinuj ceny + sentiment analysis</p>
                    </div>
                </div>
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4>Odkryj AI Insights</h4>
                        <p>Zobacz trendy z polskich społeczności</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="{{ route('dashboard') }}" class="btn btn-premium btn-large">
                🏠 Przejdź do Dashboard
            </a>
            <a href="{{ route('payment.billing') }}" class="btn btn-secondary">
                ⚙️ Zarządzaj subskrypcją
            </a>
        </div>

        <div class="premium-benefits-reminder">
            <h3>💡 Pamiętaj o swoich nowych możliwościach:</h3>
            <div class="benefits-showcase">
                <div class="benefit-highlight">
                    <span class="benefit-icon">🧠</span>
                    <div class="benefit-text">
                        <strong>AI Sentiment z polskich źródeł</strong>
                        <p>Reddit r/kryptowaluty, BitcoinPL i inne społeczności</p>
                    </div>
                </div>
                <div class="benefit-highlight">
                    <span class="benefit-icon">📈</span>
                    <div class="benefit-text">
                        <strong>History & Predictions</strong>
                        <p>Wykresy sentiment, trend predictions, confidence scores</p>
                    </div>
                </div>
                <div class="benefit-highlight">
                    <span class="benefit-icon">📱</span>
                    <div class="benefit-text">
                        <strong>Push Notifications</strong>
                        <p>Natychmiastowe alerty na telefon i email</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="billing-info">
            <h3>💳 Informacje o rozliczeniach:</h3>
            <div class="billing-details">
                <p><strong>Automatyczne odnowienia:</strong> Subskrypcja będzie automatycznie odnawiana co miesiąc</p>
                <p><strong>Zarządzanie:</strong> Możesz w każdej chwili anulować lub zmienić plan w ustawieniach</p>
                <p><strong>Gwarancja:</strong> 7 dni gwarancji zwrotu pieniędzy</p>
                <p><strong>Support:</strong> support@cryptonote.pl dla wszelkich pytań</p>
            </div>
        </div>

        <div class="email-confirmation">
            <p>📧 <strong>Potwierdzenie zostało wysłane na:</strong> {{ $user->email ?? auth()->user()->email }}</p>
        </div>
    </div>
</div>

<style>
.payment-result-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.result-card {
    background: white;
    border-radius: 16px;
    padding: 3rem;
    max-width: 800px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
}

.result-card.success {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.02), rgba(16, 185, 129, 0.01));
}

.result-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.result-card h1 {
    color: #1e293b;
    font-size: 2.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.result-card > p {
    color: #64748b;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.premium-activated {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 2px solid #10b981;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.activation-details h3 {
    color: #065f46;
    margin-bottom: 1.5rem;
}

.feature-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #10b981;
    color: #065f46;
    font-weight: 600;
}

.feature-icon {
    font-size: 1.5rem;
}

.payment-summary {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.payment-summary h3 {
    color: #1e293b;
    margin-bottom: 1rem;
    text-align: center;
}

.summary-details {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.summary-row:last-child {
    border-bottom: none;
}

.transaction-id {
    font-family: monospace;
    font-size: 0.8rem;
    color: #64748b;
}

.next-steps {
    margin-bottom: 2rem;
}

.next-steps h3 {
    color: #1e293b;
    margin-bottom: 1.5rem;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    text-align: left;
}

.step-number {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h4 {
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.step-content p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.premium-benefits-reminder {
    background: linear-gradient(135deg, #f3e8ff, #e0e7ff);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
}

.premium-benefits-reminder h3 {
    color: #4338ca;
    margin-bottom: 1.5rem;
    text-align: center;
}

.benefits-showcase {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.benefit-highlight {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #c7d2fe;
}

.benefit-highlight .benefit-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.benefit-text strong {
    color: #4338ca;
    display: block;
    margin-bottom: 0.25rem;
}

.benefit-text p {
    color: #6366f1;
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.billing-info {
    background: #fef3cd;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.billing-info h3 {
    color: #92400e;
    margin-bottom: 1rem;
    text-align: center;
}

.billing-details p {
    color: #b45309;
    margin: 0.5rem 0;
    font-size: 0.9rem;
    line-height: 1.5;
}

.email-confirmation {
    background: #f0fdf4;
    border: 1px solid #10b981;
    border-radius: 8px;
    padding: 1rem;
    font-size: 0.9rem;
}

.email-confirmation p {
    margin: 0;
    color: #065f46;
}

@media (max-width: 768px) {
    .result-card {
        padding: 2rem;
        margin: 1rem;
    }
    
    .result-card h1 {
        font-size: 2rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .feature-list,
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .step-item {
        flex-direction: column;
        text-align: center;
    }
    
    .benefit-highlight {
        flex-direction: column;
        text-align: center;
    }
    
    .summary-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>
@endsection