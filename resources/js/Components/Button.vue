<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'success', 'ghost', 'outline'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  fullWidth: {
    type: Boolean,
    default: false
  },
  icon: {
    type: String,
    default: null
  },
  iconPosition: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'right'].includes(value)
  },
  ripple: {
    type: Boolean,
    default: true
  },
  type: {
    type: String,
    default: 'button'
  }
});

const emit = defineEmits(['click']);

const buttonRef = ref(null);
const ripples = ref([]);

const buttonClasses = computed(() => {
  return [
    'btn',
    `btn--${props.variant}`,
    `btn--${props.size}`,
    {
      'btn--disabled': props.disabled,
      'btn--loading': props.loading,
      'btn--full-width': props.fullWidth,
      'btn--with-icon': props.icon,
      [`btn--icon-${props.iconPosition}`]: props.icon
    }
  ];
});

const handleClick = (event) => {
  if (props.disabled || props.loading) return;
  
  if (props.ripple) {
    createRipple(event);
  }
  
  emit('click', event);
};

const createRipple = (event) => {
  const button = buttonRef.value;
  if (!button) return;
  
  const rect = button.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = event.clientX - rect.left - size / 2;
  const y = event.clientY - rect.top - size / 2;
  
  const rippleId = Date.now();
  ripples.value.push({
    id: rippleId,
    x,
    y,
    size
  });
  
  // Remove ripple after animation
  setTimeout(() => {
    ripples.value = ripples.value.filter(r => r.id !== rippleId);
  }, 600);
};
</script>

<template>
  <button
    ref="buttonRef"
    :type="type"
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <!-- Loading Spinner -->
    <span v-if="loading" class="btn__spinner">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="12" cy="12" r="10" stroke-width="3" stroke-linecap="round" stroke-dasharray="31.42" stroke-dashoffset="10" />
      </svg>
    </span>
    
    <!-- Icon (left) -->
    <span v-else-if="icon && iconPosition === 'left'" class="btn__icon btn__icon--left">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path :d="icon" />
      </svg>
    </span>
    
    <!-- Content -->
    <span class="btn__content" :class="{ 'btn__content--hidden': loading }">
      <slot />
    </span>
    
    <!-- Icon (right) -->
    <span v-if="icon && iconPosition === 'right' && !loading" class="btn__icon btn__icon--right">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path :d="icon" />
      </svg>
    </span>
    
    <!-- Ripple Effects -->
    <span v-if="ripple" class="btn__ripples">
      <span
        v-for="ripple in ripples"
        :key="ripple.id"
        class="btn__ripple"
        :style="{
          left: `${ripple.x}px`,
          top: `${ripple.y}px`,
          width: `${ripple.size}px`,
          height: `${ripple.size}px`
        }"
      />
    </span>
  </button>
</template>

<style scoped>
.btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-1-5);
  font-family: var(--font-family-base);
  font-weight: var(--font-medium);
  text-decoration: none;
  border: none;
  border-radius: var(--radius-lg);
  cursor: pointer;
  overflow: hidden;
  transition: 
    transform var(--duration-fast) var(--ease-out-quart),
    box-shadow var(--duration-fast) var(--ease-out-quart),
    background-color var(--duration-fast) var(--ease-out-quart),
    border-color var(--duration-fast) var(--ease-out-quart);
  will-change: transform, box-shadow;
}

/* Sizes */
.btn--sm {
  height: var(--btn-height-sm);
  padding: var(--btn-padding-sm);
  font-size: var(--text-sm);
}

.btn--md {
  height: var(--btn-height-md);
  padding: var(--btn-padding-md);
  font-size: var(--text-md);
}

.btn--lg {
  height: var(--btn-height-lg);
  padding: var(--btn-padding-lg);
  font-size: var(--text-lg);
}

/* Variants */

/* Primary */
.btn--primary {
  background: linear-gradient(135deg, var(--color-primary-500), var(--color-primary-600));
  color: var(--text-inverse);
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
}

.btn--primary:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-700));
  transform: translateY(-1px);
  box-shadow: var(--shadow-primary);
}

.btn--primary:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
  box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
}

/* Secondary */
.btn--secondary {
  background: linear-gradient(135deg, var(--color-gray-100), var(--color-gray-200));
  color: var(--text-secondary);
  border: 1px solid var(--border-medium);
}

.btn--secondary:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--color-gray-200), var(--color-gray-300));
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

/* Danger */
.btn--danger {
  background: linear-gradient(135deg, var(--color-danger), #dc2626);
  color: var(--text-inverse);
  box-shadow: 0 1px 3px rgba(239, 68, 68, 0.3);
}

.btn--danger:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  transform: translateY(-1px);
  box-shadow: var(--shadow-danger);
}

/* Success */
.btn--success {
  background: linear-gradient(135deg, var(--color-success), #059669);
  color: var(--text-inverse);
  box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
}

.btn--success:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-1px);
  box-shadow: var(--shadow-success);
}

/* Ghost */
.btn--ghost {
  background: transparent;
  color: var(--text-secondary);
}

.btn--ghost:hover:not(:disabled) {
  background: var(--nav-bg-hover);
  color: var(--color-primary-600);
}

/* Outline */
.btn--outline {
  background: transparent;
  color: var(--color-primary-600);
  border: 1.5px solid var(--color-primary-300);
}

.btn--outline:hover:not(:disabled) {
  background: var(--color-primary-50);
  border-color: var(--color-primary-500);
  color: var(--color-primary-700);
}

/* Icon */
.btn__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform var(--duration-fast) var(--ease-out-quart);
}

.btn__icon svg {
  width: 1.125em;
  height: 1.125em;
}

.btn__icon--left {
  margin-right: calc(var(--space-1) * -0.5);
}

.btn__icon--right {
  margin-left: calc(var(--space-1) * -0.5);
}

.btn:hover .btn__icon--right {
  transform: translateX(2px);
}

/* Loading State */
.btn--loading {
  cursor: wait;
}

.btn__spinner {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn__spinner svg {
  width: 1.25em;
  height: 1.25em;
  animation: spin 1s linear infinite;
}

.btn__content--hidden {
  opacity: 0;
}

/* Disabled State */
.btn--disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* Full Width */
.btn--full-width {
  width: 100%;
}

/* Ripple Effect */
.btn__ripples {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  border-radius: inherit;
}

.btn__ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.4);
  transform: scale(0);
  animation: rippleEffect 0.6s var(--ease-out-quart);
  pointer-events: none;
}

@keyframes rippleEffect {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .btn {
    transition: none;
  }
  
  .btn:hover:not(:disabled) {
    transform: none;
  }
  
  .btn__ripple {
    animation: none;
    opacity: 0;
  }
  
  .btn__spinner svg {
    animation: none;
  }
}
</style>
