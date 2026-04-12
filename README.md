# Shellfin / terraf 项目说明

## 1. 项目概述

这是一个前后端分离的多模块项目，仓库内同时包含：

- `B2C_frontend`：面向终端用户的多语言官网前端
- `B2C_backend`：提供社区、询盘、认证、后台管理等能力的 Laravel API 服务

从业务定位看，这个项目围绕 `Shellfin` 品牌展开，核心叙事是：

- 将 `oyster shell / 牡蛎壳` 材料包装为品牌化官网内容
- 同时覆盖 `B2C` 商品展示、`B2B` 原料/合作询盘、`Community` 社区互动三类场景

从当前代码状态看，它并不是一个“所有模块都已经打通”的完整成品，而是一个：

- 官网内容层已经完成主要页面搭建
- B2B 询盘已接入真实后端
- 社区核心浏览/登录/评论/点赞能力已接入真实后端
- 商品、材料参数、社区创意灵感等部分仍使用前端本地 mock 数据

这意味着：**当前仓库最成熟的是“品牌官网 + 社区 API 引擎 + 后台管理”的组合形态**。

---

## 2. 仓库结构

```text
.
├─ B2C_frontend/                # Next.js 前端
│  ├─ app/                      # App Router 页面
│  ├─ components/               # 页面区块、社区组件、UI 组件
│  ├─ hooks/                    # 前端 hooks
│  ├─ lib/
│  │  ├─ api/                   # 前端 API 访问层
│  │  ├─ auth/                  # token 存储
│  │  ├─ data/                  # 本地 mock 数据
│  │  ├─ i18n.ts                # 多语言配置
│  │  └─ types.ts               # 前端类型定义
│  ├─ messages/                 # en / ko / zh 文案
│  └─ public/                   # 静态资源
│
├─ B2C_backend/                 # Laravel 后端
│  ├─ app/
│  │  ├─ Http/
│  │  │  ├─ Controllers/Api/    # REST API 控制器
│  │  │  ├─ Requests/           # 表单校验
│  │  │  └─ Resources/          # 输出序列化
│  │  ├─ Models/                # 数据模型
│  │  ├─ Services/              # 业务服务层
│  │  ├─ Policies/              # 授权策略
│  │  ├─ Middleware/            # 中间件
│  │  ├─ Filament/              # 后台资源与小部件
│  │  └─ Support/               # API 响应封装
│  ├─ config/                   # Laravel 配置
│  ├─ database/
│  │  ├─ migrations/            # 数据库迁移
│  │  ├─ seeders/               # 种子数据
│  │  └─ factories/             # 测试/种子工厂
│  ├─ routes/                   # web/api 路由
│  └─ tests/                    # Feature 测试
│
└─ README.md                    # 仓库总说明（本文件）
```

---

## 3. 技术栈

### 前端

- Next.js 16
- React 19
- TypeScript
- Tailwind CSS 4
- Radix UI / shadcn 风格组件
- `next/font` 字体加载
- Vercel Analytics

### 后端

- PHP 8.3+
- Laravel 13
- Laravel Sanctum
- Filament 5
- 队列 Job（通知异步创建）
- MySQL（默认环境模板）
- Redis（默认环境模板中的缓存/队列）
- S3 兼容对象存储（头像、帖子图片）

---

## 4. 整体架构

可以把这个项目理解为三层：

### 4.1 展示层

由 `B2C_frontend` 负责，主要职责：

- 呈现品牌内容页面
- 管理多语言路由和文案
- 组织 B2C / B2B / Community 页面结构
- 在浏览器中发起 API 请求

### 4.2 业务 API 层

由 `B2C_backend` 负责，主要职责：

- 用户注册、登录、鉴权
- 社区帖子、评论、点赞、收藏、关注
- 举报、通知、搜索、用户资料
- B2B 询盘收集
- 管理端 moderation 和 taxonomy CRUD

### 4.3 管理后台层

同样在 `B2C_backend` 内，由 Filament 提供：

- URL：`/admin`
- 面向 `admin` / `moderator`
- 适合运营或审核人员处理内容、标签、分类、举报、封禁等事务

### 4.4 实际调用关系

