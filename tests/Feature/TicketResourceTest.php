<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TicketResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_submit_a_ticket(): void
    {
        $user = User::factory()->create();
        User::factory()->admin()->create();

        $this->actingAs($user);

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'title' => 'Printer cannot print',
                'priority' => 'high',
                'description' => 'The printer on the second floor is jammed.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::query()->sole();

        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame('Printer cannot print', $ticket->title);
    }

    public function test_a_user_only_sees_their_own_tickets_in_the_list(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownTicket = Ticket::factory()->for($owner, 'requester')->create();
        $otherTicket = Ticket::factory()->for($otherUser, 'requester')->create();

        $this->actingAs($owner);

        Livewire::test(ListTickets::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$ownTicket])
            ->assertCanNotSeeTableRecords([$otherTicket]);
    }

    public function test_an_administrator_sees_every_ticket_in_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $tickets = Ticket::factory()->count(2)->create();

        $this->actingAs($admin);

        Livewire::test(ListTickets::class)
            ->assertOk()
            ->assertCanSeeTableRecords($tickets);
    }

    public function test_administrators_are_notified_when_a_ticket_is_created(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'title' => 'Monitor is off',
                'priority' => 'medium',
                'description' => 'The monitor has not turned on since this morning.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_the_view_page_shows_the_ticket_details(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->actingAs($owner);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertOk()
            ->assertSee($ticket->title)
            ->assertSee($ticket->description)
            ->assertSee('Conversation')
            ->assertSee('Write a reply');
    }

    public function test_a_user_editing_their_ticket_cannot_change_its_status(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()
            ->for($owner, 'requester')
            ->create(['status' => TicketStatus::InProgress]);

        $this->actingAs($owner);

        Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['title' => 'Updated title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $ticket->refresh();

        $this->assertSame('Updated title', $ticket->title);
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
    }

    public function test_an_administrator_can_change_the_ticket_status(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['status' => TicketStatus::Resolved->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_ticket_attachments_are_shown_on_the_view_page(): void
    {
        Storage::fake(config('filament.default_filesystem_disk', 'local'));
        Storage::disk(config('filament.default_filesystem_disk', 'local'))
            ->put('tickets/manual.pdf', 'dummy');

        $owner = User::factory()->create();
        $ticket = Ticket::factory()
            ->for($owner, 'requester')
            ->create(['attachments' => ['tickets/manual.pdf']]);

        $this->actingAs($owner);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertOk()
            ->assertSee('manual.pdf');
    }

    public function test_a_user_cannot_open_a_ticket_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->actingAs($otherUser);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertNotFound();
    }
}
