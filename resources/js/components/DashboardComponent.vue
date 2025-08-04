<template>
  <div class="dashboard-content">
    <!-- Portfolio Summary Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatCurrency(portfolioValue) }}</div>
          <div class="stat-label">Wartość Portfolio</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <div class="stat-value">{{ portfolioCount }}</div>
          <div class="stat-label">Kryptowaluty</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">🔔</div>
        <div class="stat-content">
          <div class="stat-value">{{ alertsCount }}</div>
          <div class="stat-label">Aktywne Alerty</div>
        </div>
      </div>
    </div>

    <!-- Top Cryptocurrencies -->
    <div class="dashboard-section">
      <h2>Top Kryptowaluty</h2>
      <div class="crypto-list">
        <div v-for="crypto in topCryptos" :key="crypto.id" class="crypto-item">
          <img :src="crypto.image" :alt="crypto.name" class="crypto-logo">
          <div class="crypto-info">
            <div class="crypto-name">{{ crypto.name }}</div>
            <div class="crypto-symbol">{{ crypto.symbol }}</div>
          </div>
          <div class="crypto-price">
            <div class="price">{{ formatCurrency(crypto.current_price_pln) }}</div>
            <div class="change" :class="{ 'positive': crypto.price_change_percentage_24h > 0, 'negative': crypto.price_change_percentage_24h < 0 }">
              {{ crypto.price_change_percentage_24h > 0 ? '+' : '' }}{{ crypto.price_change_percentage_24h?.toFixed(2) }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Trend Analysis (Premium) -->
    <div v-if="isPremium && trendAnalyses.length > 0" class="dashboard-section">
      <h2>🤖 Najnowsze Analizy AI</h2>
      <div class="trend-list">
        <div v-for="analysis in trendAnalyses" :key="analysis.id" class="trend-item">
          <div class="trend-crypto">
            <span class="crypto-symbol">{{ analysis.cryptocurrency.symbol }}</span>
            <span class="trend-emoji">{{ getTrendEmoji(analysis.trend_direction) }}</span>
          </div>
          <div class="trend-details">
            <div class="trend-direction" :class="'trend-' + analysis.trend_direction">
              {{ getTrendLabel(analysis.trend_direction) }}
            </div>
            <div class="trend-confidence">Pewność: {{ analysis.confidence_score }}%</div>
            <div class="trend-mentions">{{ analysis.mention_count }} wzmianek</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Premium Upgrade CTA -->
    <div v-if="!isPremium" class="premium-cta">
      <div class="cta-content">
        <h3>🚀 Odkryj AI Analizę Trendów</h3>
        <p>Uzyskaj dostęp do sentiment analysis polskiego rynku krypto</p>
        <a href="/subscription" class="btn btn-primary">Przejdź na Premium - 19 PLN/miesiąc</a>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <a href="/portfolio" class="action-card">
        <div class="action-icon">📊</div>
        <div class="action-title">Zarządzaj Portfolio</div>
        <div class="action-desc">Dodaj nowe kryptowaluty</div>
      </a>
      
      <a href="/alerts" class="action-card">
        <div class="action-icon">🔔</div>
        <div class="action-title">Ustaw Alerty</div>
        <div class="action-desc">Powiadomienia o cenach</div>
      </a>
      
      <a v-if="isPremium" href="/trends" class="action-card">
        <div class="action-icon">🤖</div>
        <div class="action-title">Analiza AI</div>
        <div class="action-desc">Trendy i sentiment</div>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DashboardComponent',
  props: {
    portfolioValue: {
      type: Number,
      default: 0
    },
    portfolioCount: {
      type: Number,
      default: 0
    },
    alertsCount: {
      type: Number,
      default: 0
    },
    topCryptos: {
      type: Array,
      default: () => []
    },
    trendAnalyses: {
      type: Array,
      default: () => []
    },
    isPremium: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN'
      }).format(value || 0);
    },
    
    getTrendEmoji(direction) {
      const emojis = {
        'up': '📈',
        'down': '📉',
        'neutral': '➡️'
      };
      return emojis[direction] || '➡️';
    },
    
    getTrendLabel(direction) {
      const labels = {
        'up': 'Wzrostowy',
        'down': 'Spadkowy',
        'neutral': 'Neutralny'
      };
      return labels[direction] || 'Neutralny';
    }
  },
  
  mounted() {
    // Refresh data every 5 minutes
    setInterval(() => {
      window.location.reload();
    }, 300000);
  }
}
</script>