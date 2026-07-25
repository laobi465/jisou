# jisou 规划 / 规范 / 开发流程文档（SPEC.md）

> 版本：0.1.0 ｜ 最后更新：2026-07-25
> 维护规范：web-project-flow /bdocs (references/09-docs-lifecycle.md)
> 配套文档：[PROJECT.md](./PROJECT.md) ｜ [UI-DESIGN.md](./UI-DESIGN.md)

---

## 第一部分：项目规划（Plan）

### 1.1 里程碑划分

| 里程碑 | 阶段目标 | 主要交付物 | 状态 |
|---|---|---|---|
| M0 基础设施 | 项目骨架、CI、Docker、文档体系 | 仓库结构 / Docker Compose / PROJECT+SPEC+UI 文档 | [规划] |
| M1 Provider 抽象 + 单源打通 | 抽象层 + 1 个网盘爬虫 + 搜索可用 | ProviderInterface / SelfIndexedProvider / 1 个爬虫 / 搜索 API | [规划] |
| M2 多源聚合 + 失效检测 | 接入 4 个国内主流网盘 + TG 索引 + 失效检测 worker | 4 网盘爬虫 / RealtimeAggregatorProvider / CheckJob | [规划] |
| M3 用户体系 + 社区 | 注册登录 / 收藏 / 历史 / 举报 | 用户模块 / 收藏历史举报 API | [规划] |
| M4 超管后台 | Provider / 爬虫 / 资源 / 黑名单管理 | 后台 SPA / 后台 API | [规划] |
| M5 解析模块 | 按来源提供真实下载链接 | ParseJob / 各网盘解析适配器 | [规划] |
| M6 二线盘 + 国际盘 | 扩展数据源覆盖 | 二线 / 国际网盘适配器 | [规划] |
| M7 上线交付 | 性能优化、监控、交接文档 | 性能报告 / README / PROMPT.md | [规划] |

### 1.2 版本路线图

| 版本 | 范围 | 对应里程碑 |
|---|---|---|
| 0.1.0 | 文档体系（PROJECT + SPEC + UI 设计） | M0 |
| 0.2.0 | Provider 抽象 + 百度网盘单源打通 | M1 |
| 0.3.0 | 4 国内主流网盘 + TG 索引聚合 | M2 |
| 0.4.0 | 用户体系 + 收藏 / 历史 / 举报 | M3 |
| 0.5.0 | 超管后台 MVP | M4 |
| 0.6.0 | 解析模块（按来源开关） | M5 |
| 0.7.0 | 二线盘 + 国际盘扩展 | M6 |
| 1.0.0 | 性能优化 + 监控 + 上线 | M7 |

### 1.3 功能优先级与排期

| 优先级 | 功能 | 依赖 |
|---|---|---|
| P0 | Provider 抽象层 + SelfIndexedProvider | M0 |
| P0 | Meilisearch 索引 + 同步 worker | M0 |
| P0 | 1 个网盘爬虫 + 搜索 API | M1 |
| P1 | 4 国内主流网盘爬虫 | M2 |
| P1 | TG 频道爬虫 + RealtimeAggregatorProvider | M2 |
| P1 | 失效检测 worker | M2 |
| P2 | 用户注册登录 | M3 |
| P2 | 收藏 / 历史 / 举报 | M3 |
| P2 | 超管后台 | M4 |
| P3 | 解析模块 | M5 |
| P3 | 二线盘 + 国际盘 | M6 |

### 1.4 风险与依赖清单

| 风险 / 依赖 | 等级 | 应对 |
|---|---|---|
| 各网盘反爬升级导致爬虫失效 | 高 | 每源独立适配器，单源失效不影响整体；接入 RealtimeAggregatorProvider 兜底 |
| TG API 限流 / 频道封禁 | 中 | 速率控制 + 多账号轮换 + 断点续抓 |
| 解析模块合规风险 | 高 | 解析作为可选 capability，超管后台一键全局关闭 |
| Meilisearch 单机容量上限 | 中 | 预留分片方案；监控索引大小 |
| DMCA / 版权投诉 | 中 | 黑名单 + 举报 + 审核流程 |
| 第三方索引 API 不稳定 | 中 | 熔断 + 降级到自建索引 |

---

## 第二部分：技术规范（Specification）

