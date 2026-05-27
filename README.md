# OXP / Terraf Shell

OXP / Terraf Shell 是一个面向材料展示、内容运营、B2C 商城和社区互动的全栈系统。代码库按后端 API / 管理后台、前端用户站点、交付文档和端到端测试分层组织：

- `B2C_backend/`：Laravel 后端、REST API、Filament 管理后台、数据库迁移、Seed 数据、队列和调度任务。
- `B2C_frontend/`：Next.js 用户端站点，包含首页、材料页、商城、购物车、结账、社区、账户中心和多语言 UI。
- `docs/`：安装、部署、后台使用、用户使用、配置、存储、商城、社区、国际化、故障排查和维护文档。
- `auto_deploy.sh`：Ubuntu/Debian 单机自动化安装和部署脚本。
- `tests/`：Playwright 端到端测试。

## 技术栈

- 后端：PHP 8.3、Laravel 13、Laravel Sanctum、Filament 5。
- 前端：Next.js 16、React 19、TypeScript、Tailwind CSS 4、Radix UI、Tiptap。
- 数据库：MySQL。
- 缓存 / 队列 / Session：默认使用 Laravel database 驱动；项目依赖中包含 Predis，可按 Laravel 标准配置切换 Redis。
- 文件存储：本地 `public` disk 和 Azure Blob Storage，可在后台切换并测试。
- 构建工具：Composer、Vite、pnpm。
- 多语言：英文、中文、韩文，覆盖前端文案、后端 API 文案、验证消息和管理后台主要文案。
- 邮件：Laravel Mail，支持后台 SMTP 配置和邮件模板 / 发送日志。

## 功能总览

- 首页和页面板块：后台维护首页内容、页面 Section、品牌 Logo、联系信息和法务页面。
- 材料和内容管理：材料页、材料规格、应用场景、故事板块、文章 / CMS 内容。
- 商城：商品分类、商品、图片、动态属性、SKU / 变体、库存、库存日志、上架状态。
- 购物车和结账：游客购物车、登录用户购物车、购物车合并、Guest Checkout、登录用户结账。
- 订单：订单创建、游客订单查询、会员订单中心、订单状态、付款状态、发货信息、库存扣减和取消回补。
- 税费和配送：GST 设置、价格是否含税、手动配送费、免费配送门槛、农村附加费、NZ Post 报价配置。
- 账户：注册、登录、邮箱验证、个人资料、密码、地址簿、订单、收藏和社区记录。
- 社区：帖子、富文本内容、封面图、附件、外部 3D 链接、Funding Link、评论 / 回复、点赞、收藏、关注、举报、通知和用户限制。
- B2B / 联系：企业询盘、联系表单、样品 / 材料请求。
- 管理后台：Filament `/admin`，覆盖商城、订单、社区、举报、用户治理、内容、邮件中心、系统设置、存储设置和交付检查。
- 存储切换：本地公开存储和 Azure Blob Storage；后台提供连接测试、上传测试、Storage Link 检查和媒体扫描导出。
- 初始化数据：Seeders 提供正式初始化内容和示例运营数据；商品初始目录标记为 `is_demo_content=false`。
- 自动化安装：`auto_deploy.sh` 可在 Ubuntu/Debian 单机上安装 PHP、Composer、Node.js、pnpm、MySQL、Nginx、PHP-FPM、Supervisor、Cron，并完成代码拉取、迁移、Seed、前端构建和服务配置。

## 快速开始

### 生产 / 测试服务器自动安装

适用于全新或可控的 Ubuntu/Debian 单机服务器：

```bash
sudo bash auto_deploy.sh example.com
```

带常用参数的示例：

```bash
sudo env \
  APP_DIR=/var/www/terraf_shell \
  REPO_URL=https://github.com/xingtuan/terraf_shell.git \
  BRANCH=main \
  DB_NAME=oxp_local \
  DB_USER=oxp_user \
  DB_PASS='change-me-long-password' \
  RUN_SEED=1 \
  bash auto_deploy.sh example.com
```

脚本完成后：

- 用户前端：`http://example.com/`
- API 代理：`http://example.com/api/`
- 后端健康检查：`http://example.com:8000/up`
- 管理后台：`http://example.com:8000/admin`
- 数据库凭据和常用命令：`/root/terraf-install/credentials.txt`

如果 `RUN_SEED=1`，初始化管理员来自 `B2C_backend/database/seeders/UserSeeder.php`，默认账号为 `admin@example.com`，默认密码为 `password`。交付或上线前必须修改密码。

完整说明见 [docs/INSTALLATION.md](docs/INSTALLATION.md) 和 [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)。

### 本地开发

后端：

```bash
cd B2C_backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

前端：

```bash
cd B2C_frontend
pnpm install
cp .env.example .env.local
pnpm dev
```

本地前端默认访问 `http://localhost:3000`，API 可通过 `.env.local` 中的 `API_PROXY_TARGET=http://127.0.0.1:8000` 代理到 Laravel。

### 手动生产部署

手动部署需要完成 PHP-FPM、Nginx、MySQL、Composer、Node.js、pnpm、Supervisor、Cron、Laravel `.env`、迁移、队列、调度器、前端 build 和 systemd 服务配置。推荐先阅读 [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)，除非有明确的服务器运维要求，否则优先使用 `auto_deploy.sh`。

## 自动化安装脚本

`auto_deploy.sh` 是当前项目的主交付脚本。它要求 root / sudo 权限，面向 apt 系发行版，主要覆盖 Ubuntu 和 Debian。脚本会执行系统依赖安装、代码拉取、数据库创建、后端部署、前端构建、Nginx 配置、PHP-FPM 配置、队列和调度器配置。

首次安装：

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

重复执行：

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