```text
浏览器
  └─ Next.js 页面 / 组件
       ├─ 本地数据层（materials / products / community ideas）
       └─ REST API 层（auth / posts / comments / inquiries 等）
             └─ Laravel Controller
                  └─ Service
                       ├─ Model / Policy / Resource
                       ├─ DB
                       ├─ Storage
                       └─ Queue
```

---

## 5. 前端架构说明

### 5.1 路由结构

前端使用 App Router，并采用动态多语言目录：

- `/`：自动重定向到默认语言 `/en`
- `/[locale]`：首页
- `/[locale]/material`：材料说明页
- `/[locale]/store`：B2C 商品页
- `/[locale]/b2b`：B2B 合作与询盘页
- `/[locale]/community`：社区首页
- `/[locale]/community/[slug]`：社区帖子详情页
- `/[locale]/contact`：联系页

当前支持语言：

- `en`
- `ko`
- `zh`

多语言相关逻辑集中在：

- `B2C_frontend/lib/i18n.ts`
- `B2C_frontend/lib/resolve-locale.ts`
- `B2C_frontend/messages/*.json`

### 5.2 页面层设计

前端页面大体分为两类：

### A. 服务端渲染为主的内容页

包括：

- 首页
- Material
- Store
- B2B
- Contact

这些页面大多是 async Server Components，特点是：

- 页面结构稳定
- 主要负责组合 section
- 适合首屏渲染和 SEO
- 读取本地文案或轻量数据函数

### B. 客户端交互页

包括：

- Community 列表页
- Community 帖子详情页
- 询盘表单
- 社区登录/注册面板

这些区域使用 Client Components，负责：

- 表单提交
- 登录态管理
- 点赞/收藏等即时交互
- 拉取社区数据

### 5.3 前端分层

前端内部已经形成比较清晰的四层结构：

### 1. 页面层 `app/`

负责路由与页面骨架。

### 2. 组件层 `components/`

负责可复用区块和交互组件，例如：

- `components/sections/*`：官网 section
- `components/community/*`：社区 feed、帖子详情、评论树、认证面板
- `components/ui/*`：基础 UI 组件

### 3. 数据访问层 `lib/api/`

负责对外部数据源做统一封装，例如：

- `auth.ts`
- `posts.ts`
- `comments.ts`
- `interactions.ts`
- `inquiries.ts`

统一底层请求函数为：

- `B2C_frontend/lib/api/client.ts`

它负责：

- 统一 API base URL
- 自动拼接 query
- 自动注入 Bearer Token
- 统一解析成功/失败响应
- 抛出标准化 `ApiError`

### 4. 本地数据层 `lib/data/`

目前以下模块仍然依赖本地数据而不是真实接口：

- `materials.ts`
- `products.ts`
- `community.ts`（社区创意 idea mock）

这说明前端已预留服务层，但后端尚未补齐这些业务接口。

### 5.4 前端认证实现

前端社区登录态是浏览器端 token 模式，不是服务端 session 模式。

流程如下：

1. 用户在 `CommunityAuthPanel` 中注册或登录
2. 前端调用 `/api/auth/register` 或 `/api/auth/login`
3. 后端返回 Sanctum Personal Access Token
4. 前端把 token 存入 `localStorage`
5. 后续 API 请求通过 `Authorization: Bearer <token>` 发送
6. 页面初始化时通过 `/api/auth/me` 重新获取当前用户
7. 登出时调用 `/api/auth/logout` 并清除本地 token

本地 token 存储位置：

- `B2C_frontend/lib/auth/token-storage.ts`

存储 key：

- `shellfin.community.auth-token`

---

## 6. 后端架构说明

### 6.1 后端分层

Laravel 后端采用典型的“薄控制器 + 服务层 + Resource”结构。

### Controller

位置：

- `B2C_backend/app/Http/Controllers/Api`

职责：

- 接收请求
- 调用 Request 校验
- 委派给 Service
- 返回标准 JSON

### Request

位置：

- `B2C_backend/app/Http/Requests`

职责：

- 参数校验
- 一部分授权判断

例如：

- `RegisterRequest`
- `LoginRequest`
- `StoreInquiryRequest`
- `ListPostsRequest`
- `StorePostRequest`

