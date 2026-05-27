# 商城说明

本文说明当前商城模块的真实功能：商品、变体、SKU、库存、购物车、Checkout、Guest Checkout、GST、Shipping 和订单查询。

## 商品模型

商城核心数据包括：

- Product Category：商品分类。
- Product：商品主体、三语标题和描述、状态、基础价格、图片。
- Product Variant：SKU、属性组合、价格、库存、库存策略。
- Product Attribute Definition / Value：动态属性和值。
- Product Image：商品图片。
- Inventory Log：库存变动记录。

商品数据由后台维护，也可以通过 Seeder 初始化。`ProductCatalogSeeder` 当前用于创建正式初始化商品目录。

## 变体和 SKU

商品可拥有多个变体。每个变体可配置：

- SKU。
- 属性组合。
- 价格。
- 库存数量。
- 库存策略。
- 是否启用。

前端商品详情会根据可用变体展示选项。无法购买通常与变体未启用、库存不足或库存策略有关。

## 库存

下单时，后端会按变体库存策略处理：

- 当库存策略为 `deny` 且库存数量非空时，创建订单会扣减库存。
- 取消待处理订单时会回补库存。
- 库存变更会记录日志。

不要直接修改数据库库存。建议通过后台或明确的维护脚本处理。

## 购物车

购物车支持游客和登录用户：

- 游客购物车通过 cookie session 保存。
- 登录用户购物车绑定用户。
- 登录后可合并游客购物车。
- 数量变更会触发价格、GST 和配送计算。

购物车 session cookie 包括当前项目 cookie 和旧兼容 cookie。后端数据库字段已支持长 session key。

## Checkout

结账流程会校验：

- 购物车是否为空。
- 商品和变体是否仍可购买。
- 库存是否充足。
- 邮箱和收货地址。
- 配送选项。
- Guest Checkout 是否启用。

当前系统没有内置在线支付网关。订单创建后付款状态默认为未付款，后台按线下或手动收款流程更新。

## Guest Checkout

Guest Checkout 支持未登录用户下单。需要：

- 后台 Feature Flags 中启用 Guest Checkout。
- 用户提供邮箱。
- 用户提供收货地址和联系方式。
- 配送设置可为该地址生成报价。

游客订单会生成查询 token。用户可通过订单提交页或订单查询页查看订单。

## 登录用户订单

登录用户下单后，订单归属当前账号。用户可在账户中心查看：

- 订单列表。
- 订单详情。
- 配送状态。
- 付款状态。
- 商品明细。

## GST

GST 配置位于后台 Tax Settings。核心配置：

- 是否启用 GST。
- 税率。
- 商品价格是否含税。
- 税费标签。

`.env` fallback：

```dotenv
STORE_GST_RATE=0.15
STORE_PRICES_INCLUDE_GST=false
STORE_TAX_LABEL=GST
```

后台 Tax Settings 保存后优先生效。若设置不生效，清理缓存：

```bash
cd B2C_backend
php artisan optimize:clear
```

## Shipping

Shipping Settings 支持：

- NZ-only 限制。
- 发货城市和邮编。
- 免费配送门槛。
- 标准配送费用。
- 加急配送费用。
- 农村地址附加费。
- 报价来源：manual、nzpost、auto。

NZ Post Settings 支持地址和报价 API 凭据。`auto` 模式会优先尝试 NZ Post，失败时回退到手动费率。

自动部署默认：

```dotenv
NZPOST_ENABLED=false
```

生产环境需要在后台配置并测试。

## 订单状态

后端订单状态枚举：

| 状态 | 含义 |
| --- | --- |
| `pending` | 已创建，待确认 |
| `confirmed` | 已确认 |
| `processing` | 处理中 |
| `shipped` | 已发货 |
| `delivered` | 已送达 |
| `cancelled` | 已取消 |

付款状态枚举：

| 状态 | 含义 |
| --- | --- |
| `unpaid` | 未付款 |
| `paid` | 已付款 |
| `refunded` | 已退款 |

系统没有 `failed` 付款状态。

## 订单查询

游客订单通过 guest token 查询。登录用户通过账户中心查询自己的订单。

管理员可在后台订单模块查看所有订单，并维护：

- 状态。
- 付款状态。
- 发货信息。
- 追踪号。
- 内部备注。

## 邮件和通知

订单创建、取消、发货和状态变更会触发邮件 / 通知事件。队列必须运行，否则发送会延迟。

检查队列：

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## 常见问题

### 加入购物车失败

检查商品和变体是否启用、SKU 是否存在、库存策略和库存数量是否允许购买。

### Guest Checkout 不显示

检查 Feature Flags 中的 Guest Checkout 是否启用，并确认前端已经刷新公共设置。

### GST 金额不正确

检查 Tax Settings 中税率是否用 `0.15` 或 `15` 正确保存，以及价格是否含税设置是否符合业务。

### 配送费不正确

检查 Shipping Settings、NZ-only、农村地址识别、免费配送门槛和 NZ Post 凭据。

### 订单创建后库存没有变化

确认订单使用的是 Product Variant，并且该变体库存策略为 `deny`，库存数量不是空值。

### 游客查不到订单

游客订单需要正确的查询 token。若只知道邮箱或订单号，应由管理员在后台确认订单并重新发送必要信息。
