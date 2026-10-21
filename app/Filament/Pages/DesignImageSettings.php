<?php

namespace App\Filament\Pages;

use App\Domain\Media\ImageConversion;
use App\Domain\Media\ImageType;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use App\Filament\Support\AdminMenu\NavigationItem;
use App\Models\Store\StoreSettings;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DesignImageSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        // One section per image type, one fieldset per conversion — all from the ImageType enum.
        // A new case in the enum appears here without touching this page.
        $sections = collect(ImageType::cases())->map(
            fn(ImageType $type) => Section::make($type->label())
                ->schema(collect($type->conversions())->map(function (ImageConversion $conversion) use ($type) {
                    $path = "image_dimensions.{$type->value}.{$conversion->value}";
                    $default = $conversion->defaultDimensions();

                    // Not required: an empty field means "use the default", which the placeholder shows
                    return Fieldset::make($conversion->label())
                        ->schema([
                            TextInput::make("{$path}.width")
                                ->integer()
                                ->minValue(1)
                                ->placeholder((string) $default['width'])
                                ->label(__('admin.design.image_settings.width')),
                            TextInput::make("{$path}.height")
                                ->integer()
                                ->minValue(1)
                                ->placeholder((string) $default['height'])
                                ->label(__('admin.design.image_settings.height')),
                        ])
                        ->columns(2);
                })->all())
        )->all();

        return $schema
            ->components([
                Form::make($sections)
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