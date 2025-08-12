@extends('layouts.app')

@section('content')
<div class="payment-result-container">
    <div class="result-card cancel">
        <div class="result-icon">⏸️</div>
        <h1>Płatność anulowana</h1>
        <p>Nie martw się - żadna płatność nie została pobrana.</p>
        
        <div class="cancel-message">
            <h3>🤔 Masz pytania dotyczące Premium?</h3>
            <p>Premium w CryptoNote.pl to nie tylko więcej funkcji - to kompletnie nowe doświadczenie AI-powered trading.</p>
        </div>

        <div class="premium-benefits">
            <h3>💡 Dlaczego warto wybrać Premium:</h3>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <span class="benefit-icon">🤖</span>
                    <div>
                        <h4>AI Sentiment</h4>
                        <p>Analiza nastrojów z polskich społeczności krypto</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📈</span>
                    <div>
                        <h4>Trend Predictions</h4>
                        <p>AI przewiduje ruchy na podstawie sentiment + technicals</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">∞</span>
                    <div>
                        <h4>Unlimited</h4>
                        <p>Brak limitów portfolio, alertów, watchlist</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">🔔</span>
                    <div>
                        <h4>Smart Alerts</h4>
                        <p>Kombinowane alerty: cena + sentiment</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="testimonial-highlight">
            <blockquote>
                "Premium CryptoNote uratował mnie przed stratami. AI sentiment alert ostrzegł przed dumpem 2 dni wcześniej."
            </blockquote>
            <cite>- Marcin K., active trader</cite>
        </div>

        <div class="action-buttons">
            <a href="{{ route('premium.upgrade') }}" class="btn btn-premium btn-large">
                Spróbuj ponownie - 19 PLN/mies
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                Powrót do Dashboard
            </a>
        </div>

        <div class="guarantee-info">
            <h3>🛡️ Gwarancje Premium:</h3>
            <ul>
                <li>💰 7 dni gwarancji zwrotu pieniędzy</li>
                <li>❌ Możliwość anulowania w każdej chwili</li>
                <li>🔒 Bezpieczne płatności przez Stripe</li>
                <li>📧 Email support w przypadku problemów</li>
            </ul>
        </div>

        <div class="continue-free">
            <p><strong>💻 Możesz nadal używać wersji darmowej</strong></p>
            <p>Portfolio do 10 kryptowalut, 5 alertów cenowych, podstawowe funkcje tracking.</p>
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
    max-width: 700px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
}

.result-card.cancel {
    border-color: #f59e0b;
}

.result-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.result-card h1 {
    color: #1e293b;
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.result-card > p {
    color: #64748b;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.cancel-message {
    background: #fef3cd;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.cancel-message h3 {
    color: #92400e;
    margin-bottom: 1rem;
}

.cancel-message p {
    color: #b45309;
    margin: 0;
    line-height: 1.6;
}

.premium-benefits {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
}

.premium-benefits h3 {
    color: #1e293b;
    margin-bottom: 1.5rem;
    text-align: center;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.benefit-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.benefit-item h4 {
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.benefit-item p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.testimonial-highlight {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
}

.testimonial-highlight blockquote {
    font-size: 1.1rem;
    font-style: italic;
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.testimonial-highlight cite {
    font-size: 0.9rem;
    opacity: 0.9;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.guarantee-info {
    background: #f0fdf4;
    border: 1px solid #10b981;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.guarantee-info h3 {
    color: #065f46;
    margin-bottom: 1rem;
    text-align: center;
}

.guarantee-info ul {
    list-style: none;
    padding: 0;
    color: #047857;
}

.guarantee-info li {
    padding: 0.5rem 0;
    font-weight: 500;
}

.continue-free {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1.5rem;
    font-size: 0.9rem;
    color: #64748b;
}

.continue-free p {
    margin: 0.5rem 0;
    line-height: 1.5;
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
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .benefit-item {
        flex-direction: column;
        text-align: center;
    }
    
    .testimonial-highlight {
        text-align: center;
    }
}
</style>
@endsection