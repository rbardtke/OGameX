@extends('ingame.layouts.main')

@section('content')

    <div id="content">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('My Tickets')</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <div style="margin-bottom: 20px; text-align: right;">
                        <a href="{{ route('tickets.create') }}" class="btn_blue" style="padding: 8px 16px; text-decoration: none;">
                            + @lang('New Ticket')
                        </a>
                    </div>

                    @if($tickets->isEmpty())
                        <p class="box_highlight textCenter no_buddies">
                            @lang('You have no tickets yet.')
                        </p>
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <p>@lang('Need help? Create a ticket to contact support.')</p>
                        </div>
                    @else
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #2a3540; border-bottom: 1px solid #3a4a5a;">
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Subject')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Status')</th>
                                    <th style="padding: 10px; text-align: left; color: #6f9fc8;">@lang('Last Update')</th>
                                    <th style="padding: 10px; text-align: center; color: #6f9fc8;">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr style="border-bottom: 1px solid #3a4a5a; {{ $ticket->status === 'open' ? 'background: rgba(244, 132, 6, 0.1);' : '' }}">
                                        <td style="padding: 10px;">
                                            <a href="{{ route('tickets.show', $ticket->id) }}" style="color: #6f9fc8; text-decoration: none;">
                                                {{ Str::limit($ticket->subject, 50) }}
                                            </a>
                                        </td>
                                        <td style="padding: 10px;">
                                            @if($ticket->status === 'open')
                                                <span style="color: #5cb85c;">@lang('Open')</span>
                                            @else
                                                <span style="color: #999;">@lang('Closed')</span>
                                            @endif
                                        </td>
                                        <td style="padding: 10px; color: #999; font-size: 11px;">{{ $ticket->updated_at->format('d.m.Y H:i') }}</td>
                                        <td style="padding: 10px; text-align: center;">
                                            <a href="{{ route('tickets.show', $ticket->id) }}" class="btn_blue" style="padding: 5px 10px; text-decoration: none; font-size: 11px;">
                                                @lang('View')
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