### Service

位置：

- `B2C_backend/app/Services`

职责：

- 承载主要业务逻辑
- 事务处理
- 模型聚合
- 计数更新
- 调用媒体、通知、搜索、审核等服务

核心服务包括：

- `AuthService`
- `ProfileService`
- `PostService`
- `CommentService`
- `InteractionService`
- `InquiryService`
- `NotificationService`
- `SearchService`
- `TaxonomyService`
- `UserDirectoryService`
- `ReportService`
- `AdminModerationService`
- `MediaService`

### Resource

位置：

- `B2C_backend/app/Http/Resources`

职责：

- 统一输出结构
- 控制字段暴露范围
- 注入 viewer 相关字段，例如：
  - `is_liked`
  - `is_favorited`
  - `is_following`
  - `can_edit`
  - `can_delete`

### Policy / Middleware / Support

- `Policies`：控制帖子、评论、举报、通知等权限
- `EnsureUserNotBanned`：禁止被封禁用户执行社区动作
- `ApiResponse`：统一响应格式

### 6.2 路由结构

后端同时包含两套入口：

### 1. REST API

- 文件：`B2C_backend/routes/api.php`
- 前缀：`/api`

### 2. Filament 管理后台

- 路径：`/admin`
- 提供登录页和后台资源管理页面

此外还有：

- `/`：返回 API 运行信息
- `/up`：Laravel health check

### 6.3 响应契约

后端所有 API 统一使用如下结构：

### 成功响应

```json
{
  "success": true,
  "message": "Optional message",
  "data": {}
}
```

### 分页响应

