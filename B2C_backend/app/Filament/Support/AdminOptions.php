<?php

namespace App\Filament\Support;

use App\Models\EmailLog;
use App\Models\ProductAttributeDefinition;
use App\Models\ProductVariant;

class AdminOptions
{
    /**
     * @return array<string, string>
     */
    public static function variantStockStatuses(): array
    {
        return self::translatedKeys('admin.products.stock_status', ProductVariant::STOCK_STATUS_OPTIONS);
    }

    /**
     * @return array<string, string>
     */
    public static function inventoryPolicies(): array
    {
        return self::translatedKeys('admin.products.inventory_policy', ProductVariant::INVENTORY_POLICY_OPTIONS);
    }

    /**
     * @return array<string, string>
     */
    public static function productAttributeTypes(): array
    {
        return self::translatedKeys('admin.products.attribute_type', ProductAttributeDefinition::TYPE_OPTIONS);
    }

    /**
     * @return array<string, string>
     */
    public static function certificationStatuses(bool $includeDemo = false): array
    {
        $options = [
            'certified' => __('admin.certifications.status.certified'),
            'tested' => __('admin.certifications.status.tested'),
            'in_testing' => __('admin.certifications.status.in_testing'),
            'pending' => __('admin.certifications.status.pending'),
            'not_applicable' => __('admin.certifications.status.not_applicable'),
        ];

        if ($includeDemo) {
            $options['demo'] = __('admin.certifications.status.demo');
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function technicalDownloadTypes(): array
    {
        return [
            'material_data_sheet' => __('admin.certifications.download_type.material_data_sheet'),
            'product_specification_sheet' => __('admin.certifications.download_type.product_specification_sheet'),
            'certification_document' => __('admin.certifications.download_type.certification_document'),
            'safety_food_contact_document' => __('admin.certifications.download_type.safety_food_contact_document'),
            'catalogue' => __('admin.certifications.download_type.catalogue'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function technicalDownloadStatuses(): array
    {
        return [
            'available' => __('admin.certifications.download_status.available'),
            'on_request' => __('admin.certifications.download_status.on_request'),
            'pending' => __('admin.certifications.download_status.pending'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function emailLogStatuses(): array
    {
        return [
            EmailLog::STATUS_QUEUED => __('admin.email.log_status.queued'),
            EmailLog::STATUS_SENT => __('admin.email.log_status.sent'),
            EmailLog::STATUS_FAILED => __('admin.email.log_status.failed'),
            EmailLog::STATUS_SKIPPED => __('admin.email.log_status.skipped'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function ideaMediaKinds(): array
    {
        return [
            'sketch' => __('admin.media.kind.sketch'),
            'concept_image' => __('admin.media.kind.concept_image'),
            'render_image' => __('admin.media.kind.render_image'),
            'presentation_pdf' => __('admin.media.kind.presentation_pdf'),
            'spec_sheet' => __('admin.media.kind.spec_sheet'),
            'model_3d' => __('admin.media.kind.model_3d'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function ideaMediaTypes(): array
    {
        return [
            'image' => __('admin.media.type.image'),
            'document' => __('admin.media.type.document'),
            'external_3d' => __('admin.media.type.external_3d'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function ideaMediaSourceTypes(): array
    {
        return [
            'upload' => __('admin.media.source_type.upload'),
            'external_url' => __('admin.media.source_type.external_url'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reportTargetTypes(): array
    {
        return [
            'post' => __('admin.reports.target_type.post'),
            'comment' => __('admin.reports.target_type.comment'),
            'user' => __('admin.reports.target_type.user'),
        ];
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private static function translatedKeys(string $prefix, array $values): array
    {
        return collect(array_keys($values))
            ->mapWithKeys(fn (string $value): array => [$value => __("{$prefix}.{$value}")])
            ->all();
    }
}
