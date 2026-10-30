<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['employee_id', 'email', 'password', 'signature_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::deleted(function (User $user): void {
            if (filled($user->signature_path)) {
                Storage::disk('local')->delete($user->signature_path);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the employee that owns the user account.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the tickets submitted by the user.
     *
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    /**
     * Get the tickets assigned to the user.
     *
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Get the messages written by the user.
     *
     * @return HasMany<TicketMessage, $this>
     */
    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Determine whether the user is an administrator.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    /**
     * Determine whether the user belongs to the IT division.
     */
    public function isItStaff(): bool
    {
        return $this->division === 'it';
    }

    /**
     * Determine whether the user can handle tickets (IT division, admin, or superadmin).
     */
    public function canHandleTickets(): bool
    {
        return $this->isStaff() || $this->isItStaff();
    }

    /**
     * Determine whether the user is a superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Scope the query to administrator accounts.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereHas(
            'employee',
            fn (Builder $query): Builder => $query->whereIn('role', ['admin', 'superadmin']),
        );
    }

    /**
     * Get the employee name.
     *
     * @return Attribute<string|null, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->employee?->name);
    }

    /**
     * Get the employee role.
     *
     * @return Attribute<string|null, never>
     */
    protected function role(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->employee?->role);
    }

    /**
     * Get the employee signature position.
     *
     * @return Attribute<string|null, never>
     */
    protected function position(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->employee?->position);
    }

    /**
     * Get the employee division.
     *
     * @return Attribute<string|null, never>
     */
    protected function division(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->employee?->division);
    }

    /**
     * Get the signature as a base64 data URL.
     */
    public function getSignatureDataUrl(): ?string
    {
        if (blank($this->signature_path)) {
            return null;
        }

        if (! Storage::disk('local')->exists($this->signature_path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($this->signature_path) ?? 'image/png';
        $contents = Storage::disk('local')->get($this->signature_path);

        return "data:{$mime};base64,".base64_encode($contents);
    }
}
