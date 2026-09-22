<section class="account-section" aria-labelledby="account-restrictions-title">
    <h2 id="account-restrictions-title">Restrictions</h2>
    <div class="account-section-content account-restrictions">
        @if($ban)
            <div>
                <h3>Account banned</h3>
                <p>{{ $ban->banreason }}</p>
                <p class="restriction-date">{{ $ban->bandate === $ban->unbandate ? 'Permanent' : 'Until '.\Carbon\CarbonImmutable::createFromTimestamp($ban->unbandate)->format('j M Y, H:i T') }}</p>
            </div>
        @endif
        @if($user->mutetime > now()->timestamp || $user->mutetime < 0)
            <div>
                <h3>Chat muted</h3>
                <p>{{ $user->mutereason }}</p>
                <p class="restriction-date">
                    @if($user->mutetime < 0)
                        {{ \Carbon\CarbonInterval::seconds(abs($user->mutetime))->cascade()->forHumans() }}, starting at your next game login.
                    @else
                        Until {{ \Carbon\CarbonImmutable::createFromTimestamp($user->mutetime)->format('j M Y, H:i T') }}
                    @endif
                </p>
            </div>
        @endif
    </div>
</section>
