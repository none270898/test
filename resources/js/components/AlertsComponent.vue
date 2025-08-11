<template>
  <div class="alerts-component">
    <!-- DODANE: Premium upgrade banner dla darmowych użytkowników -->
    <div v-if="!isPremium && alertsLimits.current_active_count >= 4" class="limit-warning-banner">
      <div class="banner-content">
        <div class="banner-icon">🔔</div>
        <div class="banner-text">
          <h4>Zbliżasz się do limitu alertów!</h4>
          <p>{{ alertsLimits.current_active_count }}/{{ alertsLimits.alerts_limit }} aktywnych alertów</p>
        </div>
        <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
          Upgrade do Premium
        </button>
      </div>
    </div>

    <!-- Add Alert Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click="closeAddModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingAlert ? 'Edit Alert' : 'Create Price Alert' }}</h3>
          <button @click="closeAddModal" class="close-btn">&times;</button>
        </div>
        
        <!-- DODANE: Limit warning w modal -->
        <div v-if="!isPremium && !editingAlert" class="modal-limit-info">
          <div class="limit-progress">
            <span class="limit-text">Aktywne alerty: {{ alertsLimits.current_active_count }}/{{ alertsLimits.alerts_limit }}</span>
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :style="{ width: (alertsLimits.current_active_count / alertsLimits.alerts_limit * 100) + '%' }"
              ></div>
            </div>
          </div>
          <div v-if="!isPremium" class="premium-features-hint">
            <p><strong>Premium:</strong> Nieograniczone alerty + sentiment alerts</p>
          </div>
        </div>
        
        <form @submit.prevent="saveAlert" class="add-alert-form">
          <div class="form-group">
            <label>Cryptocurrency</label>
            <div class="crypto-search-container">
              <input 
                v-model="searchQuery"
                @input="searchCryptocurrencies"
                placeholder="Search for cryptocurrency..."
                :disabled="editingAlert"
                class="crypto-search"
              />
              <div v-if="searchResults.length > 0 && !editingAlert" class="search-results">
                <div 
                  v-for="crypto in searchResults" 
                  :key="crypto.id"
                  @click="selectCryptocurrency(crypto)"
                  class="search-result-item"
                >
                  <img :src="crypto.image" :alt="crypto.name" class="crypto-icon-small">
                  <span class="crypto-name">{{ crypto.name }}</span>
                  <span class="crypto-symbol">{{ crypto.symbol.toUpperCase() }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Alert Type</label>
              <select v-model="alertForm.type" required>
                <option value="above">Price goes above</option>
                <option value="below">Price goes below</option>
              </select>
            </div>

            <div class="form-group">
              <label>Currency</label>
              <select v-model="alertForm.currency" required>
                <option value="PLN">PLN</option>
                <option value="USD">USD</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Target Price</label>
            <div class="price-input-container">
              <input 
                v-model="alertForm.target_price" 
                type="number" 
                step="0.01" 
                min="0"
                placeholder="0.00"
                required
              />
              <span class="currency-suffix">{{ alertForm.currency }}</span>
            </div>
            <small class="form-help" v-if="selectedCrypto">
              Current price: {{ getCurrentPrice() }} {{ alertForm.currency }}
            </small>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeAddModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" :disabled="!selectedCrypto || loading || !canAddMore" class="btn btn-primary">
              <span v-if="loading">{{ editingAlert ? 'Updating...' : 'Creating...' }}</span>
              <span v-else>{{ editingAlert ? 'Update Alert' : 'Create Alert' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DODANE: Upgrade Modal -->
    <div v-if="showUpgradeModal" class="modal-overlay" @click="closeUpgradeModal">
      <div class="modal-content upgrade-modal" @click.stop>
        <div class="modal-header">
          <h3>🚀 Upgrade do Premium - Alerty</h3>
          <button @click="closeUpgradeModal" class="close-btn">&times;</button>
        </div>
        <div class="upgrade-content">
          <div class="upgrade-benefits">
            <h4>Premium Alerts Features:</h4>
            <ul>
              <li>✅ Nieograniczone alerty cenowe</li>
              <li>✅ Alerty sentiment (AI analysis)</li>
              <li>✅ Push notifications</li>
              <li>✅ Zaawansowane warunki alertów</li>
              <li>✅ Priorytetowe powiadomienia</li>
              <li>✅ Alerty kombinowane (cena + sentiment)</li>
            </ul>
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

    <!-- Alerts List -->
    <div class="alerts-container">
      <div class="alerts-header">
        <h3>Price Alerts</h3>
        <div class="header-actions">
          <button 
            @click="showAddModal = true" 
            :disabled="!canAddMore"
            class="btn btn-primary"
            :class="{ 'btn-disabled': !canAddMore }"
          >
            <span class="btn-icon">🔔</span>
            Add Alert
            <span v-if="!isPremium" class="btn-limit">({{ alertsLimits.current_active_count }}/{{ alertsLimits.alerts_limit }})</span>
          </button>
          
          <!-- DODANE: Upgrade button dla darmowych użytkowników -->
          <button 
            v-if="!isPremium"
            @click="showUpgradeModal = true"
            class="btn btn-premium btn-small"
          >
            🚀 Unlock Unlimited
          </button>
        </div>
      </div>

      <div v-if="loading && alerts.length === 0" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading your alerts...</p>
      </div>

      <div v-else-if="alerts.length === 0" class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>No price alerts</h3>
        <p>Create your first price alert to get notified when prices change</p>
        <div class="empty-actions">
          <button @click="showAddModal = true" class="btn btn-primary">
            Create Your First Alert
          </button>
          <div v-if="!isPremium" class="limit-info">
            <p>Darmowy plan: do {{ alertsLimits.alerts_limit }} aktywnych alertów</p>
            <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
              Unlock Unlimited + AI Alerts
            </button>
          </div>
        </div>
      </div>

      <div v-else class="alerts-list">
        <div 
          v-for="alert in alerts" 
          :key="alert.id"
          class="alert-card"
          :class="{ 
            'alert-inactive': !alert.is_active, 
            'alert-triggered': alert.triggered_at,
            'alert-premium': isPremium 
          }"
        >
          <div class="alert-main">
            <div class="crypto-info">
              <img 
                :src="alert.cryptocurrency.image" 
                :alt="alert.cryptocurrency.name" 
                class="crypto-icon"
                @error="handleImageError"
              >
              <div class="crypto-details">
                <h4>{{ alert.cryptocurrency.name }}</h4>
                <span class="crypto-symbol">{{ alert.cryptocurrency.symbol.toUpperCase() }}</span>
              </div>
            </div>

            <div class="alert-details">
              <div class="alert-condition">
                <span class="condition-label">
                  {{ alert.type === 'above' ? '📈 Above' : '📉 Below' }}
                </span>
                <span class="target-price">
                  {{ formatPrice(alert.target_price) }} {{ alert.currency }}
                </span>
              </div>

              <div class="current-price">
                <label>Current Price</label>
                <span class="price-value" :class="getPriceClass(alert)">
                  {{ getCurrentPriceForAlert(alert) }} {{ alert.currency }}
                </span>
              </div>

              <div class="alert-status">
                <label>Status</label>
                <span class="status-badge" :class="getStatusClass(alert)">
                  {{ getStatusText(alert) }}
                </span>
              </div>
            </div>
          </div>

          <div class="alert-actions">
            <button 
              v-if="!alert.triggered_at"
              @click="toggleAlert(alert)" 
              class="btn btn-small"
              :class="alert.is_active ? 'btn-warning' : 'btn-success'"
              :disabled="!alert.is_active && !canActivateAlert"
            >
              {{ alert.is_active ? 'Disable' : 'Enable' }}
            </button>
            <button 
              v-if="!alert.triggered_at"
              @click="editAlert(alert)" 
              class="btn btn-small btn-secondary"
            >
              Edit
            </button>
            <button @click="deleteAlert(alert)" class="btn btn-small btn-danger">
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- DODANE: Premium features showcase -->
      <div v-if="!isPremium && alerts.length > 0" class="premium-showcase">
        <div class="showcase-content">
          <h4>🤖 Chcesz więcej? Premium oferuje:</h4>
          <div class="feature-grid">
            <div class="feature-item">
              <span class="feature-icon">🧠</span>
              <span class="feature-text">Sentiment Alerts</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">📱</span>
              <span class="feature-text">Push Notifications</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">∞</span>
              <span class="feature-text">Unlimited Alerts</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">⚡</span>
              <span class="feature-text">Priority Delivery</span>
            </div>
          </div>
          <button @click="showUpgradeModal = true" class="btn btn-premium">
            Upgrade za 19 PLN/miesiąc
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AlertsComponent',
  data() {
    return {
      alerts: [],
      loading: false,
      showAddModal: false,
      showUpgradeModal: false, // DODANE
      editingAlert: null,
      selectedCrypto: null,
      searchQuery: '',
      searchResults: [],
      searchTimeout: null,
      alertForm: {
        type: 'above',
        target_price: '',
        currency: 'PLN'
      },
      // DODANE: limity alertów
      alertsLimits: {
        is_premium: false,
        alerts_limit: 5,
        current_active_count: 0,
        can_add_more: true,
        total_count: 0
      },
      premiumFeatures: {
        unlimited_alerts: false,
        sentiment_alerts: false,
        push_notifications: false,
        advanced_conditions: false
      }
    }
  },
  computed: {
    isPremium() {
      return this.alertsLimits.is_premium;
    },
    canAddMore() {
      return this.alertsLimits.can_add_more;
    },
    canActivateAlert() {
      return this.isPremium || this.alertsLimits.current_active_count < this.alertsLimits.alerts_limit;
    }
  },
  async mounted() {
    await this.loadAlerts();
  },
  methods: {
    async loadAlerts() {
      this.loading = true;
      try {
        const response = await window.axios.get('/api/alerts');
        this.alerts = response.data.alerts;
        
        // DODANE: wczytanie limitów
        this.alertsLimits = response.data.limits || {
          is_premium: false,
          alerts_limit: 5,
          current_active_count: 0,
          can_add_more: true,
          total_count: 0
        };
        
        this.premiumFeatures = response.data.premium_features || {};
        
        this.$emit('alerts-updated', this.alertsLimits.current_active_count);
      } catch (error) {
        console.error('Error loading alerts:', error);
        this.showError('Failed to load alerts');
      } finally {
        this.loading = false;
      }
    },

    async saveAlert() {
      if (!this.selectedCrypto && !this.editingAlert) return;

      this.loading = true;
      try {
        const data = {
          cryptocurrency_id: this.editingAlert ? this.editingAlert.cryptocurrency_id : this.selectedCrypto.id,
          type: this.alertForm.type,
          target_price: parseFloat(this.alertForm.target_price),
          currency: this.alertForm.currency
        };

        if (this.editingAlert) {
          await window.axios.put(`/api/alerts/${this.editingAlert.id}`, data);
          this.showSuccess('Alert updated successfully');
        } else {
          const response = await window.axios.post('/api/alerts', data);
          
          // DODANE: aktualizacja limitów po dodaniu
          if (response.data.limits_info) {
            this.alertsLimits = {
              ...this.alertsLimits,
              ...response.data.limits_info
            };
          }
          
          this.showSuccess('Alert created successfully');
        }

        await this.loadAlerts();
        this.closeAddModal();
      } catch (error) {
        console.error('Error saving alert:', error);
        
        // DODANE: obsługa błędów limitów
        if (error.response && error.response.status === 403 && error.response.data.upgrade_required) {
          this.showUpgradeModal = true;
          this.closeAddModal();
          this.showError(error.response.data.message);
        } else {
          this.showError('Failed to save alert');
        }
      } finally {
        this.loading = false;
      }
    },

    async toggleAlert(alert) {
      try {
        const response = await window.axios.post(`/api/alerts/${alert.id}/toggle`);
        
        // DODANE: aktualizacja limitów po toggle
        if (response.data.limits_info) {
          this.alertsLimits = {
            ...this.alertsLimits,
            ...response.data.limits_info
          };
        }
        
        this.showSuccess(`Alert ${alert.is_active ? 'disabled' : 'enabled'}`);
        await this.loadAlerts();
      } catch (error) {
        console.error('Error toggling alert:', error);
        
        // DODANE: obsługa błędów limitów przy włączaniu
        if (error.response && error.response.status === 403 && error.response.data.upgrade_required) {
          this.showUpgradeModal = true;
          this.showError(error.response.data.message);
        } else {
          this.showError('Failed to toggle alert');
        }
      }
    },

    async deleteAlert(alert) {
      if (!confirm(`Are you sure you want to delete this alert for ${alert.cryptocurrency.name}?`)) {
        return;
      }

      try {
        const response = await window.axios.delete(`/api/alerts/${alert.id}`);
        
        // DODANE: aktualizacja limitów po usunięciu
        if (response.data.limits_info) {
          this.alertsLimits = {
            ...this.alertsLimits,
            ...response.data.limits_info
          };
        }
        
        this.showSuccess('Alert deleted successfully');
        await this.loadAlerts();
      } catch (error) {
        console.error('Error deleting alert:', error);
        this.showError('Failed to delete alert');
      }
    },

    // DODANE: nowe metody
    closeUpgradeModal() {
      this.showUpgradeModal = false;
    },

    // Reszta metod bez większych zmian...
    async searchCryptocurrencies() {
      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        return;
      }

      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(async () => {
        try {
          const response = await window.axios.get('/api/cryptocurrencies/search', {
            params: { q: this.searchQuery }
          });
          this.searchResults = response.data;
        } catch (error) {
          console.error('Search error:', error);
          this.searchResults = [];
        }
      }, 300);
    },

    selectCryptocurrency(crypto) {
      this.selectedCrypto = crypto;
      this.searchQuery = `${crypto.name} (${crypto.symbol.toUpperCase()})`;
      this.searchResults = [];
    },

    editAlert(alert) {
      this.editingAlert = alert;
      this.selectedCrypto = alert.cryptocurrency;
      this.searchQuery = `${alert.cryptocurrency.name} (${alert.cryptocurrency.symbol.toUpperCase()})`;
      this.alertForm = {
        type: alert.type,
        target_price: alert.target_price.toString(),
        currency: alert.currency
      };
      this.showAddModal = true;
    },

    closeAddModal() {
      this.showAddModal = false;
      this.editingAlert = null;
      this.selectedCrypto = null;
      this.searchQuery = '';
      this.searchResults = [];
      this.alertForm = {
        type: 'above',
        target_price: '',
        currency: 'PLN'
      };
    },

    getCurrentPrice() {
      if (!this.selectedCrypto) return 0;
      return this.alertForm.currency === 'PLN' 
        ? this.formatPrice(this.selectedCrypto.current_price_pln || 0)
        : this.formatPrice(this.selectedCrypto.current_price_usd || 0);
    },

    getCurrentPriceForAlert(alert) {
      const price = alert.currency === 'PLN' 
        ? alert.cryptocurrency.current_price_pln 
        : alert.cryptocurrency.current_price_usd;
      return this.formatPrice(price || 0);
    },

    getPriceClass(alert) {
      const currentPrice = alert.currency === 'PLN' 
        ? alert.cryptocurrency.current_price_pln 
        : alert.cryptocurrency.current_price_usd;

      if (alert.type === 'above') {
        return currentPrice >= alert.target_price ? 'price-above' : 'price-below';
      } else {
        return currentPrice <= alert.target_price ? 'price-below' : 'price-above';
      }
    },

    getStatusClass(alert) {
      if (alert.triggered_at) return 'status-triggered';
      if (!alert.is_active) return 'status-inactive';
      return 'status-active';
    },

    getStatusText(alert) {
      if (alert.triggered_at) return 'Triggered';
      if (!alert.is_active) return 'Inactive';
      return 'Active';
    },

    formatPrice(price) {
      return parseFloat(price).toLocaleString('pl-PL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8
      });
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
  }
}
</script>

