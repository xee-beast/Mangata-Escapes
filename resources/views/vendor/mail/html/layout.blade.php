<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<body style="margin:0; padding:0; font-family:'Poppins', sans-serif !important; -webkit-text-size-adjust:100%; color:#000000; background-color:#F7F5F4;">

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; table-layout:fixed; margin:0; padding:0; font-family:'Poppins', sans-serif !important;">
        <tr>
            <td align="center" style="font-family:'Poppins', sans-serif !important;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; font-family:'Poppins', sans-serif !important;">
                    {{ $header ?? '' }}
                    <tr>
                        <td style="padding:0; margin:0; font-family:'Poppins', sans-serif !important;">
                            <table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; max-width:570px; margin:0 auto; font-family:'Poppins', sans-serif !important;">
                                <tr>
                                    <td 
                                        background="{{ asset('img/bb_watermark_light.png') }}" 
                                        style="
                                            padding:10px 5%; 
                                            background-repeat:no-repeat; 
                                            background-position:center; 
                                            background-size:contain; 
                                            text-align:left;
                                            font-family:'Poppins', sans-serif !important;
                                            box-sizing:border-box;
                                        "
                                    >
                                        <div style="background-color:transparent; font-size:16px !important; font-family:'Poppins', sans-serif !important; line-height:1.8; color:#3C3B3B; width:100%; display:block;">
                                            {{ Illuminate\Mail\Markdown::parse($slot) }}
                                            {{ $subcopy ?? '' }}
                                        </div>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                    {{ $footer ?? '' }}
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
