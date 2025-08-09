// resources/js/components/DiscoveryComponent.vue

<template>
  <div class="discovery-component">
    <!-- Discovery Tabs -->
    <div class="discovery-tabs">
      <button 
        @click="activeTab = 'trending'" 
        :class="['tab-btn', { active: activeTab === 'trending' }]"
      >
        🔥 Trending
      </button>
      <button 
        @click="activeTab = 'search'" 
        :class="['tab-btn', { active: activeTab === 'search' }]"
      >
        🔍 Search
      </button>
      <button 
        v-if="isPremium"
        @click="activeTab = 'stats'" 
        :class="['tab-btn', { active: activeTab === 'stats' }]"
      >
        📊 Stats
      </button>
    </div>

    <!-- Trending Tab -->
    <div v-if="activeTab === 'trending'" class="tab-content">
      <div class="section-header">
        <h4>🔥 Trending Cryptocurrencies</h4>
        <button @click="loadTrending" :disabled="loading" class="btn btn-small btn-secondary">
          <span v-if="loading">Loading...</span>
          <span v-else>🔄 Refresh</span>
        </button>
      </div>

      <div v-if="loading && trending.length === 0" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading trending cryptocurrencies...</p>
      </div>

      <div v-else-if="trending.length === 0" class="empty-state">
        <div class="empty-icon">📈</div>
        <h3>No trending data available</h3>
        <p>AI analysis will appear here once we collect enough data</p>
        <button @click="loadTrending" class="btn btn-primary">Refresh Data</button>
      </div>

      <div v-else class="trending-grid">
        <div 
          v-for="crypto in trending" 
          :key="crypto.id"
          class="trending-card"
          :class="getTrendingCardClass(crypto)"
        >
          <div class="card-header">
            <div class="crypto-info">
              <img :src="crypto.image" :alt="crypto.name" class="crypto-icon" @error="handleImageError">
              <div class="crypto-details">
                <h4>{{ crypto.name }}</h4>
                <span class="crypto-symbol">{{ crypto.symbol.toUpperCase() }}</span>
              </div>
            </div>
            <div class="trending-badge">
              <span class="trending-score">{{ crypto.trending_score }}</span>
              <span class="trending-emoji">{{ crypto.emoji }}</span>
            </div>
          </div>

          <div class="card-metrics">
            <div class="metric-row">
              <div class="metric">
                <label>Price</label>
                <span class="metric-value">{{ formatPLN(crypto.current_price_pln) }}</span>
                <span class="price-change" :class="getPriceChangeClass(crypto.price_change_24h)">
                  {{ formatPercent(crypto.price_change_24h) }}%
                </span>
              </div>
              
              <div class="metric">
                <label>Sentiment</label>
                <span class="metric-value" :class="getSentimentClass(crypto.current_sentiment)">
                  {{ formatSentiment(crypto.current_sentiment) }}
                </span>
                <span v-if="crypto.sentiment_change_24h" class="sentiment-change" :class="getSentimentClass(crypto.sentiment_change_24h)">
                  {{ formatSentiment(crypto.sentiment_change_24h, true) }}
                </span>
              </div>
            </div>

            <div class="metric-row">
              <div class="metric">
                <label>Mentions</label>
                <span class="metric-value">{{ crypto.daily_mentions }}</span>
              </div>
              
              <div class="metric">
                <label>Confidence</label>
                <span class="metric-value">{{ crypto.confidence_score }}%</span>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <span class="updated-time">{{ crypto.updated_at }}</span>
            <div class="card-actions">
              <button 
                v-if="crypto.is_watchlisted"
                disabled
                class="btn-mini btn-success"
              >
                ✓ In Watchlist
              </button>
              <button 
                v-else-if="showAddButtons"
                @click="addToWatchlist(crypto)"
                class="btn-mini btn-primary"
              >
                + Add to Watchlist
              </button>
              <button 
                v-if="isPremium"
                @click="showHistory(crypto)"
                class="btn-mini btn-secondary"
              >
                📈 History
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Tab -->
    <div v-if="activeTab === 'search'" class="tab-content">
      <div class="search-section">
        <div class="search-input-container">
          <input 
            v-model="searchQuery"
            @input="searchCryptos"
            placeholder="Search cryptocurrencies..."
            class="search-input"
          />
          <div class="search-icon">🔍</div>
        </div>

        <div v-if="searchResults.length > 0" class="search-results">
          <div 
            v-for="crypto in searchResults" 
            :key="crypto.id"
            class="search-result-item"
          >
            <div class="result-info">
              <img :src="crypto.image" :alt="crypto.name" class="crypto-icon-small" @error="handleImageError">
              <div class="result-details">
                <h4>{{ crypto.name }}</h4>
                <span class="crypto-symbol">{{ crypto.symbol.toUpperCase() }}</span>
              </div>
            </div>
            
            <div class="result-metrics">
              <span class="price">{{ formatPLN(crypto.current_price_pln) }}</span>
              <div class="sentiment-info">
                <span v-if="crypto.daily_mentions > 0" class="mentions">{{ crypto.daily_mentions }} mentions</span>
                <span v-if="crypto.current_sentiment" class="sentiment" :class="getSentimentClass(crypto.current_sentiment)">
                  {{ formatSentiment(crypto.current_sentiment) }}
                </span>
              </div>
            </div>

            <div class="result-actions">
              <button 
                v-if="crypto.is_watchlisted"
                disabled
                class="btn-mini btn-success"
              >
                ✓ In Watchlist
              </button>
              <button 
                v-else-if="showAddButtons"
                @click="addToWatchlist(crypto)"
                class="btn-mini btn-primary"
              >
                + Add
              </button>
            </div>
          </div>
        </div>

        <div v-else-if="searchQuery && !searchLoading" class="empty-search">
          <p>No cryptocurrencies found for "{{ searchQuery }}"</p>
        </div>
      </div>
    </div>

    <!-- Stats Tab (Premium) -->
    <div v-if="activeTab === 'stats' && isPremium" class="tab-content">
      <div v-if="loadingStats" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading discovery stats...</p>
      </div>

      <div v-else class="stats-content">
        <div class="stats-overview">
          <div class="stat-card">
            <h4>{{ stats.overview?.total_cryptos_analyzed || 0 }}</h4>
            <p>Cryptos Analyzed</p>
          </div>
          <div class="stat-card">
            <h4>{{ stats.overview?.total_mentions_today || 0 }}</h4>
            <p>Total Mentions</p>
          </div>
          <div class="stat-card">
            <h4>{{ formatSentiment(stats.overview?.avg_sentiment_today) }}</h4>
            <p>Avg Sentiment</p>
          </div>
        </div>

        <div class="stats-sections">
          <div class="stats-section">
            <h4>🚀 Most Positive</h4>
            <div class="stats-list">
              <div v-for="crypto in stats.top_performers?.most_positive" :key="'pos-' + crypto.name" class="stats-item">
                <img :src="crypto.image" :alt="crypto.name" class="crypto-icon-mini" @error="handleImageError">
                <span class="crypto-name">{{ crypto.symbol.toUpperCase() }}</span>
                <span class="sentiment positive">{{ formatSentiment(crypto.current_sentiment) }}</span>
              </div>
            </div>
          </div>

          <div class="stats-section">
            <h4>📉 Most Negative</h4>
            <div class="stats-list">
              <div v-for="crypto in stats.top_performers?.most_negative" :key="'neg-' + crypto.name" class="stats-item">
                <img :src="crypto.image" :alt="crypto.name" class="crypto-icon-mini" @error="handleImageError">
                <span class="crypto-name">{{ crypto.symbol.toUpperCase() }}</span>
                <span class="sentiment negative">{{ formatSentiment(crypto.current_sentiment) }}</span>
              </div>
            </div>
          </div>

          <div class="stats-section">
            <h4>💬 Most Mentioned</h4>
            <div class="stats-list">
              <div v-for="crypto in stats.top_performers?.most_mentioned" :key="'men-' + crypto.name" class="stats-item">
                <img :src="crypto.image" :alt="crypto.name" class="crypto-icon-mini" @error="handleImageError">
                <span class="crypto-name">{{ crypto.symbol.toUpperCase() }}</span>
                <span class="mentions">{{ crypto.daily_mentions }} mentions</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
      <div class="modal-content history-modal" @click.stop>
        <div class="modal-header">
          <h3>📈 {{ selectedCrypto?.name }} History</h3>
          <button @click="showHistoryModal = false" class="close-btn">&times;</button>
        </div>
        <div class="history-content">
          <p>Historical sentiment analysis will be displayed here</p>
          <!-- This would contain a chart component -->
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DiscoveryComponent',
  props: {
    showAddButtons: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      activeTab: 'trending',
      trending: [],
      searchResults: [],
      stats: {},
      searchQuery: '',
      loading: false,
      searchLoading: false,
      loadingStats: false,
      searchTimeout: null,
      showHistoryModal: false,
      selectedCrypto: null,
      isPremium: false
    }
  },
  async mounted() {
    await this.checkPremiumStatus();
    await this.loadTrending();
  },
  methods: {
    async checkPremiumStatus() {
      try {
        const response = await window.axios.get('/api/user-status');
        this.isPremium = response.data.isPremium;
      } catch (error) {
        this.isPremium = false;
      }
    },

    async loadTrending() {
      this.loading = true;
      try {
        const response = await window.axios.get('/api/discovery/trending?limit=20');
        this.trending = response.data.trending;
      } catch (error) {
        console.error('Error loading trending:', error);
        this.showError('Failed to load trending cryptocurrencies');
      } finally {
        this.loading = false;
      }
    },

    async searchCryptos() {
      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        return;
      }

      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(async () => {
        this.searchLoading = true;
        try {
          const response = await window.axios.get('/api/discovery/search', {
            params: { q: this.searchQuery }
          });
          this.searchResults = response.data;
        } catch (error) {
          console.error('Search error:', error);
          this.searchResults = [];
        } finally {
          this.searchLoading = false;
        }
      }, 300);
    },

    async loadStats() {
      if (!this.isPremium) return;
      
      this.loadingStats = true;
      try {
        const response = await window.axios.get('/api/discovery/stats');
        this.stats = response.data;
      } catch (error) {
        console.error('Error loading stats:', error);
        this.showError('Failed to load discovery stats');
      } finally {
        this.loadingStats = false;
      }
    },

    async addToWatchlist(crypto) {
      try {
        await window.axios.post('/api/watchlist', {
          cryptocurrency_id: crypto.id,
          notifications_enabled: true
        });
        
        // Update the local state
        crypto.is_watchlisted = true;
        
        this.$emit('crypto-added', crypto);
        this.showSuccess(`${crypto.name} added to watchlist`);
      } catch (error) {
        console.error('Error adding to watchlist:', error);
        this.showError('Failed to add to watchlist');
      }
    },

    showHistory(crypto) {
      this.selectedCrypto = crypto;
      this.showHistoryModal = true;
    },

    getTrendingCardClass(crypto) {
      return {
        'trending-positive': crypto.current_sentiment > 0.1,
        'trending-negative': crypto.current_sentiment < -0.1,
        'trending-hot': crypto.trending_score > 50
      };
    },

    getSentimentClass(sentiment) {
      if (sentiment > 0.1) return 'sentiment-positive';
      if (sentiment < -0.1) return 'sentiment-negative';
      return 'sentiment-neutral';
    },

    getPriceChangeClass(change) {
      if (change > 0) return 'positive';
      if (change < 0) return 'negative';
      return 'neutral';
    },

    formatSentiment(score, showSign = false) {
      const value = parseFloat(score || 0);
      if (showSign && value > 0) return `+${value.toFixed(2)}`;
      return value.toFixed(2);
    },

    formatPLN(amount) {
      return parseFloat(amount || 0).toLocaleString('pl-PL', {
        style: 'currency',
        currency: 'PLN'
      });
    },

    formatPercent(percent) {
      const value = parseFloat(percent || 0);
      return (value > 0 ? '+' : '') + value.toFixed(2);
    },

    handleImageError(event) {
      event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2ZjEiLz4KPHR5cGU+4oKfPC90ZXh0Pgo8L3N2Zz4K';
    },

    showSuccess(message) {
      console.log('Success:', message);
    },

    showError(message) {
      console.error('Error:', message);
    }
  },

  watch: {
    activeTab(newTab) {
      if (newTab === 'stats' && this.isPremium && Object.keys(this.stats).length === 0) {
        this.loadStats();
      }
    }
  }
}
</script>

