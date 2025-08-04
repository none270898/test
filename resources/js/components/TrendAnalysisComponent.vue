<template>
  <div class="trends-content">
    <div class="trends-header">
      <h2>🤖 AI Analiza Trendów</h2>
      <p>Sentiment analysis polskiego rynku kryptowalut na podstawie Reddit, Twitter i forów</p>
    </div>

    <div v-if="trendAnalyses.length === 0" class="empty-state">
      <div class="empty-icon">🤖</div>
      <h4>Brak analiz</h4>
      <p>Analizy AI są generowane automatycznie co godzinę. Sprawdź ponownie później.</p>
    </div>

    <div v-else class="trends-grid">
      <div v-for="analysis in trendAnalyses" :key="analysis.id" class="trend-card">
        <div class="trend-header">
          <img :src="analysis.cryptocurrency.image" :alt="analysis.cryptocurrency.name" class="crypto-logo">
          <div class="crypto-info">
            <div class="crypto-name">{{ analysis.cryptocurrency.name }}</div>
            <div class="crypto-symbol">{{ analysis.cryptocurrency.symbol }}</div>
          </div>
          <div class="trend-emoji">{{ getTrendEmoji(analysis.trend_direction) }}</div>
        </div>

        <div class="trend-sentiment">
          <div class="sentiment-score" :class="getSentimentClass(analysis.sentiment_avg)">
            <div class="score-value">{{ analysis.sentiment_avg.toFixed(2) }}</div>
            <div class="score-label">Średni Sentiment</div>
          </div>
          <div class="sentiment-bar">
            <div class="sentiment-fill" 
                 :style="{ width: getSentimentWidth(analysis.sentiment_avg) + '%', backgroundColor: getSentimentColor(analysis.sentiment_avg) }">
            </div>
          </div>
        </div>

        <div class="trend-stats">
          <div class="stat">
            <div class="stat-value">{{ analysis.mention_count }}</div>
            <div class="stat-label">Wzmianki (24h)</div>
          </div>
          <div class="stat">
            <div class="stat-value">{{ analysis.confidence_score }}%</div>
            <div class="stat-label">Pewność</div>
          </div>
          <div class="stat">
            <div class="stat-value" :class="'trend-' + analysis.trend_direction">
              {{ getTrendLabel(analysis.trend_direction) }}
            </div>
            <div class="stat-label">Kierunek</div>
          </div>
        </div>

        <div class="source-breakdown">
          <h4>Breakdown źródeł:</h4>
          <div class="sources">
            <div v-for="(sentiment, source) in analysis.source_breakdown" :key="source" class="source-item">
              <span class="source-name">{{ getSourceLabel(source) }}</span>
              <span class="source-sentiment" :class="getSentimentClass(sentiment)">
                {{ sentiment.toFixed(2) }}
              </span>
            </div>
          </div>
        </div>

        <div class="trend-period">
          <small>
            Analiza za okres: {{ formatDate(analysis.analysis_period_start) }} - {{ formatDate(analysis.analysis_period_end) }}
          </small>
        </div>

        <div class="trend-actions">
          <a :href="`/trends/${analysis.cryptocurrency.id}`" class="btn btn-secondary btn-small">
            Szczegóły
          </a>
        </div>
      </div>
    </div>

    <!-- AI Explanation -->
    <div class="ai-explanation">
      <h3>Jak działa nasza AI?</h3>
      <div class="explanation-grid">
        <div class="explanation-item">
          <div class="explanation-icon">🔍</div>
          <h4>Zbieranie danych</h4>
          <p>Analizujemy posty z Reddit (r/BitcoinPL, r/Polska), Twitter oraz polskie fora krypto co 30 minut</p>
        </div>
        <div class="explanation-item">
          <div class="explanation-icon">🧠</div>
          <h4>Analiza sentiment</h4>
          <p>AI ocenia tonację każdego postu w skali -1 (bardzo negatywny) do +1 (bardzo pozytywny)</p>
        </div>
        <div class="explanation-item">
          <div class="explanation-icon">📊</div>
          <h4>Wykrywanie trendów</h4>
          <p>Na podstawie średniego sentimentu i liczby wzmianek określamy kierunek trendu z poziomem pewności</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TrendAnalysisComponent',
  props: {
    trendAnalyses: {
      type: Array,
      default: () => []
    }
  },
  
  methods: {
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
    },
    
    getSentimentClass(sentiment) {
      if (sentiment > 0.3) return 'positive';
      if (sentiment < -0.3) return 'negative';
      return 'neutral';
    },
    
    getSentimentWidth(sentiment) {
      return Math.abs(sentiment) * 100;
    },
    
    getSentimentColor(sentiment) {
      if (sentiment > 0.3) return '#4caf50';
      if (sentiment < -0.3) return '#f44336';
      return '#ff9800';
    },
    
    getSourceLabel(source) {
      const labels = {
        'reddit': 'Reddit',
        'twitter': 'Twitter',
        'bitcoin_pl': 'Bitcoin.pl',
        'bithub_pl': 'BitHub.pl'
      };
      return labels[source] || source;
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('pl-PL');
    }
  }
}
</script>
