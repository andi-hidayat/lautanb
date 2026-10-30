<?php

namespace Tests\Feature;

use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequest;
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

    public function test_a_position_can_save_its_signature(): void
    {
        $request = $this->createPurchaseRequest([
            'user_signature' => $this->signature(),
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
    }

    public function test_a_signature_cannot_be_changed_after_it_has_been_signed(): void
    {
        $request = $this->createPurchaseRequest([
            'user_signature' => $this->signature(),
            'finance_signature' => $this->signature('finance'),
        ]);

        $this->actingAs($this->userWithPosition(SignaturePosition::Finance));

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDisabled('user_signature')
            ->assertFormFieldDisabled('finance_signature')
            ->assertFormFieldDisabled('accounting_signature');
    }

    private function userWithPosition(SignaturePosition $position): User
    {
        return User::factory()->create([
            'employee_id' => Employee::factory()->create(match ($position) {
                SignaturePosition::User => ['position' => 'staff', 'division' => 'sales'],
                SignaturePosition::Finance => ['position' => 'senior', 'division' => 'finance'],
                SignaturePosition::Accounting => ['position' => 'staff', 'division' => 'accounting'],
                SignaturePosition::AccountingSpv => ['position' => 'supervisor', 'division' => 'accounting'],
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
