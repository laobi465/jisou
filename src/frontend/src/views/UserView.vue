<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import type { ResourceItem } from '@/api/types';
import JButton from '@/components/JButton.vue';
import JCard from '@/components/JCard.vue';
import JSourceTag from '@/components/JSourceTag.vue';
import JStatusBadge from '@/components/JStatusBadge.vue';
import JPagination from '@/components/JPagination.vue';

const router = useRouter();

type Tab = 'favorites' | 'history' | 'reports' | 'profile';

const activeTab = ref<Tab>('favorites');
const loading = ref(false);
const page = ref(1);
const pageSize = ref(10);

interface FavoriteItem extends ResourceItem {
  favorite_id: number;
  favorite_at: string;
}

interface HistoryItem {
  id: number;
  keyword: string;
  sources: string[];
  result_count: number;
  searched_at: string;
}

interface ReportItem {
  id: number;
  resource_hash: string;
  resource_title: string;
  reason: string;
  status: 'pending' | 'resolved' | 'rejected';
  created_at: string;
  reply?: string;
}

const favorites = ref<FavoriteItem[]>([]);
const history = ref<HistoryItem[]>([]);
const reports = ref<ReportItem[]>([]);
const total = ref(0);

// 用户信息（待接入：M3 阶段从 /api/user/profile 拉取）
const profile = ref({
  email: 'user@example.com',
  nickname: 'jisou 用户',
  created_at: '2026-07-01T10:00:00Z',
  favorites_count: 24,
  history_count: 168,
  reports_count: 3,
});

const tabs = [
  { key: 'favorites' as Tab, label: '收藏', icon: 'star' },
  { key: 'history' as Tab, label: '历史', icon: 'clock' },
  { key: 'reports' as Tab, label: '举报', icon: 'flag' },
  { key: 'profile' as Tab, label: '设置', icon: 'cog' },
];

const reasonMap: Record<string, string> = {
  invalid: '链接已失效',
  copyright: '版权侵权',
  illegal: '违法内容',
  spam: '垃圾信息',
  other: '其他原因',
};

const statusMap: Record<string, { label: string; type: 'active' | 'invalid' | 'unchecked' }> = {
  pending: { label: '待处理', type: 'unchecked' },
  resolved: { label: '已处理', type: 'active' },
  rejected: { label: '已驳回', type: 'invalid' },
};

async function fetchData() {
  loading.value = true;
  try {
    // 待接入：M3 阶段从对应接口拉取数据
    // const endpoint = `/user/${activeTab.value}`;
    // const { data } = await http.get<ApiResponse<...>>(endpoint, { params: { page, size: pageSize } });
    await new Promise(r => setTimeout(r, 300));

    // 模拟空数据
    if (activeTab.value === 'favorites') {
      favorites.value = [];
      total.value = 0;
    } else if (activeTab.value === 'history') {
      history.value = [];
      total.value = 0;
    } else if (activeTab.value === 'reports') {
      reports.value = [];
      total.value = 0;
    }
  } finally {
    loading.value = false;
  }
}

function switchTab(t: Tab) {
  if (activeTab.value === t) return;
  activeTab.value = t;
  page.value = 1;
  fetchData();
}

function onPageChange(p: number) {
  page.value = p;
  fetchData();
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

function goResource(hash: string) {
  router.push({ name: 'resource', params: { hash } });
}

function removeFavorite(item: FavoriteItem) {
  // 待接入：DELETE /api/favorites/:id
  favorites.value = favorites.value.filter(f => f.favorite_id !== item.favorite_id);
}

function removeHistory(item: HistoryItem) {
  // 待接入：DELETE /api/history/:id
  history.value = history.value.filter(h => h.id !== item.id);
}

function clearAllHistory() {
  // 待接入：DELETE /api/history
  history.value = [];
  total.value = 0;
}

function searchAgain(keyword: string) {
  router.push({ name: 'search', query: { q: keyword } });
}

function formatDate(iso: string): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toISOString().replace('T', ' ').slice(0, 16);
}

