## Shellfin Frontend

Shellfin 是一个基于 `Next.js 16 + TypeScript + Tailwind CSS + shadcn/ui` 的多语言官网前端，面向一家专注于`굴 패각 / oyster shell`材料的韩国材料科技公司。

当前版本已经从单页落地页重构为多页面网站，覆盖以下 3 类用户：

- `B2C`：线上浏览与销售高端餐具 / 家居物件
- `B2B`：销售 oyster shell pellets / 原材料给企业客户
- `Community`：创意共创、设计协作、概念支持与 fundraising 场景

网站内容只聚焦`牡蛎壳材料线`，不涉及其他材料系列。

## 核心特性

- 基于 `App Router` 的多页面结构
- 多语言路由支持：`English / Korean / Chinese`
- 保留原有 premium / quiet luxury 视觉方向
- 复用并重构原 landing page section
- 首页 / 材料 / 文章 / B2B 询盘 / Community 帖子已经接入 Laravel API
- Store 商品目录与 Community idea cards 仍保持明确的 mock-only 边界
- 优先使用 Server Components，只有交互区域使用 Client Components

## 页面路由

每种语言都支持以下路由：

| 路由 | 说明 |
| --- | --- |
| `/[locale]` | 首页 |
| `/[locale]/material` | 材料页 |
| `/[locale]/store` | B2C 商店页 |
| `/[locale]/b2b` | B2B 合作与询盘页 |
| `/[locale]/community` | 社区与概念合作页 |
| `/[locale]/contact` | 联系页 |

当前支持的 `locale`：

- `en`
- `ko`
- `zh`

根路径 `/` 会自动跳转到默认语言 `/en`。

## 材料叙事重点

站点当前围绕以下业务信息展开：

- oyster shells
- pellets
- compress moulding
- finished tableware / premium objects

突出展示的材料优势：

- lighter than traditional porcelain
- stronger / more durable
- safer / more natural for health-conscious positioning

## 技术栈

- `Next.js 16`
- `React 19`
- `TypeScript`
- `Tailwind CSS 4`
- `shadcn/ui`
- `lucide-react`

## 本地开发

先安装依赖：

```bash
corepack pnpm install
```

启动开发环境：

```bash
corepack pnpm dev
```

生产构建：

```bash
corepack pnpm build
```

类型检查：

```bash
corepack pnpm exec tsc --noEmit
```

## 目录结构

```text
app/
  page.tsx                  # 根路径重定向到默认语言
  [locale]/
    layout.tsx              # 多语言站点布局
    page.tsx                # 首页
    material/page.tsx       # Material 页面
    store/page.tsx          # B2C Store 页面
    b2b/page.tsx            # B2B 页面
    community/page.tsx      # Community 页面
    contact/page.tsx        # Contact 页面

components/
  header.tsx                # 顶部导航 + 语言切换
  footer.tsx                # 底部导航
  language-switcher.tsx     # 语言切换器
  page-intro.tsx            # 内页顶部介绍区
  locale-html-sync.tsx      # 同步 html lang
  sections/
    hero.tsx
    why-it-matters.tsx
    material-story.tsx
    applications.tsx
    material-facts.tsx
    collaboration.tsx
    credibility.tsx
    final-cta.tsx
    product-grid.tsx
    b2b-inquiry-form.tsx
    community-ideas.tsx
    contact-details.tsx

messages/
  en.json
  ko.json
  zh.json

lib/
  i18n.ts                   # locale、messages、路由工具
  resolve-locale.ts         # 校验 locale
  types.ts                  # Product / MaterialSpec / Inquiry 等类型
  data/
    products.ts
    materials.ts
    community.ts
  api/
    products.ts
    materials.ts
    inquiries.ts
    community.ts

hooks/
  use-section-in-view.ts    # section reveal animation hook
```

## 国际化说明

所有页面文案都来自：

- `messages/en.json`
- `messages/ko.json`
- `messages/zh.json`

Locale 配置位于：

- `lib/i18n.ts`

其中包含：

- 支持的 `locales`
- 默认语言 `defaultLocale`
- `getMessages(locale)`
- `getLocalizedHref(locale, slug)`

如果要新增文案，优先修改 message 文件，而不是在组件中写死字符串。

## 数据模型

当前已定义的核心类型：

- `Product`
- `ProductCategory`
- `MaterialSpec`
- `B2BInquiry`
- `CommunityIdea`

类型文件位于：

- `lib/types.ts`

当前仍然主要使用 mock data：

- `lib/data/products.ts`
- `lib/data/community.ts`
- `lib/data/materials.ts` 仅作为材料规格的 fallback 数据源，不是主数据源

## 后端接入状态

已接入真实后端的 service 文件：

- `lib/api/homepage.ts`
- `lib/api/materials.ts`
- `lib/api/articles.ts`
- `lib/api/leads.ts`
- `lib/api/auth.ts`
- `lib/api/posts.ts`
- `lib/api/comments.ts`
- `lib/api/interactions.ts`
- `lib/api/search.ts`
- `lib/api/notifications.ts`
- `lib/api/users.ts`
- `lib/api/inquiries.ts`（兼容包装，内部转发到 `lib/api/leads.ts`）

仍然明确保持 mock-only 的 service 文件：

- `lib/api/products.ts`
- `lib/api/community.ts`

这些 mock-only 文件不是“待接回头再说”的隐性 TODO，而是因为当前后端公共接口还没有提供对应能力。

建议后续接入方向：

1. `products.ts` 需要商品列表 / 详情 / 分类等公开目录接口
2. `community.ts` 需要 community idea 列表 / 提交 / 详情接口

## 当前页面组成

### 首页

- HeroSection
- WhyItMattersSection
- MaterialStorySection
- ApplicationsSection
- MaterialFactsSection
- CollaborationSection
- CredibilitySection
- FinalCtaSection

### Material 页面

聚焦材料逻辑、技术说明、可信度与 B2B 转化。

### Store 页面

展示 B2C 产品 grid、分类信息和产品卡片。

### B2B 页面

展示合作模式、材料信息、询盘表单。

### Community 页面

展示概念卡片、合作方向与社区支持入口。

### Contact 页面

展示联系信息，并复用询盘表单。

## 视觉与实现约束

当前实现遵循以下方向：

- premium
- minimal
- spacious
- editorial
- quiet luxury

同时尽量保留原有动画与 polished 体验，没有在无必要的情况下移除动效。

## 已验证

已完成以下验证：

- `corepack pnpm build`
- `corepack pnpm exec tsc --noEmit`

## 后续建议

如果你准备继续推进这个项目，建议按下面顺序接后端：

1. 先接 `B2B inquiry` 提交
2. 再接 `products` 与 `categories`
3. 再接 `material specs / certifications`
4. 最后补 `community` 的互动与 fundraising 数据

如果需要，我也可以继续帮你补：

- README 英文版
- API 接口约定文档
- CMS / 后端字段设计
- 部署说明