### 2.1 代码规范

#### 2.1.1 PHP（后端）

- PHP 8.2+，严格模式 `declare(strict_types=1)`
- PSR-12 代码风格
- 命名：类 PascalCase / 方法与变量 camelCase / 常量 UPPER_SNAKE / 类私有成员以 `_` 开头不推荐，统一用 `private` 修饰
- 类型声明：所有方法参数与返回值必须显式类型
- 注释：类与公共方法用 PHPDoc，复杂逻辑用行内注释，禁止注释废话
- 文件命名：类文件 PascalCase.php，与类名一致

#### 2.1.2 TypeScript / Vue3（前端）

- TypeScript strict mode
- 组件文件 PascalCase.vue
- 组合式 API `<script setup lang="ts">`
- 命名：组件 PascalCase / 变量与函数 camelCase / 常量 UPPER_SNAKE / 类型 PascalCase
- 样式：scoped + 设计 token（来自 `styles/tokens.scss`），禁止在组件内硬编码色值

#### 2.1.3 数据库命名

- 表名：snake_case 复数（如 `resources`、`crawl_tasks`）
- 字段：snake_case（如 `source_url`、`last_checked`）
- 主键：`id` BIGINT UNSIGNED AUTO_INCREMENT
- 时间字段：`created_at` / `updated_at` DATETIME
- 索引命名：`idx_表名_字段` / `uniq_表名_字段`

### 2.2 架构规范

#### 2.2.1 分层原则

```
Controller (route 入口，仅做参数校验与响应)
    ↓
Service (业务编排，聚合多个 Provider / Model)
    ↓
Provider / Model (数据来源与持久化)
    ↓
Database / Search Engine / Cache
```

- Controller 不写业务逻辑
- Service 不直接操作数据库表，通过 Model
- Provider 不持有状态，所有状态写入数据库
- 跨层调用禁止（Controller 不能直接调 Provider）

#### 2.2.2 Provider 抽象

```php
interface ProviderInterface {
    public function search(Query $q): ResultSet;
    public function fetch(string $url): ResourceMeta;
    public function parse(string $url): ?DownloadUrl;   // 可选能力
    public function capabilities(): Capabilities;
    public function health(): HealthStatus;
}
```

- 三类实现：SelfIndexedProvider / RealtimeAggregatorProvider / CrawlerProvider
- CrawlerProvider 不响应 `search()`，仅生产数据入库
- 每个 Provider 实现独立配置类，从 `provider_configs` 表读取

#### 2.2.3 模块边界

| 模块 | 边界 |
|---|---|
| AggregatorService | 编排多 Provider，去重排序，不实现具体抓取 |
| Provider | 单一数据源适配，不与其他 Provider 通信 |
| Crawler | 仅抓取入库，不响应查询 |
| 失效检测 worker | 仅更新状态，不修改其他字段 |
| 解析 worker | 仅返回解析结果，不修改 resources 主表 |

### 2.3 接口规范

#### 2.3.1 API 风格

- RESTful，前缀 `/api`
- 后台接口前缀 `/api/admin`，需超管鉴权中间件
- 请求与响应统一 JSON

#### 2.3.2 统一响应体

```json
{
  "code": 0,
  "message": "ok",
  "data": { ... }
}
```

错误响应：

```json
{
  "code": 4001,
  "message": "参数错误：q 不能为空",
  "data": null
}
```

#### 2.3.3 错误码枚举

> 所有错误码必须沿用此枚举，禁止自创状态码

| 区间 | 含义 |
|---|---|
| 0 | 成功 |
| 1000-1999 | 鉴权类（1001 未登录 / 1002 token 失效 / 1003 权限不足） |
| 2000-2999 | 参数类（2001 参数缺失 / 2002 参数格式错误 / 2003 参数越界） |
| 3000-3999 | 资源类（3001 资源不存在 / 3002 资源已失效 / 3003 资源已收藏） |
| 4000-4999 | 业务类（4001 搜索词过短 / 4002 来源未启用 / 4003 解析失败 / 4004 解析模块已关闭） |
| 5000-5999 | Provider 类（5001 Provider 不健康 / 5002 Provider 超时 / 5003 Provider 限流） |
| 6000-6999 | 爬虫类（6001 任务不存在 / 6002 任务已暂停 / 6003 抓取失败） |
| 9000-9999 | 系统类（9001 数据库错误 / 9002 缓存错误 / 9003 搜索引擎错误 / 9999 未知错误） |

