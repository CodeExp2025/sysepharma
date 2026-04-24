<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'elevated', 'outlined', 'glass'].includes(value)
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },
  hover: {
    type: Boolean,
    default: true
  },
  animate: {
    type: Boolean,
    default: true
  },
  animationDelay: {
    type: Number,
    default: 0
  },
  glowOnHover: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const isHovered = ref(false);

const cardClasses = computed(() => {
  const baseClasses = [
    'card',
    `card--${props.variant}`,
    `card--padding-${props.padding}`,
    { 'card--hover': props.hover },
    { 'card--animate': props.animate },
    { 'card--glow': props.glowOnHover },
    { 'card--loading': props.loading }
  ];
  return baseClasses;
});

const cardStyle = computed(() => {
  if (props.animationDelay > 0) {
    return { animationDelay: `${props.animationDelay}ms` };
  }
  return {};
});
</script>

<template>
  <div
    :class="cardClasses"
    :style="cardStyle"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Loading Skeleton Overlay -->
    <div v-if="loading" class="card__skeleton">
      <div class="skeleton-line skeleton-line--title"></div>
      <div class="skeleton-line"></div>
      <div class="skeleton-line skeleton-line--short"></div>
    </div>
    
    <!-- Card Content -->
    <div v-else class="card__content">
      <div v-if="$slots.header" class="card__header">
        <slot name="header" />
      </div>
      
      <div class="card__body">
        <slot />
      </div>
      
      <div v-if="$slots.footer" class="card__footer">
        <slot name="footer" />
      </div>
    </div>
    
    <!-- Glow Effect on Hover -->
    <div v-if="glowOnHover" class="card__glow" :class="{ 'is-active': isHovered }"></div>
  </div>
</template>

<style scoped>
.card {
  position: relative;
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  overflow: hidden;
  transition: 
    transform var(--duration-normal) var(--ease-out-quart),
    box-shadow var(--duration-normal) var(--ease-out-quart);
  will-change: transform, box-shadow;
}

/* Variant: Default */
.card--default {
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}

.card--default.card--hover:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--border-medium);
}

/* Variant: Elevated */
.card--elevated {
  box-shadow: var(--shadow-md);
  border: none;
}

.card--elevated.card--hover:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}

/* Variant: Outlined */
.card--outlined {
  border: 1px solid var(--border-medium);
  box-shadow: none;
  background: transparent;
}

.card--outlined.card--hover:hover {
  border-color: var(--color-primary-300);
  background: var(--bg-secondary);
}

/* Variant: Glass */
.card--glass {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 
    0 4px 6px -1px rgba(0, 0, 0, 0.05),
    0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.card--glass.card--hover:hover {
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 
    0 10px 15px -3px rgba(0, 0, 0, 0.08),
    0 4px 6px -2px rgba(0, 0, 0, 0.04);
}

/* Padding Variants */
.card--padding-none .card__content {
  padding: 0;
}

.card--padding-sm .card__content {
  padding: var(--space-3);
}

.card--padding-md .card__content {
  padding: var(--space-4);
}

.card--padding-lg .card__content {
  padding: var(--space-6);
}

/* Animation */
.card--animate {
  opacity: 0;
  animation: cardEnter var(--duration-slow) var(--ease-out-quart) forwards;
}

@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Card Sections */
.card__header {
  margin-bottom: var(--space-4);
  padding-bottom: var(--space-3);
  border-bottom: 1px solid var(--border-light);
}

.card__body {
  flex: 1;
}

.card__footer {
  margin-top: var(--space-4);
  padding-top: var(--space-3);
  border-top: 1px solid var(--border-light);
}

/* Glow Effect */
.card__glow {
  position: absolute;
  inset: -1px;
  border-radius: inherit;
  opacity: 0;
  transition: opacity var(--duration-normal) var(--ease-out-quart);
  pointer-events: none;
  background: linear-gradient(
    135deg,
    rgba(59, 130, 246, 0.15) 0%,
    rgba(139, 92, 246, 0.15) 100%
  );
  filter: blur(8px);
  z-index: -1;
}

.card__glow.is-active {
  opacity: 1;
}

/* Loading Skeleton */
.card--loading {
  pointer-events: none;
}

.card__skeleton {
  padding: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.skeleton-line {
  height: 0.75rem;
  background: linear-gradient(
    90deg,
    var(--color-gray-200) 25%,
    var(--color-gray-100) 50%,
    var(--color-gray-200) 75%
  );
  background-size: 200% 100%;
  border-radius: var(--radius-sm);
  animation: shimmer 1.5s infinite;
}

.skeleton-line--title {
  height: 1.25rem;
  width: 60%;
  margin-bottom: var(--space-2);
}

.skeleton-line--short {
  width: 40%;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .card,
  .card--animate {
    animation: none;
    opacity: 1;
    transform: none;
    transition: none;
  }
  
  .card--hover:hover {
    transform: none;
  }
  
  .skeleton-line {
    animation: none;
    background: var(--color-gray-200);
  }
}
</style>
