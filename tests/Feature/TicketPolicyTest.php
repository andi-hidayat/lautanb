<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_only_view_their_own_tickets(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->assertTrue($owner->can('view', $ticket));
        $this->assertFalse($otherUser->can('view', $ticket));
    }

    public function test_an_administrator_can_view_every_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($admin->can('view', $ticket));
        $this->assertTrue($admin->can('update', $ticket));
    }

    public function test_it_staff_can_view_and_update_every_ticket(): void
    {
        $employee = Employee::factory()->create(['division' => 'it']);
        $itStaff = User::factory()->create(['employee_id' => $employee->id]);
        $ticket = Ticket::factory()->create();

        $this->assertTrue($itStaff->can('view', $ticket));
        $this->assertTrue($itStaff->can('update', $ticket));
    }

    public function test_a_user_can_update_their_own_open_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->create();

        $this->assertTrue($owner->can('update', $ticket));
    }

    public function test_a_user_cannot_update_their_own_closed_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'requester')->closed()->create();

        $this->assertFalse($owner->can('update', $ticket));
    }

    public function test_only_a_superadmin_can_delete_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertFalse(User::factory()->create()->can('delete', $ticket));
        $this->assertFalse(User::factory()->admin()->create()->can('delete', $ticket));
        $this->assertTrue(User::factory()->superadmin()->create()->can('delete', $ticket));
    }
}
