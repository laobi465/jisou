<script setup lang="ts">
import { RouterView, RouterLink } from 'vue-router';

const navItems = [
  { path: '/admin', label: '仪表盘', exact: true },
  { path: '/admin/providers', label: 'Provider 管理' },
  { path: '/admin/crawl', label: '爬虫管理' },
  { path: '/admin/resources', label: '资源审核' },
  { path: '/admin/blacklist', label: '黑名单' },
  { path: '/admin/users', label: '用户管理' },
];
</script>

<template>
  <div class="admin-layout">
    <aside class="admin-layout__sidebar">
      <h2 class="admin-layout__brand">jisou 后台</h2>
      <nav class="admin-layout__nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="admin-layout__nav-item"
        >
          {{ item.label }}
        </RouterLink>
      </nav>
    </aside>
    <main class="admin-layout__main">
      <RouterView />
    </main>
  </div>
</template>

<style lang="scss" scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;

  &__sidebar {
    width: $sidebar-width-admin;
    background: $color-bg-secondary;
    border-right: 1px solid $color-border;
    padding: $spacing-lg 0;
  }

  &__brand {
    font-size: $font-size-h3;
    font-weight: $font-weight-semibold;
    padding: 0 $spacing-lg;
    margin: 0 0 $spacing-xl;
  }

  &__nav {
    display: flex;
    flex-direction: column;
  }

  &__nav-item {
    padding: $spacing-sm $spacing-lg;
    color: $color-text-primary;
    transition: background $transition-fast;

    &.router-link-active {
      background: rgba($color-primary, 0.08);
      color: $color-primary;
      font-weight: $font-weight-medium;
    }
  }

  &__main {
    flex: 1;
    padding: $spacing-xl;
    background: $color-bg-primary;
  }
}
</style>
