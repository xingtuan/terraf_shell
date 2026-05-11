<?php

namespace App\Filament\Resources\FundingCampaigns\Schemas;

use App\Enums\FundingCampaignStatus;
use App\Models\Post;
use App\Rules\ExternalSafeUrl;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FundingCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.campaign'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('post_id')
                                    ->label(__('admin.ui.concept'))
                                    ->relationship('post', 'title')
                                    ->getOptionLabelFromRecordUsing(fn (Post $record): string => $record->title.' (#'.$record->id.')')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('campaign_status')
                                    ->label(__('admin.ui.campaign_status'))
                                    ->options(FundingCampaignStatus::options())
                                    ->required(),
                                Toggle::make('support_enabled')
                                    ->label(__('admin.ui.support_enabled'))
                                    ->required(),
                                TextInput::make('support_button_text')
                                    ->label(__('admin.ui.support_button_text'))
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('external_crowdfunding_url')
                                    ->label(__('admin.ui.external_crowdfunding_url'))
                                    ->url()
                                    ->rule(new ExternalSafeUrl)
                                    ->maxLength(2048)
                                    ->helperText(__('admin.ui.use_a_valid_external_http_https_funding_page_unsafe_protocols_are_rejected'))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.progress'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('target_amount')
                                    ->label(__('admin.ui.target_amount'))
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('pledged_amount')
                                    ->label(__('admin.ui.pledged_amount'))
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('backer_count')
                                    ->label(__('admin.ui.backer_count'))
                                    ->numeric(),
                                DateTimePicker::make('campaign_start_at')
                                    ->label(__('admin.ui.campaign_start_at')),
                                DateTimePicker::make('campaign_end_at')
                                    ->label(__('admin.ui.campaign_end_at')),
                            ]),
                        Textarea::make('reward_description')
                            ->label(__('admin.ui.reward_description'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
