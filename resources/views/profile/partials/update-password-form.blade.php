<section class="account-section" aria-labelledby="account-password-title">
    <h2 id="account-password-title">Password</h2>
    <form method="post" action="{{ route('password.update') }}" class="account-form" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('put')
        <div class="account-field">
            <x-input-label for="password-current" value="Current password" />
            <x-text-input id="password-current" name="current_password" type="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>
        <div class="account-field">
            <x-input-label for="password-new" value="New password (8–16 characters)" />
            <x-text-input id="password-new" name="password" type="password" required minlength="8" maxlength="16" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>
        <div class="account-field">
            <x-input-label for="password-confirm" value="Confirm new password" />
            <x-text-input id="password-confirm" name="password_confirmation" type="password" required minlength="8" maxlength="16" autocomplete="new-password" />
        </div>
        <div class="account-form-actions">
            <x-primary-button class="account-button" x-bind:disabled="submitting" x-text="submitting ? 'Saving…' : 'Change password'">Change password</x-primary-button>
            @if(session('status') === 'password-updated')<span class="account-saved" role="status">Password changed.</span>@endif
        </div>
    </form>
</section>
