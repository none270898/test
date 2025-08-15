import './bootstrap';
import { createApp } from 'vue';

// Import Vue components
import DashboardComponent from './components/DashboardComponent.vue';
import PortfolioComponent from './components/PortfolioComponent.vue';
import AlertsComponent from './components/AlertsComponent.vue';
import NotificationManager from './components/NotificationManager.vue';
import SentimentAnalyticsComponent from './components/SentimentAnalyticsComponent.vue';
import WatchlistComponent from './components/WatchlistComponent.vue';
import DiscoveryComponent from './components/DiscoveryComponent.vue';
import CryptoHistoryComponent from './components/CryptoHistoryComponent.vue';
import AdminDashboardComponent from './components/AdminDashboardComponent.vue';


const app = createApp({});

// Register components globally
app.component('dashboard-component', DashboardComponent);
app.component('portfolio-component', PortfolioComponent);
app.component('alerts-component', AlertsComponent);
app.component('notification-manager', NotificationManager);
app.component('sentiment-analytics-component', SentimentAnalyticsComponent);
app.component('watchlist-component', WatchlistComponent);
app.component('discovery-component', DiscoveryComponent);
app.component('crypto-history-component', CryptoHistoryComponent);
app.component('admin-dashboard-component', AdminDashboardComponent);


// Mount Vue app
app.mount('#app');
