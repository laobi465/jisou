# jisou 项目文档（PROJECT.md）

> 版本：0.2.0 ｜ 最后更新：2026-07-25
> 维护规范：web-project-flow /bdocs (references/09-docs-lifecycle.md)
> 配套文档：[SPEC.md](./SPEC.md) ｜ [UI-DESIGN.md](./UI-DESIGN.md)

---

## 1. 项目概述

### 1.1 目标

构建一个聚合搜索引擎，统一搜索各大网盘（国内主流四盘 + 二线盘 + 国际盘）与 Telegram 公开频道 / 群组的资源分享链接，提供搜索、详情、解析、收藏、举报等全功能体验，并配套超管后台管理 Provider、爬虫任务、资源审核与黑名单。

### 1.2 背景

网盘与 TG 资源分散在多个平台，用户查找成本高。本项目通过统一抽象层聚合多数据源，让用户在一个入口完成跨平台资源发现。

### 1.3 适用范围

- 终端用户：搜索、查看、收藏、举报资源链接
- 超管：管理数据源、爬虫任务、资源审核、用户、黑名单、系统配置
- 不存储任何实体文件，仅存储元数据与链接

### 1.4 合规边界

- 仅索引公开分享链接与元数据
- 不存储、不传输实体文件
- 提供 DMCA 举报与超管审核机制
- 解析模块作为可选能力，可在超管后台一键全局关闭
- 关键词 / URL / hash 黑名单过滤

---

## 2. 架构总览

### 2.1 整体分层

```
┌──────────────────────────────────────────────────────────────┐
│  接入层: Vue3 SPA  +  ThinkPHP 8 REST API                     │
└──────────────────────────┬───────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────────┐
│  聚合层 AggregatorService                                     │
│  Query → 分发启用 Providers(并行/超时熔断) → 去重/排序/分页    │
└──────────┬───────────────────────────────┬───────────────────┘
           │                               │
   ┌───────▼────────┐              ┌──────▼────────────┐
   │ SelfIndexed    │              │ RealtimeAggregator│
   │ Provider(主力) │              │ Provider(兜底)    │
   │ 查 Meilisearch │              │ 转发第三方/TG索引 │
   └───────┬────────┘              └───────────────────┘
           │
┌──────────▼───────────────────────────────────────────────────┐
│  后台任务层 (ThinkPHP queue + Redis)                          │
│  ├─ 爬虫 worker: 抓取分享链接 → 入库                           │
│  ├─ Meilisearch 同步 worker: MySQL → 索引                     │
│  ├─ 失效检测 worker: 周期检测链接存活                          │
│  └─ 解析 worker: 按需/预解析真实下载链接                       │
└──────────┬───────────────────────────────────────────────────┘
           │
┌──────────▼───────────────────────────────────────────────────┐
│  数据层: MySQL 8(主存储) + Meilisearch(全文) + Redis(缓存/队列)│
└──────────────────────────────────────────────────────────────┘
```

### 2.2 核心模块

| 模块 | 职责 | 依赖 |
|---|---|---|
| 接入层 (API + SPA) | 接收请求、鉴权、返回响应 | 聚合层 |
| AggregatorService | 分发查询、合并去重、排序分页 | Provider 层 |
| Provider 抽象层 | 统一数据源接口，三类 Provider 实现 | 爬虫 / 第三方 API |
| 爬虫系统 | 抓取分享链接入库 | MySQL / Meilisearch |
| 失效检测系统 | 周期检测链接存活状态 | MySQL |
| 解析模块 | 按需获取真实下载链接（可选能力） | Provider 适配器 |
| 后台任务系统 | 队列调度、worker 执行 | ThinkPHP queue / Redis |
| 超管后台 | Provider / 爬虫 / 资源 / 用户 / 黑名单管理 | API |

### 2.3 数据流

**搜索流**：
1. 用户输入 query → API 接收 → AggregatorService 取启用的 Providers
2. 并行调用 `search()`，单源超时 3-5s，慢源熔断不阻塞
3. 合并：URL 规范化 hash 去重 → 排序（相关度 + 新鲜度 + 来源权重）→ 分页
4. Redis 短缓存 5-15 分钟（按 query hash）
5. 返回带来源标注的结果

