<?php

namespace App\Filament\Pages;

use App\Models\Catalog\Category;
use App\Models\Store\StoreSettings;
use Filament\Facades\Filament;
use Filament\Forms\Components\{Builder, Builder\Block, RichEditor, Select, TextInput};
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Actions\Action;

use App\Filament\Support\AdminMenu\NavigationItem;
use App\Filament\Support\AdminMenu\HasCentralizedNavigation;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

class DesignMenuSettings extends Page
{
    protected string $view = 'filament.pages.simple-form';
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([ self::menuBuilder('menu_settings') ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->extraAttributes(['style' => 'min-width: 200px'])
                                ->label(__('admin.common.buttons.save')
                            ),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    private const MAX_DEPTH = 5;

    public static function menuBuilder(string $name, int $depth = 1): Builder
    {
        return Builder::make($name)
            ->hiddenLabel()
            ->blocks(self::menuBlocks($depth))
            ->collapsible()
            ->collapsed($depth > 3)
            ->cloneable()
            ->blockNumbers(false)
            ->addActionAlignment(Alignment::End)
            ->addAction(fn (Action $action)         => $action->color('primary')->icon(Heroicon::Plus)->label(__('admin.design.menu_editor.buttons.add_parent')))
            ->addBetweenAction(fn (Action $action)  => $action->color('primary')->icon(Heroicon::OutlinedPlusCircle)->label(__('admin.design.menu_editor.buttons.add_child')))
            ->expandAllAction(fn (Action $action)   => $action->color('primary')->icon(Heroicon::ArrowsPointingOut)->size(Size::ExtraLarge))
            ->collapseAllAction(fn (Action $action) => $action->color('primary')->icon(Heroicon::ArrowsPointingIn)->size(Size::ExtraLarge));
    }

    private static function menuBlocks(int $depth): array
    {
        // Add child builder until MAX_DEPTH is reached
        $children = fn (): array => $depth < self::MAX_DEPTH
            ? [self::menuBuilder('children', $depth + 1)]
            : [];

        return [
            // Sub-parent, may have children
            Block::make('category')
                ->label(__('admin.design.menu_editor.blocks.category'))
                // ->label(fn (?array $state) => __('admin.design.menu_editor.blocks.category') . json_encode($state))
                ->icon(NavigationItem::Categories->icon())
                ->schema(fn () => [
                    Select::make('category_id')
                        ->options(fn () => []) // Preload categories
                        ->preload()
                        ->getSearchResultsUsing(fn (string $search): array => Category::where('name', 'ilike', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                        ->getOptionLabelUsing(fn ($value): ?string => Category::find($value)?->name)
                        ->searchable()
                        ->required(),
                    ...$children(),
                ]),

            Block::make('manufacturer')
                ->label(__('admin.design.menu_editor.blocks.manufacturer'))
                ->icon(NavigationItem::Manufacturers->icon())
                ->schema(fn () => [
                    Select::make('manufacturer_id')
                        ->options(fn () => [])
                        ->searchable()
                        ->required(),
                    ...$children(),
                ]),

            Block::make('title')
                ->label(__('admin.design.menu_editor.blocks.title'))
                ->icon(Heroicon::Underline)
                ->schema(fn () => [
                    TextInput::make('title')
                        ->required(),
                    ...$children(),
                ]),

            // Leaves without children
            Block::make('rich_text')
                ->label(__('admin.design.menu_editor.blocks.rich_text'))
                ->icon(Heroicon::OutlinedDocumentPlus)
                ->schema([
                    RichEditor::make('content')->required(),
                ]),
            Block::make('product')
                ->label(__('admin.design.menu_editor.blocks.product_card'))
                ->icon(NavigationItem::Products->icon())
                ->schema([
                    Select::make('manufacturer_id')
                        ->options(fn () => [])
                        ->searchable()
                        ->required(),
                ]),



            // Block::make('bestsellers')
            //     ->schema([
            //         TextInput::make('limit')->numeric()->default(6),
            //     ]),
        ];
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->only('menu_settings') ?? []);
    }

    public function save(): void
    {
        $store = Filament::getTenant();
        $formData = $this->form->getState();

        $record = StoreSettings::updateOrCreate(
            ['store_id' => $store->id],
            $formData
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
        return __('admin.design.menu_editor.subheading');
    }


    // Some repeating navigation methods in one place
    use HasCentralizedNavigation;
    protected static function getMenuConfig(): NavigationItem
    {
        return NavigationItem::MenuEditor;
    }
}
