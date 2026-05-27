# 安装说明

本文以当前代码和 `auto_deploy.sh` 为准，覆盖自动化安装、手动安装、本地开发安装、脚本参数和常见安装错误。

## 适用范围

`auto_deploy.sh` 适用于 Ubuntu / Debian 系的测试服务器或生产服务器，部署模型是单机部署：

- Nginx 监听 `80`，将 `/` 代理到 Next.js，将 `/api/` 和 `/storage/` 代理到 Laravel。
- Laravel 后端同时通过 `http://SERVER_NAME:8000` 提供管理后台和健康检查。
- Next.js 运行在 `127.0.0.1:3000`。
- Nginx 的 Laravel 站点监听 `127.0.0.1:8000` / `SERVER_NAME:8000`，PHP 请求交给 PHP-FPM。
- MySQL、PHP-FPM、Supervisor、Cron 都安装在同一台服务器。

脚本不适合 Windows 本地开发，也不等同于容器化部署。Windows / macOS / Linux 本地开发请参考“本地开发安装”。

## 执行前要求

- 系统：Ubuntu 或 Debian，要求 apt 可用。
- 权限：必须使用 root 或 `sudo` 执行。
- 网络：服务器能访问 Git 仓库、Composer、NodeSource、npm registry、pnpm registry 和 apt 源。
- 域名 / IP：传给脚本的第一个参数会写入 Nginx `server_name`、Laravel `APP_URL` 和前端 `NEXT_PUBLIC_SITE_URL`。
- Git 仓库：默认 `https://github.com/xingtuan/terraf_shell.git`，私有仓库需要提前配置访问权限。
- 数据库：脚本使用本机 MySQL，通过 root socket 创建数据库和应用用户。
- 端口：需要开放 `80`；脚本也会开放 `8000` 便于访问 `/admin`。如果使用云防火墙，还需要在云控制台放行。
- 存储：脚本默认使用本地 `public` storage，并创建 `public/storage` 符号链接。
- SSL：脚本不自动申请证书；HTTPS 需要安装后另行配置 Certbot 或反向代理证书。
- 密钥：不要在命令历史里写真实长期密钥；生产数据库密码建议通过安全的 shell 会话输入或部署平台注入。

## 自动化安装

最小命令：

```bash
sudo bash auto_deploy.sh example.com
```

指定仓库、目录、分支和数据库密码：

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

如果没有域名，可传公网 IP：

```bash
sudo bash auto_deploy.sh 203.0.113.10
```

脚本执行完成后会输出访问地址，并写入 `/root/terraf-install/credentials.txt`。该文件包含数据库名、数据库用户、数据库密码和常用服务命令，权限为 `600`。

## 脚本实际执行内容

`auto_deploy.sh` 会按顺序执行以下操作：

1. 检查 root 权限，设置 apt 非交互模式。
2. 安装系统依赖：`git`、`curl`、`unzip`、`zip`、`ca-certificates`、`gnupg`、`lsb-release`、`software-properties-common`、`nginx`、`mysql-server`、`supervisor`、`openssl`、`cron`、`python3`、`acl`。
3. 安装或校验 PHP，默认版本为 PHP 8.3；如系统缺少对应包，会添加 `ppa:ondrej/php`。
4. 安装 PHP 扩展：FPM、CLI、MySQL、mbstring、xml、curl、zip、bcmath、intl、gd。
5. 安装 Composer。
6. 安装或校验 Node.js，默认主版本为 20；如版本不匹配，会使用 NodeSource 安装。
7. 安装 pnpm。
8. 启动 MySQL，并创建数据库、数据库用户和授权。
9. 克隆或更新 Git 仓库。
10. 执行 preflight 检查：`composer.lock`、前端服务端 API 变量、法务页面 fallback、购物车 session 字段长度。
11. 生成或更新后端 `.env`，默认设置为生产环境、本地 public storage、database cache、database queue、mail log。
12. 设置 `.env`、`storage/`、`bootstrap/cache/` 权限和 ACL。
13. 执行 `composer install --no-dev --optimize-autoloader --no-interaction`。
14. 创建 `B2C_backend/public/storage` 符号链接。
15. 执行 `php artisan migrate --force`。
16. 默认执行 `php artisan db:seed --force`。
17. 清理和重建 Laravel 缓存。
18. 写入 Nginx 站点：`front` 监听 80，`laravel` 监听 8000。
19. 启动 / 重启 Nginx 和 PHP-FPM。
20. 写入 Supervisor 队列配置 `terraf-queue`。
21. 写入 Cron 调度器 `/etc/cron.d/terraf-scheduler`。
22. 生成或更新前端 `.env.local`。
23. 执行 `pnpm install --frozen-lockfile=false` 和 `pnpm build`。
24. 写入 systemd 服务 `terraf-frontend.service`，以 `www-data` 运行 Next.js。
25. 检查前端、`/api/cart` 和后端 `/up`。