已有仓库存在未提交修改时，脚本会停止并提示 `Existing repo has local modifications`。确认可以丢弃服务器本地修改后才能使用：

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

`RESET_WORKTREE=1` 会执行 `git reset --hard origin/$BRANCH` 和 `git clean -fd`，会删除目标目录中未提交、未跟踪的改动。不要在含有人工修改或上传文件的工作区中随意使用。

脚本默认 `RUN_SEED=1`，会运行 `php artisan db:seed --force`。如果只需要迁移而不重新执行 Seeder：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

脚本不会读取 `ADMIN_*`、`APP_ENV`、`APP_URL`、`STORAGE_*`、`AZURE_*` 这类自定义部署变量来改变默认部署行为。它会将后端部署为 `APP_ENV=production`、本地公开存储、database 队列和 database 缓存。上线后的品牌、邮件、商城、配送、税费、社区、Azure Storage 等设置应在后台系统设置或 `.env` 中按 [docs/CONFIGURATION.md](docs/CONFIGURATION.md) 调整。

## 环境变量

不要把真实密钥提交到仓库。`.env.example` 和 `.env.local` 只保留占位值。

- APP：`APP_NAME`、`APP_ENV`、`APP_KEY`、`APP_DEBUG`、`APP_URL`、`FRONTEND_URL`。
- DB：`DB_CONNECTION`、`DB_HOST`、`DB_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`。
- Cache / Queue / Session：`CACHE_STORE`、`QUEUE_CONNECTION`、`SESSION_DRIVER`、`SESSION_DOMAIN`、`SESSION_SECURE_COOKIE`。
- Mail：`MAIL_MAILER`、SMTP 主机、端口、账号、密码、发件人。
- Storage：`STORAGE_DISK`、`FILESYSTEM_DISK`、`MEDIA_DRIVER`、`COMMUNITY_UPLOAD_DISK`、`LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`。
- Azure：`AZURE_STORAGE_NAME`、`AZURE_STORAGE_KEY`、`AZURE_STORAGE_CONTAINER`、`AZURE_STORAGE_URL`、SAS 和临时 URL TTL 配置。
- Frontend：`NEXT_PUBLIC_API_BASE_URL`、`API_PROXY_TARGET`、`NEXT_SERVER_API_BASE_URL`、`NEXT_PUBLIC_MEDIA_BASE_URL`、`NEXT_PUBLIC_SITE_URL`、`NEXT_PUBLIC_BRAND_CONTACT_EMAIL`。
- Shop / Tax / Shipping：`STORE_GST_RATE`、`STORE_PRICES_INCLUDE_GST`、`STORE_TAX_LABEL`、`SHIPPING_*`、`NZPOST_*`。
- Community：社区分页、上传大小、附件数量、Funding Link 和审核策略相关配置。
- Security：生产环境应关闭 `APP_DEBUG`，限制 `.env` 权限，定期轮换后台账号、数据库账号、SMTP 和 Azure 密钥。

详细配置优先级见 [docs/CONFIGURATION.md](docs/CONFIGURATION.md)。

## 常用命令

后端依赖和数据库：

```bash
cd B2C_backend
composer install
php artisan migrate
php artisan db:seed
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

队列和调度器：

```bash
cd B2C_backend
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90
php artisan schedule:run
```

前端：

```bash
cd B2C_frontend
pnpm install
pnpm build
pnpm dev
pnpm lint
pnpm test
```

测试：

```bash
cd B2C_backend
php artisan test

cd ..
pnpm test
```

线上服务日志：

```bash
journalctl -u terraf-frontend -f
tail -f /var/log/nginx/error.log
tail -f B2C_backend/storage/logs/laravel.log
tail -f B2C_backend/storage/logs/queue-worker.log
```

## 文档索引

- [安装说明](docs/INSTALLATION.md)：自动化安装脚本、手动安装、本地开发和常见安装错误。
- [部署说明](docs/DEPLOYMENT.md)：生产部署、Nginx、PHP-FPM、队列、调度器、SSL 和更新流程。
- [后台使用说明](docs/ADMIN_GUIDE.md)：Filament 管理后台各模块使用方式。
- [用户使用说明](docs/USER_GUIDE.md)：用户端浏览、购物、订单、账户和社区流程。
- [系统配置](docs/CONFIGURATION.md)：`.env`、后台设置、配置优先级和缓存刷新。
- [存储说明](docs/STORAGE.md)：本地存储、Azure Storage、URL、上传路径和切换注意事项。
- [商城说明](docs/SHOP.md)：商品、SKU、库存、购物车、Checkout、GST、Shipping 和订单状态。
- [社区说明](docs/COMMUNITY.md)：帖子、附件、Funding Link、评论、收藏、举报和通知。
- [国际化说明](docs/I18N.md)：三语结构、翻译文件、验证消息和新增字段适配。
- [故障排查](docs/TROUBLESHOOTING.md)：安装、部署、数据库、权限、存储、502/404/500、队列和调度器问题。
- [维护说明](docs/MAINTENANCE.md)：日常维护、更新、备份、清缓存、重建和安全重跑脚本。

## 交付和维护

日常更新优先使用 `auto_deploy.sh` 重新部署，默认会保护已有仓库本地修改并在发现未提交改动时停止。生产环境重新执行前应先备份数据库、上传文件和 `.env`，确认 `RUN_SEED` 是否需要开启。

存储从本地切换到 Azure 前，应先在后台 Storage Settings 完成连接测试和上传测试，再检查现有媒体的访问 URL。当前后台媒体扫描支持导出和检查，实际批量迁移文件需要按导出的清单单独执行。

上线后建议配置 HTTPS、关闭不需要公开的端口、修改默认管理员密码、确认邮件发送、确认队列和调度器运行，并把数据库备份和上传文件备份纳入例行运维。
