<script setup lang="ts">
// 通用卡片组件
// 设计依据：docs/UI-DESIGN.md §五 卡片
// 白底细边框，圆角 8px，hover 时极淡阴影
import { computed } from 'vue';

interface Props {
  hoverable?: boolean;
  padding?: 'compact' | 'default' | 'loose';
}

const props = withDefaults(defineProps<Props>(), {
  hoverable: false,
  padding: 'default',
});

const classes = computed(() => [
  'j-card',
  `j-card--${props.padding}`,
  { 'j-card--hoverable': props.hoverable },
]);
</script>

<template>
  <div :class="classes">
    <slot />
  </div>
</template>

<style lang="scss" scoped>
.j-card {
  background: $color-bg-primary;
  border: 1px solid $color-border;
  border-radius: $radius-lg;
  transition: box-shadow $transition-fast, transform $transition-fast;

  &--compact { padding: $spacing-base; }
  &--default  { padding: $spacing-lg; }
  &--loose    { padding: $spacing-xl; }

  &--hoverable:hover {
    box-shadow: $shadow-card-hover;
    transform: translateY(-1px);
  }
}
</style>
