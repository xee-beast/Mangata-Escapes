<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    <!-- Fonts: DejaVu Sans is built into Dompdf, custom fonts used with Snappy/wkhtmltopdf -->
    <style type="text/css">
        @font-face {
            font-family: 'Josefin';
            font-weight: normal;
            src: url('{{ app()->environment("local") ? public_path("fonts/JosefinSans-Thin.ttf") : asset("fonts/JosefinSans-Thin.ttf") }}');
        }

        @font-face {
            font-family: 'Open';
            font-weight: normal;
            src: url('{{ app()->environment("local") ? public_path("fonts/OpenSans-Light.ttf") : asset("fonts/OpenSans-Light.ttf") }}');
        }

        @font-face {
            font-family: 'Montserrat';
            font-weight: normal;
            src: url('{{ app()->environment("local") ? public_path("fonts/Montserrat-Thin.ttf") : asset("fonts/Montserrat-Thin.ttf") }}');
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', 'Open', sans-serif;
            font-size: 18px;
            color: #495057;
            text-transform: uppercase;
        }

        .client-page .page {
            /* page-break-inside: avoid; */
            position: relative;
            top: 0;
            left: 0;
            margin: 0;
            border: 24px #e5dcdf solid;
            padding: 24px 48px;
            width: 912px;
            /* height: 1398px; */
        }
        
        .tc-page .page {
            page-break-inside: avoid;
            position: relative;
            top: 0;
            left: 0;
            margin: 0;
            border: 24px #e5dcdf solid;
            padding: 24px 48px;
            width: 912px;
            height: 1398px;
        }

        .page:not(:last-child) {
            page-break-after: always;
        }

        .header,
        .body,
        .footer {
            margin-top: 32px;
        }

        .header {
            font-family: 'DejaVu Sans', 'Montserrat', sans-serif;
        }

        .body {
            padding-left: 12px;
            padding-right: 8px;
        }

        table {
            page-break-inside: auto;
            width: 100%;
            font-size: 16px;
            border-collapse: collapse;
        }

        table th {
            page-break-inside: avoid;
            font-size: 18px;
            background-color: #e5dcdf;
            white-space: nowrap;
        }

        table th,
        table td {
            page-break-inside: avoid;
            padding: 4px;
            border: 1px solid #495057;
            text-align: center;
        }
        table tr{
            page-break-inside: avoid; 
        }

        table th.is-spaced,
        table td.is-spaced {
            padding: 8px;
        }

        table th.is-borderless,
        table td.is-borderless {
            border: none !important;
        }

        table td.no-wrap {
            white-space: nowrap;
        }

        .footer {
            page-break-before: avoid;
            margin-top: 150px;
            position: absolute;
            bottom: -22px;
            width: 912px;
            text-align: center;
        }

        .footer img {
            margin-bottom: 18px;
            width: 320px;
        }

        .footer .paging {
            font-size: 15px;
        }
    </style>

    @yield('styles')
</head>

<body>
    @yield('body')
</body>

</html>
