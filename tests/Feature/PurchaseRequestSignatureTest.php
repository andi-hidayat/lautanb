<?php

namespace Tests\Feature;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseRequestSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_page_shows_the_five_signature_pads_in_order(): void
    {
        $this->actingAs($this->userWithPosition(SignaturePosition::User));

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->assertSee('Signatures')
            ->assertSeeInOrder(['User', 'Finance', 'Accounting', 'Accounting SPV', 'Director'])
            ->assertFormFieldExists('user_signature')
            ->assertFormFieldExists('finance_signature')
            ->assertFormFieldExists('accounting_signature')
            ->assertFormFieldExists('accounting_spv_signature')
            ->assertFormFieldExists('director_signature')
            ->assertFormFieldEnabled('user_signature')
            ->assertFormFieldDisabled('finance_signature')
            ->assertFormFieldDisabled('accounting_signature')
            ->assertFormFieldDisabled('accounting_spv_signature')
            ->assertFormFieldDisabled('director_signature')
            ->assertFormComponentActionEnabled('user_signature', 'use_profile_user')
            ->assertFormComponentActionDoesNotExist('finance_signature', 'use_profile_finance')
            ->assertFormComponentActionDoesNotExist('accounting_signature', 'use_profile_accounting')
            ->assertFormComponentActionDoesNotExist('accounting_spv_signature', 'use_profile_accounting_spv')
            ->assertFormComponentActionDoesNotExist('director_signature', 'use_profile_director');
    }

    public function test_the_create_page_shows_each_signers_name_and_hides_undo_actions(): void
    {
        $requester = $this->userWithEmployee('Requester Name', 'staff', 'sales');
        $this->userWithEmployee('Finance Senior Name', 'senior', 'finance');
        $this->userWithEmployee('Accounting Staff Name', 'staff', 'accounting');
        $this->userWithEmployee('Accounting Supervisor Name', 'supervisor', 'accounting');
        $this->userWithEmployee('Director Name', 'director', 'bod');

        $this->actingAs($requester);

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->assertSeeInOrder([
                'Requester Name',
                'Finance Senior Name',
                'Accounting Staff Name',
                'Accounting Supervisor Name',
                'Director Name',
            ])
            ->assertSee('signature-pad-signer-name')
            ->assertFormComponentActionVisible('user_signature', 'clear')
            ->assertFormComponentActionHidden('user_signature', 'undo')
            ->assertFormComponentActionHidden('finance_signature', 'undo')
            ->assertFormComponentActionHidden('accounting_signature', 'undo')
            ->assertFormComponentActionHidden('accounting_spv_signature', 'undo')
            ->assertFormComponentActionHidden('director_signature', 'undo');
    }

    public function test_the_signed_by_name_falls_back_to_the_logged_in_user_when_requester_is_unset(): void
    {
        $this->actingAs($this->userWithEmployee('Andi Hidayat', 'staff', 'sales'));

        Livewire::test(CreatePurchaseRequest::class)
            ->set('data.requested_by', null)
            ->assertSee('Andi Hidayat');
    }

    public function test_when_user_signs_on_create_actions_on_finance_accounting_spv_and_director_are_inactive(): void
    {
        $this->actingAs($this->userWithPosition(SignaturePosition::User));

        Livewire::test(CreatePurchaseRequest::class)
            ->assertOk()
            ->fillForm([
                'user_signature' => $this->signature('user'),
            ])
            ->assertFormFieldEnabled('user_signature')
            ->assertFormFieldDisabled('finance_signature')
            ->assertFormFieldDisabled('accounting_signature')
            ->assertFormFieldDisabled('accounting_spv_signature')
            ->assertFormFieldDisabled('director_signature')
            ->assertFormComponentActionEnabled('user_signature', 'clear_manual_user')
            ->assertFormComponentActionDoesNotExist('finance_signature', 'use_profile_finance')
            ->assertFormComponentActionDoesNotExist('accounting_signature', 'use_profile_accounting')
            ->assertFormComponentActionDoesNotExist('accounting_spv_signature', 'use_profile_accounting_spv')
            ->assertFormComponentActionDoesNotExist('director_signature', 'use_profile_director')
            ->assertFormComponentActionDoesNotExist('finance_signature', 'clear_manual_finance')
            ->assertFormComponentActionDoesNotExist('accounting_signature', 'clear_manual_accounting')
            ->assertFormComponentActionDoesNotExist('accounting_spv_signature', 'clear_manual_accounting_spv')
            ->assertFormComponentActionDoesNotExist('director_signature', 'clear_manual_director');
    }

    public function test_manual_mode_allows_signing_with_large_modal_canvas(): void
    {
        $this->actingAs($this->userWithPosition(SignaturePosition::User));

        $signature = $this->signature('manual_signature');

        Livewire::test(CreatePurchaseRequest::class)
            ->callFormComponentAction('user_signature', 'clear_manual_user', data: [
                'manual_signature' => $signature,
            ])
            ->assertHasNoFormComponentActionErrors()
            ->assertFormSet([
                'user_signature' => $signature,
            ]);
    }

    public function test_a_position_can_only_sign_after_the_previous_position_has_signed(): void
    {
        $request = $this->createPurchaseRequest([
            'user_signature' => $this->signature(),
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);

        $this->actingAs($this->userWithPosition(SignaturePosition::Finance));

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDisabled('user_signature')
            ->assertFormFieldEnabled('finance_signature')
            ->assertFormFieldDisabled('accounting_signature')
            ->assertFormFieldDisabled('accounting_spv_signature')
            ->assertFormFieldDisabled('director_signature');
    }

    public function test_a_position_can_save_its_signature_and_advance_status(): void
    {
        $request = $this->createPurchaseRequest([
            'user_signature' => $this->signature(),
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);

        $this->actingAs($this->userWithPosition(SignaturePosition::Finance));

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->fillForm([
                'title' => $request->title,
                'pay_to' => 'PT Contoh',
                'bank_merchant_name' => 'BCA',
                'account_number' => '123456789',
                'finance_signature' => $this->signature('finance'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $request->refresh();

        $this->assertTrue($request->hasSignature(SignaturePosition::Finance));
        $this->assertEquals(PurchaseRequestStatus::PendingAccounting, $request->status);
    }

    public function test_a_signature_cannot_be_changed_after_it_has_been_signed(): void
    {
        $request = $this->createPurchaseRequest([
            'user_signature' => $this->signature(),
            'finance_signature' => $this->signature('finance'),
            'status' => PurchaseRequestStatus::PendingAccounting,
        ]);

        $this->actingAs($this->userWithPosition(SignaturePosition::Finance));

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDisabled('user_signature')
            ->assertFormFieldDisabled('finance_signature')
            ->assertFormFieldDisabled('accounting_signature');
    }

    public function test_multi_level_approval_flow_and_rejection(): void
    {
        $requester = $this->userWithEmployee('Pemohon', 'staff', 'sales');
        $finance = $this->userWithPosition(SignaturePosition::Finance);
        $accountingStaff = $this->userWithPosition(SignaturePosition::Accounting);
        $spv = $this->userWithPosition(SignaturePosition::AccountingSpv);
        $director = $this->userWithPosition(SignaturePosition::Director);

        $request = $this->createPurchaseRequest([
            'requested_by' => $requester->id,
            'user_signature' => $this->signature('requester'),
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);

        // 1. Finance approves -> moves to PendingAccounting
        $this->assertTrue($request->canBeApprovedBy($finance));
        $request->approve($finance, $this->signature('finance'));
        $this->assertEquals(PurchaseRequestStatus::PendingAccounting, $request->status);

        // 2. Accounting staff approves -> moves to PendingAccountingSpv
        $this->assertTrue($request->canBeApprovedBy($accountingStaff));
        $request->approve($accountingStaff, $this->signature('accounting_staff'));
        $this->assertEquals(PurchaseRequestStatus::PendingAccountingSpv, $request->status);

        // 3. Accounting SPV rejects with reason
        $this->assertTrue($request->canBeRejectedBy($spv));
        $request->reject($spv, 'Harga satuan terlalu mahal, mohon negosiasi ulang');
        $this->assertEquals(PurchaseRequestStatus::Rejected, $request->status);
        $this->assertEquals('Harga satuan terlalu mahal, mohon negosiasi ulang', $request->rejection_reason);
        $this->assertEquals($spv->id, $request->rejected_by);

        // 4. Requester resubmits -> returns to PendingFinance
        $this->assertTrue($request->canBeResubmittedBy($requester));
        $request->resubmit();
        $this->assertEquals(PurchaseRequestStatus::PendingFinance, $request->status);
        $this->assertNull($request->rejection_reason);
        $this->assertNull($request->finance_signature);

        // 5. Approvals from start to Director
        $request->approve($finance, $this->signature('finance'));
        $request->approve($accountingStaff, $this->signature('accounting_staff'));
        $request->approve($spv, $this->signature('spv'));
        $this->assertEquals(PurchaseRequestStatus::PendingDirector, $request->status);

        // 6. Director approves final
        $this->assertTrue($request->canBeApprovedBy($director));
        $request->approve($director, $this->signature('director'));
        $this->assertEquals(PurchaseRequestStatus::Approved, $request->status);
    }

    public function test_approvers_cannot_modify_requester_fields_and_only_submitter_can_edit_when_rejected(): void
    {
        $requester = $this->userWithEmployee('Pemohon Asli', 'staff', 'sales');
        $finance = $this->userWithPosition(SignaturePosition::Finance);

        $request = $this->createPurchaseRequest([
            'requested_by' => $requester->id,
            'title' => 'Judul Asli Pemohon',
            'pay_to' => 'Penerima Asli',
            'user_signature' => $this->signature(),
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);

        // When Finance views edit page, submitter fields are disabled:
        $this->actingAs($finance);

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDisabled('title')
            ->assertFormFieldDisabled('pay_to')
            ->assertFormFieldDisabled('bank_merchant_name')
            ->assertFormFieldDisabled('account_number')
            ->assertFormFieldDisabled('description')
            ->assertFormFieldEnabled('finance_signature');

        // When rejected, requester can edit submitter fields:
        $request->reject($finance, 'Tolong perbaiki');

        $this->actingAs($requester);

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertFormFieldEnabled('title')
            ->assertFormFieldEnabled('pay_to')
            ->assertFormFieldEnabled('bank_merchant_name')
            ->assertFormFieldEnabled('account_number')
            ->assertFormFieldEnabled('description');
    }

    public function test_approver_from_different_division_can_sign_without_purchase_tipe_validation_error(): void
    {
        PurchaseRequestType::create([
            'division' => 'it',
            'name' => 'Barang',
            'sort_order' => 1,
        ]);

        PurchaseRequestType::create([
            'division' => 'finance',
            'name' => 'Pengembalian Dana',
            'sort_order' => 1,
        ]);

        $financeUser = $this->userWithEmployee('Selvi Rahmawati', 'senior', 'finance');

        $request = $this->createPurchaseRequest([
            'division' => 'it',
            'purchase_tipe' => 'Barang',
            'pay_to' => 'PT Vendor IT',
            'bank_merchant_name' => 'Bank Mandiri',
            'account_number' => '987654321',
            'user_signature' => $this->signature('requester'),
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);

        $this->actingAs($financeUser);

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'finance_signature' => $this->signature('finance'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $request->refresh();
        $this->assertEquals(PurchaseRequestStatus::PendingAccounting, $request->status);
        $this->assertNotNull($request->finance_signature);
    }

    private function userWithPosition(SignaturePosition $position): User
    {
        return User::factory()->create([
            'employee_id' => Employee::factory()->create(match ($position) {
                SignaturePosition::User => ['position' => 'staff', 'division' => 'sales'],
                SignaturePosition::Finance => ['position' => 'senior', 'division' => 'finance'],
                SignaturePosition::Accounting => ['position' => 'staff', 'division' => 'accounting'],
                SignaturePosition::AccountingSpv => ['position' => 'supervisor', 'division' => 'accounting'],
                SignaturePosition::Reviewer => ['position' => 'manager', 'division' => 'bod'],
                SignaturePosition::Director => ['position' => 'director', 'division' => 'bod'],
            })->id,
        ]);
    }

    private function userWithEmployee(string $name, string $position, string $division): User
    {
        $employee = Employee::factory()->create([
            'company' => 'lbi',
            'name' => $name,
            'position' => $position,
            'division' => $division,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPurchaseRequest(array $attributes = []): PurchaseRequest
    {
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => User::factory()->create()->id,
            ...$attributes,
        ]);

        $request->items()->create([
            'item_required_name' => 'MacBook Pro M3',
            'unit_price' => 25000000,
            'quantity' => 1,
            'total_cost' => 25000000,
        ]);

        return $request;
    }

    private function signature(string $value = 'user'): string
    {
        return 'data:image/png;base64,'.base64_encode($value.' signature');
    }
}
