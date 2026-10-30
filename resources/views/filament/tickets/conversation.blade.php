<div class="ticket-chat">
    @foreach ($records ?? [] as $message)
        <div
            wire:key="message-{{ $message->id }}"
            @class(['ticket-chat-message', 'is-mine' => $message->user_id === auth()->id()])
        >
            <div class="ticket-chat-bubble">
                <div class="ticket-chat-meta">
                    <span class="ticket-chat-sender">
                        {{ $message->user?->name ?? 'System' }}
                    </span>

                    <span>
                        {{ $message->created_at->format('d M Y, H:i') }}
                    </span>

                    @if ($message->is_internal)
                        <x-filament::badge color="warning" size="xs">
                            Internal note
                        </x-filament::badge>
                    @endif
                </div>

                <div class="ticket-chat-body">{{ $message->body }}</div>
            </div>
        </div>
    @endforeach
</div>
