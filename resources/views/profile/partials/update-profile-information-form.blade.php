<section class="account-section" aria-labelledby="account-information-title">
    <h2 id="account-information-title">Account</h2>
    <div class="account-section-content">
        <dl class="account-facts">
            <div><dt>Username</dt><dd>{{ strtolower($user->username) }}</dd></div>
            <div><dt>Joined</dt><dd>{{ $user->joindate?->format('j M Y') ?? 'Unknown' }}</dd></div>
        </dl>
        <form method="post" action="{{ route('profile.update') }}" class="account-form" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('patch')
            <div class="account-field">
                <x-input-label for="account-email" value="Email" />
                <x-text-input id="account-email" name="email" type="email" :value="old('email', strtolower($user->email))" required maxlength="255" autocomplete="email" />
                <x-input-error :messages="$errors->updateProfile->get('email')" />
            </div>
            <div class="account-field">
                <x-input-label for="email-current-password" value="Current password" />
                <x-text-input id="email-current-password" name="current_password" type="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->updateProfile->get('current_password')" />
            </div>
            <div class="account-form-actions">
                <x-primary-button class="account-button" x-bind:disabled="submitting" x-text="submitting ? 'Sending…' : 'Change email'">Change email</x-primary-button>
                @if(session('status') === 'profile-updated')<span class="account-saved" role="status">Email saved.</span>@endif
            </div>
            @if(session('status') === 'email-confirmation-sent')<p class="account-saved" role="status">Check your new email to confirm the change.</p>@endif
            @if($user->profile?->pending_email)<p class="account-muted">Awaiting confirmation: {{ strtolower($user->profile->pending_email) }}</p>@endif
        </form>
        @if($user->email && ! $user->hasVerifiedEmail())
            <form method="post" action="{{ route('verification.send') }}" class="account-form">
                @csrf
                <p class="account-muted">Your current email is not verified.</p>
                <button type="submit" class="account-link">Send verification email</button>
                @if(session('status') === 'verification-link-sent')<p class="account-saved" role="status">Verification email sent.</p>@endif
            </form>
        @endif
    </div>
</section>
