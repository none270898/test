// resources/js/components/PortfolioComponent.vue

<template>
  <div class="portfolio-component">
    <!-- DODANE: Premium upgrade banner dla darmowych użytkowników -->
    <div v-if="!isPremium && portfolioLimits.current_count >= 8" class="limit-warning-banner">
      <div class="banner-content">
        <div class="banner-icon">⚠️</div>
        <div class="banner-text">
          <h4>Zbliżasz się do limitu portfolio!</h4>
          <p>{{ portfolioLimits.current_count }}/{{ portfolioLimits.portfolio_limit }} pozycji wykorzystanych</p>
        </div>
        <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
          Upgrade do Premium
        </button>
      </div>
    </div>

    <!-- Add Holding Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click="closeAddModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingHolding ? 'Edit Holding' : 'Add New Holding' }}</h3>
          <button @click="closeAddModal" class="close-btn">&times;</button>
        </div>
        
        <!-- DODANE: Limit warning w modal -->
        <div v-if="!isPremium && !editingHolding" class="modal-limit-info">
          <div class="limit-progress">
            <span class="limit-text">Portfolio: {{ portfolioLimits.current_count }}/{{ portfolioLimits.portfolio_limit }}</span>
            <div class="progress-bar">
              <div 
                class="progress-fill" 
                :style="{ width: (portfolioLimits.current_count / portfolioLimits.portfolio_limit * 100) + '%' }"
              ></div>
            </div>
          </div>
        </div>
        
        <form @submit.prevent="saveHolding" class="add-holding-form">
          <div class="form-group">
            <label>Cryptocurrency</label>
            <div class="crypto-search-container">
              <input 
                v-model="searchQuery"
                @input="searchCryptocurrencies"
                placeholder="Search for cryptocurrency..."
                :disabled="editingHolding"
                class="crypto-search"
              />
              <div v-if="searchResults.length > 0 && !editingHolding" class="search-results">
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

          <div class="form-group">
            <label>Amount</label>
            <input 
              v-model="holdingForm.amount" 
              type="number" 
              step="0.00000001" 
              min="0"
              placeholder="0.00000000"
              required
            />
          </div>

          <div class="form-group">
            <label>Average Buy Price (PLN) - Optional</label>
            <input 
              v-model="holdingForm.average_buy_price" 
              type="number" 
              step="0.01" 
              min="0"
              placeholder="0.00"
            />
            <small class="form-help">Leave empty if you don't want to track profit/loss</small>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeAddModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" :disabled="!selectedCrypto || loading" class="btn btn-primary">
              <span v-if="loading">{{ editingHolding ? 'Updating...' : 'Adding...' }}</span>
              <span v-else>{{ editingHolding ? 'Update Holding' : 'Add Holding' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DODANE: Upgrade Modal -->
    <div v-if="showUpgradeModal" class="modal-overlay" @click="closeUpgradeModal">
      <div class="modal-content upgrade-modal" @click.stop>
        <div class="modal-header">
          <h3>🚀 Upgrade do Premium</h3>
          <button @click="closeUpgradeModal" class="close-btn">&times;</button>
        </div>
        <div class="upgrade-content">
          <div class="upgrade-benefits">
            <h4>Portfolio Premium Features:</h4>
            <ul>
              <li>✅ Nieograniczona liczba kryptowalut</li>
              <li>✅ Zaawansowane wykresy i analytics</li>
              <li>✅ Historia transakcji</li>
              <li>✅ Export danych do CSV/PDF</li>
              <li>✅ Alerty sentiment dla portfolio</li>
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

    <!-- Holdings List -->
    <div class="holdings-container">
      <div class="holdings-header">
        <h3>Your Holdings</h3>
        <div class="header-actions">
          <button 
            @click="showAddModal = true" 
            :disabled="!canAddMore"
            class="btn btn-primary"
            :class="{ 'btn-disabled': !canAddMore }"
          >
            <span class="btn-icon">+</span>
            Add Holding
            <span v-if="!isPremium" class="btn-limit">({{ portfolioLimits.current_count }}/{{ portfolioLimits.portfolio_limit }})</span>
          </button>
          
          <!-- DODANE: Upgrade button dla darmowych użytkowników -->
          <button 
            v-if="!isPremium"
            @click="showUpgradeModal = true"
            class="btn btn-premium btn-small"
          >
            🚀 Upgrade
          </button>
        </div>
      </div>

      <div v-if="loading && holdings.length === 0" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading your portfolio...</p>
      </div>

      <div v-else-if="holdings.length === 0" class="empty-state">
        <div class="empty-icon">📊</div>
        <h3>No holdings yet</h3>
        <p>Add your first cryptocurrency to start tracking your portfolio</p>
        <div class="empty-actions">
          <button @click="showAddModal = true" class="btn btn-primary">
            Add Your First Holding
          </button>
          <div v-if="!isPremium" class="limit-info">
            <p>Darmowy plan: do {{ portfolioLimits.portfolio_limit }} kryptowalut</p>
            <button @click="showUpgradeModal = true" class="btn btn-premium btn-small">
              Unlock Unlimited Portfolio
            </button>
          </div>
        </div>
      </div>

      <div v-else class="holdings-list">
        <div 
          v-for="holding in holdings" 
          :key="holding.id"
          class="holding-card"
        >
          <div class="holding-main">
            <div class="crypto-info">
              <img 
                :src="holding.cryptocurrency.image" 
                :alt="holding.cryptocurrency.name" 
                class="crypto-icon"
                @error="handleImageError"
              >
              <div class="crypto-details">
                <h4>{{ holding.cryptocurrency.name }}</h4>
                <span class="crypto-symbol">{{ holding.cryptocurrency.symbol.toUpperCase() }}</span>
              </div>
            </div>

            <div class="holding-amounts">
              <div class="amount-section">
                <label>Amount</label>
                <div class="amount-value">
                  {{ formatAmount(holding.amount) }} {{ holding.cryptocurrency.symbol.toUpperCase() }}
                </div>
              </div>

              <div class="value-section">
                <label>Current Value</label>
                <div class="current-value">
                  {{ formatPLN(holding.amount * holding.cryptocurrency.current_price_pln) }}
                </div>
                <div class="price-per-unit">
                  {{ formatPLN(holding.cryptocurrency.current_price_pln) }} per {{ holding.cryptocurrency.symbol.toUpperCase() }}
                </div>
              </div>

              <div v-if="holding.average_buy_price" class="profit-section">
                <label>Profit/Loss</label>
                <div class="profit-value" :class="getProfitClass(holding)">
                  {{ formatPLN(calculateProfit(holding)) }}
                  <span class="profit-percent">
                    ({{ formatPercent(calculateProfitPercent(holding)) }}%)
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="holding-actions">
            <button @click="editHolding(holding)" class="btn btn-small btn-secondary">
              Edit
            </button>
            <button @click="deleteHolding(holding)" class="btn btn-small btn-danger">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PortfolioComponent',
  data() {
    return {
      holdings: [],
      loading: false,
      showAddModal: false,
      showUpgradeModal: false, // DODANE
      editingHolding: null,
      selectedCrypto: null,
      searchQuery: '',
      searchResults: [],
      searchTimeout: null,
      holdingForm: {
        amount: '',
        average_buy_price: ''
      },
      // DODANE: limity
      portfolioLimits: {
        is_premium: false,
        portfolio_limit: 10,
        current_count: 0,
        can_add_more: true
      }
    }
  },
  computed: {
    isPremium() {
      return this.portfolioLimits.is_premium;
    },
    canAddMore() {
      return this.portfolioLimits.can_add_more;
    }
  },
  async mounted() {
    await this.loadHoldings();
  },
  methods: {
    async loadHoldings() {
      this.loading = true;
      try {
        const response = await window.axios.get('/api/portfolio');
        this.holdings = response.data.holdings;
        
        // DODANE: wczytanie limitów
        this.portfolioLimits = response.data.limits || {
          is_premium: false,
          portfolio_limit: 10,
          current_count: 0,
          can_add_more: true
        };
        
        this.$emit('portfolio-updated', response.data.portfolio_stats);
      } catch (error) {
        console.error('Error loading holdings:', error);
        this.showError('Failed to load portfolio holdings');
      } finally {
        this.loading = false;
      }
    },

    async saveHolding() {
      if (!this.selectedCrypto && !this.editingHolding) return;

      this.loading = true;
      try {
        const data = {
          cryptocurrency_id: this.editingHolding ? this.editingHolding.cryptocurrency_id : this.selectedCrypto.id,
          amount: parseFloat(this.holdingForm.amount),
          average_buy_price: this.holdingForm.average_buy_price ? parseFloat(this.holdingForm.average_buy_price) : null
        };

        if (this.editingHolding) {
          await window.axios.put(`/api/portfolio/${this.editingHolding.id}`, data);
          this.showSuccess('Holding updated successfully');
        } else {
          const response = await window.axios.post('/api/portfolio', data);
          
          // DODANE: aktualizacja limitów po dodaniu
          if (response.data.limits_info) {
            this.portfolioLimits = {
              ...this.portfolioLimits,
              ...response.data.limits_info
            };
          }
          
          this.showSuccess('Holding added successfully');
        }

        await this.loadHoldings();
        this.closeAddModal();
      } catch (error) {
        console.error('Error saving holding:', error);
        
        // DODANE: obsługa błędów limitów
        if (error.response && error.response.status === 403 && error.response.data.upgrade_required) {
          this.showUpgradeModal = true;
          this.closeAddModal();
          this.showError(error.response.data.message);
        } else {
          this.showError('Failed to save holding');
        }
      } finally {
        this.loading = false;
      }
    },

    // DODANE: nowe metody
    closeUpgradeModal() {
      this.showUpgradeModal = false;
    },

    // Reszta metod bez zmian...
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

    editHolding(holding) {
      this.editingHolding = holding;
      this.selectedCrypto = holding.cryptocurrency;
      this.searchQuery = `${holding.cryptocurrency.name} (${holding.cryptocurrency.symbol.toUpperCase()})`;
      this.holdingForm.amount = holding.amount.toString();
      this.holdingForm.average_buy_price = holding.average_buy_price ? holding.average_buy_price.toString() : '';
      this.showAddModal = true;
    },

    async deleteHolding(holding) {
      if (!confirm(`Are you sure you want to delete your ${holding.cryptocurrency.name} holding?`)) {
        return;
      }

      try {
        const response = await window.axios.delete(`/api/portfolio/${holding.id}`);
        
        // DODANE: aktualizacja limitów po usunięciu
        if (response.data.limits_info) {
          this.portfolioLimits = {
            ...this.portfolioLimits,
            ...response.data.limits_info
          };
        }
        
        this.showSuccess('Holding deleted successfully');
        await this.loadHoldings();
      } catch (error) {
        console.error('Error deleting holding:', error);
        this.showError('Failed to delete holding');
      }
    },

    closeAddModal() {
      this.showAddModal = false;
      this.editingHolding = null;
      this.selectedCrypto = null;
      this.searchQuery = '';
      this.searchResults = [];
      this.holdingForm = {
        amount: '',
        average_buy_price: ''
      };
    },

    calculateProfit(holding) {
      if (!holding.average_buy_price) return 0;
      const currentValue = holding.amount * holding.cryptocurrency.current_price_pln;
      const investedValue = holding.amount * holding.average_buy_price;
      return currentValue - investedValue;
    },

    calculateProfitPercent(holding) {
      if (!holding.average_buy_price) return 0;
      const profit = this.calculateProfit(holding);
      const investedValue = holding.amount * holding.average_buy_price;
      return investedValue > 0 ? (profit / investedValue) * 100 : 0;
    },

    getProfitClass(holding) {
      const profit = this.calculateProfit(holding);
      if (profit > 0) return 'profit-positive';
      if (profit < 0) return 'profit-negative';
      return 'profit-neutral';
    },

    formatAmount(amount) {
      return parseFloat(amount).toLocaleString('pl-PL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8
      });
    },

    formatPLN(amount) {
      return parseFloat(amount).toLocaleString('pl-PL', {
        style: 'currency',
        currency: 'PLN'
      });
    },

    formatPercent(percent) {
      return parseFloat(percent).toLocaleString('pl-PL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        signDisplay: 'always'
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
/* Istniejące style + DODANE nowe */

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

.upgrade-modal {
  max-width: 500px;
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
}

.upgrade-benefits li {
  padding: 0.5rem 0;
  color: #64748b;
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

.btn-premium {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: white;
}

.btn-premium:hover {
  background: linear-gradient(135deg, #7c3aed, #5b5cf1);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.4);
}

.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-limit {
  font-size: 0.8rem;
  opacity: 0.8;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
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

/* Reszta istniejących styli pozostaje bez zmian */
.portfolio-component {
  width: 100%;
}

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

.add-holding-form {
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
.add-holding-form input[type="number"] {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.8);
}

.crypto-search:focus,
.add-holding-form input[type="number"]:focus {
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

.crypto-icon-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.crypto-name {
  font-weight: 500;
  color: #1e293b;
}

.crypto-symbol {
  color: #64748b;
  font-size: 0.9rem;
  margin-left: auto;
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

.holdings-container {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.holdings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.holdings-header h3 {
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

.btn-large {
  padding: 1rem 2rem;
  font-size: 1.1rem;
}

.btn-icon {
  font-size: 1.2rem;
  font-weight: bold;
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

.holdings-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.holding-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
}

.holding-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  border-color: #6366f1;
}

.holding-main {
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

.holding-amounts {
  display: flex;
  gap: 2rem;
  flex: 1;
}

.amount-section,
.value-section,
.profit-section {
  flex: 1;
}

.amount-section label,
.value-section label,
.profit-section label {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.amount-value,
.current-value,
.profit-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
}

.price-per-unit {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.profit-positive {
  color: #10b981;
}

.profit-negative {
  color: #ef4444;
}

.profit-neutral {
  color: #64748b;
}

.profit-percent {
  font-size: 0.9rem;
  opacity: 0.8;
}

.holding-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .holdings-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .holding-main {
    flex-direction: column;
    gap: 1rem;
  }

  .holding-amounts {
    flex-direction: column;
    gap: 1rem;
  }

  .crypto-info {
    min-width: auto;
  }

  .form-actions {
    flex-direction: column;
  }

  .holding-actions {
    justify-content: stretch;
  }

  .banner-content {
    flex-direction: column;
    text-align: center;
  }

  .header-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>