<style scoped>
.discovery-component {
  width: 100%;
}

.discovery-tabs {
  display: flex;
  border-bottom: 2px solid #e2e8f0;
  margin-bottom: 1.5rem;
}

.tab-btn {
  flex: 1;
  padding: 1rem;
  background: none;
  border: none;
  font-size: 0.95rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
  border-bottom: 3px solid transparent;
}

.tab-btn:hover {
  color: #6366f1;
  background: #f8fafc;
}

.tab-btn.active {
  color: #6366f1;
  border-bottom-color: #6366f1;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h4 {
  margin: 0;
  color: #1e293b;
  font-size: 1.25rem;
}

.trending-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.trending-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  transition: all 0.3s ease;
}

.trending-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -5px rgba(0, 0, 0, 0.1);
}

.trending-positive {
  border-color: #10b981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.02), transparent);
}

.trending-negative {
  border-color: #ef4444;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.02), transparent);
}

.trending-hot {
  border-color: #f59e0b;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), transparent);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.crypto-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.crypto-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}

.crypto-icon-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.crypto-icon-mini {
  width: 20px;
  height: 20px;
  border-radius: 50%;
}

.crypto-details h4 {
  margin: 0;
  font-size: 1rem;
  color: #1e293b;
}

.crypto-symbol {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}

.trending-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.trending-score {
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.trending-emoji {
  font-size: 1.25rem;
}

.card-metrics {
  margin-bottom: 1rem;
}

.metric-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.metric {
  flex: 1;
}

.metric label {
  display: block;
  font-size: 0.7rem;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.metric-value {
  display: block;
  font-weight: 600;
  font-size: 0.9rem;
  color: #1e293b;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
}

.updated-time {
  color: #64748b;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.search-input-container {
  position: relative;
  margin-bottom: 1.5rem;
}

.search-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-size: 1.25rem;
}

.search-results {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.search-result-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.search-result-item:hover {
  border-color: #6366f1;
  transform: translateX(4px);
}

.result-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.result-details h4 {
  margin: 0;
  font-size: 0.95rem;
  color: #1e293b;
}

.result-metrics {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.price {
  font-weight: 600;
  color: #1e293b;
}

.sentiment-info {
  display: flex;
  gap: 0.5rem;
  font-size: 0.8rem;
}

.mentions {
  color: #64748b;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  text-align: center;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.stat-card h4 {
  font-size: 2rem;
  font-weight: bold;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.stat-card p {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.stats-sections {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stats-section h4 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.stats-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.stats-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.crypto-name {
  font-weight: 600;
  color: #1e293b;
  flex: 1;
}

.btn-mini {
  padding: 0.4rem 0.8rem;
  font-size: 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover {
  background: #5b5cf1;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-success {
  background: #d1fae5;
  color: #065f46;
}

.sentiment-positive {
  color: #10b981;
}

.sentiment-negative {
  color: #ef4444;
}

.sentiment-neutral {
  color: #6b7280;
}

.positive {
  color: #10b981;
}

.negative {
  color: #ef4444;
}

/* Loading and empty states */
.loading-state,
.empty-state,
.empty-search {
  text-align: center;
  padding: 3rem 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-left: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .trending-grid {
    grid-template-columns: 1fr;
  }
  
  .metric-row {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .stats-sections {
    grid-template-columns: 1fr;
  }
  
  .search-result-item {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>