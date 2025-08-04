<template>
  <div class="portfolio-content">
    <!-- Portfolio Summary -->
    <div class="portfolio-summary">
      <div class="summary-card">
        <h3>Podsumowanie Portfolio</h3>
        <div class="summary-stats">
          <div class="stat">
            <label>Całkowita wartość:</label>
            <span class="value">{{ formatCurrency(totalValue) }}</span>
          </div>
          <div class="stat">
            <label>Całkowity zysk/strata:</label>
            <span class="value" :class="{ 'positive': totalProfitLoss > 0, 'negative': totalProfitLoss < 0 }">
              {{ formatCurrency(totalProfitLoss) }} ({{ totalProfitLossPercentage.toFixed(2) }}%)
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Add New Position -->
    <div class="add-position-card">
      <h3>Dodaj Nową Pozycję</h3>
      <form @submit.prevent="addPosition" class="add-form">
        <div class="form-row">
          <div class="form-group">
            <label>Kryptowaluta</label>
            <select v-model="newPosition.cryptocurrency_id" required class="form-control">
              <option value="">Wybierz kryptowalutę</option>
              <option v-for="crypto in cryptocurrencies" :key="crypto.id" :value="crypto.id">
                {{ crypto.name }} ({{ crypto.symbol }})
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Ilość</label>
            <input v-model="newPosition.amount" type="number" step="0.00000001" required class="form-control">
          </div>
          
          <div class="form-group">
            <label>Średnia cena zakupu (PLN)</label>
            <input v-model="newPosition.average_buy_price" type="number" step="0.01" class="form-control">
          </div>
          
          <div class="form-group">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? 'Dodawanie...' : 'Dodaj' }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Portfolio List -->
    <div class="portfolio-list">
      <h3>Twoje Pozycje</h3>
      
      <div v-if="portfolios.length === 0" class="empty-state">
        <div class="empty-icon">📊</div>
        <h4>Brak pozycji w portfolio</h4>
        <p>Dodaj swoją pierwszą kryptowalutę aby rozpocząć śledzenie</p>
      </div>
      
      <div v-else class="portfolio-table">
        <div class="table-header">
          <div class="col-crypto">Kryptowaluta</div>
          <div class="col-amount">Ilość</div>
          <div class="col-price">Cena Zakupu</div>
          <div class="col-current">Aktualna Cena</div>
          <div class="col-value">Wartość</div>
          <div class="col-profit">Zysk/Strata</div>
          <div class="col-actions">Akcje</div>
        </div>
        
        <div v-for="portfolio in portfolios" :key="portfolio.id" class="table-row">
          <div class="col-crypto">
            <img :src="portfolio.cryptocurrency.image" :alt="portfolio.cryptocurrency.name" class="crypto-logo">
            <div class="crypto-info">
              <div class="crypto-name">{{ portfolio.cryptocurrency.name }}</div>
              <div class="crypto-symbol">{{ portfolio.cryptocurrency.symbol }}</div>
            </div>
          </div>
          
          <div class="col-amount">
            <input v-if="editingId === portfolio.id" 
                   v-model="editForm.amount" 
                   type="number" 
                   step="0.00000001" 
                   class="form-control-small">
            <span v-else>{{ portfolio.amount }}</span>
          </div>
          
          <div class="col-price">
            <input v-if="editingId === portfolio.id" 
                   v-model="editForm.average_buy_price" 
                   type="number" 
                   step="0.01" 
                   class="form-control-small">
            <span v-else>{{ formatCurrency(portfolio.average_buy_price) }}</span>
          </div>
          
          <div class="col-current">
            {{ formatCurrency(portfolio.cryptocurrency.current_price_pln) }}
          </div>
          
          <div class="col-value">
            {{ formatCurrency(portfolio.current_value) }}
          </div>
          
          <div class="col-profit" :class="{ 'positive': portfolio.profit_loss > 0, 'negative': portfolio.profit_loss < 0 }">
            <div>{{ formatCurrency(portfolio.profit_loss) }}</div>
            <div class="small">{{ portfolio.profit_loss_percentage?.toFixed(2) }}%</div>
          </div>
          
          <div class="col-actions">
            <button v-if="editingId === portfolio.id" 
                    @click="saveEdit(portfolio)" 
                    class="btn btn-small btn-success">
              Zapisz
            </button>
            <button v-if="editingId === portfolio.id" 
                    @click="cancelEdit" 
                    class="btn btn-small btn-secondary">
              Anuluj
            </button>
            <button v-else 
                    @click="startEdit(portfolio)" 
                    class="btn btn-small btn-secondary">
              Edytuj
            </button>
            <button @click="deletePosition(portfolio)" 
                    class="btn btn-small btn-danger">
              Usuń
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
  props: {
    initialPortfolios: {
      type: Array,
      default: () => []
    },
    cryptocurrencies: {
      type: Array,
      default: () => []
    }
  },
  
  data() {
    return {
      portfolios: [...this.initialPortfolios],
      loading: false,
      editingId: null,
      editForm: {},
      newPosition: {
        cryptocurrency_id: '',
        amount: '',
        average_buy_price: ''
      }
    }
  },
  
  computed: {
    totalValue() {
      return this.portfolios.reduce((sum, p) => sum + parseFloat(p.current_value || 0), 0);
    },
    
    totalInvested() {
      return this.portfolios.reduce((sum, p) => {
        const invested = parseFloat(p.amount || 0) * parseFloat(p.average_buy_price || 0);
        return sum + invested;
      }, 0);
    },
    
    totalProfitLoss() {
      return this.totalValue - this.totalInvested;
    },
    
    totalProfitLossPercentage() {
      return this.totalInvested > 0 ? (this.totalProfitLoss / this.totalInvested) * 100 : 0;
    }
  },
  
  methods: {
    async addPosition() {
      this.loading = true;
      
      try {
        const response = await axios.post('/portfolio', this.newPosition);
        
        if (response.data.success) {
          // Update or add portfolio item
          const existingIndex = this.portfolios.findIndex(p => p.id === response.data.portfolio.id);
          if (existingIndex >= 0) {
            this.portfolios[existingIndex] = response.data.portfolio;
          } else {
            this.portfolios.push(response.data.portfolio);
          }
          
          // Reset form
          this.newPosition = {
            cryptocurrency_id: '',
            amount: '',
            average_buy_price: ''
          };
          
          this.showSuccess('Pozycja została dodana!');
        }
      } catch (error) {
        this.showError('Błąd podczas dodawania pozycji');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    
    startEdit(portfolio) {
      this.editingId = portfolio.id;
      this.editForm = {
        amount: portfolio.amount,
        average_buy_price: portfolio.average_buy_price
      };
    },
    
    cancelEdit() {
      this.editingId = null;
      this.editForm = {};
    },
    
    async saveEdit(portfolio) {
      try {
        const response = await axios.put(`/portfolio/${portfolio.id}`, this.editForm);
        
        if (response.data.success) {
          const index = this.portfolios.findIndex(p => p.id === portfolio.id);
          if (index >= 0) {
            this.portfolios[index] = response.data.portfolio;
          }
          
          this.editingId = null;
          this.editForm = {};
          this.showSuccess('Pozycja została zaktualizowana!');
        }
      } catch (error) {
        this.showError('Błąd podczas aktualizacji pozycji');
        console.error(error);
      }
    },
    
    async deletePosition(portfolio) {
      if (!confirm(`Czy na pewno chcesz usunąć ${portfolio.cryptocurrency.symbol} z portfolio?`)) {
        return;
      }
      
      try {
        const response = await axios.delete(`/portfolio/${portfolio.id}`);
        
        if (response.data.success) {
          this.portfolios = this.portfolios.filter(p => p.id !== portfolio.id);
          this.showSuccess('Pozycja została usunięta!');
        }
      } catch (error) {
        this.showError('Błąd podczas usuwania pozycji');
        console.error(error);
      }
    },
    
    formatCurrency(value) {
      return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN'
      }).format(value || 0);
    },
    
    showSuccess(message) {
      // Simple notification - you can integrate with a toast library
      alert(message);
    },
    
    showError(message) {
      alert(message);
    }
  }
}
</script>