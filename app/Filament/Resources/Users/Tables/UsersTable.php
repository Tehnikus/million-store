<?php
namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\Alignment;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    // ->searchable()
                    // ->sortable()
                    ->label(__('admin.users.fields.role'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->alignment(Alignment::Center)
                    ->searchable(),

                TextColumn::make('locale')
                    ->label(__('admin.users.fields.locale'))
                    ->alignment(Alignment::Center)
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('admin.users.fields.created_at'))
                    ->alignment(Alignment::Center),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}