**抓取流**：
1. CrawlTask 调度触发 → Crawler 抓取目标源页面 / TG 频道消息
2. 解析提取 → URL 规范化 → 去重 hash → 写入 `resources` 表
3. 异步同步到 Meilisearch 索引

**失效检测流**：
1. 失效检测 worker 按周期取待检测资源
2. 调用对应 Provider 的 `fetch()` 或 HEAD 请求
3. 更新 `resources.status` 与 `link_checks` 记录

---

## 3. 功能清单

> 状态标记：[规划] 未开始 ｜ [开发中] 进行中 ｜ [已实现] 完成 ｜ [已移除] 废弃

### 3.1 用户前台

| 功能 | 状态 | 说明 |
|---|---|---|
| 关键词搜索 | [规划] | 跨数据源聚合搜索，支持来源 / 时间 / 大小 / 状态筛选 |
| 资源详情 | [规划] | 元数据展示 + 文件列表 + 相关资源 |
| 解析下载 | [规划] | 可选能力，按来源支持情况提供真实下载链接 |
| 用户注册登录 | [规划] | 邮箱 + 密码 + 验证码 |
| 收藏 | [规划] | 收藏资源到个人中心 |
| 搜索历史 | [规划] | 记录用户搜索历史，可清除 |
| 举报 | [规划] | 举报失效 / 违规资源 |
| 失效标识 | [规划] | 搜索结果与详情页展示失效状态 |

### 3.2 超管后台

| 功能 | 状态 | 说明 |
|---|---|---|
| 仪表盘 | [规划] | 资源总量、调用量、失效率、爬虫吞吐统计 |
| Provider 管理 | [规划] | 启用 / 停用 / 配置 / 测试连接 / 查看调用量 |
| 爬虫管理 | [规划] | 任务调度 / 速率 / 游标 / 立即执行 / 暂停 / 日志 |
| 资源审核 | [规划] | 查看 / 隐藏 / 删除 / 加黑名单 |
| 黑名单管理 | [规划] | 关键词 / URL / hash 三类黑名单 |
| 用户管理 | [规划] | 用户列表 / 角色 / 状态 |
| 系统设置 | [规划] | 站点 / 邮件 / 解析开关 / 限流配置 |
| DMCA 举报处理 | [规划] | 处理用户举报，下架资源 |

### 3.3 后台任务

| 功能 | 状态 | 说明 |
|---|---|---|
| 爬虫 worker | [规划] | 按 CrawlTask 抓取入库 |
| Meilisearch 同步 worker | [规划] | MySQL 增量同步到索引 |
| 失效检测 worker | [规划] | 周期检测链接存活 |
| 解析 worker | [规划] | 按需或预解析下载链接 |

---

## 4. 使用指南

### 4.1 环境要求

- PHP 8.2+
- Composer 2.5+
- Node.js 18+
- MySQL 8.0+
- Redis 7.0+
- Meilisearch 1.6+

### 4.2 安装

```bash
# 克隆仓库
git clone https://github.com/laobi465/jisou.git
cd jisou

# 后端
cd src/backend
composer install
cp .env.example .env
# 编辑 .env，按提示填写数据库 / Redis / Meilisearch / 各 Provider 凭证
php artisan key:generate   # 待核实：ThinkPHP 8 等效命令
php think migrate:run

# 前端
cd ../frontend
npm install
npm run dev
```

### 4.3 配置

所有可变值从 `.env` 与数据库 `provider_configs` 表读取，禁止硬编码。

`.env.example` 关键项（必须使用 `<在此填写 XXX>` 形式）：

```ini
# 应用
APP_ENV=production
APP_DEBUG=false
APP_URL=<在此填写站点域名>

# 数据库
DB_HOST=<在此填写数据库主机>
DB_PORT=3306
DB_DATABASE=<在此填写数据库名>
DB_USERNAME=<在此填写数据库用户名>
DB_PASSWORD=<在此填写数据库密码>

# Redis
REDIS_HOST=<在此填写 Redis 主机>
REDIS_PORT=6379
REDIS_PASSWORD=<在此填写 Redis 密码>

# Meilisearch
MEILISEARCH_HOST=<在此填写 Meilisearch 地址>
MEILISEARCH_KEY=<在此填写 Meilisearch 主密钥>

# Telegram 爬虫（如启用）
TG_API_ID=<在此填写 Telegram API ID>
TG_API_HASH=<在此填写 Telegram API Hash>
TG_SESSION=<在此填写 Telegram Session 字符串>

# 邮件
MAIL_HOST=<在此填写 SMTP 主机>
MAIL_PORT=587
MAIL_USERNAME=<在此填写 SMTP 用户名>
MAIL_PASSWORD=<在此填写 SMTP 密码>
MAIL_FROM_ADDRESS=<在此填写发件邮箱>
```

