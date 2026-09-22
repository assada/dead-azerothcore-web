<x-app-layout>
    <x-slot name="header"><x-page-heading title="Confirm email" /></x-slot>
    <div class="account-page">
        <section class="account-section">
            <h2>New login email</h2>
            <form method="post" action="{{ request()->fullUrl() }}" class="account-form">
                @csrf
                <p>{{ strtolower($email) }}</p>
                <div class="account-field">
                    <x-input-label for="current-password" value="Current password" />
                    <x-text-input id="current-password" type="password" name="current_password" required autocomplete="current-password" autofocus />
                    <x-input-error :messages="$errors->all()" />
                </div>
                <div class="account-form-actions">
                    <x-primary-button>Confirm email</x-primary-button>
                    <a href="{{ route('profile.edit') }}" class="account-link">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
