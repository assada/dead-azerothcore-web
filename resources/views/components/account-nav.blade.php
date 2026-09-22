<nav class="account-tabs" aria-label="My Account">
    <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.edit')) aria-current="page" @endif>Settings</a>
    <a href="{{ route('profile.history') }}" @if(request()->routeIs('profile.history')) aria-current="page" @endif>Operation history</a>
</nav>
