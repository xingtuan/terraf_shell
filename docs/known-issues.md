# 已知问题和边界

本文记录当前真实代码下仍需人工确认或按运维流程处理的事项。历史上已经修复或不再成立的限制不再列入，例如库存不扣减、Funding Link 仅后台可见、后台主要文案不可本地化等。

## 支付网关

当前系统没有内置第三方在线支付网关。订单可以创建，付款状态由后台按线下、转账或外部流程手动维护。

影响：

- 前端 Checkout 不会跳转到 Stripe、PayPal 等支付页。
- 后台付款状态只有 `unpaid`、`paid`、`refunded`。
- 财务对账需要外部流程。

## SSL 不由自动脚本配置

`auto_deploy.sh` 不自动申请或安装 HTTPS 证书。上线需要单独配置 Certbot、负载均衡证书或反向代理证书。

配置 HTTPS 后要同步更新：

- `APP_URL`
- `FRONTEND_URL`
- `NEXT_PUBLIC_SITE_URL`
- CORS allowed origins
- Sanctum stateful domains
- session secure cookie 设置

## Azure / Local 历史媒体迁移

后台支持 local / Azure 切换、连接测试、上传测试和媒体扫描导出，但不会自动批量搬迁历史文件。

切换 storage driver 前必须单独规划：

- 旧文件复制。
- 数据库路径是否保持一致。
- 公共 URL 或 SAS URL。
- 回滚方案。

## 自动部署脚本的服务器范围

`auto_deploy.sh` 面向 Ubuntu / Debian apt 系单机。它不是 Docker、Kubernetes 或多机高可用部署方案。

脚本会安装系统包、写入 Nginx、Supervisor、Cron 和 systemd 配置。不要在已有复杂生产环境中无评估直接运行。

## Seed 数据重复执行

`RUN_SEED=1` 会运行 `php artisan db:seed --force`。当前 Seeder 包含正式初始化内容和示例运营数据。生产环境常规更新建议使用：

```bash
sudo env RUN_SEED=0 bash auto_deploy.sh your-domain-or-ip
```

## 默认管理员账号

`RUN_SEED=1` 时会创建默认管理员：

- `admin@example.com`
- `password`

交付前必须修改密码或停用默认账号。

## 端口暴露

自动部署默认：

- 80：前端和 API 代理。
- 8000：Laravel 后台和健康检查。

如果生产环境不希望公网访问 8000，需要在 Nginx / 防火墙 / 反向代理层重新设计后台入口。

## NZ Post 依赖

Shipping 的 `auto` 模式会尝试 NZ Post，失败后回退手动费率。真实 NZ Post 报价依赖有效凭据、服务代码、网络和 API 可用性。

## 文档维护边界

历史 QA、审计、修复跟踪文档保留为记录。如果它们与 README、INSTALLATION、DEPLOYMENT、CONFIGURATION 等当前文档冲突，应以当前文档和代码为准。
