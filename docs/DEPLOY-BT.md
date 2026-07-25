# jisou 宝塔面板部署详细教程

> 适用版本：v0.2.0+ ｜ 目标环境：宝塔面板 9.x + Linux（CentOS 7+/Ubuntu 20+/Debian 10+）
> 部署形态：单机宝塔面板，Nginx + MySQL + PHP-FPM + Redis + Meilisearch

---

## 目录

1. [前置准备](#1-前置准备)
2. [安装宝塔面板](#2-安装宝塔面板)
3. [安装运行环境](#3-安装运行环境)
4. [创建站点与数据库](#4-创建站点与数据库)
5. [部署后端代码](#5-部署后端代码)
6. [部署前端代码](#6-部署前端代码)
7. [配置 Meilisearch 搜索引擎](#7-配置-meilisearch-搜索引擎)
8. [配置 Nginx 伪静态与反向代理](#8-配置-nginx-伪静态与反向代理)
9. [配置 SSL 证书](#9-配置-ssl-证书)
10. [配置后台进程（队列与爬虫）](#10-配置后台进程队列与爬虫)
11. [配置定时任务](#11-配置定时任务)
12. [初始化数据库与首次启动](#12-初始化数据库与首次启动)
13. [健康检查与排错](#13-健康检查与排错)
14. [日常运维](#14-日常运维)

---

## 1. 前置准备

### 1.1 服务器要求

| 项目 | 最低配置 | 推荐配置 |
|---|---|---|
| CPU | 2 核 | 4 核+ |
| 内存 | 2 GB | 4 GB+ |
| 磁盘 | 40 GB | 100 GB+ SSD |
| 带宽 | 3 Mbps | 5 Mbps+ |
| 系统 | CentOS 7.9 / Ubuntu 20.04 / Debian 10 | Ubuntu 22.04 LTS |

### 1.2 必备材料

- 一个已备案域名（如 `jisou.example.com`），已解析 A 记录到服务器公网 IP
  - 域名需自行替换，本教程示例统一用 `jisou.example.com` 占位
- SSH root 或 sudo 权限
- 各 Provider 凭证（Telegram API ID/Hash、各网盘 Cookie 等，按需）

### 1.3 开放端口

在云服务商安全组与宝塔【安全】菜单中放行：

| 端口 | 用途 | 是否对外 |
|---|---|---|
| 22 | SSH | 是（建议改默认） |
| 80 | HTTP | 是 |
| 443 | HTTPS | 是 |
| 3306 | MySQL | 否（仅本机） |
| 6379 | Redis | 否（仅本机） |
| 7700 | Meilisearch | 否（仅本机，由 Nginx 反代） |

---

## 2. 安装宝塔面板

> 若已安装宝塔可跳过本节。

### 2.1 一键安装

SSH 登录服务器执行（按系统选其一）：

```bash
# Ubuntu / Debian
wget -O install.sh https://download.bt.cn/install/install-ubuntu_6.0.sh && sudo bash install.sh ed8484bec

# CentOS
wget -O install.sh https://download.bt.cn/install/install_6.0.sh && sh install.sh ed8484bec
```

安装完成后终端会输出：

```
=================================================================
外网面板地址: http://<服务器IP>:8888/<入口路径>
内网面板地址: http://<内网IP>:8888/<入口路径>
username: <账号>
password: <密码>
=================================================================
```

> **务必记录**：面板地址、账号、密码、入口路径。若忘记可通过 SSH 执行 `bt default` 查看。

### 2.2 首次登录

1. 浏览器打开外网面板地址
2. 同意用户协议
3. 绑定宝塔官网账号（免费注册）
4. 弹出【推荐环境安装】窗口，先关闭，下一步手动选 LNMP

### 2.3 安全建议

- 在【面板设置】→【安全设置】修改面板端口、用户名、密码
- 绑定 Google Authenticator 二次验证
- 限制面板仅指定 IP 访问（如服务器仅自己使用）

---

## 3. 安装运行环境

### 3.1 安装 LNMP（Nginx + MySQL + PHP）

进入宝塔【软件商店】→【运行环境】，按下表安装：

| 软件 | 版本 | 安装方式 | 说明 |
|---|---|---|---|
| Nginx | 1.24+ | 极速安装 | 反向代理 |
| MySQL | 8.0 | 编译安装 | 主存储 |
| PHP | 8.2 | 编译安装 | 后端运行时 |

> PHP 必须选 **8.2+**，ThinkPHP 8 要求 PHP 8.2+。

### 3.2 安装 Redis

宝塔【软件商店】搜索 `Redis`，安装 `7.x` 版本。

安装后：

1. 点击 Redis 行的【设置】→【配置修改】
2. 找到 `requirepass`，取消注释并设置密码：
   ```
   requirepass <在此填写强密码>
   ```
3. 找到 `bind 127.0.0.1`，确认仅监听本机（默认即可）
4. 找到 `maxmemory`，按服务器内存调整（2G 服务器设 `512mb`，4G 服务器设 `1gb`）
5. 保存并【重启】Redis

### 3.3 PHP 安装扩展

宝塔【软件商店】→ 找到 PHP 8.2 →【设置】→【安装扩展】，**必须**安装以下扩展：

| 扩展 | 必需 | 用途 |
|---|---|---|
| `fileinfo` | 是 | 资源文件类型识别 |
| `redis` | 是 | 缓存与队列 |
| `mysqli` / `pdo_mysql` | 是 | 数据库 |
| `mbstring` | 是 | 多字节字符串 |
| `openssl` | 是 | JWT / HTTPS |
| `curl` | 是 | 爬虫 HTTP 请求 |
| `bcmath` | 是 | 大整数计算（hash） |
| `gd` | 是 | 图像处理（验证码） |
| `intl` | 是 | 国际化 |
| `zip` | 是 | Composer |
| `pcntl` | 是 | 队列 worker 进程控制 |
| `posix` | 是 | 队列 worker |

### 3.4 PHP 禁用函数解除

宝塔【软件商店】→ PHP 8.2 →【设置】→【禁用函数】，**删除**以下函数（队列与命令行需要）：

```
putenv
proc_open
pcntl_signal
pcntl_alarm
pcntl_fork
shell_exec
symlink
```

### 3.5 安装 Composer

宝塔【软件商店】搜索 `Composer`，安装官方插件。

或 SSH 手动安装：

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# 配置国内镜像加速
composer config -g repo.packagist composer https://mirrors.cloud.tencent.com/composer/
```

验证：

```bash
composer --version
```

### 3.6 安装 Node.js（前端构建用）

宝塔【软件商店】搜索 `Node.js 版本管理器`，安装并切换到 Node.js 18 LTS。

或 SSH 通过 nvm 安装：

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install 18
nvm use 18

# 配置国内镜像
npm config set registry https://registry.npmmirror.com
```

### 3.7 安装 Meilisearch

Meilisearch 无宝塔官方插件，需 SSH 安装。

```bash
# 1. 安装（以 v1.6 为例）
curl -L https://install.meilisearch.com | sh

# 2. 移动到系统路径
mv ./meilisearch /usr/local/bin/meilisearch

# 3. 创建数据目录
mkdir -p /var/lib/meilisearch-data
mkdir -p /etc/meilisearch

# 4. 生成主密钥（务必保存）
openssl rand -hex 32
# 输出示例：a1b2c3... 记录为 <MEILI_MASTER_KEY>

# 5. 创建配置文件
cat > /etc/meilisearch/config.toml <<'EOF'
db_path = "/var/lib/meilisearch-data"
http_addr = "127.0.0.1:7700"
master_key = "<在此填写上一步生成的密钥>"
env = "production"
no_analytics = true
max_indexing_memory = "2GB"
EOF

# 6. 创建 systemd 服务
cat > /etc/systemd/system/meilisearch.service <<'EOF'
[Unit]
Description=Meilisearch
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/meilisearch --config-file-path /etc/meilisearch/config.toml
Restart=on-failure
RestartSec=5
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
EOF

# 7. 启动并设为开机自启
systemctl daemon-reload
systemctl enable meilisearch
systemctl start meilisearch

# 8. 验证
systemctl status meilisearch
curl http://127.0.0.1:7700/health
# 期望输出：{"status":"available"}
```

---

## 4. 创建站点与数据库

### 4.1 创建数据库

宝塔【数据库】→【添加数据库】：

| 项 | 值 |
|---|---|
| 数据库名 | `jisou` |
| 用户名 | `jisou` |
| 密码 | `<在此填写强密码>`（点击随机生成并保存） |
| 访问权限 | 本地服务器 |
| 字符集 | utf8mb4 |

### 4.2 创建后端站点

宝塔【网站】→【添加站点】：

| 项 | 值 |
|---|---|
| 域名 | `jisou.example.com` |
| 根目录 | `/www/wwwroot/jisou-api` |
| PHP 版本 | PHP-8.2 |
| 数据库 | 不创建（上一步已建） |
| 类型 | 反向代理（暂不选，下面手动配） |

> 实际部署常见两种架构：
> - **A 单域名**：后端 API 与前端同域，API 走 `/api` 路径
> - **B 双域名**：前端 `jisou.example.com` + 后端 `api.jisou.example.com`
>
> 本教程采用 **A 单域名** 方案，更省证书、配置更简单。

### 4.3 创建前端目录

```bash
mkdir -p /www/wwwroot/jisou-web
```

站点【网站】→【添加站点】：

| 项 | 值 |
|---|---|
| 域名 | `jisou.example.com` |
| 根目录 | `/www/wwwroot/jisou-web` |
| PHP 版本 | 纯静态 |

> 若同一域名已被后端占用，则把后端站点根目录改为 `/www/wwwroot/jisou-api/public`，前端目录单独建 `/www/wwwroot/jisou-web`，由 Nginx 统一代理（见 §8）。

---

## 5. 部署后端代码

### 5.1 拉取代码

```bash
cd /www/wwwroot/jisou-api
git clone https://github.com/laobi465/jisou.git tmp-repo
shopt -s dotglob
mv tmp-repo/* tmp-repo/.* . 2>/dev/null
rm -rf tmp-repo
```

> 也可以用宝塔【文件】菜单上传 zip 包解压。

### 5.2 安装 PHP 依赖

```bash
cd /www/wwwroot/jisou-api/src/backend
composer install --no-dev --optimize-autoloader
```

> 若内存不足，加参数 `--2` 限制内存使用：`COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader`

### 5.3 配置 .env

```bash
cp .env.example .env
```

编辑 `.env`（宝塔【文件】菜单可直接编辑），按下表填写：

```ini
APP_ENV = production
APP_DEBUG = false
APP_URL = https://jisou.example.com

DB_TYPE = mysql
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = jisou
DB_USER = jisou
DB_PASS = <在此填写 §4.1 数据库密码>
DB_CHARSET = utf8mb4
DB_PREFIX = jisou_

REDIS_HOST = 127.0.0.1
REDIS_PORT = 6379
REDIS_PASSWORD = <在此填写 §3.2 Redis 密码>

MEILISEARCH_HOST = http://127.0.0.1:7700
MEILISEARCH_KEY = <在此填写 §3.7 Meilisearch 主密钥>

JWT_SECRET = <在此填写随机字符串，可用 openssl rand -hex 32 生成>
JWT_ACCESS_TTL = 7200
JWT_REFRESH_TTL = 604800

# TG 爬虫凭证（启用 TG 来源时填写）
TG_API_ID = <在此填写 Telegram API ID>
TG_API_HASH = <在此填写 Telegram API Hash>
TG_SESSION = <在此填写 Telegram Session 字符串>

# 邮件
MAIL_HOST = <在此填写 SMTP 主机>
MAIL_PORT = 587
MAIL_USERNAME = <在此填写 SMTP 用户名>
MAIL_PASSWORD = <在此填写 SMTP 密码>
MAIL_FROM_ADDRESS = <在此填写发件邮箱>
MAIL_FROM_NAME = jisou

RATE_LIMIT_SEARCH = 60
RATE_LIMIT_LOGIN = 5

PARSE_MODULE_ENABLED = false

DEFAULT_LANG = zh-cn
```

### 5.4 设置目录权限

宝塔【文件】→ 进入 `/www/wwwroot/jisou-api/src/backend`，右键【权限】，所有者改为 `www:www`，递归应用。

或 SSH：

```bash
cd /www/wwwroot/jisou-api/src/backend
chown -R www:www .
chmod -R 755 .
chmod -R 775 runtime
```

### 5.5 初始化数据库

```bash
cd /www/wwwroot/jisou-api/src/backend
php think migrate:run
```

> 若 M1+ 阶段有种子数据：`php think seed:run`（当前 M0 无 seed）

---

## 6. 部署前端代码

### 6.1 本地构建（推荐）

前端在本地或构建服务器构建，上传 dist 到生产机：

```bash
cd src/frontend
npm install
# 配置 API 地址（生产环境通常同域，留空走相对路径）
echo "VITE_API_BASE_URL=" > .env.production
npm run build
# 产物在 dist/ 目录
```

> 若 `VITE_API_BASE_URL` 留空，http.ts 会因缺失变量报错。生产环境应配置为同域：
> ```ini
> # .env.production
> VITE_API_BASE_URL = https://jisou.example.com
> ```

### 6.2 上传 dist

将 `dist/` 内所有文件上传到 `/www/wwwroot/jisou-web/`。

可使用宝塔【文件】→ 压缩上传 → 解压，或 rsync：

```bash
rsync -avz --delete dist/ root@<服务器IP>:/www/wwwroot/jisou-web/
```

### 6.3 设置权限

```bash
chown -R www:www /www/wwwroot/jisou-web
chmod -R 755 /www/wwwroot/jisou-web
```

---

## 7. 配置 Meilisearch 搜索引擎

### 7.1 创建索引

```bash
# 设置环境变量便于重复执行
export MEILI_KEY='<在此填写 §3.7 主密钥>'

# 创建 resources 索引
curl -X POST http://127.0.0.1:7700/indexes \
  -H "Authorization: Bearer $MEILI_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "uid": "resources",
    "primaryKey": "url_hash"
  }'

# 配置可搜索字段
curl -X PATCH http://127.0.0.1:7700/indexes/resources/settings/searchable-attributes \
  -H "Authorization: Bearer $MEILI_KEY" \
  -H "Content-Type: application/json" \
  -d '["title", "file_type"]'

# 配置筛选字段
curl -X PATCH http://127.0.0.1:7700/indexes/resources/settings/filterable-attributes \
  -H "Authorization: Bearer $MEILI_KEY" \
  -H "Content-Type: application/json" \
  -d '["source", "status", "size_bytes", "extracted_at"]'

# 配置排序字段
curl -X PATCH http://127.0.0.1:7700/indexes/resources/settings/sortable-attributes \
  -H "Authorization: Bearer $MEILI_KEY" \
  -H "Content-Type: application/json" \
  -d '["extracted_at", "size_bytes"]'
```

### 7.2 中文分词（可选但推荐）

Meilisearch 1.6+ 内置中文分词。如需更精准，待核实：是否需要接入 jieba 分词预处理（M2 阶段验证）。

---

## 8. 配置 Nginx 伪静态与反向代理

宝塔【网站】→ 找到 `jisou.example.com` 站点 →【设置】→【配置文件】，替换为以下内容：

> 域名、路径需按实际替换。以下假设前端在 `/www/wwwroot/jisou-web`，后端入口在 `/www/wwwroot/jisou-api/src/backend/public`。

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name jisou.example.com;

    # SSL 证书（§9 配置后宝塔会自动填入）
    # ssl_certificate     /www/server/panel/vhost/cert/jisou.example.com/fullchain.pem;
    # ssl_certificate_key /www/server/panel/vhost/cert/jisou.example.com/privkey.pem;

    # ===== 前端静态资源 =====
    root /www/wwwroot/jisou-web;
    index index.html;

    # 上传大小
    client_max_body_size 20m;

    # ===== API 反向代理到 ThinkPHP =====
    location /api {
        # 转发到 PHP-FPM（ThinkPHP public/index.php）
        root /www/wwwroot/jisou-api/src/backend/public;
        try_files $uri $uri/ /index.php?$query_string;

        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # ===== 健康检查端点 =====
    location = /health {
        root /www/wwwroot/jisou-api/src/backend/public;
        try_files $uri /index.php?$query_string;
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # ===== Meilisearch 管理界面（仅内网，可选）=====
    # 默认不暴露，如需暴露请加 IP 白名单
    # location /meili/ {
    #     allow 127.0.0.1;
    #     allow <你的IP>;
    #     deny all;
    #     proxy_pass http://127.0.0.1:7700/;
    #     proxy_set_header Host $host;
    # }

    # ===== ThinkPHP 静态资源（如 captcha 图片等）=====
    location ~ ^/(static|storage)/ {
        root /www/wwwroot/jisou-api/src/backend/public;
        expires 30d;
        access_log off;
    }

    # ===== 前端 SPA 路由 fallback =====
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 禁止访问敏感文件
    location ~ /\.(env|git|htaccess) {
        deny all;
        return 404;
    }
    location ~* \.(log|sql|md)$ {
        deny all;
        return 404;
    }

    # 日志
    access_log /www/wwwlogs/jisou.example.com.log;
    error_log  /www/wwwlogs/jisou.example.com.error.log;
}
```

保存后点击【保存】，宝塔会自动 reload Nginx。

> **PHP-FPM sock 路径**：宝塔默认 `/tmp/php-cgi-82.sock`。可在【软件商店】→ PHP 8.2 →【设置】→【配置文件】查看 `listen` 字段确认。

---

## 9. 配置 SSL 证书

### 9.1 申请 Let's Encrypt 免费证书

1. 宝塔【网站】→ 站点【设置】→【SSL】
2. 选择【Let's Encrypt】
3. 勾选域名，点击【申请】
4. 申请成功后开启【强制 HTTPS】

### 9.2 已有证书

若有商业证书，选择【其他证书】→ 粘贴 `fullchain.pem` 与 `privkey.pem` → 保存 → 开启强制 HTTPS。

### 9.3 验证

浏览器访问 `https://jisou.example.com`，确认锁标志正常。

---

## 10. 配置后台进程（队列与爬虫）

队列 worker 与爬虫任务需要常驻进程。宝塔通过【软件商店】→`Supervisor管理器` 安装 Supervisor 管理。

### 10.1 安装 Supervisor

宝塔【软件商店】搜索 `Supervisor管理器`，安装。

### 10.2 添加队列 worker 守护进程

进入【Supervisor管理器】→【添加守护进程】：

| 项 | 值 |
|---|---|
| 名称 | `jisou-queue` |
| 启动用户 | `www` |
| 运行目录 | `/www/wwwroot/jisou-api/src/backend` |
| 启动命令 | `php think queue:work --tries=3 --sleep=3` |
| 进程数量 | 2（按 CPU 调整） |

> **环境变量**：Supervisor 默认不读取 `.env`，但 ThinkPHP 会自动加载 `.env`，无需额外配置。

### 10.3 添加 Meilisearch 同步 worker

| 项 | 值 |
|---|---|
| 名称 | `jisou-sync` |
| 启动用户 | `www` |
| 运行目录 | `/www/wwwroot/jisou-api/src/backend` |
| 启动命令 | `php think meili:sync` |
| 进程数量 | 1 |

> `meili:sync` 命令在 M1+ 实现后启用。当前 M0 阶段可跳过此进程。

### 10.4 添加失效检测 worker

| 项 | 值 |
|---|---|
| 名称 | `jisou-check` |
| 启动用户 | `www` |
| 运行目录 | `/www/wwwroot/jisou-api/src/backend` |
| 启动命令 | `php think check:links` |
| 进程数量 | 1 |

> M2 阶段实现后启用。

### 10.5 验证

启动后【Supervisor管理器】状态应为 `RUNNING`。日志在守护进程详情页查看。

---

## 11. 配置定时任务

宝塔【计划任务】→【添加任务】，按下表添加：

### 11.1 爬虫调度（每 10 分钟）

| 项 | 值 |
|---|---|
| 任务类型 | Shell 脚本 |
| 任务名称 | jisou-crawl-schedule |
| 执行周期 | 每 10 分钟 |
| 脚本内容 | 见下 |

```bash
#!/bin/bash
cd /www/wwwroot/jisou-api/src/backend
php think crawl:schedule >> /www/wwwlogs/jisou-crawl.log 2>&1
```

> M1+ 实现后启用，调度各爬虫任务按配置周期抓取。

### 11.2 失效检测调度（每天 03:00）

| 项 | 值 |
|---|---|
| 任务类型 | Shell 脚本 |
| 任务名称 | jisou-check-schedule |
| 执行周期 | 每天 03:00 |
| 脚本内容 | 见下 |

```bash
#!/bin/bash
cd /www/wwwroot/jisou-api/src/backend
php think check:schedule >> /www/wwwlogs/jisou-check.log 2>&1
```

### 11.3 Meilisearch 备份（每天 04:00）

| 项 | 值 |
|---|---|
| 任务类型 | Shell 脚本 |
| 任务名称 | jisou-meili-backup |
| 执行周期 | 每天 04:00 |
| 脚本内容 | 见下 |

```bash
#!/bin/bash
BACKUP_DIR=/www/backup/meili/$(date +%Y%m%d)
mkdir -p $BACKUP_DIR
curl -X POST http://127.0.0.1:7700/dumps \
  -H "Authorization: Bearer $MEILI_KEY"
# 清理 7 天前的备份
find /www/backup/meili/ -type d -mtime +7 -exec rm -rf {} \;
```

### 11.4 数据库备份（每天 05:00）

宝塔【计划任务】→【添加任务】→【任务类型】选 `数据库备份` → 选择 `jisou` 库 → 保留 7 份。

### 11.5 SSL 续签

宝塔自动续签 Let's Encrypt 证书，无需手动配置。

---

## 12. 初始化数据库与首次启动

### 12.1 运行数据库迁移

```bash
cd /www/wwwroot/jisou-api/src/backend
php think migrate:run
```

预期输出：

```
== ... Migration started ...
... Done
```

### 12.2 创建超级管理员

> M3 阶段实现用户体系后启用。当前 M0 无用户表，跳过。

待接入：M3 实现后命令为 `php think admin:create`。

### 12.3 创建 Meilisearch 索引

执行 §7.1 命令创建索引。

### 12.4 测试 API

```bash
# 健康检查
curl https://jisou.example.com/health
# 期望：{"code":0,"message":"ok","data":{"service":"jisou","status":"up"}}

# 搜索（M0 阶段无数据，应返回空结果）
curl "https://jisou.example.com/api/search?q=test&page=1&size=20"
```

### 12.5 测试前端

浏览器访问 `https://jisou.example.com`，应看到首页搜索框。

---

## 13. 健康检查与排错

### 13.1 各服务状态检查

```bash
# Nginx
systemctl status nginx

# PHP-FPM
systemctl status php-fpm-82

# MySQL
systemctl status mysqld

# Redis
systemctl status redis

# Meilisearch
systemctl status meilisearch
curl http://127.0.0.1:7700/health

# Supervisor 进程
supervisorctl status
```

### 13.2 常见问题

| 现象 | 排查 |
|---|---|
| 访问 502 Bad Gateway | PHP-FPM 未启动 / sock 路径错误。检查 Nginx 配置中 `fastcgi_pass` 与 PHP 设置中 `listen` 一致 |
| 访问 404 | 站点根目录错误 / 伪静态规则缺失。确认 root 路径与 try_files |
| 500 Internal Server Error | 查看 `/www/wwwroot/jisou-api/src/backend/runtime/log/` 日志。常见：`.env` 配置错误 / 目录权限 |
| composer install 报错 | PHP 扩展缺失。回到 §3.3 检查必需扩展 |
| 队列 worker 不工作 | 检查 Supervisor 状态 / Redis 连接 / `.env` 中 REDIS_PASSWORD |
| 搜索无结果 | Meilisearch 索引未创建 / 索引为空 / worker 未同步 |
| Meilisearch 401 | master_key 配置错误。`.env` 的 MEILISEARCH_KEY 与 `/etc/meilisearch/config.toml` 必须一致 |
| 前端 API 404 | Nginx `/api` location 配置错误 / try_files 缺失 |
| 前端刷新 404 | SPA fallback 缺失。确认 `location /` 有 `try_files $uri $uri/ /index.html` |
| 上传文件 413 | Nginx `client_max_body_size` 过小 |
| 宝塔面板无法访问 | 安全组未放行面板端口 / 面板入口路径错误。SSH 执行 `bt default` 查看 |

### 13.3 日志位置

| 服务 | 日志路径 |
|---|---|
| Nginx 访问 | `/www/wwwlogs/jisou.example.com.log` |
| Nginx 错误 | `/www/wwwlogs/jisou.example.com.error.log` |
| ThinkPHP 应用 | `/www/wwwroot/jisou-api/src/backend/runtime/log/` |
| PHP-FPM | `/var/log/php-fpm/error.log`（或宝塔 PHP 设置→日志） |
| MySQL | `/www/server/data/mysql.err` |
| Redis | `/www/server/redis/redis.log` |
| Meilisearch | `journalctl -u meilisearch -f` |
| Supervisor | 宝塔 Supervisor管理器界面 / `/var/log/supervisor/` |

---

## 14. 日常运维

### 14.1 代码更新

```bash
cd /www/wwwroot/jisou-api
git pull origin main
cd src/backend
composer install --no-dev --optimize-autoloader
php think migrate:run
chown -R www:www .
# 重启队列 worker 加载新代码
supervisorctl restart jisou-queue jisou-sync
```

### 14.2 前端更新

本地 `npm run build` → 上传 `dist/` → 覆盖 `/www/wwwroot/jisou-web/` → `chown -R www:www /www/wwwroot/jisou-web`

### 14.3 数据库备份恢复

宝塔【数据库】→ 选择 `jisou` →【备份】→【恢复】。

### 14.4 监控

- 宝塔【监控】可查看 CPU / 内存 / 磁盘 / 网络趋势
- 建议配置告警：CPU > 80% 持续 5 分钟、磁盘 > 90%、内存 > 90%
- Meilisearch 监控：`curl http://127.0.0.1:7700/stats` 查看索引大小

### 14.5 性能调优

| 场景 | 优化项 |
|---|---|
| PHP-FPM | 宝塔 PHP 设置→性能调整，`pm = dynamic`，`pm.max_children` 按 `内存/80` 估算 |
| MySQL | 启用 InnoDB buffer pool，`innodb_buffer_pool_size = 内存*60%` |
| Redis | `maxmemory-policy = allkeys-lru` |
| Meilisearch | `max_indexing_memory` 不超过内存 50% |
| OpCache | 宝塔 PHP 设置→安装 opcache，启用后 PHP 性能提升 30%+ |

### 14.6 安全加固

- 定期更新宝塔面板与各软件（宝塔【首页】→【更新】）
- 关闭不必要端口
- MySQL / Redis 仅监听 127.0.0.1
- 配置 fail2ban 防暴力破解（宝塔【软件商店】可装）
- 定期审计 `.env` 权限：`chmod 600 /www/wwwroot/jisou-api/src/backend/.env`

---

## 附录 A：完整目录结构（部署后）

```
/www/wwwroot/
├── jisou-api/                          # 后端仓库根
│   ├── docs/                           # 项目文档
│   ├── src/backend/                    # ThinkPHP 8
│   │   ├── app/
│   │   ├── public/                     # Nginx root 指向此
│   │   ├── runtime/                    # 日志与缓存（需可写）
│   │   ├── .env                        # 配置（chmod 600）
│   │   └── composer.json
│   ├── web-project-flow/               # skill（skills 分支）
│   └── README.md
└── jisou-web/                          # 前端静态产物
    ├── index.html
    ├── assets/
    └── favicon.svg

/etc/meilisearch/
└── config.toml

/var/lib/meilisearch-data/              # Meilisearch 数据

/www/backup/
├── meili/                              # Meilisearch 备份
└── database/                           # 数据库备份（宝塔默认路径）

/www/wwwlogs/
├── jisou.example.com.log               # Nginx 访问日志
├── jisou.example.com.error.log         # Nginx 错误日志
├── jisou-crawl.log                     # 爬虫调度日志
└── jisou-check.log                     # 失效检测日志
```

---

## 附录 B：一键部署检查清单

部署完成后逐项确认：

- [ ] 宝塔面板已安装且能登录
- [ ] Nginx 1.24+ 已安装
- [ ] MySQL 8.0 已安装，jisou 库已创建
- [ ] PHP 8.2 已安装，必需扩展全部启用
- [ ] PHP 禁用函数已解除
- [ ] Redis 7 已安装并设置密码
- [ ] Meilisearch 1.6 已安装并以 systemd 服务运行
- [ ] 后端代码已拉取到 `/www/wwwroot/jisou-api`
- [ ] `composer install` 成功
- [ ] `.env` 已配置且权限 600
- [ ] 目录权限 `www:www` 已设置
- [ ] 数据库迁移已执行
- [ ] Meilisearch 索引已创建
- [ ] 前端已构建并上传到 `/www/wwwroot/jisou-web`
- [ ] Nginx 配置已写入并 reload
- [ ] SSL 证书已申请并强制 HTTPS
- [ ] Supervisor 守护进程全部 RUNNING
- [ ] 计划任务已添加
- [ ] `curl https://域名/health` 返回 ok
- [ ] 浏览器访问首页正常
- [ ] 搜索 API 返回正常（即使空结果）

---

## 附录 C：版本与待核实项

| 项 | 状态 | 说明 |
|---|---|---|
| 宝塔面板版本 | 9.x | 8.x 也可，菜单路径略不同 |
| Meilisearch 安装方式 | 待核实 | 1.6 为目标版本，实际安装时确认 install.sh 拉取版本号 |
| ThinkPHP migrate 命令 | 待核实 | M1 实现 migrations 后验证 `php think migrate:run` 输出 |
| Meilisearch 中文分词 | 待核实 | 默认分词够用性需 M2 阶段实测验证 |
| `php think admin:create` | 待接入 | M3 用户体系实现后提供 |
| `php think meili:sync` | 待接入 | M1 Meilisearch 同步 worker 实现后提供 |
| `php think crawl:schedule` | 待接入 | M2 爬虫调度实现后提供 |
