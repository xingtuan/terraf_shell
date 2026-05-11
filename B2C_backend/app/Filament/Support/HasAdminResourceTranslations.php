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
            'AdminActionLogResource' => ['navigation' => 'admin.resources.admin_action_logs'],
            'ArticleResource' => ['navigation' => 'admin.resources.articles'],
            'B2BLeadResource' => ['navigation' => 'admin.resources.all_leads', 'model' => 'admin.resources.b2b_lead', 'plural' => 'admin.resources.b2b_leads'],
            'CartResource' => ['navigation' => 'admin.resources.carts', 'model' => 'admin.resources.cart', 'plural' => 'admin.resources.carts'],
            'CategoryResource' => ['navigation' => 'admin.resources.concept_categories', 'model' => 'admin.resources.concept_category', 'plural' => 'admin.resources.concept_categories'],
            'CommentResource' => ['navigation' => 'admin.resources.comments'],
            'EmailEventResource' => ['navigation' => 'admin.resources.email_events'],
            'EmailLogResource' => ['navigation' => 'admin.resources.email_logs'],
            'EmailTemplateResource' => ['navigation' => 'admin.resources.email_templates'],
            'EnquiryResource' => ['navigation' => 'admin.resources.general_enquiries', 'model' => 'admin.resources.general_enquiry', 'plural' => 'admin.resources.general_enquiries'],
            'FundingCampaignResource' => ['navigation' => 'admin.resources.funding_campaigns'],
            'HomeSectionResource' => ['navigation' => 'admin.resources.homepage_sections'],
            'IdeaMediaResource' => ['navigation' => 'admin.resources.concept_media'],
            'InventoryResource' => ['navigation' => 'admin.resources.inventory'],
            'MaterialApplicationResource' => ['navigation' => 'admin.resources.material_applications'],
            'MaterialResource' => ['navigation' => 'admin.resources.materials'],
            'MaterialSpecResource' => ['navigation' => 'admin.resources.material_specs'],
            'MaterialStorySectionResource' => ['navigation' => 'admin.resources.material_story_sections'],
            'MediaFileResource' => ['navigation' => 'admin.resources.media_files', 'model' => 'admin.resources.media_file', 'plural' => 'admin.resources.media_files'],
            'ModerationLogResource' => ['navigation' => 'admin.resources.moderation_logs'],
            'OrderResource' => ['navigation' => 'admin.resources.orders'],
            'PostResource' => ['navigation' => 'admin.resources.concepts'],
            'ProductAttributeDefinitionResource' => ['navigation' => 'admin.resources.product_attributes'],
            'ProductAttributeValueResource' => ['navigation' => 'admin.resources.attribute_values'],
            'ProductCategoryResource' => ['navigation' => 'admin.resources.product_categories', 'model' => 'admin.resources.product_category', 'plural' => 'admin.resources.product_categories'],
            'ProductImageResource' => ['navigation' => 'admin.resources.product_media'],
            'ProductResource' => ['navigation' => 'admin.resources.products', 'model' => 'admin.resources.product', 'plural' => 'admin.resources.products'],
            'ProductVariantResource' => ['navigation' => 'admin.resources.product_variants'],
            'ReportResource' => ['navigation' => 'admin.resources.reports'],
            'TagResource' => ['navigation' => 'admin.resources.concept_tags', 'model' => 'admin.resources.concept_tag', 'plural' => 'admin.resources.concept_tags'],
            'UserNotificationResource' => ['navigation' => 'admin.resources.announcements'],
            'UserResource' => ['navigation' => 'admin.resources.users'],
            'UserViolationResource' => ['navigation' => 'admin.resources.user_violations'],
        ];

        return $map[$class][$type] ?? null;
    }
}