## 脚本参数和环境变量

| 名称 | 默认值 | 用途 |
| --- | --- | --- |
| `SERVER_NAME` | 第一个参数；未传时取 `hostname -I` 的第一个地址 | Nginx `server_name`、后端 URL、前端 URL |
| `APP_DIR` | `/var/www/terraf_shell` | 应用部署目录 |
| `REPO_URL` | `https://github.com/xingtuan/terraf_shell.git` | Git 仓库地址 |
| `BRANCH` | `main` | 部署分支 |
| `PHP_VERSION` | `8.3` | 安装和使用的 PHP 版本 |
| `NODE_MAJOR` | `20` | 安装和使用的 Node.js 主版本 |
| `DB_NAME` | `oxp_local` | MySQL 数据库名 |
| `DB_USER` | `oxp_user` | MySQL 应用用户 |
| `DB_PASS` | 随机 24 字符 | MySQL 应用用户密码 |
| `RUN_SEED` | `1` | 是否执行 `php artisan db:seed --force` |
| `RESET_WORKTREE` | `0` | 是否强制重置已有 Git 工作区 |
| `STRICT_PREFLIGHT` | `1` | preflight 失败时是否直接中止 |

脚本内部还会设置：

- `BACKEND_DIR=$APP_DIR/B2C_backend`
- `FRONTEND_DIR=$APP_DIR/B2C_frontend`
- `DEPLOY_USER=${SUDO_USER:-victor}`，用于给执行部署的用户补充 storage/cache ACL
- `FRONTEND_URL=http://SERVER_NAME`
- `BACKEND_URL=http://SERVER_NAME:8000`
- `BACKEND_LOCAL_URL=http://127.0.0.1:8000`
- `COMPOSER_ALLOW_SUPERUSER=1`
- `DEBIAN_FRONTEND=noninteractive`

以下变量不是 `auto_deploy.sh` 的部署参数：`APP_ENV`、`APP_URL`、`ADMIN_*`、`STORAGE_*`、`AZURE_*`、`MAIL_*`、`STORE_*`、`SHIPPING_*`。脚本会按自己的生产默认值写入 `.env`，这些业务配置应在部署后通过后台设置或 `.env` 调整。

## 重复执行策略

普通重复执行：

```bash
sudo bash auto_deploy.sh example.com
```

脚本会检查已有 Git 仓库。如果存在未提交或未跟踪的本地修改，会停止并提示：

```text
Existing repo has local modifications. Commit/stash them, or rerun with RESET_WORKTREE=1.
```

确认可以丢弃服务器本地修改后，才使用：

```bash
sudo env RESET_WORKTREE=1 bash auto_deploy.sh example.com
```

风险说明：

- `RESET_WORKTREE=1` 会执行 `git reset --hard origin/$BRANCH`。
- 会执行 `git clean -fd` 删除未跟踪文件。
- 不会删除数据库，但如果同时保留 `RUN_SEED=1`，Seeder 会重新执行。
- 不应把客户上传文件或人工修改文件放在 Git 工作区内。

如果只想更新代码和迁移，不希望再次 seed：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh example.com
```

## 初始化数据和管理员账号

默认 `RUN_SEED=1` 时会执行 `DatabaseSeeder`。当前 Seeder 包含：

- 管理员、审核员、普通用户和受限用户。
- 正式初始化商品目录、商品属性、SKU、库存。
- 材料内容、文章、首页 / 页面 Section。
- 默认系统设置和邮件中心数据。

默认管理员来自 `UserSeeder`：

- 邮箱：`admin@example.com`
- 密码：`password`

上线或交付前必须登录后台修改密码，或在生产环境关闭 `RUN_SEED` 后通过 Web Installer / Artisan / 后台流程创建正式管理员。

## 本地开发安装

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

本地 `.env.local` 推荐：

```dotenv
NEXT_PUBLIC_API_BASE_URL=/api
API_PROXY_TARGET=http://127.0.0.1:8000
NEXT_SERVER_API_BASE_URL=http://127.0.0.1:8000/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

