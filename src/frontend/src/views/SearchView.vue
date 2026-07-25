<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { search as searchApi, type SearchParams } from '@/api/search';
import type { ResourceItem, SearchResult } from '@/api/types';
import JButton from '@/components/JButton.vue';
import JCard from '@/components/JCard.vue';
import JSourceTag from '@/components/JSourceTag.vue';
import JStatusBadge from '@/components/JStatusBadge.vue';
import JPagination from '@/components/JPagination.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const result = ref<SearchResult | null>(null);
const keyword = ref('');
const currentPage = ref(1);
const pageSize = ref(20);

// 筛选条件
const filters = reactive({
  sources: [] as string[],
  timeRange: '' as '' | 'day' | 'week' | 'month' | 'year',
  sizeRange: '' as '' | 'small' | 'medium' | 'large',
  status: '' as '' | 'active' | 'invalid' | 'unchecked',
});

// 来源列表
const sourceOptions = [
  { key: 'baidu', label: '百度网盘' },
  { key: 'aliyun', label: '阿里云盘' },
  { key: 'quark', label: '夸克网盘' },
  { key: '115', label: '115 网盘' },
  { key: 'telegram', label: 'TG 频道' },
];

const timeOptions = [
  { key: 'day', label: '近一天' },
  { key: 'week', label: '近一周' },
  { key: 'month', label: '近一月' },
  { key: 'year', label: '近一年' },
];

const sizeOptions = [
  { key: 'small', label: '小于 100 MB' },
  { key: 'medium', label: '100 MB - 1 GB' },
  { key: 'large', label: '大于 1 GB' },
];

const statusOptions = [
  { key: 'active', label: '有效' },
  { key: 'unchecked', label: '待检测' },
  { key: 'invalid', label: '已失效' },
];

const showFilter = ref(false);

const canSearch = computed(() => keyword.value.trim().length >= 2);

const sizeRangeMap: Record<string, [number, number]> = {
  small: [0, 100 * 1024 * 1024],
  medium: [100 * 1024 * 1024, 1024 * 1024 * 1024],
  large: [1024 * 1024 * 1024, Number.MAX_SAFE_INTEGER],
};

async function doSearch() {
  const q = keyword.value.trim();
  if (q.length < 2) {
    result.value = null;
    return;
  }

  loading.value = true;
  try {
    const params: SearchParams = {
      q,
      page: currentPage.value,
      size: pageSize.value,
    };
    if (filters.sources.length) params.sources = filters.sources;
    if (filters.timeRange) params.time_range = filters.timeRange;
    if (filters.status) params.status = filters.status;
    if (filters.sizeRange) {
      const [min, max] = sizeRangeMap[filters.sizeRange];
      params.min_size = min;
      if (max !== Number.MAX_SAFE_INTEGER) params.max_size = max;
    }
    result.value = await searchApi(params);
  } catch (e) {
    result.value = null;
  } finally {
    loading.value = false;
  }
}

function syncFromRoute() {
  const q = (route.query.q as string) || '';
  const sources = (route.query.sources as string) || '';
  const category = (route.query.category as string) || '';
  keyword.value = q;
  filters.sources = sources ? sources.split(',') : [];
  currentPage.value = parseInt((route.query.page as string) || '1', 10) || 1;

  // 分类入口映射到来源
  if (category === 'pan') {
    filters.sources = ['baidu', 'aliyun', 'quark', '115'];
  } else if (category === 'tg') {
    filters.sources = ['telegram'];
  }
}

function handleSearch() {
  if (!canSearch.value) return;
  currentPage.value = 1;
  pushRoute();
  doSearch();
}

function pushRoute() {
  router.push({
    name: 'search',
    query: {
      q: keyword.value.trim(),
      ...(filters.sources.length ? { sources: filters.sources.join(',') } : {}),
      ...(filters.timeRange ? { time: filters.timeRange } : {}),
      ...(filters.status ? { status: filters.status } : {}),
      ...(currentPage.value > 1 ? { page: currentPage.value } : {}),
    },
  });
}

