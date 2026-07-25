<script setup lang="ts">
// 超管后台布局
// 设计依据：docs/UI-DESIGN.md §六（后台侧边栏 240px）+ §八（后台页面清单）
// 现代简约 · 藏蓝专业色系 · 线性 SVG 图标
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { RouterView, RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

interface NavItem {
  path: string;
  name: string;
  label: string;
  icon: 'dashboard' | 'provider' | 'crawl' | 'resource' | 'blacklist' | 'user';
  desc: string;
}

const navItems: NavItem[] = [
  { path: '/admin', name: 'admin-dashboard', label: '仪表盘', icon: 'dashboard', desc: '资源与抓取总览' },
  { path: '/admin/providers', name: 'admin-providers', label: 'Provider 管理', icon: 'provider', desc: '数据源接入与状态' },
  { path: '/admin/crawl', name: 'admin-crawl', label: '爬虫管理', icon: 'crawl', desc: '任务调度与执行' },
  { path: '/admin/resources', name: 'admin-resources', label: '资源审核', icon: 'resource', desc: '资源状态与举报处理' },
  { path: '/admin/blacklist', name: 'admin-blacklist', label: '黑名单', icon: 'blacklist', desc: '关键词 / URL / hash 拦截' },
  { path: '/admin/users', name: 'admin-users', label: '用户管理', icon: 'user', desc: '用户与角色' },
];

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const currentNav = computed(() => {
  return navItems.find(n => n.name === route.name) || navItems[0];
});

const pageTitle = computed(() => currentNav.value.label);
const pageDesc = computed(() => currentNav.value.desc);

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
}

function toggleMobileSidebar() {
  mobileSidebarOpen.value = !mobileSidebarOpen.value;
}

function closeMobileSidebar() {
  mobileSidebarOpen.value = false;
}

function logout() {
  // 待接入：M3 阶段调用 /api/auth/logout 并清除 token
  router.push('/login');
}

function goHome() {
  router.push('/');
}

// ESC 关闭移动端侧边栏
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && mobileSidebarOpen.value) {
    mobileSidebarOpen.value = false;
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown);
});

// 管理员信息（待接入：从 /api/admin/profile 拉取）
const admin = ref({
  nickname: 'admin',
  email: 'admin@jisou.local',
  role: '超级管理员',
});
</script>

