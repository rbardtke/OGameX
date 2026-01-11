@extends('ingame.layouts.admin')

@section('content')

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('Support Tickets')</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <!-- Status Filter Tabs -->
                    <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                        <a href="{{ route('admin.tickets.index', ['status' => 'open']) }}"
                           class="btn_blue {{ $currentStatus === 'open' ? '' : 'btn_inactive' }}"
                           style="padding: 8px 16px; text-decoration: none;">
                            @lang('Open')
                            @if($openTicketCount > 0)
                                <span style="background: #c00; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 5px;">{{ $openTicketCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('admin.tickets.index', ['status' => 'closed']) }}"
                           class="btn_blue {{ $currentStatus === 'closed' ? '' : 'btn_inactive' }}"
                           style="padding: 8px 16px; text-decoration: none;">
                            @lang('Closed')
                        </a>
                        <a href="{{ route('admin.tickets.index', ['status' => 'all']) }}"
                           class="btn_blue {{ $currentStatus === 'all' ? '' : 'btn_inactive' }}"
                           style="padding: 8px 16px; text-decoration: none;">
                            @lang('All')
                        </a>
                    </div>

                    @if($tickets->isEmpty())
                        <p class="box_highlight textCenter no_buddies">
                            @if($currentStatus === 'open')
                                @lang('No open tickets.')
                            @elseif($currentStatus === 'closed')
                                @lang('No closed tickets.')
                            @else
                                @lang('No tickets found.')
                            @endif
                        </p>
                    @else
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #2a3540; border-bottom: 1px solid #3a4a5a;">
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('ID')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Subject')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('User')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Status')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Created')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Last Update')</th>
                                    <th style="padding: 10px; text-align: center; color: #6f9fc8;">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr style="border-bottom: 1px solid #3a4a5a; {{ $ticket->status === 'open' ? 'background: rgba(244, 132, 6, 0.1);' : '' }}">
                                        <td style="padding: 10px; color: #fff;">#{{ $ticket->id }}</td>
                                        <td style="padding: 10px;">
                                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" style="color: #6f9fc8; text-decoration: none;">
                                                {{ Str::limit($ticket->subject, 50) }}
                                            </a>
                                        </td>
                                        <td style="padding: 10px; color: #fff;">{{ $ticket->user->username ?? 'Unknown' }}</td>
                                        <td style="padding: 10px;">
                                            @if($ticket->status === 'open')
                                                <span style="color: #5cb85c;">@lang('Open')</span>
                                            @else
                                                <span style="color: #999;">@lang('Closed')</span>
                                            @endif
                                        </td>
                                        <td style="padding: 10px; color: #999; font-size: 11px;">{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                                        <td style="padding: 10px; color: #999; font-size: 11px;">{{ $ticket->updated_at->format('d.m.Y H:i') }}</td>
                                        <td style="padding: 10px; text-align: center;">
                                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn_blue" style="padding: 5px 10px; text-decoration: none; font-size: 11px;">
                                                @lang('View')
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        @if($tickets->hasPages())
                            <div style="margin-top: 20px; text-align: center;">
                                {{ $tickets->appends(['status' => $currentStatus])->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn_inactive {
            opacity: 0.6;
        }
        .btn_inactive:hover {
            opacity: 1;
        }
    </style>

@endsection
