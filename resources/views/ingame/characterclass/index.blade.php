@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="characterclasscomponent" class="maincontent">
        <div id="characterclass">
            <div id="inhalt">
                <div id="planet">
                    <h2>@lang('Character Class')</h2>
                </div>
                <div class="c-left"></div>
                <div class="c-right"></div>

                <div id="content" style="color:#fff;">
                    <div class="contentBox">
                        <h3>@lang('Select Your Character Class')</h3>

                        @if($currentClass)
                            <div class="infobox">
                                <p><strong>@lang('Current Class'):</strong> {{ ucfirst($currentClass) }}</p>
                                @if($classChangedAt)
                                    <p><strong>@lang('Last Changed'):</strong> {{ $classChangedAt->format('Y-m-d H:i:s') }}</p>
                                @endif
                                @if(!$canChangeClass)
                                    <p class="error">@lang('You can change your class once per week. Next change available: ') {{ $classChangedAt->addWeek()->format('Y-m-d H:i:s') }}</p>
                                @endif
                            </div>
                        @else
                            <div class="infobox">
                                <p>@lang('You have not selected a character class yet. Choose one below to activate special bonuses and abilities.')</p>
                            </div>
                        @endif

                        <div class="characterClassGrid" style="display: flex; gap: 20px; margin-top: 20px;">
                            <!-- Collector Class -->
                            <div class="characterClassCard {{ $currentClass === 'collector' ? 'active' : '' }}" style="flex: 1; border: 2px solid #555; padding: 15px; border-radius: 8px;">
                                <div class="characterClassHeader" style="text-align: center; margin-bottom: 15px;">
                                    <div class="sprite characterclass medium miner" style="margin: 0 auto 10px;"></div>
                                    <h3>@lang('Collector')</h3>
                                </div>
                                <div class="characterClassDescription">
                                    <h4>@lang('Bonuses'):</h4>
                                    <ul>
                                        <li>+25% base mine production</li>
                                        <li>+10% base energy production</li>
                                        <li>+100% speed for cargo ships</li>
                                        <li>+25% cargo capacity for cargo ships</li>
                                    </ul>
                                </div>
                                <div class="characterClassActions" style="text-align: center; margin-top: 15px;">
                                    @if($canChangeClass)
                                        @if($currentClass === 'collector')
                                            <button class="btn_blue" disabled>@lang('Currently Active')</button>
                                        @else
                                            <button class="btn_blue selectClassBtn" data-class-id="1">@lang('Select Collector')</button>
                                        @endif
                                    @else
                                        <button class="btn_blue" disabled>@lang('Cannot Change Yet')</button>
                                    @endif
                                </div>
                            </div>

                            <!-- General Class -->
                            <div class="characterClassCard {{ $currentClass === 'general' ? 'active' : '' }}" style="flex: 1; border: 2px solid #555; padding: 15px; border-radius: 8px;">
                                <div class="characterClassHeader" style="text-align: center; margin-bottom: 15px;">
                                    <div class="sprite characterclass medium warrior" style="margin: 0 auto 10px;"></div>
                                    <h3>@lang('General')</h3>
                                </div>
                                <div class="characterClassDescription">
                                    <h4>@lang('Bonuses'):</h4>
                                    <ul>
                                        <li>+100% speed for combat ships</li>
                                        <li>+100% speed for recyclers</li>
                                        <li>-25% deuterium consumption</li>
                                        <li>+25% cargo for recyclers & pathfinders</li>
                                    </ul>
                                </div>
                                <div class="characterClassActions" style="text-align: center; margin-top: 15px;">
                                    @if($canChangeClass)
                                        @if($currentClass === 'general')
                                            <button class="btn_blue" disabled>@lang('Currently Active')</button>
                                        @else
                                            <button class="btn_blue selectClassBtn" data-class-id="2">@lang('Select General')</button>
                                        @endif
                                    @else
                                        <button class="btn_blue" disabled>@lang('Cannot Change Yet')</button>
                                    @endif
                                </div>
                            </div>

                            <!-- Discoverer Class -->
                            <div class="characterClassCard {{ $currentClass === 'discoverer' ? 'active' : '' }}" style="flex: 1; border: 2px solid #555; padding: 15px; border-radius: 8px;">
                                <div class="characterClassHeader" style="text-align: center; margin-bottom: 15px;">
                                    <div class="sprite characterclass medium explorer" style="margin: 0 auto 10px;"></div>
                                    <h3>@lang('Discoverer')</h3>
                                </div>
                                <div class="characterClassDescription">
                                    <h4>@lang('Bonuses'):</h4>
                                    <ul>
                                        <li>-25% research time</li>
                                        <li>+50% expedition resources (economy speed × 1.5)</li>
                                        <li>+50% expedition ships (economy speed × 1.5)</li>
                                    </ul>
                                </div>
                                <div class="characterClassActions" style="text-align: center; margin-top: 15px;">
                                    @if($canChangeClass)
                                        @if($currentClass === 'discoverer')
                                            <button class="btn_blue" disabled>@lang('Currently Active')</button>
                                        @else
                                            <button class="btn_blue selectClassBtn" data-class-id="3">@lang('Select Discoverer')</button>
                                        @endif
                                    @else
                                        <button class="btn_blue" disabled>@lang('Cannot Change Yet')</button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="footer" style="margin-top: 30px;">
                            <p><strong>@lang('Note'):</strong> @lang('You can change your character class once per week.')</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.selectClassBtn').click(function() {
                var classId = $(this).data('class-id');
                var btn = $(this);

                btn.prop('disabled', true).text('{{ __("Changing...") }}');

                $.ajax({
                    url: '{{ route('characterclass.select') }}',
                    type: 'POST',
                    data: {
                        characterClassId: classId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        fadeBox(response.message, false);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : '{{ __("Failed to change class") }}';
                        fadeBox(errorMsg, true);
                        btn.prop('disabled', false).text('{{ __("Select") }}');
                    }
                });
            });
        });
    </script>

@endsection