### 4.4 运行

```bash
# 启动后端 API
cd src/backend && php think run --host=0.0.0.0 --port=8000

# 启动队列 worker
php think queue:work

# 启动前端开发服务器
cd src/frontend && npm run dev

# 生产构建
npm run build
```

### 4.5 示例

**搜索请求**：
```
GET /api/search?q=关键词&sources=baidu,aliyun,telegram&page=1&size=20
```

**响应结构**（统一响应体，详见 SPEC.md §2.2）：
```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "total": 152,
    "took_ms": 234,
    "items": [
      {
        "hash": "sha256...",
        "title": "资源标题",
        "source": "baidu",
        "source_url": "https://pan.baidu.com/s/xxx",
        "size": "1.2GB",
        "status": "active",
        "first_seen": "2026-07-20T10:00:00Z",
        "last_checked": "2026-07-25T08:00:00Z"
      }
    ]
  }
}
```

---

## 5. 目录结构说明

```
jisou/
├── docs/                              # 项目文档
│   ├── PROJECT.md                     # 本文件
│   ├── SPEC.md                        # 规划 / 规范 / 开发流程
│   └── UI-DESIGN.md                   # UI 设计文档
├── src/
│   ├── backend/                       # ThinkPHP 8 后端
│   │   ├── app/
│   │   │   ├── controller/            # 控制器（api / admin）
│   │   │   ├── service/               # 业务服务（AggregatorService 等）
│   │   │   ├── provider/              # Provider 实现
│   │   │   │   ├── ProviderInterface.php
│   │   │   │   ├── SelfIndexedProvider.php
│   │   │   │   ├── RealtimeAggregatorProvider.php
│   │   │   │   └── crawler/           # 各网盘 / TG 爬虫适配器
│   │   │   ├── model/                 # 数据模型
│   │   │   ├── job/                   # 队列任务（CrawlJob / SyncJob / CheckJob / ParseJob）
│   │   │   ├── middleware/            # 鉴权 / 限流 / 日志
│   │   │   └── config/                # 配置文件
│   │   ├── route/                     # 路由
│   │   ├── config/                    # 框架配置
│   │   ├── database/
│   │   │   └── migrations/            # 数据库迁移
│   │   ├── .env.example
│   │   └── composer.json
│   └── frontend/                      # Vue3 SPA
│       ├── src/
│       │   ├── api/                   # 接口封装
│       │   ├── components/            # 通用组件
│       │   ├── views/                 # 页面（user / admin）
│       │   ├── router/
│       │   ├── stores/                # Pinia
│       │   ├── styles/
│       │   │   └── tokens.scss        # 设计 token
│       │   └── assets/
│       │       └── icons/             # 线性 SVG 图标
│       ├── public/
│       ├── package.json
│       └── vite.config.ts
├── docker/                            # Docker 配置
│   ├── docker-compose.yml
│   ├── backend.Dockerfile
│   ├── frontend.Dockerfile
│   └── meilisearch.Dockerfile
├── web-project-flow/                  # web-project-flow skill（位于 skills 分支）
└── README.md
```

---

## 6. 贡献指南

参见 [SPEC.md](./SPEC.md) §2.3 开发流程章节（分支策略、PR 流程、提交规范）。

---

## 7. 变更记录

| 版本 | 日期 | 变更 |
|---|---|---|
| 0.1.0 | 2026-07-25 | 初始版本：架构设计、功能清单、目录结构、配置规范 |
| 0.2.0 | 2026-07-25 | M0 骨架：ThinkPHP 8 后端、Vue3 前端、Provider 抽象层、错误码枚举、Docker 配置 |
