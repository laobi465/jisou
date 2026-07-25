<script setup lang="ts">
// 来源标签
// 设计依据：docs/UI-DESIGN.md §五 标签 Tag
// 浅色背景 + 主色文字，圆角 4px
import { computed } from 'vue';

interface Props {
  source: string; // baidu / aliyun / quark / 115 / telegram / self
  size?: 'small' | 'default';
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default',
});

const sourceLabels: Record<string, string> = {
  baidu: '百度网盘',
  aliyun: '阿里云盘',
  quark: '夸克网盘',
  '115': '115 网盘',
  telegram: 'TG 频道',
  self: '自建索引',
};

const label = computed(() => sourceLabels[props.source] ?? props.source);

const colorMap: Record<string, string> = {
  baidu: '#3A7BD5',
  aliyun: '#1E5BB8',
  quark: '#2E8B6F',
  '115': '#6B7280',
  telegram: '#3A7BD5',
  self: '#1E5BB8',
};

const style = computed(() => {
  const c = colorMap[props.source] ?? '#1E5BB8';
  return {
    background: `rgba(${hexToRgb(c)}, 0.10)`,
    color: c,
  };
});

function hexToRgb(hex: string): string {
  const h = hex.replace('#', '');
  const r = parseInt(h.substring(0, 2), 16);
  const g = parseInt(h.substring(2, 4), 16);
  const b = parseInt(h.substring(4, 6), 16);
  return `${r}, ${g}, ${b}`;
}
</script>

<template>
  <span class="j-source-tag" :class="`j-source-tag--${size}`" :style="style">{{ label }}</span>
</template>

<style lang="scss" scoped>
.j-source-tag {
  display: inline-flex;
  align-items: center;
  height: $height-tag;
  padding: 0 $spacing-sm;
  border-radius: $radius-sm;
  font-size: $font-size-small;
  font-weight: $font-weight-medium;
  line-height: 1;
  letter-spacing: 0.02em;

  &--small {
    height: 18px;
    font-size: 11px;
    padding: 0 $spacing-xs;
  }
}
</style>
