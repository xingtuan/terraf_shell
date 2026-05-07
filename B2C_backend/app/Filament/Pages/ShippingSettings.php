<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminNavigationGroup;
use App\Filament\Support\PanelAccess;
use App\Models\Cart;
use App\Services\Shipping\AddressLookupService;
use App\Services\Shipping\NzPostClient;
use App\Services\Shipping\ShippingQuoteService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Throwable;

class ShippingSettings extends Page
{
    public ?array $data = [];

    public ?string $lastAddressLookupStatus = null;

    public ?string $lastShippingQuoteStatus = null;

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = AdminNavigationGroup::StoreOperations;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'shipping-settings';

    public function mount(): void
    {
        $this->form->fill([
            'address_lookup_query' => 'Auckland',
            'shipping_quote_postcode' => (string) (config('store.shipping.origin.postcode') ?: '1010'),
        ]);
    }

    public static function canAccess(): bool
    {
        return PanelAccess::isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.shipping_settings_nav');
    }

    public function getTitle(): string
    {
        return __('admin.pages.shipping_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin.sections.shipping_status'))
                    ->description(__('admin.shipping.nz_only_notice'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('nzpost_enabled')
                                    ->label(__('admin.fields.nz_post_enabled'))
                                    ->content(fn (): string => app(NzPostClient::class)->isEnabled() ? __('admin.system.enabled') : __('admin.system.disabled')),
                                Placeholder::make('nzpost_configured')
                                    ->label(__('admin.fields.nz_post_api_status'))
                                    ->content(fn (): string => app(NzPostClient::class)->isConfigured() ? __('admin.system.configured') : __('admin.system.not_configured')),
                                Placeholder::make('last_tested')
                                    ->label(__('admin.fields.last_tested_status'))
                                    ->content(fn (): string => collect([$this->lastAddressLookupStatus, $this->lastShippingQuoteStatus])->filter()->implode(' | ') ?: __('admin.shipping.not_tested')),
                            ]),
                        Placeholder::make('secrets_notice')
                            ->hiddenLabel()
                            ->content(__('admin.shipping.read_only_notice'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.sections.shipping_configuration'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('origin_city')
                                    ->label(__('admin.fields.origin_city'))
                                    ->content(fn (): string => $this->displayValue(config('store.shipping.origin.city'))),
                                Placeholder::make('origin_postcode')
                                    ->label(__('admin.fields.origin_postcode'))
                                    ->content(fn (): string => $this->displayValue(config('store.shipping.origin.postcode'))),
                                Placeholder::make('origin_country')
                                    ->label(__('admin.fields.origin_country'))
                                    ->content(fn (): string => $this->displayValue(config('store.shipping.origin.country', 'NZ'))),
                                Placeholder::make('default_package_weight')
                                    ->label(__('admin.fields.default_package_weight'))
                                    ->content('500 g'),
                                Placeholder::make('free_shipping_threshold')
                                    ->label(__('admin.fields.free_shipping_threshold'))
                                    ->content(fn (): string => $this->money(config('store.shipping.free_shipping_threshold', 200))),
                                Placeholder::make('standard_rate')
                                    ->label(__('admin.fields.standard_shipping_amount'))
                                    ->content(fn (): string => $this->money(config('store.shipping.standard_rate', 8))),
                                Placeholder::make('express_rate')
                                    ->label(__('admin.fields.express_shipping_amount'))
                                    ->content(fn (): string => $this->money(config('store.shipping.express_rate', 14))),
                                Placeholder::make('rural_surcharge')
                                    ->label(__('admin.fields.rural_surcharge'))
                                    ->content(fn (): string => $this->money(config('store.shipping.rural_surcharge', 5))),
                                Placeholder::make('tax')
                                    ->label(__('admin.fields.tax_label'))
                                    ->content(fn (): string => sprintf(
                                        '%s | %s%% | %s',
                                        (string) config('store.tax.label', 'GST included'),
                                        number_format(((float) config('store.tax.gst_rate', 0.15)) * 100, 2),
                                        (bool) config('store.tax.prices_include_gst', true) ? __('admin.fields.prices_include_gst') : __('admin.system.not_configured'),
                                    )),
                                Placeholder::make('nzpost_base_url')
                                    ->label(__('admin.fields.base_url'))
                                    ->content(fn (): string => $this->displayValue(config('store.nzpost.base_url'))),
                            ]),
                    ]),
                Section::make(__('admin.sections.shipping_test_tools'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address_lookup_query')
                                    ->label(__('admin.fields.address_lookup_query'))
                                    ->maxLength(120),
                                TextInput::make('shipping_quote_postcode')
                                    ->label(__('admin.fields.shipping_quote_postcode'))
                                    ->maxLength(20),
                            ]),
                    ]),
            ]);
    }

    public function testAddressLookup(AddressLookupService $addressLookupService): void
    {
        $query = trim((string) ($this->form->getState()['address_lookup_query'] ?? ''));

        if ($query === '') {
            return;
        }

        $result = $addressLookupService->search($query);
        $count = count($result['items'] ?? []);
        $message = $count > 0
            ? __('admin.shipping.address_lookup_success', ['count' => $count, 'source' => $result['source'] ?? '-'])
            : __('admin.shipping.address_lookup_empty');

        $this->lastAddressLookupStatus = $message;

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    public function testShippingQuote(ShippingQuoteService $shippingQuoteService): void
    {
        $postcode = trim((string) ($this->form->getState()['shipping_quote_postcode'] ?? ''));
        $cart = Cart::query()->make();
        $cart->setRelation('items', new EloquentCollection);

        try {
            $quote = $shippingQuoteService->quote($cart, [
                'line1' => 'Test address',
                'city' => (string) (config('store.shipping.origin.city') ?: 'Auckland'),
                'region' => null,
                'postcode' => $postcode !== '' ? $postcode : '1010',
                'country' => 'NZ',
                'is_rural' => false,
            ]);

            $message = __('admin.shipping.quote_success', ['count' => count($quote['options'] ?? [])]);
            $this->lastShippingQuoteStatus = $message;

            Notification::make()
                ->title($message)
                ->success()
                ->send();
        } catch (Throwable $throwable) {
            $this->lastShippingQuoteStatus = __('admin.shipping.quote_failed');

            Notification::make()
                ->title(__('admin.shipping.quote_failed'))
                ->body($throwable->getMessage())
                ->danger()
                ->send();
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                EmbeddedSchema::make('form'),
            ])
                ->id('form')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('testAddressLookup')
                ->label(__('admin.actions.test_address_lookup'))
                ->action('testAddressLookup'),
            Action::make('testShippingQuote')
                ->label(__('admin.actions.test_shipping_quote'))
                ->action('testShippingQuote'),
        ];
    }

    private function displayValue(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : __('admin.system.not_configured');
    }

    private function money(mixed $value): string
    {
        return '$'.number_format((float) $value, 2).' '.(string) config('store.currency', 'NZD');
    }
}