<style scoped>
/* Reuse most styles from PortfolioComponent */
.alerts-component {
  width: 100%;
}

.alert-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
}

.alert-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  border-color: #6366f1;
}

.alert-inactive {
  opacity: 0.7;
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.5), rgba(241, 245, 249, 0.5));
}

.alert-triggered {
  border-color: #10b981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
}

.alert-main {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-bottom: 1rem;
}

.crypto-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 200px;
}

.crypto-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 2px solid #f1f5f9;
}

.crypto-icon-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.crypto-details h4 {
  margin: 0;
  color: #1e293b;
  font-size: 1.1rem;
}

.crypto-symbol {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

.alert-details {
  display: flex;
  gap: 2rem;
  flex: 1;
}

.alert-condition,
.current-price,
.alert-status {
  flex: 1;
}

.alert-condition label,
.current-price label,
.alert-status label {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.condition-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
}

.target-price {
  display: block;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin-top: 0.25rem;
}

.price-value {
  font-size: 1.1rem;
  font-weight: 600;
}

.price-above {
  color: #10b981;
}

.price-below {
  color: #ef4444;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-active {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-inactive {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.status-triggered {
  background: rgba(99, 102, 241, 0.1);
  color: #6366f1;
}

.alert-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.price-input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-suffix {
  position: absolute;
  right: 1rem;
  color: #64748b;
  font-weight: 600;
  pointer-events: none;
}

.price-input-container input {
  padding-right: 4rem !important;
}

/* Modal and form styles - reuse from portfolio component */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 0;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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
  font-size: 1.5rem;
  color: #0f172a;
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

.add-alert-form {
  padding: 0 2rem 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.crypto-search-container {
  position: relative;
}

.crypto-search,
.add-alert-form input[type="number"],
.add-alert-form select {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
}

.crypto-search:focus,
.add-alert-form input[type="number"]:focus,
.add-alert-form select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  background: white;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
}

.search-result-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
  gap: 0.75rem;
}

.search-result-item:hover {
  background: #f8fafc;
}

.crypto-name {
  font-weight: 500;
  color: #1e293b;
}

.form-help {
  display: block;
  color: #64748b;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.alerts-container {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.alerts-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.alerts-header h3 {
  color: #1e293b;
  font-size: 1.5rem;
  margin: 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  gap: 0.5rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}

.btn-primary:hover:not(:disabled) {
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

.btn-success {
  background: #f0fdf4;
  color: #16a34a;
}

.btn-success:hover {
  background: #dcfce7;
}

.btn-warning {
  background: #fefce8;
  color: #ca8a04;
}

.btn-warning:hover {
  background: #fef3c7;
}

.btn-danger {
  background: #fef2f2;
  color: #dc2626;
}

.btn-danger:hover {
  background: #fee2e2;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-icon {
  font-size: 1.2rem;
}

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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .alerts-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .alert-main {
    flex-direction: column;
    gap: 1rem;
  }

  .alert-details {
    flex-direction: column;
    gap: 1rem;
  }

  .crypto-info {
    min-width: auto;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .alert-actions {
    justify-content: stretch;
  }
}
</style>