<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'fade-up',
    validator: (value) => ['fade', 'fade-up', 'fade-down', 'slide-left', 'slide-right', 'scale', 'none'].includes(value)
  },
  duration: {
    type: Number,
    default: 300
  },
  delay: {
    type: Number,
    default: 0
  }
});

const isVisible = ref(false);

onMounted(() => {
  // Small delay to ensure DOM is ready
  requestAnimationFrame(() => {
    setTimeout(() => {
      isVisible.value = true;
    }, props.delay);
  });
});

const transitionClasses = {
  fade: {
    enter: 'opacity-0',
    enterTo: 'opacity-100',
    leave: 'opacity-100',
    leaveTo: 'opacity-0'
  },
  'fade-up': {
    enter: 'opacity-0 translate-y-4',
    enterTo: 'opacity-100 translate-y-0',
    leave: 'opacity-100 translate-y-0',
    leaveTo: 'opacity-0 -translate-y-4'
  },
  'fade-down': {
    enter: 'opacity-0 -translate-y-4',
    enterTo: 'opacity-100 translate-y-0',
    leave: 'opacity-100 translate-y-0',
    leaveTo: 'opacity-0 translate-y-4'
  },
  'slide-left': {
    enter: 'opacity-0 translate-x-8',
    enterTo: 'opacity-100 translate-x-0',
    leave: 'opacity-100 translate-x-0',
    leaveTo: 'opacity-0 -translate-x-8'
  },
  'slide-right': {
    enter: 'opacity-0 -translate-x-8',
    enterTo: 'opacity-100 translate-x-0',
    leave: 'opacity-100 translate-x-0',
    leaveTo: 'opacity-0 translate-x-8'
  },
  scale: {
    enter: 'opacity-0 scale-95',
    enterTo: 'opacity-100 scale-100',
    leave: 'opacity-100 scale-100',
    leaveTo: 'opacity-0 scale-95'
  },
  none: {
    enter: '',
    enterTo: '',
    leave: '',
    leaveTo: ''
  }
};

const classes = transitionClasses[props.mode];
</script>

<template>
  <Transition
    :enter-active-class="`page-transition-enter ${classes.enter}`"
    :enter-to-class="`page-transition-enter-to ${classes.enterTo}`"
    :leave-active-class="`page-transition-leave ${classes.leave}`"
    :leave-to-class="`page-transition-leave-to ${classes.leaveTo}`"
    :css="mode !== 'none'"
  >
    <div
      v-show="isVisible"
      class="page-transition-wrapper"
      :class="{ 'is-visible': isVisible }"
    >
      <slot />
    </div>
  </Transition>
</template>

<style scoped>
.page-transition-wrapper {
  will-change: transform, opacity;
}

.page-transition-enter,
.page-transition-leave {
  transition-property: transform, opacity;
  transition-timing-function: cubic-bezier(0.25, 1, 0.5, 1);
  transition-duration: v-bind('duration + "ms"');
}

.page-transition-enter {
  transition-delay: v-bind('delay + "ms"');
}

.page-transition-leave {
  transition-duration: calc(v-bind('duration') * 0.75ms);
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .page-transition-enter,
  .page-transition-leave {
    transition: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
