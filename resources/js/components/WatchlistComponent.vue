<template>
  <div class="watchlist-component">
    <!-- DODANE: Premium upgrade banner -->
    <div v-if="!isPremium && watchlistLimits.current_count >= 12" class="limit-warning-banner">
      <div class="banner-content">
        <div class="banner-icon">🎯</div>
        <div class="banner-text">
          <h4>Zbliżasz się do limitu watchlist!</h4>
          <p>{{ watchlistLimits.current_count }}/{{ watchlistLimits.watchlist_limit }} pozycji + brak AI sentiment</p>
        </div>
        <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
          Unlock AI Features
        </button>
      </div>
    </div>

    <!-- Header with Add Button -->
    <div class="watchlist-header">
      <h3>🎯 My Crypto Watchlist</h3>
      <div class="header-actions">
        <button 
          @click="showAddModal = true" 
          :disabled="!canAddMore"
          class="btn btn-primary"
          :class="{ 'btn-disabled': !canAddMore }"
        >
          <span class="btn-icon">+</span>
          Add Crypto
          <span v-if="!isPremium" class="btn-limit">({{ watchlistLimits.current_count }}/{{ watchlistLimits.watchlist_limit }})</span>
        </button>
        <button
          @click="showDiscovery = !showDiscovery"
          class="btn btn-secondary"
        >
          <span class="btn-icon">🔍</span>
          {{ showDiscovery ? "Hide" : "Discover" }}
        </button>
        <!-- DODANE: Premium button -->
        <button 
          v-if="!isPremium"
          @click="showUpgradeModal = true"
          class="btn btn-premium btn-small"
        >
          🚀 Premium
        </button>
      </div>
    </div>

    <!-- Discovery Section -->
    <div v-if="showDiscovery" class="discovery-section">
      <discovery-component @crypto-added="onCryptoAdded"></discovery-component>
    </div>

    <!-- DODANE: Upgrade Modal -->
    <div v-if="showUpgradeModal" class="modal-overlay" @click="closeUpgradeModal">
      <div class="modal-content upgrade-modal" @click.stop>
        <div class="modal-header">
          <h3>🚀 Unlock Watchlist Premium</h3>
          <button @click="closeUpgradeModal" class="close-btn">&times;</button>
        </div>
        <div class="upgrade-content">
          <div class="upgrade-benefits">
            <h4>Watchlist Premium Features:</h4>
            <ul>
              <li>✅ Nieograniczona liczba pozycji</li>
              <li>✅ AI sentiment tracking</li>
              <li>✅ Sentiment notifications</li>
              <li>✅ Trend analysis i prognozy</li>
              <li>✅ Zaawansowane insights</li>
              <li>✅ Portfolio sentiment overview</li>
            </ul>
            <div class="feature-comparison">
              <div class="comparison-row">
                <span class="feature-name">Pozycje w watchlist</span>
                <span class="free-value">15</span>
                <span class="premium-value">∞ Unlimited</span>
              </div>
              <div class="comparison-row">
                <span class="feature-name">AI Sentiment</span>
                <span class="free-value">❌</span>
                <span class="premium-value">✅ Full access</span>
              </div>
            </div>
          </div>
          <div class="upgrade-pricing">
            <div class="price">19 PLN/miesiąc</div>
            <button class="btn btn-premium btn-large">
              Aktywuj Premium
            </button>
          </div>
        </div>
      </div>
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
          Add cryptocurrencies to track their prices{{ isPremium ? ' and AI sentiment' : '' }}
        </p>
        <div class="empty-actions">
          <button @click="initializeDefaultWatchlist" class="btn btn-primary">
            Add Popular Cryptos
          </button>
          <div v-if="!isPremium" class="limit-info">
            <p>Darmowy plan: do {{ watchlistLimits.watchlist_limit }} pozycji (bez AI sentiment)</p>
            <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
              Unlock AI Sentiment Tracking
            </button>
          </div>
        </div>
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
                v-if="isPremium"
                @click="showCryptoHistory(item.cryptocurrency)"
                class="btn btn-small btn-ghost"
                title="View Sentiment History"
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

          <!-- Sentiment Info - ZMIENIONE: z premium lock -->
          <div class="sentiment-section">
            <div v-if="isPremium && item.sentiment_access" class="sentiment-content">
              <div class="sentiment-row">
                <label>AI Sentiment</label>
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

            <!-- DODANE: Premium lock dla sentiment -->
            <div v-else class="sentiment-locked">
              <div class="lock-content">
                <div class="lock-icon">🔒</div>
                <div class="lock-text">
                  <h5>AI Sentiment Analysis</h5>
                  <p>Upgrade to Premium to unlock sentiment tracking</p>
                </div>
              </div>
              <button @click="showUpgradeModal = true" class="btn btn-premium btn-mini">
                Unlock
              </button>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer">
            <span class="update-time">{{ isPremium && item.analysis_time ? item.analysis_time : 'Price updated live' }}</span>
            <div class="notification-toggle">
              <label class="toggle-switch">
                <input
                  type="checkbox"
                  :checked="item.notifications_enabled"
                  @change="toggleNotifications(item, $event)"
                />
                <span class="toggle-slider"></span>
              </label>
              <span class="toggle-label">{{ isPremium ? 'Smart Alerts' : 'Price Alerts' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- DODANE: Premium features showcase dla darmowych użytkowników -->
      <div v-if="!isPremium && watchlist.length > 0" class="premium-showcase">
        <div class="showcase-content">
          <h4>🤖 Chcesz więcej insights? Premium oferuje:</h4>
          <div class="feature-grid">
            <div class="feature-item">
              <span class="feature-icon">🧠</span>
              <span class="feature-text">AI Sentiment per crypto</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">📊</span>
              <span class="feature-text">Trend Analysis</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">∞</span>
              <span class="feature-text">Unlimited Watchlist</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">🔔</span>
              <span class="feature-text">Sentiment Alerts</span>
            </div>
          </div>
          <button @click="showUpgradeModal = true" class="btn btn-premium">
            Upgrade za 19 PLN/miesiąc
          </button>
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

        <!-- DODANE: Limit info w modal -->
        <div v-if="!isPremium" class="modal-limit-info">
          <div class="limit-progress">
            <span class="limit-text">Watchlist: {{ watchlistLimits.current_count }}/{{ watchlistLimits.watchlist_limit }}</span>
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :style="{ width: (watchlistLimits.current_count / watchlistLimits.watchlist_limit * 100) + '%' }"
              ></div>
            </div>
          </div>
          <div class="premium-features-hint">
            <p><strong>Premium:</strong> Unlimited watchlist + AI sentiment tracking</p>
          </div>
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
                  <div class="mentions" v-if="isPremium">
                    {{ crypto.daily_mentions }} mentions
                  </div>
                  <div class="mentions" v-else>
                    🔒 AI data
                  </div>
                </div>
                <div class="action-area">
                  <span v-if="crypto.is_watchlisted" class="already-added-label"
                    >✓ Added</span
                  >
                  <button v-else-if="canAddMore" class="btn btn-small btn-primary">Add</button>
                  <span v-else class="limit-reached">Limit reached</span>
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

    <!-- Crypto History Modal - tylko dla Premium -->
    <div
      v-if="selectedCryptoHistory && isPremium"
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
      showUpgradeModal: false, // DODANE
      showDiscovery: false,
      searchQuery: "",
      searchResults: [],
      searchTimeout: null,
      selectedCryptoHistory: null,
      // DODANE: limity watchlist
      watchlistLimits: {
        is_premium: false,
        watchlist_limit: 15,
        current_count: 0,
        can_add_more: true,
        sentiment_access: false
      },
      premiumFeatures: {
        unlimited_watchlist: false,
        sentiment_tracking: false,
        sentiment_notifications: false,
        trend_analysis: false,
        ai_insights: false
      }
    };
  },
  computed: {
    isPremium() {
      return this.watchlistLimits.is_premium;
    },
    canAddMore() {
      return this.watchlistLimits.can_add_more;
    }
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
        
        // DODANE: wczytanie limitów
        this.watchlistLimits = response.data.limits || {
          is_premium: false,
          watchlist_limit: 15,
          current_count: 0,
          can_add_more: true,
          sentiment_access: false
        };
        
        this.premiumFeatures = response.data.premium_features || {};
        
        this.$emit("watchlist-updated", response.data.total_count);
      } catch (error) {
        console.error("Error loading watchlist:", error);
        this.showError("Failed to load watchlist");
      } finally {
        this.loading = false;
      }
    },

    async addToWatchlist(crypto) {
      try {
        const response = await window.axios.post("/api/watchlist", {
          cryptocurrency_id: crypto.id,
          notifications_enabled: true,
        });

        // DODANE: aktualizacja limitów po dodaniu
        if (response.data.limits_info) {
          this.watchlistLimits = {
            ...this.watchlistLimits,
            ...response.data.limits_info
          };
        }

        this.showSuccess(`${crypto.name} added to watchlist`);
        await this.loadWatchlist();

        crypto.is_watchlisted = true;
      } catch (error) {
        console.error("Error adding to watchlist:", error);
        
        // DODANE: obsługa błędów limitów
        if (error.response && error.response.status === 403 && error.response.data.upgrade_required) {
          this.showUpgradeModal = true;
          this.closeAddModal();
          this.showError(error.response.data.message);
        } else {
          this.showError("Failed to add to watchlist");
        }
      }
    },

    async removeCrypto(watchlistItem) {
      const cryptoName = watchlistItem.cryptocurrency.name;

      if (!confirm(`Remove ${cryptoName} from your watchlist?`)) {
        return;
      }

      try {
        const response = await window.axios.delete(`/api/watchlist/${watchlistItem.id}`);

        // DODANE: aktualizacja limitów po usunięciu
        if (response.data.limits_info) {
          this.watchlistLimits = {
            ...this.watchlistLimits,
            ...response.data.limits_info
          };
        }

        this.showSuccess(`${cryptoName} removed from watchlist`);
        await this.loadWatchlist();
      } catch (error) {
        console.error("Error removing from watchlist:", error);
        this.showError("Failed to remove from watchlist");
      }
    },

    async toggleNotifications(watchlistItem, event) {
      try {
        await window.axios.put(`/api/watchlist/${watchlistItem.id}`, {
          notifications_enabled: event.target.checked,
        });

        const status = event.target.checked ? "enabled" : "disabled";
        const alertType = this.isPremium ? "smart alerts" : "price alerts";
        this.showSuccess(`${alertType} ${status} for ${watchlistItem.cryptocurrency.name}`);
      } catch (error) {
        console.error("Error updating notifications:", error);
        this.showError("Failed to update notification settings");
        event.target.checked = !event.target.checked;
      }
    },

    async initializeDefaultWatchlist() {
      try {
        const response = await window.axios.get("/api/discovery/trending?limit=10");
        const topCryptos = response.data.trending.slice(0, 10);

        const cryptoIds = topCryptos.map((crypto) => crypto.id);
        const bulkResponse = await window.axios.post("/api/watchlist/bulk-add", {
          cryptocurrency_ids: cryptoIds,
          notifications_enabled: true,
        });

        // DODANE: aktualizacja limitów po bulk add
        if (bulkResponse.data.limits_info) {
          this.watchlistLimits = {
            ...this.watchlistLimits,
            ...bulkResponse.data.limits_info
          };
        }

        this.showSuccess("Added popular cryptocurrencies to your watchlist!");
        await this.loadWatchlist();
      } catch (error) {
        console.error("Error initializing watchlist:", error);
        
        // DODANE: obsługa błędów limitów przy bulk add
        if (error.response && error.response.status === 403 && error.response.data.upgrade_required) {
          this.showUpgradeModal = true;
          this.showError(error.response.data.message);
        } else {
          this.showError("Failed to initialize watchlist");
        }
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

    showCryptoHistory(crypto) {
      if (!this.isPremium) {
        this.showUpgradeModal = true;
        return;
      }
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

    // DODANE: nowa metoda
    closeUpgradeModal() {
      this.showUpgradeModal = false;
    },

    getWatchlistCardClass(item) {
      if (!this.isPremium || !item.sentiment_access) {
        return { 'card-locked': true };
      }
      
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

/* DODANE: Style dla limitów i upgrade */
.limit-warning-banner {
  background: linear-gradient(135deg, #fef3cd, #fde68a);
  border: 1px solid #fbbf24;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.banner-icon {
  font-size: 1.5rem;
}

.banner-text h4 {
  margin: 0;
  color: #92400e;
  font-size: 1rem;
}

.banner-text p {
  margin: 0;
  color: #b45309;
  font-size: 0.9rem;
}

.modal-limit-info {
  background: #f8fafc;
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.limit-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.limit-text {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  transition: width 0.3s ease;
}

.premium-features-hint {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 6px;
  text-align: center;
}

.premium-features-hint p {
  margin: 0;
  font-size: 0.85rem;
  color: #64748b;
}

.upgrade-modal {
  max-width: 600px;
}

.upgrade-content {
  padding: 1rem 0;
}

.upgrade-benefits {
  margin-bottom: 2rem;
}

.upgrade-benefits h4 {
  color: #1e293b;
  margin-bottom: 1rem;
}

.upgrade-benefits ul {
  list-style: none;
  padding: 0;
  margin-bottom: 1.5rem;
}

.upgrade-benefits li {
  padding: 0.5rem 0;
  color: #64748b;
}

.feature-comparison {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.comparison-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 1rem;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
}

.comparison-row:last-child {
  border-bottom: none;
}

.feature-name {
  font-weight: 500;
  color: #1e293b;
}

.free-value {
  color: #64748b;
  font-size: 0.9rem;
  text-align: center;
  min-width: 60px;
}

.premium-value {
  color: #10b981;
  font-weight: 600;
  font-size: 0.9rem;
  text-align: center;
  min-width: 80px;
}

.upgrade-pricing {
  text-align: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 12px;
}

.price {
  font-size: 2rem;
  font-weight: bold;
  color: #1e293b;
  margin-bottom: 1rem;
}

.sentiment-locked {
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border: 2px dashed #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  text-align: center;
  margin: 1rem 0;
}

.lock-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.lock-icon {
  font-size: 2rem;
  color: #64748b;
}

.lock-text h5 {
  margin: 0;
  color: #1e293b;
  font-size: 1rem;
}

.lock-text p {
  margin: 0;
  color: #64748b;
  font-size: 0.85rem;
}

.card-locked {
  border-color: #e2e8f0;
  opacity: 0.9;
}

.premium-showcase {
  margin-top: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 12px;
  border: 2px dashed #e2e8f0;
  text-align: center;
}

.showcase-content h4 {
  color: #1e293b;
  margin-bottom: 1.5rem;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.feature-icon {
  font-size: 1.5rem;
}

.feature-text {
  font-weight: 500;
  color: #1e293b;
}

.btn-premium {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: white;
}

.btn-premium:hover {
  background: linear-gradient(135deg, #7c3aed, #5b5cf1);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.4);
}

.btn-mini {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-limit {
  font-size: 0.8rem;
  opacity: 0.8;
}

.limit-reached {
  color: #ef4444;
  font-size: 0.8rem;
  font-weight: 500;
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

.sentiment-content {
  /* Style dla zwykłej sentiment content */
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

.btn-large {
  padding: 1rem 2rem;
  font-size: 1.1rem;
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

.empty-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.limit-info {
  text-align: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.limit-info p {
  margin: 0 0 0.5rem 0;
  color: #64748b;
  font-size: 0.9rem;
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

  .banner-content {
    flex-direction: column;
    text-align: center;
  }

  .feature-grid {
    grid-template-columns: 1fr;
  }

  .comparison-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    text-align: center;
  }
}
</style>