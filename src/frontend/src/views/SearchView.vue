<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { search as searchApi, type SearchParams, type SearchResult } from '@/api/search';

const route = useRoute();
const loading = ref(false);
const result = ref<SearchResult | null>(null);
const keyword = ref('');

async function doSearch() {
  const q = (route.query.q as string) || '';
  if (q.length < 2) return;
  keyword.value = q;
  loading.value = true;
  try {
    result.value = await searchApi({ q });
  } finally {
    loading.value = false;
  }
}

watch(() => route.query.q, doSearch);
onMounted(doSearch);
</script>

<template>
  <div class="search-page container">
    <header class="search-page__header">
      <el-input v-model="keyword" placeholder="搜索资源" @keyup.enter="doSearch" />
    </header>

    <div v-loading="loading" class="search-page__body">
      <template v-if="result">
        <p class="search-page__meta">
          共 {{ result.total }} 条结果，耗时 {{ (result.took_ms / 1000).toFixed(2) }}s
          <span v-if="result.sources_failed.length > 0" class="search-page__warn">
            （部分来源失败：{{ result.sources_failed.join('、') }}）
          </span>
        </p>

        <ul v-if="result.items.length > 0" class="search-page__list">
          <li v-for="item in result.items" :key="item.hash" class="card search-page__item">
            <router-link :to="{ name: 'resource', params: { hash: item.hash } }" class="search-page__title">
              {{ item.title }}
            </router-link>
            <div class="search-page__meta-row">
              <span class="source-tag">{{ item.source }}</span>
              <span v-if="item.size_bytes" class="search-page__size">
                {{ (item.size_bytes / 1024 / 1024 / 1024).toFixed(2) }} GB
              </span>
              <span class="search-page__time">{{ item.extracted_at }}</span>
            </div>
          </li>
        </ul>

        <p v-else class="search-page__empty">未找到相关资源，试试更换关键词或扩大来源范围</p>
      </template>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.search-page {
  padding: $spacing-xl 0;

  &__header {
    margin-bottom: $spacing-xl;
  }

  &__meta {
    color: $color-text-secondary;
    font-size: $font-size-small;
    margin: 0 0 $spacing-base;
  }

  &__warn {
    color: $color-warning;
    margin-left: $spacing-sm;
  }

  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: $spacing-base;
  }

  &__item {
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
  }

  &__title {
    font-size: $font-size-h3;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
  }

  &__meta-row {
    display: flex;
    align-items: center;
    gap: $spacing-md;
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__empty {
    text-align: center;
    color: $color-text-secondary;
    padding: $spacing-3xl 0;
  }
}
</style>