```json
{
  "success": true,
  "message": null,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

### 错误响应

```json
{
  "success": false,
  "message": "Error summary",
  "errors": {
    "field": ["reason"]
  }
}
```

### 6.4 后端鉴权方式

后端对前端 API 使用的是 **Sanctum token 鉴权**：

- 登录/注册成功后签发 token
- 前端通过 Bearer Token 访问受保护接口

而 `/admin` 后台使用的是 **Laravel Web Session**：

- 后台与前台 API 是两套使用方式
- API 面向前端 SPA/浏览器 fetch
- Filament 面向内部管理人员

补充说明：

- Filament 面板允许 `admin` 和 `moderator` 登录
- `/api/admin/*` 这组 REST 接口大多要求 `admin` 权限

---

## 7. 功能模块说明

### 7.1 官网内容模块

前端已经完成的内容型模块包括：

- 首页 Hero 与品牌叙事
- 材料优势与应用说明
- B2C 商品展示页
- B2B 合作介绍页
- 联系页

这些模块主要起到：

- 品牌呈现
- 营销转化
- 多语言展示
- 将用户导向 B2B 询盘或 Community 互动

### 7.2 B2C 商品模块

目前前端已具备：

- 商品列表展示
- 分类展示
- 价格、图片、标签、可用性展示

但当前数据来源是：

- `B2C_frontend/lib/data/products.ts`

也就是说：

- 已完成页面结构
- 尚未接入真实商品接口
- 没有库存、订单、购物车、支付等后端能力

### 7.3 B2B 询盘模块

这是当前前后端联动最完整的业务模块之一。

前端：

- `B2BInquiryFormSection`
- 出现在 `b2b` 页和 `contact` 页

后端：

- `POST /api/inquiries`
- `InquiryService`
- `inquiries` 表

功能：

- 收集姓名、公司、邮箱、电话、应用场景、预计量级、时间线、留言
- 后端生成记录并返回询盘编号 `INQ-000001` 这样的 reference

### 7.4 Community 社区模块

这是当前最完整的 API 模块。

已实现的后端能力包括：

- 用户注册 / 登录 / 登出 / 当前用户
- 更新个人资料
- 帖子列表、详情、创建、更新、删除
- 评论、回复、编辑、删除
- 点赞帖子 / 评论
- 收藏帖子
- 用户关注 / 取消关注
- 举报
- 通知
- 搜索
- 分类和标签公共列表
- 管理员内容审核与封禁

当前前端已接入的社区能力主要是：

- 登录 / 注册 / 登出
- 拉取当前用户
- 帖子列表
- 帖子详情
- 评论列表
- 发表评论
- 点赞帖子
- 收藏帖子

后端已支持但前端还没有明显 UI 入口的能力包括：

- 发帖
- 回复评论
- 编辑帖子/评论
- 关注用户
- 举报内容
- 通知中心
- 搜索入口
- 个人主页
- 后台管理操作

### 7.5 后台管理模块

基于 Filament 的后台主要面向内部运营或审核人员，包含：

- Dashboard 统计
- Recent Activity
- 用户管理
- 内容审核
- 举报处理
- 分类管理
- 标签管理
- 封禁/解封用户

管理员相关 API 还暴露了 `/api/admin/*` 路由，适合以后对接自定义运营后台或内部工具。

---

## 8. 前后端联系与联调说明

### 8.1 当前联调状态总览

| 模块 | 前端数据来源 | 后端接口 | 当前状态 |
| --- | --- | --- | --- |
| 首页材料参数 | `lib/api/materials.ts` + 本地 mock | 暂无对应接口 | 仅前端 mock |
| Store 商品列表 | `lib/api/products.ts` + 本地 mock | 暂无对应接口 | 仅前端 mock |
| Community 创意灵感 | `lib/api/community.ts` + 本地 mock | 暂无对应接口 | 仅前端 mock |
| B2B 询盘 | `submitB2BInquiry()` | `POST /api/inquiries` | 已打通 |
| 社区登录/注册 | `auth.ts` | `/api/auth/*` | 已打通 |
| 社区帖子列表/详情 | `posts.ts` | `/api/posts` | 已打通 |
| 社区评论列表/创建 | `comments.ts` | `/api/posts/{id}/comments` | 已打通 |
| 帖子点赞/收藏 | `interactions.ts` | `/api/posts/{id}/like` `/favorite` | 已打通 |
| 搜索/通知/关注/举报 | 后端有能力，前端缺少完整 UI | 有 | 后端先行 |

### 8.2 前端如何调用后端

所有真实请求都经过：

- `B2C_frontend/lib/api/client.ts`

它根据：

- `NEXT_PUBLIC_API_BASE_URL`

拼出实际请求地址。默认值为：

- `http://127.0.0.1:8000/api`

也就是说本地联调默认假设：

- 前端：`http://localhost:3000`
- 后端：`http://127.0.0.1:8000`

### 8.3 典型交互链路

### 社区登录链路

```text
CommunityAuthPanel
  -> lib/api/auth.login()
    -> requestApi("/auth/login")
      -> Laravel AuthController@login
        -> AuthService@login
          -> Sanctum token
      -> 返回 user + token
  -> localStorage 持久化 token
  -> useAuthSession 拉取 /auth/me
```

### B2B 询盘链路

```text
B2BInquiryFormSection
  -> submitB2BInquiry()
    -> requestApi("/inquiries", POST)
      -> InquiryController@store
        -> InquiryService@create
          -> 写入 inquiries 表
      -> 返回 reference / status
  -> 前端展示询盘编号
```

### 社区互动链路

```text
CommunityHub / CommunityPostDetail
  -> listPosts / getPost / listComments
  -> togglePostLike / togglePostFavorite / createComment
    -> 对应 Laravel Controller
      -> Service 更新计数、状态或写入记录
      -> Resource 序列化返回
```

### 8.4 一个重要的现状说明

前端品牌叙事是 `Shellfin / oyster shell material`，但后端社区种子数据和分类目前更像一个通用“产品社区”模板，例如：

- `Software Tools`
- `Hardware`
- `Productivity`
- `Design`
- `AI Products`

因此当前仓库在业务语义上存在一个“半对齐”状态：

- 官网内容已经是 Shellfin 品牌语境
- 社区后端能力是可复用的通用社区引擎
- B2B 询盘已经和品牌语境对齐
- 社区真实数据模型与 Shellfin 业务内容还需要进一步贴合

这不是结构错误，但在后续上线前需要统一。

---

## 9. 后端数据模型概览

### 9.1 用户与身份

- `users`
- `profiles`
- `personal_access_tokens`
- `sessions`

### 9.2 社区内容

- `posts`
- `post_images`
- `comments`
- `categories`
- `tags`
- `post_tags`

### 9.3 社区互动

- `post_likes`
- `comment_likes`
- `favorites`
- `follows`

### 9.4 审核与通知

- `reports`
- `moderation_logs`
- `user_notifications`

### 9.5 商务线索

- `inquiries`

### 9.6 关键状态

内容状态：

- `pending`
- `approved`
- `rejected`
- `hidden`

用户角色：

- `user`
- `moderator`
- `admin`

通知类型：

- `comment`
- `reply`
- `like`
- `follow`

---

## 10. 本地开发启动

### 10.1 建议环境

- Node.js 20+
- pnpm
- PHP 8.3+
- Composer
- MySQL
- Redis

如果只是快速本地跑通，也可以把后端改成更轻量的本地配置：

- `QUEUE_CONNECTION=sync`
- `CACHE_STORE=file`
- `SESSION_DRIVER=file`
- `FILESYSTEM_DISK=public`
- `COMMUNITY_UPLOAD_DISK=public`

### 10.2 启动后端

```bash
cd B2C_backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

如果你需要异步通知正常入库，再开一个终端：

```bash
cd B2C_backend
php artisan queue:work
```

本地默认地址：

- API：`http://127.0.0.1:8000`
- Admin：`http://127.0.0.1:8000/admin`

### 10.3 启动前端

```bash
cd B2C_frontend
corepack pnpm install
copy .env.example .env.local
corepack pnpm dev
```

本地默认地址：

- 前端：`http://localhost:3000`

### 10.4 关键环境变量

### 前端

```env
NEXT_PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api
```

### 后端

重点变量包括：

- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER`
- `QUEUE_CONNECTION`
- `CACHE_STORE`
- `FILESYSTEM_DISK`
- `COMMUNITY_UPLOAD_DISK`
- `SANCTUM_STATEFUL_DOMAINS`
- `FRONTEND_URL`
- `CORS_ALLOWED_ORIGINS`

---

## 11. 测试与种子数据

### 11.1 已有测试

后端已经包含较完整的 Feature 测试，覆盖方向包括：

- Auth
- Inquiry
- Post workflow
- Search
- Interaction
- Comment editing
- Taxonomy and filtering
- User profile endpoints
- Admin moderation
- Admin panel access

运行方式：

```bash
cd B2C_backend
php artisan test
```

### 11.2 种子账号

执行 `php artisan migrate --seed` 后，默认有以下账号：

- 管理员：`admin@example.com` / `password`
- 审核员：`moderator@example.com` / `password`
- 被封禁示例用户：`banned@example.com` / `password`

### 11.3 种子数据现状

当前种子数据会生成：

- 分类
- 标签
- 普通用户
- 帖子与评论
- 点赞、收藏、关注
- 示例举报和通知

注意：这些种子更偏“通用产品社区演示数据”，不是 Shellfin 品牌业务数据。

---

## 12. 当前实现总结

### 已经完成的部分

- 前后端仓库已拆分清楚
- 官网主页面结构完成
- 多语言路由完成
- B2B 询盘链路完成
- 社区基础链路完成
- 后端审核/管理能力完成度较高

### 尚未完全闭环的部分

- 商品系统还没有真实后端
- 材料参数还没有真实后端
- Community 创意/灵感模块仍是 mock
- 前端没有完整接入后端的高级社区功能
- 社区数据语义和 Shellfin 业务语义仍需进一步统一

---

## 13. 建议的下一步

如果继续推进这个项目，建议按下面顺序补齐：

1. 先统一业务语义：把后端分类、标签、种子内容从“通用产品社区”迁移到 Shellfin 领域
2. 补 `materials` 接口，让材料参数、认证、实验数据从后端输出
3. 补 `products` / `categories` 接口，替换 Store 页 mock 数据
4. 给前端补社区高级 UI：发帖、回复、通知、举报、关注、搜索、个人主页
5. 再根据业务需要决定是否继续发展为真正的 B2C 电商系统

---

## 14. 一句话结论

这个仓库当前最准确的定位是：

**一个以 Shellfin 品牌官网为外壳、以 Laravel 社区与询盘能力为中台、并带有可扩展管理后台的前后端分离项目。**
