<?php
namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->email() // Validate email
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                // Dehydrated password - only changed if password field is not empty
                TextInput::make('password')
                    ->label(__('admin.users.fields.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->maxLength(255),

                // Admin panel user language switch
                Select::make('locale')
                    ->label(__('admin.users.fields.locale'))
                    ->options([
                        'en' => 'English',
                        'uk' => 'Українська',
                        'ru' => 'Русский',
                    ])
                    ->required()
                    ->default('en'),

            ]);
    }
}