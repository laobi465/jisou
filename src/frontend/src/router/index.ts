import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

// 路由表
// 详见 PROJECT.md §8 页面清单
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { title: '搜索' },
  },
  {
    path: '/search',
    name: 'search',
    component: () => import('@/views/SearchView.vue'),
    meta: { title: '搜索结果' },
  },
  {
    path: '/resource/:hash',
    name: 'resource',
    component: () => import('@/views/ResourceView.vue'),
    meta: { title: '资源详情' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: '登录' },
  },
  {
    path: '/user',
    name: 'user',
    component: () => import('@/views/UserView.vue'),
    meta: { title: '个人中心', requiresAuth: true },
  },
  {
    path: '/admin',
    component: () => import('@/views/admin/AdminLayout.vue'),
    meta: { requiresAdmin: true },
    children: [
      { path: '', name: 'admin-dashboard', component: () => import('@/views/admin/DashboardView.vue') },
      { path: 'providers', name: 'admin-providers', component: () => import('@/views/admin/ProvidersView.vue') },
      { path: 'crawl', name: 'admin-crawl', component: () => import('@/views/admin/CrawlView.vue') },
      { path: 'resources', name: 'admin-resources', component: () => import('@/views/admin/ResourcesView.vue') },
      { path: 'blacklist', name: 'admin-blacklist', component: () => import('@/views/admin/BlacklistView.vue') },
      { path: 'users', name: 'admin-users', component: () => import('@/views/admin/UsersView.vue') },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 全局前置守卫：鉴权待 M3 接入
router.beforeEach((to, _from, next) => {
  const title = to.meta.title as string | undefined;
  if (title) {
    document.title = `${title} - jisou`;
  }
  next();
});

export default router;
