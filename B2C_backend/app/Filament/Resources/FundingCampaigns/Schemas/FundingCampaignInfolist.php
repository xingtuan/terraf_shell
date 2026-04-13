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
                Section::make('Campaign')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('post.title')
                                    ->label('Concept')
                                    ->columnSpanFull(),
                                TextEntry::make('post.user.name')
                                    ->label('Creator'),
                                TextEntry::make('campaign_status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('support_enabled')
                                    ->label('Support enabled')
                                    ->state(fn ($record): string => $record->support_enabled ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'Yes' ? 'success' : 'gray'),
                                TextEntry::make('support_button_text')
                                    ->label('Button text'),
                                TextEntry::make('external_crowdfunding_url')
                                    ->label('Crowdfunding URL')
                                    ->placeholder('No external URL set.')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Progress')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('target_amount')
                                    ->money('USD')
                                    ->placeholder('No target set.'),
                                TextEntry::make('pledged_amount')
                                    ->money('USD')
                                    ->placeholder('No pledged amount set.'),
                                TextEntry::make('backer_count')
                                    ->label('Backers')
                                    ->placeholder('No backer count.'),
                                TextEntry::make('campaign_start_at')
                                    ->label('Starts')
                                    ->dateTime()
                                    ->placeholder('Not scheduled.'),
                                TextEntry::make('campaign_end_at')
                                    ->label('Ends')
                                    ->dateTime()
                                    ->placeholder('No end date.'),
                            ]),
                        TextEntry::make('reward_description')
                            ->placeholder('No reward description.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
