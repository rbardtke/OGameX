<div id="ticketsTab">
    <div class="tab_ctn">
        <div class="js_subtabs_tickets ui-tabs ui-corner-all ui-widget ui-widget-content">
            <ul class="subtabs ui-tabs-nav ui-corner-all ui-helper-reset ui-helper-clearfix ui-widget-header" role="tablist">
                <li id="subtabs-nfTickets-list"
                    class="list_item first ui-tabs-tab ui-corner-top ui-state-default ui-tab ui-tabs-active ui-state-active"
                    data-subtabname="My Tickets" data-tabid="tickets-list" role="tab" tabindex="0"
                    aria-controls="ui-id-tickets-1" aria-labelledby="ui-id-tickets-1-tab" aria-selected="true"
                    aria-expanded="true">
                    <a href="{{ route('messages.ajax.gettabcontents', ['tab' => 'tickets', 'subtab' => 'list']) }}"
                       class="txt_link ui-tabs-anchor" role="presentation" tabindex="-1"
                       id="ui-id-tickets-1-tab">
                        @lang('My Tickets')
                    </a>
                </li>
                <li id="subtabs-nfTickets-create"
                    class="list_item last ui-tabs-tab ui-corner-top ui-state-default ui-tab"
                    data-subtabname="New Ticket" data-tabid="tickets-create" role="tab" tabindex="-1"
                    aria-controls="ui-id-tickets-2" aria-labelledby="ui-id-tickets-2-tab" aria-selected="false"
                    aria-expanded="false">
                    <a href="{{ route('messages.ajax.gettabcontents', ['tab' => 'tickets', 'subtab' => 'create']) }}"
                       class="txt_link ui-tabs-anchor" role="presentation" tabindex="-1"
                       id="ui-id-tickets-2-tab">
                        @lang('New Ticket')
                    </a>
                </li>
            </ul>
            <div id="ui-id-tickets-1" aria-live="polite" aria-labelledby="ui-id-tickets-1-tab" role="tabpanel"
                 class="ui-tabs-panel ui-corner-bottom ui-widget-content" aria-hidden="false">
            </div>
            <div id="ui-id-tickets-2" aria-live="polite" aria-labelledby="ui-id-tickets-2-tab" role="tabpanel"
                 class="ui-tabs-panel ui-corner-bottom ui-widget-content" aria-hidden="true"
                 style="display: none;">
            </div>
        </div>
    </div>
</div>

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
