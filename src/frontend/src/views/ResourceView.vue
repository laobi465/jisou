<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '@/api/http';
import type { ApiResponse, ResourceItem } from '@/api/types';
import JButton from '@/components/JButton.vue';
import JCard from '@/components/JCard.vue';
import JSourceTag from '@/components/JSourceTag.vue';
import JStatusBadge from '@/components/JStatusBadge.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const resource = ref<ResourceItem | null>(null);
const error = ref<string>('');
const favorited = ref(false);
const parsing = ref(false);
const parseResult = ref<string>('');

// 文件列表（待接入：M5 阶段从 /api/resource/:hash/files 拉取）
interface FileEntry {
  name: string;
  size: number;
  type: string;
}

const files = ref<FileEntry[]>([
  { name: 'README.md', size: 2_400, type: 'md' },
  { name: 'docs/intro.pdf', size: 1_240_000, type: 'pdf' },
  { name: 'src/index.ts', size: 8_500, type: 'ts' },
  { name: 'assets/cover.png', size: 540_000, type: 'png' },
]);

// 相关资源（待接入：M1 阶段从 /api/resource/:hash/related 拉取）
const related = ref<ResourceItem[]>([]);

const hash = computed(() => (route.params.hash as string) || '');

const sizeText = computed(() => {
  if (!resource.value?.size_bytes) return '—';
  const bytes = resource.value.size_bytes;
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let v = bytes;
  let i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return `${v.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
});

function formatDate(iso?: string): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toISOString().replace('T', ' ').slice(0, 16);
}

async function fetchResource() {
  if (!hash.value) {
    error.value = '资源 hash 缺失';
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    const { data } = await http.get<ApiResponse<ResourceItem>>(`/resource/${hash.value}`);
    if (data.code === 0) {
      resource.value = data.data;
    } else {
      error.value = data.message || '资源不存在或已被删除';
    }
  } catch (e) {
    error.value = '加载失败，请稍后重试';
  } finally {
    loading.value = false;
  }
}

async function fetchRelated() {
  // 待接入：M1 阶段从 /api/resource/:hash/related 拉取
  related.value = [];
}

function openOrigin() {
  if (resource.value?.origin_url) {
    window.open(resource.value.origin_url, '_blank', 'noopener,noreferrer');
  }
}

function copyLink() {
  if (resource.value?.source_url && typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(resource.value.source_url).then(() => {
      // 待接入：使用 toast 提示
    });
  }
}

async function toggleFavorite() {
  // 待接入：M3 阶段接入 POST /api/favorites / DELETE /api/favorites/:id
  favorited.value = !favorited.value;
}

async function triggerParse() {
  if (!resource.value) return;
  parsing.value = true;
  parseResult.value = '';
  try {
    // 待接入：M5 阶段接入 POST /api/resource/:hash/parse
    await new Promise(r => setTimeout(r, 800));
    parseResult.value = '解析功能待接入，M5 阶段提供按来源开关的解析能力';
  } finally {
    parsing.value = false;
  }
}

function report() {
  // 待接入：M3 阶段接入 POST /api/reports
}

function backToSearch() {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push({ name: 'home' });
  }
}

function formatFileSize(bytes: number): string {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  let v = bytes;
  let i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return `${v.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function fileTypeIcon(type: string): string {
  const map: Record<string, string> = {
    pdf: 'pdf', doc: 'doc', docx: 'doc', txt: 'doc',
    md: 'doc',
    mp4: 'media', mkv: 'media', avi: 'media', mov: 'media',
    mp3: 'media', wav: 'media', flac: 'media',
    png: 'image', jpg: 'image', jpeg: 'image', gif: 'image', webp: 'image',
    zip: 'archive', rar: 'archive', '7z': 'archive', tar: 'archive', gz: 'archive',
    ts: 'code', js: 'code', py: 'code', java: 'code', go: 'code', rs: 'code',
  };
  return map[type.toLowerCase()] || 'file';
}

watch(() => route.params.hash, () => {
  fetchResource();
  fetchRelated();
});

onMounted(() => {
  fetchResource();
  fetchRelated();
});
</script>

<template>
  <div class="resource">
    <!-- 顶部导航 -->
    <header class="resource__nav">
      <div class="container resource__nav-inner">
        <router-link to="/" class="resource__logo" aria-label="jisou 首页">
          <span class="resource__logo-mark" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="resource__logo-text">jisou</span>
        </router-link>

        <nav class="resource__nav-links">
          <button type="button" class="resource__nav-back" @click="backToSearch">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M10 4l-4 4 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>返回</span>
          </button>
        </nav>
      </div>
    </header>

    <div class="container resource__body">
      <!-- 加载骨架屏 -->
      <div v-if="loading" class="resource__loading">
        <div class="skeleton resource__skeleton-title" />
        <div class="skeleton resource__skeleton-meta" />
        <div class="skeleton resource__skeleton-content" />
      </div>

      <!-- 错误状态 -->
      <div v-else-if="error" class="resource__error">
        <div class="resource__error-icon" aria-hidden="true">
          <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="22" stroke="currentColor" stroke-width="1.6"/>
            <path d="M20 36L36 20M36 36L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </div>
        <h2 class="resource__error-title">资源加载失败</h2>
        <p class="resource__error-desc">{{ error }}</p>
        <JButton @click="fetchResource">重新加载</JButton>
      </div>

      <!-- 详情主体 -->
      <article v-else-if="resource" class="resource__main animate-fade-up">
        <!-- 失效提示 -->
        <div v-if="resource.status === 'invalid'" class="resource__alert">
          <span class="resource__alert-icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
              <path d="M10 2L1 18h18L10 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
              <path d="M10 8v4M10 15v.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="resource__alert-text">
            该资源链接已失效，可能已被分享者删除。可尝试通过原链接确认或选择其他来源资源。
          </span>
        </div>

        <!-- 资源头部 -->
        <JCard padding="loose" class="resource__header">
          <div class="resource__header-top">
            <div class="resource__header-icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M14 3v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="resource__header-content">
              <h1 class="resource__title">{{ resource.title }}</h1>

              <div class="resource__header-meta">
                <JSourceTag :source="resource.source" />
                <JStatusBadge v-if="resource.status" :status="resource.status" />
                <span class="resource__header-meta-item">
                  <span class="resource__header-meta-label">总大小</span>
                  <span class="resource__header-meta-value">{{ sizeText }}</span>
                </span>
                <span v-if="files.length" class="resource__header-meta-item">
                  <span class="resource__header-meta-label">文件数</span>
                  <span class="resource__header-meta-value">{{ files.length }}</span>
                </span>
                <span class="resource__header-meta-item">
                  <span class="resource__header-meta-label">抓取于</span>
                  <span class="resource__header-meta-value">{{ formatDate(resource.extracted_at) }}</span>
                </span>
                <span v-if="resource.last_checked" class="resource__header-meta-item">
                  <span class="resource__header-meta-label">最后检测</span>
                  <span class="resource__header-meta-value">{{ formatDate(resource.last_checked) }}</span>
                </span>
              </div>
            </div>
          </div>

          <div class="resource__actions">
            <JButton
              v-if="resource.origin_url"
              type="primary"
              size="large"
              @click="openOrigin"
            >
              打开原链接
            </JButton>
            <JButton
              size="large"
              :loading="parsing"
              @click="triggerParse"
            >
              解析下载
            </JButton>
            <JButton
              size="large"
              :ghost="!favorited"
              @click="toggleFavorite"
            >
              {{ favorited ? '已收藏' : '收藏' }}
            </JButton>
            <JButton size="large" @click="copyLink">
              复制链接
            </JButton>
            <JButton
              size="large"
              type="danger"
              ghost
              @click="report"
            >
              举报
            </JButton>
          </div>

          <!-- 解析结果 -->
          <div v-if="parseResult" class="resource__parse-result">
            <span class="resource__parse-result-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 5v3.5M8 11v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </span>
            <span>{{ parseResult }}</span>
          </div>
        </JCard>

        <!-- 文件列表 -->
        <JCard v-if="files.length > 0" padding="default" class="resource__files">
          <div class="resource__files-head">
            <h2 class="resource__files-title">文件列表</h2>
            <span class="resource__files-count">{{ files.length }} 个文件</span>
          </div>

          <div class="resource__file-table">
            <div class="resource__file-row resource__file-row--head">
              <span class="resource__file-name">文件名</span>
              <span class="resource__file-size">大小</span>
              <span class="resource__file-type">类型</span>
            </div>
            <div
              v-for="(f, i) in files"
              :key="i"
              class="resource__file-row"
            >
              <span class="resource__file-name">
                <span class="resource__file-icon" :class="`resource__file-icon--${fileTypeIcon(f.type)}`" aria-hidden="true">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M9 2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V5L9 2z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                    <path d="M9 2v3h3" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                  </svg>
                </span>
                {{ f.name }}
              </span>
              <span class="resource__file-size">{{ formatFileSize(f.size) }}</span>
              <span class="resource__file-type">
                <span class="resource__file-type-tag">{{ f.type.toUpperCase() }}</span>
              </span>
            </div>
          </div>
        </JCard>

        <!-- 源链接信息 -->
        <JCard v-if="resource.source_url" padding="default" class="resource__source-info">
          <h3 class="resource__source-info-title">源链接</h3>
          <div class="resource__source-info-box">
            <span class="resource__source-info-url">{{ resource.source_url }}</span>
            <button type="button" class="resource__source-info-copy" @click="copyLink" aria-label="复制链接">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <rect x="5" y="5" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M3 11V3a1 1 0 0 1 1-1h7" stroke="currentColor" stroke-width="1.4"/>
              </svg>
            </button>
          </div>
          <p class="resource__source-info-tip">
            点击「打开原链接」可直接跳转到该来源的原页面；解析功能由超管后台按来源开关控制。
          </p>
        </JCard>

        <!-- 相关资源 -->
        <section v-if="related.length > 0" class="resource__related">
          <h2 class="resource__related-title">相关资源</h2>
          <div class="resource__related-grid">
            <JCard
              v-for="r in related"
              :key="r.hash"
              hoverable
              padding="default"
              class="resource__related-card"
              @click="router.push({ name: 'resource', params: { hash: r.hash } })"
            >
              <h4 class="resource__related-card-title">{{ r.title }}</h4>
              <div class="resource__related-card-meta">
                <JSourceTag :source="r.source" size="small" />
                <span class="resource__related-card-size">{{ formatFileSize(r.size_bytes || 0) }}</span>
              </div>
            </JCard>
          </div>
        </section>
      </article>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.resource {
  min-height: 100vh;
  background: $color-bg-secondary;

  // ============ 顶部导航 ============
  &__nav {
    position: sticky;
    top: 0;
    z-index: $z-sticky;
    height: $height-nav;
    background: rgba(255, 255, 255, 0.95);
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

    &:hover { color: $color-primary; }
  }

  &__logo-mark {
    display: inline-flex;
    color: $color-primary;
  }

  &__nav-links {
    display: flex;
    align-items: center;
    gap: $spacing-lg;
  }

  &__nav-back {
    display: inline-flex;
    align-items: center;
    gap: $spacing-xs;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    padding: $spacing-sm $spacing-md;
    border-radius: $radius-base;
    transition: all $transition-fast;

    &:hover {
      color: $color-primary;
      background: $color-primary-soft;
    }
  }

  // ============ 主体 ============
  &__body {
    padding: $spacing-xl 0 $spacing-5xl;
    max-width: 920px;
  }

  // ============ 加载状态 ============
  &__loading {
    display: flex;
    flex-direction: column;
    gap: $spacing-base;
    padding: $spacing-2xl;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
  }

  &__skeleton-title {
    height: 28px;
    width: 70%;
    border-radius: $radius-sm;
  }

  &__skeleton-meta {
    height: 14px;
    width: 50%;
    border-radius: $radius-sm;
  }

  &__skeleton-content {
    height: 200px;
    border-radius: $radius-base;
    margin-top: $spacing-base;
  }

  // ============ 错误状态 ============
  &__error {
    text-align: center;
    padding: $spacing-5xl $spacing-base;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
  }

  &__error-icon {
    display: inline-flex;
    color: $color-danger;
    margin-bottom: $spacing-base;
  }

  &__error-title {
    font-size: $font-size-h2;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__error-desc {
    color: $color-text-secondary;
    font-size: $font-size-body;
    margin-bottom: $spacing-lg;
  }

  // ============ 失效提示 ============
  &__alert {
    display: flex;
    align-items: flex-start;
    gap: $spacing-md;
    padding: $spacing-md $spacing-lg;
    margin-bottom: $spacing-base;
    background: $color-warning-soft;
    border-left: 3px solid $color-warning;
    border-radius: $radius-base;
    color: $color-warning;
    font-size: $font-size-small;
    line-height: $line-height-body;
  }

  &__alert-icon {
    display: inline-flex;
    flex-shrink: 0;
    margin-top: 1px;
  }

  &__alert-text {
    flex: 1;
    color: $color-text-primary;
  }

  // ============ 资源头部 ============
  &__header {
    margin-bottom: $spacing-base;
  }

  &__header-top {
    display: flex;
    align-items: flex-start;
    gap: $spacing-lg;
  }

  &__header-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: $color-primary-soft;
    color: $color-primary;
    border-radius: $radius-lg;
    flex-shrink: 0;
  }

  &__header-content {
    flex: 1;
    min-width: 0;
  }

  &__title {
    font-size: $font-size-h1;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    line-height: $line-height-heading;
    letter-spacing: $letter-spacing-tight;
    margin-bottom: $spacing-md;

    @media (max-width: $breakpoint-mobile) {
      font-size: $font-size-h2;
    }
  }

  &__header-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm $spacing-lg;
  }

  &__header-meta-item {
    display: inline-flex;
    align-items: center;
    gap: $spacing-xs;
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__header-meta-label {
    color: $color-text-placeholder;
  }

  &__header-meta-value {
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm;
    margin-top: $spacing-xl;
    padding-top: $spacing-lg;
    border-top: 1px solid $color-divider;
  }

  &__parse-result {
    display: flex;
    align-items: flex-start;
    gap: $spacing-sm;
    margin-top: $spacing-base;
    padding: $spacing-md $spacing-base;
    background: $color-bg-secondary;
    border-radius: $radius-base;
    color: $color-text-secondary;
    font-size: $font-size-small;
    line-height: $line-height-body;
  }

  &__parse-result-icon {
    display: inline-flex;
    color: $color-accent-1;
    flex-shrink: 0;
    margin-top: 1px;
  }

  // ============ 文件列表 ============
  &__files {
    margin-bottom: $spacing-base;
  }

  &__files-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-base;
  }

  &__files-title {
    font-size: $font-size-h3;
    color: $color-text-primary;
  }

  &__files-count {
    font-size: $font-size-small;
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__file-table {
    display: flex;
    flex-direction: column;
    border: 1px solid $color-divider;
    border-radius: $radius-base;
    overflow: hidden;
  }

  &__file-row {
    display: grid;
    grid-template-columns: 1fr 100px 80px;
    align-items: center;
    gap: $spacing-md;
    padding: $spacing-sm $spacing-md;
    font-size: $font-size-small;
    color: $color-text-primary;
    border-bottom: 1px solid $color-divider;
    transition: background $transition-fast;

    &:last-child { border-bottom: none; }

    &:not(.resource__file-row--head):hover {
      background: $color-bg-secondary;
    }

    &--head {
      background: $color-bg-secondary;
      font-weight: $font-weight-medium;
      color: $color-text-secondary;
      font-size: $font-size-caption;
      letter-spacing: $letter-spacing-wider;
      text-transform: uppercase;
    }

    @media (max-width: $breakpoint-mobile) {
      grid-template-columns: 1fr 80px;
      .resource__file-type { display: none; }
    }
  }

  &__file-name {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__file-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: $color-primary-soft;
    color: $color-primary;
    border-radius: $radius-sm;
    flex-shrink: 0;

    &--pdf { background: $color-danger-soft; color: $color-danger; }
    &--doc { background: $color-info-soft; color: $color-info; }
    &--media { background: $color-warning-soft; color: $color-warning; }
    &--image { background: $color-accent-2-soft; color: $color-accent-2; }
    &--archive { background: rgba(107, 114, 128, 0.12); color: #6b7280; }
    &--code { background: $color-primary-soft; color: $color-primary; }
  }

  &__file-size {
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__file-type {
    text-align: right;
  }

  &__file-type-tag {
    display: inline-block;
    padding: 2px $spacing-sm;
    background: $color-bg-tertiary;
    color: $color-text-secondary;
    border-radius: $radius-xs;
    font-size: $font-size-caption;
    font-family: $font-family-mono;
    letter-spacing: $letter-spacing-wide;
  }

  // ============ 源链接信息 ============
  &__source-info {
    margin-bottom: $spacing-base;
  }

  &__source-info-title {
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    margin-bottom: $spacing-md;
    letter-spacing: $letter-spacing-wide;
  }

  &__source-info-box {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-sm $spacing-md;
    background: $color-bg-secondary;
    border: 1px solid $color-divider;
    border-radius: $radius-base;
  }

  &__source-info-url {
    flex: 1;
    color: $color-text-secondary;
    font-family: $font-family-mono;
    font-size: $font-size-small;
    word-break: break-all;
  }

  &__source-info-copy {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    color: $color-text-secondary;
    border-radius: $radius-sm;
    transition: all $transition-fast;
    flex-shrink: 0;

    &:hover {
      background: $color-primary-soft;
      color: $color-primary;
    }
  }

  &__source-info-tip {
    margin-top: $spacing-sm;
    font-size: $font-size-small;
    color: $color-text-placeholder;
    line-height: $line-height-body;
  }

  // ============ 相关资源 ============
  &__related {
    margin-top: $spacing-2xl;
  }

  &__related-title {
    font-size: $font-size-h2;
    color: $color-text-primary;
    margin-bottom: $spacing-base;
  }

  &__related-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: $spacing-base;

    @media (max-width: $breakpoint-mobile) {
      grid-template-columns: 1fr;
    }
  }

  &__related-card {
    cursor: pointer;
  }

  &__related-card-title {
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
    line-height: $line-height-subheading;
    margin-bottom: $spacing-sm;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__related-card-meta {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
  }

  &__related-card-size {
    font-size: $font-size-small;
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }
}
</style>
