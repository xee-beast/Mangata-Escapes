<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'Barefoot Bridal'))</title>
    @yield('meta')

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:300,400,600&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bellefair&family=Montserrat&family=Open+Sans&family=Dancing+Script:ital,wght@0,100;0,200;0,300;0,400;1,100;1,200;1,300;1,400&display=swap" rel="stylesheet">

    <!-- Styles -->
    @if (app()->environment('local'))
    <link href="{{ mix('css/web/app.css') }}" rel="stylesheet">
    @else
    <link href="{{ asset('css/web/app.css') }}" rel="stylesheet">
    @endif


    <style type="text/css">
        @font-face {
            font-family: 'brittany_signatureregular';
            src: url({{ asset('Brittany-Signature/brittanysignature-webfont.woff2') }}) format('woff2'),
                url({{ asset('Brittany-Signature/brittanysignature-webfont.woff') }}) format('woff');
            font-weight: normal;
            font-style: normal;
        }
    </style>

    @yield('styles')
</head>

<body>
    <header>
        @include('web.layouts.header')
    </header>

    <div id="app">
        <main role="main">
            @yield('content')
        </main>
    </div>

    <footer>
        @include('web.layouts.footer')
    </footer>

    @yield('scripts')
</body>

<!-- Scripts -->
<script>window.assetUrl = "{{ asset('') }}";</script>
@if (app()->environment('local'))
<script src="{{ mix('js/web/app.js') }}" defer></script>
@else
<script src="{{ asset('js/web/app.js') }}" defer></script>
@endif
</html>
