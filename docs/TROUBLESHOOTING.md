# 故障排查

本文按安装、部署、数据库、权限、storage、商城、社区、多语言、HTTP 错误、队列和调度器整理常见问题。

## 快速定位

常用日志：

```bash
tail -f B2C_backend/storage/logs/laravel.log
tail -f B2C_backend/storage/logs/queue-worker.log
sudo journalctl -u terraf-frontend -f
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

健康检查：

```bash
curl -I http://127.0.0.1:8000/up
curl -I http://127.0.0.1:3000/
curl -I http://your-domain/api/cart
```

## 安装问题

### Existing repo has local modifications

`auto_deploy.sh` 发现部署目录已有本地修改。处理方式：

```bash
git -C /var/www/terraf_shell status --short
```

需要保留时先备份、提交或 stash。确认可以丢弃时：

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

`RESET_WORKTREE=1` 会丢弃未提交修改并删除未跟踪文件。

### Composer 安装失败

检查：

```bash
php -v
php -m
cd B2C_backend
composer validate
composer install --no-dev --optimize-autoloader --no-interaction
```

生产服务器不要运行 `composer update`。如果 `composer.lock` 缺失或不匹配，应在开发环境修复后提交。

### Node / pnpm 版本问题

自动脚本默认 Node.js 20。检查：

```bash
node -v
pnpm -v
cd B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
```

如果 build 阶段访问 API 失败，检查 `NEXT_SERVER_API_BASE_URL` 是否指向可访问的 Laravel API。

### MySQL 连接失败

```bash
sudo systemctl status mysql
mysql -u oxp_user -p oxp_local
cd B2C_backend
php artisan migrate:status
```

确认 `.env` 中的 `DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD` 与数据库实际一致。

### Migration 失败

查看完整错误，不要手工改表绕过迁移。常见原因：

- 数据库不是当前分支对应状态。
- 上一次迁移部分失败。
- `.env` 指向了错误数据库。
- MySQL 权限不足。

修复后重新执行：

```bash
cd B2C_backend
php artisan migrate --force
```

### Seeder 造成重复或覆盖疑虑

自动部署默认 `RUN_SEED=1`。正式上线后的重复部署建议：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

生产环境执行 Seeder 前必须确认 Seeder 是否会创建、更新或覆盖已有运营数据。

## 部署问题

### Nginx 站点不生效

```bash
sudo nginx -t
ls -l /etc/nginx/sites-enabled/
sudo systemctl status nginx
sudo tail -f /var/log/nginx/error.log
```

当前自动脚本应启用 `front` 和 `laravel` 两个站点。

### PHP-FPM 未启动

```bash
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

确认 Nginx 站点中的 PHP-FPM socket 与实际 PHP 版本一致。

### 前端服务未启动

```bash
sudo systemctl status terraf-frontend
sudo journalctl -u terraf-frontend -f
cd B2C_frontend
pnpm build
```

确认 `.env.local` 存在并包含：

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_SITE_URL=http://your-domain-or-ip
```

### 前端 build 失败

常见原因：

- 缺少 Node.js 20 或 pnpm。
- i18n key 不一致。
- 构建期 API 地址不可访问。
- TypeScript 类型错误。

检查：

```bash
cd B2C_frontend
node scripts/check-i18n-keys.mjs
node scripts/i18n-diff.mjs
pnpm build
```

## 权限问题

Laravel 需要写入：

```text
B2C_backend/storage
B2C_backend/bootstrap/cache
```

修复：

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo chmod -R ug+rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -R -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -dR -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
```

## Storage 问题

### 上传文件不能显示

检查本地 storage：

```bash
cd B2C_backend
php artisan storage:link
ls -l public/storage
```

检查 Azure：

- 后台 Storage Settings 连接测试。
- 上传测试。
- 容器名、密钥、base URL。
- SAS URL 是否过期。

### Local / Azure 切换后文件丢失

切换 driver 不会自动迁移历史文件。需要把历史文件复制到新 storage 对应路径，或保持旧 storage 可访问。

### Livewire 上传失败

检查 `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`、PHP `upload_max_filesize`、Nginx body size、storage 权限和社区上传限制。

## 商城问题

### 购物车加入失败

检查商品、变体、SKU、库存策略和库存数量。商品或变体停用时前端不能购买。

### Checkout 失败

检查：

- Guest Checkout Feature Flag。
- 邮箱和地址字段。
- 配送选项是否返回。
- 商品库存是否足够。
- Laravel 日志。

### GST 不生效

检查后台 Tax Settings，保存后清缓存：

```bash
cd B2C_backend
php artisan optimize:clear
```

### Shipping 不生效

检查 Shipping Settings 和 NZ Post Settings。`auto` 报价会优先尝试 NZ Post，失败后回退到手动费率。

### 订单查询失败

游客订单需要 guest token。登录用户只能查询自己的订单。后台可通过订单模块查找订单并确认邮箱、token 和状态。

## 后台问题

### Admin 保存失败

检查：

- 当前用户权限。
- 表单验证错误。
- storage 写入权限。
- `.env` 和运行时设置是否冲突。
- `laravel.log`。

### 后台设置保存后不生效

清理缓存：

```bash
cd B2C_backend
php artisan optimize:clear
```

队列 / 前端相关设置还需要重启对应服务或重新 build。

## 多语言问题

前端检查：

```bash
cd B2C_frontend
node scripts/check-i18n-keys.mjs
node scripts/i18n-diff.mjs
```

后台检查：

```bash
cd B2C_backend
php artisan admin:check-translations
```

如果某语言仍显示英文，检查是否缺少翻译 key，或代码中存在硬编码文案。

## HTTP 错误

### 502

常见原因是 upstream 服务未运行：

```bash
sudo systemctl status terraf-frontend
curl -I http://127.0.0.1:3000/
curl -I http://127.0.0.1:8000/up
sudo systemctl status php8.3-fpm
```

### 404

检查：

- Nginx server_name。
- 前端路由是否存在。
- API 路由是否正确。
- Laravel route cache 是否过期。

```bash
cd B2C_backend
php artisan route:list | grep cart
php artisan route:clear
php artisan route:cache
```

### 500

优先看 Laravel 日志：

```bash
tail -f B2C_backend/storage/logs/laravel.log
```

常见原因：

- `APP_KEY` 缺失。
- 数据库连接失败。
- 权限不足。
- storage driver 配置错误。
- 运行时设置中的密钥无效。

## Queue / Scheduler

队列：

```bash
sudo supervisorctl status terraf-queue:*
sudo supervisorctl restart terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

调度器：

```bash
cat /etc/cron.d/terraf-scheduler
sudo systemctl status cron
cd B2C_backend
php artisan schedule:run
```

缓存：

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
```
