<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use App\Filament\Support\NavigationGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use BackedEnum;

class DesignImageSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static ?int $navigationSort = 1;
    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::Design;

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('admin.design.image_types.logo'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_types.logo'))
                                ->schema([
                                    TextInput::make("image_dimensions.logo.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.logo.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_types.product'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_types.product_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.product.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.product.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_types.product_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.product.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.product.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_types.product_options'))
                                ->schema([
                                    TextInput::make("image_dimensions.product.option.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.product.option.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_types.category'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_types.category_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.category.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.category.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_types.category_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.category.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.category.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_types.blog'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_types.blog_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.blog.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.blog.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_types.blog_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.blog.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.width')),
                                    TextInput::make("image_dimensions.blog.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_types.height')),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->submit('save')->extraAttributes(['style' => 'min-width: 200px'])->label(__('admin.common.buttons.save')),
                    ]),
                ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->toArray() ?? []);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState();
        $formData['store_id'] = $store->id;

        $record = $this->getRecord() ?? new StoreSetting();
        $record->fill($formData);
        // $record->save();
        // Update only target column
        $record->update([
            'image_dimensions' => $formData['image_dimensions'],
        ]);

        $this->form->record($record);

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    public function getRecord(): ?StoreSetting
    {
        $store = Filament::getTenant();

        return StoreSetting::query()
            ->where('store_id', $store->id)
            ->first();
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.items.image_settings');
    }

    public function getHeading(): string
    {
        return __('admin.design.image_types.title');
    }

    public function getTitle(): string
    {
        return __('admin.design.image_types.title');
    }
    public function getSubheading(): string|null
    {
        return __('admin.design.image_types.subheading');
    }
}