function formatSize(bytes: number | null): string {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let v = bytes;
  let i = 0;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return `${v.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

function logout() {
  // 待接入：POST /api/auth/logout + 清除本地 token
  router.push('/');
}

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="user">
    <!-- 顶部导航 -->
    <header class="user__nav">
      <div class="container user__nav-inner">
        <router-link to="/" class="user__logo" aria-label="jisou 首页">
          <span class="user__logo-mark" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="user__logo-text">jisou</span>
        </router-link>

        <nav class="user__nav-links">
          <router-link to="/" class="user__nav-link">搜索</router-link>
          <router-link to="/user" class="user__nav-link is-active">我的</router-link>
        </nav>
      </div>
    </header>

    <div class="container user__body">
      <!-- 用户信息头部 -->
      <JCard padding="loose" class="user__header animate-fade-up">
        <div class="user__header-row">
          <div class="user__avatar" aria-hidden="true">
            <span class="user__avatar-text">{{ profile.nickname.slice(0, 1).toUpperCase() }}</span>
          </div>
          <div class="user__header-info">
            <h1 class="user__name">{{ profile.nickname }}</h1>
            <p class="user__email">{{ profile.email }}</p>
            <p class="user__joined">注册于 {{ formatDate(profile.created_at) }}</p>
          </div>
          <div class="user__header-stats">
            <div class="user__stat">
              <span class="user__stat-value">{{ profile.favorites_count }}</span>
              <span class="user__stat-label">收藏</span>
            </div>
            <div class="user__stat-divider" aria-hidden="true" />
            <div class="user__stat">
              <span class="user__stat-value">{{ profile.history_count }}</span>
              <span class="user__stat-label">历史</span>
            </div>
            <div class="user__stat-divider" aria-hidden="true" />
            <div class="user__stat">
              <span class="user__stat-value">{{ profile.reports_count }}</span>
              <span class="user__stat-label">举报</span>
            </div>
          </div>
        </div>
      </JCard>

      <div class="user__layout">
        <!-- 左侧 Tab 切换 -->
        <aside class="user__tabs">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            class="user__tab"
            :class="{ 'is-active': activeTab === t.key }"
            @click="switchTab(t.key)"
          >
            <span class="user__tab-icon" aria-hidden="true">
              <svg v-if="t.icon === 'star'" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 1.5l2 4 4.5.5-3.3 3 .9 4.5L8 11.5 3.9 13.5l.9-4.5L1.5 6 6 5.5l2-4z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              </svg>
              <svg v-else-if="t.icon === 'clock'" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
              <svg v-else-if="t.icon === 'flag'" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 14V2M3 3h9l-2 2 2 2H3" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              </svg>
              <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 1.5v2M8 12.5v2M14.5 8h-2M3.5 8h-2M12.6 3.4l-1.4 1.4M4.8 11.2l-1.4 1.4M12.6 12.6l-1.4-1.4M4.8 4.8L3.4 3.4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="user__tab-text">{{ t.label }}</span>
          </button>

          <div class="user__tabs-foot">
            <button type="button" class="user__logout" @click="logout">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M10 11l3-3-3-3M13 8H6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>退出登录</span>
            </button>
          </div>
        </aside>

        <!-- 右侧内容 -->
        <main class="user__main">
          <!-- 收藏列表 -->
          <div v-if="activeTab === 'favorites'" class="user__panel">
            <div class="user__panel-head">
              <h2 class="user__panel-title">我的收藏</h2>
              <span class="user__panel-count">共 {{ total }} 条</span>
            </div>

            <div v-if="loading" class="user__skeletons">
              <div v-for="i in 4" :key="i" class="user__skeleton">
                <div class="skeleton user__skeleton-icon" />
                <div class="user__skeleton-body">
                  <div class="skeleton user__skeleton-title" />
                  <div class="skeleton user__skeleton-meta" />
                </div>
              </div>
            </div>

            <div v-else-if="favorites.length === 0" class="user__empty">
              <div class="user__empty-icon" aria-hidden="true">
                <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                  <path d="M28 12l4.5 9 10 1.5-7.5 7 1.8 10L28 44l-9 5 1.8-10-7.5-7 10-1.5L28 12z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
              </div>
              <h3 class="user__empty-title">还没有收藏</h3>
              <p class="user__empty-desc">在搜索结果中点击「收藏」按钮，资源会保存在这里</p>
              <JButton type="primary" @click="router.push('/')">去搜索</JButton>
            </div>

            <ul v-else class="user__list">
              <li v-for="f in favorites" :key="f.favorite_id" class="user__list-item">
                <JCard hoverable padding="default">
                  <div class="user__list-row">
                    <div class="user__list-icon" aria-hidden="true">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M14 3v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                      </svg>
                    </div>
                    <div class="user__list-content">
                      <a class="user__list-title" @click="goResource(f.hash)">{{ f.title }}</a>
                      <div class="user__list-meta">
                        <JSourceTag :source="f.source" size="small" />
                        <JStatusBadge v-if="f.status" :status="f.status" />
                        <span class="user__list-size">{{ formatSize(f.size_bytes) }}</span>
                        <span class="user__list-date">收藏于 {{ formatDate(f.favorite_at) }}</span>
                      </div>
                    </div>
                    <div class="user__list-actions">
                      <JButton size="small" @click="goResource(f.hash)">查看</JButton>
                      <JButton size="small" type="danger" ghost @click="removeFavorite(f)">取消收藏</JButton>
                    </div>
                  </div>
                </JCard>
              </li>
            </ul>

            <div v-if="total > pageSize" class="user__pagination">
              <JPagination :page="page" :total="total" :size="pageSize" @change="onPageChange" />
            </div>
          </div>

          <!-- 搜索历史 -->
          <div v-else-if="activeTab === 'history'" class="user__panel">
            <div class="user__panel-head">
              <h2 class="user__panel-title">搜索历史</h2>
              <div class="user__panel-actions">
                <span class="user__panel-count">共 {{ total }} 条</span>
                <JButton v-if="history.length > 0" size="small" type="danger" ghost @click="clearAllHistory">清空全部</JButton>
              </div>
            </div>

            <div v-if="loading" class="user__skeletons">
              <div v-for="i in 4" :key="i" class="user__skeleton">
                <div class="skeleton user__skeleton-icon" />
                <div class="user__skeleton-body">
                  <div class="skeleton user__skeleton-title" />
                  <div class="skeleton user__skeleton-meta" />
                </div>
              </div>
            </div>

            <div v-else-if="history.length === 0" class="user__empty">
              <div class="user__empty-icon" aria-hidden="true">
                <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                  <circle cx="28" cy="28" r="20" stroke="currentColor" stroke-width="1.6"/>
                  <path d="M28 16v12l8 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
              </div>
              <h3 class="user__empty-title">暂无搜索历史</h3>
              <p class="user__empty-desc">你的搜索关键词会自动记录在这里，方便快速再次搜索</p>
              <JButton type="primary" @click="router.push('/')">开始搜索</JButton>
            </div>

            <ul v-else class="user__history-list">
              <li v-for="h in history" :key="h.id" class="user__history-item">
                <button type="button" class="user__history-keyword" @click="searchAgain(h.keyword)">
                  <span class="user__history-icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                      <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.4"/>
                      <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                  </span>
                  <span class="user__history-text">{{ h.keyword }}</span>
                </button>
                <div class="user__history-meta">
                  <span v-if="h.sources.length" class="user__history-sources">{{ h.sources.join('、') }}</span>
                  <span class="user__history-result">{{ h.result_count }} 条结果</span>
                  <span class="user__history-date">{{ formatDate(h.searched_at) }}</span>
                </div>
                <button type="button" class="user__history-remove" aria-label="删除此记录" @click="removeHistory(h)">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  </svg>
                </button>
              </li>
            </ul>

            <div v-if="total > pageSize" class="user__pagination">
              <JPagination :page="page" :total="total" :size="pageSize" @change="onPageChange" />
            </div>
          </div>

          <!-- 我的举报 -->
          <div v-else-if="activeTab === 'reports'" class="user__panel">
            <div class="user__panel-head">
              <h2 class="user__panel-title">我的举报</h2>
              <span class="user__panel-count">共 {{ total }} 条</span>
            </div>

            <div v-if="loading" class="user__skeletons">
              <div v-for="i in 4" :key="i" class="user__skeleton">
                <div class="skeleton user__skeleton-icon" />
                <div class="user__skeleton-body">
                  <div class="skeleton user__skeleton-title" />
                  <div class="skeleton user__skeleton-meta" />
                </div>
              </div>
            </div>

            <div v-else-if="reports.length === 0" class="user__empty">
              <div class="user__empty-icon" aria-hidden="true">
                <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                  <path d="M14 42V14M14 16h22l-4 4 4 4H14" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
              </div>
              <h3 class="user__empty-title">暂无举报记录</h3>
              <p class="user__empty-desc">遇到失效或违规资源时，可在详情页点击「举报」提交反馈</p>
            </div>

            <ul v-else class="user__list">
              <li v-for="r in reports" :key="r.id" class="user__list-item">
                <JCard padding="default">
                  <div class="user__report">
                    <div class="user__report-head">
                      <JStatusBadge :status="statusMap[r.status].type" />
                      <span class="user__report-reason">{{ reasonMap[r.reason] || r.reason }}</span>
                      <span class="user__report-date">{{ formatDate(r.created_at) }}</span>
                    </div>
                    <a class="user__report-title" @click="goResource(r.resource_hash)">{{ r.resource_title }}</a>
                    <div v-if="r.reply" class="user__report-reply">
                      <span class="user__report-reply-label">管理员回复：</span>
                      <span class="user__report-reply-text">{{ r.reply }}</span>
                    </div>
                  </div>
                </JCard>
              </li>
            </ul>

            <div v-if="total > pageSize" class="user__pagination">
              <JPagination :page="page" :total="total" :size="pageSize" @change="onPageChange" />
            </div>
          </div>

          <!-- 个人设置 -->
          <div v-else-if="activeTab === 'profile'" class="user__panel">
            <div class="user__panel-head">
              <h2 class="user__panel-title">个人设置</h2>
            </div>

            <JCard padding="loose" class="user__profile-card">
              <h3 class="user__profile-section">账号信息</h3>
              <div class="user__profile-row">
                <span class="user__profile-label">昵称</span>
                <input class="user__profile-input" :value="profile.nickname" />
              </div>
              <div class="user__profile-row">
                <span class="user__profile-label">邮箱</span>
                <input class="user__profile-input" :value="profile.email" disabled />
              </div>
              <div class="user__profile-row">
                <span class="user__profile-label">注册时间</span>
                <span class="user__profile-value">{{ formatDate(profile.created_at) }}</span>
              </div>

              <div class="user__profile-divider" />

              <h3 class="user__profile-section">修改密码</h3>
              <div class="user__profile-row">
                <span class="user__profile-label">当前密码</span>
                <input class="user__profile-input" type="password" placeholder="请输入当前密码" />
              </div>
              <div class="user__profile-row">
                <span class="user__profile-label">新密码</span>
                <input class="user__profile-input" type="password" placeholder="至少 8 位字符" />
              </div>
              <div class="user__profile-row">
                <span class="user__profile-label">确认新密码</span>
                <input class="user__profile-input" type="password" placeholder="再次输入新密码" />
              </div>

              <div class="user__profile-actions">
                <JButton type="primary">保存修改</JButton>
                <JButton type="danger" ghost>注销账号</JButton>
              </div>
            </JCard>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.user {
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
    gap: $spacing-xl;
  }

  &__nav-link {
    position: relative;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
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

  // ============ 主体 ============
  &__body {
    padding: $spacing-xl 0 $spacing-5xl;
  }

  // ============ 用户头部 ============
  &__header {
    margin-bottom: $spacing-xl;
  }

  &__header-row {
    display: flex;
    align-items: center;
    gap: $spacing-xl;

    @media (max-width: $breakpoint-mobile) {
      flex-direction: column;
      align-items: flex-start;
      gap: $spacing-base;
    }
  }

  &__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: $gradient-hero;
    border: 1px solid $color-primary;
    color: $color-primary;
    font-size: $font-size-h1;
    font-weight: $font-weight-semibold;
    flex-shrink: 0;
  }

  &__avatar-text {
    font-family: $font-family-mono;
    letter-spacing: $letter-spacing-tight;
  }

  &__header-info {
    flex: 1;
    min-width: 0;
  }

  &__name {
    font-size: $font-size-h1;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-tight;
    margin-bottom: $spacing-xs;
  }

  &__email {
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-family: $font-family-mono;
    margin-bottom: $spacing-xs;
  }

  &__joined {
    color: $color-text-placeholder;
    font-size: $font-size-small;
  }

  &__header-stats {
    display: flex;
    align-items: center;
    gap: $spacing-xl;
    padding: $spacing-md $spacing-xl;
    background: $color-bg-secondary;
    border-radius: $radius-base;

    @media (max-width: $breakpoint-mobile) {
      width: 100%;
      justify-content: space-around;
      gap: $spacing-base;
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
    font-family: $font-family-mono;
    letter-spacing: $letter-spacing-tight;
  }

  &__stat-label {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__stat-divider {
    width: 1px;
    height: 28px;
    background: $color-divider;

    @media (max-width: $breakpoint-mobile) {
      display: none;
    }
  }

  // ============ 布局 ============
  &__layout {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: $spacing-xl;
    align-items: start;

    @media (max-width: $breakpoint-tablet) {
      grid-template-columns: 1fr;
    }
  }

  // ============ 左侧 Tab ============
  &__tabs {
    position: sticky;
    top: #{$height-nav + $spacing-xl};
    display: flex;
    flex-direction: column;
    gap: $spacing-xs;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-lg;
    padding: $spacing-sm;

    @media (max-width: $breakpoint-tablet) {
      position: static;
      flex-direction: row;
      overflow-x: auto;
    }
  }

  &__tab {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-md $spacing-base;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    border-radius: $radius-base;
    transition: all $transition-fast;
    text-align: left;
    white-space: nowrap;

    &:hover {
      background: $color-bg-secondary;
      color: $color-text-primary;
    }

    &.is-active {
      background: $color-primary-soft;
      color: $color-primary;
    }
  }

  &__tab-icon {
    display: inline-flex;
    flex-shrink: 0;
  }

  &__tabs-foot {
    margin-top: auto;
    padding-top: $spacing-md;
    border-top: 1px dashed $color-divider;

    @media (max-width: $breakpoint-tablet) {
      margin-top: 0;
      padding-top: 0;
      border-top: none;
      border-left: 1px dashed $color-divider;
      padding-left: $spacing-sm;
    }
  }

  &__logout {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    width: 100%;
    padding: $spacing-md $spacing-base;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    border-radius: $radius-base;
    transition: all $transition-fast;

    &:hover {
      background: $color-danger-soft;
      color: $color-danger;
    }
  }

  // ============ 右侧主区 ============
  &__main {
    min-width: 0;
  }

  &__panel {
    display: flex;
    flex-direction: column;
  }

  &__panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-base;
  }

  &__panel-title {
    font-size: $font-size-h2;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-tight;
  }

  &__panel-count {
    font-size: $font-size-small;
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__panel-actions {
    display: flex;
    align-items: center;
    gap: $spacing-base;
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

  // ============ 收藏列表 ============
  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
  }

  &__list-item {
    width: 100%;
  }

  &__list-row {
    display: flex;
    align-items: flex-start;
    gap: $spacing-base;
  }

  &__list-icon {
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

  &__list-content {
    flex: 1;
    min-width: 0;
  }

  &__list-title {
    display: block;
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    line-height: $line-height-subheading;
    margin-bottom: $spacing-sm;
    cursor: pointer;
    transition: color $transition-fast;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    &:hover { color: $color-primary; }
  }

  &__list-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: $spacing-sm $spacing-md;
  }

  &__list-size,
  &__list-date {
    font-size: $font-size-small;
    color: $color-text-secondary;
    font-family: $font-family-mono;
  }

  &__list-actions {
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
    flex-shrink: 0;
  }

  // ============ 搜索历史 ============
  &__history-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: $spacing-xs;
  }

  &__history-item {
    display: flex;
    align-items: center;
    gap: $spacing-base;
    padding: $spacing-md $spacing-lg;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-base;
    transition: border-color $transition-fast;

    &:hover {
      border-color: $color-primary;
    }
  }

  &__history-keyword {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    flex: 1;
    min-width: 0;
    text-align: left;

    &:hover .user__history-text { color: $color-primary; }
  }

  &__history-icon {
    display: inline-flex;
    color: $color-text-placeholder;
    flex-shrink: 0;
  }

  &__history-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: color $transition-fast;
  }

  &__history-meta {
    display: flex;
    align-items: center;
    gap: $spacing-md;
    font-size: $font-size-small;
    color: $color-text-secondary;
    flex-shrink: 0;
  }

  &__history-sources,
  &__history-result,
  &__history-date {
    font-family: $font-family-mono;
  }

  &__history-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    color: $color-text-placeholder;
    border-radius: $radius-sm;
    flex-shrink: 0;
    transition: all $transition-fast;

    &:hover {
      background: $color-danger-soft;
      color: $color-danger;
    }
  }

  // ============ 举报列表 ============
  &__report {
    display: flex;
    flex-direction: column;
    gap: $spacing-sm;
  }

  &__report-head {
    display: flex;
    align-items: center;
    gap: $spacing-md;
    flex-wrap: wrap;
  }

  &__report-reason {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__report-date {
    font-size: $font-size-small;
    color: $color-text-placeholder;
    font-family: $font-family-mono;
    margin-left: auto;
  }

  &__report-title {
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
    cursor: pointer;
    transition: color $transition-fast;

    &:hover { color: $color-primary; }
  }

  &__report-reply {
    padding: $spacing-sm $spacing-md;
    background: $color-bg-secondary;
    border-left: 2px solid $color-accent-2;
    border-radius: $radius-sm;
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__report-reply-label {
    font-weight: $font-weight-medium;
    color: $color-accent-2;
  }

  // ============ 个人设置 ============
  &__profile-card {
    padding: $spacing-xl;
  }

  &__profile-section {
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    margin-bottom: $spacing-base;
    padding-bottom: $spacing-sm;
    border-bottom: 1px solid $color-divider;
  }

  &__profile-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    align-items: center;
    gap: $spacing-md;
    padding: $spacing-sm 0;

    @media (max-width: $breakpoint-mobile) {
      grid-template-columns: 1fr;
      gap: $spacing-xs;
    }
  }

  &__profile-label {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &__profile-value {
    font-size: $font-size-body;
    color: $color-text-primary;
    font-family: $font-family-mono;
  }

  &__profile-input {
    height: $height-input;
    padding: 0 $spacing-md;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-base;
    font-size: $font-size-body;
    color: $color-text-primary;
    transition: border-color $transition-fast, box-shadow $transition-fast;

    &:focus {
      outline: none;
      border-color: $color-primary;
      box-shadow: $shadow-focus;
    }

    &:disabled {
      background: $color-bg-secondary;
      color: $color-text-secondary;
      cursor: not-allowed;
    }
  }

  &__profile-divider {
    height: 1px;
    background: $gradient-divider;
    margin: $spacing-xl 0;
  }

  &__profile-actions {
    display: flex;
    gap: $spacing-sm;
    margin-top: $spacing-xl;
  }

  // ============ 分页 ============
  &__pagination {
    display: flex;
    justify-content: center;
    margin-top: $spacing-2xl;
  }
}
</style>
