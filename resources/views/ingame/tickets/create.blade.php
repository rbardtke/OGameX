@extends('ingame.layouts.main')

@section('content')

    <div id="content">
        <div id="buttonz">
            <div class="header">
                <h2>@lang('Create New Ticket')</h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <div style="margin-bottom: 20px;">
                        <a href="{{ route('tickets.index') }}" style="color: #6f9fc8; text-decoration: none;">
                            &larr; @lang('Back to Tickets')
                        </a>
                    </div>

                    <form action="{{ route('tickets.store') }}" method="POST">
                        @csrf

                        <p class="box_highlight textCenter no_buddies">@lang('Contact Support')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy" for="subject">@lang('Subject'):</label>
                                <div class="thefield">
                                    <input type="text" id="subject" name="subject" class="textInput w200"
                                           value="{{ old('subject') }}"
                                           placeholder="@lang('Brief description of your issue')"
                                           required minlength="3" maxlength="255">
                                </div>
                                @error('subject')
                                    <div style="color: #d9534f; font-size: 11px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fieldwrapper">
                                <label class="styled textBeefy" for="body">@lang('Message'):</label>
                                <div class="thefield">
                                    <textarea id="body" name="body" rows="8"
                                              style="width: 100%; padding: 10px; background: #0d1014; border: 1px solid #3a4a5a; color: #fff; border-radius: 3px; resize: vertical;"
                                              placeholder="@lang('Please describe your issue in detail...')"
                                              required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
                                </div>
                                @error('body')
                                    <div style="color: #d9534f; font-size: 11px; margin-top: 5px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fieldwrapper" style="text-align: center; margin-top: 20px;">
                                <button type="submit" class="btn_blue" style="padding: 10px 30px;">
                                    @lang('Submit Ticket')
                                </button>
                            </div>
                        </div>
                    </form>

                    <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 5px; color: #999; font-size: 12px;">
                        <p><strong>@lang('Tips for a faster response'):</strong></p>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>@lang('Be as specific as possible about your issue')</li>
                            <li>@lang('Include relevant coordinates or player names if applicable')</li>
                            <li>@lang('Describe what you expected to happen vs what actually happened')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
