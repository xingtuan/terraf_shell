<?php

namespace App\Filament\Resources\FundingCampaigns\Schemas;

use App\Enums\FundingCampaignStatus;
use App\Models\Post;
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
                Section::make('Campaign')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('post_id')
                                    ->label('Concept')
                                    ->relationship('post', 'title')
                                    ->getOptionLabelFromRecordUsing(fn (Post $record): string => $record->title.' (#'.$record->id.')')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                Select::make('campaign_status')
                                    ->options(FundingCampaignStatus::options())
                                    ->required(),
                                Toggle::make('support_enabled')
                                    ->label('Support enabled')
                                    ->required(),
                                TextInput::make('support_button_text')
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('external_crowdfunding_url')
                                    ->label('External crowdfunding URL')
                                    ->url()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Progress')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('target_amount')
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('pledged_amount')
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('backer_count')
                                    ->numeric(),
                                DateTimePicker::make('campaign_start_at'),
                                DateTimePicker::make('campaign_end_at'),
                            ]),
                        Textarea::make('reward_description')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
