<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { search as searchApi, type SearchParams } from '@/api/search';

const router = useRouter();
const keyword = ref('');
const loading = ref(false);

async function handleSearch() {
  const q = keyword.value.trim();
  if (q.length < 2) {
    return;
  }
  loading.value = true;
  try {
    // 首页搜索后跳到结果页，结果页基于 query 参数再次请求
    router.push({ name: 'search', query: { q } });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="home">
    <section class="home__hero">
      <h1 class="home__title">jisou 聚合搜索</h1>
      <p class="home__subtitle">一站式搜索网盘与 Telegram 资源</p>

      <div class="home__search">
        <el-input
          v-model="keyword"
          placeholder="输入关键词搜索资源"
          size="large"
          @keyup.enter="handleSearch"
        />
        <el-button type="primary" size="large" :loading="loading" @click="handleSearch">
          搜索
        </el-button>
      </div>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.home {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $color-bg-primary;

  &__hero {
    text-align: center;
    max-width: 640px;
    width: 100%;
    padding: 0 $spacing-lg;
  }

  &__title {
    font-size: $font-size-h1;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    margin: 0 0 $spacing-sm;
  }

  &__subtitle {
    font-size: $font-size-body;
    color: $color-text-secondary;
    margin: 0 0 $spacing-3xl;
  }

  &__search {
    display: flex;
    gap: $spacing-sm;
    justify-content: center;
  }
}
</style>
