<x-app-layout>
    <x-slot name="header"><x-page-heading title="My Account" /></x-slot>
    <div class="account-page">
        <x-account-nav />
        <h2 class="account-history-title">Operation history</h2>
        <ol class="account-history">
            @forelse($operations as $operation)
                <li>
                    <div class="operation-detail">
                        <h3>{{ $operation->action->label() }}</h3>
                        @if($operation->character_name)
                            <p>{{ $operation->character_name }} · {{ \App\Support\Wow::realmName($operation->realm_id) }}</p>
                        @endif
                        @if($operation->context['destination'] ?? null)<p>{{ $operation->context['destination'] }}</p>@endif
                        @if($operation->context['message'] ?? null)<p class="operation-message">{{ $operation->context['message'] }}</p>@endif
                    </div>
                    <div class="operation-result">
                        <span class="operation-status is-{{ $operation->status }}">{{ $operation->result() }}</span>
                        <time datetime="{{ $operation->created_at->toIso8601String() }}">{{ $operation->created_at->format('j M Y, H:i T') }}</time>
                    </div>
                </li>
            @empty
                <li class="account-empty">No operations yet.</li>
            @endforelse
        </ol>
        <div class="account-pagination">{{ $operations->links() }}</div>
    </div>
</x-app-layout>
