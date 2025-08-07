// resources/js/components/NotificationManager.vue

<template>
  <div class="notification-manager">
    <!-- OneSignal Permission Prompt -->
    <div v-if="showPermissionPrompt" class="permission-prompt">
      <div class="permission-card">
        <div class="permission-icon">🔔</div>
        <h3>Enable Push Notifications</h3>
        <p>Get instant alerts when your crypto price targets are reached!</p>
        <div class="permission-actions">
          <button @click="enableNotifications" class="btn btn-primary">
            Enable Notifications
          </button>
          <button @click="dismissPrompt" class="btn btn-secondary">
            Not Now
          </button>
        </div>
      </div>
    </div>

    <!-- Notification Status Indicator -->
    <div v-if="showStatus" class="notification-status">
      <div class="status-indicator" :class="statusClass">
        <span class="status-icon">{{ statusIcon }}</span>
        <span class="status-text">{{ statusText }}</span>
        <button v-if="!isSubscribed" @click="enableNotifications" class="btn btn-small btn-primary">
          Enable
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotificationManager',
  props: {
    showStatus: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showPermissionPrompt: false,
      isSubscribed: false,
      isSupported: false,
      permissionState: 'default' // 'default', 'granted', 'denied'
    }
  },
  computed: {
    statusClass() {
      return {
        'status-enabled': this.isSubscribed,
        'status-disabled': !this.isSubscribed && this.permissionState === 'denied',
        'status-pending': !this.isSubscribed && this.permissionState === 'default'
      };
    },
    statusIcon() {
      if (this.isSubscribed) return '✅';
      if (this.permissionState === 'denied') return '❌';
      return '🔔';
    },
    statusText() {
      if (this.isSubscribed) return 'Notifications enabled';
      if (this.permissionState === 'denied') return 'Notifications blocked';
      return 'Notifications disabled';
    }
  },
  async mounted() {
    await this.checkOneSignalStatus();
    
    // Show prompt after 10 seconds if not subscribed and not denied
    if (!this.isSubscribed && this.permissionState === 'default') {
      setTimeout(() => {
        if (!this.isSubscribed && !localStorage.getItem('onesignal_prompt_dismissed')) {
          this.showPermissionPrompt = true;
        }
      }, 10000);
    }
  },
  methods: {
    async checkOneSignalStatus() {
      try {
        if (typeof OneSignal !== 'undefined' && OneSignal.User) {
          await OneSignal.User.PushSubscription.optIn();
          this.isSupported = true;
          this.isSubscribed = OneSignal.User.PushSubscription.id !== null;
          this.permissionState = await OneSignal.Notifications.permission;
          
          console.log('OneSignal status:', {
            isSubscribed: this.isSubscribed,
            permissionState: this.permissionState,
            subscriptionId: OneSignal.User.PushSubscription.id
          });
        }
      } catch (error) {
        console.error('Error checking OneSignal status:', error);
      }
    },

    async enableNotifications() {
      try {
        if (typeof OneSignal === 'undefined') {
          console.error('OneSignal not loaded');
          return;
        }

        console.log('Requesting notification permission...');
        
        await OneSignal.Notifications.requestPermission();
        
        // Wait a bit for the permission to be processed
        setTimeout(async () => {
          await this.checkOneSignalStatus();
          
          if (this.isSubscribed) {
            this.showPermissionPrompt = false;
            this.$emit('notifications-enabled');
            this.showSuccess('Push notifications enabled! You\'ll receive alerts for price changes.');
          } else {
            this.showError('Failed to enable notifications. Please check your browser settings.');
          }
        }, 1000);
        
      } catch (error) {
        console.error('Error enabling notifications:', error);
        this.showError('Failed to enable notifications: ' + error.message);
      }
    },

    dismissPrompt() {
      this.showPermissionPrompt = false;
      localStorage.setItem('onesignal_prompt_dismissed', 'true');
    },

    showSuccess(message) {
      // You can implement toast notifications here
      console.log('Success:', message);
    },

    showError(message) {
      // You can implement toast notifications here  
      console.error('Error:', message);
    }
  }
}
</script>

<style scoped>
.notification-manager {
  position: relative;
}

.permission-prompt {
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
  animation: fadeIn 0.3s ease-out;
}

.permission-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.permission-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.permission-card h3 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.5rem;
}

.permission-card p {
  color: #64748b;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.permission-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.notification-status {
  margin-bottom: 1rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.status-enabled {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-disabled {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.status-pending {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

.status-icon {
  font-size: 1rem;
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
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
}

.btn-primary:hover {
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

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 480px) {
  .permission-card {
    padding: 1.5rem;
  }
  
  .permission-actions {
    flex-direction: column;
  }
}
</style>