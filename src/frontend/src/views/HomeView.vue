<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import JButton from '@/components/JButton.vue';
import JCard from '@/components/JCard.vue';

const router = useRouter();

const keyword = ref('');
const selectedSources = ref<string[]>([]);

// 热门搜索词（待接入：M1 阶段从 /api/search/hot 接口拉取）
const hotKeywords = [
  '编程入门', '机器学习', '设计模式', '英语口语',
  '考研政治', '英语四级', 'Python 实战', '前端框架',
  '算法导论', '操作系统', '数据库', '深度学习',
];

// 来源列表
const sources = [
  { key: 'baidu', label: '百度网盘' },
  { key: 'aliyun', label: '阿里云盘' },
  { key: 'quark', label: '夸克网盘' },
  { key: '115', label: '115 网盘' },
  { key: 'telegram', label: 'TG 频道' },
];

// 分类入口
const categories = [
  {
    key: 'pan',
    title: '网盘资源',
    desc: '聚合主流网盘分享链接',
    count: '12.4 万',
    icon: 'pan',
  },
  {
    key: 'tg',
    title: 'TG 频道',
    desc: '索引公开频道历史消息',
    count: '8.7 万',
    icon: 'tg',
  },
  {
    key: 'doc',
    title: '文档资料',
    desc: '教程 / 电子书 / 论文',
    count: '3.2 万',
    icon: 'doc',
  },
  {
    key: 'media',
    title: '影视资源',
    desc: '电影 / 剧集 / 纪录片',
    count: '5.6 万',
    icon: 'media',
  },
];

const canSearch = computed(() => keyword.value.trim().length >= 2);

function handleSearch() {
  if (!canSearch.value) return;
  router.push({
    name: 'search',
    query: {
      q: keyword.value.trim(),
      ...(selectedSources.value.length ? { sources: selectedSources.value.join(',') } : {}),
    },
  });
}

function toggleSource(key: string) {
  const idx = selectedSources.value.indexOf(key);
  if (idx >= 0) {
    selectedSources.value.splice(idx, 1);
  } else {
    selectedSources.value.push(key);
  }
}

function searchHot(word: string) {
  keyword.value = word;
  handleSearch();
}

function goCategory(key: string) {
  router.push({ name: 'search', query: { category: key } });
}
</script>

