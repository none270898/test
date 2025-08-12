// resources/js/components/PremiumButtonComponent.vue
<template>
  <div class="premium-button-component">
    <!-- Upgrade Modal -->
    <div v-if="showUpgradeModal" class="modal-overlay" @click="closeUpgradeModal">
      <div class="modal-content upgrade-modal" @click.stop>
        <div class="modal-header">
          <h3>🚀 Unlock Premium Features</h3>
          <button @click="closeUpgradeModal" class="close-btn">&times;</button>
        </div>
        
        <div class="upgrade-content">
          <div class="premium-preview">
            <h4>🤖 AI-Powered Crypto Analysis</h4>
            <p>Odblokowuj ukryte trendy z polskich społeczności krypto</p>
          </div>

          <div class="features-preview">
            <div class="feature-item">
              <span class="feature-icon">🧠</span>
              <div class="feature-text">
                <strong>AI Sentiment Analysis</strong>
                <p>Analiza nastrojów z Reddit, Twitter, forów</p>
              </div>
            </div>
            
            <div class="feature-item">
              <span class="feature-icon">📊</span>
              <div class="feature-text">
                <strong>Trend Predictions</strong>
                <p>Przewidywanie ruchów na podstawie AI</p>
              </div>
            </div>
            
            <div class="feature-item">
              <span class="feature-icon">∞</span>
              <div class="feature-text">
                <strong>Unlimited Everything</strong>
                <p>Portfolio, alerty, watchlist bez limitów</p>
              </div>
            </div>
            
            <div class="feature-item">
              <span class="feature-icon">🔔</span>
              <div class="feature-text">
                <strong>Smart Alerts</strong>
                <p>Kombinowane: cena + sentiment + push</p>
              </div>
            </div>
          </div>

          <div class="pricing-info">
            <div class="price-display">
              <span class="price">19 PLN</span>
              <span class="period">/miesiąc</span>
            </div>
            <div class="price-benefits">
              <span class="benefit">✅ 7 dni gwarancji zwrotu</span>
              <span class="benefit">✅ Anulowanie w każdej chwili</span>
            </div>
          </div>

          <div class="modal-actions">
            <a href="/premium/upgrade" class="btn btn-premium btn-large">
              Aktywuj Premium
            </a>
            <button @click="closeUpgradeModal" class="btn btn-secondary">
              Może później
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PremiumButtonComponent',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showUpgradeModal: false
    }
  },
  watch: {
    show(newVal) {
      this.showUpgradeModal = newVal;
    }
  },
  methods: {
    closeUpgradeModal() {
      this.showUpgradeModal = false;
      this.$emit('close');
    }
  }
}
</script>

<style scoped>
.premium-button-component {
  position: relative;
}

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

.upgrade-modal {
  background: linear-gradient(135deg, #f8fafc, #ffffff);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 0;
  margin-bottom: 1rem;
}

.modal-header h3 {
  margin: 0;
  color: #1e293b;
  font-size: 1.5rem;
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

.upgrade-content {
  padding: 0 2rem 2rem;
}

.premium-preview {
  text-align: center;
  padding: 2rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.premium-preview h4 {
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
}

.premium-preview p {
  margin: 0;
  opacity: 0.9;
  font-size: 1.1rem;
}

.features-preview {
  display: grid;
  gap: 1rem;
  margin-bottom: 2rem;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 2px solid #f1f5f9;
  transition: all 0.3s ease;
}

.feature-item:hover {
  border-color: #6366f1;
  transform: translateY(-2px);
}

.feature-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.feature-text strong {
  display: block;
  color: #1e293b;
  font-size: 1.1rem;
  margin-bottom: 0.25rem;
}

.feature-text p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.4;
}

.pricing-info {
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  margin-bottom: 2rem;
}

.price-display {
  margin-bottom: 1rem;
}

.price {
  font-size: 3rem;
  font-weight: bold;
  color: #1e293b;
}

.period {
  font-size: 1.25rem;
  color: #64748b;
}

.price-benefits {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.benefit {
  color: #10b981;
  font-weight: 500;
  font-size: 0.9rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

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

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-large {
  padding: 1rem 2rem;
  font-size: 1.1rem;
}

@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    width: calc(100% - 2rem);
  }
  
  .upgrade-content {
    padding: 0 1.5rem 1.5rem;
  }
  
  .premium-preview {
    padding: 1.5rem;
  }
  
  .premium-preview h4 {
    font-size: 1.25rem;
  }
  
  .feature-item {
    flex-direction: column;
    text-align: center;
  }
  
  .pricing-info {
    padding: 1.5rem;
  }
  
  .price {
    font-size: 2.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
}
</style>