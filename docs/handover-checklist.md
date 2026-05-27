# 交付前检查清单

本清单以当前代码和 `auto_deploy.sh` 为准，用于最终交付前确认系统可安装、可运营、可维护。

## 代码和依赖

- [ ] 后端 PHP 版本为 8.3。
- [ ] `B2C_backend/composer.lock` 存在，生产部署使用 `composer install`。
- [ ] 前端 Node.js 主版本为 20。
- [ ] 前端使用 pnpm 安装和构建。
- [ ] 根目录 `auto_deploy.sh` 可在目标 Ubuntu / Debian 服务器执行。
- [ ] 没有把真实密钥、密码、token 写入仓库。

## 自动化安装

- [ ] 已阅读 [INSTALLATION.md](INSTALLATION.md)。
- [ ] 已确认服务器域名 / IP、端口、防火墙和 Git 仓库权限。
- [ ] 首次安装命令已确认：

```bash
sudo bash auto_deploy.sh your-domain-or-ip
```

- [ ] 生产更新命令使用 `RUN_SEED=0`：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

- [ ] 已理解 `RESET_WORKTREE=1` 会丢弃本地 Git 修改和未跟踪文件。
- [ ] 已确认 `/root/terraf-install/credentials.txt` 权限和保管方式。

## 后端

- [ ] `.env` 中 `APP_ENV=production`。
- [ ] `.env` 中 `APP_DEBUG=false`。
- [ ] `APP_KEY` 已生成并备份。
- [ ] 数据库连接正常。
- [ ] `php artisan migrate --force` 成功。
- [ ] 如需初始化内容，`php artisan db:seed --force` 已成功。
- [ ] `php artisan optimize:clear` 后可正常访问。
- [ ] `php artisan config:cache`、`route:cache`、`view:cache` 可执行。

## 前端

- [ ] `B2C_frontend/.env.local` 包含 `NEXT_PUBLIC_API_BASE_URL=/api`。
- [ ] `NEXT_SERVER_API_BASE_URL` 指向可访问的 Laravel API。
- [ ] `NEXT_PUBLIC_SITE_URL` 为正式前端 URL。
- [ ] `pnpm build` 成功。
- [ ] `terraf-frontend` systemd 服务运行。

## 管理后台

- [ ] 后台 `/admin` 可访问。
- [ ] 默认管理员密码已修改，或默认账号已停用。
- [ ] 管理员权限和交付账号已确认。
- [ ] 后台语言切换可用。
- [ ] Application Settings 已配置品牌、URL、联系信息和 Logo。
- [ ] Email Settings 已配置生产 SMTP，或明确保持 log mailer。
- [ ] Storage Settings 连接测试和上传测试通过。

## 内容和初始化数据

- [ ] 首页 Section 可编辑并在前端展示。
- [ ] 材料页内容可编辑并在前端展示。
- [ ] 文章 / CMS 内容可编辑并在前端展示。
- [ ] 商品初始化目录符合交付要求。
- [ ] 明显测试用户、测试帖子或测试订单已清理或标记。
- [ ] Seed 数据按正式初始化内容描述。

## 商城

- [ ] 商品分类、商品、图片、变体和 SKU 正常。
- [ ] SKU 库存和库存策略符合业务。
- [ ] 下单时库存扣减已验证。
- [ ] 取消待处理订单库存回补已验证。
- [ ] Guest Checkout 按业务要求启用或关闭。
- [ ] 游客订单查询可用。
- [ ] 登录用户订单中心可用。
- [ ] GST 计算符合 Tax Settings。
- [ ] Shipping Settings 和 NZ Post 设置符合业务。
- [ ] 当前系统无内置在线支付网关，付款状态按手动流程维护。

## 社区

- [ ] 社区 Feature Flag 符合业务。
- [ ] 发帖、评论、点赞、收藏、关注可用。
- [ ] Cover image 和附件上传可用。
- [ ] Funding Link 前端展示已验证。
- [ ] 举报流程进入后台 Reports / Moderation Queue。
- [ ] 用户限制和违规记录可维护。
- [ ] 通知依赖队列并已验证。

## 存储

- [ ] 本地 storage link 存在，或 Azure Storage 已配置。
- [ ] 商品、首页、材料、文章、社区图片可访问。
- [ ] local / Azure 切换策略已确认。
- [ ] 历史媒体迁移需求已确认；后台媒体扫描只负责检查和导出，不自动搬迁文件。

## 队列和调度器

- [ ] Supervisor `terraf-queue` 正常。
- [ ] `/etc/cron.d/terraf-scheduler` 存在。
- [ ] `php artisan schedule:run` 可执行。
- [ ] 邮件、通知、异步任务不会长期堆积。

## 安全和上线

- [ ] HTTPS 已配置或上线计划已确认。
- [ ] 80 / 443 / 8000 端口暴露策略已确认。
- [ ] 数据库备份策略已确认。
- [ ] 上传文件或 Azure 容器备份策略已确认。
- [ ] `.env`、`.env.local`、数据库密码和 Azure 密钥已安全保存。
- [ ] 日志查看和应急联系人已交付。

## 验证命令

```bash
cd B2C_backend
php artisan test
php artisan admin:check-translations

cd ../B2C_frontend
node scripts/check-i18n-keys.mjs
pnpm build
```

端到端测试需要前后端服务运行：

```bash
pnpm test
```
