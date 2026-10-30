<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can restore the employee.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can permanently delete the employee.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user has a management role.
     */
    private function isManager(User $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }
}
