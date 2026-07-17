<?php

namespace App\Filament\Resources\Languages\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\HtmlString;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('admin.languages.fields.name'))
                    ->helperText(__('admin.languages.helpers.name')),
                TextInput::make('iso_code')
                    ->label(__('admin.languages.fields.iso_code'))
                    ->required()
                    ->maxLength(5)
                    ->unique(ignoreRecord: true) // Required so DB write does not fails because of own iso_code already exists
                    ->helperText(new HtmlString(__('admin.languages.helpers.iso_code'))), 
                TextInput::make('locale')
                    ->label(__('admin.languages.fields.locale'))
                    ->helperText(__('admin.languages.helpers.locale'))
                    ->required()
                    ->maxLength(10),

                Select::make('ts_config')
                    ->label(__('admin.languages.fields.fulltext_search_language'))
                    ->options([
                        'simple'        => 'Simple',
                        'english'       => 'English',
                        'russian'       => 'Russian',
                        'german'        => 'German',
                        'french'        => 'French',
                        'spanish'       => 'Spanish',
                        'italian'       => 'Italian',
                        'portuguese'    => 'Portuguese',
                        'dutch'         => 'Dutch',
                        'danish'        => 'Danish',
                        'finnish'       => 'Finnish',
                        'hungarian'     => 'Hungarian',
                        'norwegian'     => 'Norwegian',
                        'swedish'       => 'Swedish',
                        'turkish'       => 'Turkish',
                    ])
                    ->required()
                    ->default('simple')
                    ->helperText(__('admin.languages.helpers.fulltext_search_language')),
                    // FileUpload component requires php artisan storage:link, otherwise the file will be uploaded but will not be displayed in backend/frontend
                    FileUpload::make('image')
                        ->label(__('admin.languages.fields.flag'))
                        ->disk('public')        // Means store locally, no CDN
                        ->directory('flags')    // path where files fill be uploaded: storage/app/public/flags/...
                        ->image()               // Only images can be uploaded. Also gives crop/preview in UI
                        ->imageEditor()         // Built in image editor
                        ->nullable(),           // because table column languages.image is also nullable

                    // Select with ->relationship() autocomplete with search in related table
                    Select::make('default_currency_id')
                        ->label(__('admin.languages.fields.default_currency'))
                        ->relationship('defaultCurrency', 'name') // 'defaultCurrency' - method name in app\Models\Language.php to find currency by %name%
                        ->searchable()
                        ->preload() // Preload all currencies
                        ->required(), // restrictOnDelete() in migration forces to have this relation, so this field is required

                    Toggle::make('is_active')
                        ->label(__('admin.languages.fields.is_active'))
                        ->default(true),
            ]);
    }
}
