<nav class="site-nav" aria-label="Main navigation" x-data="{ menuOpen: false }" :class="{ 'is-open': menuOpen }" @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.menuToggle.focus() }">
    <div class="nav-inner">
        <a class="nav-brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} home"><x-application-logo /></a>
        <div class="nav-links" id="main-navigation">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('characters.index', ['realm' => \App\Support\Wow::realmParamFromId(\App\Support\Wow::defaultRealmId())])" :active="request()->routeIs('characters.*')">Characters</x-nav-link>
            <x-nav-link :href="route('guilds.index')" :active="request()->routeIs('guilds.*')">Guilds</x-nav-link>
            <x-nav-link :href="route('auctions.index')" :active="request()->routeIs('auctions.*')">Auction House</x-nav-link>
        </div>
        <div class="nav-account">
            @auth
            <x-dropdown align="right" width="48" content-classes="account-menu">
                <x-slot name="trigger">
                    <button type="button" class="account-trigger" :aria-expanded="open.toString()" aria-controls="account-navigation">
                        <span>{{ Auth::user()->username }}</span><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" aria-hidden="true"><path d="m3 6 5 5 5-5" /></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <div id="account-navigation">
                        <div class="account-role">{{ Auth::user()->security_code }}</div>
                        <a href="{{ route('profile.edit') }}">My Account</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log Out</button></form>
                    </div>
                </x-slot>
            </x-dropdown>
            @else
                <a href="{{ route('login') }}">Log in</a>
            @endauth
        </div>
        <button type="button" class="nav-toggle" x-ref="menuToggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="main-navigation" aria-label="Toggle navigation"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button>
    </div>
</nav>
