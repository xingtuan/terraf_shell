# 文档中心

本目录是 OXP / Terraf Shell 的交付文档。当前权威入口为根目录 [README.md](../README.md)，安装、部署、维护和模块说明以本目录顶层文档为准。

## 推荐阅读顺序

1. [安装说明](INSTALLATION.md)：自动化安装脚本、手动安装、本地开发和常见安装错误。
2. [部署说明](DEPLOYMENT.md)：生产部署、Nginx、PHP-FPM、前端服务、队列、调度器和 SSL。
3. [系统配置](CONFIGURATION.md)：`.env`、后台运行时设置、配置优先级和缓存刷新。
4. [后台使用说明](ADMIN_GUIDE.md)：Filament 管理后台模块和交付操作。
5. [用户使用说明](USER_GUIDE.md)：前端用户流程。
6. [商城说明](SHOP.md)：商品、SKU、库存、购物车、Checkout、GST、Shipping 和订单。
7. [社区说明](COMMUNITY.md)：帖子、评论、附件、Funding Link、举报、审核和通知。
8. [存储说明](STORAGE.md)：本地 storage、Azure storage、URL、上传路径和切换。
9. [国际化说明](I18N.md)：英文、中文、韩文三语结构和新增字段要求。
10. [故障排查](TROUBLESHOOTING.md)：安装、部署、storage、商城、后台、多语言和 HTTP 错误。
11. [维护说明](MAINTENANCE.md)：更新代码、备份、日志、清缓存、重建和安全重跑脚本。

## 多语言客户手册

`docs/en`、`docs/zh`、`docs/ko` 保留给客户和运营人员阅读的分章节手册。它们用于说明系统使用方式；涉及安装、部署、脚本参数和环境变量时，以顶层文档为准。

## 历史审计和 QA 文档

以下文档保留为交付和测试记录，不能替代当前安装和部署说明：

- `ADMIN_LOCALIZATION_QA.md`
- `ADMIN_OPERATIONS_GUIDE.md`
- `ADMIN_SETTINGS_GUIDE.md`
- `BACKEND_ADMIN_AUDIT.md`
- `DELIVERY_READINESS_CHECKLIST.md`
- `FINAL_DELIVERY_FIX_TRACKER.md`
- `GUEST_CHECKOUT_QA.md`
- `HANDOVER_PACKAGE_GUIDE.md`
- `HANDOVER_READINESS.md`
- `INITIAL_CONTENT_POLICY.md`
- `QA_CHECKLIST.md`
- `RUNTIME_SETTINGS_AUDIT.md`
- `SHIPPING_SETTINGS_QA.md`
- `WEB_INSTALLER_GUIDE.md`

如果这些历史文档与 `README.md`、`INSTALLATION.md`、`DEPLOYMENT.md`、`CONFIGURATION.md` 冲突，以当前顶层文档和代码为准。

## 文档维护规则

- 更新脚本、环境变量或部署端口时，必须同步 `README.md`、`INSTALLATION.md` 和 `DEPLOYMENT.md`。
- 新增后台设置时，必须同步 `CONFIGURATION.md` 和 `ADMIN_GUIDE.md`。
- 新增商品、订单、GST、配送相关行为时，必须同步 `SHOP.md`。
- 新增社区行为时，必须同步 `COMMUNITY.md`。
- 新增 storage driver、URL 策略或上传路径时，必须同步 `STORAGE.md`。
- 新增用户可见文案时，必须同步三语翻译并更新 `I18N.md` 中的规则。
