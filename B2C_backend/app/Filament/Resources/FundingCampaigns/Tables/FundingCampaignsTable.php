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
                    ->label('Concept')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('post.user.name')
                    ->label('Creator')
                    ->searchable(),
                IconColumn::make('support_enabled')
                    ->label('Support')
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
                    ->label('Backers')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('campaign_end_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_status')
                    ->options(FundingCampaignStatus::options()),
                TernaryFilter::make('support_enabled')
                    ->label('Support enabled'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
