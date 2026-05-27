# 存储说明

系统支持本地 public storage 和 Azure Blob Storage。当前代码通过 `StorageManagerService`、`StorageUrl` 和后台 Storage Settings 管理 active driver、URL 生成、连接测试和上传测试。

## 默认行为

`auto_deploy.sh` 默认将后端 `.env` 设置为本地 public storage：

```dotenv
STORAGE_DISK=public
FILESYSTEM_DISK=public
MEDIA_DRIVER=public
COMMUNITY_UPLOAD_DISK=public
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public
```

并创建：

```text
B2C_backend/public/storage -> B2C_backend/storage/app/public
```

前端和 Nginx 通过 `/storage/...` 访问本地公开文件。

## 本地 Storage

本地公开文件保存在：

```text
B2C_backend/storage/app/public
```

公开访问路径：

```text
http://your-domain/storage/{path}
```

手动创建链接：

```bash
cd B2C_backend
php artisan storage:link
```

检查：

```bash
ls -l B2C_backend/public/storage
test -w B2C_backend/storage/app/public && echo writable
```

如果 `public/storage` 已存在但不是符号链接，`auto_deploy.sh` 会停止，避免覆盖未知文件。

## Azure Storage

Azure Blob Storage 通过 Laravel filesystem disk `azure` 接入。需要配置：

```dotenv
AZURE_STORAGE_NAME=
AZURE_STORAGE_KEY=
AZURE_STORAGE_CONTAINER=
AZURE_STORAGE_URL=
```

后台 Storage Settings 支持：

- 切换 active driver。
- 保存 Azure account、key、container、URL。
- 连接测试。
- 上传测试。
- 临时 URL / SAS 设置。
- 回滚最近的 storage driver。

如果启用 SAS 临时 URL，`StorageUrl` 会使用 Azure temporary URL；否则会根据公开 URL 和 container 生成稳定 URL。

## 上传路径

常见上传来源：

- 商品图片。
- 首页 / 页面 Section 图片。
- 材料和文章图片。
- 社区帖子 cover image。
- 社区附件和 idea media。
- Filament / Livewire 临时上传文件。

社区附件默认目录和限制来自 `config/community.php` 以及后台 Community Settings。

## 图片访问

本地 public storage：

```text
/storage/{path}
```

非公开 local disk 或受保护文件可通过后端媒体路由访问：

```text
/media/files/{disk}/{path}
```

Azure：

- 有公开 base URL 时生成 Azure URL。
- 启用临时 URL 时生成带过期时间的 URL。
- URL 过期会导致图片短时间后无法访问，需要检查 SAS TTL。

## 前端媒体配置

前端可配置：

```dotenv
NEXT_PUBLIC_MEDIA_BASE_URL=
```

通常保持为空，让后端 API 返回完整或相对媒体 URL。只有在使用独立 CDN / 媒体域名且前端需要拼接 URL 时才设置该值。

## 本地切换到 Azure

建议流程：

1. 备份数据库和 `storage/app/public`。
2. 在后台 Storage Settings 填写 Azure 配置。
3. 执行连接测试和上传测试。
4. 确认新上传文件可以访问。
5. 导出现有媒体扫描清单。
6. 按清单单独迁移历史文件到 Azure。
7. 检查商品、首页、材料、文章、社区图片。
8. 清理缓存并重新构建前端。

当前后台媒体扫描支持检查和导出，不会自动批量搬迁文件。

## Azure 切回本地

建议流程：

1. 备份数据库和 Azure 容器。
2. 将需要保留的 Azure 文件下载到 `storage/app/public` 对应路径。
3. 切换 active driver 为 local / public。
4. 确认 `php artisan storage:link` 正常。
5. 检查媒体 URL 是否仍能解析。
6. 清理缓存。

## 权限

生产环境中 Laravel 需要写入：

```text
B2C_backend/storage
B2C_backend/bootstrap/cache
```

自动部署会设置 `www-data` 权限和 ACL。手动修复：

```bash
sudo chown -R www-data:www-data B2C_backend/storage B2C_backend/bootstrap/cache
sudo chmod -R ug+rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -R -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
sudo setfacl -dR -m u:www-data:rwX B2C_backend/storage B2C_backend/bootstrap/cache
```

## 常见问题

### 上传成功但图片不显示

检查：

- 当前 active driver 是否正确。
- 本地是否存在 `public/storage` 链接。
- Nginx `/storage/` 是否代理到 Laravel。
- Azure container、key、base URL 是否正确。
- 后台 Storage Settings 上传测试是否通过。

### 切换 Azure 后旧图片失效

数据库中记录的路径通常不会自动变更。需要把历史文件迁移到 Azure 对应路径，或保持本地文件仍可访问。

### 本地 storage link 创建失败

检查 `public/storage` 是否已经存在。若是普通目录或文件，先备份确认后再处理，不要直接删除未知生产文件。

### SAS URL 很快失效

检查后台或 `.env` 中的 temporary URL TTL。过短会导致页面缓存或用户长时间停留后图片失效。

### Livewire 临时上传失败

确认 `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` 指向可写 disk，且后端 PHP 上传大小、Nginx body size 和社区上传限制一致。
