import './bootstrap';
import { createApp } from 'vue';

// Import all Vue components
import DashboardComponent from './components/DashboardComponent.vue';
import PortfolioComponent from './components/PortfolioComponent.vue';
import PriceAlertComponent from './components/PriceAlertComponent.vue';
import TrendAnalysisComponent from './components/TrendAnalysisComponent.vue';
// import CryptoPricesComponent from './components/CryptoPricesComponent.vue';
import SubscriptionComponent from './components/SubscriptionComponent.vue';

const app = createApp({});

// Register components globally
app.component('dashboard-component', DashboardComponent);
app.component('portfolio-component', PortfolioComponent);
app.component('price-alert-component', PriceAlertComponent);
app.component('trend-analysis-component', TrendAnalysisComponent);
// app.component('crypto-prices-component', CryptoPricesComponent);
app.component('subscription-component', SubscriptionComponent);

// Mount the app
app.mount('#app');

// OneSignal initialization
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/OneSignalSDKWorker.js');
    });
}

// Initialize OneSignal
window.OneSignal = window.OneSignal || [];
OneSignal.push(function() {
    OneSignal.init({
        appId: document.querySelector('meta[name="onesignal-app-id"]')?.getAttribute('content'),
        notifyButton: {
            enable: true,
        },
        allowLocalhostAsSecureOrigin: true,
    });
    
    OneSignal.on('subscriptionChange', function (isSubscribed) {
        if (isSubscribed) {
            OneSignal.getUserId(function(userId) {
                // Send player ID to Laravel backend
                fetch('/api/v1/onesignal/player-id', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ player_id: userId })
                });
            });
        }
    });
});