## 手动服务器安装

手动部署时至少需要完成：

```bash
sudo apt update
sudo apt install -y nginx mysql-server supervisor cron git curl unzip zip acl
```

安装 PHP 8.3、Composer、Node.js 20、pnpm 后：

```bash
git clone --branch main https://github.com/xingtuan/terraf_shell.git /var/www/terraf_shell
cd /var/www/terraf_shell/B2C_backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

前端：

```bash
cd /var/www/terraf_shell/B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
pnpm start --hostname 127.0.0.1 --port 3000
```

生产手动部署还必须配置 Nginx、PHP-FPM、Supervisor 队列、Cron 调度器和 systemd 前端服务。配置模板可参考 `auto_deploy.sh` 实际写入的内容。

## Web Installer

后端提供 `/install` Web Installer，用于手动安装场景创建最小 `.env`、运行迁移、初始化默认设置、创建管理员和检查 storage link。已经存在安装锁或管理员账号后，Installer 不应作为日常部署入口。

自动化部署优先使用 `auto_deploy.sh`，Web Installer 主要用于没有 shell 自动部署权限的环境。

## 常见安装错误

### Existing repo has local modifications

说明服务器部署目录中有未提交或未跟踪修改。先确认这些修改是否需要保留。需要保留时手动提交、备份或 stash；可以丢弃时使用 `RESET_WORKTREE=1`。

### Composer 安装失败

确认 PHP 版本是 8.3，扩展齐全，并且 `composer.lock` 存在。生产服务器不要运行 `composer update`，应修复锁文件或网络问题后重试 `composer install`。

### pnpm / Node 版本问题

脚本默认要求 Node.js 20。如果系统已有其他大版本，脚本会重新安装 NodeSource 版本。手动部署时用 `node -v` 和 `pnpm -v` 检查版本。

### MySQL 连接失败

检查 MySQL 服务是否运行、数据库用户和密码是否与 `.env` 一致：

```bash
systemctl status mysql
cd B2C_backend
php artisan migrate:status
```

### Migration 失败

先查看 `B2C_backend/storage/logs/laravel.log` 和终端错误。不要手工改表绕过迁移；确认代码分支、数据库状态和迁移文件一致后重试。

### Storage 权限失败

确认 `storage/`、`bootstrap/cache/` 归属和 ACL：

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo -u www-data php B2C_backend/artisan storage:link
```

### Nginx 站点不生效

检查配置和服务：

```bash
sudo nginx -t
sudo systemctl status nginx
ls -l /etc/nginx/sites-enabled/
```

### PHP-FPM 未启动

```bash
sudo systemctl status php8.3-fpm
sudo journalctl -u php8.3-fpm -n 100 --no-pager
```

### 前端 build 失败

检查 `B2C_frontend/.env.local` 中的 `NEXT_PUBLIC_API_BASE_URL`、`NEXT_SERVER_API_BASE_URL` 和 `API_PROXY_TARGET`，再执行：

```bash
cd B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
```

### 上传文件不能显示

本地 storage 检查 `php artisan storage:link` 和 Nginx `/storage/` 代理；Azure storage 检查后台 Storage Settings 的连接测试、容器、密钥、公共 URL 或 SAS 设置。

### 502 / 404 / 500

- 502：检查 `terraf-frontend.service`、Laravel `/up`、PHP-FPM 和 Nginx upstream。
- 404：检查 Next 路由、Nginx server_name、Laravel route cache。
- 500：检查 Laravel 日志、`.env`、APP_KEY、数据库连接和文件权限。

### Queue / Scheduler 不执行

```bash
sudo supervisorctl status terraf-queue:*
sudo tail -f B2C_backend/storage/logs/queue-worker.log
cat /etc/cron.d/terraf-scheduler
```
