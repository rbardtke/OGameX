@extends('ingame.layouts.main')

@section('content')

    <div id="content">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('Ticket') #{{ $ticket->id }}: {{ Str::limit($ticket->subject, 40) }}</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    @if (session('status'))
                        <div style="background: #1a472a; color: #5cb85c; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div style="background: #4a1a1a; color: #d9534f; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Back Button and Status -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <a href="{{ route('tickets.index') }}" style="color: #6f9fc8; text-decoration: none;">
                            &larr; @lang('Back to Tickets')
                        </a>

                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span style="color: #999;">@lang('Status'):</span>
                            @if($ticket->status === 'open')
                                <span style="color: #5cb85c; font-weight: bold;">@lang('Open')</span>
                            @else
                                <span style="color: #999; font-weight: bold;">@lang('Closed')</span>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Info -->
                    <div class="group bborder" style="display: block; margin-bottom: 20px; padding: 10px;">
                        <div style="display: flex; gap: 30px; color: #999; font-size: 11px;">
                            <div>
                                <strong style="color: #6f9fc8;">@lang('Created'):</strong>
                                {{ $ticket->created_at->format('d.m.Y H:i') }}
                            </div>
                            <div>
                                <strong style="color: #6f9fc8;">@lang('Last Update'):</strong>
                                {{ $ticket->updated_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <p class="box_highlight textCenter no_buddies">@lang('Conversation')</p>

                    <div style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
                        @foreach($ticket->messages as $message)
                            <div class="group bborder" style="display: block; margin-bottom: 10px; {{ $message->is_admin_reply ? 'background: rgba(107, 159, 200, 0.1); border-left: 3px solid #6f9fc8;' : 'background: rgba(244, 132, 6, 0.1); border-left: 3px solid #f48406;' }}">
                                <div style="padding: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="font-weight: bold; {{ $message->is_admin_reply ? 'color: #6f9fc8;' : 'color: #f48406;' }}">
                                            @if($message->is_admin_reply)
                                                @lang('Support Team')
                                            @else
                                                @lang('You')
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

                        <form action="{{ route('tickets.reply', $ticket->id) }}" method="POST">
                            @csrf
                            <div class="group bborder" style="display: block;">
                                <div class="fieldwrapper">
                                    <textarea name="body" rows="4"
                                              style="width: 100%; padding: 10px; background: #0d1014; border: 1px solid #3a4a5a; color: #fff; border-radius: 3px; resize: vertical;"
                                              placeholder="@lang('Write your reply...')"
                                              required minlength="1" maxlength="5000"></textarea>
                                    @error('body')
                                        <div style="color: #d9534f; font-size: 11px; margin-top: 5px;">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="fieldwrapper" style="text-align: right; margin-top: 10px;">
                                    <button type="submit" class="btn_blue" style="padding: 8px 20px;">@lang('Send Reply')</button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="box_highlight textCenter no_buddies" style="color: #999;">
                            @lang('This ticket is closed.')
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
