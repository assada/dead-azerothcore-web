<section class="account-section" aria-labelledby="account-sessions-title">
    <h2 id="account-sessions-title">Web sessions</h2>
    <div class="account-section-content">
        <ul class="account-sessions">
            @forelse($sessions as $webSession)
                <li>
                    <div class="session-heading">
                        <strong>{{ $webSession->browser }}</strong>
                        @if($webSession->id === session()->getId())<span class="session-current">This session</span>@endif
                    </div>
                    <div class="session-details">
                        <span>{{ $webSession->platform }}@if($webSession->ip_address) · {{ $webSession->ip_address }}@endif</span>
                        <time datetime="{{ \Carbon\CarbonImmutable::createFromTimestamp($webSession->last_activity)->toIso8601String() }}">{{ \Carbon\CarbonImmutable::createFromTimestamp($webSession->last_activity)->diffForHumans() }}</time>
                    </div>
                </li>
            @empty
                <li>This session is active.</li>
            @endforelse
        </ul>
        @if($sessions->contains(fn ($webSession) => $webSession->id !== session()->getId()))
            <form method="post" action="{{ route('profile.sessions.destroy') }}" class="account-form session-form" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('delete')
                <div class="account-field">
                    <x-input-label for="sessions-current-password" value="Current password" />
                    <x-text-input id="sessions-current-password" name="current_password" type="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->endSessions->get('current_password')" />
                </div>
                <x-primary-button class="account-button" x-bind:disabled="submitting" x-text="submitting ? 'Ending sessions…' : 'End other sessions'">End other sessions</x-primary-button>
            </form>
        @endif
        @if(session('status') === 'sessions-ended')<p class="account-saved" role="status">Other web sessions ended.</p>@endif
    </div>
</section>
