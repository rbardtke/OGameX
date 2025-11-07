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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }} - Battle Simulator</title>

    <link rel="stylesheet" href="{{ mix('css/ingame.css') }}">
    <style>
        body {
            background: #0d1117;
            color: #c9d1d9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        #content {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div id="content">
        @yield('content')
    </div>

    <script src="{{ mix('js/ingame.js') }}"></script>
    @stack('scripts')
</body>
</html>
