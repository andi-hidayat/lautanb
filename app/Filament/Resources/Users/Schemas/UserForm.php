<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Employee;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->options(function (?User $record) {
                        return Employee::query()
                            ->where(function ($query) use ($record) {
                                $query->whereDoesntHave('user');

                                if ($record?->employee_id) {
                                    $query->orWhere('id', $record->employee_id);
                                }
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->unique(table: 'users', column: 'employee_id', ignoreRecord: true)
                    ->disabled(fn (?User $record): bool => ! auth()->user()?->isStaff())
                    ->dehydrated()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $set('email', Employee::find($state)?->email);
                    }),
                TextInput::make('email')
                    ->label('Email address')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('password')
                    ->password()
                    ->formatStateUsing(fn () => null)
                    ->saved(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                FileUpload::make('signature_path')
                    ->label('Signature')
                    ->helperText('Upload a PNG or JPG image (max 2 MB). A transparent PNG works best.')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                    ->maxSize(2048)
                    ->disk('local')
                    ->visibility('private')
                    ->directory('signatures')
                    ->preventFilePathTampering(
                        allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'signatures/'),
                    ),
            ]);
    }
}