<template>
  <div class="admin" :class="{ 'is-collapsed': sidebarCollapsed }">
    <!-- 移动端遮罩 -->
    <transition name="admin-overlay">
      <div
        v-if="mobileSidebarOpen"
        class="admin__overlay"
        @click="closeMobileSidebar"
      />
    </transition>

    <!-- 侧边栏 -->
    <aside
      class="admin__sidebar"
      :class="{ 'is-open': mobileSidebarOpen }"
    >
      <!-- 品牌 -->
      <div class="admin__brand">
        <router-link to="/admin" class="admin__brand-link" aria-label="jisou 后台首页">
          <span class="admin__brand-mark" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span v-if="!sidebarCollapsed" class="admin__brand-text">
            <span class="admin__brand-name">jisou</span>
            <span class="admin__brand-tag">控制台</span>
          </span>
        </router-link>
        <button
          type="button"
          class="admin__collapse"
          :aria-label="sidebarCollapsed ? '展开侧边栏' : '收起侧边栏'"
          @click="toggleSidebar"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M10 4l-4 4 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>

      <!-- 导航 -->
      <nav class="admin__nav" aria-label="后台导航">
        <p v-if="!sidebarCollapsed" class="admin__nav-title">主导航</p>
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="admin__nav-item"
          :class="{ 'is-active': currentNav.name === item.name }"
          :title="sidebarCollapsed ? item.label : undefined"
          @click="closeMobileSidebar"
        >
          <span class="admin__nav-icon" aria-hidden="true">
            <!-- dashboard -->
            <svg v-if="item.icon === 'dashboard'" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2" y="2" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/>
              <rect x="10" y="2" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/>
              <rect x="2" y="10" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/>
              <rect x="10" y="10" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/>
            </svg>
            <!-- provider -->
            <svg v-else-if="item.icon === 'provider'" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="9" r="6.5" stroke="currentColor" stroke-width="1.4"/>
              <path d="M9 2.5v13M2.5 9h13M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="1.2"/>
            </svg>
            <!-- crawl -->
            <svg v-else-if="item.icon === 'crawl'" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.4"/>
              <path d="M9 1v2M9 15v2M1 9h2M15 9h2M3.3 3.3l1.4 1.4M13.3 13.3l1.4 1.4M3.3 14.7l1.4-1.4M13.3 4.7l1.4-1.4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <!-- resource -->
            <svg v-else-if="item.icon === 'resource'" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M11 2H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6l-3-4z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              <path d="M11 2v4h3M6 10h6M6 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <!-- blacklist -->
            <svg v-else-if="item.icon === 'blacklist'" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="9" r="6.5" stroke="currentColor" stroke-width="1.4"/>
              <path d="M4.4 4.4l9.2 9.2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <!-- user -->
            <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.4"/>
              <path d="M3 16c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </span>
          <span v-if="!sidebarCollapsed" class="admin__nav-text">
            <span class="admin__nav-label">{{ item.label }}</span>
            <span class="admin__nav-desc">{{ item.desc }}</span>
          </span>
          <span v-if="currentNav.name === item.name" class="admin__nav-indicator" aria-hidden="true" />
        </RouterLink>
      </nav>

      <!-- 侧边栏底部：返回前台 -->
      <div class="admin__sidebar-foot">
        <button
          type="button"
          class="admin__foot-btn"
          :title="sidebarCollapsed ? '返回前台' : undefined"
          @click="goHome"
        >
          <span class="admin__foot-icon" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M7 3L2 8l5 5M2 8h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span v-if="!sidebarCollapsed">返回前台</span>
        </button>
      </div>
    </aside>

    <!-- 主区域 -->
    <div class="admin__body">
      <!-- 顶栏 -->
      <header class="admin__topbar">
        <div class="admin__topbar-left">
          <button
            type="button"
            class="admin__menu-btn"
            aria-label="打开导航"
            @click="toggleMobileSidebar"
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </button>

          <div class="admin__page-head">
            <nav class="admin__breadcrumb" aria-label="面包屑">
              <span class="admin__breadcrumb-item">控制台</span>
              <span class="admin__breadcrumb-sep" aria-hidden="true">/</span>
              <span class="admin__breadcrumb-item is-current">{{ pageTitle }}</span>
            </nav>
            <h1 class="admin__page-title">{{ pageTitle }}</h1>
            <p class="admin__page-desc">{{ pageDesc }}</p>
          </div>
        </div>

        <div class="admin__topbar-right">
          <!-- 系统状态指示 -->
          <div class="admin__status" title="系统状态">
            <span class="admin__status-dot" aria-hidden="true" />
            <span class="admin__status-text">系统正常</span>
          </div>

          <!-- 管理员 -->
          <div class="admin__user">
            <div class="admin__user-info">
              <span class="admin__user-name">{{ admin.nickname }}</span>
              <span class="admin__user-role">{{ admin.role }}</span>
            </div>
            <div class="admin__user-avatar" aria-hidden="true">
              <span>{{ admin.nickname.slice(0, 1).toUpperCase() }}</span>
            </div>
            <button
              type="button"
              class="admin__user-logout"
              aria-label="退出登录"
              title="退出登录"
              @click="logout"
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M10 11l3-3-3-3M13 8H6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      </header>

      <!-- 内容区 -->
      <main class="admin__content">
        <RouterView />
      </main>

      <!-- 页脚 -->
      <footer class="admin__footer">
        <span>jisou Console · v0.3</span>
        <span class="admin__footer-divider" aria-hidden="true">·</span>
        <span>仅用于运维管理，所有操作均记录审计日志</span>
      </footer>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.admin {
  display: flex;
  min-height: 100vh;
  background: $color-bg-secondary;
  --admin-sidebar-width: #{$sidebar-width-admin};

  &.is-collapsed {
    --admin-sidebar-width: 72px;
  }
}

