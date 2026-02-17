<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/ivy-presto-display" rel="stylesheet">

    <style>
        {!! file_get_contents(resource_path('views/vendor/mail/html/themes/default.css')) !!}
    </style>
</head>

<body>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F7F5F4;">
        <tr>
            <td align="center" style="padding: 0; margin: 0;">
                <table  width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:100%; margin:0 auto;">
                    <tr>
                        <td style="padding: 30px 10%;text-align: center;font-family: 'Ivy Presto Display', serif;font-size: 40px;color: #995C64;letter-spacing: 5px;font-weight: 100;width: 100%;">
                            @yield('title')
                        </td>
                    </tr>

                    <tr>
                        <td width="100%" 
                            cellpadding="0" 
                            cellspacing="0" 
                            style="
                                background-color: #F7F5F4;
                                background-image: url('{{ asset('img/bb_watermark_light.png') }}');
                                background-repeat: no-repeat;
                                background-position: center center;
                                background-size: 85%;
                                border-top: none;
                                border-bottom: none;
                                margin: 0;
                                padding: 50px;
                                position: relative;
                                -premailer-cellpadding: 0;
                                -premailer-cellspacing: 0;
                                -premailer-width: 100%;
                            ">
                            <table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:85%; max-width:100%; margin:0 auto; background-color:transparent;">
                                <tr>
                                    <td style="position:relative; z-index:1; min-height:900px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" style="  padding:10px 60px 50px 30px;font-family:'Poppins', sans-serif !important;font-size:14px; line-height:1.5; margin:0;">
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; max-width:570px;margin:0">
                                <tr>
                                    <td class="content-cell" align="right">
                                        <img class="footer-logo" src="{{ asset('img/colored-logo.png') }}" alt="Barefoot Bridal">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
