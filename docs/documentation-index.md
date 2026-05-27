# 文档索引

本文列出当前文档状态，便于交付、维护和后续更新。

## 当前权威文档

| 文档 | 状态 | 用途 |
| --- | --- | --- |
| [../README.md](../README.md) | 当前 | 项目入口、技术栈、功能总览、快速开始和文档索引 |
| [INSTALLATION.md](INSTALLATION.md) | 当前 | 自动化安装脚本、手动安装、本地开发和安装错误 |
| [DEPLOYMENT.md](DEPLOYMENT.md) | 当前 | 生产部署、服务配置、SSL、更新和回滚建议 |
| [ADMIN_GUIDE.md](ADMIN_GUIDE.md) | 当前 | 管理后台模块和运营操作 |
| [USER_GUIDE.md](USER_GUIDE.md) | 当前 | 前端用户流程 |
| [CONFIGURATION.md](CONFIGURATION.md) | 当前 | `.env`、后台设置、配置优先级和缓存 |
| [STORAGE.md](STORAGE.md) | 当前 | local / Azure storage、URL、上传和切换 |
| [SHOP.md](SHOP.md) | 当前 | 商品、SKU、库存、购物车、Checkout、GST、Shipping 和订单 |
| [COMMUNITY.md](COMMUNITY.md) | 当前 | 社区帖子、附件、Funding Link、评论、举报、审核和通知 |
| [I18N.md](I18N.md) | 当前 | 英文、中文、韩文三语结构和检查命令 |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | 当前 | 安装、部署、数据库、权限、storage、HTTP 错误和队列排查 |
| [MAINTENANCE.md](MAINTENANCE.md) | 当前 | 日常维护、更新、备份、日志、清缓存和重跑脚本 |

## 多语言手册

| 目录 | 状态 | 说明 |
| --- | --- | --- |
| [en](en) | 运营手册 | 英文客户 / 运营手册 |
| [zh](zh) | 运营手册 | 中文客户 / 运营手册 |
| [ko](ko) | 运营手册 | 韩文客户 / 运营手册 |

多语言手册中与部署、脚本参数、环境变量有关的内容，应以顶层权威文档为准。

## 历史和专项文档

| 文档 | 状态 | 说明 |
| --- | --- | --- |
| [INITIAL_CONTENT_POLICY.md](INITIAL_CONTENT_POLICY.md) | 当前策略 | 初始化内容作为正式起始数据的说明 |
| [ENV_AND_RUNTIME_SETTINGS_POLICY.md](ENV_AND_RUNTIME_SETTINGS_POLICY.md) | 策略参考 | 环境变量和后台运行时设置策略 |
| [WEB_INSTALLER_GUIDE.md](WEB_INSTALLER_GUIDE.md) | 辅助 | Web Installer 说明 |
| [HANDOVER_PACKAGE_GUIDE.md](HANDOVER_PACKAGE_GUIDE.md) | 交付参考 | 交付包组织建议 |
| [HANDOVER_READINESS.md](HANDOVER_READINESS.md) | 交付参考 | 交付准备检查 |
| [DELIVERY_READINESS_CHECKLIST.md](DELIVERY_READINESS_CHECKLIST.md) | QA 参考 | 交付检查清单 |
| [QA_CHECKLIST.md](QA_CHECKLIST.md) | QA 参考 | 测试清单 |
| [GUEST_CHECKOUT_QA.md](GUEST_CHECKOUT_QA.md) | QA 参考 | Guest Checkout 测试记录 |
| [SHIPPING_SETTINGS_QA.md](SHIPPING_SETTINGS_QA.md) | QA 参考 | Shipping 设置测试记录 |
| [ADMIN_LOCALIZATION_QA.md](ADMIN_LOCALIZATION_QA.md) | QA 参考 | 后台本地化检查 |
| [RUNTIME_SETTINGS_AUDIT.md](RUNTIME_SETTINGS_AUDIT.md) | 审计参考 | 运行时设置审计 |
| [BACKEND_ADMIN_AUDIT.md](BACKEND_ADMIN_AUDIT.md) | 审计参考 | 后台能力审计 |
| [LATEST_BACKEND_ADMIN_AUDIT.md](LATEST_BACKEND_ADMIN_AUDIT.md) | 审计参考 | 最新后台审计记录 |
| [FINAL_DELIVERY_FIX_TRACKER.md](FINAL_DELIVERY_FIX_TRACKER.md) | 历史记录 | 最终交付修复跟踪 |
| [known-issues.md](known-issues.md) | 当前 | 已知问题和边界 |
| [handover-checklist.md](handover-checklist.md) | 当前 | 交付前检查 |

## 已清理的过期重点

- 旧环境变量 `NEXT_PUBLIC_API_URL` 已改为当前 `NEXT_PUBLIC_API_BASE_URL`。
- 部署默认路径以 `auto_deploy.sh` 的 `/var/www/terraf_shell` 为准。
- 旧服务器 IP 不应出现在文档和测试默认配置中。
- Redis 不再作为默认必需项；当前默认 cache / queue / session 为 database 驱动。
- Seed 数据按正式起始数据说明，不再描述为可随意删除的临时内容。
- 库存扣减、Funding Link 前端展示和后台多语言已按当前代码修正。
