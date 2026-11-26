<?php

namespace Tests\Feature;

use App\Actions\PurchaseRequests\IssuePurchaseRequestDocument;
use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreatePurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_page_renders_with_the_item_repeater(): void
    {
        $user = $this->userInDivision('it');
        $this->configureDivisionDocuments('it');
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->assertSee('PT LAUTAN BERKAH')
            ->assertSee('INTERNASIONAL')
            ->assertSee('FORM PENGELUARAN DANA')
            ->assertSee('Doc. Number')
            ->assertSee('Rev. Number')
            ->assertSee('Issue Date')
            ->assertSee('Effective Date')
            ->assertSee('page')
            ->assertSee('Purchase Title')
            ->assertSee('Add Item')
            ->assertSee('Total Required Cost')
            ->assertFormSet([
                'purchase_request_number' => 'PR/IT/'.today()->year.'/0001',
            ]);
    }

    public function test_a_purchase_request_can_be_created_with_items(): void
    {
        $user = $this->userInDivision('it');
        $this->configureDivisionDocuments('it');
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm([
                'title' => 'Permintaan Laptop Baru',
                'needed_at' => '2026-11-01',
                'requested_by' => $user->id,
                'document_number' => 'FORGED-DOC',
                'revision_number' => '99',
                'issue_date' => '2000-01-01',
                'effective_date' => '2000-01-01',
                'purchase_request_number' => 'FORGED-NUMBER',
                'description' => 'Kebutuhan kantor untuk tim developer.',
                'pay_to' => 'PT Contoh',
                'bank_merchant_name' => 'BCA',
                'account_number' => '123456789',
                'items' => [
                    [
                        'item_required_name' => 'MacBook Pro M3',
                        'unit_price' => 25000000,
                        'quantity' => 2,
                        'total_cost' => 50000000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $user->id,
            'pay_to' => 'PT Contoh',
            'bank_merchant_name' => 'BCA',
            'account_number' => '123456789',
            'division' => 'it',
            'document_number' => 'FRM-IT',
            'revision_number' => '02',
            'issue_date' => today()->toDateString(),
            'effective_date' => '2026-01-01',
            'purchase_request_number' => 'PR/IT/'.today()->year.'/0001',
        ]);

        $this->assertDatabaseHas('purchase_request_items', [
            'item_required_name' => 'MacBook Pro M3',
            'unit_price' => 25000000,
            'quantity' => 2,
            'total_cost' => 50000000,
        ]);
    }

    public function test_a_user_with_lba_access_can_create_a_request_for_lba(): void
    {
        $user = $this->userInDivision('operational');
        $user->update([
            'can_create_pr_lba' => true,
            'can_sign_pr_lba' => false,
        ]);
        $this->configureDivisionDocuments('operational');
        PurchaseRequestDocumentSetting::create([
            'company' => 'lba',
            'division' => 'operational',
            'document_number' => 'FRM-LBA',
            'revision_number' => '01',
            'effective_date' => '2026-01-01',
            'number_prefix' => 'PR/LBA',
        ]);
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm([
                'company' => 'lba',
                'title' => 'Permintaan Operasional LBA',
                'needed_at' => '2026-11-01',
                'requested_by' => $user->id,
                'description' => 'Kebutuhan operasional.',
                'pay_to' => 'PT Contoh',
                'bank_merchant_name' => 'BCA',
                'account_number' => '123456789',
                'items' => [
                    [
                        'item_required_name' => 'Peralatan operasional',
                        'unit_price' => 100000,
                        'quantity' => 1,
                        'total_cost' => 100000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'Permintaan Operasional LBA',
            'requested_by' => $user->id,
            'company' => 'lba',
            'approval_flow' => 'lba',
            'document_number' => 'FRM-LBA',
            'purchase_request_number' => 'PR/LBA/'.today()->year.'/0001',
        ]);
    }

    public function test_a_user_with_only_lba_create_access_defaults_to_lba(): void
    {
        $user = $this->userInDivision('operational');
        $user->update([
            'can_create_pr_lbi' => false,
            'can_create_pr_lba' => true,
        ]);
        $this->assertTrue($user->fresh()->canCreatePurchaseRequestsFor('lba'));
        PurchaseRequestDocumentSetting::create([
            'company' => 'lba',
            'division' => 'operational',
            'document_number' => 'FRM-LBA',
            'revision_number' => '01',
            'effective_date' => '2026-01-01',
            'number_prefix' => 'PR/LBA',
        ]);
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->assertFormFieldExists('title')
            ->fillForm([
                'title' => 'Permintaan Operasional LBA',
                'needed_at' => '2026-11-01',
                'requested_by' => $user->id,
                'pay_to' => 'PT Contoh',
                'bank_merchant_name' => 'BCA',
                'account_number' => '123456789',
                'items' => [
                    [
                        'item_required_name' => 'Peralatan operasional',
                        'unit_price' => 100000,
                        'quantity' => 1,
                        'total_cost' => 100000,
                    ],
                ],
            ])
            ->call('create');

        $this->assertDatabaseHas('purchase_requests', [
            'title' => 'Permintaan Operasional LBA',
            'requested_by' => $user->id,
            'company' => 'lba',
            'approval_flow' => 'lba',
            'document_number' => 'FRM-LBA',
            'purchase_request_number' => 'PR/LBA/'.today()->year.'/0001',
        ]);
    }

    public function test_a_user_with_an_unmapped_lba_division_defaults_to_the_lbi_approval_flow(): void
    {
        $user = User::factory()->create([
            'employee_id' => Employee::factory()->create([
                'company' => 'lba',
                'division' => 'it',
            ])->id,
            'can_create_pr_lbi' => true,
            'can_create_pr_lba' => true,
        ]);
        $this->configureDivisionDocuments('it');
        $this->actingAs($user);

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->assertFormSet(['company' => 'lbi'])
            ->assertSeeInOrder(['Finance', 'Accounting', 'Accounting SPV', 'Director'])
            ->assertDontSee('Finance LBA');
    }

    public function test_a_user_cannot_create_a_request_for_a_company_without_create_access(): void
    {
        $user = $this->userInDivision('operational');

        $this->expectException(AuthorizationException::class);

        app(IssuePurchaseRequestDocument::class)->forCreator($user, 'lba');
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

    private function configureDivisionDocuments(string $division): void
    {
        PurchaseRequestDocumentSetting::create([
            'division' => $division,
            'document_number' => 'FRM-'.strtoupper($division),
            'revision_number' => '02',
            'effective_date' => '2026-01-01',
            'number_prefix' => 'PR/'.strtoupper($division),
        ]);
    }
}
