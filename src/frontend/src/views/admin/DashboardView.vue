<script setup lang="ts">
// 超管仪表盘
// 设计依据：docs/UI-DESIGN.md §八 后台页面 - 仪表盘
// 顶部 4 个统计卡片 / 7 天抓取趋势 / 来源占比 / 最近爬虫日志
// 数据待接入：M4 里程碑从 /api/admin/dashboard 拉取
import { ref, computed, onMounted } from 'vue';
import JButton from '@/components/JButton.vue';
import JCard from '@/components/JCard.vue';

type Trend = 'up' | 'down' | 'flat';

interface StatCard {
  key: string;
  label: string;
  value: string;
  unit?: string;
  trend: Trend;
  trendValue: string;
  desc: string;
  icon: 'resource' | 'plus' | 'call' | 'invalid';
  accent: 'primary' | 'green' | 'warning' | 'danger';
}

const stats = ref<StatCard[]>([
  {
    key: 'total',
    label: '资源总量',
    value: '312,847',
    trend: 'up',
    trendValue: '+2.4%',
    desc: '较上周新增 7,326 条',
    icon: 'resource',
    accent: 'primary',
  },
  {
    key: 'today',
    label: '今日新增',
    value: '1,268',
    unit: '条',
    trend: 'up',
    trendValue: '+18.6%',
    desc: '今日 0:00 至现在',
    icon: 'plus',
    accent: 'green',
  },
  {
    key: 'calls',
    label: 'Provider 调用量',
    value: '48.2k',
    unit: '次',
    trend: 'flat',
    trendValue: '+0.8%',
    desc: '近 24 小时累计',
    icon: 'call',
    accent: 'warning',
  },
  {
    key: 'invalid',
    label: '失效率',
    value: '4.32',
    unit: '%',
    trend: 'down',
    trendValue: '-0.6%',
    desc: '近 7 天检测均值',
    icon: 'invalid',
    accent: 'danger',
  },
]);

// 最近 7 天抓取趋势（待接入：M4 阶段从 /api/admin/dashboard/trend 拉取）
interface TrendPoint {
  date: string;
  weekday: string;
  value: number;
}

const trendData = ref<TrendPoint[]>([
  { date: '07-19', weekday: '周六', value: 980 },
  { date: '07-20', weekday: '周日', value: 1120 },
  { date: '07-21', weekday: '周一', value: 1480 },
  { date: '07-22', weekday: '周二', value: 1320 },
  { date: '07-23', weekday: '周三', value: 1680 },
  { date: '07-24', weekday: '周四', value: 1420 },
  { date: '07-25', weekday: '今日', value: 1268 },
]);

const trendMax = computed(() => Math.max(...trendData.value.map(p => p.value)));
const trendMin = computed(() => Math.min(...trendData.value.map(p => p.value)));
const trendAvg = computed(() =>
  Math.round(trendData.value.reduce((s, p) => s + p.value, 0) / trendData.value.length)
);