<template>
  <div class="home">
    <!-- 顶部导航 -->
    <header class="home__nav">
      <div class="container home__nav-inner">
        <router-link to="/" class="home__logo" aria-label="jisou 首页">
          <span class="home__logo-mark" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="home__logo-text">jisou</span>
        </router-link>

        <nav class="home__nav-links">
          <router-link to="/" class="home__nav-link is-active">搜索</router-link>
          <router-link to="/user" class="home__nav-link">我的</router-link>
          <router-link to="/login" class="home__nav-link">登录</router-link>
        </nav>
      </div>
    </header>

    <!-- Hero 区 -->
    <section class="home__hero">
      <div class="container home__hero-inner">
        <!-- 品牌标 -->
        <div class="home__brand animate-fade-up">
          <span class="home__brand-dot" aria-hidden="true" />
          <span class="home__brand-text">聚合搜索引擎 · v0.3</span>
        </div>

        <!-- 主标题 -->
        <h1 class="home__title animate-fade-up" style="animation-delay: 0.05s">
          一个入口，搜遍
          <span class="home__title-accent">网盘</span>
          与
          <span class="home__title-accent">Telegram</span>
        </h1>

        <p class="home__subtitle animate-fade-up" style="animation-delay: 0.1s">
          聚合百度 / 阿里 / 夸克 / 115 与公开 TG 频道，统一去重排序，快速定位你需要的资源
        </p>

        <!-- 搜索框 -->
        <div class="home__search animate-fade-up" style="animation-delay: 0.15s">
          <div class="home__search-box">
            <span class="home__search-icon" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6"/>
                <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </span>
            <input
              v-model="keyword"
              class="home__search-input"
              type="text"
              placeholder="输入关键词，至少 2 个字符"
              aria-label="搜索关键词"
              @keyup.enter="handleSearch"
            />
            <JButton
              type="primary"
              size="large"
              :loading="false"
              :disabled="!canSearch"
              class="home__search-btn"
              @click="handleSearch"
            >
              搜索
            </JButton>
          </div>

          <!-- 来源快选 -->
          <div class="home__sources">
            <span class="home__sources-label">来源</span>
            <button
              v-for="s in sources"
              :key="s.key"
              type="button"
              class="home__source-chip"
              :class="{ 'is-active': selectedSources.includes(s.key) }"
              @click="toggleSource(s.key)"
            >
              {{ s.label }}
            </button>
          </div>
        </div>

        <!-- 热门搜索 -->
        <div class="home__hot animate-fade-up" style="animation-delay: 0.2s">
          <span class="home__hot-label">热门</span>
          <div class="home__hot-list">
            <button
              v-for="(word, i) in hotKeywords"
              :key="word"
              type="button"
              class="home__hot-item"
              :style="{ opacity: 1 - i * 0.04 }"
              @click="searchHot(word)"
            >
              {{ word }}
            </button>
          </div>
        </div>
      </div>

      <!-- 装饰光晕 -->
      <div class="home__hero-glow" aria-hidden="true" />
    </section>

    <!-- 分类入口 -->
    <section class="home__categories">
      <div class="container">
        <div class="home__categories-head">
          <h2 class="home__categories-title">按类型浏览</h2>
          <p class="home__categories-desc">覆盖常见资源类型，快速进入对应索引</p>
        </div>

        <div class="home__category-grid">
          <JCard
            v-for="(cat, i) in categories"
            :key="cat.key"
            hoverable
            padding="loose"
            class="home__category-card animate-fade-up"
            :style="{ animationDelay: `${0.25 + i * 0.05}s` }"
            @click="goCategory(cat.key)"
          >
            <div class="home__category-body">
              <div class="home__category-icon" :class="`home__category-icon--${cat.icon}`">
                <svg v-if="cat.icon === 'pan'" width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M8 14a4 4 0 1 1 0-8 5 5 0 0 1 9.9-1 4 4 0 0 1-.9 9H8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                <svg v-else-if="cat.icon === 'tg'" width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M21 5L3 11l5 2 2 5 3-3 5 4 3-14z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                <svg v-else-if="cat.icon === 'doc'" width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                  <path d="M14 3v5h5M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none">
                  <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
                  <path d="M10 9l5 3-5 3V9z" fill="currentColor"/>
                </svg>
              </div>
              <div class="home__category-meta">
                <h3 class="home__category-name">{{ cat.title }}</h3>
                <p class="home__category-text">{{ cat.desc }}</p>
              </div>
              <div class="home__category-foot">
                <span class="home__category-count">{{ cat.count }} 条</span>
                <span class="home__category-arrow" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              </div>
            </div>
          </JCard>
        </div>
      </div>
    </section>

    <!-- 数据统计条 -->
    <section class="home__stats">
      <div class="container home__stats-inner">
        <div class="home__stat">
          <span class="home__stat-value">30+ 万</span>
          <span class="home__stat-label">已索引资源</span>
        </div>
        <div class="home__stat-divider" aria-hidden="true" />
        <div class="home__stat">
          <span class="home__stat-value">5+</span>
          <span class="home__stat-label">数据源</span>
        </div>
        <div class="home__stat-divider" aria-hidden="true" />
        <div class="home__stat">
          <span class="home__stat-value">5 min</span>
          <span class="home__stat-label">失效检测周期</span>
        </div>
        <div class="home__stat-divider" aria-hidden="true" />
        <div class="home__stat">
          <span class="home__stat-value">240 ms</span>
          <span class="home__stat-label">平均响应</span>
        </div>
      </div>
    </section>

    <!-- 页脚 -->
    <footer class="home__footer">
      <div class="container home__footer-inner">
        <div class="home__footer-brand">
          <span class="home__footer-mark">jisou</span>
          <span class="home__footer-text">聚合搜索引擎</span>
        </div>
        <p class="home__footer-notice">
          仅索引公开分享链接与元数据，不存储任何实体文件。如遇失效或违规资源可发起举报。
        </p>
        <p class="home__footer-copy">© 2026 jisou · 仅供学习研究使用</p>
      </div>
    </footer>
  </div>
</template>

