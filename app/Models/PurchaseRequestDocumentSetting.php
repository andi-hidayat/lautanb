<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'division',
    'document_number',
    'revision_number',
    'effective_date',
    'number_prefix',
    'next_number',
    'sequence_year',
    'company',
    'approval_steps',
])]
class PurchaseRequestDocumentSetting extends Model
{
    public const array DIVISIONS = [
        'finance' => 'Finance',
        'accounting' => 'Accounting',
        'hr' => 'HR',
        'ga' => 'GA',
        'hrga' => 'HR and GA',
        'it' => 'IT',
        'legal' => 'Legal',
        'marketing' => 'Marketing',
        'sales' => 'Sales',
        'secretary' => 'Secretary',
        'logistic' => 'Logistic',
        'hse' => 'HSE',
        'operational' => 'Operational',
        'crewing' => 'Crewing',
        'ops_crw' => 'Operations and Crewing',
        'procurement' => 'Procurement',
        'technical' => 'Technical',
        'auditor' => 'Auditor',
        'bod' => 'BOD',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'next_number' => 'integer',
            'sequence_year' => 'integer',
            'approval_steps' => 'array',
        ];
    }

    /**
     * @return array<int, array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}>
     */
    public function approvalSteps(): array
    {
        $approvalSteps = $this->getAttribute('approval_steps');

        return is_array($approvalSteps)
            ? $approvalSteps
            : self::defaultApprovalStepsFor((string) $this->company, (string) $this->division);
    }

    public static function approvalStepsValidationMessage(mixed $approvalSteps): ?string
    {
        if (! is_array($approvalSteps)) {
            return 'Pengaturan alur persetujuan belum valid. Hubungi admin.';
        }

        $approvalSteps = array_values($approvalSteps);
        $approvalStepsAreArrays = count(array_filter($approvalSteps, 'is_array')) === count($approvalSteps);
        $approvalPositions = $approvalStepsAreArrays
            ? array_map(
                static fn (array $step): ?SignaturePosition => is_string($step['stage'] ?? null)
                    ? SignaturePosition::tryFrom($step['stage'])
                    : null,
                $approvalSteps,
            )
            : [];
        $approvalStages = $approvalStepsAreArrays ? array_column($approvalSteps, 'stage') : [];

        if (
            ! $approvalStepsAreArrays
            || count($approvalPositions) < 2
            || $approvalPositions[0] !== SignaturePosition::User
            || ($approvalSteps[0]['approver_type'] ?? null) !== 'requester'
            || in_array(null, $approvalPositions, true)
            || count(array_unique($approvalStages)) !== count($approvalStages)
        ) {
            return 'Pengaturan alur persetujuan belum valid. Hubungi admin.';
        }

        foreach (array_slice($approvalSteps, 1) as $index => $step) {
            $position = $approvalPositions[$index + 1];
            $hasValidApprover = match ($step['approver_type'] ?? null) {
                'position' => filled($step['position'] ?? null) && filled($step['division'] ?? null),
                'name' => filled($step['signer_name'] ?? null),
                default => false,
            };

            if (! $hasValidApprover || PurchaseRequestStatus::forSignaturePosition($position) === null) {
                return 'Pengaturan penandatangan pada alur persetujuan belum valid. Hubungi admin.';
            }
        }

        return null;
    }

    /**
     * @return array<int, SignaturePosition>
     */
    public function signingPositions(): array
    {
        return self::positionsFromApprovalSteps($this->approvalSteps());
    }

    /**
     * @return array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}|null
     */
    public function approvalStep(SignaturePosition $position): ?array
    {
        foreach ($this->approvalSteps() as $step) {
            if (($step['stage'] ?? null) === $position->value) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}>
     */
    public static function approvalStepsFor(string $company, string $division): array
    {
        $setting = self::query()
            ->where('company', $company)
            ->where('division', $division)
            ->first();

        return $setting?->approvalSteps() ?? self::defaultApprovalStepsFor($company, $division);
    }

    /**
     * @return array<int, SignaturePosition>
     */
    public static function signingPositionsFor(string $company, string $division): array
    {
        return self::positionsFromApprovalSteps(self::approvalStepsFor($company, $division));
    }

    /**
     * @return array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}|null
     */
    public static function approvalStepFor(
        string $company,
        string $division,
        SignaturePosition $position,
    ): ?array {
        foreach (self::approvalStepsFor($company, $division) as $step) {
            if (($step['stage'] ?? null) === $position->value) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Get approval stages that the user is configured to sign.
     *
     * @return array<int, SignaturePosition>
     */
    public static function approvalPositionsForUser(User $user): array
    {
        $positions = [];

        foreach (self::query()->get() as $setting) {
            if (! $user->canSignPurchaseRequestsFor((string) $setting->company)) {
                continue;
            }

            foreach ($setting->approvalSteps() as $step) {
                if (! self::stepMatchesUser($step, $user)) {
                    continue;
                }

                $position = SignaturePosition::from($step['stage']);

                if (PurchaseRequestStatus::forSignaturePosition($position) !== null) {
                    $positions[] = $position;
                }
            }
        }

        return array_values(array_unique($positions, SORT_REGULAR));
    }

    /**
     * Get the configured employee name for an approval stage.
     */
    public function signerName(SignaturePosition $position): ?string
    {
        $step = $this->approvalStep($position);

        return $step === null ? null : self::signerNameForStep($step);
    }

    /**
     * @return array<int, array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}>
     */
    public static function defaultApprovalStepsFor(string $company, string $division): array
    {
        $requester = self::requesterStep();

        if ($company === 'lbi') {
            return [
                $requester,
                self::positionStep(SignaturePosition::Finance, 'senior_staff', 'finance'),
                self::positionStep(SignaturePosition::Accounting, 'staff', 'accounting'),
                self::positionStep(SignaturePosition::AccountingSpv, 'supervisor', 'accounting'),
                self::positionStep(SignaturePosition::Director, 'director', 'bod'),
            ];
        }

        if ($company !== 'lba') {
            return [];
        }

        $stages = match ($division) {
            'hr', 'ga', 'hrga', 'accounting', 'crewing', 'marketing', 'marcom' => [
                SignaturePosition::FinanceLba,
                SignaturePosition::FinanceSeniorLba,
                SignaturePosition::AccountingTax,
                SignaturePosition::DirectorOne,
                SignaturePosition::DirectorMain,
            ],
            'finance' => [
                SignaturePosition::FinanceSeniorLba,
                SignaturePosition::AccountingTax,
                SignaturePosition::DirectorOne,
                SignaturePosition::DirectorMain,
            ],
            'legal' => [
                SignaturePosition::FinanceSeniorLba,
                SignaturePosition::AccountingTax,
                SignaturePosition::DirectorMain,
            ],
            'operational', 'operations', 'operasional', 'ops_crw' => [
                SignaturePosition::FinanceLba,
                SignaturePosition::FinanceSeniorLba,
                SignaturePosition::AccountingTax,
                SignaturePosition::OpsFleetManager,
                SignaturePosition::DirectorOne,
                SignaturePosition::DirectorTwo,
                SignaturePosition::DirectorMain,
            ],
            'procurement', 'purchasing' => [
                SignaturePosition::FinanceLba,
                SignaturePosition::FinanceSeniorLba,
                SignaturePosition::AccountingTax,
                SignaturePosition::OpsFleetManager,
                SignaturePosition::DirectorOne,
                SignaturePosition::DirectorMain,
            ],
            default => [],
        };
        $signerNames = [
            SignaturePosition::FinanceLba->value => 'Dhya',
            SignaturePosition::FinanceSeniorLba->value => 'Selvi Rahmawati',
            SignaturePosition::AccountingTax->value => 'Tubagus Sururi',
            SignaturePosition::OpsFleetManager->value => 'Denny',
            SignaturePosition::DirectorOne->value => 'Firmannudin',
            SignaturePosition::DirectorTwo->value => 'Sayid Segap',
            SignaturePosition::DirectorMain->value => 'Kabal Yudhanegara',
        ];

        return [
            $requester,
            ...array_map(
                fn (SignaturePosition $position): array => self::nameStep($position, $signerNames[$position->value]),
                $stages,
            ),
        ];
    }

    /**
     * Format the purchase request number for this document setting.
     */
    public function formatPurchaseRequestNumber(?int $sequence = null, ?int $year = null): string
    {
        $year ??= today()->year;
        $sequence ??= ($this->sequence_year === $year ? $this->next_number : 1);

        return $this->number_prefix
            .'/'.$year
            .'/'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return array<int, SignaturePosition>
     */
    private static function positionsFromApprovalSteps(array $steps): array
    {
        return array_map(
            static fn (array $step): SignaturePosition => SignaturePosition::from($step['stage']),
            $steps,
        );
    }

    /**
     * @param  array<string, mixed>  $step
     */
    public static function stepMatchesUser(array $step, User $user): bool
    {
        return match ($step['approver_type'] ?? null) {
            'name' => self::userNameMatches($user, (string) ($step['signer_name'] ?? '')),
            'position' => $user->position === ($step['position'] ?? null)
                && $user->division === ($step['division'] ?? null),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $step
     */
    public static function signerNameForStep(array $step): ?string
    {
        if (($step['approver_type'] ?? null) === 'name') {
            $signerName = (string) ($step['signer_name'] ?? '');

            return Employee::query()
                ->where('name', 'like', $signerName.'%')
                ->orderBy('id')
                ->value('name')
                ?? $signerName;
        }

        if (($step['approver_type'] ?? null) === 'position') {
            return Employee::query()
                ->where('position', $step['position'] ?? null)
                ->where('division', $step['division'] ?? null)
                ->orderBy('id')
                ->value('name');
        }

        return null;
    }

    private static function userNameMatches(User $user, string $signerName): bool
    {
        $expectedName = mb_strtolower(trim($signerName));
        $userName = mb_strtolower(trim((string) $user->name));

        return $expectedName !== ''
            && ($userName === $expectedName || str_starts_with($userName, $expectedName.' '));
    }

    /**
     * @return array{stage: string, approver_type: string, position: null, division: null, signer_name: null}
     */
    private static function requesterStep(): array
    {
        return [
            'stage' => SignaturePosition::User->value,
            'approver_type' => 'requester',
            'position' => null,
            'division' => null,
            'signer_name' => null,
        ];
    }

    /**
     * @return array{stage: string, approver_type: string, position: string, division: string, signer_name: null}
     */
    private static function positionStep(SignaturePosition $position, string $employeePosition, string $division): array
    {
        return [
            'stage' => $position->value,
            'approver_type' => 'position',
            'position' => $employeePosition,
            'division' => $division,
            'signer_name' => null,
        ];
    }

    /**
     * @return array{stage: string, approver_type: string, position: null, division: null, signer_name: string}
     */
    private static function nameStep(SignaturePosition $position, string $signerName): array
    {
        return [
            'stage' => $position->value,
            'approver_type' => 'name',
            'position' => null,
            'division' => null,
            'signer_name' => $signerName,
        ];
    }
}
