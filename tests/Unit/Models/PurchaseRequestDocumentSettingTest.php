<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Tests\TestCase;

class PurchaseRequestDocumentSettingTest extends TestCase
{
    public function test_finance_lba_requests_skip_the_finance_approval_stage(): void
    {
        $stages = array_column(
            PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lba', 'finance'),
            'stage',
        );

        $this->assertSame([
            'user',
            'finance_senior_lba',
            'accounting_tax',
            'director1',
            'director_main',
        ], $stages);
    }

    public function test_operational_lba_requests_include_ops_and_both_director_stages(): void
    {
        $stages = array_column(
            PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lba', 'operational'),
            'stage',
        );

        $this->assertSame([
            'user',
            'finance_lba',
            'finance_senior_lba',
            'accounting_tax',
            'ops_fleet_manager',
            'director1',
            'director2',
            'director_main',
        ], $stages);
    }

    public function test_ops_crw_uses_the_operational_lba_approval_workflow(): void
    {
        $stages = array_column(
            PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lba', 'ops_crw'),
            'stage',
        );

        $this->assertSame([
            'user',
            'finance_lba',
            'finance_senior_lba',
            'accounting_tax',
            'ops_fleet_manager',
            'director1',
            'director2',
            'director_main',
        ], $stages);
    }

    public function test_lbi_requests_keep_the_existing_approval_sequence(): void
    {
        $stages = array_column(
            PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lbi', 'operational'),
            'stage',
        );

        $this->assertSame([
            'user',
            'finance',
            'accounting',
            'accounting_spv',
            'director',
        ], $stages);
    }

    public function test_lba_signer_name_matches_case_insensitively_and_allows_a_name_suffix(): void
    {
        $user = new User;
        $user->setRelation('employee', new Employee(['name' => 'Dhya Finance']));

        $this->assertTrue(PurchaseRequestDocumentSetting::stepMatchesUser([
            'stage' => 'finance_lba',
            'approver_type' => 'name',
            'position' => null,
            'division' => null,
            'signer_name' => 'dhya',
        ], $user));
    }

    public function test_approval_workflow_must_start_with_the_requester(): void
    {
        $approvalSteps = PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lbi', 'it');
        $approvalSteps[0]['stage'] = 'reviewer';

        $this->assertSame(
            'Pengaturan alur persetujuan belum valid. Hubungi admin.',
            PurchaseRequestDocumentSetting::approvalStepsValidationMessage($approvalSteps),
        );
    }

    public function test_approval_workflow_requires_a_valid_approver_for_each_stage(): void
    {
        $approvalSteps = PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lbi', 'it');
        $approvalSteps[1]['signer_name'] = null;
        $approvalSteps[1]['position'] = null;
        $approvalSteps[1]['approver_type'] = 'name';

        $this->assertSame(
            'Pengaturan penandatangan pada alur persetujuan belum valid. Hubungi admin.',
            PurchaseRequestDocumentSetting::approvalStepsValidationMessage($approvalSteps),
        );
    }

    public function test_approval_workflow_cannot_repeat_a_stage(): void
    {
        $approvalSteps = PurchaseRequestDocumentSetting::defaultApprovalStepsFor('lbi', 'it');
        $approvalSteps[2]['stage'] = 'finance';

        $this->assertSame(
            'Pengaturan alur persetujuan belum valid. Hubungi admin.',
            PurchaseRequestDocumentSetting::approvalStepsValidationMessage($approvalSteps),
        );
    }
}
