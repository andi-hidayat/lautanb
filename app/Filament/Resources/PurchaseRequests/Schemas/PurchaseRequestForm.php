<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\SignaturePosition;
use App\Models\Employee;
use App\Models\PurchasePlan;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseRequestType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section Proporsional (Logo & Nama PT, Judul Form, Info Dokumen)
                Grid::make([
                    'default' => 12,
                ])
                    ->schema([
                        // Kolom 1 (Kiri): Logo & Nama Perusahaan (3 Kolom = 25%)
                        Placeholder::make('company_logo')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; height: 100%; padding-top: 2px;">
                                    <img src="'.asset('images/lbilogo.svg').'" style="height: 80px; width: auto; max-width: 100%; object-fit: contain; margin-bottom: 8px;" alt="Logo PT Lautan Berkah Internasional">
                                    <span style="font-weight: 700; font-size: 13px; line-height: 1.25; letter-spacing: 0.05em; text-transform: uppercase;" class="text-gray-900 dark:text-white">PT LAUTAN BERKAH</span>
                                    <span style="font-weight: 700; font-size: 13px; line-height: 1.25; letter-spacing: 0.05em; text-transform: uppercase;" class="text-gray-900 dark:text-white">INTERNASIONAL</span>
                                </div>
                            '))
                            ->columnSpan([
                                'default' => 3,
                            ]),

                        // Kolom 2 (Tengah): Judul Form Utama (6 Kolom = 50%, Sejajar Rata Atas)
                        Placeholder::make('form_title')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div style="display: flex; align-items: flex-start; justify-content: center; text-align: center; height: 100%; padding-top: 4px;">
                                    <h1 style="font-weight: 800; font-size: 24px; line-height: 1.2; letter-spacing: 0.05em; text-transform: uppercase; margin: 0;" class="text-gray-900 dark:text-white">
                                        FORM PENGELUARAN DANA
                                    </h1>
                                </div>
                            '))
                            ->columnSpan([
                                'default' => 6,
                            ]),

                        // Kolom 3 (Kanan): 5 Input Dokumen dengan Prefix Sejajar (3 Kolom = 25%)
                        Grid::make(1)
                            ->columnSpan([
                                'default' => 3,
                            ])
                            ->extraAttributes([
                                'class' => 'space-y-1.5 [&_.fi-input-wrp-label]:w-28 [&_.fi-input-wrp-label]:text-xs [&_.fi-input-wrp-label]:font-medium',
                            ])
                            ->schema([
                                TextInput::make('document_number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->prefix('Doc. Number')
                                    ->default(fn (): ?string => self::currentDocumentSetting()?->document_number),
                                TextInput::make('revision_number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->prefix('Rev. Number')
                                    ->default(fn (): ?string => self::currentDocumentSetting()?->revision_number),
                                TextInput::make('issue_date')
                                    ->disabled()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->prefix('Issue Date')
                                    ->default(fn (): string => today()->toDateString()),
                                TextInput::make('effective_date')
                                    ->disabled()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->prefix('Effective Date')
                                    ->default(fn (): ?string => self::currentDocumentSetting()?->effective_date?->toDateString()),
                                TextInput::make('purchase_request_number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->hiddenLabel()
                                    ->prefix('Number')
                                    ->helperText('Nomor surat diterbitkan otomatis saat permintaan disimpan.'),
                            ]),
                    ])
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Purchase Title')
                    ->required(),
                Select::make('requested_by')
                    ->relationship('requestedBy', 'email', fn ($query) => $query->with('employee'))
                    ->getOptionLabelUsing(fn ($value) => User::with('employee')->find($value)?->employee?->name)
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::id())
                    ->disabled(fn (string $operation) => $operation === 'create')  // lock saat create
                    ->dehydrated()
                    ->live(),
                TextInput::make('needed_at')
                    ->label('Waktu Pengajuan')
                    ->readOnly()
                    ->dehydrated()
                    ->default(fn () => now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('Y-m-d H:i:s'))
                    ->extraAttributes([
                        'wire:ignore' => true,
                    ])
                    ->extraInputAttributes([
                        'x-data' => 'realtimeClock',
                    ]),
                Select::make('purchase_tipe')
                    ->label('Purchase Tipe')
                    ->options(function (): array {
                        $division = Auth::user()?->division;

                        if ($division) {
                            $options = PurchaseRequestType::optionsForDivision($division);

                            if (! empty($options)) {
                                return $options;
                            }
                        }

                        // Fallback to the 'default' set when the user's division has no specific types
                        return PurchaseRequestType::optionsForDivision('default');
                    })
                    ->native(false),

                Textarea::make('description')
                    ->rows(7)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        TextInput::make('pay_to')
                            ->label('Pay To')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('bank_merchant_name')
                            ->label('Bank / Merchant')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('account_number')
                            ->label('Bank / Merchant Account Number')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->relationship()
                    ->label('Items')
                    ->addActionLabel('Add Item')
                    ->addAction(
                        fn (Action $action): Action => $action
                            ->button()
                            ->color('primary')
                    )
                    ->reorderable(false)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->deleteAction(
                        fn (Action $action): Action => $action
                            ->visible(fn (Repeater $component): bool => count($component->getRawState() ?? []) > 1)
                    )
                    ->live()
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                TextInput::make('item_required_name')
                                    ->label('Purchase Item Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->prefix('Rp')
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        self::updateItemTotal($get, $set);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        self::updateItemTotal($get, $set);
                                    })
                                    ->columnSpan(1),
                                TextInput::make('reference')
                                    ->label('Reference')
                                    ->nullable()
                                    ->maxLength(255),

                                TextInput::make('total_cost')
                                    ->label('Total Price')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Grid::make(1)
                    ->schema([
                        TextEntry::make('total_required_cost_label')
                            ->hiddenLabel()
                            ->weight('bold')
                            ->size('lg')
                            ->state('Total Required Cost')
                            ->alignEnd(),
                        TextEntry::make('items_total')
                            ->hiddenLabel()
                            ->weight('bold')
                            ->size('lg')
                            ->state(fn (Get $get): string => PurchasePlan::formatRupiah(
                                PurchasePlan::calculateItemsTotalCost($get('items')),
                            ))
                            ->alignEnd(),
                    ])
                    ->columnSpanFull(),

                Section::make('Signatures')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => fn (Get $get): int => self::submittedByAccounting($get) ? 4 : 5,
                        ])
                            ->extraAttributes([
                                'class' => 'gap-6 md:gap-x-8',
                            ])
                            ->schema(array_map(
                                fn (SignaturePosition $position): SignaturePad => self::signaturePad($position),
                                SignaturePosition::cases(),
                            )),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Determine whether the submitted-by user belongs to the Accounting division.
     * Used to hide the Accounting signature pads when an accounting staff submits the request.
     */
    private static function submittedByAccounting(Get $get): bool
    {
        $requestedById = $get('requested_by');

        if (blank($requestedById)) {
            return false;
        }

        $division = User::with('employee')
            ->find($requestedById)
            ?->employee
            ?->division;

        return $division === 'accounting';
    }

    /**
     * Build the signature pad for the given position.
     */
    private static function signaturePad(SignaturePosition $position): SignaturePad
    {
        $isAccountingPosition = $position === SignaturePosition::Accounting;

        return SignaturePad::make($position->signatureField())
            ->label($position->getLabel())
            ->exportBackgroundColor('#fff')
            ->undoable(false)
            ->live()
            ->extraAttributes([
                // Mencegah scroll/zoom halaman saat jari menyentuh area canvas
                'style' => 'touch-action: none; -ms-touch-action: none;',
            ])
            ->extraFieldWrapperAttributes([
                'class' => 'signature-pad-centered',
            ])
            ->hidden(fn (Get $get): bool => $isAccountingPosition && self::submittedByAccounting($get))
            ->disabled(fn (Get $get, ?PurchaseRequest $record = null): bool => ! self::canSignPosition($position, $get, $record))
            ->helperText(fn (Get $get, ?PurchaseRequest $record = null): string => self::signatureHint($position, $get, $record))
            ->belowContent([
                Placeholder::make($position->value.'_signer_name')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => self::signerName($position, $get('requested_by')))
                    ->extraAttributes([
                        'class' => 'signature-pad-signer-name text-center font-semibold text-gray-950 dark:text-white',
                    ]),
                Action::make('use_profile_'.$position->value)
                    ->label('Tempel Otomatis')
                    ->tooltip('Gunakan tanda tangan profil '.$position->getLabel())
                    ->visible(fn (Get $get, ?PurchaseRequest $record = null): bool => self::canSignPosition($position, $get, $record))
                    ->disabled(fn (Get $get, ?PurchaseRequest $record = null): bool => ! self::canSignPosition($position, $get, $record))
                    ->action(function (Set $set, Get $get, ?PurchaseRequest $record = null) use ($position): void {
                        if (! self::canSignPosition($position, $get, $record)) {
                            return;
                        }

                        $user = self::findUserForPosition($position, $get('requested_by'));
                        $dataUrl = $user?->getSignatureDataUrl();

                        if (! $dataUrl) {
                            Notification::make()
                                ->title('Tanda tangan profil belum tersedia')
                                ->body('Belum ada tanda tangan profil untuk posisi '.$position->getLabel().'. Silakan gambar manual di canvas atau unggah tanda tangan pada profil.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $set($position->signatureField(), $dataUrl);

                        Notification::make()
                            ->title('Tanda tangan profil dimuat')
                            ->body('Tanda tangan '.$position->getLabel().' berhasil dimuat dari profil '.($user->name ?? $user->email).'.')
                            ->success()
                            ->send();
                    }),
                Action::make('clear_manual_'.$position->value)
                    ->label('Manual')
                    ->icon('heroicon-m-pencil-square')
                    ->tooltip('Tanda tangan manual pada canvas')
                    ->visible(fn (Get $get, ?PurchaseRequest $record = null): bool => self::canSignPosition($position, $get, $record))
                    ->disabled(fn (Get $get, ?PurchaseRequest $record = null): bool => ! self::canSignPosition($position, $get, $record))
                    ->modalHeading('Tanda Tangan Manual - '.$position->getLabel())
                    ->modalDescription('Silakan tanda tangan pada area canvas besar di bawah ini.')
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Simpan Tanda Tangan')
                    ->form([
                        SignaturePad::make('manual_signature')
                            ->hiddenLabel()
                            ->exportBackgroundColor('#fff')
                            ->undoable(false)
                            ->extraAttributes([
                                'class' => '[&_canvas]:!h-80',
                                'style' => 'touch-action: none; -ms-touch-action: none;',
                            ])
                            ->default(fn (Get $get) => $get($position->signatureField()))
                            ->required(),
                    ])
                    ->action(function (array $data, Set $set, Get $get, ?PurchaseRequest $record = null) use ($position): void {
                        if (! self::canSignPosition($position, $get, $record)) {
                            return;
                        }

                        $set($position->signatureField(), $data['manual_signature']);

                        Notification::make()
                            ->title('Tanda tangan manual disimpan')
                            ->body('Tanda tangan '.$position->getLabel().' berhasil disimpan.')
                            ->success()
                            ->send();
                    })
                    ->extraModalFooterActions([
                        Action::make('clear_signature_'.$position->value)
                            ->label('Kosongkan')
                            ->color('danger')
                            ->cancelParentActions()
                            ->action(function (Set $set) use ($position): void {
                                $set($position->signatureField(), null);

                                Notification::make()
                                    ->title('Canvas dikosongkan')
                                    ->body('Tanda tangan '.$position->getLabel().' berhasil dikosongkan.')
                                    ->info()
                                    ->send();
                            }),
                    ]),
            ]);
    }

    private static function signerName(SignaturePosition $position, mixed $requestedById): string
    {
        if ($position === SignaturePosition::User) {
            $requesterName = User::with('employee')->find($requestedById)?->name;

            return $requesterName ?? Auth::user()?->name ?? 'Nama belum tersedia';
        }

        return self::findEmployeeForPosition($position)?->name ?? 'Nama belum tersedia';
    }

    private static function findEmployeeForPosition(SignaturePosition $position): ?Employee
    {
        [$employeePosition, $division] = self::employeeAttributesForPosition($position);

        return Employee::query()
            ->where('position', $employeePosition)
            ->where('division', $division)
            ->orderBy('id')
            ->first()
            ?? Employee::query()
                ->where('position', $position->value)
                ->orderBy('id')
                ->first();
    }

    /**
     * @return array{string, string}
     */
    private static function employeeAttributesForPosition(SignaturePosition $position): array
    {
        return match ($position) {
            SignaturePosition::Finance => ['senior', 'finance'],
            SignaturePosition::Accounting => ['staff', 'accounting'],
            SignaturePosition::AccountingSpv => ['supervisor', 'accounting'],
            SignaturePosition::Director => ['director', 'bod'],
            SignaturePosition::User => throw new \LogicException('The requester does not have a division signer mapping.'),
        };
    }

    private static function userMatchesPosition(?User $user, SignaturePosition $position): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->position === $position->value) {
            return true;
        }

        if ($position === SignaturePosition::User) {
            return false;
        }

        [$employeePosition, $division] = self::employeeAttributesForPosition($position);

        return $user->position === $employeePosition && $user->division === $division;
    }

    /**
     * Find the user associated with the given signature position.
     */
    public static function findUserForPosition(SignaturePosition $position, mixed $requestedById = null): ?User
    {
        // 1. For User position, check requested_by user if provided
        if ($position === SignaturePosition::User && filled($requestedById)) {
            $user = User::with('employee')->find($requestedById);
            if ($user && filled($user->signature_path)) {
                return $user;
            }
        }

        // 2. Check currently authenticated user if matching position and has signature
        $authUser = Auth::user();
        if (self::userMatchesPosition($authUser, $position) && filled($authUser->signature_path)) {
            return $authUser;
        }

        $userWithSignature = null;

        if ($position !== SignaturePosition::User) {
            [$employeePosition, $division] = self::employeeAttributesForPosition($position);

            $userWithSignature = User::query()
                ->with('employee')
                ->whereHas('employee', fn (Builder $query): Builder => $query
                    ->where('position', $employeePosition)
                    ->where('division', $division))
                ->whereNotNull('signature_path')
                ->orderBy('id')
                ->first()
                ?? User::query()
                    ->with('employee')
                    ->whereHas('employee', fn (Builder $query): Builder => $query->where('position', $position->value))
                    ->whereNotNull('signature_path')
                    ->orderBy('id')
                    ->first();
        }

        if ($userWithSignature) {
            return $userWithSignature;
        }

        // 4. Fallback for User position: requested_by user even without signature
        if ($position === SignaturePosition::User && filled($requestedById)) {
            return User::with('employee')->find($requestedById);
        }

        // 5. Fallback: authenticated user if matching position
        if (self::userMatchesPosition($authUser, $position)) {
            return $authUser;
        }

        if ($position !== SignaturePosition::User) {
            [$employeePosition, $division] = self::employeeAttributesForPosition($position);

            return User::query()
                ->with('employee')
                ->whereHas('employee', fn (Builder $query): Builder => $query
                    ->where('position', $employeePosition)
                    ->where('division', $division))
                ->orderBy('id')
                ->first()
                ?? User::query()
                    ->with('employee')
                    ->whereHas('employee', fn (Builder $query): Builder => $query->where('position', $position->value))
                    ->orderBy('id')
                    ->first();
        }

        return null;
    }

    /**
     * Determine whether the current user can sign the given position.
     */
    public static function canSignPosition(SignaturePosition $position, Get $get, ?PurchaseRequest $record = null): bool
    {
        // 1. If already signed and persisted in the record, it cannot be changed.
        if (filled($record?->getAttribute($position->signatureField()))) {
            return false;
        }

        // 2. On create page (record does not exist yet):
        // Only User position can be signed. Finance, Accounting, Accounting SPV, Direktur cannot sign on create.
        if ($record === null || ! $record->exists) {
            if ($position !== SignaturePosition::User) {
                return false;
            }

            return Auth::user()?->position === SignaturePosition::User->value
                || (Auth::user() !== null && (int) $get('requested_by') === (int) Auth::id())
                || (bool) Auth::user()?->isStaff();
        }

        // 3. On existing record:
        // User signature cannot be signed/changed if already persisted.
        if ($position === SignaturePosition::User) {
            return blank($record->getAttribute($position->signatureField()))
                && (Auth::user()?->position === SignaturePosition::User->value || (int) $record->requested_by === (int) Auth::id() || (bool) Auth::user()?->isStaff());
        }

        // 4. For approval positions (Finance, Accounting, Accounting SPV, Director):
        // Current user must match this position.
        if (! self::userMatchesPosition(Auth::user(), $position)) {
            return false;
        }

        // 5. The previous position in sequence must have already signed.
        $previousPosition = $position->previous();

        return $previousPosition === null || filled($get($previousPosition->signatureField()) ?? $record->getAttribute($previousPosition->signatureField()));
    }

    /**
     * Get the helper text explaining the state of a signature pad.
     */
    private static function signatureHint(SignaturePosition $position, Get $get, ?PurchaseRequest $record = null): string
    {
        if (filled($record?->getAttribute($position->signatureField())) || filled($get($position->signatureField()))) {
            return 'Signed';
        }

        if ($record === null || ! $record->exists) {
            if ($position !== SignaturePosition::User) {
                return 'Waiting for the '.$position->previous()?->getLabel().' signature.';
            }

            if (! self::canSignPosition($position, $get, $record)) {
                return 'Akses dibatasi untuk '.$position->getLabel();
            }

            return 'Sign here.';
        }

        if (! self::userMatchesPosition(Auth::user(), $position) && ! (Auth::user()?->isStaff() && $position === SignaturePosition::User)) {
            return 'Akses dibatasi untuk '.$position->getLabel();
        }

        $previousPosition = $position->previous();

        if ($previousPosition !== null && blank($get($previousPosition->signatureField()) ?? $record->getAttribute($previousPosition->signatureField()))) {
            return 'Waiting for the '.$previousPosition->getLabel().' signature.';
        }

        return 'Sign here.';
    }

    /**
     * Update the item total based on the unit price and quantity.
     */
    public static function updateItemTotal(Get $get, Set $set): void
    {
        $set('total_cost', PurchaseRequestItem::calculateTotalCost([
            'unit_price' => $get('unit_price'),
            'quantity' => $get('quantity'),
        ]));
    }

    private static function currentDocumentSetting(): ?PurchaseRequestDocumentSetting
    {
        $division = Auth::user()?->division;

        if (! $division) {
            return null;
        }

        return PurchaseRequestDocumentSetting::query()
            ->where('division', $division)
            ->first();
    }
}
