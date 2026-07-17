<?php

namespace App\Filament\Resources\BlogAuthors\Schemas;

use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Filament\Schemas\LanguageTabs;
use App\Filament\Schemas\Tabs\DescriptionTab;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Section;
class BlogAuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        $languages = Filament::getTenant()
            ->languages()
            ->wherePivot('is_active', true)
            ->get();
        return $schema
            ->components([

                Section::make()
                    ->columns([
                        'sm'    => 5,
                        'xl'    => 5,
                        '2xl'   => 5,
                    ])
                    ->schema([
                        FileUpload::make('avatar.path')
                            ->label(__('admin.blog.authors.fields.avatar'))
                            ->image()
                            ->disk('public')
                            ->directory('blog/authors/' . Filament::getTenant()->id)
                            ->getUploadedFileNameForStorageUsing(
                                fn($file) => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                . '-' . Str::random(6)
                                . '.' . $file->getClientOriginalExtension(),
                            )
                            ->avatar()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'xl' => 1,
                                '2xl' => 1,
                            ])
                            ->alignCenter()
                            ->extraFieldWrapperAttributes(['class' => 'label-center']),

                        Repeater::make('social_links')
                            ->label(__('admin.blog.authors.fields.social_links'))
                            ->reorderable()
                            ->addActionLabel(__('admin.blog.authors.buttons.add_social_link'))
                            ->compact()
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.blog.authors.fields.social_platform'))->width('200px'),
                                TableColumn::make(__('admin.blog.authors.fields.social_url')),
                            ])
                            ->schema([
                                Select::make('platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'twitter' => 'X (Twitter)',
                                        'linkedin' => 'LinkedIn',
                                        'youtube' => 'YouTube',
                                        'telegram' => 'Telegram',
                                    ])
                                    ->hiddenLabel(),

                                TextInput::make('url')
                                    ->url()
                                    ->hiddenLabel(),
                            ])
                            ->compact()
                            ->columnSpan([
                                'default'   => 4,
                                'sm'        => 4,
                                'xl'        => 4,
                                '2xl'       => 4,
                            ]),
                    ])
                    ->columnSpanFull(),

                LanguageTabs::make($languages, [
                    [DescriptionTab::class, ['withSlug' => true]],
                ])
                    ->columnSpanFull(),
            ]);
    }
}
