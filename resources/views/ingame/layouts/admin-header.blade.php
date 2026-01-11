@php /** @var OGame\Services\PlayerService $currentPlayer */ @endphp
<div id="adminHeader">
    <div class="admin-title">
        Server Admin Panel
    </div>
    <div class="admin-info">
        <a href="{{ route('overview.index') }}" class="back-to-game">
            &larr; Back to Game
        </a>
        <span>
            @lang('Player'): <strong>{!! $currentPlayer->getUsername() !!}</strong>
        </span>
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
            @lang('Log out')
        </a>
        <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
        <span class="admin-clock">{{ \Carbon\Carbon::now()->format('d.m.Y H:i:s') }}</span>
    </div>
</div>
