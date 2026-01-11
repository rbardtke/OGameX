@extends('ingame.layouts.admin')

@section('content')

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('Event Settings')</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <p class="box_highlight textCenter no_buddies">@lang('Event Settings')</p>

                    <div class="group bborder" style="display: block;">
                        <div class="fieldwrapper" style="text-align: center; padding: 40px;">
                            <div style="color: #6f9fc8; font-size: 48px; margin-bottom: 20px;">
                                <span style="opacity: 0.5;">&#128679;</span>
                            </div>
                            <h3 style="color: #f48406; margin-bottom: 10px;">@lang('Coming Soon')</h3>
                            <p style="color: #999;">
                                @lang('Event settings will be available in a future update.')
                            </p>
                            <p style="color: #666; font-size: 12px; margin-top: 20px;">
                                @lang('This section will allow you to configure in-game events such as resource bonuses, speed events, and special occasions.')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