// =====================================================================
// 侧边栏
// =====================================================================
.admin__sidebar {
  width: var(--admin-sidebar-width);
  background: $color-bg-primary;
  border-right: 1px solid $color-border;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: $z-fixed;
  transition: width $transition-base;

  .is-collapsed & {
    width: 72px;
  }
}

.admin__brand {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-lg $spacing-base;
  border-bottom: 1px solid $color-divider;
  min-height: $height-nav;

  &-link {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    text-decoration: none;
    flex: 1;
    min-width: 0;
  }

  &-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: $color-primary;
    flex-shrink: 0;
  }

  &-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    overflow: hidden;
  }

  &-name {
    font-size: $font-size-h3;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-tight;
  }

  &-tag {
    font-size: $font-size-caption;
    color: $color-text-secondary;
    letter-spacing: $letter-spacing-wider;
    text-transform: uppercase;
    margin-top: 2px;
  }
}

.admin__collapse {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: $color-text-secondary;
  border-radius: $radius-sm;
  cursor: pointer;
  transition: all $transition-fast;
  flex-shrink: 0;

  &:hover {
    background: $color-bg-tertiary;
    color: $color-text-primary;
  }

  .is-collapsed & svg {
    transform: rotate(180deg);
  }

  svg {
    transition: transform $transition-base;
  }
}

.admin__nav {
  flex: 1;
  padding: $spacing-base $spacing-sm;
  overflow-y: auto;

  &-title {
    font-size: $font-size-caption;
    color: $color-text-placeholder;
    text-transform: uppercase;
    letter-spacing: $letter-spacing-wider;
    padding: $spacing-sm $spacing-base;
    margin: $spacing-sm 0;
  }
}

.admin__nav-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-sm $spacing-base;
  margin-bottom: 2px;
  color: $color-text-secondary;
  text-decoration: none;
  border-radius: $radius-base;
  transition: all $transition-fast;
  min-height: 44px;

  .is-collapsed & {
    justify-content: center;
    padding: $spacing-sm;
  }

  &:hover {
    background: $color-bg-tertiary;
    color: $color-text-primary;
  }

  &.is-active {
    background: $color-primary-soft;
    color: $color-primary;

    .admin__nav-indicator {
      opacity: 1;
    }
  }
}

.admin__nav-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
}

.admin__nav-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
  overflow: hidden;
  flex: 1;
  min-width: 0;
}

.admin__nav-label {
  font-size: $font-size-body;
  font-weight: $font-weight-medium;
}

.admin__nav-desc {
  font-size: $font-size-caption;
  color: $color-text-placeholder;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  .admin__nav-item.is-active & {
    color: rgba($color-primary, 0.7);
  }
}

.admin__nav-indicator {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 24px;
  background: $color-primary;
  border-radius: 0 $radius-xs $radius-xs 0;
  opacity: 0;
  transition: opacity $transition-fast;

  .is-collapsed & {
    display: none;
  }
}

.admin__sidebar-foot {
  padding: $spacing-base;
  border-top: 1px solid $color-divider;
}

.admin__foot-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  width: 100%;
  padding: $spacing-sm $spacing-base;
  background: transparent;
  border: 1px solid $color-border;
  border-radius: $radius-base;
  color: $color-text-secondary;
  font-size: $font-size-body;
  font-family: inherit;
  cursor: pointer;
  transition: all $transition-fast;
  min-height: 36px;

  &:hover {
    color: $color-primary;
    border-color: $color-primary;
    background: $color-primary-softer;
  }

  .is-collapsed & {
    padding: $spacing-sm;
  }
}

// =====================================================================
// 主区域
// =====================================================================
.admin__body {
  flex: 1;
  margin-left: var(--admin-sidebar-width);
  display: flex;
  flex-direction: column;
  min-width: 0;
  transition: margin-left $transition-base;

  .is-collapsed & {
    margin-left: 72px;
  }
}

