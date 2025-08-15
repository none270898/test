
<template>
  <div class="admin-dashboard">
    <!-- Loading State -->
    <div v-if="loading" class="admin-loading">
      <div class="loading-spinner"></div>
      <p>Loading admin dashboard...</p>
    </div>

    <!-- Main Dashboard -->
    <div v-else class="admin-content">
      <!-- Stats Overview -->
      <div class="stats-grid">
        <div class="stat-card users">
          <div class="stat-header">
            <h3>👥 Users</h3>
            <span class="stat-trend positive">{{ stats.users?.today || 0 }} today</span>
          </div>
          <div class="stat-main">
            <div class="stat-number">{{ stats.users?.total || 0 }}</div>
            <div class="stat-details">
              <span class="premium">{{ stats.users?.premium || 0 }} Premium</span>
              <span class="verified">{{ stats.users?.verified || 0 }} Verified</span>
            </div>
          </div>
        </div>

        <div class="stat-card revenue">
          <div class="stat-header">
            <h3>💰 Revenue</h3>
            <span class="stat-trend">{{ stats.revenue?.conversion_rate || 0 }}% conversion</span>
          </div>
          <div class="stat-main">
            <div class="stat-number">{{ stats.revenue?.monthly || 0 }} PLN/month</div>
            <div class="stat-details">
              <span>{{ stats.revenue?.annual || 0 }} PLN/year</span>
            </div>
          </div>
        </div>

        <div class="stat-card activity">
          <div class="stat-header">
            <h3>📊 Activity</h3>
            <span class="stat-trend">{{ stats.activity?.cryptocurrencies || 0 }} cryptos</span>
          </div>
          <div class="stat-main">
            <div class="stat-number">{{ stats.activity?.portfolio_items || 0 }}</div>
            <div class="stat-details">
              <span>{{ stats.activity?.active_alerts || 0 }} alerts</span>
              <span>{{ stats.activity?.watchlist_items || 0 }} watchlist</span>
            </div>
          </div>
        </div>

        <div class="stat-card engagement">
          <div class="stat-header">
            <h3>🎯 Engagement</h3>
            <span class="stat-trend">{{ Math.round(stats.engagement?.avg_portfolio_size || 0) }} avg portfolio</span>
          </div>
          <div class="stat-main">
            <div class="stat-number">{{ stats.engagement?.users_with_portfolio || 0 }}</div>
            <div class="stat-details">
              <span>{{ stats.engagement?.users_with_alerts || 0 }} with alerts</span>
              <span>{{ stats.engagement?.users_with_watchlist || 0 }} with watchlist</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="admin-tabs">
        <nav class="tab-nav">
          <button 
            @click="activeTab = 'overview'" 
            :class="['tab-button', { 'tab-active': activeTab === 'overview' }]"
          >
            📊 Overview
          </button>
          <button 
            @click="activeTab = 'users'" 
            :class="['tab-button', { 'tab-active': activeTab === 'users' }]"
          >
            👥 Users ({{ stats.users?.total || 0 }})
          </button>
          <button 
            @click="activeTab = 'cryptos'" 
            :class="['tab-button', { 'tab-active': activeTab === 'cryptos' }]"
          >
            💎 Cryptocurrencies
          </button>
          <button 
            @click="activeTab = 'system'" 
            :class="['tab-button', { 'tab-active': activeTab === 'system' }]"
          >
            ⚙️ System
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Overview Tab -->
        <div v-show="activeTab === 'overview'" class="tab-panel">
          <div class="overview-grid">
            <!-- Charts -->
            <div class="chart-card">
              <h3>📈 User Registrations (30 days)</h3>
              <div class="chart-placeholder" v-if="!systemStats.user_registrations?.length">
                No data available
              </div>
              <div v-else class="simple-chart">
                <div 
                  v-for="(day, index) in systemStats.user_registrations" 
                  :key="index"
                  class="chart-bar"
                  :style="{ height: Math.max(day.count * 20, 5) + 'px' }"
                  :title="`${day.date}: ${day.count} users`"
                ></div>
              </div>
            </div>

            <div class="chart-card">
              <h3>🎯 Popular Cryptocurrencies</h3>
              <div class="crypto-list">
                <div 
                  v-for="crypto in systemStats.popular_cryptos?.slice(0, 5)" 
                  :key="crypto.cryptocurrency_id"
                  class="crypto-item"
                >
                  <span class="crypto-name">{{ crypto.cryptocurrency?.name || 'Unknown' }}</span>
                  <span class="crypto-users">{{ crypto.user_count }} users</span>
                </div>
              </div>
            </div>

            <!-- Recent Activities -->
            <div class="activity-card">
              <h3>🔔 Recent Activities</h3>
              <div class="activity-list">
                <div v-for="activity in systemLogs" :key="activity.type" class="activity-group">
                  <h4>{{ activity.description }}</h4>
                  <div v-for="item in activity.data" :key="item.id" class="activity-item">
                    <span class="activity-name">{{ item.name }}</span>
                    <span class="activity-time">{{ formatDate(item.created_at || item.updated_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Tab -->
        <div v-show="activeTab === 'users'" class="tab-panel">
          <div class="users-section">
            <!-- Users Controls -->
            <div class="section-header">
              <h3>User Management</h3>
              <div class="controls">
                <select v-model="userFilter" @change="loadUsers" class="filter-select">
                  <option value="">All Users</option>
                  <option value="premium">Premium Only</option>
                  <option value="free">Free Only</option>
                  <option value="unverified">Unverified</option>
                </select>
                <input 
                  v-model="userSearch" 
                  @input="debounceSearch"
                  placeholder="Search users..." 
                  class="search-input"
                >
              </div>
            </div>

            <!-- Users Table -->
            <div class="users-table-container">
              <table class="users-table">
                <thead>
                  <tr>
                    <th @click="sortUsers('name')">Name</th>
                    <th @click="sortUsers('email')">Email</th>
                    <th>Status</th>
                    <th>Stats</th>
                    <th @click="sortUsers('created_at')">Joined</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users.data" :key="user.id" class="user-row">
                    <td>
                      <div class="user-info">
                        <strong>{{ user.name }}</strong>
                        <span v-if="user.is_admin" class="admin-badge-small">ADMIN</span>
                      </div>
                    </td>
                    <td>{{ user.email }}</td>
                    <td>
                      <div class="status-badges">
                        <span v-if="user.premium" class="badge premium">Premium</span>
                        <span v-else class="badge free">Free</span>
                        <span v-if="user.email_verified_at" class="badge verified">✓</span>
                        <span v-else class="badge unverified">Unverified</span>
                      </div>
                    </td>
                    <td>
                      <div class="user-stats">
                        <span>{{ user.stats?.portfolio_count || 0 }} portfolio</span>
                        <span>{{ user.stats?.alerts_count || 0 }} alerts</span>
                        <span>{{ user.stats?.portfolio_value?.toFixed(2) || 0 }} PLN</span>
                      </div>
                    </td>
                    <td>{{ formatDate(user.created_at) }}</td>
                    <td>
                      <div class="user-actions">
                        <button 
                          @click="toggleUserPremium(user)"
                          :class="['btn', 'btn-sm', user.premium ? 'btn-warning' : 'btn-success']"
                        >
                          {{ user.premium ? 'Remove Premium' : 'Add Premium' }}
                        </button>
                        <button 
                          @click="viewUserDetails(user)"
                          class="btn btn-sm btn-info"
                        >
                          Details
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="pagination">
              <button 
                @click="loadUsers(users.current_page - 1)"
                :disabled="users.current_page <= 1"
                class="btn btn-sm"
              >
                Previous
              </button>
              <span class="page-info">
                Page {{ users.current_page }} of {{ users.last_page }}
              </span>
              <button 
                @click="loadUsers(users.current_page + 1)"
                :disabled="users.current_page >= users.last_page"
                class="btn btn-sm"
              >
                Next
              </button>
            </div>
          </div>
        </div>

        <!-- Cryptocurrencies Tab -->
        <div v-show="activeTab === 'cryptos'" class="tab-panel">
          <div class="cryptos-section">
            <div class="section-header">
              <h3>Cryptocurrency Management</h3>
              <div class="controls">
                <input 
                  v-model="cryptoSearch" 
                  @input="debounceCryptoSearch"
                  placeholder="Search cryptocurrencies..." 
                  class="search-input"
                >
              </div>
            </div>

            <div class="cryptos-table-container">
              <table class="cryptos-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Symbol</th>
                    <th>Portfolio Holdings</th>
                    <th>Price Alerts</th>
                    <th>Watchlists</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="crypto in cryptocurrencies.data" :key="crypto.id">
                    <td>{{ crypto.name }}</td>
                    <td>{{ crypto.symbol }}</td>
                    <td>{{ crypto.portfolio_holdings_count || 0 }}</td>
                    <td>{{ crypto.price_alerts_count || 0 }}</td>
                    <td>{{ crypto.watchlists_count || 0 }}</td>
                    <td>
  <button 
    @click="viewCryptoDetails(crypto)" 
    class="btn btn-sm btn-info"
  >
    View Details
  </button>
</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- System Tab -->
        <div v-show="activeTab === 'system'" class="tab-panel">
          <div class="system-section">
            <h3>System Information</h3>
            <div class="system-grid">
              <div class="system-card">
                <h4>📊 Database Stats</h4>
                <div class="system-stats">
                  <div>Total Users: {{ stats.users?.total || 0 }}</div>
                  <div>Premium Users: {{ stats.users?.premium || 0 }}</div>
                </div>
              </div>

              <div class="system-card">
                <h4>🎯 Engagement Stats</h4>
                <div class="system-stats">
                  <div>Users with Portfolio: {{ stats.engagement?.users_with_portfolio || 0 }}</div>
                  <div>Users with Alerts: {{ stats.engagement?.users_with_alerts || 0 }}</div>
                  <div>Users with Watchlist: {{ stats.engagement?.users_with_watchlist || 0 }}</div>
                  <div>Avg Portfolio Size: {{ Math.round(stats.engagement?.avg_portfolio_size || 0) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Details Modal -->
    <div v-if="showUserModal" class="modal-overlay" @click="closeUserModal">
      <div class="modal-content user-modal" @click.stop>
        <div class="modal-header">
          <h3>👤 User Details: {{ selectedUser?.name }}</h3>
          <button @click="closeUserModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" v-if="selectedUser">
          <div class="user-details-grid">
            <div class="user-info-section">
              <h4>📝 Basic Info</h4>
              <div class="info-grid">
                <div><strong>Name:</strong> {{ selectedUser.name }}</div>
                <div><strong>Email:</strong> {{ selectedUser.email }}</div>
                <div><strong>Status:</strong> 
                  <span :class="['badge', selectedUser.premium ? 'premium' : 'free']">
                    {{ selectedUser.premium ? 'Premium' : 'Free' }}
                  </span>
                </div>
                <div><strong>Verified:</strong> 
                  <span :class="['badge', selectedUser.email_verified_at ? 'verified' : 'unverified']">
                    {{ selectedUser.email_verified_at ? 'Yes' : 'No' }}
                  </span>
                </div>
                <div><strong>Joined:</strong> {{ formatDate(selectedUser.created_at) }}</div>
                <div v-if="selectedUser.premium_expires_at">
                  <strong>Premium Expires:</strong> {{ formatDate(selectedUser.premium_expires_at) }}
                </div>
              </div>
            </div>

            <div class="user-stats-section">
              <h4>📊 Statistics</h4>
              <div class="stats-grid">
                <div class="stat-item">
                  <span class="stat-label">Portfolio Value</span>
                  <span class="stat-value">{{ userDetails?.stats?.portfolio_value?.toFixed(2) || 0 }} PLN</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Total Invested</span>
                  <span class="stat-value">{{ userDetails?.stats?.total_invested?.toFixed(2) || 0 }} PLN</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Profit/Loss</span>
                  <span :class="['stat-value', userDetails?.stats?.profit_loss >= 0 ? 'positive' : 'negative']">
                    {{ userDetails?.stats?.profit_loss?.toFixed(2) || 0 }} PLN 
                    ({{ userDetails?.stats?.profit_loss_percent?.toFixed(2) || 0 }}%)
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="user-activity-section">
            <div class="activity-tabs">
              <button 
                @click="userActiveTab = 'portfolio'" 
                :class="['tab-btn', { active: userActiveTab === 'portfolio' }]"
              >
                Portfolio ({{ selectedUser.portfolio_holdings?.length || 0 }})
              </button>
              <button 
                @click="userActiveTab = 'alerts'" 
                :class="['tab-btn', { active: userActiveTab === 'alerts' }]"
              >
                Alerts ({{ selectedUser.price_alerts?.length || 0 }})
              </button>
              <button 
                @click="userActiveTab = 'watchlist'" 
                :class="['tab-btn', { active: userActiveTab === 'watchlist' }]"
              >
                Watchlist ({{ selectedUser.watchlist?.length || 0 }})
              </button>
            </div>

            <div class="activity-content">
              <div v-if="userActiveTab === 'portfolio'" class="portfolio-list">
                <div v-for="holding in selectedUser.portfolio_holdings" :key="holding.id" class="portfolio-item">
                  <span class="crypto-name">{{ holding.cryptocurrency?.name }}</span>
                  <span class="amount">{{ holding.amount }}</span>
                  <span class="avg-price">{{ holding.average_buy_price || 0 }} PLN</span>
                </div>
              </div>
              
              <div v-if="userActiveTab === 'alerts'" class="alerts-list">
                <div v-for="alert in selectedUser.price_alerts" :key="alert.id" class="alert-item">
                  <span class="crypto-name">{{ alert.cryptocurrency?.name }}</span>
                  <span class="alert-type">{{ alert.type }}</span>
                  <span class="target-price">{{ alert.target_price }} {{ alert.currency }}</span>
                  <span :class="['status', alert.is_active ? 'active' : 'inactive']">
                    {{ alert.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
              
              <div v-if="userActiveTab === 'watchlist'" class="watchlist-list">
                <div v-for="item in selectedUser.watchlist" :key="item.id" class="watchlist-item">
                  <span class="crypto-name">{{ item.cryptocurrency?.name }}</span>
                  <span class="notifications">
                    {{ item.notifications_enabled ? 'Notifications On' : 'Notifications Off' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Crypto Details Modal -->
    <div v-if="showCryptoModal" class="modal-overlay" @click="closeCryptoModal">
      <div class="modal-content crypto-modal" @click.stop>
        <div class="modal-header">
          <h3>💎 {{ selectedCrypto?.name }} ({{ selectedCrypto?.symbol }}) Details</h3>
          <button @click="closeCryptoModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" v-if="cryptoDetails">
          <div class="crypto-details-grid">
            <!-- Basic Info -->
            <div class="crypto-info-section">
              <h4>📝 Basic Information</h4>
              <div class="info-grid">
                <div><strong>Name:</strong> {{ cryptoDetails.cryptocurrency.name }}</div>
                <div><strong>Symbol:</strong> {{ cryptoDetails.cryptocurrency.symbol }}</div>
                <div><strong>CoinGecko ID:</strong> {{ cryptoDetails.cryptocurrency.coingecko_id }}</div>
                <div><strong>Created:</strong> {{ formatDate(cryptoDetails.cryptocurrency.created_at) }}</div>
                <div><strong>Last Updated:</strong> {{ formatDate(cryptoDetails.cryptocurrency.updated_at) }}</div>
              </div>
            </div>

            <!-- Price Stats -->
            <div class="price-stats-section">
              <h4>💰 Price Statistics</h4>
              <div class="stats-grid">
                <div class="stat-item">
                  <span class="stat-label">Current Price (PLN)</span>
                  <span class="stat-value">{{ parseFloat(cryptoDetails.stats.price_stats.current_price_pln)?.toFixed(2) || 'N/A' }} PLN</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Current Price (USD)</span>
                  <span class="stat-value">${{ parseFloat(cryptoDetails.stats.price_stats.current_price_usd)?.toFixed(4) || 'N/A' }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">24h Change</span>
                  <span :class="['stat-value', cryptoDetails.stats.price_stats.price_change_24h >= 0 ? 'positive' : 'negative']">
                    {{ parseFloat(cryptoDetails.stats.price_stats.price_change_24h)?.toFixed(2) || 0 }}%
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Last Update</span>
                  <span class="stat-value">{{ cryptoDetails.stats.price_stats.last_price_update }}</span>
                </div>
              </div>
            </div>

            <!-- Sentiment Stats -->
            <div class="sentiment-stats-section">
              <h4>🤖 Sentiment Statistics</h4>
              <div class="stats-grid">
                <div class="stat-item">
                  <span class="stat-label">Current Sentiment</span>
                  <span :class="['stat-value', getSentimentClass(cryptoDetails.stats.sentiment_stats.current_sentiment)]">
                    {{ parseFloat(cryptoDetails.stats.sentiment_stats.current_sentiment)?.toFixed(2) || 'N/A' }}
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">24h Sentiment Change</span>
                  <span :class="['stat-value', cryptoDetails.stats.sentiment_stats.sentiment_change_24h >= 0 ? 'positive' : 'negative']">
                    {{ parseFloat(cryptoDetails.stats.sentiment_stats.sentiment_change_24h)?.toFixed(2) || 0 }}
                  </span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Daily Mentions</span>
                  <span class="stat-value">{{ cryptoDetails.stats.sentiment_stats.daily_mentions || 0 }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Trending Score</span>
                  <span class="stat-value">{{ cryptoDetails.stats.sentiment_stats.trending_score || 0 }}/100</span>
                </div>
              </div>
            </div>

            <!-- Usage Stats -->
            <div class="usage-stats-section">
              <h4>📊 Usage Statistics</h4>
              <div class="stats-grid">
                <div class="stat-item">
                  <span class="stat-label">Total Holders</span>
                  <span class="stat-value">{{ cryptoDetails.stats.total_holders }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Total Amount Held</span>
                  <span class="stat-value">{{ cryptoDetails.stats.total_amount_held?.toFixed(4) || 0 }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Total Value Held</span>
                  <span class="stat-value">{{ cryptoDetails.stats.total_value_held?.toFixed(2) || 0 }} PLN</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Active Alerts</span>
                  <span class="stat-value">{{ cryptoDetails.stats.active_alerts_count }} / {{ cryptoDetails.stats.total_alerts_count }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Watchlist Count</span>
                  <span class="stat-value">{{ cryptoDetails.stats.watchlist_count }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Average Holding</span>
                  <span class="stat-value">{{ cryptoDetails.stats.average_holding?.toFixed(4) || 0 }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Detailed Data Tabs -->
          <div class="crypto-data-tabs">
            <div class="tab-nav">
              <button 
                @click="cryptoActiveTab = 'holders'" 
                :class="['tab-btn', { active: cryptoActiveTab === 'holders' }]"
              >
                👥 Top Holders ({{ cryptoDetails.top_holders?.length || 0 }})
              </button>
              <button 
                @click="cryptoActiveTab = 'alerts'" 
                :class="['tab-btn', { active: cryptoActiveTab === 'alerts' }]"
              >
                🔔 Recent Alerts ({{ cryptoDetails.recent_alerts?.length || 0 }})
              </button>
              <button 
                @click="cryptoActiveTab = 'watchlist'" 
                :class="['tab-btn', { active: cryptoActiveTab === 'watchlist' }]"
              >
                ⭐ Watchlist ({{ cryptoDetails.watchlist_users?.length || 0 }})
              </button>
              <button 
                @click="cryptoActiveTab = 'analyses'" 
                :class="['tab-btn', { active: cryptoActiveTab === 'analyses' }]"
              >
                📈 Recent Analyses ({{ cryptoDetails.recent_analyses?.length || 0 }})
              </button>
            </div>

            <div class="tab-content">
              <!-- Top Holders -->
              <div v-if="cryptoActiveTab === 'holders'" class="holders-list">
                <div v-for="holder in cryptoDetails.top_holders" :key="holder.user.id" class="holder-item">
                  <div class="holder-user">
                    <strong>{{ holder.user.name }}</strong>
                    <span class="user-email">{{ holder.user.email }}</span>
                  </div>
                  <div class="holder-stats">
                    <div>Amount: {{parseFloat(holder.amount)?.toFixed(4) }}</div>
                    <div>Value: {{ holder.current_value?.toFixed(2) }} PLN</div>
                    <div :class="['profit-loss', holder.profit_loss >= 0 ? 'positive' : 'negative']">
                      P&L: {{ holder.profit_loss?.toFixed(2) }} PLN
                    </div>
                  </div>
                </div>
              </div>

              <!-- Recent Alerts -->
              <div v-if="cryptoActiveTab === 'alerts'" class="alerts-list">
                <div v-for="alert in cryptoDetails.recent_alerts" :key="alert.id" class="alert-item">
                  <div class="alert-user">
                    <strong>{{ alert.user.name }}</strong>
                    <span class="user-email">{{ alert.user.email }}</span>
                  </div>
                  <div class="alert-details">
                    <div>{{ alert.type.toUpperCase() }} {{ alert.target_price }} {{ alert.currency }}</div>
                    <div>Created: {{ alert.created_at }}</div>
                    <span :class="['status', alert.is_active ? 'active' : 'inactive']">
                      {{ alert.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span v-if="alert.triggered_at" class="triggered">
                      Triggered: {{ alert.triggered_at }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Watchlist Users -->
              <div v-if="cryptoActiveTab === 'watchlist'" class="watchlist-list">
                <div v-for="item in cryptoDetails.watchlist_users" :key="item.user.id" class="watchlist-item">
                  <div class="watchlist-user">
                    <strong>{{ item.user.name }}</strong>
                    <span class="user-email">{{ item.user.email }}</span>
                  </div>
                  <div class="watchlist-details">
                    <div>Added: {{ item.added_at }}</div>
                    <span :class="['notifications', item.notifications_enabled ? 'enabled' : 'disabled']">
                      {{ item.notifications_enabled ? 'Notifications On' : 'Notifications Off' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Recent Analyses -->
              <div v-if="cryptoActiveTab === 'analyses'" class="analyses-list">
                <div v-for="analysis in cryptoDetails.recent_analyses" :key="analysis.id" class="analysis-item">
                  <div class="analysis-details">
                    <div class="analysis-row">
                      <span>Sentiment: {{ analysis.sentiment_avg?.toFixed(2) }}</span>
                      <span>Mentions: {{ analysis.mention_count }}</span>
                      <span>Trend: {{ analysis.trend_direction.toUpperCase() }}</span>
                      <span>Confidence: {{ analysis.confidence_score }}%</span>
                    </div>
                    <div class="analysis-time">
                      Created: {{ analysis.created_at }}
                      <span v-if="analysis.period_start"> | Period: {{ analysis.period_start }} - {{ analysis.period_end }}</span>
                    </div>
                  </div>
                </div>
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
  name: "AdminDashboardComponent",
  data() {
    return {
      loading: true,
      activeTab: "overview",
      userActiveTab: "portfolio",

      // Data
      stats: {},
      systemStats: {},
      systemLogs: [],
      users: { data: [], current_page: 1, last_page: 1 },
      cryptocurrencies: { data: [] },

      // User management
      userFilter: "",
      userSearch: "",
      userSort: "created_at",
      userSortDir: "desc",

      // Cryptocurrency management
      cryptoSearch: "",

      // User details modal
      showUserModal: false,
      selectedUser: null,
      userDetails: null,

      // Crypto details modal
      showCryptoModal: false,
      selectedCrypto: null,
      cryptoDetails: null,
      cryptoActiveTab: "holders",

      // Debounce timers
      searchTimer: null,
      cryptoSearchTimer: null,
    };
  },

  async mounted() {
    await this.loadDashboardData();
  },

  methods: {
    async loadDashboardData() {
      try {
        this.loading = true;

        // Load all dashboard data in parallel
        const [statsResponse, systemStatsResponse, systemLogsResponse] =
          await Promise.all([
            window.axios.get("/api/admin/stats"),
            window.axios.get("/api/admin/system-stats"),
            window.axios.get("/api/admin/system-logs"),
          ]);

        this.stats = statsResponse.data;
        this.systemStats = systemStatsResponse.data;
        this.systemLogs = systemLogsResponse.data;

        // Load initial users and cryptocurrencies
        await Promise.all([this.loadUsers(), this.loadCryptocurrencies()]);
      } catch (error) {
        console.error("Error loading admin dashboard:", error);
      } finally {
        this.loading = false;
      }
    },

    async loadUsers(page = 1) {
      try {
        const params = {
          page,
          filter: this.userFilter,
          search: this.userSearch,
          sort_by: this.userSort,
          sort_dir: this.userSortDir,
        };

        const response = await window.axios.get("/api/admin/users", { params });
        this.users = response.data;
      } catch (error) {
        console.error("Error loading users:", error);
      }
    },

    async loadCryptocurrencies(page = 1) {
      try {
        const params = {
          page,
          search: this.cryptoSearch,
        };

        const response = await window.axios.get("/api/admin/cryptocurrencies", {
          params,
        });
        this.cryptocurrencies = response.data;
      } catch (error) {
        console.error("Error loading cryptocurrencies:", error);
      }
    },

    async toggleUserPremium(user) {
      try {
        const response = await window.axios.post(
          `/api/admin/users/${user.id}/toggle-premium`
        );

        if (response.data.success) {
          // Update user in the list
          const userIndex = this.users.data.findIndex((u) => u.id === user.id);
          if (userIndex !== -1) {
            this.users.data[userIndex] = response.data.user;
          }

          // Refresh stats
          this.loadDashboardData();
        }
      } catch (error) {
        console.error("Error toggling premium:", error);
        alert("Error updating user premium status");
      }
    },

    async viewUserDetails(user) {
      try {
        const response = await window.axios.get(`/api/admin/users/${user.id}`);
        this.selectedUser = response.data.user;
        this.userDetails = response.data;
        this.showUserModal = true;
        this.userActiveTab = "portfolio";
      } catch (error) {
        console.error("Error loading user details:", error);
      }
    },

    closeUserModal() {
      this.showUserModal = false;
      this.selectedUser = null;
      this.userDetails = null;
    },

    async viewCryptoDetails(crypto) {
      try {
        const response = await window.axios.get(
          `/api/admin/cryptocurrencies/${crypto.id}`
        );
        this.selectedCrypto = crypto;
        this.cryptoDetails = response.data;
        this.showCryptoModal = true;
      } catch (error) {
        console.error("Error loading crypto details:", error);
        alert("Error loading cryptocurrency details");
      }
    },

    closeCryptoModal() {
      this.showCryptoModal = false;
      this.selectedCrypto = null;
      this.cryptoDetails = null;
      this.cryptoActiveTab = "holders";
    },

    getSentimentClass(sentiment) {
      if (sentiment > 0.1) return "positive";
      if (sentiment < -0.1) return "negative";
      return "neutral";
    },

    sortUsers(column) {
      if (this.userSort === column) {
        this.userSortDir = this.userSortDir === "asc" ? "desc" : "asc";
      } else {
        this.userSort = column;
        this.userSortDir = "asc";
      }
      this.loadUsers();
    },

    debounceSearch() {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => {
        this.loadUsers();
      }, 500);
    },

    debounceCryptoSearch() {
      clearTimeout(this.cryptoSearchTimer);
      this.cryptoSearchTimer = setTimeout(() => {
        this.loadCryptocurrencies();
      }, 500);
    },

    formatDate(dateString) {
      if (!dateString) return "N/A";
      return new Date(dateString).toLocaleDateString("pl-PL", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
  },
};
</script>

<style scoped>
.admin-dashboard {
  width: 100%;
}

.admin-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  gap: 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.stat-card.users {
  border-left: 4px solid #3b82f6;
}
.stat-card.revenue {
  border-left: 4px solid #10b981;
}
.stat-card.activity {
  border-left: 4px solid #8b5cf6;
}
.stat-card.engagement {
  border-left: 4px solid #f59e0b;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #64748b;
}

.stat-trend {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  background: #f1f5f9;
  color: #64748b;
}

.stat-trend.positive {
  background: #dcfce7;
  color: #16a34a;
}

.stat-main {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #1e293b;
}

.stat-details {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #64748b;
}

.stat-details span {
  padding: 0.25rem 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
}

.stat-details .premium {
  background: #fef3c7;
  color: #d97706;
}

.stat-details .verified {
  background: #dcfce7;
  color: #16a34a;
}

/* Tabs */
.admin-tabs {
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
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.3s ease;
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
}

.tab-content {
  padding: 2rem;
}

/* Overview Tab */
.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
}

.chart-card,
.activity-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.chart-card h3,
.activity-card h3 {
  margin: 0 0 1rem 0;
  color: #1e293b;
}

.simple-chart {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 100px;
  padding: 1rem 0;
}

.chart-bar {
  flex: 1;
  background: linear-gradient(to top, #6366f1, #8b5cf6);
  border-radius: 2px;
  min-height: 5px;
  cursor: pointer;
}

.crypto-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.crypto-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.crypto-name {
  font-weight: 600;
  color: #1e293b;
}

.crypto-users {
  color: #64748b;
  font-size: 0.875rem;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.activity-group h4 {
  margin: 0 0 0.75rem 0;
  color: #374151;
  font-size: 0.875rem;
}

.activity-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  margin-bottom: 0.5rem;
}

.activity-name {
  font-weight: 500;
  color: #1e293b;
}

.activity-time {
  color: #64748b;
  font-size: 0.75rem;
}

/* Users Section */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  color: #1e293b;
}

.controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filter-select,
.search-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.search-input {
  min-width: 200px;
}

.users-table-container,
.cryptos-table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.users-table,
.cryptos-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.cryptos-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e2e8f0;
  cursor: pointer;
}

.users-table th:hover,
.cryptos-table th:hover {
  background: #f1f5f9;
}

.users-table td,
.cryptos-table td {
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.user-row:hover {
  background: #f8fafc;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.admin-badge-small {
  background: #ef4444;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 6px;
  font-size: 0.625rem;
  font-weight: bold;
}

.status-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge.premium {
  background: #fef3c7;
  color: #d97706;
}

.badge.free {
  background: #f3f4f6;
  color: #6b7280;
}

.badge.verified {
  background: #dcfce7;
  color: #16a34a;
}

.badge.unverified {
  background: #fecaca;
  color: #dc2626;
}

.user-stats {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #64748b;
}

.user-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background: #d97706;
}

.btn-info {
  background: #3b82f6;
  color: white;
}

.btn-info:hover {
  background: #2563eb;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
}

.page-info {
  color: #64748b;
  font-size: 0.875rem;
}

/* System Section */
.system-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.system-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.system-card h4 {
  margin: 0 0 1rem 0;
  color: #1e293b;
}

.system-stats {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.system-stats div {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.875rem;
}

/* Modal */
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
  border-radius: 16px;
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.crypto-modal {
  max-width: 1000px;
  width: 95%;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  color: #1e293b;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  padding: 0.25rem;
  line-height: 1;
}

.close-btn:hover {
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.user-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.user-info-section h4,
.user-stats-section h4 {
  margin: 0 0 1rem 0;
  color: #374151;
}

.info-grid {
  display: grid;
  gap: 0.75rem;
}

.info-grid div {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.875rem;
}

.stats-grid {
  display: grid;
  gap: 1rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.stat-label {
  color: #64748b;
  font-size: 0.875rem;
}

.stat-value {
  font-weight: 600;
  color: #1e293b;
}

.stat-value.positive {
  color: #16a34a;
}

.stat-value.negative {
  color: #dc2626;
}

.activity-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.tab-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}

.tab-btn.active {
  background: #6366f1;
  color: white;
}

.activity-content {
  min-height: 200px;
}

.portfolio-list,
.alerts-list,
.watchlist-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.portfolio-item,
.alert-item,
.watchlist-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  font-size: 0.875rem;
}

.status.active {
  color: #16a34a;
}

.status.inactive {
  color: #dc2626;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .tab-nav {
    flex-direction: column;
  }

  .tab-button {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }

  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .controls {
    flex-direction: column;
    gap: 0.5rem;
  }

  .user-details-grid {
    grid-template-columns: 1fr;
  }

  .overview-grid {
    grid-template-columns: 1fr;
  }

  .system-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .tab-nav {
    flex-direction: column;
  }
  
  .tab-button {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .user-details-grid {
    grid-template-columns: 1fr;
  }
  
  .crypto-details-grid {
    grid-template-columns: 1fr;
  }
  
  .overview-grid {
    grid-template-columns: 1fr;
  }
  
  .system-grid {
    grid-template-columns: 1fr;
  }
}

/* Crypto Details Styles */
.crypto-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.crypto-info-section h4,
.price-stats-section h4,
.sentiment-stats-section h4,
.usage-stats-section h4 {
  margin: 0 0 1rem 0;
  color: #374151;
}

.crypto-data-tabs {
  margin-top: 2rem;
}

.crypto-data-tabs .tab-nav {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.holders-list, .alerts-list, .watchlist-list, .analyses-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 400px;
  overflow-y: auto;
}

.holder-item, .alert-item, .watchlist-item, .analysis-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.holder-user, .alert-user, .watchlist-user {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.user-email {
  color: #64748b;
  font-size: 0.875rem;
}

.holder-stats, .alert-details, .watchlist-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: right;
  font-size: 0.875rem;
}

.profit-loss.positive {
  color: #16a34a;
}

.profit-loss.negative {
  color: #dc2626;
}

.notifications.enabled {
  color: #16a34a;
}

.notifications.disabled {
  color: #dc2626;
}

.triggered {
  color: #f59e0b;
  font-style: italic;
}

.analysis-item {
  flex-direction: column;
  align-items: stretch;
}

.analysis-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.analysis-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.analysis-row span {
  padding: 0.25rem 0.5rem;
  background: white;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  font-size: 0.75rem;
}

.analysis-time {
  color: #64748b;
  font-size: 0.75rem;
}

.stat-value.neutral {
  color: #64748b;
}

.status.active {
  color: #16a34a;
}

.status.inactive {
  color: #dc2626;
}
</style> 