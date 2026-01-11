@extends('ingame.layouts.admin')

@section('content')

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('Ticket') #{{ $ticket->id }}: {{ Str::limit($ticket->subject, 50) }}</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <!-- Back Button and Status -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <a href="{{ route('admin.tickets.index') }}" class="btn_blue" style="padding: 8px 16px; text-decoration: none;">
                            &larr; @lang('Back to Tickets')
                        </a>

                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="color: #999;">@lang('Status'):</span>
                            @if($ticket->status === 'open')
                                <span style="color: #5cb85c; font-weight: bold;">@lang('Open')</span>
                                <form action="{{ route('admin.tickets.status', $ticket->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit" class="btn_blue" style="padding: 5px 10px; font-size: 11px;">@lang('Close Ticket')</button>
                                </form>
                            @else
                                <span style="color: #999; font-weight: bold;">@lang('Closed')</span>
                                <form action="{{ route('admin.tickets.status', $ticket->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" class="btn_blue" style="padding: 5px 10px; font-size: 11px;">@lang('Reopen Ticket')</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Info -->
                    <div class="group bborder" style="display: block; margin-bottom: 20px;">
                        <div class="fieldwrapper" style="display: flex; gap: 30px;">
                            <div>
                                <strong style="color: #6f9fc8;">@lang('User'):</strong>
                                <span style="color: #fff;">{{ $ticket->user->username ?? 'Unknown' }}</span>
                            </div>
                            <div>
                                <strong style="color: #6f9fc8;">@lang('Created'):</strong>
                                <span style="color: #999;">{{ $ticket->created_at->format('d.m.Y H:i:s') }}</span>
                            </div>
                            @if($ticket->closed_at)
                                <div>
                                    <strong style="color: #6f9fc8;">@lang('Closed'):</strong>
                                    <span style="color: #999;">{{ $ticket->closed_at->format('d.m.Y H:i:s') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Messages -->
                    <p class="box_highlight textCenter no_buddies">@lang('Conversation')</p>

                    <div style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                        @foreach($ticket->messages as $message)
                            <div class="group bborder" style="display: block; margin-bottom: 10px; {{ $message->is_admin_reply ? 'background: rgba(107, 159, 200, 0.1); border-left: 3px solid #6f9fc8;' : 'background: rgba(244, 132, 6, 0.1); border-left: 3px solid #f48406;' }}">
                                <div style="padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="font-weight: bold; {{ $message->is_admin_reply ? 'color: #6f9fc8;' : 'color: #f48406;' }}">
                                            @if($message->is_admin_reply)
                                                @lang('Admin'): {{ $message->user->username ?? 'Unknown' }}
                                            @else
                                                @lang('User'): {{ $message->user->username ?? 'Unknown' }}
                                            @endif
                                        </span>
                                        <span style="color: #999; font-size: 11px;">{{ $message->created_at->format('d.m.Y H:i:s') }}</span>
                                    </div>
                                    <div style="color: #fff; white-space: pre-wrap; word-wrap: break-word;">{{ $message->body }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Reply Form (only if ticket is open) -->
                    @if($ticket->status === 'open')
                        <p class="box_highlight textCenter no_buddies">@lang('Reply')</p>

                        <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="group bborder" style="display: block;">
                                <div class="fieldwrapper">
                                    <textarea name="body" rows="5" style="width: 100%; padding: 10px; background: #1a1f25; border: 1px solid #3a4a5a; color: #fff; border-radius: 3px; resize: vertical;" placeholder="@lang('Write your reply...')" required></textarea>
                                </div>
                                <div class="fieldwrapper" style="text-align: right; margin-top: 10px;">
                                    <button type="submit" class="btn_blue" style="padding: 10px 20px;">@lang('Send Reply')</button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="box_highlight textCenter no_buddies" style="color: #999;">
                            @lang('This ticket is closed. Reopen it to reply.')
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
