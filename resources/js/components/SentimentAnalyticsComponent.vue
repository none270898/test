
<template>
  <div class="sentiment-analytics">
    <!-- Header Stats -->
    <div class="analytics-stats">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
          <h3>Total Analyses</h3>
          <p class="stat-value">{{ stats.total_analyses }}</p>
          <span class="stat-label">last 24h</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
          <h3>Mentions</h3>
          <p class="stat-value">{{ stats.total_mentions }}</p>
          <span class="stat-label">discussions tracked</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-info">
          <h3>Avg Sentiment</h3>
          <p
            class="stat-value"
            :class="getSentimentClass(stats.average_sentiment)"
          >
            {{ formatSentiment(stats.average_sentiment) }}
          </p>
          <span class="stat-label">market mood</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-info">
          <h3>Watchlist Items</h3>
          <p class="stat-value">{{ stats.watchlist_count }}</p>
          <span class="stat-label">cryptos tracked</span>
        </div>
      </div>
    </div>

    <!-- Watchlist Analysis -->
    <div class="analytics-section">
      <div class="section-header">
        <h3>🎯 My Watchlist Analysis</h3>
        <button
          @click="refreshData"
          :disabled="loading"
          class="btn btn-small btn-secondary"
        >
          <span v-if="loading">Refreshing...</span>
          <span v-else>🔄 Refresh</span>
        </button>
      </div>

      <div v-if="loading && trendSummary.length === 0" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Analyzing your watchlist sentiment...</p>
      </div>

      <div v-else-if="trendSummary.length === 0" class="empty-state">
        <div class="empty-icon">🎯</div>
        <h3>No watchlist analysis available</h3>
        <p>
          Add cryptocurrencies to your watchlist to see AI sentiment analysis
        </p>
        <router-link to="/dashboard" class="btn btn-primary"
          >Manage Watchlist</router-link
        >
      </div>

      <div v-else class="trends-grid">
        <div
          v-for="trend in trendSummary"
          :key="trend.cryptocurrency.symbol"
          class="trend-card"
          :class="getTrendCardClass(trend.trend_direction)"
          @click="showTrendDetails(trend)"
        >
          <div class="trend-header">
            <div class="crypto-info">
              <img
                :src="trend.cryptocurrency.image"
                :alt="trend.cryptocurrency.name"
                class="crypto-icon"
                @error="handleImageError"
              />
              <div class="crypto-details">
                <h4>{{ trend.cryptocurrency.name }}</h4>
                <span class="crypto-symbol">{{
                  trend.cryptocurrency.symbol.toUpperCase()
                }}</span>
              </div>
            </div>
            <div class="trend-emoji">{{ trend.emoji }}</div>
          </div>

          <div class="trend-metrics">
            <div class="metric">
              <label>Sentiment</label>
              <span
                class="sentiment-score"
                :class="getSentimentClass(trend.sentiment_avg)"
              >
                {{ formatSentiment(trend.sentiment_avg) }}
              </span>
              <div v-if="trend.sentiment_change !== 0" class="metric-change">
                <span :class="getSentimentClass(trend.sentiment_change)">
                  {{ formatSentimentChange(trend.sentiment_change) }}
                </span>
              </div>
            </div>

            <div class="metric">
              <label>Mentions</label>
              <span class="mention-count">{{ trend.mention_count }}</span>
            </div>

            <div class="metric">
              <label>Confidence</label>
              <div class="confidence-bar">
                <div
                  class="confidence-fill"
                  :style="{ width: trend.confidence_score + '%' }"
                ></div>
                <span class="confidence-text"
                  >{{ trend.confidence_score }}%</span
                >
              </div>
            </div>
          </div>

          <div class="trend-footer">
            <span class="analysis-time">{{ trend.analysis_time }}</span>
            <span
              class="trend-direction"
              :class="getTrendClass(trend.trend_direction)"
            >
              {{ getTrendText(trend.trend_direction) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Market Comparison -->
    <div v-if="trendingComparison.length > 0" class="analytics-section">
      <div class="section-header">
        <h3>📊 Market Comparison</h3>
        <p class="section-subtitle">
          See how your watchlist compares to trending cryptocurrencies
        </p>
      </div>

      <div class="comparison-grid">
        <div
          v-for="crypto in trendingComparison.slice(0, 6)"
          :key="`trending-${crypto.symbol}`"
          class="comparison-card"
        >
          <div class="comparison-header">
            <img
              :src="crypto.image"
              :alt="crypto.name"
              class="crypto-icon-small"
            />
            <div class="crypto-details">
              <span class="crypto-name">{{ crypto.name }}</span>
              <span class="crypto-symbol">{{
                crypto.symbol.toUpperCase()
              }}</span>
            </div>
            <div class="trending-rank">
              #{{ trendingComparison.indexOf(crypto) + 1 }}
            </div>
          </div>

          <div class="comparison-metrics">
            <div class="comparison-metric">
              <span
                class="metric-value"
                :class="getSentimentClass(crypto.current_sentiment)"
              >
                {{ formatSentiment(crypto.current_sentiment) }}
              </span>
              <span class="metric-label">sentiment</span>
            </div>
            <div class="comparison-metric">
              <span class="metric-value">{{ crypto.daily_mentions }}</span>
              <span class="metric-label">mentions</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="analytics-section">
      <div class="section-header">
        <h3>📈 Recent Activity</h3>
        <select
          v-model="selectedTimeframe"
          @change="refreshData"
          class="timeframe-select"
        >
          <option value="6">Last 6 hours</option>
          <option value="24">Last 24 hours</option>
          <option value="48">Last 48 hours</option>
        </select>
      </div>

      <div class="activity-grid">
        <div
          v-for="(count, source) in stats.recent_activity"
          :key="source"
          class="activity-card"
        >
          <div class="activity-icon">{{ getSourceIcon(source) }}</div>
          <div class="activity-info">
            <h4>{{ getSourceName(source) }}</h4>
            <p class="activity-count">{{ count }} mentions</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Trend Details Modal -->
    <div v-if="selectedTrend" class="modal-overlay" @click="closeTrendDetails">
      <div class="modal-content trend-modal" @click.stop>
        <div class="modal-header">
          <div class="trend-modal-title">
            <img
              :src="selectedTrend.cryptocurrency.image"
              :alt="selectedTrend.cryptocurrency.name"
              class="crypto-icon"
            />
            <div>
              <h3>{{ selectedTrend.cryptocurrency.name }} Analysis</h3>
              <p>
                {{ selectedTrend.cryptocurrency.symbol.toUpperCase() }} •
                {{ selectedTrend.analysis_time }}
              </p>
            </div>
          </div>
          <button @click="closeTrendDetails" class="close-btn">&times;</button>
        </div>

        <div class="trend-modal-content">
          <div class="trend-overview">
            <div class="overview-metric">
              <label>Overall Sentiment</label>
              <span
                class="sentiment-large"
                :class="getSentimentClass(selectedTrend.sentiment_avg)"
              >
                {{ formatSentiment(selectedTrend.sentiment_avg) }}
              </span>
            </div>

            <div class="overview-metric">
              <label>Total Mentions</label>
              <span class="mentions-large">{{
                selectedTrend.mention_count
              }}</span>
            </div>

            <div class="overview-metric">
              <label>Trend Direction</label>
              <span
                class="trend-large"
                :class="getTrendClass(selectedTrend.trend_direction)"
              >
                {{ selectedTrend.emoji }}
                {{ getTrendText(selectedTrend.trend_direction) }}
              </span>
            </div>
          </div>

          <div class="confidence-section">
            <label>AI Confidence Score</label>
            <div class="confidence-bar-large">
              <div
                class="confidence-fill"
                :style="{ width: selectedTrend.confidence_score + '%' }"
              ></div>
              <span class="confidence-text-large"
                >{{ selectedTrend.confidence_score }}% Confidence</span
              >
            </div>
            <p class="confidence-explanation">
              Based on {{ selectedTrend.mention_count }} mentions from Polish
              crypto communities
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SentimentAnalyticsComponent",
  data() {
    return {
      loading: false,
      trendSummary: [],
      trendingComparison: [],
      stats: {
        total_analyses: 0,
        total_mentions: 0,
        average_sentiment: 0,
        recent_activity: {},
        watchlist_count: 0,
      },
      selectedTrend: null,
      selectedTimeframe: "24",
    };
  },
  async mounted() {
    await this.loadAnalyticsData();
  },
  methods: {
    async loadAnalyticsData() {
      this.loading = true;
      try {
        const response = await window.axios.get("/api/sentiment/dashboard");
        this.trendSummary = response.data.watchlist_summary || [];
        this.trendingComparison = response.data.trending_comparison || [];
        this.stats = response.data.stats;
      } catch (error) {
        console.error("Error loading analytics data:", error);
        this.showError("Failed to load AI analytics data");
      } finally {
        this.loading = false;
      }
    },

    async refreshData() {
      await this.loadAnalyticsData();
      this.showSuccess("Analytics data refreshed");
    },

    showTrendDetails(trend) {
      this.selectedTrend = trend;
    },

    closeTrendDetails() {
      this.selectedTrend = null;
    },

    getSentimentClass(sentiment) {
      if (sentiment > 0.1) return "sentiment-positive";
      if (sentiment < -0.1) return "sentiment-negative";
      return "sentiment-neutral";
    },

    getTrendClass(direction) {
      return `trend-${direction}`;
    },

    getTrendCardClass(direction) {
      return {
        "trend-card-up": direction === "up",
        "trend-card-down": direction === "down",
        "trend-card-neutral": direction === "neutral",
      };
    },

    formatSentiment(score) {
      const value = parseFloat(score);
      if (value > 0) return `+${value.toFixed(2)}`;
      return value.toFixed(2);
    },
    formatSentimentChange(change) {
      const value = parseFloat(change);
      if (value > 0) return `+${value.toFixed(2)}`;
      return value.toFixed(2);
    },

    getTrendText(direction) {
      const texts = {
        up: "Bullish",
        down: "Bearish",
        neutral: "Neutral",
      };
      return texts[direction] || "Unknown";
    },

    getSourceIcon(source) {
      const icons = {
        reddit: "📱",
        twitter: "🐦",
        forum: "💬",
        news: "📰",
      };
      return icons[source] || "📊";
    },

    getSourceName(source) {
      const names = {
        reddit: "Reddit",
        twitter: "Twitter",
        forum: "Forums",
        news: "News",
      };
      return names[source] || source;
    },

    handleImageError(event) {
      event.target.src =
        "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2ZjEiLz4KPHR5cGU+4oKfPC90ZXh0Pgo8L3N2Zz4K";
    },

    showSuccess(message) {
      console.log("Success:", message);
    },

    showError(message) {
      console.error("Error:", message);
    },
  },
};
</script>

<style scoped>
.sentiment-analytics {
  width: 100%;
}

.analytics-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.analytics-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  color: #1e293b;
  font-size: 1.25rem;
}

