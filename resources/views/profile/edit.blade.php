<x-app-layout>
    <x-slot name="header"><x-page-heading title="My Account" /></x-slot>
    <div class="account-page">
        <x-account-nav />
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.sessions')
        @if($ban || $user->mutetime > now()->timestamp || $user->mutetime < 0)
            @include('profile.partials.restrictions')
        @endif
        @include('profile.partials.deactivate-account')
    </div>
</x-app-layout>