<style lang="scss" scoped>
.home {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: $color-bg-primary;

  // ============ 顶部导航 ============
  &__nav {
    position: sticky;
    top: 0;
    z-index: $z-sticky;
    height: $height-nav;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: none;
    border-bottom: 1px solid $color-divider;
  }

  &__nav-inner {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__logo {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    font-weight: $font-weight-semibold;
    font-size: $font-size-h3;
    letter-spacing: $letter-spacing-tight;
    transition: color $transition-fast;

    &:hover { color: $color-primary; }
  }

  &__logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: $color-primary;
  }

  &__nav-links {
    display: flex;
    align-items: center;
    gap: $spacing-xl;
  }

  &__nav-link {
    position: relative;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    padding: $spacing-sm 0;
    transition: color $transition-fast;

    &:hover { color: $color-primary; }

    &.is-active {
      color: $color-primary;

      &::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -22px;
        height: 2px;
        background: $color-primary;
        border-radius: $radius-pill;
      }
    }
  }

  // ============ Hero 区 ============
  &__hero {
    position: relative;
    padding: $spacing-6xl 0 $spacing-5xl;
    overflow: hidden;
    background: $gradient-hero;
  }

  &__hero-inner {
    position: relative;
    z-index: $z-base;
    text-align: center;
    max-width: 760px;
  }

  &__hero-glow {
    position: absolute;
    top: -120px;
    left: 50%;
    transform: translateX(-50%);
    width: 720px;
    height: 480px;
    background: radial-gradient(
      ellipse at center,
      rgba(30, 91, 184, 0.08) 0%,
      rgba(58, 123, 213, 0.04) 35%,
      transparent 70%
    );
    pointer-events: none;
    z-index: 0;
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-xs $spacing-md;
    margin-bottom: $spacing-xl;
    background: $color-primary-soft;
    border-radius: $radius-pill;
    color: $color-primary;
    font-size: $font-size-small;
    font-weight: $font-weight-medium;
    letter-spacing: $letter-spacing-wide;
  }

  &__brand-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: $color-primary;
    animation: pulse-soft 2.4s ease-in-out infinite;
  }

  &__brand-text {
    font-family: $font-family-mono;
  }

  &__title {
    font-size: $font-size-display;
    font-weight: $font-weight-semibold;
    line-height: $line-height-tight;
    letter-spacing: $letter-spacing-tight;
    color: $color-text-primary;
    margin-bottom: $spacing-base;
  }

  &__title-accent {
    color: $color-primary;
    position: relative;
    display: inline-block;

    &::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 2px;
      height: 8px;
      background: $color-primary-soft;
      border-radius: $radius-pill;
      z-index: -1;
    }
  }

  &__subtitle {
    font-size: $font-size-body-lg;
    color: $color-text-secondary;
    line-height: $line-height-body;
    max-width: 560px;
    margin: 0 auto $spacing-3xl;
  }

  // ============ 搜索框 ============
  &__search {
    max-width: 680px;
    margin: 0 auto;
  }

  &__search-box {
    position: relative;
    display: flex;
    align-items: center;
    height: 56px;
    padding: 0 6px 0 $spacing-lg;
    background: $color-bg-primary;
    border: 1.5px solid $color-border;
    border-radius: $radius-xl;
    box-shadow: 0 1px 2px rgba(30, 91, 184, 0.04);
    transition:
      border-color $transition-fast,
      box-shadow $transition-fast;

    &:focus-within {
      border-color: $color-primary;
      box-shadow: $shadow-focus;
    }
  }

  &__search-icon {
    display: inline-flex;
    color: $color-text-placeholder;
    margin-right: $spacing-sm;
    flex-shrink: 0;
  }

  &__search-input {
    flex: 1;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: $font-size-body-lg;
    color: $color-text-primary;

    &::placeholder { color: $color-text-placeholder; }
  }

  &__search-btn {
    margin-left: $spacing-sm;
    height: 44px;
    padding: 0 $spacing-xl;
    font-weight: $font-weight-medium;
  }

  // ============ 来源快选 ============
  &__sources {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm;
    margin-top: $spacing-base;
    justify-content: center;
  }

  &__sources-label {
    color: $color-text-secondary;
    font-size: $font-size-small;
    margin-right: $spacing-xs;
  }

  &__source-chip {
    display: inline-flex;
    align-items: center;
    height: $height-tag-lg;
    padding: 0 $spacing-md;
    border: 1px solid $color-border;
    border-radius: $radius-pill;
    background: $color-bg-primary;
    color: $color-text-secondary;
    font-size: $font-size-small;
    font-weight: $font-weight-medium;
    transition:
      color $transition-fast,
      border-color $transition-fast,
      background $transition-fast;

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

  // ============ 热门搜索 ============
  &__hot {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: $spacing-sm $spacing-md;
    margin-top: $spacing-3xl;
  }

  &__hot-label {
    color: $color-text-placeholder;
    font-size: $font-size-small;
    letter-spacing: $letter-spacing-wider;
    text-transform: uppercase;
  }

  &__hot-list {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm $spacing-base;
  }

  &__hot-item {
    color: $color-text-secondary;
    font-size: $font-size-small;
    transition: color $transition-fast;

    &:hover {
      color: $color-primary;
    }
  }

  // ============ 分类入口 ============
  &__categories {
    padding: $spacing-5xl 0 $spacing-4xl;
    background: $color-bg-primary;
  }

  &__categories-head {
    text-align: center;
    margin-bottom: $spacing-3xl;
  }

  &__categories-title {
    font-size: $font-size-h2;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__categories-desc {
    color: $color-text-secondary;
    font-size: $font-size-body;
  }

  &__category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: $spacing-lg;

    @media (max-width: $breakpoint-tablet) {
      grid-template-columns: repeat(2, 1fr);
    }

    @media (max-width: $breakpoint-mobile) {
      grid-template-columns: 1fr;
    }
  }

  &__category-card {
    cursor: pointer;
    padding: $spacing-xl !important;
  }

  &__category-body {
    display: flex;
    flex-direction: column;
    height: 100%;
    gap: $spacing-base;
  }

  &__category-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: $radius-lg;
    background: $color-primary-soft;
    color: $color-primary;
    margin-bottom: $spacing-sm;
    transition:
      background $transition-fast,
      transform $transition-fast;

    .home__category-card:hover & {
      background: $color-primary;
      color: $color-text-inverse;
      transform: scale(1.04);
    }

    &--tg { background: $color-info-soft; color: $color-info; }
    &--doc { background: $color-accent-2-soft; color: $color-accent-2; }
    &--media { background: $color-warning-soft; color: $color-warning; }
  }

  &__category-meta {
    flex: 1;
  }

  &__category-name {
    font-size: $font-size-h3;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    margin-bottom: $spacing-xs;
  }

  &__category-text {
    font-size: $font-size-small;
    color: $color-text-secondary;
    line-height: $line-height-body;
  }

  &__category-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: $spacing-md;
    border-top: 1px dashed $color-divider;
  }

  &__category-count {
    font-size: $font-size-small;
    color: $color-text-placeholder;
    font-family: $font-family-mono;
  }

  &__category-arrow {
    display: inline-flex;
    color: $color-text-placeholder;
    transition:
      transform $transition-fast,
      color $transition-fast;

    .home__category-card:hover & {
      transform: translateX(2px);
      color: $color-primary;
    }
  }

  // ============ 数据统计条 ============
  &__stats {
    background: $color-bg-secondary;
    border-top: 1px solid $color-divider;
    border-bottom: 1px solid $color-divider;
    padding: $spacing-2xl 0;
  }

  &__stats-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: $spacing-xl;

    @media (max-width: $breakpoint-mobile) {
      flex-wrap: wrap;
      gap: $spacing-base $spacing-xl;
    }
  }

  &__stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: $spacing-xs;
  }

  &__stat-value {
    font-size: $font-size-h2;
    font-weight: $font-weight-semibold;
    color: $color-primary;
    letter-spacing: $letter-spacing-tight;
    font-family: $font-family-mono;
  }

  &__stat-label {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__stat-divider {
    width: 1px;
    height: 32px;
    background: $color-divider;

    @media (max-width: $breakpoint-mobile) {
      display: none;
    }
  }

  // ============ 页脚 ============
  &__footer {
    margin-top: auto;
    padding: $spacing-3xl 0 $spacing-2xl;
    background: $color-bg-primary;
    border-top: 1px solid $color-divider;
  }

  &__footer-inner {
    text-align: center;
  }

  &__footer-brand {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    margin-bottom: $spacing-sm;
  }

  &__footer-mark {
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    color: $color-primary;
  }

  &__footer-text {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__footer-notice {
    font-size: $font-size-small;
    color: $color-text-placeholder;
    max-width: 520px;
    margin: 0 auto $spacing-sm;
    line-height: $line-height-body;
  }

  &__footer-copy {
    font-size: $font-size-caption;
    color: $color-text-placeholder;
    letter-spacing: $letter-spacing-wide;
  }
}
</style>
