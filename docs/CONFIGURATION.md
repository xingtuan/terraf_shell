# 系统配置说明

系统配置来源分为两类：

- `.env`：启动期配置、基础设施配置和敏感密钥。
- 后台 System Settings：运行时业务配置，保存到数据库并通过 `SettingsService` 缓存。

以当前代码为准，后台设置会覆盖一部分 `.env` 默认值；但数据库连接、APP_KEY、CORS、Sanctum、基础队列和缓存驱动仍应在 `.env` 中配置。

## 配置优先级

1. Laravel 启动必须依赖 `.env` 的配置先完成 bootstrap。
2. 数据库可用后，`RuntimeSettingsServiceProvider` 会读取 `app_settings`。
3. 应用、存储、邮件、商城、配送、GST、NZ Post、社区、Feature Flags 等可由后台设置覆盖。
4. 设置会被缓存，修改后如未立即生效，需要清理缓存。

清理缓存：

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
```

## APP 配置

`.env` 中的关键项：

```dotenv
APP_NAME=OXP
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://api.example.com
FRONTEND_URL=https://example.com
```

说明：

- `APP_KEY` 必须生成并长期保留，否则加密字段、session 和部分 token 会失效。
- 生产环境必须 `APP_DEBUG=false`。
- `APP_URL` 影响后端 URL、storage URL 和邮件链接。
- `FRONTEND_URL` 影响 CORS、邮件链接和前端跳转。

后台 Application Settings 可维护站点名称、Logo、默认语言、联系邮箱、支持邮箱和前端 URL 等业务展示配置。

## 数据库

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oxp_local
DB_USERNAME=oxp_user
DB_PASSWORD=change-me
```

数据库配置只能通过 `.env` 修改。修改后需要重启相关服务并清理配置缓存：

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
sudo supervisorctl restart terraf-queue:*
```

## Cache / Queue / Session

自动部署默认：

```dotenv
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

数据库驱动适合单机交付和中小规模部署。需要 Redis 时，可按 Laravel 标准配置 Redis，并确保队列 worker、缓存和 session 同步切换。

队列配置改变后必须重启 worker：

```bash
sudo supervisorctl restart terraf-queue:*
```

## Mail

默认 `.env.example` 使用 log mailer，自动部署也会设置：

```dotenv
MAIL_MAILER=log
```

生产环境应在后台 Email Settings 或 `.env` 中配置 SMTP。后台 Email Center 支持模板、事件、预览和测试发送。

常用检查：

```bash
cd B2C_backend
php artisan email:center:preview order.created --locale=en
php artisan email:center:test admin@example.com
```

## Storage

关键变量：

```dotenv
STORAGE_DISK=public
FILESYSTEM_DISK=public
MEDIA_DRIVER=public
COMMUNITY_UPLOAD_DISK=public
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public
```

Azure 变量：

```dotenv
AZURE_STORAGE_NAME=
AZURE_STORAGE_KEY=
AZURE_STORAGE_CONTAINER=
AZURE_STORAGE_URL=
```

自动部署默认使用本地 `public` storage。后台 Storage Settings 可以切换 local / Azure，并执行连接测试和上传测试。详细说明见 [STORAGE.md](STORAGE.md)。

## Frontend

前端 `.env.local`：

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
API_PROXY_TARGET=http://127.0.0.1:8000
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_BRAND_CONTACT_EMAIL=
NEXT_PUBLIC_SITE_URL=https://example.com
```

说明：

- `NEXT_PUBLIC_API_BASE_URL=/api` 让浏览器请求走 Next / Nginx 代理。
- `API_PROXY_TARGET` 由 Next rewrites 使用，开发环境通常指向 Laravel。
- `NEXT_SERVER_API_BASE_URL` 供 Next 服务端渲染和 build 阶段访问 Laravel API。
- `NEXT_PUBLIC_MEDIA_BASE_URL` 留空时使用后端返回的媒体 URL。
- 修改前端环境变量后需要重新 `pnpm build` 并重启前端服务。

## Admin

当前自动化脚本不支持通过 `ADMIN_*` 环境变量创建管理员。管理员来源有三种：

- `RUN_SEED=1` 时由 `UserSeeder` 创建默认管理员。
- 手动使用 Web Installer 创建管理员。
- 已有管理员登录后台创建或维护用户。

默认管理员仅用于初始化，生产交付必须修改密码。

## Shop / Tax / Shipping

商城设置分为 `.env` fallback 和后台运行时设置：

- Tax Settings：GST 是否启用、税率、价格是否含税、税费标签。
- Shipping Settings：NZ-only、发货地、免费配送门槛、标准 / 加急 / 农村费用、报价来源。
- NZ Post Settings：NZ Post 客户号、API Key、API Secret、服务代码。

相关 `.env` fallback 包括：

```dotenv
STORE_GST_RATE=0.15
STORE_PRICES_INCLUDE_GST=false
STORE_TAX_LABEL=GST
NZPOST_ENABLED=false
```

后台设置保存后优先生效。

## Community

社区配置覆盖：

- 分页大小。
- 上传文件数量、大小、扩展名和 MIME。
- Funding Link 支持。
- 审核策略。
- 敏感词。
- Guest upload 开关。
- B2B / 社区通知收件人。

部分上传限制来自 `config/community.php` 和后台设置共同决定。修改上传限制后应测试发帖、封面图和附件上传。

## Security

生产环境必须确认：

- `APP_DEBUG=false`
- `.env` 不可公开访问，权限限制为服务用户可读。
- 默认管理员密码已修改。
- 数据库用户只授予应用所需数据库权限。
- SMTP、Azure、数据库和后台管理员密钥定期轮换。
- HTTPS 已配置，`APP_URL`、`FRONTEND_URL`、`NEXT_PUBLIC_SITE_URL`、CORS 和 session secure 设置与 HTTPS 一致。

## 缓存刷新方式

修改 `.env` 后：

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
sudo supervisorctl restart terraf-queue:*
sudo systemctl restart terraf-frontend
```

修改后台运行时设置后，一般清理应用缓存即可：

```bash
cd B2C_backend
php artisan optimize:clear
```

修改前端 `.env.local` 后：

```bash
cd B2C_frontend
pnpm build
sudo systemctl restart terraf-frontend
```
