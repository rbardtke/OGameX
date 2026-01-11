@if($tickets->isEmpty())
    <div class="no_msg" style="padding: 20px; text-align: center; color: #999;">
        <p>@lang('You have no tickets yet.')</p>
        <p style="margin-top: 10px;">
            @lang('Click on "New Ticket" tab to contact support.')
        </p>
    </div>
@else
    <ul class="msg_list" id="ticketsList">
        @foreach($tickets as $ticket)
            <li class="msg {{ $ticket->status === 'open' ? 'msg_new' : '' }}" data-ticket-id="{{ $ticket->id }}">
                <div class="msg_head" style="cursor: pointer;" onclick="window.location='{{ route('tickets.show', $ticket->id) }}'">
                    <span class="msg_title">
                        <span class="msg_status" style="{{ $ticket->status === 'open' ? 'color: #5cb85c;' : 'color: #999;' }}">
                            [{{ $ticket->status === 'open' ? __('Open') : __('Closed') }}]
                        </span>
                        {{ Str::limit($ticket->subject, 50) }}
                    </span>
                    <span class="msg_date" style="float: right; color: #999;">
                        {{ $ticket->updated_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </li>
        @endforeach
    </ul>
@endif

<style>
    .msg_list .msg {
        padding: 10px;
        border-bottom: 1px solid #3a4a5a;
        background: rgba(0, 0, 0, 0.2);
    }
    .msg_list .msg:hover {
        background: rgba(107, 159, 200, 0.1);
    }
    .msg_list .msg.msg_new {
        background: rgba(244, 132, 6, 0.1);
    }
    .msg_list .msg.msg_new:hover {
        background: rgba(244, 132, 6, 0.2);
    }
    .msg_status {
        font-weight: bold;
        margin-right: 10px;
    }
</style>
