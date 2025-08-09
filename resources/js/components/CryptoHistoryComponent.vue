
<template>
  <div class="crypto-history">
    <!-- Time Range Selector -->
    <div class="history-controls">
      <div class="time-range-buttons">
        <button 
          v-for="range in timeRanges" 
          :key="range.value"
          @click="selectedDays = range.value; loadHistory()"
          :class="['time-btn', { 'active': selectedDays === range.value }]"
        >
          {{ range.label }}
        </button>
      </div>
      <button @click="loadHistory" :disabled="loading" class="btn btn-small btn-secondary">
        🔄 Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading sentiment history...</p>
    </div>

    <!-- History Content -->
    <div v-else-if="historyData && historyData.history.length > 0" class="history-content">
      <!-- Summary Stats -->
      <div class="summary-stats">
        <div class="summary-card">
          <div class="summary-icon">📊</div>
          <div class="summary-info">
            <div class="summary-label">Total Mentions</div>
            <div class="summary-value">{{ historyData.summary.total_mentions }}</div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon">🎯</div>
          <div class="summary-info">
            <div class="summary-label">Avg Sentiment</div>
            <div class="summary-value" :class="getSentimentClass(historyData.summary.avg_sentiment)">
              {{ formatSentiment(historyData.summary.avg_sentiment) }}
            </div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon">📈</div>
          <div class="summary-info">
            <div class="summary-label">Sentiment Trend</div>
            <div class="summary-value" :class="getSentimentClass(historyData.summary.sentiment_trend)">
              {{ formatSentimentChange(historyData.summary.sentiment_trend) }}
            </div>
          </div>
        </div>
        
        <div class="summary-card">
          <div class="summary-icon">📅</div>
          <div class="summary-info">
            <div class="summary-label">Days Analyzed</div>
            <div class="summary-value">{{ historyData.summary.days_analyzed }}</div>
          </div>
        </div>
      </div>

      <!-- Sentiment Chart -->
      <div class="chart-section">
        <h4>📈 Sentiment Over Time</h4>
        <div class="chart-container">
          <svg class="sentiment-chart" :viewBox="`0 0 ${chartWidth} ${chartHeight}`">
            <!-- Grid Lines -->
            <g class="grid-lines">
              <line 
                v-for="i in 5" 
                :key="`grid-${i}`"
                :x1="chartPadding.left" 
                :y1="chartPadding.top + (i-1) * (chartHeight - chartPadding.top - chartPadding.bottom) / 4"
                :x2="chartWidth - chartPadding.right" 
                :y2="chartPadding.top + (i-1) * (chartHeight - chartPadding.top - chartPadding.bottom) / 4"
                stroke="#f1f5f9" 
                stroke-width="1"
              />
            </g>
            
            <!-- Zero Line -->
            <line 
              :x1="chartPadding.left" 
              :y1="zeroLine"
              :x2="chartWidth - chartPadding.right" 
              :y2="zeroLine"
              stroke="#6b7280" 
              stroke-width="2" 
              stroke-dasharray="5,5"
              opacity="0.7"
            />
            
            <!-- Sentiment Line -->
            <polyline
              :points="sentimentPoints"
              fill="none"
              stroke="#6366f1"
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            
            <!-- Data Points -->
            <circle
              v-for="(point, index) in chartPoints"
              :key="`point-${index}`"
              :cx="point.x"
              :cy="point.y"
              :r="4"
              :fill="getSentimentColor(point.sentiment)"
              stroke="white"
              stroke-width="2"
              class="data-point"
              @mouseover="showTooltip($event, point, index)"
              @mouseout="hideTooltip"
            />
            
            <!-- Y-axis Labels -->
            <g class="y-labels">
              <text 
                v-for="(label, i) in yLabels" 
                :key="`y-label-${i}`"
                :x="chartPadding.left - 10" 
                :y="label.y"
                text-anchor="end" 
                dominant-baseline="middle"
                class="chart-label"
              >
                {{ label.text }}
              </text>
            </g>
            
            <!-- X-axis Labels -->
            <g class="x-labels">
              <text 
                v-for="(label, i) in xLabels" 
                :key="`x-label-${i}`"
                :x="label.x" 
                :y="chartHeight - chartPadding.bottom + 20"
                text-anchor="middle" 
                class="chart-label"
              >
                {{ label.text }}
              </text>
            </g>
          </svg>
          
          <!-- Tooltip -->
          <div 
            v-if="tooltip.show" 
            class="chart-tooltip"
            :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
          >
            <div class="tooltip-date">{{ tooltip.date }}</div>
            <div class="tooltip-sentiment" :class="getSentimentClass(tooltip.sentiment)">
              Sentiment: {{ formatSentiment(tooltip.sentiment) }}
            </div>
            <div class="tooltip-mentions">{{ tooltip.mentions }} mentions</div>
            <div class="tooltip-confidence">{{ tooltip.confidence }}% confidence</div>
          </div>
        </div>
      </div>

      <!-- Daily Breakdown -->
      <div class="daily-breakdown">
        <h4>📊 Daily Breakdown</h4>
        <div class="breakdown-list">
          <div 
            v-for="day in historyData.history" 
            :key="day.date"
            class="breakdown-item"
            :class="getBreakdownClass(day.trend_direction)"
          >
            <div class="breakdown-date">
              <div class="date-day">{{ formatDate(day.date) }}</div>
              <div class="date-weekday">{{ formatWeekday(day.date) }}</div>
            </div>
            
            <div class="breakdown-metrics">
              <div class="metric">
                <span class="metric-label">Sentiment</span>
                <span class="metric-value" :class="getSentimentClass(day.sentiment_avg)">
                  {{ formatSentiment(day.sentiment_avg) }}
                </span>
              </div>
              
              <div class="metric">
                <span class="metric-label">Mentions</span>
                <span class="metric-value">{{ day.mention_count }}</span>
              </div>
              
              <div class="metric">
                <span class="metric-label">Confidence</span>
                <span class="metric-value">{{ day.confidence_score }}%</span>
              </div>
              
              <div class="metric">
                <span class="metric-label">Change</span>
                <span class="metric-value" :class="getSentimentClass(day.sentiment_change)">
                  {{ formatSentimentChange(day.sentiment_change) }}
                </span>
              </div>
            </div>
            
            <div class="breakdown-trend">
              <span class="trend-badge" :class="getTrendClass(day.trend_direction)">
                {{ getTrendEmoji(day.trend_direction) }} {{ getTrendText(day.trend_direction) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">📈</div>
      <h3>No sentiment history available</h3>
      <p>Sentiment analysis data will appear here once we collect enough mentions for this cryptocurrency.</p>
      <button @click="loadHistory" class="btn btn-primary">
        Check Again
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CryptoHistoryComponent',
  props: {
    coinGeckoId: {
      type: String,
      required: true
    },
    cryptoName: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      historyData: null,
      loading: false,
      selectedDays: 7,
      timeRanges: [
        { label: '7D', value: 7 },
        { label: '14D', value: 14 },
        { label: '30D', value: 30 }
      ],
      tooltip: {
        show: false,
        x: 0,
        y: 0,
        date: '',
        sentiment: 0,
        mentions: 0,
        confidence: 0
      },
      chartWidth: 800,
      chartHeight: 400,
      chartPadding: { top: 20, right: 20, bottom: 60, left: 60 }
    }
  },
  computed: {
    chartPoints() {
      if (!this.historyData || !this.historyData.history.length) return [];
      
      const history = this.historyData.history;
      const width = this.chartWidth - this.chartPadding.left - this.chartPadding.right;
      const height = this.chartHeight - this.chartPadding.top - this.chartPadding.bottom;
      
      const minSentiment = Math.min(-1, Math.min(...history.map(d => d.sentiment_avg)));
      const maxSentiment = Math.max(1, Math.max(...history.map(d => d.sentiment_avg)));
      
      return history.map((day, index) => {
        const x = this.chartPadding.left + (index / (history.length - 1)) * width;
        const y = this.chartPadding.top + height - ((day.sentiment_avg - minSentiment) / (maxSentiment - minSentiment)) * height;
        
        return {
          x,
          y,
          sentiment: day.sentiment_avg,
          mentions: day.mention_count,
          confidence: day.confidence_score,
          date: day.date,
          trend: day.trend_direction
        };
      });
    },
    
    sentimentPoints() {
      return this.chartPoints.map(p => `${p.x},${p.y}`).join(' ');
    },
    
    zeroLine() {
      const height = this.chartHeight - this.chartPadding.top - this.chartPadding.bottom;
      const minSentiment = Math.min(-1, Math.min(...(this.historyData?.history || []).map(d => d.sentiment_avg)));
      const maxSentiment = Math.max(1, Math.max(...(this.historyData?.history || []).map(d => d.sentiment_avg)));
      
      return this.chartPadding.top + height - ((0 - minSentiment) / (maxSentiment - minSentiment)) * height;
    },
    
    yLabels() {
      const height = this.chartHeight - this.chartPadding.top - this.chartPadding.bottom;
      const minSentiment = Math.min(-1, Math.min(...(this.historyData?.history || []).map(d => d.sentiment_avg)));
      const maxSentiment = Math.max(1, Math.max(...(this.historyData?.history || []).map(d => d.sentiment_avg)));
      
      return [-1, -0.5, 0, 0.5, 1].map(value => {
        const y = this.chartPadding.top + height - ((value - minSentiment) / (maxSentiment - minSentiment)) * height;
        return {
          y,
          text: value > 0 ? `+${value.toFixed(1)}` : value.toFixed(1)
        };
      });
    },
    
    xLabels() {
      if (!this.historyData || !this.historyData.history.length) return [];
      
      const history = this.historyData.history;
      const width = this.chartWidth - this.chartPadding.left - this.chartPadding.right;
      const step = Math.max(1, Math.floor(history.length / 6));
      
      return history.filter((_, index) => index % step === 0).map((day, index) => {
        const originalIndex = index * step;
        const x = this.chartPadding.left + (originalIndex / (history.length - 1)) * width;
        
        return {
          x,
          text: this.formatShortDate(day.date)
        };
      });
    }
  },
  async mounted() {
    await this.loadHistory();
  },
  methods: {
    async loadHistory() {
      this.loading = true;
      try {
        const response = await window.axios.get(`/api/discovery/history/${this.coinGeckoId}`, {
          params: { days: this.selectedDays }
        });
        this.historyData = response.data;
      } catch (error) {
        console.error('Error loading crypto history:', error);
        this.showError('Failed to load sentiment history');
      } finally {
        this.loading = false;
      }
    },

    showTooltip(event, point, index) {
      const rect = event.target.getBoundingClientRect();
      const container = event.target.closest('.chart-container').getBoundingClientRect();
      
      this.tooltip = {
        show: true,
        x: rect.left - container.left + 10,
        y: rect.top - container.top - 10,
        date: this.formatDate(point.date),
        sentiment: point.sentiment,
        mentions: point.mentions,
        confidence: point.confidence
      };
    },

    hideTooltip() {
      this.tooltip.show = false;
    },

    getSentimentColor(sentiment) {
      if (sentiment > 0.1) return '#10b981';
      if (sentiment < -0.1) return '#ef4444';
      return '#6b7280';
    },

    getSentimentClass(sentiment) {
      if (sentiment > 0.1) return 'sentiment-positive';
      if (sentiment < -0.1) return 'sentiment-negative';
      return 'sentiment-neutral';
    },

    getBreakdownClass(direction) {
      return `breakdown-${direction}`;
    },

    getTrendClass(direction) {
      return `trend-${direction}`;
    },

    getTrendEmoji(direction) {
      const emojis = { up: '📈', down: '📉', neutral: '➡️' };
      return emojis[direction] || '➡️';
    },

    getTrendText(direction) {
      const texts = { up: 'Bullish', down: 'Bearish', neutral: 'Neutral' };
      return texts[direction] || 'Unknown';
    },

    formatSentiment(score) {
      const value = parseFloat(score || 0);
      return value > 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
    },

    formatSentimentChange(change) {
      const value = parseFloat(change || 0);
      if (value === 0) return '—';
      return value > 0 ? `+${value.toFixed(2)}` : value.toFixed(2);
    },

    formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('pl-PL', { 
        day: 'numeric', 
        month: 'short'
      });
    },

    formatShortDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('pl-PL', { 
        day: 'numeric', 
        month: 'numeric'
      });
    },

    formatWeekday(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('pl-PL', { weekday: 'short' });
    },

    showError(message) {
      console.error('Error:', message);
    }
  }
}
</script>