.admin__topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $spacing-lg;
  padding: $spacing-base $spacing-xl;
  background: $color-bg-primary;
  border-bottom: 1px solid $color-border;
  position: sticky;
  top: 0;
  z-index: $z-sticky;
  min-height: $height-nav;

  &-left {
    display: flex;
    align-items: center;
    gap: $spacing-base;
    min-width: 0;
    flex: 1;
  }

  &-right {
    display: flex;
    align-items: center;
    gap: $spacing-lg;
    flex-shrink: 0;
  }
}

.admin__menu-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid $color-border;
  background: $color-bg-primary;
  border-radius: $radius-base;
  color: $color-text-primary;
  cursor: pointer;
  flex-shrink: 0;
}

.admin__page-head {
  min-width: 0;
  flex: 1;
}

.admin__breadcrumb {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  font-size: $font-size-caption;
  color: $color-text-secondary;
  margin-bottom: 2px;

  &-item {
    &.is-current {
      color: $color-text-primary;
    }
  }

  &-sep {
    color: $color-text-placeholder;
  }
}

.admin__page-title {
  font-size: $font-size-h2;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  line-height: $line-height-tight;
  letter-spacing: $letter-spacing-tight;
}

.admin__page-desc {
  font-size: $font-size-small;
  color: $color-text-secondary;
  margin-top: 2px;
  line-height: 1.4;
}

.admin__status {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-xs $spacing-md;
  background: $color-accent-2-soft;
  border-radius: $radius-pill;
  font-size: $font-size-small;
  color: $color-accent-2;

  &-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: $color-accent-2;
    box-shadow: 0 0 0 3px rgba($color-accent-2, 0.18);
  }
}

.admin__user {
  display: flex;
  align-items: center;
  gap: $spacing-md;

  &-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    line-height: 1.2;
  }

  &-name {
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
  }

  &-role {
    font-size: $font-size-caption;
    color: $color-text-secondary;
    margin-top: 2px;
  }

  &-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $color-primary;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-body;
    font-weight: $font-weight-semibold;
    flex-shrink: 0;
  }

  &-logout {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid $color-border;
    background: $color-bg-primary;
    border-radius: $radius-base;
    color: $color-text-secondary;
    cursor: pointer;
    transition: all $transition-fast;

    &:hover {
      color: $color-danger;
      border-color: $color-danger;
    }
  }
}

.admin__content {
  flex: 1;
  padding: $spacing-xl;
  max-width: $container-max-width-admin;
  width: 100%;
}

.admin__footer {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-base $spacing-xl;
  border-top: 1px solid $color-border;
  background: $color-bg-primary;
  font-size: $font-size-caption;
  color: $color-text-placeholder;

  &-divider {
    color: $color-text-placeholder;
  }
}

// =====================================================================
// 移动端遮罩
// =====================================================================
.admin__overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(45, 55, 72, 0.36);
  z-index: $z-fixed - 1;
}

.admin-overlay-enter-active,
.admin-overlay-leave-active {
  transition: opacity $transition-base;
}

.admin-overlay-enter-from,
.admin-overlay-leave-to {
  opacity: 0;
}

// =====================================================================
// 响应式
// =====================================================================
@media (max-width: 1023px) {
  .admin__sidebar {
    transform: translateX(-100%);
    transition: transform $transition-base, width $transition-base;

    &.is-open {
      transform: translateX(0);
    }
  }

  .admin.is-collapsed .admin__sidebar {
    width: $sidebar-width-admin;
  }

  .admin.is-collapsed .admin__body,
  .admin__body {
    margin-left: 0;
  }

  .admin__overlay {
    display: block;
  }

  .admin__menu-btn {
    display: inline-flex;
  }

  .admin__collapse {
    display: none;
  }

  .admin__topbar {
    padding: $spacing-base $spacing-base;
  }

  .admin__content {
    padding: $spacing-base;
  }

  .admin__status,
  .admin__user-info {
    display: none;
  }

  .admin__page-desc {
    display: none;
  }
}

@media (max-width: 640px) {
  .admin__breadcrumb {
    display: none;
  }
}
</style>
