<template>
  <div class="dashboard-vue">
    <!-- Notification Manager -->
    <notification-manager 
      :show-status="activeTab === 'alerts'" 
      @notifications-enabled="onNotificationsEnabled"
    ></notification-manager>
    
    <div class="dashboard-stats">
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <h3>Portfolio Value</h3>
          <p class="stat-value">{{ portfolioValue }} PLN</p>
          <span class="stat-change" :class="portfolioChangeClass">
            {{ portfolioChange }}%
          </span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-info">
          <h3>24h Change</h3>
          <p class="stat-value">{{ dailyChange }} PLN</p>
          <span class="stat-change" :class="dailyChangeClass">
            {{ dailyChangePercent }}%
          </span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">🔔</div>
        <div class="stat-info">
          <h3>Active Alerts</h3>
          <p class="stat-value">{{ activeAlerts }}</p>
          <span class="stat-label">alerts set</span>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Navigation Tabs -->
      <div class="dashboard-tabs">
        <nav class="tab-nav">
          <button 
            @click="activeTab = 'portfolio'" 
            :class="['tab-button', { 'tab-active': activeTab === 'portfolio' }]"
          >
            <span class="tab-icon">📊</span>
            Portfolio
          </button>
          <button 
            @click="activeTab = 'alerts'" 
            :class="['tab-button', { 'tab-active': activeTab === 'alerts' }]"
          >
            <span class="tab-icon">🔔</span>
            Price Alerts
            <span v-if="activeAlerts > 0" class="alert-badge">{{ activeAlerts }}</span>
          </button>
          <button 
            @click="activeTab = 'watchlist'" 
            :class="['tab-button', { 'tab-active': activeTab === 'watchlist' }]"
          >
            <span class="tab-icon">⭐</span>
            Watchlist
          </button>
          <button 
            @click="activeTab = 'discovery'" 
            :class="['tab-button', { 'tab-active': activeTab === 'discovery' }]"
          >
            <span class="tab-icon">🔥</span>
            Discovery
          </button>
          <button 
            v-if="isPremium"
            @click="activeTab = 'analytics'" 
            :class="['tab-button', { 'tab-active': activeTab === 'analytics' }]"
          >
            <span class="tab-icon">🤖</span>
            AI Analytics
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <div v-show="activeTab === 'portfolio'" class="tab-panel">
          <portfolio-component @portfolio-updated="updatePortfolioStats"></portfolio-component>
        </div>
        
        <div v-show="activeTab === 'alerts'" class="tab-panel">
          <alerts-component @alerts-updated="updateAlertsCount"></alerts-component>
        </div>
        
        <div v-show="activeTab === 'watchlist'" class="tab-panel">
          <watchlist-component></watchlist-component>
        </div>
        
        <div v-show="activeTab === 'discovery'" class="tab-panel">
          <discovery-component :is-premium="isPremium"></discovery-component>
        </div>
        
        <div v-show="activeTab === 'analytics'" class="tab-panel">
          <sentiment-analytics-component v-if="isPremium"></sentiment-analytics-component>
          <div v-else class="premium-content">
            <h3>🤖 AI Trend Analysis</h3>
            <p>Upgrade to Premium to unlock AI-powered sentiment analysis from Polish crypto communities!</p>
            <button class="btn btn-premium" style="margin-top: 1rem;">
              Upgrade to Premium - 19 PLN/month
            </button>
          </div>
        </div>
      </div>
      
      <!-- Premium Upsell - Show when not premium and not on premium tab -->
      <div v-if="!isPremium" class="premium-section">
        <div class="premium-upsell">
          <div class="upsell-content">
            <h3>🤖 Unlock AI Trend Analysis</h3>
            <p>Get insights from Polish crypto communities and advanced sentiment analysis</p>
            <ul class="premium-features">
              <li>✅ AI sentiment analysis from Reddit, Twitter, forums</li>
              <li>✅ Polish market trend detection</li>
              <li>✅ Advanced price alerts</li>
              <li>✅ Portfolio statistics & charts</li>
            </ul>
            <button class="btn btn-premium">
              Upgrade to Premium - 19 PLN/month
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Holding Modal placeholder -->
    <div v-if="showAddHolding" class="modal-overlay" @click="showAddHolding = false">
      <div class="modal-content" @click.stop>
        <h3>Add Holding - Coming Soon</h3>
        <p>This feature will be implemented in the next step!</p>
        <button class="btn btn-primary" @click="showAddHolding = false">Close</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DashboardComponent',
  data() {
    return {
      loading: true,
      portfolioValue: 0,
      portfolioChange: 0,
      dailyChange: 0,
      dailyChangePercent: 0,
      activeAlerts: 0,
      isPremium: false,
      activeTab: 'portfolio'
    }
  },
  computed: {
    portfolioChangeClass() {
      return this.portfolioChange >= 0 ? 'positive' : 'negative';
    },
    dailyChangeClass() {
      return this.dailyChangePercent >= 0 ? 'positive' : 'negative';
    }
  },
  async mounted() {
    await this.loadDashboardData();
  },
  methods: {
    async loadDashboardData() {
      try {
        // Get user data including premium status
        const userResponse = await window.axios.get('/api/user-status');
        this.isPremium = userResponse.data.isPremium || false;
        
        console.log('Premium status:', this.isPremium);
        
        this.loading = false;
      } catch (error) {
        console.error('Error loading dashboard:', error);
        this.isPremium = false; // Fallback to false
        this.loading = false;
      }
    },

    updatePortfolioStats(stats) {
      this.portfolioValue = stats.total_value || 0;
      this.portfolioChange = stats.profit_loss_percent || 0;
      this.dailyChange = stats.profit_loss || 0;
      this.dailyChangePercent = stats.profit_loss_percent || 0;
    },

    updateAlertsCount(count) {
      this.activeAlerts = count;
    },

    onNotificationsEnabled() {
      console.log('Push notifications enabled successfully!');
      // Może tutaj pokazać jakąś wiadomość sukcesu
    }
  }
}
</script>