.section-subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.comparison-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  transition: all 0.3s ease;
}

.comparison-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.comparison-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.crypto-icon-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.crypto-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
}

.trending-rank {
  margin-left: auto;
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: bold;
}

.comparison-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.comparison-metric {
  text-align: center;
}

.metric-change {
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .comparison-grid {
    grid-template-columns: 1fr;
  }

  .comparison-metrics {
    grid-template-columns: 1fr;
  }
}

.timeframe-select {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-size: 0.9rem;
}

.trends-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.trend-card {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.8),
    rgba(248, 250, 252, 0.8)
  );
}

.trend-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.trend-card-up {
  border-color: #10b981;
  background: linear-gradient(
    135deg,
    rgba(16, 185, 129, 0.05),
    rgba(16, 185, 129, 0.02)
  );
}

.trend-card-down {
  border-color: #ef4444;
  background: linear-gradient(
    135deg,
    rgba(239, 68, 68, 0.05),
    rgba(239, 68, 68, 0.02)
  );
}

.trend-card-neutral {
  border-color: #6b7280;
}

.trend-header {
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

.trend-emoji {
  font-size: 1.5rem;
}

.trend-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.metric label {
  display: block;
  font-size: 0.7rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.sentiment-score {
  font-weight: 600;
  font-size: 0.9rem;
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

.mention-count {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
}

.confidence-bar {
  position: relative;
  background: #f1f5f9;
  border-radius: 20px;
  height: 16px;
  overflow: hidden;
}

.confidence-fill {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  height: 100%;
  transition: width 0.3s ease;
}

.confidence-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.7rem;
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.trend-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.analysis-time {
  color: #64748b;
}

.trend-direction {
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  text-transform: uppercase;
}

.trend-up {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.trend-down {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.trend-neutral {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.activity-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.activity-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.activity-icon {
  font-size: 2rem;
}

.activity-info h4 {
  margin: 0;
  color: #1e293b;
  font-size: 1rem;
}

.activity-count {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.trend-modal {
  max-width: 600px;
  width: 90%;
}

.trend-modal-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.trend-modal-title h3 {
  margin: 0;
  color: #1e293b;
}

.trend-modal-title p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.trend-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.overview-metric {
  text-align: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.overview-metric label {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.sentiment-large,
.mentions-large,
.trend-large {
  display: block;
  font-size: 1.5rem;
  font-weight: bold;
}

.confidence-section {
  text-align: center;
}

.confidence-section label {
  display: block;
  font-size: 1rem;
  color: #1e293b;
  margin-bottom: 1rem;
  font-weight: 600;
}

.confidence-bar-large {
  position: relative;
  background: #f1f5f9;
  border-radius: 20px;
  height: 32px;
  overflow: hidden;
  margin-bottom: 1rem;
}

.confidence-text-large {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.confidence-explanation {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0;
}

/* Loading and Empty States */
.loading-state,
.empty-state {
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
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  font-size: 0.95rem;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .analytics-stats {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }

  .trends-grid {
    grid-template-columns: 1fr;
  }

  .trend-metrics {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .activity-grid {
    grid-template-columns: 1fr;
  }

  .trend-overview {
    grid-template-columns: 1fr;
  }
}
</style>