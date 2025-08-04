<template>
  <div class="alerts-content">
    <!-- Add New Alert -->
    <div class="add-alert-card">
      <h3>Dodaj Nowy Alert</h3>
      <form @submit.prevent="addAlert" class="add-form">
        <div class="form-row">
          <div class="form-group">
            <label>Kryptowaluta</label>
            <select v-model="newAlert.cryptocurrency_id" required class="form-control">
              <option value="">Wybierz kryptowalutę</option>
              <option v-for="crypto in cryptocurrencies" :key="crypto.id" :value="crypto.id">
                {{ crypto.name }} ({{ crypto.symbol }})
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Typ alertu</label>
            <select v-model="newAlert.alert_type" required class="form-control">
              <option value="above">Powyżej ceny</option>
              <option value="below">Poniżej ceny</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Cena docelowa</label>
            <input v-model="newAlert.target_price" type="number" step="0.01" required class="form-control">
          </div>
          
          <div class="form-group">
            <label>Waluta</label>
            <select v-model="newAlert.currency" class="form-control">
              <option value="PLN">PLN</option>
              <option value="USD">USD</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="newAlert.email_notification">
              <span class="checkmark"></span>
              Powiadomienie email
            </label>
          </div>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="newAlert.push_notification">
              <span class="checkmark"></span>
              Powiadomienie push
            </label>
          </div>
          
          <div class="form-group">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              {{ loading ? 'Dodawanie...' : 'Dodaj Alert' }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Alerts List -->
    <div class="alerts-list">
      <h3>Twoje Alerty</h3>
      
      <div v-if="alerts.length === 0" class="empty-state">
        <div class="empty-icon">🔔</div>
        <h4>Brak alertów</h4>
        <p>Dodaj swój pierwszy alert cenowy aby otrzymywać powiadomienia</p>
      </div>
      
      <div v-else class="alerts-grid">
        <div v-for="alert in alerts" :key="alert.id" class="alert-card" :class="{ 'inactive': !alert.is_active, 'triggered': alert.triggered_at }">
          <div class="alert-header">
            <img :src="alert.cryptocurrency.image" :alt="alert.cryptocurrency.name" class="crypto-logo">
            <div class="alert-crypto">
              <div class="crypto-name">{{ alert.cryptocurrency.name }}</div>
              <div class="crypto-symbol">{{ alert.cryptocurrency.symbol }}</div>
            </div>
            <div class="alert-status">
              <span v-if="alert.triggered_at" class="status-badge triggered">Uruchomiony</span>
              <span v-else-if="alert.is_active" class="status-badge active">Aktywny</span>
              <span v-else class="status-badge inactive">Nieaktywny</span>
            </div>
          </div>
          
          <div class="alert-details">
            <div class="alert-condition">
              <strong>{{ alert.alert_type === 'above' ? 'Powyżej' : 'Poniżej' }}:</strong>
              {{ formatCurrency(alert.target_price, alert.currency) }}
            </div>
            
            <div class="alert-current">
              <strong>Aktualna cena:</strong>
              {{ formatCurrency(getCurrentPrice(alert), alert.currency) }}
            </div>
            
            <div class="alert-notifications">
              <span v-if="alert.email_notification" class="notification-type">📧</span>
              <span v-if="alert.push_notification" class="notification-type">📱</span>
            </div>
            
            <div v-if="alert.triggered_at" class="alert-triggered">
              Uruchomiony: {{ formatDate(alert.triggered_at) }}
            </div>
          </div>
          
          <div class="alert-actions">
            <button v-if="editingId === alert.id" 
                    @click="saveEdit(alert)" 
                    class="btn btn-small btn-success">
              Zapisz
            </button>
            <button v-if="editingId === alert.id" 
                    @click="cancelEdit" 
                    class="btn btn-small btn-secondary">
              Anuluj
            </button>
            <button v-else 
                    @click="startEdit(alert)" 
                    class="btn btn-small btn-secondary">
              Edytuj
            </button>
            <button @click="toggleAlert(alert)" 
                    class="btn btn-small" 
                    :class="alert.is_active ? 'btn-warning' : 'btn-success'">
              {{ alert.is_active ? 'Deaktywuj' : 'Aktywuj' }}
            </button>
            <button @click="deleteAlert(alert)" 
                    class="btn btn-small btn-danger">
              Usuń
            </button>
          </div>
          
          <!-- Edit form overlay -->
          <div v-if="editingId === alert.id" class="edit-overlay">
            <div class="edit-form">
              <div class="form-group">
                <label>Cena docelowa</label>
                <input v-model="editForm.target_price" type="number" step="0.01" class="form-control">
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="editForm.email_notification">
                  <span class="checkmark"></span>
                  Email
                </label>
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="editForm.push_notification">
                  <span class="checkmark"></span>
                  Push
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PriceAlertComponent',
  props: {
    initialAlerts: {
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
      alerts: [...this.initialAlerts],
      loading: false,
      editingId: null,
      editForm: {},
      newAlert: {
        cryptocurrency_id: '',
        alert_type: 'above',
        target_price: '',
        currency: 'PLN',
        email_notification: true,
        push_notification: true
      }
    }
  },
  
  methods: {
    async addAlert() {
      this.loading = true;
      
      try {
        const response = await axios.post('/alerts', this.newAlert);
        
        if (response.data.success) {
          this.alerts.unshift(response.data.alert);
          
          // Reset form
          this.newAlert = {
            cryptocurrency_id: '',
            alert_type: 'above',
            target_price: '',
            currency: 'PLN',
            email_notification: true,
            push_notification: true
          };
          
          this.showSuccess('Alert został dodany!');
        }
      } catch (error) {
        this.showError('Błąd podczas dodawania alertu');
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    
    startEdit(alert) {
      this.editingId = alert.id;
      this.editForm = {
        target_price: alert.target_price,
        email_notification: alert.email_notification,
        push_notification: alert.push_notification
      };
    },
    
    cancelEdit() {
      this.editingId = null;
      this.editForm = {};
    },
    
    async saveEdit(alert) {
      try {
        const response = await axios.put(`/alerts/${alert.id}`, this.editForm);
        
        if (response.data.success) {
          const index = this.alerts.findIndex(a => a.id === alert.id);
          if (index >= 0) {
            this.alerts[index] = response.data.alert;
          }
          
          this.editingId = null;
          this.editForm = {};
          this.showSuccess('Alert został zaktualizowany!');
        }
      } catch (error) {
        this.showError('Błąd podczas aktualizacji alertu');
        console.error(error);
      }
    },
    
    async toggleAlert(alert) {
      try {
        const response = await axios.put(`/alerts/${alert.id}`, {
          is_active: !alert.is_active,
          target_price: alert.target_price,
          email_notification: alert.email_notification,
          push_notification: alert.push_notification
        });
        
        if (response.data.success) {
          const index = this.alerts.findIndex(a => a.id === alert.id);
          if (index >= 0) {
            this.alerts[index] = response.data.alert;
          }
          
          this.showSuccess(`Alert został ${alert.is_active ? 'deaktywowany' : 'aktywowany'}!`);
        }
      } catch (error) {
        this.showError('Błąd podczas zmiany statusu alertu');
        console.error(error);
      }
    },
    
    async deleteAlert(alert) {
      if (!confirm(`Czy na pewno chcesz usunąć alert dla ${alert.cryptocurrency.symbol}?`)) {
        return;
      }
      
      try {
        const response = await axios.delete(`/alerts/${alert.id}`);
        
        if (response.data.success) {
          this.alerts = this.alerts.filter(a => a.id !== alert.id);
          this.showSuccess('Alert został usunięty!');
        }
      } catch (error) {
        this.showError('Błąd podczas usuwania alertu');
        console.error(error);
      }
    },
    
    getCurrentPrice(alert) {
      return alert.currency === 'PLN' 
        ? alert.cryptocurrency.current_price_pln 
        : alert.cryptocurrency.current_price_usd;
    },
    
    formatCurrency(value, currency = 'PLN') {
      return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: currency
      }).format(value || 0);
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleString('pl-PL');
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