// 趋势图 SVG 路径
const trendPath = computed(() => {
  const data = trendData.value;
  if (data.length === 0) return '';
  const w = 100;
  const h = 100;
  const pad = 4;
  const step = (w - pad * 2) / (data.length - 1);
  const max = trendMax.value;
  const min = trendMin.value;
  const range = max - min || 1;
  return data
    .map((p, i) => {
      const x = pad + i * step;
      const y = h - pad - ((p.value - min) / range) * (h - pad * 2);
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`;
    })
    .join(' ');
});

const trendAreaPath = computed(() => {
  if (!trendPath.value) return '';
  const data = trendData.value;
  const w = 100;
  const h = 100;
  const pad = 4;
  const step = (w - pad * 2) / (data.length - 1);
  const startX = pad;
  const endX = pad + (data.length - 1) * step;
  return `${trendPath.value} L${endX.toFixed(2)},${h - pad} L${startX.toFixed(2)},${h - pad} Z`;
});

// 各来源占比（待接入：M4 阶段从 /api/admin/dashboard/sources 拉取）
interface SourceSlice {
  key: string;
  label: string;
  value: number;
  color: string;
}

const sources = ref<SourceSlice[]>([
  { key: 'baidu', label: '百度网盘', value: 38, color: '#3A7BD5' },
  { key: 'aliyun', label: '阿里云盘', value: 24, color: '#1E5BB8' },
  { key: 'quark', label: '夸克网盘', value: 16, color: '#2E8B6F' },
  { key: '115', label: '115 网盘', value: 9, color: '#6B7280' },
  { key: 'tg', label: 'TG 频道', value: 11, color: '#5B8DEF' },
  { key: 'self', label: '自建索引', value: 2, color: '#A0AEC0' },
]);

const totalSource = computed(() => sources.value.reduce((s, x) => s + x.value, 0));

// 饼图 SVG（环形）
const donutSegments = computed(() => {
  const radius = 42;
  const circumference = 2 * Math.PI * radius;
  let offset = 0;
  return sources.value.map(s => {
    const percent = s.value / totalSource.value;
    const dash = percent * circumference;
    const seg = {
      ...s,
      dasharray: `${dash.toFixed(2)} ${(circumference - dash).toFixed(2)}`,
      dashoffset: `${(-offset).toFixed(2)}`,
      percent: (percent * 100).toFixed(1),
    };
    offset += dash;
    return seg;
  });
});

// 最近爬虫日志（待接入：M4 阶段从 /api/admin/dashboard/logs 拉取）
type LogStatus = 'success' | 'failed' | 'partial' | 'running';

interface CrawlLog {
  id: number;
  task: string;
  provider: string;
  status: LogStatus;
  found: number;
  duration: string;
  finished_at: string;
}

const logs = ref<CrawlLog[]>([
  { id: 8742, task: 'TG 频道增量抓取', provider: 'TG', status: 'success', found: 124, duration: '12.4s', finished_at: '2026-07-25 14:32:08' },
  { id: 8741, task: '百度网盘全量巡检', provider: '百度', status: 'success', found: 86, duration: '124.6s', finished_at: '2026-07-25 14:30:42' },
  { id: 8740, task: '阿里云盘失效检测', provider: '阿里', status: 'partial', found: 0, duration: '45.1s', finished_at: '2026-07-25 14:28:11' },
  { id: 8739, task: '夸克网盘关键词扫描', provider: '夸克', status: 'success', found: 38, duration: '8.9s', finished_at: '2026-07-25 14:25:33' },
  { id: 8738, task: '115 网盘索引同步', provider: '115', status: 'failed', found: 0, duration: '3.2s', finished_at: '2026-07-25 14:22:50' },
  { id: 8737, task: 'TG 频道增量抓取', provider: 'TG', status: 'success', found: 92, duration: '11.8s', finished_at: '2026-07-25 14:18:22' },
  { id: 8736, task: '百度网盘全量巡检', provider: '百度', status: 'running', found: 0, duration: '—', finished_at: '—' },
  { id: 8735, task: '阿里云盘失效检测', provider: '阿里', status: 'success', found: 0, duration: '47.3s', finished_at: '2026-07-25 14:12:08' },
]);

const logStatusMap: Record<LogStatus, { label: string; type: 'active' | 'invalid' | 'unchecked' }> = {
  success: { label: '成功', type: 'active' },
  failed: { label: '失败', type: 'invalid' },
  partial: { label: '部分失败', type: 'unchecked' },
  running: { label: '执行中', type: 'unchecked' },
};

// Provider 健康概览
interface ProviderHealth {
  key: string;
  label: string;
  status: 'healthy' | 'degraded' | 'down';
  calls: string;
  successRate: string;
}

const providers = ref<ProviderHealth[]>([
  { key: 'baidu', label: '百度网盘', status: 'healthy', calls: '14.2k', successRate: '99.4%' },
  { key: 'aliyun', label: '阿里云盘', status: 'degraded', calls: '11.8k', successRate: '92.1%' },
  { key: 'quark', label: '夸克网盘', status: 'healthy', calls: '8.6k', successRate: '98.8%' },
  { key: '115', label: '115 网盘', status: 'down', calls: '3.1k', successRate: '0%' },
  { key: 'tg', label: 'TG 频道', status: 'healthy', calls: '10.5k', successRate: '99.9%' },
]);

const healthMap: Record<string, { label: string; type: 'active' | 'unchecked' | 'invalid' }> = {
  healthy: { label: '健康', type: 'active' },
  degraded: { label: '降级', type: 'unchecked' },
  down: { label: '异常', type: 'invalid' },
};

const loading = ref(false);

async function refresh() {
  loading.value = true;
  try {
    // 待接入：M4 阶段调用 /api/admin/dashboard/refresh
    await new Promise(r => setTimeout(r, 400));
  } finally {
    loading.value = false;
  }
}

function formatNumber(n: number): string {
  return n.toLocaleString();
}

onMounted(() => {
  // 待接入：M4 阶段从后端拉取数据
});
</script>

<template>
  <div class="dashboard">
    <!-- 操作条 -->
    <div class="dashboard__bar">
      <div class="dashboard__bar-info">
        <span class="dashboard__bar-dot" aria-hidden="true" />
        <span class="dashboard__bar-text">数据快照：2026-07-25 14:32 · 下次自动刷新 60s</span>
      </div>
      <JButton size="small" :loading="loading" @click="refresh">
        <span class="dashboard__bar-action">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M2 7a5 5 0 0 1 8.5-3.5M12 7a5 5 0 0 1-8.5 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M11 1v3H8M3 13v-3h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>刷新</span>
        </span>
      </JButton>
    </div>

    <!-- 顶部统计卡片 -->
    <section class="dashboard__stats">
      <JCard
        v-for="(s, i) in stats"
        :key="s.key"
        padding="loose"
        class="dashboard__stat-card animate-fade-up"
        :style="{ animationDelay: `${i * 0.04}s` }"
      >
        <div class="dashboard__stat-head">
          <div class="dashboard__stat-icon" :class="`dashboard__stat-icon--${s.accent}`" aria-hidden="true">
            <svg v-if="s.icon === 'resource'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M12 2H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6l-3-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M12 2v4h3" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
            </svg>
            <svg v-else-if="s.icon === 'plus'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/>
              <path d="M10 6v8M6 10h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <svg v-else-if="s.icon === 'call'" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M3 5l3 3 2-2 4 4-2 2 3 3 4-4c0-5-7-12-12-12L3 5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
            </svg>
            <svg v-else width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 2L2 17h16L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M10 8v4M10 15v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <div
            class="dashboard__stat-trend"
            :class="`dashboard__stat-trend--${s.trend}`"
          >
            <svg v-if="s.trend === 'up'" width="10" height="10" viewBox="0 0 10 10" fill="none">
              <path d="M2 7l3-4 3 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else-if="s.trend === 'down'" width="10" height="10" viewBox="0 0 10 10" fill="none">
              <path d="M2 3l3 4 3-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else width="10" height="10" viewBox="0 0 10 10" fill="none">
              <path d="M2 5h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span>{{ s.trendValue }}</span>
          </div>
        </div>
        <div class="dashboard__stat-value">
          <span class="dashboard__stat-number">{{ s.value }}</span>
          <span v-if="s.unit" class="dashboard__stat-unit">{{ s.unit }}</span>
        </div>
        <div class="dashboard__stat-label">{{ s.label }}</div>
        <div class="dashboard__stat-desc">{{ s.desc }}</div>
      </JCard>
    </section>

    <!-- 中部图表区 -->
    <section class="dashboard__charts">
      <!-- 7 天抓取趋势 -->
      <JCard padding="loose" class="dashboard__chart-card dashboard__chart-card--trend animate-fade-up">
        <header class="dashboard__chart-head">
          <div>
            <h3 class="dashboard__chart-title">最近 7 天抓取趋势</h3>
            <p class="dashboard__chart-desc">各 Provider 合计新增资源数</p>
          </div>
          <div class="dashboard__chart-summary">
            <div class="dashboard__chart-summary-item">
              <span class="dashboard__chart-summary-label">日均</span>
              <span class="dashboard__chart-summary-value">{{ formatNumber(trendAvg) }}</span>
            </div>
            <div class="dashboard__chart-summary-item">
              <span class="dashboard__chart-summary-label">峰值</span>
              <span class="dashboard__chart-summary-value">{{ formatNumber(trendMax) }}</span>
            </div>
            <div class="dashboard__chart-summary-item">
              <span class="dashboard__chart-summary-label">本周合计</span>
              <span class="dashboard__chart-summary-value">{{ formatNumber(trendData.reduce((s, p) => s + p.value, 0)) }}</span>
            </div>
          </div>
        </header>

        <div class="dashboard__trend">
          <svg
            class="dashboard__trend-svg"
            viewBox="0 0 100 100"
            preserveAspectRatio="none"
            aria-hidden="true"
          >
            <defs>
              <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1E5BB8" stop-opacity="0.18" />
                <stop offset="100%" stop-color="#1E5BB8" stop-opacity="0" />
              </linearGradient>
            </defs>
            <!-- 网格线 -->
            <line x1="4" y1="20" x2="96" y2="20" stroke="#E2E8F0" stroke-width="0.3" />
            <line x1="4" y1="50" x2="96" y2="50" stroke="#E2E8F0" stroke-width="0.3" />
            <line x1="4" y1="80" x2="96" y2="80" stroke="#E2E8F0" stroke-width="0.3" />
            <!-- 区域 -->
            <path :d="trendAreaPath" fill="url(#trendFill)" />
            <!-- 折线 -->
            <path
              :d="trendPath"
              fill="none"
              stroke="#1E5BB8"
              stroke-width="0.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              vector-effect="non-scaling-stroke"
            />
            <!-- 数据点 -->
            <circle
              v-for="(p, i) in trendData"
              :key="i"
              :cx="4 + (i * 92) / (trendData.length - 1)"
              :cy="96 - ((p.value - trendMin) / (trendMax - trendMin || 1)) * 92"
              r="0.9"
              fill="#fff"
              stroke="#1E5BB8"
              stroke-width="0.6"
              vector-effect="non-scaling-stroke"
            />
          </svg>

          <div class="dashboard__trend-x">
            <div
              v-for="(p, i) in trendData"
              :key="i"
              class="dashboard__trend-x-item"
              :class="{ 'is-today': i === trendData.length - 1 }"
            >
              <span class="dashboard__trend-x-weekday">{{ p.weekday }}</span>
              <span class="dashboard__trend-x-value">{{ formatNumber(p.value) }}</span>
            </div>
          </div>
        </div>
      </JCard>

      <!-- 来源占比 -->
      <JCard padding="loose" class="dashboard__chart-card dashboard__chart-card--source animate-fade-up" style="animation-delay: 0.05s">
        <header class="dashboard__chart-head">
          <div>
            <h3 class="dashboard__chart-title">来源占比</h3>
            <p class="dashboard__chart-desc">全量资源按数据源分布</p>
          </div>
        </header>

        <div class="dashboard__source">
          <div class="dashboard__source-donut">
            <svg viewBox="0 0 100 100" class="dashboard__source-svg">
              <circle cx="50" cy="50" r="42" fill="none" stroke="#F7F9FC" stroke-width="12" />
              <circle
                v-for="(seg, i) in donutSegments"
                :key="seg.key"
                cx="50"
                cy="50"
                r="42"
                fill="none"
                :stroke="seg.color"
                stroke-width="12"
                :stroke-dasharray="seg.dasharray"
                :stroke-dashoffset="seg.dashoffset"
                transform="rotate(-90 50 50)"
                :style="{ transition: `stroke-dasharray 0.6s ${i * 0.08}s ease` }"
              />
            </svg>
            <div class="dashboard__source-center">
              <span class="dashboard__source-center-value">{{ formatNumber(totalSource) }}%</span>
              <span class="dashboard__source-center-label">合计</span>
            </div>
          </div>

          <ul class="dashboard__source-list">
            <li
              v-for="(s, i) in donutSegments"
              :key="s.key"
              class="dashboard__source-item animate-fade-up"
              :style="{ animationDelay: `${0.1 + i * 0.04}s` }"
            >
              <span class="dashboard__source-dot" :style="{ background: s.color }" aria-hidden="true" />
              <span class="dashboard__source-label">{{ s.label }}</span>
              <span class="dashboard__source-value">{{ s.percent }}%</span>
            </li>
          </ul>
        </div>
      </JCard>
    </section>

    <!-- 下部：Provider 健康 + 爬虫日志 -->
    <section class="dashboard__bottom">
      <!-- Provider 健康概览 -->
      <JCard padding="loose" class="dashboard__panel dashboard__panel--providers animate-fade-up">
        <header class="dashboard__panel-head">
          <div>
            <h3 class="dashboard__panel-title">Provider 健康概览</h3>
            <p class="dashboard__panel-desc">近 24 小时调用与成功率</p>
          </div>
          <router-link to="/admin/providers" class="dashboard__panel-link">
            管理
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M4 2l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </router-link>
        </header>

        <ul class="dashboard__provider-list">
          <li
            v-for="(p, i) in providers"
            :key="p.key"
            class="dashboard__provider-item animate-fade-up"
            :style="{ animationDelay: `${i * 0.04}s` }"
          >
            <div class="dashboard__provider-info">
              <span class="dashboard__provider-dot" :class="`dashboard__provider-dot--${p.status}`" aria-hidden="true" />
              <span class="dashboard__provider-name">{{ p.label }}</span>
            </div>
            <div class="dashboard__provider-stats">
              <span class="dashboard__provider-stat">
                <span class="dashboard__provider-stat-label">调用</span>
                <span class="dashboard__provider-stat-value">{{ p.calls }}</span>
              </span>
              <span class="dashboard__provider-stat">
                <span class="dashboard__provider-stat-label">成功率</span>
                <span
                  class="dashboard__provider-stat-value"
                  :class="{
                    'is-good': parseFloat(p.successRate) >= 95,
                    'is-warn': parseFloat(p.successRate) >= 50 && parseFloat(p.successRate) < 95,
                    'is-bad': parseFloat(p.successRate) < 50,
                  }"
                >
                  {{ p.successRate }}
                </span>
              </span>
              <span
                class="dashboard__provider-badge"
                :class="`dashboard__provider-badge--${healthMap[p.status].type}`"
              >
                {{ healthMap[p.status].label }}
              </span>
            </div>
          </li>
        </ul>
      </JCard>

      <!-- 最近爬虫日志 -->
      <JCard padding="loose" class="dashboard__panel dashboard__panel--logs animate-fade-up" style="animation-delay: 0.05s">
        <header class="dashboard__panel-head">
          <div>
            <h3 class="dashboard__panel-title">最近爬虫日志</h3>
            <p class="dashboard__panel-desc">最近 8 条任务执行记录</p>
          </div>
          <router-link to="/admin/crawl" class="dashboard__panel-link">
            全部
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M4 2l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </router-link>
        </header>

        <div class="dashboard__log-table">
          <div class="dashboard__log-row dashboard__log-row--head">
            <span class="dashboard__log-cell dashboard__log-cell--id">#</span>
            <span class="dashboard__log-cell dashboard__log-cell--task">任务</span>
            <span class="dashboard__log-cell dashboard__log-cell--provider">来源</span>
            <span class="dashboard__log-cell dashboard__log-cell--status">状态</span>
            <span class="dashboard__log-cell dashboard__log-cell--found">新增</span>
            <span class="dashboard__log-cell dashboard__log-cell--duration">耗时</span>
            <span class="dashboard__log-cell dashboard__log-cell--time">结束时间</span>
          </div>
          <div
            v-for="log in logs"
            :key="log.id"
            class="dashboard__log-row"
          >
            <span class="dashboard__log-cell dashboard__log-cell--id">{{ log.id }}</span>
            <span class="dashboard__log-cell dashboard__log-cell--task">{{ log.task }}</span>
            <span class="dashboard__log-cell dashboard__log-cell--provider">
              <span class="dashboard__log-provider">{{ log.provider }}</span>
            </span>
            <span class="dashboard__log-cell dashboard__log-cell--status">
              <span
                class="dashboard__log-badge"
                :class="`dashboard__log-badge--${logStatusMap[log.status].type}`"
              >
                {{ logStatusMap[log.status].label }}
              </span>
            </span>
            <span class="dashboard__log-cell dashboard__log-cell--found">
              <span v-if="log.found > 0">+{{ log.found }}</span>
              <span v-else class="dashboard__log-empty">—</span>
            </span>
            <span class="dashboard__log-cell dashboard__log-cell--duration">{{ log.duration }}</span>
            <span class="dashboard__log-cell dashboard__log-cell--time">{{ log.finished_at }}</span>
          </div>
        </div>
      </JCard>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: $spacing-xl;
}

// =====================================================================
// 操作条
// =====================================================================
.dashboard__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-md $spacing-lg;
  background: $color-bg-primary;
  border: 1px solid $color-border;
  border-radius: $radius-lg;

  &-info {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
  }

  &-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: $color-accent-2;
    box-shadow: 0 0 0 3px rgba($color-accent-2, 0.18);
  }

  &-text {
    font-size: $font-size-small;
    color: $color-text-secondary;
  }

  &-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
}

// =====================================================================
// 统计卡片
// =====================================================================
.dashboard__stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-base;
}

.dashboard__stat-card {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.dashboard__stat-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: $spacing-sm;
}

.dashboard__stat-icon {
  width: 36px;
  height: 36px;
  border-radius: $radius-base;
  display: inline-flex;
  align-items: center;
  justify-content: center;

  &--primary {
    background: $color-primary-soft;
    color: $color-primary;
  }
  &--green {
    background: $color-accent-2-soft;
    color: $color-accent-2;
  }
  &--warning {
    background: $color-warning-soft;
    color: $color-warning;
  }
  &--danger {
    background: $color-danger-soft;
    color: $color-danger;
  }
}

.dashboard__stat-trend {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: $font-size-caption;
  font-weight: $font-weight-medium;
  padding: 2px 6px;
  border-radius: $radius-sm;

  &--up {
    color: $color-accent-2;
    background: $color-accent-2-soft;
  }
  &--down {
    color: $color-accent-2;
    background: $color-accent-2-soft;
  }
  &--flat {
    color: $color-text-secondary;
    background: $color-bg-tertiary;
  }

  // 失效率下降是好事，颜色翻转
  .dashboard__stat-card:nth-child(4) &--down {
    color: $color-accent-2;
    background: $color-accent-2-soft;
  }
  .dashboard__stat-card:nth-child(4) &--up {
    color: $color-danger;
    background: $color-danger-soft;
  }
}

.dashboard__stat-value {
  display: flex;
  align-items: baseline;
  gap: 4px;
  line-height: 1;
}

.dashboard__stat-number {
  font-size: 28px;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  letter-spacing: $letter-spacing-tight;
  font-variant-numeric: tabular-nums;
}

.dashboard__stat-unit {
  font-size: $font-size-body;
  color: $color-text-secondary;
}

.dashboard__stat-label {
  font-size: $font-size-body;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
  margin-top: $spacing-xs;
}

.dashboard__stat-desc {
  font-size: $font-size-caption;
  color: $color-text-placeholder;
}

// =====================================================================
// 图表区
// =====================================================================
.dashboard__charts {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: $spacing-base;
}

.dashboard__chart-card {
  display: flex;
  flex-direction: column;
}

.dashboard__chart-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $spacing-base;
  margin-bottom: $spacing-lg;
}

.dashboard__chart-title {
  font-size: $font-size-h3;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  line-height: 1.3;
}

.dashboard__chart-desc {
  font-size: $font-size-small;
  color: $color-text-secondary;
  margin-top: 2px;
}

.dashboard__chart-summary {
  display: flex;
  gap: $spacing-lg;

  &-item {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    line-height: 1.2;
  }

  &-label {
    font-size: $font-size-caption;
    color: $color-text-placeholder;
  }

  &-value {
    font-size: $font-size-body-lg;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    font-variant-numeric: tabular-nums;
    margin-top: 2px;
  }
}

// 趋势图
.dashboard__trend {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 240px;
}

.dashboard__trend-svg {
  width: 100%;
  height: 200px;
  display: block;
}

.dashboard__trend-x {
  display: flex;
  justify-content: space-between;
  margin-top: $spacing-sm;
  padding: 0 2%;
}

.dashboard__trend-x-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  flex: 1;

  &.is-today {
    .dashboard__trend-x-weekday,
    .dashboard__trend-x-value {
      color: $color-primary;
      font-weight: $font-weight-medium;
    }
  }
}

.dashboard__trend-x-weekday {
  font-size: $font-size-caption;
  color: $color-text-placeholder;
}

.dashboard__trend-x-value {
  font-size: $font-size-small;
  color: $color-text-secondary;
  font-variant-numeric: tabular-nums;
}

// 来源占比
.dashboard__source {
  display: flex;
  align-items: center;
  gap: $spacing-xl;
  flex: 1;
}

.dashboard__source-donut {
  position: relative;
  width: 140px;
  height: 140px;
  flex-shrink: 0;
}

.dashboard__source-svg {
  width: 100%;
  height: 100%;
}

.dashboard__source-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  pointer-events: none;

  &-value {
    font-size: $font-size-h3;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    line-height: 1;
    font-variant-numeric: tabular-nums;
  }

  &-label {
    font-size: $font-size-caption;
    color: $color-text-placeholder;
    margin-top: 4px;
  }
}

.dashboard__source-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
  flex: 1;
}

.dashboard__source-item {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  font-size: $font-size-small;
}

.dashboard__source-dot {
  width: 8px;
  height: 8px;
  border-radius: 2px;
  flex-shrink: 0;
}

.dashboard__source-label {
  color: $color-text-secondary;
  flex: 1;
}

.dashboard__source-value {
  color: $color-text-primary;
  font-weight: $font-weight-medium;
  font-variant-numeric: tabular-nums;
}

// =====================================================================
// 下部面板
// =====================================================================
.dashboard__bottom {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: $spacing-base;
}

.dashboard__panel {
  display: flex;
  flex-direction: column;
}

.dashboard__panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $spacing-base;
  margin-bottom: $spacing-base;
}

.dashboard__panel-title {
  font-size: $font-size-h3;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  line-height: 1.3;
}

.dashboard__panel-desc {
  font-size: $font-size-small;
  color: $color-text-secondary;
  margin-top: 2px;
}

.dashboard__panel-link {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: $font-size-small;
  color: $color-primary;
  text-decoration: none;
  transition: color $transition-fast;

  &:hover {
    color: $color-primary-hover;
  }
}

// Provider 列表
.dashboard__provider-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
}

.dashboard__provider-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-md 0;
  border-bottom: 1px solid $color-divider;
  gap: $spacing-base;

  &:last-child {
    border-bottom: none;
  }
}

.dashboard__provider-info {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  min-width: 0;
  flex: 1;
}

.dashboard__provider-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;

  &--healthy {
    background: $color-accent-2;
    box-shadow: 0 0 0 3px rgba($color-accent-2, 0.18);
  }
  &--degraded {
    background: $color-warning;
    box-shadow: 0 0 0 3px rgba($color-warning, 0.18);
  }
  &--down {
    background: $color-danger;
    box-shadow: 0 0 0 3px rgba($color-danger, 0.18);
  }
}

.dashboard__provider-name {
  font-size: $font-size-body;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
}

.dashboard__provider-stats {
  display: flex;
  align-items: center;
  gap: $spacing-lg;
}

.dashboard__provider-stat {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.2;

  &-label {
    font-size: $font-size-caption;
    color: $color-text-placeholder;
  }

  &-value {
    font-size: $font-size-small;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
    font-variant-numeric: tabular-nums;
    margin-top: 2px;

    &.is-good { color: $color-accent-2; }
    &.is-warn { color: $color-warning; }
    &.is-bad  { color: $color-danger; }
  }
}

.dashboard__provider-badge {
  font-size: $font-size-caption;
  font-weight: $font-weight-medium;
  padding: 2px 8px;
  border-radius: $radius-sm;
  white-space: nowrap;

  &--active {
    color: $color-accent-2;
    background: $color-accent-2-soft;
  }
  &--unchecked {
    color: $color-warning;
    background: $color-warning-soft;
  }
  &--invalid {
    color: $color-danger;
    background: $color-danger-soft;
  }
}

// 日志表格
.dashboard__log-table {
  display: flex;
  flex-direction: column;
  border: 1px solid $color-border;
  border-radius: $radius-base;
  overflow: hidden;
}

.dashboard__log-row {
  display: grid;
  grid-template-columns: 56px 1.6fr 80px 80px 70px 70px 1.2fr;
  gap: $spacing-sm;
  padding: $spacing-md $spacing-base;
  align-items: center;
  font-size: $font-size-small;
  border-bottom: 1px solid $color-divider;

  &:last-child {
    border-bottom: none;
  }

  &--head {
    background: $color-bg-secondary;
    font-weight: $font-weight-medium;
    color: $color-text-secondary;
    font-size: $font-size-caption;
  }

  &:not(&--head):hover {
    background: $color-bg-secondary;
  }
}

.dashboard__log-cell {
  &--id {
    color: $color-text-placeholder;
    font-variant-numeric: tabular-nums;
  }
  &--task {
    color: $color-text-primary;
    font-weight: $font-weight-medium;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  &--time {
    color: $color-text-secondary;
    font-variant-numeric: tabular-nums;
  }
}

.dashboard__log-provider {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  background: $color-bg-tertiary;
  border-radius: $radius-sm;
  font-size: $font-size-caption;
  color: $color-text-secondary;
}

.dashboard__log-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: $radius-sm;
  font-size: $font-size-caption;
  font-weight: $font-weight-medium;

  &--active {
    color: $color-accent-2;
    background: $color-accent-2-soft;
  }
  &--unchecked {
    color: $color-warning;
    background: $color-warning-soft;
  }
  &--invalid {
    color: $color-danger;
    background: $color-danger-soft;
  }
}

.dashboard__log-empty {
  color: $color-text-placeholder;
}

// =====================================================================
// 响应式
// =====================================================================
@media (max-width: 1023px) {
  .dashboard__stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard__charts {
    grid-template-columns: 1fr;
  }

  .dashboard__bottom {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .dashboard__stats {
    grid-template-columns: 1fr;
  }

  .dashboard__chart-summary {
    flex-wrap: wrap;
    gap: $spacing-base;
  }

  .dashboard__source {
    flex-direction: column;
    align-items: stretch;
  }

  .dashboard__source-donut {
    margin: 0 auto;
  }

  .dashboard__provider-stats {
    flex-wrap: wrap;
    gap: $spacing-base;
  }

  .dashboard__log-row {
    grid-template-columns: 40px 1.4fr 70px 70px;
    gap: $spacing-xs;

    .dashboard__log-cell--found,
    .dashboard__log-cell--duration,
    .dashboard__log-cell--time {
      display: none;
    }
  }
}
</style>
