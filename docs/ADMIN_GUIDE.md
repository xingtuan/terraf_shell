# 管理后台使用说明

后台入口为 Laravel Filament 面板：

```text
http://your-domain-or-ip:8000/admin
```

后台支持英文、中文、韩文切换。语言切换入口位于用户菜单中的 locale 操作。

## 登录和账号

默认 Seeder 会创建管理员账号：

- 邮箱：`admin@example.com`
- 密码：`password`

该账号仅用于初始化和交付检查。正式交付前必须修改密码，或创建正式管理员后停用默认账号。

## 仪表盘

Dashboard 用于快速查看系统状态和运营入口。生产环境应重点关注：

- 最近订单和订单状态。
- 社区内容、举报和审核任务。
- 系统设置是否完整。
- 邮件、存储和队列是否正常。

## 内容管理

后台内容模块覆盖：

- 首页 Section。
- 页面 Section。
- 材料内容、材料规格、材料应用、材料故事板块。
- 文章 / CMS 内容。
- 法务页面。
- 品牌、Logo、联系邮箱、站点 URL。

首页和材料页的展示内容来自数据库，前端通过 API 读取。修改后如前端没有立即更新，先清理后端缓存并重新刷新页面：

```bash
cd B2C_backend
php artisan optimize:clear
```

## 页面板块

Page Section 用于管理可复用页面内容。新增或修改时需要注意：

- 三语标题、摘要和正文是否完整。
- 排序值是否符合前端显示顺序。
- 是否启用。
- 图片是否可以通过当前 storage 访问。

## 商品和 SKU

商城管理包含：

- Product Categories：商品分类。
- Products：商品主体、三语内容、价格、状态、图片。
- Product Variants：SKU、属性组合、价格、库存、库存策略。
- Product Attribute Definitions / Values：动态属性和值。
- Product Images：商品图片。
- Inventory：库存和库存日志。

库存策略为 `deny` 且库存数量非空时，下单会扣减库存；取消待处理订单会回补库存。不要直接修改数据库库存，建议通过后台或明确的库存维护流程处理。

## 订单

订单模块支持：

- 查看游客订单和登录用户订单。
- 更新订单状态。
- 更新付款状态。
- 填写发货信息和追踪号。
- 查看订单明细、配送费、GST 和总价。

当前系统没有内置第三方支付网关，订单付款状态需要按实际线下 / 手动收款流程维护。

订单状态来自后端枚举：

- `pending`
- `confirmed`
- `processing`
- `shipped`
- `delivered`
- `cancelled`

付款状态来自后端枚举：

- `unpaid`
- `paid`
- `refunded`

## 配送和 GST

后台设置中包含：

- Tax Settings：GST 是否启用、税率、价格是否含税、税费标签。
- Shipping Settings：NZ-only、发货城市 / 邮编、免费配送门槛、标准 / 加急 / 农村配送费、报价来源。
- NZ Post Settings：NZ Post API 凭据、报价和地址服务。

配置保存后会写入运行时设置，必要时清理缓存：

```bash
cd B2C_backend
php artisan optimize:clear
```

## 社区管理

社区模块包含：

- Posts：帖子、封面、富文本内容、Funding Link、附件、标签。
- Comments：评论和回复。
- Tags / Categories：社区标签和分类。
- Reports：用户举报。
- Moderation Queue：审核队列。
- User Violations：用户违规记录。
- User Notifications：用户通知。
- Moderation Logs / Admin Action Logs：审核和后台操作记录。

帖子可包含 cover image、富文本 JSON、附件、外部 3D 链接和 funding URL。敏感词和审核策略由 Community Settings / Moderation Settings 控制。

## 举报和用户限制

用户可以举报帖子、评论或相关内容。后台处理举报时应：

1. 查看原始内容和上下文。
2. 记录处理动作。
3. 必要时限制用户账户状态。
4. 通过通知告知相关用户。

用户治理模块支持账户状态、限制记录和违规记录维护。批量修复账户状态可使用后端命令：

```bash
cd B2C_backend
php artisan users:repair-account-status --dry-run
```

确认结果后再去掉 `--dry-run`。

## 通知

系统会为订单、社区互动、审核等场景创建通知。队列必须正常运行，否则异步通知、邮件或延迟任务可能不会及时执行。

检查队列：

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## 邮件中心

Email Center 包含：

- Email Events。
- Email Templates。
- Email Logs。
- 测试发送和预览命令。

常用命令：

```bash
cd B2C_backend
php artisan email:center:seed
php artisan email:center:preview order.created --locale=en
php artisan email:center:test admin@example.com
```

生产环境应在 Email Settings 中配置 SMTP，不要长期使用 `MAIL_MAILER=log`。

## 品牌和 Logo

Application Settings 支持维护：

- 站点名称。
- 前端 URL。
- 后台品牌。
- 默认语言。
- 时区。
- 联系邮箱和支持邮箱。
- Logo 上传。

Logo 使用当前 storage driver 保存。切换 storage 后应检查 logo URL 是否仍可访问。

## Storage 设置

Storage Settings 支持：

- 本地 public storage。
- Azure Blob Storage。
- 本地 storage link 检查和创建。
- Azure 连接测试。
- 上传测试。
- 当前配置回滚。
- 媒体扫描导出。

当前后台媒体扫描支持检查和导出清单，不会自动批量搬迁文件。实际 local / Azure 迁移应根据导出清单单独执行。

## Feature Flags

Feature Flags 控制：

- B2C Store。
- B2B Inquiry。
- Community。
- Funding Links。
- Guest Checkout。
- Maintenance Mode 和多语言维护提示。

关闭功能后，前端会按公开设置隐藏或限制对应入口。接口层仍应通过后端策略保护关键写入行为。

## 初始化数据策略

当前 Seed 数据按正式初始化内容处理。商品初始目录、材料内容、页面 Section 和默认设置用于交付验收，其中商品目录标记为正式初始化来源。

交付到生产后：

- 保留需要作为初始内容的 Seed 数据。
- 删除或替换明显的示例用户、示例帖子和测试内容。
- 修改默认管理员密码。
- 正式上线后的更新建议使用 `RUN_SEED=0`。

## 交付检查

后台包含 System Handover / Readiness 相关页面，用于检查系统交付状态。建议上线前确认：

- 后台管理员账号安全。
- 邮件发送配置可用。
- Storage 测试通过。
- GST / Shipping 设置符合业务。
- 法务页面内容完整。
- 队列和调度器运行。
- 前端三语页面关键路径可访问。
