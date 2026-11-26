<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_request_document_settings', function (Blueprint $table) {
            $table->json('approval_steps')->nullable();
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->json('approval_steps')->nullable()->after('approval_flow');
        });

        $settings = DB::table('purchase_request_document_settings')
            ->get(['id', 'company', 'division']);

        foreach ($settings as $setting) {
            $steps = $this->defaultApprovalStepsFor($setting->company, $setting->division);

            if ($steps !== []) {
                DB::table('purchase_request_document_settings')
                    ->where('id', $setting->id)
                    ->update(['approval_steps' => json_encode($steps, JSON_THROW_ON_ERROR)]);
            }
        }

        $settingsByCompanyAndDivision = DB::table('purchase_request_document_settings')
            ->get(['company', 'division', 'approval_steps'])
            ->keyBy(fn (object $setting): string => $setting->company.'|'.$setting->division);

        DB::table('purchase_requests')
            ->select(['id', 'company', 'approval_flow', 'division'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $requests) use ($settingsByCompanyAndDivision): void {
                foreach ($requests as $request) {
                    $company = $request->approval_flow === 'lba' ? 'lba' : $request->company;
                    $setting = $settingsByCompanyAndDivision->get($company.'|'.$request->division);
                    $steps = $setting?->approval_steps
                        ? json_decode($setting->approval_steps, true, flags: JSON_THROW_ON_ERROR)
                        : $this->defaultApprovalStepsFor($company, $request->division);

                    if ($steps !== []) {
                        DB::table('purchase_requests')
                            ->where('id', $request->id)
                            ->update(['approval_steps' => json_encode($steps, JSON_THROW_ON_ERROR)]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('approval_steps');
        });

        Schema::table('purchase_request_document_settings', function (Blueprint $table) {
            $table->dropColumn('approval_steps');
        });
    }

    /**
     * @return array<int, array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}>
     */
    private function defaultApprovalStepsFor(string $company, string $division): array
    {
        $requester = [
            'stage' => 'user',
            'approver_type' => 'requester',
            'position' => null,
            'division' => null,
            'signer_name' => null,
        ];

        if ($company !== 'lba') {
            return [
                $requester,
                $this->positionStep('finance', 'senior_staff', 'finance'),
                $this->positionStep('accounting', 'staff', 'accounting'),
                $this->positionStep('accounting_spv', 'supervisor', 'accounting'),
                $this->positionStep('director', 'director', 'bod'),
            ];
        }

        $stages = match ($division) {
            'hr', 'ga', 'hrga', 'accounting', 'crewing', 'marketing', 'marcom' => [
                'finance_lba',
                'finance_senior_lba',
                'accounting_tax',
                'director1',
                'director_main',
            ],
            'finance' => [
                'finance_senior_lba',
                'accounting_tax',
                'director1',
                'director_main',
            ],
            'legal' => [
                'finance_senior_lba',
                'accounting_tax',
                'director_main',
            ],
            'operational', 'operations', 'operasional', 'ops_crw' => [
                'finance_lba',
                'finance_senior_lba',
                'accounting_tax',
                'ops_fleet_manager',
                'director1',
                'director2',
                'director_main',
            ],
            'procurement', 'purchasing' => [
                'finance_lba',
                'finance_senior_lba',
                'accounting_tax',
                'ops_fleet_manager',
                'director1',
                'director_main',
            ],
            default => [],
        };
        $signerNames = [
            'finance_lba' => 'Dhya',
            'finance_senior_lba' => 'Selvi Rahmawati',
            'accounting_tax' => 'Tubagus Sururi',
            'ops_fleet_manager' => 'Denny',
            'director1' => 'Firmannudin',
            'director2' => 'Sayid Segap',
            'director_main' => 'Kabal Yudhanegara',
        ];

        return [
            $requester,
            ...array_map(
                fn (string $stage): array => [
                    'stage' => $stage,
                    'approver_type' => 'name',
                    'position' => null,
                    'division' => null,
                    'signer_name' => $signerNames[$stage],
                ],
                $stages,
            ),
        ];
    }

    /**
     * @return array{stage: string, approver_type: string, position: string, division: string, signer_name: null}
     */
    private function positionStep(string $stage, string $position, string $division): array
    {
        return [
            'stage' => $stage,
            'approver_type' => 'position',
            'position' => $position,
            'division' => $division,
            'signer_name' => null,
        ];
    }
};
