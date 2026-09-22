<section class="account-section" aria-labelledby="deactivate-account-heading">
    <h2 id="deactivate-account-heading">Deactivate account</h2>
    <div class="account-section-content">
        <p class="account-muted">Your characters and progress will be kept. Only an administrator can restore access.</p>
        <div x-data>
            <button type="button" class="account-link account-danger" x-on:click="$dispatch('open-modal', 'deactivate-account')">Deactivate account</button>
        </div>
        <x-modal name="deactivate-account" :show="$errors->deactivateAccount->isNotEmpty()">
            <form method="post" action="{{ route('profile.destroy') }}" class="account-dialog-content account-form">
                @csrf
                @method('delete')
                <h2 id="deactivate-account-title">Deactivate account?</h2>
                <p>Website and game access will be disabled. Your characters and progress will be kept.</p>
                <div class="account-field">
                    <x-input-label for="deactivate-password" value="Current password" />
                    <x-text-input id="deactivate-password" type="password" name="current_password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->deactivateAccount->all()" />
                </div>
                <label class="account-check"><input type="checkbox" name="confirmed" value="1" required> Only an administrator can restore my access.</label>
                <div class="account-form-actions">
                    <button type="button" class="account-link" x-on:click="$dispatch('close-modal', 'deactivate-account')">Cancel</button>
                    <x-danger-button class="account-button is-danger">Deactivate account</x-danger-button>
                </div>
            </form>
        </x-modal>
    </div>
</section>
