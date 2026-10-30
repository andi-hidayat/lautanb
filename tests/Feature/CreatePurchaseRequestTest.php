<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
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
            ->assertSee('Total Required Cost');
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
