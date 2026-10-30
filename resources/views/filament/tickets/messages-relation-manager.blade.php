<div class="fi-resource-relation-manager">
    {{ $this->content }}

    @unless ($this->isReadOnly())
        <form wire:submit="sendReply" class="ticket-chat-composer">
            <div class="ticket-chat-composer-field">
                <textarea
                    wire:model="replyBody"
                    class="ticket-chat-composer-input"
                    rows="2"
                    placeholder="Write a reply..."
                ></textarea>

                @error('replyBody')
                    <p class="ticket-chat-composer-error">{{ $message }}</p>
                @enderror
            </div>

            <x-filament::button type="submit" wire:loading.attr="disabled">
                Send
            </x-filament::button>
        </form>
    @endunless

    <x-filament-panels::unsaved-action-changes-alert />
</div>
