<script setup lang="ts">
// 分页组件
// 设计依据：docs/UI-DESIGN.md §五 表格 / 分页
import { computed } from 'vue';

interface Props {
  total: number;
  page: number;
  size: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'change', page: number): void }>();

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.size)));

// 生成页码窗口（最多显示 7 个，当前页居中）
const pages = computed<(number | string)[]>(() => {
  const total = totalPages.value;
  const cur = props.page;

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const result: (number | string)[] = [1];
  const left = Math.max(2, cur - 2);
  const right = Math.min(total - 1, cur + 2);

  if (left > 2) result.push('...');
  for (let i = left; i <= right; i++) result.push(i);
  if (right < total - 1) result.push('...');
  result.push(total);
  return result;
});

function go(p: number) {
  if (p < 1 || p > totalPages.value || p === props.page) return;
  emit('change', p);
}
</script>

<template>
  <nav class="j-pagination" aria-label="分页">
    <button
      class="j-pagination__btn"
      :disabled="page <= 1"
      @click="go(page - 1)"
    >上一页</button>

    <ul class="j-pagination__list">
      <li v-for="(p, i) in pages" :key="i">
        <span v-if="p === '...'" class="j-pagination__ellipsis">…</span>
        <button
          v-else
          class="j-pagination__page"
          :class="{ 'is-active': p === page }"
          @click="go(p as number)"
        >{{ p }}</button>
      </li>
    </ul>

    <button
      class="j-pagination__btn"
      :disabled="page >= totalPages"
      @click="go(page + 1)"
    >下一页</button>

    <span class="j-pagination__info">共 {{ total }} 条</span>
  </nav>
</template>

<style lang="scss" scoped>
.j-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  margin-top: $spacing-xl;

  &__list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: $spacing-xs;
  }

  &__btn,
  &__page {
    height: $height-button-sm;
    min-width: $height-button-sm;
    padding: 0 $spacing-sm;
    border: 1px solid $color-border;
    background: $color-bg-primary;
    color: $color-text-primary;
    font-size: $font-size-small;
    border-radius: $radius-sm;
    cursor: pointer;
    transition: all $transition-fast;

    &:hover:not(:disabled):not(.is-active) {
      border-color: $color-primary;
      color: $color-primary;
    }

    &:disabled {
      color: $color-text-placeholder;
      cursor: not-allowed;
      background: $color-bg-secondary;
    }
  }

  &__page.is-active {
    background: $color-primary;
    border-color: $color-primary;
    color: #fff;
    font-weight: $font-weight-medium;
  }

  &__ellipsis {
    display: inline-flex;
    align-items: center;
    height: $height-button-sm;
    padding: 0 $spacing-xs;
    color: $color-text-placeholder;
  }

  &__info {
    margin-left: $spacing-md;
    color: $color-text-secondary;
    font-size: $font-size-small;
  }
}
</style>
