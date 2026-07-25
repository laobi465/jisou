<script setup lang="ts">
// 状态徽章
// 设计依据：docs/UI-DESIGN.md §九 状态规范
// active 墨绿 / invalid 砖红 / unchecked 中灰
import { computed } from 'vue';

interface Props {
  status: 'active' | 'invalid' | 'unchecked';
}

const props = defineProps<Props>();

const labels = {
  active: '有效',
  invalid: '已失效',
  unchecked: '待检测',
};

const label = computed(() => labels[props.status]);
</script>

<template>
  <span class="j-status-badge" :class="`j-status-badge--${status}`">
    <i class="j-status-badge__dot" />
    {{ label }}
  </span>
</template>

<style lang="scss" scoped>
.j-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: $height-tag;
  padding: 0 $spacing-sm;
  border-radius: $radius-sm;
  font-size: $font-size-small;
  font-weight: $font-weight-medium;
  line-height: 1;

  &__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  &--active {
    background: rgba($color-accent-2, 0.12);
    color: $color-accent-2;
  }

  &--invalid {
    background: rgba($color-danger, 0.12);
    color: $color-danger;
  }

  &--unchecked {
    background: rgba($color-text-secondary, 0.12);
    color: $color-text-secondary;
  }
}
</style>
