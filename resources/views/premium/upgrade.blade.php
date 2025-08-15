@extends('layouts.app')

@section('content')
    <div class="upgrade-container">
        <div class="upgrade-header">
            <h1>🚀 Upgrade do Premium</h1>
            <p>Odblokuj pełną moc AI w analizie krypto</p>
        </div>

        <div class="pricing-section">
            <div class="pricing-card current">
                <div class="plan-badge" style="">Darmowy-<span style="text-decoration: line-through">Aktualny Plan</span></div>
                <h2>Darmowy</h2>
                <div class="price">0 PLN/miesiąc</div>

                <ul class="features-list">
                    <li class="included">✅ Portfolio do 10 kryptowalut</li>
                    <li class="included">✅ Alerty cenowe (5 aktywnych)</li>
                    <li class="included">✅ Watchlist (15 pozycji)</li>
                    <li class="included">✅ Podstawowe ceny w PLN</li>
                    <li class="limited">❌ AI Sentiment Analysis</li>
                    <li class="limited">❌ Nieograniczone portfolio</li>
                    <li class="limited">❌ Sentiment alerts</li>
                    <li class="limited">❌ Historia i wykresy</li>
                </ul>
            </div>

            <div class="pricing-card premium featured">
                <div class="plan-badge premium">Aktualny Plan-<span style="text-decoration: line-through">Polecany</span> </div>
                <h2>Premium</h2>
                <div class="price">19 PLN<span>/miesiąc</span></div>

                <ul class="features-list">
                    <li class="included">✅ Wszystko z planu darmowego</li>
                    <li class="included">✅ Nieograniczone portfolio</li>
                    <li class="included">✅ Nieograniczone alerty + sentiment</li>
                    <li class="included">✅ Nieograniczona watchlist</li>
                    <li class="premium">🤖 AI Sentiment Analysis</li>
                    <li class="premium">📊 Trend predictions</li>
                    <li class="premium">📈 Historia sentiment + wykresy</li>
                    <li class="premium">🔔 Smart alerts (cena + sentiment)</li>
                    <li class="premium">📱 Push notifications</li>
                    {{-- <li class="premium">📄 Export danych (CSV/PDF)</li> --}}
                </ul>

                @if(0)
                    <div class="payment-options">
                        <h3>💳 Wybierz sposób płatności</h3>

                        {{-- Subskrypcja (tylko karty) --}}
                        <div class="payment-option subscription-option">
                            <div class="option-header">
                                <h4>🔄 Subskrypcja miesięczna</h4>
                                <span class="recommended-badge">Polecane</span>
                            </div>
                            <div class="option-details">
                                <p>✅ Automatyczne odnawianie co miesiąc</p>
                                <p>✅ Możliwość anulowania w każdej chwili</p>
                                <p>✅ Zarządzanie przez panel Stripe</p>
                                <p>💳 Tylko karty kredytowe/debetowe</p>
                            </div>
                            <form action="{{ route('payment.checkout') }}" method="POST" class="payment-form">
                                @csrf
                                <input type="hidden" name="mode" value="subscription">
                                <button type="submit" class="btn btn-premium btn-large">
                                    Subskrypcja - 19 PLN/mies
                                </button>
                            </form>
                        </div>

                        {{-- One-time payment (wszystkie metody) --}}
                        <div class="payment-option onetime-option">
                            <div class="option-header">
                                <h4>💰 Płatność jednorazowa</h4>
                            </div>
                            <div class="option-details">
                                <p>✅ BLIK, Przelewy24, karty</p>
                                <p>✅ Premium na 1 miesiąc</p>
                                <p>⚠️ Wymaga ręcznego odnawiania</p>
                            </div>
                            <form action="{{ route('payment.checkout') }}" method="POST" class="payment-form">
                                @csrf
                                <input type="hidden" name="mode" value="payment">
                                <button type="submit" class="btn btn-secondary btn-large">
                                    Jednorazowo - 19 PLN
                                </button>
                            </form>
                        </div>
                    </div>
                @else  
                    <div class="upgrade-form disabled">
                        <button type="button" class="btn btn-premium btn-large disabled" disabled>
                            ⏳ Wkrótce dostępne
                        </button>
                    </div>
                @endif

                <div class="premium-guarantee">
                    <p>💰 7 dni gwarancji zwrotu pieniędzy</p>
                    <p>🔒 Bezpieczne płatności przez Stripe</p>
                    <p>❌ Możliwość anulowania w każdej chwili</p>
                </div>
            </div>
        </div>

        <div class="features-showcase">
            <h2>🤖 Co daje Premium?</h2>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🧠</div>
                    <h3>AI Sentiment Analysis</h3>
                    <p>Analiza nastrojów z polskich społeczności krypto - Reddit, Twitter, fora dyskusyjne</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <h3>Trend Predictions</h3>
                    <p>AI przewiduje trendy na podstawie sentiment + analiza techniczna</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📈</div>
                    <h3>Historia + Wykresy</h3>
                    <p>Pełna historia sentiment, wykresy trendów, porównania z rynkiem</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🔔</div>
                    <h3>Smart Alerts</h3>
                    <p>Alerty kombinowane: cena + sentiment + push notifications</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">∞</div>
                    <h3>Unlimited Everything</h3>
                    <p>Nieograniczone portfolio, watchlist, alerty - bez limitów</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📄</div>
                    <h3>Export & Analytics</h3>
                    <p>Export portfolio do CSV/PDF, zaawansowane analytics</p>
                </div>
            </div>
        </div>

        <div class="testimonials">
            <h2>👥 Co mówią nasi użytkownicy</h2>

            <div class="testimonials-grid">
                <div class="testimonial">
                    <p>"AI sentiment z polskich grup to game-changer. Wcześniej śledzenie wszystkiego ręcznie zajmowało
                        godziny."</p>
                    <span>C. B</span>
                </div>

                {{-- <div class="testimonial">
                    <p>"Wreszcie portfolio tracker który rozumie polski rynek. Alerty sentiment uratowały mnie przed
                        stratami."</p>
                    <span>- Anna S., inwestorka</span>
                </div>

                <div class="testimonial">
                    <p>"19 PLN to nic w porównaniu do tego co zyskuję dzięki AI insights. ROI w pierwszym tygodniu."</p>
                    <span>- Tomasz D., hodler</span>
                </div> --}}
            </div>
        </div>

        <div class="faq-section">
            <h2>❓ Często zadawane pytania</h2>

            <div class="faq-grid">
                <div class="faq-item">
                    <h3>Czy mogę anulować w każdej chwili?</h3>
                    <p>Tak, możesz anulować subskrypcję w każdej chwili. Zachowasz dostęp do Premium do końca opłaconego
                        okresu.</p>
                </div>

                <div class="faq-item">
                    <h3>Jakie metody płatności akceptujecie?</h3>
                    <p>Karty kredytowe/debetowe, BLIK, Przelewy24. Wszystkie płatności są bezpiecznie obsługiwane przez
                        Stripe.</p>
                </div>

                <div class="faq-item">
                    <h3>Czy AI analizuje tylko polskie źródła?</h3>
                    <p>Nie tylko - analizujemy polskie społeczności + największe międzynarodowe źródła, ale z fokusem na
                        polski rynek.</p>
                </div>

                <div class="faq-item">
                    <h3>Co jeśli nie będę zadowolony?</h3>
                    <p>7 dni gwarancji zwrotu pieniędzy. Jeśli Premium nie spełni Twoich oczekiwań, zwrócimy pełną kwotę.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
