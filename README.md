# jisou

聚合搜索引擎 —— 统一搜索各大网盘与 Telegram 公开频道的资源分享链接。

## 文档

- [PROJECT.md](docs/PROJECT.md) — 项目概述、架构总览、功能清单、使用指南
- [SPEC.md](docs/SPEC.md) — 里程碑、技术规范、错误码枚举、开发流程
- [UI-DESIGN.md](docs/UI-DESIGN.md) — UI 设计规则、色彩/组件/排版 token

## 技术栈

- 后端：ThinkPHP 8 + PHP 8.2+
- 前端：Vue 3 + Vite + TypeScript + Pinia + Element Plus
- 存储：MySQL 8 + Meilisearch + Redis
- 部署：Docker Compose

## 目录结构

```
jisou/
├── docs/              # 项目文档
├── src/
│   ├── backend/       # ThinkPHP 8 后端
│   └── frontend/      # Vue3 SPA
├── docker/            # Docker 配置
└── README.md
```

## 快速开始

详见 [PROJECT.md §4 使用指南](docs/PROJECT.md)。

## 开发规范

详见 [SPEC.md](docs/SPEC.md)。