<style scoped>
.crypto-history {
  width: 100%;
}

.history-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.time-range-buttons {
  display: flex;
  gap: 0.5rem;
}

.time-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.time-btn:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.time-btn.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.summary-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.summary-label {
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #1e293b;
}

.chart-section {
  margin-bottom: 2rem;
}

.chart-section h4 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.chart-container {
  position: relative;
  background: white;
  border-radius: 12px;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.sentiment-chart {
  width: 100%;
  height: auto;
  max-height: 400px;
}

.chart-label {
  font-size: 12px;
  fill: #64748b;
  font-weight: 500;
}

.data-point {
  cursor: pointer;
  transition: r 0.2s ease;
}

.data-point:hover {
  r: 6;
}

.chart-tooltip {
  position: absolute;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  pointer-events: none;
  z-index: 10;
  backdrop-filter: blur(4px);
}

.tooltip-date {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.tooltip-sentiment,
.tooltip-mentions,
.tooltip-confidence {
  margin-bottom: 0.25rem;
}

.daily-breakdown h4 {
  color: #1e293b;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.breakdown-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.breakdown-up {
  border-color: #10b981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.01));
}

.breakdown-down {
  border-color: #ef4444;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.01));
}

.breakdown-neutral {
  border-color: #6b7280;
}

.breakdown-date {
  min-width: 80px;
  text-align: center;
}

.date-day {
  font-size: 1.1rem;
  font-weight: bold;
  color: #1e293b;
}

.date-weekday {
  font-size: 0.8rem;
  color: #64748b;
  text-transform: uppercase;
}

.breakdown-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 1rem;
  flex: 1;
}

.metric {
  text-align: center;
}

.metric-label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 1rem;
}

.breakdown-trend {
  min-width: 120px;
  text-align: right;
}

.trend-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.trend-up {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.trend-down {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.trend-neutral {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.sentiment-positive {
  color: #10b981;
}

.sentiment-negative {
  color: #ef4444;
}

.sentiment-neutral {
  color: #6b7280;
}

/* Loading and Empty States */
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .history-controls {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .time-range-buttons {
    justify-content: center;
  }
  
  .summary-stats {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
  }
  
  .summary-card {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .breakdown-item {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .breakdown-metrics {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }
  
  .breakdown-date,
  .breakdown-trend {
    min-width: auto;
  }
  
  .chart-container {
    padding: 0.5rem;
  }
}

@media (max-width: 480px) {
  .time-btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
  }
  
  .summary-stats {
    grid-template-columns: 1fr;
  }
  
  .breakdown-metrics {
    grid-template-columns: 1fr;
  }
  
  .breakdown-item {
    padding: 1rem;
  }
}
</style>