# jisou

聚合搜索引擎 —— 统一搜索各大网盘与 Telegram 公开频道的资源分享链接。

## 文档

- [PROJECT.md](docs/PROJECT.md) — 项目概述、架构总览、功能清单、使用指南
- [SPEC.md](docs/SPEC.md) — 里程碑、技术规范、错误码枚举、开发流程
- [UI-DESIGN.md](docs/UI-DESIGN.md) — UI 设计规则、色彩/组件/排版 token
- [DEPLOY-BT.md](docs/DEPLOY-BT.md) — 宝塔面板部署详细教程

## 技术栈

- 后端：ThinkPHP 8 + PHP 8.2+
- 前端：Vue 3 + Vite + TypeScript + Pinia + Element Plus
- 存储：MySQL 8 + Meilisearch + Redis
- 部署：宝塔面板 + Supervisor

## 目录结构

```
jisou/
├── docs/              # 项目文档
├── src/
│   ├── backend/       # ThinkPHP 8 后端
│   └── frontend/      # Vue3 SPA
└── README.md
```

## 快速开始

### 本地开发

详见 [PROJECT.md §4.3 本地开发](docs/PROJECT.md)。

### 生产部署

采用宝塔面板部署，详见 [DEPLOY-BT.md](docs/DEPLOY-BT.md)。

## 开发规范

详见 [SPEC.md](docs/SPEC.md)。
