@php /** @var OGame\Services\PlayerService $currentPlayer */ @endphp
@php /** @var OGame\Services\SettingsService $settings */ @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <!--
     ===========================================
       ____   _____                     __   __
      / __ \ / ____|                    \ \ / /
     | |  | | |  __  __ _ _ __ ___   ___ \ V /
     | |  | | | |_ |/ _` | '_ ` _ \ / _ \ > <
     | |__| | |__| | (_| | | | | | |  __// . \
      \____/ \_____|\__,_|_| |_| |_|\___/_/ \_\
     ===========================================

     Powered by OGameX - Explore the universe! Conquer your enemies!
     GitHub: https://github.com/lanedirt/OGameX
     Version: {{ \OGame\Facades\GitInfoUtil::getAppVersionBranchCommit() }}

    This application is released under the MIT License. For more details, visit the GitHub repository.
    -->
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" href="/img/icons/20da7e6c416e6cd5f8544a73f588e5.png"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Language" content="en"/>
    <meta name="ogame-session" content="3c442273a6de4c8f79549e78f4c3ca50e7ea7580"/>
    <meta name="ogame-version" content="{{ \OGame\Facades\GitInfoUtil::getAppVersion() }}"/>
    <meta name="ogame-timestamp" content="1513426692"/>
    <meta name="ogame-universe" content="s1"/>
    <meta name="ogame-universe-name" content="Home"/>
    <meta name="ogame-language" content="en"/>
    <meta name="ogame-player-id" content="{{ $currentPlayer->getId() }}"/>
    <meta name="ogame-player-name" content="{{ $currentPlayer->getUsername() }}"/>

    <title>Admin Panel - {{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="{{ mix('css/ingame.css') }}">
    <script src="{{ mix('js/ingame.min.js') }}"></script>

    <script type="text/javascript">
        window.token = "{{ csrf_token() }}";
    </script>

    <style>
        /* Admin Layout Styles */
        body.admin-layout {
            background: #0d1014 url('/img/game/menu_bg.jpg') repeat-x top center;
            min-height: 100vh;
        }

        #adminWrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        #adminHeader {
            background: linear-gradient(180deg, #1a1f25 0%, #0d1014 100%);
            border: 1px solid #3a4a5a;
            border-radius: 5px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #adminHeader .admin-title {
            color: #f48406;
            font-size: 20px;
            font-weight: bold;
        }

        #adminHeader .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        #adminHeader .admin-info a {
            color: #6f9fc8;
            text-decoration: none;
        }

        #adminHeader .admin-info a:hover {
            color: #fff;
        }

        #adminHeader .admin-clock {
            color: #848484;
            font-size: 11px;
        }

        #adminTabs {
            background: linear-gradient(180deg, #1a1f25 0%, #0d1014 100%);
            border: 1px solid #3a4a5a;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        #adminTabs ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            border-bottom: 1px solid #3a4a5a;
        }

        #adminTabs ul li {
            margin: 0;
        }

        #adminTabs ul li a {
            display: block;
            padding: 15px 25px;
            color: #6f9fc8;
            text-decoration: none;
            font-size: 13px;
            border-right: 1px solid #3a4a5a;
            transition: background 0.2s, color 0.2s;
        }

        #adminTabs ul li a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        #adminTabs ul li a.active {
            background: #f48406;
            color: #fff;
        }

        #adminTabs ul li a .badge {
            background: #c00;
            color: #fff;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
        }

        #adminContent {
            background: linear-gradient(180deg, #1a1f25 0%, #0d1014 100%);
            border: 1px solid #3a4a5a;
            border-radius: 5px;
            padding: 20px;
            min-height: 500px;
        }

        #adminContent .maincontent {
            background: transparent;
        }

        #adminContent #buttonz {
            margin: 0;
        }

        #adminContent #buttonz .header {
            background: linear-gradient(180deg, #405060 0%, #2a3540 100%);
            border-radius: 5px 5px 0 0;
        }

        #adminContent #buttonz .content {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #3a4a5a;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }

        .back-to-game {
            display: inline-block;
            padding: 8px 15px;
            background: #2a3540;
            color: #6f9fc8;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            border: 1px solid #3a4a5a;
        }

        .back-to-game:hover {
            background: #3a4a5a;
            color: #fff;
        }
    </style>
</head>
<body id="{{ !empty($body_id) ? $body_id : 'adminpage' }}" class="ogame lang-en default no-touch admin-layout">

<div id="adminWrapper">
    @include('ingame.layouts.admin-header')

    @include('ingame.layouts.admin-tabs')

    <div id="adminContent">
        @if (session('status'))
            <div class="alert alert-success" style="background: #1a472a; color: #5cb85c; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" style="background: #4a1a1a; color: #d9534f; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize any jQuery UI components if needed
        if (typeof $.fn.tooltip !== 'undefined') {
            $('.tooltipHTML').tooltip();
        }
    });
</script>

</body>
</html>
