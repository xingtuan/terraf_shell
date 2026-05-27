# 社区说明

社区模块支持帖子、富文本、附件、封面图、Funding Link、评论、收藏、举报、审核、用户限制和通知。

## 功能范围

当前代码包含：

- 社区帖子列表、详情、搜索和排序。
- 富文本内容 JSON。
- Cover image。
- 附件 / idea media。
- 外部 3D 链接。
- Funding Link 和 Funding Campaign 展示。
- 评论和回复。
- 点赞、收藏、关注。
- 举报。
- 审核队列。
- 用户违规和限制。
- 用户通知。

社区入口是否展示由后台 Feature Flags 控制。

## 帖子

帖子支持：

- 三语或单语标题和内容。
- Tiptap 富文本 JSON。
- 摘要。
- Cover image。
- 分类和标签。
- 附件。
- 外部链接。
- Funding URL。

帖子创建和更新由后端 `PostService` 处理，会执行权限、审核策略、敏感词和媒体同步逻辑。

## 富文本和附件

前端使用 Tiptap 编辑富文本。附件上传限制来自 `config/community.php` 和后台 Community Settings，包含：

- 最大文件数量。
- 最大文件大小。
- 允许扩展名。
- 允许 MIME。
- 图片和文档类型限制。

上传使用当前 storage driver。切换 local / Azure 后，必须测试 cover image 和附件访问。

## Cover Image

帖子可配置封面图。封面图 URL 由后端根据 storage driver 解析：

- local public：通常为 `/storage/...`。
- Azure：根据 Azure URL 或临时 SAS URL 返回。

如果列表页封面不显示，优先检查 Storage Settings 和 `public/storage` 链接。

## Funding Link

帖子可包含 funding URL。后台 Feature Flags 中的 Funding Links 控制前端入口。系统也包含 Funding Campaign 管理，用于展示支持进度、目标金额和外部众筹链接。

Funding Link 是内容和运营能力，不是支付网关。真实收款仍依赖外部链接或线下流程。

## 评论

用户可对帖子发表评论或回复。评论支持：

- 创建。
- 查看。
- 删除或审核处理。
- 举报。

评论可能受用户状态、审核策略和敏感词限制影响。

## 收藏、点赞和关注

登录用户可以：

- 点赞帖子。
- 收藏帖子。
- 关注作者。
- 在账户中心查看保存内容和社区记录。

相关接口需要登录认证。

## 举报

用户可以举报帖子、评论或其他社区内容。举报进入后台 Reports / Moderation Queue。

管理员处理举报时应记录：

- 被举报内容。
- 举报原因。
- 处理动作。
- 是否限制用户。
- 是否通知相关用户。

## 审核

审核策略由 Community Moderation Settings 控制。系统支持：

- 敏感词。
- 自动标记。
- 审核队列。
- 管理员处理记录。
- 用户违规记录。

如内容没有展示，可能处于待审核、被隐藏、被删除或作者账号受限。

## 用户限制

后台 Users Governance 相关模块支持：

- 账户状态。
- 用户违规记录。
- 用户限制。
- 管理员操作日志。

账户状态异常可先 dry run：

```bash
cd B2C_backend
php artisan users:repair-account-status --dry-run
```

## 通知

社区互动、审核处理和关注相关事件会生成通知。通知依赖数据库和队列。

检查：

```bash
sudo supervisorctl status terraf-queue:*
tail -f B2C_backend/storage/logs/queue-worker.log
```

## 后台管理入口

后台社区相关模块：

- Posts
- Comments
- Reports
- Tags
- Categories
- Moderation Queue
- User Violations
- User Notifications
- Moderation Logs
- Admin Action Logs
- Funding Campaigns
- Community Settings
- Community Moderation Settings

## 常见问题

### 用户不能发帖

检查 Community Feature Flag、登录状态、账户限制、审核策略和上传限制。

### 附件上传失败

检查文件大小、扩展名、MIME、PHP 上传限制、Nginx body size、storage driver 和写入权限。

### 帖子发出后不显示

可能被审核策略拦截、命中敏感词、作者账号受限或内容被管理员隐藏。

### Funding Link 不显示

检查 Feature Flags 中 Funding Links 是否启用，并确认帖子或 Campaign 已填写外部链接。

### 举报后没有通知

检查队列 worker 是否运行，查看 `queue-worker.log` 和 `laravel.log`。
