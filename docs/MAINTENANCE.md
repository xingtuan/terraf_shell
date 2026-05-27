# 维护说明

本文面向交付后的日常维护人员，覆盖更新代码、备份、日志、缓存、重新构建、重新运行安装脚本和客户维护建议。

## 日常检查

建议定期检查：

- 前端首页、商城、购物车、Checkout、社区和后台是否可访问。
- `terraf-frontend` systemd 服务状态。
- `terraf-queue` Supervisor 状态。
- Cron scheduler 是否存在。
- Laravel 日志是否有持续异常。
- Nginx 和 PHP-FPM 日志。
- 数据库备份是否成功。
- 本地 storage 或 Azure 容器是否可访问。
- 默认管理员是否已改密码或停用。

命令：

```bash
sudo systemctl status terraf-frontend
sudo supervisorctl status terraf-queue:*
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
```

## 更新代码

推荐使用自动部署脚本：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

脚本会：

- 拉取代码。
- 安装后端依赖。
- 运行迁移。
- 清理和重建缓存。
- 安装前端依赖。
- 重新 build 前端。
- 重启 Nginx、PHP-FPM、队列和前端服务。

如果有本地修改，脚本会停止。确认可以丢弃后再执行：

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

`RESET_WORKTREE=1` 会丢弃未提交修改和未跟踪文件，必须谨慎使用。

## 数据库备份

MySQL 备份示例：

```bash
mkdir -p ~/terraf-backups
mysqldump -u oxp_user -p oxp_local > ~/terraf-backups/oxp_local-$(date +%F-%H%M%S).sql
```

建议：

- 部署前备份。
- 每日自动备份。
- 定期做恢复演练。
- 备份文件加密并保存到服务器外部位置。

恢复示例：

```bash
mysql -u oxp_user -p oxp_local < backup.sql
```

恢复生产数据库前必须确认目标数据库和备份时间。

## 文件备份

本地 storage：

```bash
tar -czf ~/terraf-backups/storage-public-$(date +%F-%H%M%S).tar.gz -C B2C_backend/storage/app public
```

还应备份：

- `B2C_backend/.env`
- `B2C_frontend/.env.local`
- Nginx 站点配置。
- Supervisor 配置。
- systemd 服务文件。

Azure storage 需要使用 Azure 工具或平台备份策略备份容器。

## 日志检查

Laravel：

```bash
tail -f B2C_backend/storage/logs/laravel.log
```

队列：

```bash
tail -f B2C_backend/storage/logs/queue-worker.log
```

前端：

```bash
sudo journalctl -u terraf-frontend -f
```

Nginx：

```bash
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

PHP-FPM：

```bash
sudo journalctl -u php8.3-fpm -f
```

## 清缓存

后端：

```bash
cd B2C_backend
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

后台运行时设置异常时，优先：

```bash
cd B2C_backend
php artisan optimize:clear
```

队列相关配置变更后：

```bash
sudo supervisorctl restart terraf-queue:*
```

## 重新构建前端

```bash
cd B2C_frontend
pnpm install --frozen-lockfile=false
pnpm build
sudo systemctl restart terraf-frontend
```

修改 `.env.local`、前端文案、页面代码或 API base URL 后都需要重新 build。

## 重新运行安装脚本

安全方式：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

只在首次交付或确认 Seeder 可重复执行时使用：

```bash
sudo env RUN_SEED=1 bash auto_deploy.sh your-domain-or-ip
```

强制重置工作区：

```bash
sudo env RUN_SEED=0 RESET_WORKTREE=1 bash auto_deploy.sh your-domain-or-ip
```

重置前检查：

- 数据库已备份。
- 上传文件已备份。
- `.env` 和 `.env.local` 已备份。
- Git 工作区没有需要保留的人工修改。
- `RUN_SEED` 设置符合本次目的。

## Storage 维护

本地 storage：

- 定期备份 `storage/app/public`。
- 检查 `public/storage` 链接。
- 确认目录权限。

Azure storage：

- 定期轮换密钥。
- 检查容器访问策略。
- 检查 SAS URL TTL。
- 使用后台 Storage Settings 做连接和上传测试。

切换 storage driver 前先阅读 [STORAGE.md](STORAGE.md)。

## 邮件维护

生产环境应在后台 Email Settings 配置 SMTP，并测试：

```bash
cd B2C_backend
php artisan email:center:test admin@example.com
```

如果邮件延迟，检查队列 worker。

## 客户维护建议

交付后客户维护人员应掌握：

- 如何登录后台和修改管理员密码。
- 如何编辑首页、材料、文章和页面 Section。
- 如何维护商品、SKU、库存和订单。
- 如何调整 GST、Shipping 和 NZ Post。
- 如何处理社区举报和用户限制。
- 如何测试 storage 和邮件。
- 如何查看日志并联系开发人员。

建议保留一份客户专用的账号清单、服务器清单、备份策略和紧急联系人清单，不要把真实密码写入 Git 仓库。

## 发布前检查清单

- `APP_DEBUG=false`。
- HTTPS 已配置。
- 默认管理员密码已修改。
- 数据库和 storage 已备份。
- 前端 `pnpm build` 成功。
- 后端迁移成功。
- 队列 worker 正常。
- Scheduler 正常。
- 邮件测试通过。
- Storage 上传测试通过。
- 购物车、Guest Checkout、订单查询通过。
- GST 和 Shipping 结果符合业务。
- 三语关键页面无明显缺失。
