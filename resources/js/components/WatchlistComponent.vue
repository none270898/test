<template>
  <div class="watchlist-component">
    <!-- Header with Add Button -->
    <div class="watchlist-header">
      <h3>🎯 My Crypto Watchlist</h3>
      <div class="header-actions">
        <button @click="showAddModal = true" class="btn btn-primary">
          <span class="btn-icon">+</span>
          Add Crypto
        </button>
        <button
          @click="showDiscovery = !showDiscovery"
          class="btn btn-secondary"
        >
          <span class="btn-icon">🔍</span>
          {{ showDiscovery ? "Hide" : "Discover" }}
        </button>
      </div>
    </div>

    <!-- Discovery Section -->
    <div v-if="showDiscovery" class="discovery-section">
      <discovery-component @crypto-added="onCryptoAdded"></discovery-component>
    </div>

    <!-- Watchlist Content -->
    <div class="watchlist-content">
      <div v-if="loading && watchlist.length === 0" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading your watchlist...</p>
      </div>

      <div v-else-if="watchlist.length === 0" class="empty-state">
        <div class="empty-icon">🎯</div>
        <h3>Your watchlist is empty</h3>
        <p>
          Add cryptocurrencies to track their sentiment and get personalized
          insights
        </p>
        <button @click="initializeDefaultWatchlist" class="btn btn-primary">
          Add Popular Cryptos
        </button>
      </div>

      <div v-else class="watchlist-grid">
        <div
          v-for="item in watchlist"
          :key="item.cryptocurrency.id"
          class="watchlist-card"
          :class="getWatchlistCardClass(item)"
        >
          <!-- Card Header -->
          <div class="card-header">
            <div class="crypto-info">
              <img
                :src="item.cryptocurrency.image"
                :alt="item.cryptocurrency.name"
                class="crypto-icon"
                @error="handleImageError"
              />
              <div class="crypto-details">
                <h4>{{ item.cryptocurrency.name }}</h4>
                <span class="crypto-symbol">{{
                  item.cryptocurrency.symbol.toUpperCase()
                }}</span>
              </div>
            </div>

            <div class="card-actions">
              <button
                @click="showCryptoHistory(item.cryptocurrency)"
                class="btn btn-small btn-ghost"
                title="View History"
              >
                📈
              </button>
              <button
                @click="removeCrypto(item)"
                class="btn btn-small btn-ghost btn-danger"
                title="Remove from Watchlist"
              >
                ×
              </button>
            </div>
          </div>

          <!-- Price Info -->
          <div class="price-section">
            <div class="current-price">
              {{ formatPLN(item.cryptocurrency.current_price_pln) }}
            </div>
            <div
              class="price-change"
              :class="getPriceChangeClass(item.cryptocurrency.price_change_24h)"
            >
              {{ formatPercent(item.cryptocurrency.price_change_24h) }}% 24h
            </div>
          </div>

          <!-- Sentiment Info -->
          <div class="sentiment-section">
            <div class="sentiment-row">
              <label>Sentiment</label>
              <div class="sentiment-value">
                <span class="sentiment-emoji">{{ item.emoji }}</span>
                <span
                  class="sentiment-score"
                  :class="getSentimentClass(item.sentiment_avg)"
                >
                  {{ formatSentiment(item.sentiment_avg) }}
                </span>
              </div>
            </div>

            <div class="metrics-row">
              <div class="metric">
                <label>Mentions</label>
                <span class="metric-value">{{ item.mention_count }}</span>
              </div>
              <div class="metric">
                <label>Confidence</label>
                <span class="metric-value">{{ item.confidence_score }}%</span>
              </div>
            </div>

            <!-- Sentiment Change -->
            <div v-if="item.sentiment_change !== 0" class="sentiment-change">
              <span class="change-label">24h change:</span>
              <span
                class="change-value"
                :class="getSentimentClass(item.sentiment_change)"
              >
                {{ formatSentimentChange(item.sentiment_change) }}
              </span>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer">
            <span class="update-time">{{ item.analysis_time }}</span>
            <div class="notification-toggle">
              <label class="toggle-switch">
                <input
                  type="checkbox"
                  :checked="item.notifications_enabled"
                  @change="toggleNotifications(item, $event)"
                />
                <span class="toggle-slider"></span>
              </label>
              <span class="toggle-label">Alerts</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Crypto Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click="closeAddModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Add to Watchlist</h3>
          <button @click="closeAddModal" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <div class="search-section">
            <input
              v-model="searchQuery"
              @input="searchCryptocurrencies"
              placeholder="Search cryptocurrencies..."
              class="search-input"
              autofocus
            />

            <div v-if="searchResults.length > 0" class="search-results">
              <div
                v-for="crypto in searchResults"
                :key="crypto.id"
                class="search-result-item"
                :class="{ 'already-added': crypto.is_watchlisted }"
                @click="!crypto.is_watchlisted && addToWatchlist(crypto)"
              >
                <img
                  :src="crypto.image"
                  :alt="crypto.name"
                  class="crypto-icon-small"
                />
                <div class="crypto-info">
                  <span class="crypto-name">{{ crypto.name }}</span>
                  <span class="crypto-symbol">{{
                    crypto.symbol.toUpperCase()
                  }}</span>
                </div>
                <div class="crypto-metrics">
                  <div class="price">
                    {{ formatPLN(crypto.current_price_pln) }}
                  </div>
                  <div class="mentions">
                    {{ crypto.daily_mentions }} mentions
                  </div>
                </div>
                <div class="action-area">
                  <span v-if="crypto.is_watchlisted" class="already-added-label"
                    >✓ Added</span
                  >
                  <button v-else class="btn btn-small btn-primary">Add</button>
                </div>
              </div>
            </div>

            <div v-else-if="searchQuery.length > 2" class="no-results">
              <p>No cryptocurrencies found matching "{{ searchQuery }}"</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Crypto History Modal -->
    <div
      v-if="selectedCryptoHistory"
      class="modal-overlay"
      @click="closeCryptoHistory"
    >
      <div class="modal-content history-modal" @click.stop>
        <div class="modal-header">
          <div class="crypto-header">
            <img
              :src="selectedCryptoHistory.cryptocurrency.image"
              class="crypto-icon"
            />
            <div>
              <h3>{{ selectedCryptoHistory.cryptocurrency.name }} History</h3>
              <p>
                {{ selectedCryptoHistory.cryptocurrency.symbol.toUpperCase() }}
                sentiment over time
              </p>
            </div>
          </div>
          <button @click="closeCryptoHistory" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <crypto-history-component
            :coin-gecko-id="selectedCryptoHistory.cryptocurrency.coingecko_id"
            :crypto-name="selectedCryptoHistory.cryptocurrency.name"
          ></crypto-history-component>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "WatchlistComponent",
  data() {
    return {
      watchlist: [],
      loading: false,
      showAddModal: false,
      showDiscovery: false,
      searchQuery: "",
      searchResults: [],
      searchTimeout: null,
      selectedCryptoHistory: null,
    };
  },
  async mounted() {
    await this.loadWatchlist();
  },
  methods: {
    async loadWatchlist() {
      this.loading = true;
      try {
        const response = await window.axios.get("/api/watchlist");
        this.watchlist = response.data.watchlist;
        this.$emit("watchlist-updated", response.data.total_count);
      } catch (error) {
        console.error("Error loading watchlist:", error);
        this.showError("Failed to load watchlist");
      } finally {
        this.loading = false;
      }
    },

    async searchCryptocurrencies() {
      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        return;
      }

      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(async () => {
        try {
          const response = await window.axios.get("/api/discovery/search", {
            params: { q: this.searchQuery },
          });
          this.searchResults = response.data;
        } catch (error) {
          console.error("Search error:", error);
          this.searchResults = [];
        }
      }, 300);
    },

    async addToWatchlist(crypto) {
      try {
        await window.axios.post("/api/watchlist", {
          cryptocurrency_id: crypto.id,
          notifications_enabled: true,
        });

        this.showSuccess(`${crypto.name} added to watchlist`);
        await this.loadWatchlist();

        // Update search results to reflect the change
        crypto.is_watchlisted = true;
      } catch (error) {
        console.error("Error adding to watchlist:", error);
        this.showError("Failed to add to watchlist");
      }
    },

    async removeCrypto(watchlistItem) {
      const cryptoName = watchlistItem.cryptocurrency.name;

      if (!confirm(`Remove ${cryptoName} from your watchlist?`)) {
        return;
      }

      try {
        // Użyj ID z watchlistItem
        await window.axios.delete(`/api/watchlist/${watchlistItem.id}`);

        this.showSuccess(`${cryptoName} removed from watchlist`);
        await this.loadWatchlist();
      } catch (error) {
        console.error("Error removing from watchlist:", error);
        this.showError("Failed to remove from watchlist");
      }
    },

    async toggleNotifications(watchlistItem, event) {
      try {
        // Użyj ID z watchlistItem
        await window.axios.put(`/api/watchlist/${watchlistItem.id}`, {
          notifications_enabled: event.target.checked,
        });

        const status = event.target.checked ? "enabled" : "disabled";
        this.showSuccess(
          `Notifications ${status} for ${watchlistItem.cryptocurrency.name}`
        );
      } catch (error) {
        console.error("Error updating notifications:", error);
        this.showError("Failed to update notification settings");
        // Revert checkbox
        event.target.checked = !event.target.checked;
      }
    },

    async initializeDefaultWatchlist() {
      try {
        // Get top 10 cryptos and add them
        const response = await window.axios.get(
          "/api/discovery/trending?limit=10"
        );
        const topCryptos = response.data.trending.slice(0, 10);

        const cryptoIds = topCryptos.map((crypto) => crypto.id);
        await window.axios.post("/api/watchlist/bulk-add", {
          cryptocurrency_ids: cryptoIds,
          notifications_enabled: true,
        });

        this.showSuccess("Added popular cryptocurrencies to your watchlist!");
        await this.loadWatchlist();
      } catch (error) {
        console.error("Error initializing watchlist:", error);
        this.showError("Failed to initialize watchlist");
      }
    },

    showCryptoHistory(crypto) {
      this.selectedCryptoHistory = { cryptocurrency: crypto };
    },

    closeCryptoHistory() {
      this.selectedCryptoHistory = null;
    },

    onCryptoAdded(crypto) {
      this.loadWatchlist();
      this.showSuccess(`${crypto.name} added to watchlist`);
    },

    closeAddModal() {
      this.showAddModal = false;
      this.searchQuery = "";
      this.searchResults = [];
    },

    getWatchlistCardClass(item) {
      return {
        "card-bullish": item.trend_direction === "up",
        "card-bearish": item.trend_direction === "down",
        "card-neutral": item.trend_direction === "neutral",
      };
    },

    getSentimentClass(sentiment) {
      if (sentiment > 0.1) return "sentiment-positive";
      if (sentiment < -0.1) return "sentiment-negative";
      return "sentiment-neutral";
    },

    getPriceChangeClass(change) {
      if (change > 0) return "price-positive";
      if (change < 0) return "price-negative";
      return "price-neutral";
    },

    formatPLN(amount) {
      return parseFloat(amount || 0).toLocaleString("pl-PL", {
        style: "currency",
        currency: "PLN",
      });
    },

    formatPercent(percent) {
      const value = parseFloat(percent || 0);
      return value > 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
    },

    formatSentiment(score) {
      const value = parseFloat(score || 0);
      return value > 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
    },

    formatSentimentChange(change) {
      const value = parseFloat(change || 0);
      return value > 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
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
.watchlist-component {
  width: 100%;
}

.watchlist-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.watchlist-header h3 {
  margin: 0;
  color: #1e293b;
  font-size: 1.5rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.discovery-section {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 2px dashed #e2e8f0;
}

.watchlist-content {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.watchlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.watchlist-card {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  background: white;
  transition: all 0.3s ease;
  position: relative;
}

.watchlist-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}

.card-bullish {
  border-color: #10b981;
  background: linear-gradient(
    135deg,
    rgba(16, 185, 129, 0.03),
    rgba(16, 185, 129, 0.01)
  );
}

.card-bearish {
  border-color: #ef4444;
  background: linear-gradient(
    135deg,
    rgba(239, 68, 68, 0.03),
    rgba(239, 68, 68, 0.01)
  );
}

.card-neutral {
  border-color: #6b7280;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.crypto-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.crypto-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.crypto-icon-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.crypto-details h4 {
  margin: 0;
  font-size: 1.1rem;
  color: #1e293b;
}

.crypto-symbol {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 500;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.price-section {
  margin-bottom: 1rem;
}

.current-price {
  font-size: 1.25rem;
  font-weight: bold;
  color: #1e293b;
}

.price-change {
  font-size: 0.9rem;
  font-weight: 600;
  margin-top: 0.25rem;
}

.price-positive {
  color: #10b981;
}

.price-negative {
  color: #ef4444;
}

.price-neutral {
  color: #6b7280;
}

.sentiment-section {
  margin-bottom: 1rem;
}

.sentiment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.sentiment-row label {
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
}

.sentiment-value {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sentiment-emoji {
  font-size: 1.2rem;
}

.sentiment-score {
  font-weight: 600;
  font-size: 1rem;
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

.metrics-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.metric label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-weight: 600;
  color: #1e293b;
}

.sentiment-change {
  margin-top: 0.75rem;
  font-size: 0.85rem;
}

.change-label {
  color: #64748b;
}

.change-value {
  font-weight: 600;
  margin-left: 0.5rem;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.update-time {
  font-size: 0.8rem;
  color: #64748b;
}

.notification-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: 0.3s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: #6366f1;
}

input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.toggle-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
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

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 0;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.history-modal {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 0;
  margin-bottom: 1.5rem;
}

.modal-header h3 {
  margin: 0;
  color: #1e293b;
}

.crypto-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.crypto-header h3 {
  margin: 0;
  color: #1e293b;
}

.crypto-header p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  color: #64748b;
  cursor: pointer;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f1f5f9;
  color: #334155;
}

.modal-body {
  padding: 0 2rem 2rem;
}

.search-input {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  margin-bottom: 1rem;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-results {
  max-height: 400px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-result-item:hover:not(.already-added) {
  background: #f8fafc;
  border-color: #6366f1;
}

.search-result-item.already-added {
  background: #f0fdf4;
  border-color: #10b981;
  cursor: default;
}

.crypto-info {
  flex: 1;
}

.crypto-name {
  display: block;
  font-weight: 600;
  color: #1e293b;
}

.crypto-metrics {
  text-align: right;
  margin-right: 1rem;
}

.crypto-metrics .price {
  font-weight: 600;
  color: #1e293b;
}

.crypto-metrics .mentions {
  font-size: 0.8rem;
  color: #64748b;
}

.action-area {
  min-width: 80px;
  text-align: right;
}

.already-added-label {
  color: #10b981;
  font-weight: 600;
  font-size: 0.9rem;
}

.no-results {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

/* Button Styles */
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
  gap: 0.5rem;
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

.btn-ghost {
  background: transparent;
  color: #64748b;
  padding: 0.5rem;
}

.btn-ghost:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-danger:hover {
  color: #ef4444;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 1.2rem;
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

.empty-state h3 {
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #64748b;
  margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
  .watchlist-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .header-actions {
    justify-content: stretch;
  }

  .watchlist-grid {
    grid-template-columns: 1fr;
  }

  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .card-actions {
    justify-content: flex-end;
  }

  .metrics-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
}
</style>