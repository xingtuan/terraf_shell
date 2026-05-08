<?php

namespace App\Filament\Resources\FundingCampaigns\Schemas;

use App\Enums\FundingCampaignStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FundingCampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.ui.campaign'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('post.title')
                                    ->label(__('admin.ui.concept'))
                                    ->columnSpanFull(),
                                TextEntry::make('post.user.name')
                                    ->label(__('admin.ui.creator')),
                                TextEntry::make('campaign_status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('support_enabled')
                                    ->label(__('admin.ui.support_enabled'))
                                    ->state(fn ($record): string => $record->support_enabled ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'Yes' ? 'success' : 'gray'),
                                TextEntry::make('support_button_text')
                                    ->label(__('admin.ui.button_text')),
                                TextEntry::make('external_crowdfunding_url')
                                    ->label(__('admin.ui.crowdfunding_url'))
                                    ->placeholder(__('admin.ui.no_external_url_set'))
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('admin.ui.progress'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('target_amount')
                                    ->money('USD')
                                    ->placeholder(__('admin.ui.no_target_set')),
                                TextEntry::make('pledged_amount')
                                    ->money('USD')
                                    ->placeholder(__('admin.ui.no_pledged_amount_set')),
                                TextEntry::make('backer_count')
                                    ->label(__('admin.ui.backers'))
                                    ->placeholder(__('admin.ui.no_backer_count')),
                                TextEntry::make('campaign_start_at')
                                    ->label(__('admin.ui.starts'))
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.not_scheduled')),
                                TextEntry::make('campaign_end_at')
                                    ->label(__('admin.ui.ends'))
                                    ->dateTime()
                                    ->placeholder(__('admin.ui.no_end_date')),
                            ]),
                        TextEntry::make('reward_description')
                            ->placeholder(__('admin.ui.no_reward_description'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
