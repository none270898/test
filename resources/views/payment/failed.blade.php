@extends('layouts.app')

@section('content')
<div class="payment-result-container">
    <div class="result-card failed">
        <div class="result-icon">❌</div>
        <h1>Płatność nieudana</h1>
        <p>Niestety, nie udało się przetworzyć Twojej płatności.</p>
        
        <div class="error-details">
            <h3>🔍 Możliwe przyczyny:</h3>
            <ul>
                <li>💳 Niewystarczające środki na karcie</li>
                <li>🚫 Karta została odrzucona przez bank</li>
                <li>⚠️ Nieprawidłowe dane karty</li>
                <li>🌐 Problem z połączeniem internetowym</li>
                <li>🏦 Tymczasowe problemy z systemem bankowym</li>
            </ul>
        </div>

        <div class="solutions">
            <h3>💡 Co możesz zrobić:</h3>
            <div class="solutions-grid">
                <div class="solution-item">
                    <span class="solution-icon">🔄</span>
                    <div>
                        <h4>Spróbuj ponownie</h4>
                        <p>Sprawdź dane karty i spróbuj jeszcze raz</p>
                    </div>
                </div>
                <div class="solution-item">
                    <span class="solution-icon">💳</span>
                    <div>
                        <h4>Inna metoda płatności</h4>
                        <p>Użyj BLIK lub Przelewy24</p>
                    </div>
                </div>
                <div class="solution-item">
                    <span class="solution-icon">🏦</span>
                    <div>
                        <h4>Skontaktuj się z bankiem</h4>
                        <p>Sprawdź czy karta nie jest zablokowana</p>
                    </div>
                </div>
                <div class="solution-item">
                    <span class="solution-icon">📧</span>
                    <div>
                        <h4>Napisz do nas</h4>
                        <p>Pomożemy rozwiązać problem</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="payment-security">
            <h3>🔒 Bezpieczeństwo płatności</h3>
            <p>Wszystkie płatności są obsługiwane przez <strong>Stripe</strong> - jeden z najbezpieczniejszych procesorów płatności na świecie. Twoje dane karty są w pełni chronione.</p>
            <div class="security-badges">
                <span class="badge">🔐 SSL Encryption</span>
                <span class="badge">✅ PCI DSS Compliant</span>
                <span class="badge">🛡️ 3D Secure</span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="{{ route('premium.upgrade') }}" class="btn btn-premium btn-large">
                Spróbuj ponownie
            </a>
            <a href="mailto:support@cryptonote.pl" class="btn btn-secondary">
                Skontaktuj się z pomocą
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-tertiary">
                Powrót do Dashboard
            </a>
        </div>

        <div class="alternative-options">
            <h3>🎯 Alternatywne opcje płatności:</h3>
            <div class="payment-methods">
                <div class="payment-method">
                    <span class="method-icon">📱</span>
                    <div>
                        <h4>BLIK</h4>
                        <p>Szybka płatność mobilna</p>
                    </div>
                </div>
                <div class="payment-method">
                    <span class="method-icon">🏦</span>
                    <div>
                        <h4>Przelewy24</h4>
                        <p>Płatność przez bank online</p>
                    </div>
                </div>
                <div class="payment-method">
                    <span class="method-icon">💳</span>
                    <div>
                        <h4>Karta kredytowa/debetowa</h4>
                        <p>Visa, Mastercard, American Express</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="support-info">
            <h3>📞 Potrzebujesz pomocy?</h3>
            <div class="support-options">
                <div class="support-option">
                    <span class="support-icon">📧</span>
                    <div>
                        <strong>Email:</strong> support@cryptonote.pl<br>
                        <small>Odpowiadamy w ciągu 24h</small>
                    </div>
                </div>
                <div class="support-option">
                    <span class="support-icon">💬</span>
                    <div>
                        <strong>Chat:</strong> Dostępny 9:00-17:00<br>
                        <small>Szybka pomoc w czasie rzeczywistym</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="continue-free">
            <p><strong>💻 Możesz nadal korzystać z wersji darmowej</strong></p>
            <p>Twoje portfolio i alerty działają normalnie. Premium możesz aktywować później.</p>
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

.result-card.failed {
    border-color: #ef4444;
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

.error-details {
    background: #fef2f2;
    border: 1px solid #ef4444;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.error-details h3 {
    color: #dc2626;
    margin-bottom: 1rem;
    text-align: center;
}

.error-details ul {
    list-style: none;
    padding: 0;
    color: #dc2626;
}

.error-details li {
    padding: 0.5rem 0;
    font-weight: 500;
}

.solutions {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: left;
}

.solutions h3 {
    color: #1e293b;
    margin-bottom: 1.5rem;
    text-align: center;
}

.solutions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.solution-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.solution-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.solution-item h4 {
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.solution-item p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.payment-security {
    background: #f0fdf4;
    border: 1px solid #10b981;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.payment-security h3 {
    color: #065f46;
    margin-bottom: 1rem;
}

.payment-security p {
    color: #047857;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.security-badges {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.badge {
    background: #d1fae5;
    color: #065f46;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-tertiary {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-tertiary:hover {
    background: #f1f5f9;
}

.alternative-options {
    background: #fef3cd;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.alternative-options h3 {
    color: #92400e;
    margin-bottom: 1rem;
    text-align: center;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #fbbf24;
}

.method-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.payment-method h4 {
    color: #92400e;
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
}

.payment-method p {
    color: #b45309;
    margin: 0;
    font-size: 0.8rem;
}

.support-info {
    background: linear-gradient(135deg, #e0e7ff, #f3e8ff);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.support-info h3 {
    color: #4338ca;
    margin-bottom: 1rem;
    text-align: center;
}

.support-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.support-option {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #c7d2fe;
}

.support-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.support-option strong {
    color: #4338ca;
}

.support-option small {
    color: #6366f1;
    font-size: 0.8rem;
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
    
    .solutions-grid,
    .payment-methods,
    .support-options {
        grid-template-columns: 1fr;
    }
    
    .solution-item,
    .payment-method,
    .support-option {
        flex-direction: column;
        text-align: center;
    }
    
    .security-badges {
        flex-direction: column;
        align-items: center;
    }
}
</style>
@endsection