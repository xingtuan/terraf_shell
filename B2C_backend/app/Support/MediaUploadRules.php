<?php

namespace App\Support;

final class MediaUploadRules
{
    /**
     * @return list<string>
     */
    public static function imageExtensions(): array
    {
        return self::csvConfig('community.idea_media.image_extensions', ['jpg', 'jpeg', 'png', 'webp']);
    }

    /**
     * @return list<string>
     */
    public static function attachmentExtensions(): array
    {
        return self::csvConfig('community.idea_media.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /**
     * @return list<string>
     */
    public static function imageMimeTypes(): array
    {
        return self::csvConfig('community.idea_media.image_mime_types', ['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * @return list<string>
     */
    public static function attachmentMimeTypes(): array
    {
        return self::csvConfig('community.idea_media.attachment_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<string>
     */
    public static function imageRules(): array
    {
        return [
            'required',
            'file',
            'image',
            'mimetypes:'.implode(',', self::imageMimeTypes()),
            'extensions:'.implode(',', self::imageExtensions()),
            'max:'.self::maxImageSizeKb(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function optionalImageRules(): array
    {
        return ['nullable', ...array_values(array_filter(self::imageRules(), static fn (string $rule): bool => $rule !== 'required'))];
    }

    /**
     * @return list<string>
     */
    public static function attachmentRules(): array
    {
        return [
            'required',
            'file',
            'mimetypes:'.implode(',', self::attachmentMimeTypes()),
            'extensions:'.implode(',', self::attachmentExtensions()),
            'max:'.self::maxAttachmentSizeKb(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function optionalAttachmentRules(): array
    {
        return ['nullable', ...array_values(array_filter(self::attachmentRules(), static fn (string $rule): bool => $rule !== 'required'))];
    }

    /**
     * @return list<string>
     */
    public static function genericFileRules(?string $category): array
    {
        return self::isAttachmentCategory($category) ? self::attachmentRules() : self::imageRules();
    }

    public static function maxImageSizeKb(): int
    {
        return max(1, (int) config('community.idea_media.max_image_size_kb', 5120));
    }

    public static function maxAttachmentSizeKb(): int
    {
        return max(1, (int) config('community.idea_media.max_attachment_size_kb', 10240));
    }

    private static function isAttachmentCategory(?string $category): bool
    {
        $normalized = str((string) $category)->lower()->replace('_', '-')->trim()->value();

        return in_array($normalized, [
            'attachment',
            'attachments',
            'community-attachment',
            'community-attachments',
            'document',
            'documents',
            'business-document',
            'business-documents',
        ], true);
    }

    /**
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private static function csvConfig(string $key, array $fallback): array
    {
        $value = config($key, $fallback);
        $items = is_array($value)
            ? $value
            : explode(',', (string) $value);

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => strtolower(trim((string) $item)),
            $items
        )));
    }
}