<style scoped>
.dashboard-vue {
  width: 100%;
}

.dashboard-tabs {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  overflow: hidden;
}

.tab-nav {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
}

.tab-button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  border-right: 1px solid #e2e8f0;
}

.tab-button:last-child {
  border-right: none;
}

.tab-button:hover {
  background: #f8fafc;
  color: #334155;
}

.tab-button.tab-active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  box-shadow: inset 0 -3px 0 rgba(255, 255, 255, 0.3);
}

.tab-button.tab-active:hover {
  background: linear-gradient(135deg, #5b5cf1, #7c3aed);
}

.tab-icon {
  font-size: 1.2rem;
}

.alert-badge {
  background: #ef4444;
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
  padding: 0.25rem 0.5rem;
  border-radius: 10px;
  min-width: 1.5rem;
  text-align: center;
  margin-left: 0.5rem;
}

.tab-content {
  animation: fadeIn 0.3s ease-in-out;
}

.tab-panel {
  min-height: 400px;
}

.premium-section {
  margin-top: 3rem;
  padding-top: 3rem;
  border-top: 2px dashed #e2e8f0;
}

@media (max-width: 768px) {
  .premium-section {
    margin-top: 2rem;
    padding-top: 2rem;
  }
}

@media (max-width: 480px) {
  .premium-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
  }
}

.premium-content {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.premium-content h3 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.5rem;
}

.premium-content p {
  color: #64748b;
  font-size: 1.1rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

@media (max-width: 768px) {
  .dashboard-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
  }
}

@media (max-width: 480px) {
  .dashboard-stats {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
  .stat-card {
    padding: 1.25rem;
    gap: 0.75rem;
  }
}

@media (max-width: 480px) {
  .stat-card {
    padding: 1rem;
    gap: 0.75rem;
    flex-direction: column;
    text-align: center;
  }
}

.stat-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 12px;
}

.stat-info h3 {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1e293b;
  margin: 0;
}

.stat-change {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
}

.stat-change.positive {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-change.negative {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-label {
  font-size: 0.8rem;
  color: #64748b;
}

.content-section {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
}

.content-section h2 {
  color: #1e293b;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.holdings-empty {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.holdings-empty h3 {
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.holdings-empty p {
  color: #64748b;
  margin-bottom: 2rem;
}

.premium-upsell {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: white;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  margin: -2rem -2rem -2rem -2rem;
  position: relative;
  overflow: hidden;
}

@media (max-width: 768px) {
  .premium-upsell {
    margin: -1.5rem -1.5rem -1.5rem -1.5rem;
    padding: 1.5rem;
    border-radius: 8px;
  }
}

@media (max-width: 480px) {
  .premium-upsell {
    margin: -1rem -1rem -1rem -1rem;
    padding: 1.25rem;
    border-radius: 8px;
  }
}

.upsell-content h3 {
  margin-bottom: 1rem;
  font-size: 1.5rem;
}

.upsell-content p {
  margin-bottom: 1.5rem;
  opacity: 0.9;
}

@media (max-width: 768px) {
  .upsell-content h3 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
  }
  
  .upsell-content p {
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
  }
}

@media (max-width: 480px) {
  .upsell-content h3 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
  }
  
  .upsell-content p {
    font-size: 0.9rem;
    margin-bottom: 1rem;
  }
}

.premium-features {
  list-style: none;
  text-align: left;
  max-width: 400px;
  margin: 0 auto 2rem;
}

.premium-features li {
  padding: 0.5rem 0;
  opacity: 0.9;
}

@media (max-width: 768px) {
  .premium-features {
    max-width: 100%;
    margin: 0 auto 1.5rem;
    text-align: center;
  }
  
  .premium-features li {
    padding: 0.4rem 0;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .premium-features {
    margin: 0 auto 1rem;
  }
  
  .premium-features li {
    padding: 0.3rem 0;
    font-size: 0.85rem;
  }
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  text-align: center;
}

@media (max-width: 768px) {
  .dashboard-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .stat-card {
    padding: 1.25rem;
  }
  
  .content-section {
    padding: 1.5rem;
    margin: 0 -0.5rem 1.5rem;
  }
  
  .tab-nav {
    flex-direction: column;
  }
  
  .tab-button {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.25rem 1rem;
  }
  
  .tab-button:last-child {
    border-bottom: none;
  }
  
  .tab-button.tab-active {
    box-shadow: inset 3px 0 0 rgba(255, 255, 255, 0.3);
  }
  
  .premium-section {
    margin-top: 2rem;
    padding-top: 2rem;
  }
}

@media (max-width: 480px) {
  .dashboard-tabs {
    margin: 0 -1rem 2rem;
    border-radius: 0;
  }
  
  .tab-button {
    font-size: 0.9rem;
    padding: 1rem;
  }
  
  .tab-icon {
    font-size: 1rem;
  }
}
</style>