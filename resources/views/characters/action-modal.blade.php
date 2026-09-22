<x-modal name="character-action">
    <form method="post" :action="character?.url" class="account-form character-action-form" @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf
        <input type="hidden" name="action" :value="action">
        <input type="hidden" name="request_id" :value="requestId">
        <input type="hidden" name="confirmed" value="1">
        <h2 id="character-action-title" x-text="title"></h2>
        <p class="action-character" x-text="character?.name"></p>
        <div x-show="action === 'unstuck'">
            <p>Return to your home in <strong x-text="character?.home"></strong>?</p>
            <p class="action-limit">{{ \App\Enums\AccountAction::Unstuck->cooldownDescription() }}</p>
        </div>
        <div x-show="action === 'rename'">
            <p>Choose a new name on the character screen when you next log in.</p>
            <p class="action-limit">{{ \App\Enums\AccountAction::Rename->cooldownDescription() }}</p>
        </div>
        <div x-show="action === 'customize'">
            <p>Change your appearance on the character screen when you next log in.</p>
            <p class="action-limit">{{ \App\Enums\AccountAction::Customize->cooldownDescription() }}</p>
        </div>
        <div class="account-form-actions">
            <x-secondary-button class="account-button is-secondary" x-on:click="$dispatch('close')" x-bind:disabled="submitting" autofocus>Cancel</x-secondary-button>
            <x-primary-button class="account-button" x-bind:disabled="submitting" x-text="submitting ? 'Processing…' : title">Confirm</x-primary-button>
        </div>
    </form>
</x-modal>
