<?php

namespace App\Filament\Resources\B2BLeads\Schemas;

use App\Enums\B2BLeadStatus;
use App\Enums\B2BLeadType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class B2BLeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lead')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reference'),
                                TextEntry::make('lead_type')
                                    ->label('Lead type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadType::tryFrom($state)?->label() ?? $state),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->label() ?? $state)
                                    ->color(fn (string $state): string => B2BLeadStatus::tryFrom($state)?->color() ?? 'gray'),
                                TextEntry::make('inquiry_type'),
                                TextEntry::make('name'),
                                TextEntry::make('company_name')
                                    ->label('Company / Institution'),
                                TextEntry::make('organization_type')
                                    ->placeholder('Not specified.'),
                                TextEntry::make('job_title')
                                    ->placeholder('Not specified.'),
                                TextEntry::make('email')
                                    ->label('Email'),
                                TextEntry::make('phone')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('country')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('region')
                                    ->placeholder('Not provided.'),
                                TextEntry::make('company_website')
                                    ->placeholder('Not provided.')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                                TextEntry::make('source_page')
                                    ->label('CTA source')
                                    ->placeholder('Not tracked.'),
                                TextEntry::make('message')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Review')
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->placeholder('No internal notes yet.')
                            ->columnSpanFull(),
                        TextEntry::make('reviewer.name')
                            ->label('Reviewed by')
                            ->placeholder('Unassigned.'),
                        TextEntry::make('reviewed_at')
                            ->label('Reviewed at')
                            ->dateTime()
                            ->placeholder('Not reviewed yet.'),
                        TextEntry::make('created_at')
                            ->label('Submitted at')
                            ->dateTime(),
                    ]),
                Section::make('Structured Detail')
                    ->schema([
                        TextEntry::make('partnershipInquiry.collaboration_type')
                            ->label('Collaboration type')
                            ->placeholder('No partnership detail attached.'),
                        TextEntry::make('partnershipInquiry.collaboration_goal')
                            ->label('Collaboration goal')
                            ->placeholder('No partnership detail attached.')
                            ->columnSpanFull(),
                        TextEntry::make('sampleRequest.material_interest')
                            ->label('Material interest')
                            ->placeholder('No sample request detail attached.'),
                        TextEntry::make('sampleRequest.intended_use')
                            ->label('Intended use')
                            ->placeholder('No sample request detail attached.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
