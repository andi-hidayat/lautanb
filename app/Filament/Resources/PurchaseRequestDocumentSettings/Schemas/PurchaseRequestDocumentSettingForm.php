<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings\Schemas;

use App\Enums\SignaturePosition;
use App\Models\Employee;
use App\Models\PurchaseRequestDocumentSetting;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PurchaseRequestDocumentSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company')
                    ->label('Company')
                    ->options([
                        'lbi' => 'LBI',
                        'lba' => 'LBA',
                    ])
                    ->default(fn (): ?string => auth()->user()?->company ?? 'lbi')
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $set('approval_steps', PurchaseRequestDocumentSetting::defaultApprovalStepsFor(
                            $state ?? 'lbi',
                            (string) $get('division'),
                        ));
                    })
                    ->required(),
                Select::make('division')
                    ->options(PurchaseRequestDocumentSetting::DIVISIONS)
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $set('approval_steps', PurchaseRequestDocumentSetting::defaultApprovalStepsFor(
                            (string) $get('company'),
                            (string) $state,
                        ));
                    })
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('company', $get('company')),
                    ),
                TextInput::make('document_number')
                    ->label('Doc. Number')
                    ->required()
                    ->maxLength(255),
                TextInput::make('revision_number')
                    ->label('Revision Number')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('effective_date')
                    ->required(),
                TextInput::make('number_prefix')
                    ->label('Purchase Request Number Prefix')
                    ->helperText('Number Format PREFIX/YEAR/SEQUENCE, ex PR/IT/2026/0001.')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64),
                Repeater::make('approval_steps')
                    ->label('Approval Workflow')
                    ->default(fn (Get $get): array => PurchaseRequestDocumentSetting::defaultApprovalStepsFor(
                        (string) $get('company'),
                        (string) $get('division'),
                    ))
                    ->minItems(2)
                    ->reorderable()
                    ->rules([
                        function (string $attribute, mixed $value, Closure $fail): void {
                            $message = PurchaseRequestDocumentSetting::approvalStepsValidationMessage($value);

                            if ($message !== null) {
                                $fail($message);
                            }
                        },
                    ])
                    ->schema([
                        Select::make('stage')
                            ->label('Approval Stage')
                            ->options(SignaturePosition::options())
                            ->required(),
                        Select::make('approver_type')
                            ->label('Approver Rule')
                            ->options([
                                'requester' => 'Request creator',
                                'position' => 'Employee position + division',
                                'name' => 'Employee name',
                            ])
                            ->live()
                            ->required(),
                        Select::make('position')
                            ->label('Employee Position')
                            ->options(fn (): array => Employee::query()
                                ->whereNotNull('position')
                                ->distinct()
                                ->orderBy('position')
                                ->pluck('position', 'position')
                                ->all())
                            ->visible(fn (Get $get): bool => $get('approver_type') === 'position')
                            ->required(fn (Get $get): bool => $get('approver_type') === 'position'),
                        Select::make('division')
                            ->label('Employee Division')
                            ->options(PurchaseRequestDocumentSetting::DIVISIONS)
                            ->visible(fn (Get $get): bool => $get('approver_type') === 'position')
                            ->required(fn (Get $get): bool => $get('approver_type') === 'position'),
                        TextInput::make('signer_name')
                            ->label('Employee Name')
                            ->visible(fn (Get $get): bool => $get('approver_type') === 'name')
                            ->required(fn (Get $get): bool => $get('approver_type') === 'name')
                            ->maxLength(255),
                    ])
                    ->columnSpanFull()
                    ->required(),
            ]);
    }
}
