<?php

namespace Tests\Feature;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_approved_and_signed_request_cannot_be_edited(): void
    {
        $requester = User::factory()->create();
        $user = User::factory()->create();
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Selesai',
            'requested_by' => $requester->id,
            'user_signature' => 'requester-signature',
            'finance_signature' => 'finance-signature',
            'accounting_signature' => 'accounting-signature',
            'accounting_spv_signature' => 'spv-signature',
            'director_signature' => 'director-signature',
            'status' => PurchaseRequestStatus::Approved,
        ]);
        $this->actingAs($user);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertActionHidden('edit');
        Livewire::test(ListPurchaseRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->assertTableActionHidden('edit', $request);
        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertForbidden();
    }

    public function test_a_request_that_is_not_fully_approved_and_signed_remains_editable(): void
    {
        $requester = User::factory()->create();
        $user = User::factory()->create();
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Belum Selesai',
            'requested_by' => $requester->id,
            'user_signature' => 'requester-signature',
            'finance_signature' => 'finance-signature',
            'accounting_signature' => 'accounting-signature',
            'accounting_spv_signature' => 'spv-signature',
            'director_signature' => null,
            'status' => PurchaseRequestStatus::PendingDirector,
        ]);
        $this->actingAs($user);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertActionVisible('edit');
    }

    public function test_the_list_page_does_not_offer_approve_or_delete_actions_to_any_approval_level(): void
    {
        $requester = User::factory()->create();
        $approvalLevels = [
            ['position' => 'senior', 'division' => 'finance', 'status' => PurchaseRequestStatus::PendingFinance],
            ['position' => 'staff', 'division' => 'accounting', 'status' => PurchaseRequestStatus::PendingAccounting],
            ['position' => 'supervisor', 'division' => 'accounting', 'status' => PurchaseRequestStatus::PendingAccountingSpv],
            ['position' => 'director', 'division' => 'bod', 'status' => PurchaseRequestStatus::PendingDirector],
        ];
        $recordsByApprover = [];

        foreach ($approvalLevels as $approvalLevel) {
            $employee = Employee::factory()->create([
                'position' => $approvalLevel['position'],
                'division' => $approvalLevel['division'],
            ]);
            $approver = User::factory()->create(['employee_id' => $employee->id]);
            $request = PurchaseRequest::create([
                'title' => 'Permintaan '.$approvalLevel['position'],
                'requested_by' => $requester->id,
                'status' => $approvalLevel['status'],
            ]);

            $recordsByApprover[] = [$approver, $request];
        }

        foreach ($recordsByApprover as [$approver, $request]) {
            $this->actingAs($approver);

            Livewire::test(ListPurchaseRequests::class)
                ->assertCanSeeTableRecords([$request])
                ->assertTableActionDoesNotExist('approve')
                ->assertTableActionDoesNotExist('reject')
                ->assertTableBulkActionDoesNotExist('delete');
        }
    }

    public function test_the_view_page_renders_each_approval_action_once(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('signatures/finance.png', 'signature-image');
        $requester = User::factory()->create();
        $financeEmployee = Employee::factory()->create([
            'position' => 'senior',
            'division' => 'finance',
        ]);
        $financeUser = User::factory()->create([
            'employee_id' => $financeEmployee->id,
            'signature_path' => 'signatures/finance.png',
        ]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,cmVxdWVzdGVy',
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $this->actingAs($financeUser);

        $html = Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->html();

        preg_match_all('/<button\b[^>]*>.*?<\/button>/s', $html, $buttons);
        $buttonLabels = array_map(
            static fn (string $button): string => trim(strip_tags($button)),
            $buttons[0],
        );

        $this->assertSame(1, count(array_filter($buttonLabels, static fn (string $label): bool => $label === 'Tempel Otomatis')));
        $this->assertSame(1, count(array_filter($buttonLabels, static fn (string $label): bool => $label === 'Manual')));
        $this->assertSame(1, count(array_filter($buttonLabels, static fn (string $label): bool => $label === 'Reject')));
    }

    public function test_approvers_can_choose_profile_signature_or_manual_signature_on_the_view_page(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('signatures/finance.png', 'signature-image');
        $requester = User::factory()->create();
        $financeEmployee = Employee::factory()->create([
            'position' => 'senior',
            'division' => 'finance',
        ]);
        $financeUser = User::factory()->create([
            'employee_id' => $financeEmployee->id,
            'signature_path' => 'signatures/finance.png',
        ]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,cmVxdWVzdGVy',
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $this->actingAs($financeUser);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertSeeInOrder([
                'Informasi Permintaan',
                'Signatures',
                'Tempel Otomatis',
                'Manual',
            ]);
    }

    public function test_the_view_page_places_signatures_in_a_full_width_row(): void
    {
        $requester = User::factory()->create();
        $financeEmployee = Employee::factory()->create([
            'position' => 'senior',
            'division' => 'finance',
        ]);
        $financeUser = User::factory()->create([
            'employee_id' => $financeEmployee->id,
        ]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,cmVxdWVzdGVy',
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $this->actingAs($financeUser);

        $page = Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk();
        $signatureForm = $page->instance()->getSchema('infolist')->getComponent(
            fn ($component): bool => $component instanceof EmbeddedSchema && $component->getName() === 'form',
        );

        $this->assertInstanceOf(EmbeddedSchema::class, $signatureForm);
        $this->assertSame('full', $signatureForm->getColumnSpan('default'));
    }

    public function test_the_view_page_saves_the_inline_signature_when_approving(): void
    {
        $requester = User::factory()->create();
        $financeEmployee = Employee::factory()->create([
            'position' => 'senior',
            'division' => 'finance',
        ]);
        $financeUser = User::factory()->create([
            'employee_id' => $financeEmployee->id,
        ]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,cmVxdWVzdGVy',
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $signature = 'data:image/png;base64,Zm9vLWZpbmFuY2Utc2lnbmF0dXJl';
        $this->actingAs($financeUser);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->set('data.finance_signature', $signature)
            ->call('approve')
            ->assertHasNoErrors();

        $request->refresh();

        $this->assertSame($signature, $request->finance_signature);
        $this->assertSame(PurchaseRequestStatus::PendingAccounting, $request->status);
    }

    public function test_the_view_page_enables_the_inline_signature_for_each_approval_level_through_director(): void
    {
        $requester = User::factory()->create();
        $approvalLevels = [
            [
                'position' => 'senior',
                'division' => 'finance',
                'status' => PurchaseRequestStatus::PendingFinance,
                'field' => 'finance_signature',
                'signatures' => ['user_signature' => 'requester-signature'],
            ],
            [
                'position' => 'staff',
                'division' => 'accounting',
                'status' => PurchaseRequestStatus::PendingAccounting,
                'field' => 'accounting_signature',
                'signatures' => [
                    'user_signature' => 'requester-signature',
                    'finance_signature' => 'finance-signature',
                ],
            ],
            [
                'position' => 'supervisor',
                'division' => 'accounting',
                'status' => PurchaseRequestStatus::PendingAccountingSpv,
                'field' => 'accounting_spv_signature',
                'signatures' => [
                    'user_signature' => 'requester-signature',
                    'finance_signature' => 'finance-signature',
                    'accounting_signature' => 'accounting-signature',
                ],
            ],
            [
                'position' => 'director',
                'division' => 'bod',
                'status' => PurchaseRequestStatus::PendingDirector,
                'field' => 'director_signature',
                'signatures' => [
                    'user_signature' => 'requester-signature',
                    'finance_signature' => 'finance-signature',
                    'accounting_signature' => 'accounting-signature',
                    'accounting_spv_signature' => 'spv-signature',
                ],
            ],
        ];

        foreach ($approvalLevels as $approvalLevel) {
            $employee = Employee::factory()->create([
                'position' => $approvalLevel['position'],
                'division' => $approvalLevel['division'],
            ]);
            $approver = User::factory()->create(['employee_id' => $employee->id]);
            $request = PurchaseRequest::create([
                'title' => 'Permintaan '.$approvalLevel['position'],
                'requested_by' => $requester->id,
                'status' => $approvalLevel['status'],
                ...$approvalLevel['signatures'],
            ]);
            $this->actingAs($approver);

            Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
                ->assertOk()
                ->assertFormFieldEnabled($approvalLevel['field'], 'form')
                ->assertSee('Tempel Otomatis')
                ->assertSee('Manual');
        }
    }

    public function test_the_edit_page_keeps_the_rejection_icon_at_a_fixed_size(): void
    {
        $requester = User::factory()->create();
        $financeEmployee = Employee::factory()->create([
            'position' => 'senior',
            'division' => 'finance',
        ]);
        $financeUser = User::factory()->create([
            'employee_id' => $financeEmployee->id,
        ]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,cmVxdWVzdGVy',
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $request->reject($financeUser, 'Mohon lengkapi dokumen pendukung.');
        $this->actingAs($requester);

        Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertSee('width="20" height="20"', escape: false);
    }

    public function test_the_view_page_places_the_finance_signature_under_the_finance_label(): void
    {
        $requester = User::factory()->create();
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'user_signature' => 'data:image/png;base64,dXNlci1zaWduYXR1cmU=',
            'finance_signature' => 'data:image/png;base64,Zm9vLWZpbmFuY2Utc2lnbmF0dXJl',
            'status' => PurchaseRequestStatus::PendingAccounting,
        ]);
        $this->actingAs($requester);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertSeeInOrder([
                'Senior Finance',
                'src="data:image/png;base64,Zm9vLWZpbmFuY2Utc2lnbmF0dXJl" style="height: 80px; width: 160px;"',
                'Accounting (Staff)',
                'Menunggu Persetujuan',
            ], escape: false);
    }

    public function test_the_view_page_displays_each_signers_name_below_their_signature(): void
    {
        $signers = [
            ['name' => 'Pemohon Contoh', 'position' => 'staff', 'division' => 'sales'],
            ['name' => 'Finance Penandatangan', 'position' => 'senior', 'division' => 'finance'],
            ['name' => 'Accounting Penandatangan', 'position' => 'staff', 'division' => 'accounting'],
            ['name' => 'SPV Penandatangan', 'position' => 'supervisor', 'division' => 'accounting'],
            ['name' => 'Direktur Penandatangan', 'position' => 'director', 'division' => 'bod'],
        ];
        $users = [];

        foreach ($signers as $signer) {
            $employee = Employee::factory()->create([
                'name' => $signer['name'],
                'position' => $signer['position'],
                'division' => $signer['division'],
            ]);
            $users[] = User::factory()->create(['employee_id' => $employee->id]);
        }

        $signatures = [
            'user_signature' => 'data:image/png;base64,c2lnbmVyLXVzZXI=',
            'finance_signature' => 'data:image/png;base64,c2lnbmVyLWZpbmFuY2U=',
            'accounting_signature' => 'data:image/png;base64,c2lnbmVyLWFjY291bnRpbmc=',
            'accounting_spv_signature' => 'data:image/png;base64,c2lnbmVyLXNwdg==',
            'director_signature' => 'data:image/png;base64,c2lnbmVyLWRpcmVjdG9y',
        ];
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Selesai',
            'requested_by' => $users[0]->id,
            'status' => PurchaseRequestStatus::Approved,
            ...$signatures,
        ]);

        $stages = ['finance', 'accounting', 'accounting_spv', 'director'];
        $signatureValues = array_values($signatures);

        foreach ($stages as $index => $stage) {
            $request->approvalHistories()->create([
                'round' => 1,
                'stage' => $stage,
                'action' => 'approved',
                'actor_id' => $users[$index + 1]->id,
                'signature' => $signatureValues[$index + 1],
                'acted_at' => now(),
            ]);
        }

        $this->actingAs($users[0]);

        $html = Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertSeeInOrder([
                $signatures['user_signature'],
                'Pemohon Contoh',
                $signatures['finance_signature'],
                'Finance Penandatangan',
                $signatures['accounting_signature'],
                'Accounting Penandatangan',
                $signatures['accounting_spv_signature'],
                'SPV Penandatangan',
                $signatures['director_signature'],
                'Direktur Penandatangan',
            ])
            ->html();

        $document = new \DOMDocument;
        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);

        try {
            $htmlWasLoaded = $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrorSetting);
        }

        $this->assertTrue($htmlWasLoaded);
        $xpath = new \DOMXPath($document);
        $signatureGrid = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " purchase-request-signatures-display ")]/div[contains(concat(" ", normalize-space(@class), " "), " fi-sc ") and contains(@style, "repeat(5, minmax(0, 1fr))")]',
        )->item(0);

        $this->assertNotNull($signatureGrid);
        $this->assertSame(
            5,
            $xpath->query('./div[contains(concat(" ", normalize-space(@class), " "), " fi-grid-col ")]', $signatureGrid)->length,
        );

        $roleLabels = [
            'Submitted by',
            'Senior Finance',
            'Accounting (Staff)',
            'Accounting SPV',
            'Director',
        ];

        foreach (array_values($signatures) as $index => $signature) {
            $signatureColumns = $xpath->query(
                '//div[contains(concat(" ", normalize-space(@class), " "), " purchase-request-signature-column ")][.//img[@src="'.$signature.'"]]',
            );

            $this->assertSame(1, $signatureColumns->length);
            $signatureColumn = $signatureColumns->item(0);
            $this->assertStringContainsString($roleLabels[$index], $signatureColumn->textContent);
            $this->assertStringContainsString($signers[$index]['name'], $signatureColumn->textContent);

            $centeredEntries = $xpath->query(
                './/div[contains(concat(" ", normalize-space(@class), " "), " fi-in-entry ") and contains(concat(" ", normalize-space(@class), " "), " text-center ")]',
                $signatureColumn,
            );

            $this->assertSame(3, $centeredEntries->length);
        }
    }

    public function test_all_five_signature_columns_remain_visible_when_the_requester_is_accounting_staff(): void
    {
        $requesterEmployee = Employee::factory()->create([
            'name' => 'Pemohon Accounting',
            'position' => 'staff',
            'division' => 'accounting',
        ]);
        $requester = User::factory()->create(['employee_id' => $requesterEmployee->id]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Accounting',
            'requested_by' => $requester->id,
            'status' => PurchaseRequestStatus::Approved,
        ]);
        $this->actingAs($requester);

        $html = Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertSeeInOrder([
                'Submitted by',
                'Senior Finance',
                'Accounting (Staff)',
                'Accounting SPV',
                'Director',
            ])
            ->html();

        $document = new \DOMDocument;
        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);

        try {
            $htmlWasLoaded = $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrorSetting);
        }

        $this->assertTrue($htmlWasLoaded);
        $signatureColumns = (new \DOMXPath($document))->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " purchase-request-signature-column ")]',
        );

        $this->assertSame(5, $signatureColumns->length);
    }

    public function test_all_five_signature_pads_are_visible_and_aligned_when_editing_a_request_from_accounting_staff(): void
    {
        $requesterEmployee = Employee::factory()->create([
            'position' => 'staff',
            'division' => 'accounting',
        ]);
        $requester = User::factory()->create(['employee_id' => $requesterEmployee->id]);
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Accounting',
            'requested_by' => $requester->id,
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $this->actingAs($requester);

        $page = Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertFormFieldVisible('user_signature')
            ->assertFormFieldVisible('finance_signature')
            ->assertFormFieldVisible('accounting_signature')
            ->assertFormFieldVisible('accounting_spv_signature')
            ->assertFormFieldVisible('director_signature');

        $signatureGrid = $page->instance()->getSchema('form')->getComponent(
            fn ($component): bool => $component instanceof Grid
                && $component->getChildSchema()?->getComponent('accounting_signature', withHidden: true) !== null,
        );

        $this->assertInstanceOf(Grid::class, $signatureGrid);
        $this->assertSame(5, $signatureGrid->getColumns('default'));
        $this->assertSame(5, $signatureGrid->getColumns('md'));
        $this->assertSame(5, $signatureGrid->getChildSchema()->getColumns('default'));
        $this->assertSame(5, $signatureGrid->getChildSchema()->getColumns('md'));

        $document = new \DOMDocument;
        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);

        try {
            $htmlWasLoaded = $document->loadHTML($page->html());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrorSetting);
        }

        $this->assertTrue($htmlWasLoaded);
        $xpath = new \DOMXPath($document);
        $renderedGrid = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " fi-grid ") and contains(@style, "repeat(5, minmax(0, 1fr))")][.//canvas[@aria-label="Senior Finance"]]',
        )->item(0);

        $this->assertNotNull($renderedGrid);

        $gridChildren = $xpath->query('./*', $renderedGrid);
        $childDescriptions = [];

        foreach ($gridChildren as $gridChild) {
            $childDescriptions[] = $gridChild->nodeName.' '.$gridChild->getAttribute('class').' '.$gridChild->getAttribute('style');
        }

        $this->assertSame(5, $gridChildren->length, implode(' | ', $childDescriptions));

        $visibleSignatureComponents = $xpath->query(
            './div[contains(concat(" ", normalize-space(@class), " "), " fi-grid-col ") and not(contains(concat(" ", normalize-space(@class), " "), " fi-hidden "))]',
            $renderedGrid,
        );

        $this->assertSame(5, $visibleSignatureComponents->length);
    }

    public function test_the_approval_history_shows_comments_without_rendering_saved_signatures(): void
    {
        $requester = User::factory()->create();
        $request = PurchaseRequest::create([
            'title' => 'Permintaan Laptop Baru',
            'requested_by' => $requester->id,
            'status' => PurchaseRequestStatus::PendingFinance,
        ]);
        $historySignature = 'data:image/png;base64,aGlzdG9yeS1zaWduYXR1cmUtZXhjbHVkZWQ=';
        $request->approvalHistories()->create([
            'round' => 1,
            'stage' => 'finance',
            'action' => 'rejected',
            'actor_id' => $requester->id,
            'signature' => $historySignature,
            'comments' => 'Mohon lampirkan dokumen pendukung.',
            'acted_at' => now(),
        ]);
        $this->actingAs($requester);

        Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
            ->assertOk()
            ->assertSee('Riwayat Persetujuan & Revisi')
            ->assertSee('Mohon lampirkan dokumen pendukung.')
            ->assertDontSee('src="'.$historySignature.'"', escape: false)
            ->assertDontSee('Tanda tangan', escape: false);
    }
}
