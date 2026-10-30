<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable([
    'user_id',
    'assigned_to',
    'title',
    'priority',
    'status',
    'description',
    'attachments',
    'resolved_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'attachments' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The user who submitted the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The staff member handling the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * All replies on the ticket.
     *
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Build a temporary link to open an attachment.
     */
    public function attachmentUrl(string $path): string
    {
        $disk = config('filament.default_filesystem_disk', 'local');

        try {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(30));
        } catch (Throwable) {
            return Storage::disk($disk)->url($path);
        }
    }
}
