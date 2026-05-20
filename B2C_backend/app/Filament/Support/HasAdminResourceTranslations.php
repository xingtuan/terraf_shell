<?php

namespace App\Filament\Support;

trait HasAdminResourceTranslations
{
    public static function getNavigationLabel(): string
    {
        return __(static::adminResourceTranslationKey('navigation') ?? parent::getNavigationLabel());
    }

    public static function getModelLabel(): string
    {
        return __(static::adminResourceTranslationKey('model') ?? parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::adminResourceTranslationKey('plural') ?? parent::getPluralModelLabel());
    }

    private static function adminResourceTranslationKey(string $type): ?string
    {
        $class = class_basename(static::class);

        $map = [
            'AddressResource' => ['navigation' => 'admin.resources.addresses', 'model' => 'admin.resources.address', 'plural' => 'admin.resources.addresses'],
            'AdminActionLogResource' => ['navigation' => 'admin.resources.admin_action_logs', 'model' => 'admin.resources.admin_action_log', 'plural' => 'admin.resources.admin_action_logs'],
            'ArticleResource' => ['navigation' => 'admin.resources.articles', 'model' => 'admin.resources.article', 'plural' => 'admin.resources.articles'],
            'B2BLeadResource' => ['navigation' => 'admin.resources.all_leads', 'model' => 'admin.resources.b2b_lead', 'plural' => 'admin.resources.b2b_leads'],
            'CartResource' => ['navigation' => 'admin.resources.carts', 'model' => 'admin.resources.cart', 'plural' => 'admin.resources.carts'],
            'CategoryResource' => ['navigation' => 'admin.resources.concept_categories', 'model' => 'admin.resources.concept_category', 'plural' => 'admin.resources.concept_categories'],
            'CommentResource' => ['navigation' => 'admin.resources.comments', 'model' => 'admin.resources.comment', 'plural' => 'admin.resources.comments'],
            'EmailEventResource' => ['navigation' => 'admin.resources.email_events', 'model' => 'admin.resources.email_event', 'plural' => 'admin.resources.email_events'],
            'EmailLogResource' => ['navigation' => 'admin.resources.email_logs'],
            'EmailTemplateResource' => ['navigation' => 'admin.resources.email_templates', 'model' => 'admin.resources.email_template', 'plural' => 'admin.resources.email_templates'],
            'EnquiryResource' => ['navigation' => 'admin.resources.general_enquiries', 'model' => 'admin.resources.general_enquiry', 'plural' => 'admin.resources.general_enquiries'],
            'FundingCampaignResource' => ['navigation' => 'admin.resources.funding_campaigns', 'model' => 'admin.resources.funding_campaign', 'plural' => 'admin.resources.funding_campaigns'],
            'HomeSectionResource' => ['navigation' => 'admin.resources.page_sections', 'model' => 'admin.resources.page_section', 'plural' => 'admin.resources.page_sections'],
            'IdeaMediaResource' => ['navigation' => 'admin.resources.concept_media', 'model' => 'admin.resources.concept_medium', 'plural' => 'admin.resources.concept_media'],
            'InventoryResource' => ['navigation' => 'admin.resources.inventory', 'model' => 'admin.resources.inventory', 'plural' => 'admin.resources.inventory'],
            'MaterialApplicationResource' => ['navigation' => 'admin.resources.material_applications', 'model' => 'admin.resources.material_application', 'plural' => 'admin.resources.material_applications'],
            'MaterialResource' => ['navigation' => 'admin.resources.materials', 'model' => 'admin.resources.material', 'plural' => 'admin.resources.materials'],
            'MaterialSpecResource' => ['navigation' => 'admin.resources.material_specs', 'model' => 'admin.resources.material_spec', 'plural' => 'admin.resources.material_specs'],
            'MaterialStorySectionResource' => ['navigation' => 'admin.resources.material_story_sections', 'model' => 'admin.resources.material_story_section', 'plural' => 'admin.resources.material_story_sections'],
            'MediaFileResource' => ['navigation' => 'admin.resources.media_files', 'model' => 'admin.resources.media_file', 'plural' => 'admin.resources.media_files'],
            'ModerationLogResource' => ['navigation' => 'admin.resources.moderation_logs', 'model' => 'admin.resources.moderation_log', 'plural' => 'admin.resources.moderation_logs'],
            'OrderResource' => ['navigation' => 'admin.resources.orders', 'model' => 'admin.resources.order', 'plural' => 'admin.resources.orders'],
            'PostResource' => ['navigation' => 'admin.resources.concepts', 'model' => 'admin.resources.concept', 'plural' => 'admin.resources.concepts'],
            'ProductAttributeDefinitionResource' => ['navigation' => 'admin.resources.product_attributes', 'model' => 'admin.resources.product_attribute', 'plural' => 'admin.resources.product_attributes'],
            'ProductAttributeValueResource' => ['navigation' => 'admin.resources.attribute_values', 'model' => 'admin.resources.attribute_value', 'plural' => 'admin.resources.attribute_values'],
            'ProductCategoryResource' => ['navigation' => 'admin.resources.product_categories', 'model' => 'admin.resources.product_category', 'plural' => 'admin.resources.product_categories'],
            'ProductImageResource' => ['navigation' => 'admin.resources.product_media'],
            'ProductResource' => ['navigation' => 'admin.resources.products', 'model' => 'admin.resources.product', 'plural' => 'admin.resources.products'],
            'ProductVariantResource' => ['navigation' => 'admin.resources.product_variants', 'model' => 'admin.resources.product_variant', 'plural' => 'admin.resources.product_variants'],
            'ReportResource' => ['navigation' => 'admin.resources.reports', 'model' => 'admin.resources.report', 'plural' => 'admin.resources.reports'],
            'TagResource' => ['navigation' => 'admin.resources.concept_tags', 'model' => 'admin.resources.concept_tag', 'plural' => 'admin.resources.concept_tags'],
            'UserNotificationResource' => ['navigation' => 'admin.resources.announcements', 'model' => 'admin.resources.announcement', 'plural' => 'admin.resources.announcements'],
            'UserResource' => ['navigation' => 'admin.resources.users', 'model' => 'admin.resources.user', 'plural' => 'admin.resources.users'],
            'UserViolationResource' => ['navigation' => 'admin.resources.user_violations', 'model' => 'admin.resources.user_violation', 'plural' => 'admin.resources.user_violations'],
        ];

        return $map[$class][$type] ?? null;
    }
}
