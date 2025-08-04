<template>
  <div class="subscription-content">
    <div v-if="isPremium" class="current-plan premium">
      <div class="plan-header">
        <h3>Plan Premium Aktywny ⭐</h3>
        <div class="plan-status">
          Aktywny do: {{ formatDate(user.subscription_expires_at) }}
        </div>
      </div>
      
      <div class="premium-features">
        <h4>Twoje korzyści Premium:</h4>
        <ul class="features-list">
          <li>✅ AI Analiza trendów i sentiment analysis</li>
          <li>✅ Zaawansowane alerty cenowe</li>
          <li>✅ Tygodniowe raporty portfolio</li>
          <li>✅ Priorytetowe wsparcie techniczne</li>
          <li>✅ Wcześniejszy dostęp do nowych funkcji</li>
        </ul>
      </div>
      
      <div class="plan-actions">
        <button @click="cancelSubscription" class="btn btn-danger" :disabled="loading">
          {{ loading ? 'Anulowanie...' : 'Anuluj subskrypcję' }}
        </button>
      </div>
    </div>

    <div v-else class="upgrade-section">
      <div class="plan-comparison">
        <div class="plan-card free">
          <h3>Plan Free</h3>
          <div class="price">0 PLN</div>
          <ul class="features-list">
            <li>✅ Portfolio tracker</li>
            <li>✅ Podstawowe alerty cenowe</li>
            <li>✅ Aktualne ceny kryptowalut</li>
            <li>❌ AI analiza trendów</li>
            <li>❌ Zaawansowane alerty</li>
            <li>❌ Raporty tygodniowe</li>
          </ul>
          <div class="current-badge">Aktualny plan</div>
        </div>

        <div class="plan-card premium featured">
          <h3>Plan Premium</h3>
          <div class="price">19 PLN<span>/miesiąc</span></div>
          <ul class="features-list">
            <li>✅ Wszystko z planu Free</li>
            <li>✅ 🤖 AI Analiza trendów</li>
            <li>✅ Sentiment analysis PL rynku</li>
            <li>✅ Zaawansowane alerty</li>
            <li>✅ Raporty tygodniowe email</li>
            <li>✅ Priorytetowe wsparcie</li>
          </ul>
          <button @click="upgradeSubscription" class="btn btn-primary btn-large" :disabled="loading">
            {{ loading ? 'Przetwarzanie...' : 'Przejdź na Premium' }}
          </button>
        </div>
      </div>

      <div class="premium-preview">
        <h3>🤖 Przykład AI Analizy Trendów</h3>
        <div class="preview-card">
          <div class="preview-header">
            <img src="https://assets.coingecko.com/coins/images/1/small/bitcoin.png" alt="Bitcoin" class="crypto-logo">
            <div class="crypto-info">
              <div class="crypto-name">Bitcoin</div>
              <div class="crypto-symbol">BTC</div>
            </div>
            <div class="trend-emoji">📈</div>
          </div>
          
          <div class="preview-stats">
            <div class="stat">
              <div class="stat-value positive">+0.42</div>
              <div class="stat-label">Sentiment (24h)</div>
            </div>
            <div class="stat">
              <div class="stat-value">127</div>
              <div class="stat-label">Wzmianki</div>
            </div>
            <div class="stat">
              <div class="stat-value">85%</div>
              <div class="stat-label">Pewność</div>
            </div>
          </div>
          
          <div class="preview-sources">
            <small>Źródła: Reddit +0.51, Twitter +0.38, Bitcoin.pl +0.29</small>
          </div>
          
          <div class="preview-overlay">
            <div class="upgrade-prompt">
              <span class="lock-icon">🔒</span>
              <p>Upgrade do Premium aby zobaczyć pełne analizy!</p>
            </div>
          </div>
        </div>
      </div>

      <div class="why-premium">
        <h3>Dlaczego warto?</h3>
        <div class="benefits-grid">
          <div class="benefit-item">
            <div class="benefit-icon">🎯</div>
            <h4>Lepsze decyzje inwestycyjne</h4>
            <p>AI analizuje sentiment polskiego rynku krypto - pierwsza taka usługa w Polsce</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">⏰</div>
            <h4>Oszczędność czasu</h4>
            <p>Nie musisz ręcznie przeglądać forów i social mediów - AI robi to za Ciebie</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">📊</div>
            <h4>Lokalne insights</h4>
            <p>Analiza polskich źródeł daje unikalny wgląd w nastroje lokalnego rynku</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SubscriptionComponent',
  props: {
    user: {
      type: Object,
      required: true
    },
    isPremium: {
      type: Boolean,
      default: false
    }
  },
  
  data() {
    return {
      loading: false
    }
  },
  
  methods: {
    async upgradeSubscription() {
      this.loading = true;
      
      try {
        const response = await axios.post('/subscription/upgrade');
        
        if (response.data.success) {
          this.showSuccess(response.data.message);
          // Reload page to update premium status
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        }
      } catch (error) {
        this.showError('Błąd podczas upgrade\'u subskrypcji');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    
    async cancelSubscription() {
      if (!confirm('Czy na pewno chcesz anulować subskrypcję Premium? Stracisz dostęp do AI analizy trendów.')) {
        return;
      }
      
      this.loading = true;
      
      try {
        const response = await axios.post('/subscription/cancel');
        
        if (response.data.success) {
          this.showSuccess(response.data.message);
          // Reload page to update premium status
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        }
      } catch (error) {
        this.showError('Błąd podczas anulowania subskrypcji');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('pl-PL');
    },
    
    showSuccess(message) {
      alert(message);
    },
    
    showError(message) {
      alert(message);
    }
  }
}
</script>