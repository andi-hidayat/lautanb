<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_upload_a_signature_for_a_user(): void
    {
        Storage::fake('local');

        $administrator = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->callAction('uploadSignature', data: [
                'signature_path' => UploadedFile::fake()->image('signature.png'),
            ])
            ->assertHasNoActionErrors();

        $user->refresh();

        $this->assertNotNull($user->signature_path);
        Storage::disk('local')->assertExists($user->signature_path);
    }

    public function test_an_administrator_can_replace_an_existing_signature(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('signatures/old-signature.png', 'old signature');

        $administrator = User::factory()->admin()->create();
        $user = User::factory()->create([
            'signature_path' => 'signatures/old-signature.png',
        ]);

        $this->actingAs($administrator);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->mountAction('uploadSignature')
            // Simulate removing the current file before uploading a new one.
            ->set('mountedActions.0.data.signature_path', [])
            ->fillForm([
                'signature_path' => UploadedFile::fake()->image('new-signature.png'),
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $user->refresh();

        $this->assertNotSame('signatures/old-signature.png', $user->signature_path);
        Storage::disk('local')->assertExists($user->signature_path);
        Storage::disk('local')->assertMissing('signatures/old-signature.png');
    }

    public function test_an_administrator_can_remove_a_signature(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('signatures/signature.png', 'signature');

        $administrator = User::factory()->admin()->create();
        $user = User::factory()->create([
            'signature_path' => 'signatures/signature.png',
        ]);

        $this->actingAs($administrator);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->callAction('removeSignature')
            ->assertHasNoActionErrors();

        $user->refresh();

        $this->assertNull($user->signature_path);
        Storage::disk('local')->assertMissing('signatures/signature.png');
    }

    public function test_a_user_can_access_their_own_edit_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertOk()
            ->assertFormFieldExists('signature_path');
    }

    public function test_a_user_cannot_access_another_users_edit_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $otherUser->getRouteKey()])
            ->assertNotFound();
    }

    public function test_a_user_can_upload_a_signature_on_the_edit_page(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'signature_path' => UploadedFile::fake()->image('my-signature.png'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertNotNull($user->signature_path);
        Storage::disk('local')->assertExists($user->signature_path);
    }

    public function test_a_user_can_replace_their_signature_on_the_edit_page(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('signatures/old.png', 'old');

        $user = User::factory()->create([
            'signature_path' => 'signatures/old.png',
        ]);

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->set('data.signature_path', [])
            ->fillForm([
                'signature_path' => UploadedFile::fake()->image('new.png'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertNotSame('signatures/old.png', $user->signature_path);
        Storage::disk('local')->assertExists($user->signature_path);
        Storage::disk('local')->assertMissing('signatures/old.png');
    }

    public function test_a_user_can_remove_their_signature_on_the_edit_page(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('signatures/sig.png', 'content');

        $user = User::factory()->create([
            'signature_path' => 'signatures/sig.png',
        ]);

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'signature_path' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertNull($user->signature_path);
        Storage::disk('local')->assertMissing('signatures/sig.png');
    }

    public function test_a_user_cannot_change_their_employee_on_the_edit_page(): void
    {
        $user = User::factory()->create();
        $originalEmployeeId = $user->employee_id;
        $otherEmployee = Employee::factory()->create();

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldDisabled('employee_id')
            ->fillForm([
                'employee_id' => $otherEmployee->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame($originalEmployeeId, $user->employee_id);
    }
}
