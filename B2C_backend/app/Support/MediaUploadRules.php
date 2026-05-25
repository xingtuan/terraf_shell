<?php

namespace App\Support;

use App\Rules\AllowedCommunityAttachment;
use App\Services\CommunitySettingsService;

final class MediaUploadRules
{
    /**
     * @var list<string>
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * @return list<string>
     */
    public static function imageExtensions(): array
    {
        $extensions = array_values(array_intersect(
            self::csvConfig('community.idea_media.image_extensions', self::IMAGE_EXTENSIONS),
            self::IMAGE_EXTENSIONS,
        ));

        return $extensions !== [] ? $extensions : self::IMAGE_EXTENSIONS;
    }

    /**
     * @return list<string>
     */
    public static function attachmentExtensions(): array
    {
        return self::communitySettings()->allowedExtensions();
    }

    /**
     * @return list<string>
     */
    public static function imageMimeTypes(): array
    {
        return self::csvConfig('community.idea_media.image_mime_types', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
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
    public static function legacyDocumentExtensions(): array
    {
        return self::csvConfig('community.idea_media.document_extensions', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
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
            'max:'.self::maxFileSizeKb(),
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
     * @return list<string|AllowedCommunityAttachment>
     */
    public static function attachmentRules(): array
    {
        return [
            'required',
            'file',
            'max:'.self::maxFileSizeKb(),
            new AllowedCommunityAttachment(self::communitySettings()),
        ];
    }

    /**
     * @return list<string|AllowedCommunityAttachment>
     */
    public static function optionalAttachmentRules(): array
    {
        return ['nullable', ...array_values(array_filter(self::attachmentRules(), static fn (mixed $rule): bool => $rule !== 'required'))];
    }

    /**
     * @return list<string|AllowedCommunityAttachment>
     */
    public static function genericFileRules(?string $category): array
    {
        if (self::isCommunityAttachmentCategory($category)) {
            return self::attachmentRules();
        }

        if (self::isLegacyDocumentCategory($category)) {
            return self::legacyAttachmentRules();
        }

        return self::imageRules();
    }

    public static function maxImageSizeKb(): int
    {
        return self::maxFileSizeKb();
    }

    public static function maxAttachmentSizeKb(): int
    {
        return self::maxFileSizeKb();
    }

    public static function maxFileSizeKb(): int
    {
        return self::communitySettings()->maxFileSizeKb();
    }

    /**
     * @return list<string>
     */
    private static function legacyAttachmentRules(): array
    {
        return [
            'required',
            'file',
            'mimetypes:'.implode(',', self::attachmentMimeTypes()),
            'extensions:'.implode(',', self::legacyDocumentExtensions()),
            'max:'.self::maxFileSizeKb(),
        ];
    }

    private static function isCommunityAttachmentCategory(?string $category): bool
    {
        $normalized = str((string) $category)->lower()->replace('_', '-')->trim()->value();

        return in_array($normalized, [
            'community',
            'attachment',
            'attachments',
            'community-attachment',
            'community-attachments',
        ], true);
    }

    private static function isLegacyDocumentCategory(?string $category): bool
    {
        $normalized = str((string) $category)->lower()->replace('_', '-')->trim()->value();

        return in_array($normalized, [
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

    private static function communitySettings(): CommunitySettingsService
    {
        return app(CommunitySettingsService::class);
    }
}
