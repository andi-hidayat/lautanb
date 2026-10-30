<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseRequestDocumentSettings\Pages\CreatePurchaseRequestDocumentSetting;
use App\Filament\Resources\PurchaseRequestDocumentSettings\Pages\ListPurchaseRequestDocumentSettings;
use App\Filament\Resources\PurchaseRequestDocumentSettings\PurchaseRequestDocumentSettingResource;
use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseRequestDocumentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_division_uses_its_own_number_and_existing_requests_keep_their_metadata_snapshot(): void
    {
        $this->travelTo('2026-06-15 10:00:00');

        $itUser = $this->userInDivision('it');
        $financeUser = $this->userInDivision('finance');
        $itSetting = $this->configureDivisionDocuments('it');
        $this->configureDivisionDocuments('finance');

        $this->actingAs($itUser);
        $itRequest = $this->createPurchaseRequest($itUser, 'IT request one');

        Livewire::test(EditPurchaseRequest::class, ['record' => $itRequest->getRouteKey()])
            ->fillForm([
                ...$this->purchaseRequestData($itUser, 'IT request one'),
                'requested_by' => $financeUser->id,
                'document_number' => 'FORGED-DOC',
                'revision_number' => '99',
                'issue_date' => '2000-01-01',
                'effective_date' => '2000-01-01',
                'purchase_request_number' => 'FORGED-NUMBER',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $itSetting->update([
            'document_number' => 'FRM-IT-UPDATED',
            'revision_number' => '03',
            'effective_date' => '2026-06-01',
        ]);

        $this->createPurchaseRequest($itUser, 'IT request two');

        $this->actingAs($financeUser);
        $this->createPurchaseRequest($financeUser, 'Finance request');

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'IT request one',
            'division' => 'it',
            'document_number' => 'FRM-IT',
            'revision_number' => '02',
            'issue_date' => '2026-06-15',
            'effective_date' => '2026-01-01',
            'purchase_request_number' => 'PR/IT/2026/0001',
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'IT request two',
            'division' => 'it',
            'document_number' => 'FRM-IT-UPDATED',
            'revision_number' => '03',
            'issue_date' => '2026-06-15',
            'effective_date' => '2026-06-01',
            'purchase_request_number' => 'PR/IT/2026/0002',
        ]);

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'Finance request',
            'division' => 'finance',
            'document_number' => 'FRM-FINANCE',
            'purchase_request_number' => 'PR/FINANCE/2026/0001',
        ]);
    }

    public function test_purchase_request_creation_fails_when_the_division_has_no_document_settings(): void
    {
        $user = $this->userInDivision('it');
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm($this->purchaseRequestData($user, 'Missing settings'))
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'Pengaturan dokumen untuk divisi Anda belum tersedia. Hubungi admin.',
            ]);

        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_purchase_request_creation_fails_when_the_creator_has_no_division(): void
    {
        $employee = Employee::factory()->create([
            'company' => 'lbi',
            'division' => null,
        ]);
        $user = User::factory()->create([
            'employee_id' => $employee->id,
        ]);
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm($this->purchaseRequestData($user, 'Missing division'))
            ->call('create')
            ->assertHasFormErrors([
                'title' => 'Akun Anda belum memiliki divisi. Hubungi admin untuk memperbarui data karyawan.',
            ]);

        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_only_staff_can_manage_document_settings(): void
    {
        $regularUser = $this->userInDivision('it');
        $this->actingAs($regularUser);

        $this->assertFalse(PurchaseRequestDocumentSettingResource::canViewAny());
        $this->assertFalse(PurchaseRequestDocumentSettingResource::canCreate());
        Livewire::test(ListPurchaseRequestDocumentSettings::class)
            ->assertForbidden();

        $adminEmployee = Employee::factory()->create([
            'company' => 'lbi',
            'role' => 'admin',
            'division' => 'it',
        ]);
        $admin = User::factory()->create([
            'employee_id' => $adminEmployee->id,
        ]);
        $this->actingAs($admin);

        $this->assertTrue(PurchaseRequestDocumentSettingResource::canViewAny());
        $this->assertTrue(PurchaseRequestDocumentSettingResource::canCreate());
        Livewire::test(ListPurchaseRequestDocumentSettings::class)
            ->assertOk();
    }

    public function test_admin_can_create_a_division_document_setting(): void
    {
        $adminEmployee = Employee::factory()->create([
            'company' => 'lbi',
            'role' => 'admin',
            'division' => 'it',
        ]);
        $this->actingAs(User::factory()->create([
            'employee_id' => $adminEmployee->id,
        ]));

        Livewire::test(CreatePurchaseRequestDocumentSetting::class)
            ->fillForm([
                'division' => 'it',
                'document_number' => 'FRM-IT',
                'revision_number' => '01',
                'effective_date' => '2026-01-01',
                'number_prefix' => 'PR/IT',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_request_document_settings', [
            'division' => 'it',
            'document_number' => 'FRM-IT',
            'revision_number' => '01',
            'effective_date' => '2026-01-01',
            'number_prefix' => 'PR/IT',
            'next_number' => 1,
        ]);

        Livewire::test(CreatePurchaseRequestDocumentSetting::class)
            ->fillForm([
                'division' => 'finance',
                'document_number' => 'FRM-FINANCE',
                'revision_number' => '01',
                'effective_date' => '2026-01-01',
                'number_prefix' => 'PR/IT',
            ])
            ->call('create')
            ->assertHasFormErrors(['number_prefix']);

        $this->assertDatabaseCount('purchase_request_document_settings', 1);
    }

    private function userInDivision(string $division): User
    {
        $employee = Employee::factory()->create([
            'company' => 'lbi',
            'division' => $division,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
        ]);
    }

    private function configureDivisionDocuments(string $division): PurchaseRequestDocumentSetting
    {
        return PurchaseRequestDocumentSetting::create([
            'division' => $division,
            'document_number' => 'FRM-'.strtoupper($division),
            'revision_number' => '02',
            'effective_date' => '2026-01-01',
            'number_prefix' => 'PR/'.strtoupper($division),
        ]);
    }

    private function createPurchaseRequest(User $user, string $title): PurchaseRequest
    {
        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm($this->purchaseRequestData($user, $title))
            ->call('create')
            ->assertHasNoFormErrors();

        return PurchaseRequest::query()->where('title', $title)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function purchaseRequestData(User $user, string $title): array
    {
        return [
            'title' => $title,
            'requested_by' => $user->id,
            'pay_to' => 'PT Contoh',
            'bank_merchant_name' => 'BCA',
            'account_number' => '123456789',
            'items' => [
                [
                    'item_required_name' => 'Laptop',
                    'unit_price' => 10000000,
                    'quantity' => 1,
                    'total_cost' => 10000000,
                ],
            ],
        ];
    }
}