function toggleSource(key: string) {
  const idx = filters.sources.indexOf(key);
  if (idx >= 0) filters.sources.splice(idx, 1);
  else filters.sources.push(key);
  currentPage.value = 1;
  doSearch();
}

function setTimeRange(key: string) {
  filters.timeRange = (filters.timeRange === key ? '' : key) as typeof filters.timeRange;
  currentPage.value = 1;
  doSearch();
}

function setSizeRange(key: string) {
  filters.sizeRange = (filters.sizeRange === key ? '' : key) as typeof filters.sizeRange;
  currentPage.value = 1;
  doSearch();
}

function setStatus(key: string) {
  filters.status = (filters.status === key ? '' : key) as typeof filters.status;
  currentPage.value = 1;
  doSearch();
}

function resetFilters() {
  filters.sources = [];
  filters.timeRange = '';
  filters.sizeRange = '';
  filters.status = '';
  currentPage.value = 1;
  doSearch();
}

function onPageChange(p: number) {
  currentPage.value = p;
  pushRoute();
  doSearch();
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

function formatSize(bytes: number | null): string {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let val = bytes;
  let i = 0;
  while (val >= 1024 && i < units.length - 1) {
    val /= 1024;
    i++;
  }
  return `${val.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function formatDate(iso: string): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  const now = Date.now();
  const diff = now - d.getTime();
  const day = 24 * 60 * 60 * 1000;
  if (diff < day) {
    const h = Math.floor(diff / (60 * 60 * 1000));
    return h <= 0 ? '刚刚' : `${h} 小时前`;
  }
  if (diff < 30 * day) {
    return `${Math.floor(diff / day)} 天前`;
  }
  return d.toISOString().split('T')[0];
}

function openOrigin(item: ResourceItem) {
  if (item.origin_url) {
    window.open(item.origin_url, '_blank', 'noopener,noreferrer');
  }
}

function favorite(item: ResourceItem) {
  // 待接入：M3 阶段接入 POST /api/favorites
  console.log('favorite', item.hash);
}

function report(item: ResourceItem) {
  // 待接入：M3 阶段接入 POST /api/reports
  console.log('report', item.hash);
}

function parse(item: ResourceItem) {
  // 待接入：M5 阶段接入 POST /api/resource/:hash/parse
  console.log('parse', item.hash);
}

// 骨架占位
const skeletons = Array.from({ length: 6 });

watch(() => route.query, () => {
  syncFromRoute();
  if (keyword.value.trim().length >= 2) doSearch();
});

onMounted(() => {
  syncFromRoute();
  if (keyword.value.trim().length >= 2) doSearch();
});
</script>

<template>
  <div class="search">
    <!-- 顶部搜索条 -->
    <header class="search__topbar">
      <div class="container search__topbar-inner">
        <router-link to="/" class="search__logo" aria-label="返回首页">
          <span class="search__logo-mark" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="search__logo-text">jisou</span>
        </router-link>

        <div class="search__topbar-search">
          <span class="search__topbar-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
              <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6"/>
              <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <input
            v-model="keyword"
            class="search__topbar-input"
            type="text"
            placeholder="输入关键词，至少 2 个字符"
            aria-label="搜索关键词"
            @keyup.enter="handleSearch"
          />
          <JButton
            type="primary"
            :disabled="!canSearch"
            @click="handleSearch"
          >
            搜索
          </JButton>
        </div>

        <div class="search__topbar-actions">
          <router-link to="/user" class="search__topbar-link">我的</router-link>
          <router-link to="/login" class="search__topbar-link">登录</router-link>
        </div>
      </div>
    </header>

    <!-- 主体 -->
    <div class="container search__body">
      <!-- 移动端筛选切换 -->
      <button
        class="search__filter-toggle"
        type="button"
        @click="showFilter = !showFilter"
      >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
          <path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span>筛选</span>
        <span v-if="filters.sources.length || filters.timeRange || filters.sizeRange || filters.status" class="search__filter-badge">
          {{ filters.sources.length + (filters.timeRange ? 1 : 0) + (filters.sizeRange ? 1 : 0) + (filters.status ? 1 : 0) }}
        </span>
      </button>

      <div class="search__layout">
        <!-- 左侧筛选 -->
        <aside class="search__filters" :class="{ 'is-open': showFilter }">
          <div class="search__filter-head">
            <h3 class="search__filter-title">筛选</h3>
            <button
              v-if="filters.sources.length || filters.timeRange || filters.sizeRange || filters.status"
              type="button"
              class="search__filter-reset"
              @click="resetFilters"
            >
              清空
            </button>
          </div>

          <!-- 来源 -->
          <div class="search__filter-group">
            <h4 class="search__filter-label">来源</h4>
            <div class="search__filter-chips">
              <button
                v-for="s in sourceOptions"
                :key="s.key"
                type="button"
                class="search__filter-chip"
                :class="{ 'is-active': filters.sources.includes(s.key) }"
                @click="toggleSource(s.key)"
              >
                {{ s.label }}
              </button>
            </div>
          </div>

          <!-- 时间 -->
          <div class="search__filter-group">
            <h4 class="search__filter-label">抓取时间</h4>
            <div class="search__filter-list">
              <button
                v-for="t in timeOptions"
                :key="t.key"
                type="button"
                class="search__filter-item"
                :class="{ 'is-active': filters.timeRange === t.key }"
                @click="setTimeRange(t.key)"
              >
                <span class="search__filter-radio" aria-hidden="true" />
                {{ t.label }}
              </button>
            </div>
          </div>

          <!-- 大小 -->
          <div class="search__filter-group">
            <h4 class="search__filter-label">文件大小</h4>
            <div class="search__filter-list">
              <button
                v-for="s in sizeOptions"
                :key="s.key"
                type="button"
                class="search__filter-item"
                :class="{ 'is-active': filters.sizeRange === s.key }"
                @click="setSizeRange(s.key)"
              >
                <span class="search__filter-radio" aria-hidden="true" />
                {{ s.label }}
              </button>
            </div>
          </div>

          <!-- 状态 -->
          <div class="search__filter-group">
            <h4 class="search__filter-label">链接状态</h4>
            <div class="search__filter-list">
              <button
                v-for="s in statusOptions"
                :key="s.key"
                type="button"
                class="search__filter-item"
                :class="{ 'is-active': filters.status === s.key }"
                @click="setStatus(s.key)"
              >
                <span class="search__filter-radio" aria-hidden="true" />
                {{ s.label }}
              </button>
            </div>
          </div>
        </aside>

        <!-- 右侧结果 -->
        <main class="search__main">
          <!-- 统计 -->
          <div v-if="result && !loading" class="search__stats">
            <p class="search__stats-text">
              共找到 <strong>{{ result.total.toLocaleString() }}</strong> 条结果
              <span class="search__stats-divider">·</span>
              耗时 <strong>{{ (result.took_ms / 1000).toFixed(2) }}s</strong>
              <span v-if="result.sources_failed.length" class="search__stats-warn">
                · 部分来源失败：{{ result.sources_failed.join('、') }}
              </span>
            </p>
            <div v-if="filters.sources.length || filters.timeRange || filters.sizeRange || filters.status" class="search__active-filters">
              <span
                v-for="s in filters.sources"
                :key="s"
                class="search__active-filter"
              >
                {{ sourceOptions.find(o => o.key === s)?.label || s }}
                <button type="button" class="search__active-filter-close" @click="toggleSource(s)" aria-label="移除">×</button>
              </span>
              <span v-if="filters.timeRange" class="search__active-filter">
                {{ timeOptions.find(o => o.key === filters.timeRange)?.label }}
                <button type="button" class="search__active-filter-close" @click="setTimeRange(filters.timeRange)" aria-label="移除">×</button>
              </span>
              <span v-if="filters.sizeRange" class="search__active-filter">
                {{ sizeOptions.find(o => o.key === filters.sizeRange)?.label }}
                <button type="button" class="search__active-filter-close" @click="setSizeRange(filters.sizeRange)" aria-label="移除">×</button>
              </span>
              <span v-if="filters.status" class="search__active-filter">
                {{ statusOptions.find(o => o.key === filters.status)?.label }}
                <button type="button" class="search__active-filter-close" @click="setStatus(filters.status)" aria-label="移除">×</button>
              </span>
            </div>
          </div>

          <!-- 加载骨架屏 -->
          <div v-if="loading" class="search__skeletons">
            <div v-for="(_, i) in skeletons" :key="i" class="search__skeleton">
              <div class="skeleton search__skeleton-icon" />
              <div class="search__skeleton-body">
                <div class="skeleton search__skeleton-title" />
                <div class="skeleton search__skeleton-meta" />
              </div>
            </div>
          </div>

          <!-- 空状态 -->
          <div v-else-if="result && result.items.length === 0" class="search__empty">
            <div class="search__empty-icon" aria-hidden="true">
              <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                <circle cx="28" cy="28" r="18" stroke="currentColor" stroke-width="1.6"/>
                <path d="M42 42L54 54" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M22 28h12M28 22v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </div>
            <h3 class="search__empty-title">未找到相关资源</h3>
            <p class="search__empty-desc">试试更换关键词、扩大来源范围或清除筛选条件</p>
            <JButton v-if="filters.sources.length || filters.timeRange || filters.sizeRange || filters.status" @click="resetFilters">
              清空筛选
            </JButton>
          </div>

          <!-- 结果列表 -->
          <ul v-else-if="result && result.items.length > 0" class="search__list">
            <li
              v-for="(item, i) in result.items"
              :key="item.hash"
              class="search__item animate-fade-up"
              :style="{ animationDelay: `${Math.min(i * 0.04, 0.32)}s` }"
            >
              <JCard hoverable padding="default" class="search__item-card">
                <div class="search__item-main">
                  <div class="search__item-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                      <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                      <path d="M14 3v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                  </div>

                  <div class="search__item-content">
                    <router-link
                      :to="{ name: 'resource', params: { hash: item.hash } }"
                      class="search__item-title"
                    >
                      {{ item.title }}
                    </router-link>

                    <div class="search__item-meta">
                      <JSourceTag :source="item.source" size="small" />
                      <JStatusBadge v-if="item.status" :status="item.status" />
                      <span class="search__item-meta-item">
                        <span class="search__item-meta-label">大小</span>
                        <span class="search__item-meta-value">{{ formatSize(item.size_bytes) }}</span>
                      </span>
                      <span v-if="item.file_count" class="search__item-meta-item">
                        <span class="search__item-meta-label">文件</span>
                        <span class="search__item-meta-value">{{ item.file_count }}</span>
                      </span>
                      <span class="search__item-meta-item">
                        <span class="search__item-meta-label">抓取</span>
                        <span class="search__item-meta-value">{{ formatDate(item.extracted_at) }}</span>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="search__item-actions">
                  <JButton
                    v-if="item.origin_url"
                    size="small"
                    @click="openOrigin(item)"
                  >
                    打开原链接
                  </JButton>
                  <JButton
                    size="small"
                    @click="parse(item)"
                  >
                    解析
                  </JButton>
                  <JButton
                    size="small"
                    @click="favorite(item)"
                  >
                    收藏
                  </JButton>
                  <JButton
                    size="small"
                    type="danger"
                    ghost
                    @click="report(item)"
                  >
                    举报
                  </JButton>
                </div>
              </JCard>
            </li>
          </ul>

          <!-- 分页 -->
          <div v-if="result && result.total > pageSize" class="search__pagination">
            <JPagination
              :page="currentPage"
              :total="result.total"
              :size="pageSize"
              @change="onPageChange"
            />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.search {
  min-height: 100vh;
  background: $color-bg-secondary;

  // ============ 顶部搜索条 ============
  &__topbar {
    position: sticky;
    top: 0;
    z-index: $z-sticky;
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid $color-divider;
  }

  &__topbar-inner {
    height: $height-nav;
    display: flex;
    align-items: center;
    gap: $spacing-xl;
  }

  &__logo {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    font-weight: $font-weight-semibold;
    font-size: $font-size-h3;
    letter-spacing: $letter-spacing-tight;
    flex-shrink: 0;

    &:hover { color: $color-primary; }
  }

  &__logo-mark {
    display: inline-flex;
    color: $color-primary;
  }

  &__topbar-search {
    flex: 1;
    display: flex;
    align-items: center;
    height: 40px;
    padding: 0 4px 0 $spacing-md;
    background: $color-bg-secondary;
    border: 1px solid $color-border;
    border-radius: $radius-pill;
    transition: border-color $transition-fast, box-shadow $transition-fast, background $transition-fast;

    &:focus-within {
      background: $color-bg-primary;
      border-color: $color-primary;
      box-shadow: $shadow-focus;
    }
  }

  &__topbar-icon {
    display: inline-flex;
    color: $color-text-placeholder;
    margin-right: $spacing-xs;
  }

  &__topbar-input {
    flex: 1;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: $font-size-body;
    color: $color-text-primary;

    &::placeholder { color: $color-text-placeholder; }
  }

  &__topbar-actions {
    display: flex;
    gap: $spacing-lg;
    flex-shrink: 0;

    @media (max-width: $breakpoint-mobile) {
      display: none;
    }
  }

  &__topbar-link {
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    transition: color $transition-fast;

    &:hover { color: $color-primary; }
  }

  // ============ 主体布局 ============
  &__body {
    padding: $spacing-xl 0 $spacing-5xl;
  }

  &__layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: $spacing-xl;
    align-items: start;

    @media (max-width: $breakpoint-tablet) {
      grid-template-columns: 1fr;
    }
  }

  // ============ 移动端筛选切换 ============
  &__filter-toggle {
    display: none;
    align-items: center;
    gap: $spacing-sm;
    width: 100%;
    height: 40px;
    padding: 0 $spacing-base;
    margin-bottom: $spacing-base;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-base;
    color: $color-text-primary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;

    @media (max-width: $breakpoint-tablet) {
      display: inline-flex;
    }
  }

  &__filter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 $spacing-xs;
    margin-left: auto;
    background: $color-primary;
    color: $color-text-inverse;
    font-size: $font-size-caption;
    border-radius: $radius-pill;
  }

  // ============ 左侧筛选 ============
  &__filters {
    position: sticky;
    top: #{$height-nav + $spacing-xl};
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
    padding: $spacing-base;

    @media (max-width: $breakpoint-tablet) {
      position: static;
      display: none;

      &.is-open { display: block; }
    }
  }

  &__filter-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-sm $spacing-sm $spacing-base;
    border-bottom: 1px solid $color-divider;
    margin-bottom: $spacing-sm;
  }

  &__filter-title {
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-wide;
  }

  &__filter-reset {
    color: $color-text-secondary;
    font-size: $font-size-small;
    transition: color $transition-fast;

    &:hover { color: $color-danger; }
  }

  &__filter-group {
    padding: $spacing-md $spacing-sm;

    &:not(:last-child) {
      border-bottom: 1px dashed $color-divider;
    }
  }

  &__filter-label {
    font-size: $font-size-caption;
    font-weight: $font-weight-medium;
    color: $color-text-secondary;
    letter-spacing: $letter-spacing-wider;
    text-transform: uppercase;
    margin-bottom: $spacing-sm;
  }

  &__filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: $spacing-xs;
  }

  &__filter-chip {
    display: inline-flex;
    align-items: center;
    height: 26px;
    padding: 0 $spacing-sm;
    border: 1px solid $color-border;
    border-radius: $radius-pill;
    background: $color-bg-primary;
    color: $color-text-secondary;
    font-size: $font-size-small;
    transition: all $transition-fast;

    &:hover {
      border-color: $color-primary;
      color: $color-primary;
    }

    &.is-active {
      background: $color-primary;
      border-color: $color-primary;
      color: $color-text-inverse;
    }
  }

  &__filter-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  &__filter-item {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-sm $spacing-xs;
    border-radius: $radius-sm;
    color: $color-text-secondary;
    font-size: $font-size-small;
    text-align: left;
    transition: all $transition-fast;

    &:hover {
      background: $color-bg-secondary;
      color: $color-text-primary;
    }

    &.is-active {
      color: $color-primary;
      font-weight: $font-weight-medium;
    }
  }

  &__filter-radio {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 1.5px solid $color-border-strong;
    border-radius: 50%;
    position: relative;
    flex-shrink: 0;
    transition: all $transition-fast;

    .search__filter-item.is-active & {
      border-color: $color-primary;

      &::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6px;
        height: 6px;
        background: $color-primary;
        border-radius: 50%;
      }
    }
  }

  // ============ 右侧主区 ============
  &__main {
    min-width: 0;
  }

  &__stats {
    margin-bottom: $spacing-base;
  }

  &__stats-text {
    color: $color-text-secondary;
    font-size: $font-size-small;
    margin: 0 0 $spacing-sm;

    strong {
      color: $color-text-primary;
      font-weight: $font-weight-semibold;
      font-family: $font-family-mono;
    }
  }

  &__stats-divider {
    margin: 0 $spacing-xs;
    color: $color-text-placeholder;
  }

  &__stats-warn {
    color: $color-warning;
    margin-left: $spacing-xs;
  }

  &__active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: $spacing-xs;
    margin-top: $spacing-sm;
  }

  &__active-filter {
    display: inline-flex;
    align-items: center;
    gap: $spacing-xs;
    padding: $spacing-xs $spacing-sm;
    background: $color-primary-soft;
    color: $color-primary;
    border-radius: $radius-sm;
    font-size: $font-size-small;
    font-weight: $font-weight-medium;
  }

  &__active-filter-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    color: $color-primary;
    font-size: 14px;
    line-height: 1;
    border-radius: 50%;

    &:hover {
      background: rgba(30, 91, 184, 0.2);
    }
  }

  // ============ 骨架屏 ============
  &__skeletons {
    display: flex;
    flex-direction: column;
    gap: $spacing-base;
  }

  &__skeleton {
    display: flex;
    align-items: center;
    gap: $spacing-base;
    padding: $spacing-base;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
  }

  &__skeleton-icon {
    width: 40px;
    height: 40px;
    border-radius: $radius-base;
    flex-shrink: 0;
  }

  &__skeleton-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
  }

  &__skeleton-title {
    height: 16px;
    width: 60%;
    border-radius: $radius-sm;
  }

  &__skeleton-meta {
    height: 12px;
    width: 40%;
    border-radius: $radius-sm;
  }

  // ============ 空状态 ============
  &__empty {
    text-align: center;
    padding: $spacing-5xl $spacing-base;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
  }

  &__empty-icon {
    display: inline-flex;
    color: $color-text-placeholder;
    margin-bottom: $spacing-base;
  }

  &__empty-title {
    font-size: $font-size-h3;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__empty-desc {
    color: $color-text-secondary;
    font-size: $font-size-body;
    margin-bottom: $spacing-lg;
  }

  // ============ 结果列表 ============
  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
  }

  &__item {
    width: 100%;
  }

  &__item-card {
    width: 100%;
  }

  &__item-main {
    display: flex;
    align-items: flex-start;
    gap: $spacing-base;
  }

  &__item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: $color-primary-soft;
    color: $color-primary;
    border-radius: $radius-base;
    flex-shrink: 0;
  }

  &__item-content {
    flex: 1;
    min-width: 0;
  }

  &__item-title {
    display: block;
    font-size: $font-size-body-lg;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    line-height: $line-height-subheading;
    margin-bottom: $spacing-sm;
    transition: color $transition-fast;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    &:hover {
      color: $color-primary;
    }
  }

  &__item-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm $spacing-md;
  }

  &__item-meta-item {
    display: inline-flex;
    align-items: center;
    gap: $spacing-xs;
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__item-meta-label {
    color: $color-text-placeholder;
  }

  &__item-meta-value {
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__item-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm;
    margin-top: $spacing-md;
    padding-top: $spacing-md;
    border-top: 1px dashed $color-divider;
  }

  // ============ 分页 ============
  &__pagination {
    display: flex;
    justify-content: center;
    margin-top: $spacing-2xl;
  }
}
</style>
