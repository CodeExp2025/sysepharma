<script setup>
import { computed } from 'vue';
import Card from './Card.vue';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: String,
    required: true
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'success', 'warning', 'danger', 'info'].includes(value)
  },
  trend: {
    type: Object,
    default: null
    // { value: number, label: string, positive: boolean }
  },
  subtitle: {
    type: String,
    default: null
  },
  progress: {
    type: Number,
    default: null
    // 0-100 percentage
  },
  animationDelay: {
    type: Number,
    default: 0
  },
  href: {
    type: String,
    default: null
  }
});

const variantColors = {
  primary: {
    bg: 'from-blue-500 to-blue-600',
    light: 'from-blue-100 to-blue-200',
    text: 'text-blue-600',
    lightText: 'text-blue-800',
    bgSoft: 'bg-blue-50',
    border: 'border-blue-200',
    progress: 'bg-blue-600'
  },
  success: {
    bg: 'from-emerald-500 to-emerald-600',
    light: 'from-emerald-100 to-emerald-200',
    text: 'text-emerald-600',
    lightText: 'text-emerald-800',
    bgSoft: 'bg-emerald-50',
    border: 'border-emerald-200',
    progress: 'bg-emerald-600'
  },
  warning: {
    bg: 'from-amber-500 to-amber-600',
    light: 'from-amber-100 to-amber-200',
    text: 'text-amber-600',
    lightText: 'text-amber-800',
    bgSoft: 'bg-amber-50',
    border: 'border-amber-200',
    progress: 'bg-amber-600'
  },
  danger: {
    bg: 'from-red-500 to-red-600',
    light: 'from-red-100 to-red-200',
    text: 'text-red-600',
    lightText: 'text-red-800',
    bgSoft: 'bg-red-50',
    border: 'border-red-200',
    progress: 'bg-red-600'
  },
  info: {
    bg: 'from-violet-500 to-violet-600',
    light: 'from-violet-100 to-violet-200',
    text: 'text-violet-600',
    lightText: 'text-violet-800',
    bgSoft: 'bg-violet-50',
    border: 'border-violet-200',
    progress: 'bg-violet-600'
  }
};

const colors = computed(() => variantColors[props.variant]);

const displayValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('fr-FR');
  }
  return props.value;
});
</script>

<template>
  <Card
    :variant="'elevated'"
    :padding="'md'"
    :animate="true"
    :animation-delay="animationDelay"
    :glow-on-hover="true"
    :class="{ 'cursor-pointer': href }"
    @click="href ? $inertia.visit(href) : null"
  >
    <div class="stats-card">
      <!-- Header with Icon and Value -->
      <div class="stats-card__header">
        <div 
          class="stats-card__icon"
          :class="`bg-gradient-to-br ${colors.light}`"
        >
          <svg 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            :class="`w-6 h-6 ${colors.text}`"
          >
            <path :d="icon" />
          </svg>
        </div>
        
        <div class="stats-card__value-section">
          <div class="stats-card__value" :class="colors.text">
            {{ displayValue }}
          </div>
        </div>
      </div>
      
      <!-- Title -->
      <h3 class="stats-card__title">{{ title }}</h3>
      
      <!-- Progress Bar -->
      <div v-if="progress !== null" class="stats-card__progress">
        <div class="stats-card__progress-bar">
          <div 
            class="stats-card__progress-fill"
            :class="colors.progress"
            :style="{ width: `${Math.min(progress, 100)}%` }"
          />
        </div>
        <span class="stats-card__progress-text" :class="colors.text">
          {{ Math.round(progress) }}%
        </span>
      </div>
      
      <!-- Trend/Subtitle -->
      <div v-else-if="trend || subtitle" class="stats-card__footer">
        <p v-if="subtitle" class="stats-card__subtitle">
          {{ subtitle }}
        </p>
        
        <div v-if="trend" class="stats-card__trend" :class="trend.positive ? 'text-emerald-600' : 'text-red-600'">
          <svg 
            v-if="trend.positive"
            class="w-3 h-3" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
          <svg 
            v-else
            class="w-3 h-3" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
          </svg>
          <span class="font-medium">{{ trend.value }}</span>
          <span class="stats-card__trend-label">{{ trend.label }}</span>
        </div>
      </div>
    </div>
  </Card>
</template>

<style scoped>
.stats-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.stats-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.stats-card__icon {
  width: 3rem;
  height: 3rem;
  border-radius: var(--radius-xl);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform var(--duration-normal) var(--ease-out-quart);
}

.stats-card:hover .stats-card__icon {
  transform: scale(1.1);
}

.stats-card__value-section {
  text-align: right;
}

.stats-card__value {
  font-size: 1.875rem;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.02em;
}

.stats-card__title {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--text-muted);
  margin: 0;
  line-height: var(--leading-snug);
}

.stats-card__progress {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-top: var(--space-1);
}

.stats-card__progress-bar {
  flex: 1;
  height: 0.5rem;
  background: var(--color-gray-200);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.stats-card__progress-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: width 1s var(--ease-out-quart);
}

.stats-card__progress-text {
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  flex-shrink: 0;
}

.stats-card__footer {
  margin-top: auto;
}

.stats-card__subtitle {
  font-size: var(--text-xs);
  color: var(--text-muted);
  margin: 0;
}

.stats-card__trend {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-xs);
}

.stats-card__trend-label {
  color: var(--text-muted);
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .stats-card__icon,
  .stats-card__progress-fill {
    transition: none;
  }
  
  .stats-card:hover .stats-card__icon {
    transform: none;
  }
}
</style>
