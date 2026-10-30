<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\RelationManagers\MessagesRelationManager;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_reply_to_their_open_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->set('replyBody', 'Is there any progress?')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSee('Is there any progress?');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'body' => 'Is there any progress?',
        ]);
    }

    public function test_a_reply_body_is_required(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->call('sendReply')
            ->assertHasErrors(['replyBody' => 'required']);

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    public function test_a_user_cannot_reply_to_a_closed_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->closed()->create();

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertDontSee('Write a reply')
            ->set('replyBody', 'Trying to reply to a closed ticket')
            ->call('sendReply');

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    public function test_an_administrator_can_reply_to_a_closed_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->closed()->create();

        $this->actingAs($admin);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertSee('Write a reply')
            ->set('replyBody', 'We are reopening this ticket.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'We are reopening this ticket.',
        ]);
    }

    public function test_the_owner_can_see_the_conversation(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();
        $message = TicketMessage::factory()
            ->for($ticket)
            ->for($owner, 'user')
            ->create();

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertOk()
            ->assertSee($message->body);
    }

    public function test_the_ticket_owner_is_notified_when_an_administrator_replies(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->actingAs($admin);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->set('replyBody', 'We are looking into it.')
            ->call('sendReply');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_internal_notes_are_hidden_from_regular_users(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $publicMessage = TicketMessage::factory()
            ->for($ticket)
            ->for($owner, 'user')
            ->create(['body' => 'Public message for the ticket owner']);

        $internalMessage = TicketMessage::factory()
            ->for($ticket)
            ->for($admin, 'user')
            ->internal()
            ->create(['body' => 'Secret internal note']);

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertSee($publicMessage->body)
            ->assertDontSee($internalMessage->body);
    }

    public function test_administrators_can_see_internal_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        $internalMessage = TicketMessage::factory()
            ->for($ticket)
            ->for($admin, 'user')
            ->internal()
            ->create(['body' => 'Secret internal note']);

        $this->actingAs($admin);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertSee($internalMessage->body);
    }

    public function test_the_ticket_status_is_used_to_block_replies(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create([
            'status' => TicketStatus::Resolved,
        ]);

        $this->actingAs($owner);

        Livewire::test(MessagesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertSee('Write a reply')
            ->set('replyBody', 'Replying to a resolved ticket')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'Replying to a resolved ticket',
        ]);
    }
}
