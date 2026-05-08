<?php

namespace App\Filament\Resources\FundingCampaigns\Tables;

use App\Enums\FundingCampaignStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FundingCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post.title')
                    ->label(__('admin.ui.concept'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('post.user.name')
                    ->label(__('admin.ui.creator'))
                    ->searchable(),
                IconColumn::make('support_enabled')
                    ->label(__('admin.ui.support'))
                    ->boolean(),
                TextColumn::make('campaign_status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state): string => FundingCampaignStatus::tryFrom($state)?->color() ?? 'gray'),
                TextColumn::make('pledged_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('target_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('backer_count')
                    ->label(__('admin.ui.backers'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('campaign_end_at')
                    ->label(__('admin.ui.ends'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.ui.updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_status')
                    ->options(FundingCampaignStatus::options()),
                TernaryFilter::make('support_enabled')
                    ->label(__('admin.ui.support_enabled')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
