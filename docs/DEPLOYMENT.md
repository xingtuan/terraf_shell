# 部署说明

本文说明当前项目的生产部署方式。默认推荐使用根目录 `auto_deploy.sh`，手动部署时应以脚本实际写入的服务配置为模板。

## 推荐部署模型

单机部署结构：

| 入口 | 目标服务 |
| --- | --- |
| `http://SERVER/` | Nginx 代理到 Next.js `127.0.0.1:3000` |
| `http://SERVER/api/` | Nginx 代理到 Laravel `127.0.0.1:8000/api/` |
| `http://SERVER/storage/` | Nginx 代理到 Laravel storage 路由 |
| `http://SERVER:8000/admin` | Laravel / Filament 管理后台 |
| `http://SERVER:8000/up` | Laravel 健康检查 |

自动部署脚本会安装和配置：

- Nginx
- PHP 8.3 FPM
- MySQL
- Composer
- Node.js 20
- pnpm
- Supervisor queue worker
- Cron scheduler
- systemd 前端服务 `terraf-frontend`

## 自动部署命令

```bash
sudo bash auto_deploy.sh example.com
```

常用生产参数：

```bash
sudo env \
  APP_DIR=/var/www/terraf_shell \
  REPO_URL=https://github.com/xingtuan/terraf_shell.git \
  BRANCH=main \
  DB_NAME=oxp_local \
  DB_USER=oxp_user \
  DB_PASS='change-me-long-password' \
  RUN_SEED=0 \
  bash auto_deploy.sh example.com
```

首次交付通常使用 `RUN_SEED=1` 初始化数据；正式上线后的增量更新建议使用 `RUN_SEED=0`，避免重复执行 Seeder 造成运营数据混淆。

## Nginx

脚本写入两个站点：

- `/etc/nginx/sites-available/front`：监听 80，处理前端、`/api/` 和 `/storage/`。
- `/etc/nginx/sites-available/laravel`：监听 8000，直接提供 Laravel public 目录和管理后台。

脚本会禁用旧的默认站点和历史站点名：

- `default`
- `terraf`
- `terraf-backend-8000`
- `terraf-frontend-80`

检查命令：

```bash
sudo nginx -t
sudo systemctl status nginx
curl -I http://example.com/
curl -I http://example.com/api/cart
curl -I http://example.com:8000/up
```

## PHP-FPM

脚本使用 `php8.3-fpm`，并将 8000 站点的 PHP 请求转发到 PHP-FPM socket。

检查命令：

```bash
php -v
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

## 前端服务

脚本写入 systemd 服务：

- 文件：`/etc/systemd/system/terraf-frontend.service`
- 工作目录：`$APP_DIR/B2C_frontend`
- 用户：`www-data`
- 启动命令：`pnpm start --hostname 127.0.0.1 --port 3000`

检查和重启：

```bash
sudo systemctl status terraf-frontend
sudo journalctl -u terraf-frontend -f
sudo systemctl restart terraf-frontend
```

前端生产构建：

```bash
cd /var/www/terraf_shell/B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
```

## Laravel 后端

部署命令：

```bash
cd /var/www/terraf_shell/B2C_backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

如果需要初始化内容：

```bash
php artisan db:seed --force
```

生产环境不要在服务器上运行 `composer update`。依赖变更应在开发环境更新锁文件后再部署。

## 队列

脚本写入 Supervisor 配置：

- 文件：`/etc/supervisor/conf.d/terraf-queue.conf`
- 程序：`terraf-queue`
- 命令：`php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90`
- 用户：`www-data`
- 日志：`B2C_backend/storage/logs/queue-worker.log`

检查命令：

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status terraf-queue:*
tail -f /var/www/terraf_shell/B2C_backend/storage/logs/queue-worker.log
```

## Scheduler

脚本写入：

```text
/etc/cron.d/terraf-scheduler
```

内容为每分钟以 `www-data` 运行：

```bash
cd /var/www/terraf_shell/B2C_backend && php artisan schedule:run
```

检查：

```bash
cat /etc/cron.d/terraf-scheduler
sudo systemctl status cron
```

## 环境变量

后端 `.env` 由脚本从 `.env.example` 复制并设置关键值。脚本默认：

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=http://SERVER:8000`
- `FRONTEND_URL=http://SERVER`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`
- `STORAGE_DISK=public`
- `FILESYSTEM_DISK=public`
- `MEDIA_DRIVER=public`
- `COMMUNITY_UPLOAD_DISK=public`
- `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public`
- `MAIL_MAILER=log`
- `NZPOST_ENABLED=false`

前端 `.env.local` 默认：

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_BRAND_CONTACT_EMAIL=
NEXT_PUBLIC_SITE_URL=http://SERVER
```

配置优先级和后台设置见 [CONFIGURATION.md](CONFIGURATION.md)。

## SSL / 域名

`auto_deploy.sh` 不自动申请或配置 SSL。上线建议：

1. DNS 指向服务器。
2. 确认 80 端口可访问。
3. 使用 Certbot 或外部负载均衡配置 HTTPS。
4. 将 `APP_URL`、`FRONTEND_URL`、`NEXT_PUBLIC_SITE_URL`、CORS 和 Sanctum 域名改为 HTTPS 域名。
5. 重新执行 Laravel 缓存命令和前端 build。

示例：

```bash
cd /var/www/terraf_shell/B2C_backend
php artisan optimize:clear
php artisan config:cache

cd ../B2C_frontend
pnpm build
sudo systemctl restart terraf-frontend
```

## 更新部署流程

普通更新：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh example.com
```

如果脚本提示存在本地修改：

1. 查看修改：`git -C /var/www/terraf_shell status --short`
2. 确认是否为需要保留的人工改动。
3. 需要保留时先备份或提交。
4. 可以丢弃时再执行：

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh example.com
```

## 回滚建议

项目没有内置自动回滚脚本。建议每次部署前记录：

- Git commit
- 数据库备份文件
- `.env` 备份
- 上传文件或 Azure 容器状态
- 前端 build 成功时间

代码回滚示例：

```bash
cd /var/www/terraf_shell
git fetch origin
git checkout <known-good-commit>
cd B2C_backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
cd ../B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
sudo systemctl restart terraf-frontend
sudo supervisorctl restart terraf-queue:*
```

数据库迁移回滚需要逐次评估，不建议在生产环境盲目执行 `migrate:rollback`。