#### 2.3.4 分页约定

- 入参：`page`（默认 1） / `size`（默认 20，最大 50）
- 出参：`{ total, page, size, items }`

#### 2.3.5 核心接口清单（首期）

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | /api/search | 聚合搜索 |
| GET | /api/resource/:hash | 资源详情 |
| POST | /api/resource/:hash/parse | 触发解析（如该来源支持） |
| POST | /api/auth/register | 注册 |
| POST | /api/auth/login | 登录 |
| POST | /api/favorites | 收藏 |
| GET | /api/favorites | 收藏列表 |
| DELETE | /api/favorites/:id | 取消收藏 |
| GET | /api/history | 搜索历史 |
| DELETE | /api/history | 清除历史 |
| POST | /api/reports | 举报 |
| GET | /api/admin/dashboard | 仪表盘 |
| GET | /api/admin/providers | Provider 列表 |
| PUT | /api/admin/providers/:id | 更新 Provider 配置 |
| POST | /api/admin/providers/:id/test | 测试连接 |
| GET | /api/admin/crawl | 爬虫任务列表 |
| POST | /api/admin/crawl | 创建任务 |
| POST | /api/admin/crawl/:id/run | 立即执行 |
| GET | /api/admin/resources | 资源审核列表 |
| PUT | /api/admin/resources/:id | 隐藏 / 删除 |
| GET | /api/admin/blacklist | 黑名单 |
| POST | /api/admin/blacklist | 加入黑名单 |

### 2.4 提交规范

#### 2.4.1 Commit Message

Conventional Commits：

```
<type>(<scope>): <subject>

<body>
```

- type：feat / fix / docs / style / refactor / perf / test / chore / build / ci
- scope：模块名（如 `provider` / `crawler` / `api` / `admin` / `frontend` / `docs`）
- subject：祈使句，不超过 50 字

示例：
```
feat(provider): 实现百度网盘爬虫适配器
fix(search): 修复多源聚合时去重 hash 不稳定
docs(spec): 补充错误码枚举区间
```

#### 2.4.2 分支策略

- `main`：生产分支，受保护，仅通过 PR 合入
- `develop`：开发集成分支
- `feature/<scope>-<short-desc>`：功能分支，从 develop 切出
- `fix/<scope>-<short-desc>`：修复分支
- `release/<version>`：发布分支

#### 2.4.3 PR 规则

- 必须关联 issue
- 至少 1 人 review 通过
- CI 全绿（lint + 单元测试 + 构建）
- 必须更新 PROJECT.md / SPEC.md（如涉及功能 / 规范变更）
- 合并方式：squash merge

### 2.5 测试规范

#### 2.5.1 覆盖率要求

- Service 层：>= 80%
- Provider 适配器：>= 70%
- 工具类：>= 90%
- Controller：>= 60%

#### 2.5.2 测试分类

- 单元测试：PHPUnit（后端）/ Vitest（前端）
- 集成测试：Provider 与数据库 / Meilisearch 交互
- E2E：搜索主流程（待核实：M7 阶段引入 Playwright）

#### 2.5.3 测试数据

- 测试数据必须明确标注 `// 仅本地测试模拟`
- 禁止使用真实网盘 token / 真实用户数据
- 测试 fixture 与生产代码分离

### 2.6 安全规范

- 输入校验：所有 API 入参在 Controller 层校验类型 / 长度 / 范围
- 鉴权：JWT，access token 2h / refresh token 7d
- 限流：搜索 API 单 IP 60 次 / 分钟，登录 5 次 / 分钟
- 密码：bcrypt，cost=12
- 敏感信息：所有密钥 / token / 凭证走 `.env` 或 `provider_configs` 表，禁止入仓库
- SQL 注入：使用 ORM 参数绑定，禁止拼接 SQL
- XSS：前端输出统一转义，禁止 v-html 直接渲染用户内容
- CSRF：使用 SameSite Cookie + token
- 文件下载：解析模块返回的下载链接必须经过签名校验

### 2.7 配置规范

