@extends('ingame.layouts.main')

@section('content')

    <div id="characterclassselection">
        <div id="inhalt">
            <div class="header small" id="planet">
                <h2>@lang('Class Selection')</h2>
            </div>
            <div class="c-left shortCorner"></div>
            <div class="c-right shortCorner"></div>
            <div class="boxWrapper">
                <div class="header"></div>
                <div class="content">
                    <h2>@lang('Choose Your Class')</h2>
                    <p>@lang('Select a class to receive additional benefits.')
                        @if($currentClass && !$canChangeClass)
                            <br><span class="overmark">@lang('You can change your class once per week. Next change available: ') <span class="countdown" data-time="{{ $classChangedAt->addWeek()->timestamp }}">{{ $classChangedAt->addWeek()->format('Y-m-d H:i:s') }}</span></span>
                        @endif
                    </p>

                    <div class="characterclass boxes">
                        <!-- Collector Class -->
                        <div class="characterclass box {{ $currentClass === 'collector' ? 'selected' : '' }}" data-character-class-id="1" data-character-class-name="Collector">
                            <div class="buttons">
                                @if($currentClass === 'collector')
                                    <a class="deactivate-it deactivate" href="javascript:void(0);" onclick="deselectClass(1)">
                                        <span>@lang('Deactivate')</span>
                                    </a>
                                @elseif($canChangeClass)
                                    <a class="build-it" href="javascript:void(0);" onclick="selectClass(1, 'Collector')">
                                        <span>@lang('Select')</span>
                                    </a>
                                @else
                                    <a class="build-it_disabled" href="javascript:void(0);">
                                        <span>@lang('Cooldown Active')</span>
                                    </a>
                                @endif
                            </div>
                            <div class="sprite characterclass large miner"></div>
                            <div class="boxClassBoni">
                                <h2>@lang('Collector')</h2>
                                <ul>
                                    <li class="characterclass bonus">+25% mine production</li>
                                    <li class="characterclass bonus">+10% energy production</li>
                                    <li class="characterclass bonus">+100% speed for Transporters</li>
                                    <li class="characterclass bonus">+25% cargo bay for Transporters</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">+50% Crawler bonus</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">+10% more usable Crawlers with Geologist</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">Overload the Crawlers up to 150%</li>
                                </ul>
                            </div>
                            <div class="shipinfo">
                                <span>Crawler</span>
                                <div class="shipdescription">The Crawler is a large trench vehicle that increases the production of mines and synthesizers. Each Crawler increases production by 0.02% per resource type. As a collector, production bonus also increases. The maximum total bonus depends on the overall level of your mines. <em>(Coming soon)</em></div>
                                <div class="sprite ship small ship217"></div>
                            </div>
                        </div>

                        <!-- General Class -->
                        <div class="characterclass box {{ $currentClass === 'general' ? 'selected' : '' }}" data-character-class-id="2" data-character-class-name="General">
                            <div class="buttons">
                                @if($currentClass === 'general')
                                    <a class="deactivate-it deactivate" href="javascript:void(0);" onclick="deselectClass(2)">
                                        <span>@lang('Deactivate')</span>
                                    </a>
                                @elseif($canChangeClass)
                                    <a class="build-it" href="javascript:void(0);" onclick="selectClass(2, 'General')">
                                        <span>@lang('Select')</span>
                                    </a>
                                @else
                                    <a class="build-it_disabled" href="javascript:void(0);">
                                        <span>@lang('Cooldown Active')</span>
                                    </a>
                                @endif
                            </div>
                            <div class="sprite characterclass large warrior"></div>
                            <div class="boxClassBoni">
                                <h2>@lang('General')</h2>
                                <ul>
                                    <li class="characterclass bonus">+100% speed for combat ships</li>
                                    <li class="characterclass bonus">+100% speed for Recyclers</li>
                                    <li class="characterclass bonus">-25% deuterium consumption for all ships</li>
                                    <li class="characterclass bonus">+25% cargo bay for Recyclers and Pathfinders</li>
                                    <li class="characterclass bonus">+2 fleet slots</li>
                                    <li class="characterclass bonus">+5 additional Moon Fields</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">A small chance to immediately destroy a Deathstar with a light fighter</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">Wreckage at attack</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">+2 combat research levels</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">Detailed fleet speed settings</li>
                                </ul>
                            </div>
                            <div class="shipinfo">
                                <span>Reaper</span>
                                <div class="shipdescription">There's hardly anything more destructive than a ship of the Reaper class. These vessels combine fire power, strong shields, speed and capacity along with the unique ability to mine a portion of the created debris field directly after a battle. <em>(Coming soon)</em></div>
                                <div class="sprite ship small ship218"></div>
                            </div>
                        </div>

                        <!-- Discoverer Class -->
                        <div class="characterclass box {{ $currentClass === 'discoverer' ? 'selected' : '' }}" data-character-class-id="3" data-character-class-name="Discoverer">
                            <div class="buttons">
                                @if($currentClass === 'discoverer')
                                    <a class="deactivate-it deactivate" href="javascript:void(0);" onclick="deselectClass(3)">
                                        <span>@lang('Deactivate')</span>
                                    </a>
                                @elseif($canChangeClass)
                                    <a class="build-it" href="javascript:void(0);" onclick="selectClass(3, 'Discoverer')">
                                        <span>@lang('Select')</span>
                                    </a>
                                @else
                                    <a class="build-it_disabled" href="javascript:void(0);">
                                        <span>@lang('Cooldown Active')</span>
                                    </a>
                                @endif
                            </div>
                            <div class="sprite characterclass large explorer"></div>
                            <div class="boxClassBoni">
                                <h2>@lang('Discoverer')</h2>
                                <ul>
                                    <li class="characterclass bonus">-25% research time</li>
                                    <li class="characterclass bonus">Increased gain on successful expeditions</li>
                                    <li class="characterclass bonus">+10% larger planets on colonisation</li>
                                    <li class="characterclass bonus">+2 expeditions</li>
                                    <li class="characterclass bonus">75% loot from inactive players</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">Debris fields created on expeditions visible in Galaxy view</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">-50% chance of expedition enemies</li>
                                    <li class="characterclass bonus coming-soon" title="Not yet implemented">+20% phalanx range</li>
                                </ul>
                            </div>
                            <div class="shipinfo">
                                <span>Pathfinder</span>
                                <div class="shipdescription">Pathfinders are fast and spacious. Their construction method is optimised for pushing into unknown territory. They are capable of discovering and mining debris fields during expeditions. Additionally they can find items out on expeditions. <em>(Coming soon)</em></div>
                                <div class="sprite ship small ship219"></div>
                            </div>
                        </div>
                    </div>

                    <br>
                </div>
                <div class="footer"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var locaCharacterClassSelection = {
            "LOCA_CHARACTER_CLASS_NOTE_ACTIVATE": "Do you want to activate the #characterClassName# class? In doing so, you will lose your current class.",
            "LOCA_CHARACTER_CLASS_NOTE_DEACTIVATE": "Do you really want to deactivate the #characterClassName# class?",
            "LOCA_ALL_YES": "@lang('yes')",
            "LOCA_ALL_NO": "@lang('No')",
            "LOCA_ALL_NOTICE": "@lang('Reference')"
        };

        function selectClass(classId, className) {
            @if(!$canChangeClass)
                fadeBox("@lang('You can only change your class once per week.')", true);
                return;
            @endif

            var message = locaCharacterClassSelection.LOCA_CHARACTER_CLASS_NOTE_ACTIVATE.replace('#characterClassName#', className);

            if (confirm(message)) {
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
                            : "@lang('Failed to change class')";
                        fadeBox(errorMsg, true);
                    }
                });
            }
        }

        function deselectClass(classId) {
            @if(!$canChangeClass)
                fadeBox("@lang('You can only change your class once per week.')", true);
                return;
            @endif

            var className = $('.characterclass.box[data-character-class-id="' + classId + '"]').data('character-class-name');
            var message = locaCharacterClassSelection.LOCA_CHARACTER_CLASS_NOTE_DEACTIVATE.replace('#characterClassName#', className);

            if (confirm(message)) {
                $.ajax({
                    url: '{{ route('characterclass.deselect') }}',
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
                            : "@lang('Failed to deactivate class')";
                        fadeBox(errorMsg, true);
                    }
                });
            }
        }

        // Countdown timer for cooldown
        $(document).ready(function() {
            $('.countdown').each(function() {
                var targetTime = $(this).data('time');
                var $this = $(this);

                function updateCountdown() {
                    var now = Math.floor(Date.now() / 1000);
                    var diff = targetTime - now;

                    if (diff <= 0) {
                        $this.text('Available now');
                        return;
                    }

                    var days = Math.floor(diff / 86400);
                    var hours = Math.floor((diff % 86400) / 3600);
                    var minutes = Math.floor((diff % 3600) / 60);
                    var seconds = diff % 60;

                    var text = '';
                    if (days > 0) text += days + 'd ';
                    if (hours > 0 || days > 0) text += hours + 'h ';
                    if (minutes > 0 || hours > 0 || days > 0) text += minutes + 'm ';
                    text += seconds + 's';

                    $this.text(text);
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            });
        });
    </script>

    <style>
        .characterclass.bonus.coming-soon {
            opacity: 0.5;
            font-style: italic;
        }
        .characterclass.bonus.coming-soon::after {
            content: " ⏳";
        }
    </style>

@endsection
