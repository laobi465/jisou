<script setup lang="ts">
// 通用按钮组件
// 设计依据：docs/UI-DESIGN.md §五 按钮
// 圆角 6px，主按钮高 40px，次按钮高 32px，无渐变无发光
import { computed } from 'vue';

interface Props {
  type?: 'primary' | 'secondary' | 'danger' | 'ghost';
  size?: 'default' | 'small' | 'large';
  loading?: boolean;
  disabled?: boolean;
  block?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'primary',
  size: 'default',
  loading: false,
  disabled: false,
  block: false,
});

const classes = computed(() => [
  'j-btn',
  `j-btn--${props.type}`,
  `j-btn--${props.size}`,
  {
    'is-loading': props.loading,
    'is-disabled': props.disabled,
    'is-block': props.block,
  },
]);
</script>

<template>
  <button
    :class="classes"
    :disabled="disabled || loading"
    type="button"
  >
    <span v-if="loading" class="j-btn__spinner" aria-hidden="true" />
    <span class="j-btn__content"><slot /></span>
  </button>
</template>

<style lang="scss" scoped>
.j-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  border: 1px solid transparent;
  border-radius: $radius-base;
  font-family: inherit;
  font-size: $font-size-body;
  font-weight: $font-weight-medium;
  line-height: 1;
  cursor: pointer;
  transition: all $transition-fast;
  white-space: nowrap;
  user-select: none;

  &--default { height: $height-button; padding: 0 $spacing-lg; }
  &--small  { height: $height-button-sm; padding: 0 $spacing-base; font-size: $font-size-small; }
  &--large  { height: 48px; padding: 0 $spacing-xl; font-size: $font-size-h3; }

  &--primary {
    background: $color-primary;
    color: #fff;
    box-shadow: $shadow-button;

    &:hover:not(.is-disabled):not(.is-loading) {
      background: $color-primary-hover;
    }
  }

  &--secondary {
    background: $color-bg-primary;
    color: $color-primary;
    border-color: $color-primary;

    &:hover:not(.is-disabled):not(.is-loading) {
      background: rgba($color-primary, 0.06);
    }
  }

  &--danger {
    background: $color-danger;
    color: #fff;
    &:hover:not(.is-disabled):not(.is-loading) { background: darken($color-danger, 6%); }
  }

  &--ghost {
    background: transparent;
    color: $color-text-secondary;
    border-color: $color-border;

    &:hover:not(.is-disabled):not(.is-loading) {
      color: $color-text-primary;
      border-color: $color-text-placeholder;
    }
  }

  &.is-disabled,
  &:disabled {
    background: $color-border;
    color: $color-text-placeholder;
    cursor: not-allowed;
    box-shadow: none;
    border-color: transparent;
  }

  &.is-block { width: 100%; }

  &__spinner {
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: j-btn-spin 0.6s linear infinite;
  }
}

@keyframes j-btn-spin {
  to { transform: rotate(360deg); }
}
</style>
