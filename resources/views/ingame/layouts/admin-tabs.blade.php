@php
    $activeTab = $activeAdminTab ?? 'developershortcuts';
    $openTicketCount = $openTicketCount ?? 0;
@endphp
<div id="adminTabs">
    <ul>
        <li>
            <a href="{{ route('admin.developershortcuts.index') }}"
               class="{{ $activeTab === 'developershortcuts' ? 'active' : '' }}">
                @lang('Developer Shortcuts')
            </a>
        </li>
        <li>
            <a href="{{ route('admin.serversettings.index') }}"
               class="{{ $activeTab === 'serversettings' ? 'active' : '' }}">
                @lang('Server Settings')
            </a>
        </li>
        <li>
            <a href="{{ route('admin.tickets.index') }}"
               class="{{ $activeTab === 'tickets' ? 'active' : '' }}">
                @lang('Tickets')
                @if($openTicketCount > 0)
                    <span class="badge">{{ $openTicketCount }}</span>
                @endif
            </a>
        </li>
        <li>
            <a href="{{ route('admin.eventsettings.index') }}"
               class="{{ $activeTab === 'eventsettings' ? 'active' : '' }}">
                @lang('Event Settings')
            </a>
        </li>
    </ul>
</div>
