<?php

namespace App\Enums;

enum PublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public static function normalize(mixed $value, ?self $fallback = null): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? self::Published : self::Draft;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'published',
            'publishstatus::published',
            'app\\enums\\publishstatus::published',
            'visible',
            'active',
            'frontend',
            'front_end',
            'front-end',
            'live',
            'public',
            'true',
            '1',
            'yes',
            'y',
            'on',
            '前端显示',
            '显示',
            '已发布',
            '发布' => self::Published,

            'archived',
            'archive',
            'publishstatus::archived',
            'app\\enums\\publishstatus::archived',
            'disabled',
            'removed',
            '归档',
            '已归档' => self::Archived,

            'draft',
            'publishstatus::draft',
            'app\\enums\\publishstatus::draft',
            'hidden',
            'hide',
            'inactive',
            'unpublished',
            'false',
            '0',
            'no',
            'n',
            'off',
            '草稿',
            '隐藏',
            '未发布',
            '' => self::Draft,

            default => $fallback ?? self::Draft,
        };
    }

    public static function normalizeValue(mixed $value, ?self $fallback = null): string
    {
        return self::normalize($value, $fallback)->value;
    }

    public static function labelFor(mixed $value): string
    {
        return self::normalize($value)->label();
    }

    public static function colorFor(mixed $value): string
    {
        return self::normalize($value)->color();
    }

    public function label(): string
    {
        return __("admin.publish_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }
}
