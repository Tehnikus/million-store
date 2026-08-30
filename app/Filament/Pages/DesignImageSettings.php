<?php

namespace App\Filament\Pages;

use App\Models\Store\StoreSettings;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Fieldset;
use Filament\Actions\Action;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;

class DesignImageSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('admin.design.image_settings.logo'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_settings.logo'))
                                ->schema([
                                    TextInput::make("image_dimensions.logo.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.logo.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_settings.product'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_settings.product_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.product.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.product.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_settings.product_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.product.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.product.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),

                        ]),
                    
                    Section::make(__('admin.design.image_settings.options_attributes'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_settings.options'))
                                ->schema([
                                    TextInput::make("image_dimensions.option.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.option.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_settings.attributes'))
                                ->schema([
                                    TextInput::make("image_dimensions.attribute.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.attribute.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_settings.category'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_settings.category_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.category.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.category.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_settings.category_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.category.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.category.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                        ]),

                    Section::make(__('admin.design.image_settings.blog'))
                        ->schema([
                            Fieldset::make(__('admin.design.image_settings.blog_miniature'))
                                ->schema([
                                    TextInput::make("image_dimensions.blog.miniature.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.blog.miniature.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
                                ])
                                ->columns(2),
                            Fieldset::make(__('admin.design.image_settings.blog_main'))
                                ->schema([
                                    TextInput::make("image_dimensions.blog.main.width")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.width')),
                                    TextInput::make("image_dimensions.blog.main.height")
                                        ->numeric()
                                        ->required()
                                        ->label(__('admin.design.image_settings.height')),
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

        $record = StoreSettings::updateOrCreate(
            ['store_id' => $formData['store_id']], // store_id condition
            $formData                              // Data to be written
        );

        $this->form->record($record);

        Notification::make()->success()->title(__('admin.messages.settings_saved'))->send();
    }

    public function getRecord(): ?StoreSettings
    {
        $store = Filament::getTenant();

        return StoreSettings::query()
            ->where('store_id', $store->id)
            ->first();
    }

    public function getSubheading(): string|null
    {
        return __('admin.design.image_settings.subheading');
    }


    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::ImageSettings;
    }
}
