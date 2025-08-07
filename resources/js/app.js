import './bootstrap';
import { createApp } from 'vue';

// Import Vue components
import DashboardComponent from './components/DashboardComponent.vue';
import PortfolioComponent from './components/PortfolioComponent.vue';
import AlertsComponent from './components/AlertsComponent.vue';
import NotificationManager from './components/NotificationManager.vue';

const app = createApp({});

// Register components globally
app.component('dashboard-component', DashboardComponent);
app.component('portfolio-component', PortfolioComponent);
app.component('alerts-component', AlertsComponent);
app.component('notification-manager', NotificationManager);

// Mount Vue app
app.mount('#app');