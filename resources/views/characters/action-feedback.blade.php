@if($result = session('character-operation'))
    <p class="account-notice {{ $result['status'] !== 'completed' ? 'is-error' : '' }}" role="status">
        <strong>{{ $result['name'] }} · {{ $result['action'] }}</strong> {{ $result['message'] }}
        <a href="{{ route('profile.history') }}">View history</a>
    </p>
@endif
@if($errors->characterAction->any())
    <div class="account-notice is-error" role="alert">{{ $errors->characterAction->first() }}</div>
@endif
