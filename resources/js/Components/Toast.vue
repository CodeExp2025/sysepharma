<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    required: true
  },
  duration: {
    type: Number,
    default: 5000
  },
  dismissible: {
    type: Boolean,
    default: true
  },
  position: {
    type: String,
    default: 'top-right'
  }
});

const emit = defineEmits(['dismiss', 'close']);

const isVisible = ref(false);
const isPaused = ref(false);
const progress = ref(100);
const timer = ref(null);
const animationFrame = ref(null);
let startTime = null;
let remainingTime = props.duration;

const typeConfig = {
  success: {
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    bgClass: 'bg-emerald-50',
    borderClass: 'border-emerald-200',
    textClass: 'text-emerald-800',
    iconClass: 'text-emerald-500',
    progressClass: 'bg-emerald-500'
  },
  error: {
    icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    bgClass: 'bg-red-50',
    borderClass: 'border-red-200',
    textClass: 'text-red-800',
    iconClass: 'text-red-500',
    progressClass: 'bg-red-500'
  },
  warning: {
    icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    bgClass: 'bg-amber-50',
    borderClass: 'border-amber-200',
    textClass: 'text-amber-800',
    iconClass: 'text-amber-500',
    progressClass: 'bg-amber-500'
  },
  info: {
    icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    bgClass: 'bg-blue-50',
    borderClass: 'border-blue-200',
    textClass: 'text-blue-800',
    iconClass: 'text-blue-500',
    progressClass: 'bg-blue-500'
  }
};

const config = computed(() => typeConfig[props.type]);

const startTimer = () => {
  if (props.duration <= 0) return;
  
  startTime = Date.now();
  
  const updateProgress = () => {
    if (isPaused.value) {
      animationFrame.value = requestAnimationFrame(updateProgress);
      return;
    }
    
    const elapsed = Date.now() - startTime;
    const newRemaining = Math.max(0, remainingTime - elapsed);
    progress.value = (newRemaining / props.duration) * 100;
    
    if (newRemaining <= 0) {
      dismiss();
    } else {
      animationFrame.value = requestAnimationFrame(updateProgress);
    }
  };
  
  animationFrame.value = requestAnimationFrame(updateProgress);
};

const pauseTimer = () => {
  isPaused.value = true;
  if (startTime) {
    remainingTime = Math.max(0, remainingTime - (Date.now() - startTime));
  }
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value);
  }
};

const resumeTimer = () => {
  isPaused.value = false;
  startTime = Date.now();
  startTimer();
};

const dismiss = () => {
  isVisible.value = false;
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value);
  }
  setTimeout(() => {
    emit('dismiss', props.id);
  }, 300);
};

onMounted(() => {
  // Small delay for entrance animation
  setTimeout(() => {
    isVisible.value = true;
    startTimer();
  }, 50);
});

onUnmounted(() => {
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value);
  }
});
</script>

<template>
  <Transition
    enter-active-class="toast-enter"
    enter-from-class="toast-enter-from"
    enter-to-class="toast-enter-to"
    leave-active-class="toast-leave"
    leave-from-class="toast-leave-from"
    leave-to-class="toast-leave-to"
  >
    <div
      v-show="isVisible"
      class="toast"
      :class="[config.bgClass, config.borderClass]"
      @mouseenter="pauseTimer"
      @mouseleave="resumeTimer"
    >
      <!-- Icon -->
      <div class="toast__icon" :class="config.iconClass">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path :d="config.icon" />
        </svg>
      </div>
      
      <!-- Content -->
      <div class="toast__content">
        <h4 v-if="title" class="toast__title" :class="config.textClass">{{ title }}</h4>
        <p class="toast__message" :class="config.textClass">{{ message }}</p>
      </div>
      
      <!-- Close Button -->
      <button
        v-if="dismissible"
        class="toast__close"
        :class="config.textClass"
        @click="dismiss"
        aria-label="Fermer"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <!-- Progress Bar -->
      <div v-if="duration > 0" class="toast__progress">
        <div
          class="toast__progress-bar"
          :class="config.progressClass"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.toast {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  border-radius: var(--radius-xl);
  border: 1px solid;
  box-shadow: var(--shadow-lg);
  min-width: 20rem;
  max-width: 28rem;
  position: relative;
  overflow: hidden;
  pointer-events: auto;
}

.toast__icon {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  margin-top: 0.125rem;
}

.toast__content {
  flex: 1;
  min-width: 0;
}

.toast__title {
  font-size: var(--text-md);
  font-weight: var(--font-semibold);
  margin: 0 0 var(--space-1) 0;
  line-height: var(--leading-tight);
}

.toast__message {
  font-size: var(--text-sm);
  margin: 0;
  line-height: var(--leading-normal);
  opacity: 0.9;
}

.toast__close {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  background: transparent;
  border: none;
  cursor: pointer;
  opacity: 0.6;
  transition: all var(--duration-fast) var(--ease-out-quart);
  padding: 0;
}

.toast__close:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.05);
}

.toast__close svg {
  width: 1rem;
  height: 1rem;
}

.toast__progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0, 0, 0, 0.05);
}

.toast__progress-bar {
  height: 100%;
  transition: width 100ms linear;
}

/* Transitions */
.toast-enter {
  transition: all 300ms var(--ease-out-quart);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-enter-to {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.toast-leave {
  transition: all 200ms var(--ease-out-quart);
}

.toast-leave-from {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .toast-enter,
  .toast-leave {
    transition: none;
  }
  
  .toast-enter-from,
  .toast-leave-to {
    opacity: 0;
    transform: none;
  }
  
  .toast-enter-to,
  .toast-leave-from {
    opacity: 1;
    transform: none;
  }
  
  .toast__progress-bar {
    transition: none;
  }
}
</style>