- 所有可变值走 `.env`（部署期）或 `provider_configs` 表（运行期）
- `.env.example` 必须使用 `<在此填写 XXX>` 形式，禁止示例占位值如 `your_api_key_here`
- 严禁硬编码：密钥 / token / 域名 / IP / 接口地址 / 限流数值 / 倍率 / 定价
- 站点域名、邮件配置、解析开关、限流参数均由超管后台动态配置

---

## 第三部分：开发流程（Workflow）

### 3.1 分支策略

参见 §2.4.2

### 3.2 PR / MR 流程

1. 从 `develop` 切出 `feature/<scope>-<short-desc>`
2. 开发 + 自测 + 单元测试
3. 提交 PR 到 `develop`，填写 PR 模板（变更说明 / 关联 issue / 自检清单）
4. 至少 1 人 review
5. CI 全绿
6. squash merge
7. 删除源分支

#### PR 自检清单

- [ ] 关联 issue
- [ ] 单元测试通过
- [ ] 新增功能有测试覆盖
- [ ] 未硬编码任何密钥 / 域名 / 接口地址
- [ ] 未使用占位符（TODO / pass / Lorem Ipsum）
- [ ] 错误码沿用项目枚举
- [ ] 命名 / 分层 / 注释风格与现有源码一致
- [ ] 涉及功能 / 规范变更已更新 PROJECT.md / SPEC.md

### 3.3 发布流程

#### 3.3.1 版本号递增

- 重大功能 / 破坏性变更 → 主版本号 +1（X.0.0）
- 新增功能 / 向下兼容 → 次版本号 +1（0.X.0）
- 修复 / 小优化 → 修订号 +1（0.0.X）

#### 3.3.2 发版检查清单

- [ ] 所有 P0 / P1 bug 已修复
- [ ] CI 全绿
- [ ] 文档已更新（PROJECT / SPEC / README）
- [ ] 数据库迁移脚本可逆
- [ ] 回滚方案已准备
- [ ] 监控告警已配置
- [ ] 超管后台可关闭新功能（解析模块等）

#### 3.3.3 回滚方案

- 数据库迁移：每个 migration 必须有对应 rollback
- 部署：保留上一版本镜像，可一键切换
- 索引：Meilisearch 索引版本化，回滚不影响搜索

### 3.4 CI/CD 流程

#### CI（Pull Request 触发）

1. PHP lint（php-cs-fixer check）
2. PHP 单元测试（PHPUnit）
3. 前端 lint（eslint + tsc）
4. 前端单元测试（Vitest）
5. 构建前端（vite build）
6. 安全扫描（待核实：M7 阶段引入）

#### CD（合并到 main 触发）

1. 构建后端镜像 + 前端镜像
2. 推送镜像仓库
3. SSH 到生产机，docker compose pull + up
4. 运行数据库迁移
5. 重启 queue worker
6. 健康检查

### 3.5 协作流程

- 任务认领：在 issue 列表认领，指派自己
- 进度同步：每日站会 + issue 状态更新（todo / in progress / done）
- 交接规范：参见 web-project-flow /bhandover (references/10)
- 文档同步：任何变更必须按 §2.4 同步 PROJECT + SPEC

---

## 第四部分：合规校验与变更记录

### 4.1 文档联动校验

- PROJECT 功能清单与 SPEC 接口清单一致
- SPEC 里程碑与 PROJECT 功能清单对应
- 架构变更同步更新 PROJECT §2 与 SPEC §2.2
- 移除功能同时从 PROJECT 功能清单删除并在 SPEC 路线图标"已移除"

### 4.2 铁律自检清单

| 校验项 | 状态 |
|---|---|
| 文档内无硬编码密钥 / token / 域名（均用 `<在此填写>`） | 通过 |
| 文档内无占位符（TODO / pass / Lorem Ipsum / your_api_key_here） | 通过 |
| 错误码使用统一枚举区间 | 通过 |
| 接口路径与功能清单可追溯 | 通过 |
| 里程碑与版本路线图一一对应 | 通过 |
| 配置项全部走 .env 或数据库 | 通过 |
| 不存疑信息无标注（待核实项已标注） | 通过 |

### 4.3 变更记录

| 版本 | 日期 | 变更 |
|---|---|---|
| 0.1.0 | 2026-07-25 | 初始版本：里程碑 / 路线图 / 技术规范 / 开发流程 / 错误码枚举 |
