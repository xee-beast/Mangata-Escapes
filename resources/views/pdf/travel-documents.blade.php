<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Travel Documents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Birthstone&family=Corinthia:wght@0;700&family=DM+Serif+Text:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Birthstone&family=Corinthia:wght@0;700&family=DM+Serif+Text:ital@0;1&family=Miss+Fajardose&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IM+Fell+French+Canon:ital@0;1&family=Imperial+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gwendolyn:wght@400;700&family=Imperial+Script&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bellefair&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@0;600;700&family=Montserrat:wght@0;400;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Allura&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Passions+Conflict&family=WindSong:wght@0;500&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Bona+Nova+SC:ital,wght@0,400;0,700;1,400&family=Oswald:wght@0..700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Text:ital@0;1&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Bona+Nova+SC:ital,wght@0,400;0,700;1,400&display=swap');

        @font-face {
            font-family: 'Safiramarch';
            src: url("{{ asset('fonts/Safiramarch.ttf') }}") format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Brittany';
            src: url("{{ asset('fonts/BrittanySignature.ttf') }}") format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
       
        @font-face {
            font-family: "Gwendolyn", cursive;
            src: url("{{ asset('fonts/Gwendolyn-Regular.ttf') }}") format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Poppins';
            src: url("{{asset('/fonts/Poppins-Light.ttf')}}") format('truetype');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Ivypresto Display';
            src: url('{{ asset("fonts/IvyprestoDisplay-Thin.ttf") }}') format('truetype');
            font-weight: 100;
            font-style: normal;
        }
        @font-face {
            font-family: 'Ivypresto Display';
            src: url('{{ asset("fonts/IvyprestoDisplay-Light.ttf") }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'Adore Calligraphy';
            src: url("{{ asset('/fonts/Adore.ttf') }}") format('truetype');
            font-weight:700;
            font-style: normal;
            font-display: swap;
        }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            -webkit-print-color-adjust: exact;
        }
        *,
        *::before,
        *::after { box-sizing: border-box; }

        p { margin: 0; }

        .container {
            position: relative;
            width: 100%;
            height: 1120px;  
            page-break-after: always;
            overflow: hidden; 
            display: flex;   
                     
        }
        .container:last-child,
        .container:last-of-type {
            page-break-after: auto !important;
            break-after: auto !important;
            page-break-inside: avoid !important;
        }
        .left-strip {
            width: 220px;
            height: 1120px;
            background-image: url("{{ asset('img/BB_Textures_Watercolor_Charcoal_new.jpg') }}");
            background-size: cover;      
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            z-index: 10;
        }
        .vertical-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-90deg);
            z-index: 3;
        }

        .vertical-wrapper .vertical-text {
            font-family: 'Playfair Display', serif;
            font-size: 260px;
            color: #f3efec;
            opacity: 6;
            margin-top: 70px;
        }
        .checklist-left-strip {
            width: 200px;
            height: 1121px;
            background-image: url("{{ asset('img/BB_Textures_Watercolor_Charcoal_new.jpg') }}");
            background-size: cover;      
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            z-index: 1;
        }
        .checklist-left-strip .vertical-text {
            font-family: 'Ivypresto Display', serif;
            font-size: 260px;
            color: #f3efec;
            opacity: 6;
            white-space: nowrap;
            margin-top: 50px;
        }
        .checklist-checkbox {
            position: absolute;
            left: 200px;
            top: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
            padding-top: 20px;
            padding-left: 40px;
            background: transparent; 
        }
        .checklist-checkbox::before {
            content: "";
            position: absolute;
            inset: 0; 
            background-color: rgba(231, 224, 218, 0.5); 
            z-index: -1; 
            pointer-events: none;
        }

        .checklist-checkbox ul {
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }
        .checklist-checkbox ul li {
            padding: 6px 0;      
            display: flex;         
            align-items: center;    
            gap: 12px;              
            font-size: 16px;
            line-height: 0.9;
            white-space: normal;   
            color:#3C3B3B"
        }

        .checklist-checkbox ul li input[type="checkbox"] {
            width: 20px;            
            height: 20px;
            margin: 0;              
            flex: 0 0 auto;         
            vertical-align: middle;
        }
        .checklist-checkbox ul li label.check-item {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        .checklist-checkbox ul li .check-text {
            display: inline-block;
            margin-top: -20px;
        }
        .right-image {
            position: absolute;
            left: 200px;
            top: 0;
            right: 0;
            bottom: 20px;
            overflow: hidden;
            z-index:0;
        }

        .right-image img {
            width: 100%;
            height: 90%; 
            object-fit: cover; 
            display:block;
            transform: none; 
        }

        .content-area {
            position: absolute;
            left: 220px;
            right: 0;
            top: 0;
            bottom: 0;
            background: #fffefa;
        }

        .image-wrap {
            position: absolute;
            left: 220px;
            top: 0;
            right: 0;
            bottom: 239px;
            overflow: hidden;
            z-index: -10;
        }

        .image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .travelling-text {
            position: absolute;
            left: 220px;
            right: 0;
            bottom: 0;
            height: 180px;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #3C3B3B;
            background: transparent;
            overflow: hidden;
            z-index: 2;
        }

        .travelling-text::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background-color: rgba(231, 224, 218, 0.5);
            background-image: url("{{ asset('img/bb_watermark_light.png') }}");
            background-repeat: no-repeat;
            background-size: contain;
            background-position: left bottom;
        }

        .travelling-text h1{
            font-size:60px;
            font-weight: 700;
            color: #3C3B3B;
        }

        .date{
            margin:0;
            position: relative;
            font-size: 30px;
            color: #C7979C;
            text-transform: none;
            letter-spacing: 1.2px;
            
        }
        .confirmation{
            width: 100%;
        }
        .confirmation img{
            width: 100%;
            height: 30%;
            object-fit: cover;
            display: block;
        }
        .confirmation-content {
            position: relative;
            z-index: 1; 
            padding: 0px 40px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            text-align: start;
            color: #3C3B3B; 
            background: transparent; 
            overflow: hidden;
        }

       
        .confirmation-content::before {
            content: "";
            position: absolute;
            inset: 0; 
            z-index: -1; 
            pointer-events: none;
            background-color: rgba(228, 220, 213, 0.5);
            background-image: url("{{ asset('img/bb_watermark_light.png') }}");
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center top;
        }

        .confirmation-content h1 {
            margin: 0px 0px 10px 0px; 
            padding: 0;
            font-size: 40px;
            font-weight: 600;
            letter-spacing: -3px;
            color: #3C3B3B;
        }

        /* Confirmation text */
        .confirmation-content .confirmation-text {
            margin: 0px 0px 20px 30px; 
            padding: 0;
            font-size: 18px;
            font-family: "Poppins", sans-serif;
            line-height: 24px;
            font-weight: 500;
            color: #3C3B3B;
        }

        /* Hotel name */
        .hotel-name {
            margin-top: 5px;
            font-size: 32px;
            color: #C7979C;
            font-weight: 600;
            text-align: center;
        }

        .airport-transfer {
            text-align: center;
            position: relative;
            height: 90%;
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center;
            padding: 50px 20px;
            overflow: hidden;
            color: #3C3B3B; 
        }

        .airport-transfer::before {
            content: "";
            position: absolute;
            inset: 0; 
            background: #E7E0DA; 
            z-index: -1; 
            opacity: 0.5;
        }

        
        /* Watermark image layer */
        .airport-transfer .watermark {
            position: absolute;
            top: 8%;
            left: 50%;
            transform: translate(-50%, -50%); 
            width: 130px;  
            height: 120px;
            background-image: url("{{ asset('img/BB_Mark_Full-Color.png') }}");
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center center;
            opacity: 0.3; 
            z-index: 1;
            pointer-events: none;
        }
        .airport-transfer p {
            font-size: 18px;
            color:#3C3B3B;
            margin-top: 30px;
            margin-bottom: 10px;
        } 
        .airport-flight{
            font-size: 14px;
            color:#3C3B3B;
            font-family: "Poppins";
            line-height:20px;
            font-weight: 300;
            z-index:3;
        }
        .travel-issue{
            width:100%;
        }
        .travel-issue img{
            width: 100%;
            height: 25%;
            object-fit: cover;
            display: block;
        }
        .travel-issue-title{
            color:#3C3B3B;
            font-size: 40px;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom:5px !important;
            margin-left: 50px;
            font-family: "Ivypresto Display", serif;
        }   
        .travel-issue-note {
            position: relative;
            background: #995C64;
            padding: 15px 40px;
            margin: 0px 25px;
            border-radius: 3px;
            opacity: 6;
            font-size: 12px;
            line-height: 1.2;
            font-family: "Poppins", sans-serif;
            color: #F7F5F4;
            overflow: hidden; 
        }
        .travel-issue-note::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("{{ asset('img/output-onlinepngtools.png') }}");
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center center;
            opacity: 0.1; 
            z-index: 1;
            pointer-events: none;
        }
        
        .travel-issue-note .note {
            
            margin-top: 0px;
        }
        .travel-issue-note p {
            font-size:12px;
            font-weight:500;
            font-family: "Poppins", sans-serif;
        }
        .travel-issue-note p span {
            font-size:14px;
            font-weight:700;
            font-family: "Poppins", sans-serif;
        }
        .bon-voyage{
            width: 100%;
            height:650px;
        }
        .bon-voyage img{
            width: 100%;
            height: 650px;
            object-fit: cover;
            display: block;
        }
        .bon-voyage-content {
            position: relative;
            height: 34%;
            padding: 0px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            z-index: 1;
            color: #3C3B3B; 
            background: transparent; 
            overflow: hidden;
        }
        .bon-voyage-content::before {
            content: "";
            position: absolute;
            left:0;
            inset: 0; 
            z-index: -1; 
            pointer-events: none;
            background-color: rgba(228, 220, 213, 0.5);
            background-image: url("{{ asset('img/Capture-removebg-preview.png') }}");
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center top;
        }
        .bon-voyage-content h1 {
            font-size: 100px;
            color:#3C3B3B;
            font-style: normal;
            z-index: 9;
            margin:0;
            padding:0;
        }
        .bon-voyage-content p {
            font-size: 18px;
            line-height: 20px;
            margin-top:20px;
            display:flex;
            text-align:center;
            justify-content:center;
            color:#3C3B3B;
            font-weight: 400;
            z-index: 9;
        }
        .footer-bottom{
            width:100%;
            height:65px;
            background:#3C3B3B;
            position:absolute;
            bottom:7px;
            left:0;
            display:flex;
            justify-content:center;
            align-items:center;
            text-align:center;
            padding-top:15px;
        }
        .footer-bottom img{
            height:50px;
            width:500px;
        }
        @page {
            size: A4;
            margin: 0;
        }

    </style>

</head>

<body>
        <!-- First page pdf file -->
        <div class="container ">
            <div class="left-strip">
                <div class="vertical-wrapper">
                    <div class="vertical-text">travelling</div>
                </div>
            </div>

            <div class="image-wrap">
                <img src="{{ $hotel ? ($hotel->travel_docs_cover_image ? Storage::url($hotel->travel_docs_cover_image->path) : '') : ($booking->travel_docs_cover_image ? Storage::url($booking->travel_docs_cover_image->path) : '') }}" alt="beach resort" />
            </div>

            <div class="travelling-text">
                <h1 class="is-uppercase" style="margin: 0; margin-top:5px; color:#3C3B3B; font-size:50px; font-weight:700; font-family: 'Adore Calligraphy', 'brittany_signatureregular', 'Dancing Script', cursive; z-index:99;">
                    {{ $group ? $group->name : $booking->full_name }}
                </h1>
                <div class="date" style="font-family:'Ivypresto Display', serif; font-weight:300;">{{ $group ? $group->event_date->format('F d, Y') : ($booking->check_in->format('F d, Y') . ' - ' . $booking->check_out->format('F d, Y')) }}</div>
            </div>
        </div>
        <div class="container">
            @php
                $hasConfirmationImage = ($hotel && $hotel->travel_docs_image_two) || ($booking && $booking->travel_docs_image_two);
                $confirmationContentHeight = $hasConfirmationImage ? '62%' : '92%';
            @endphp
            <div class="confirmation">
               @if($hasConfirmationImage)
                    <img 
                        src="{{ $hotel && $hotel->travel_docs_image_two ? Storage::url($hotel->travel_docs_image_two->path) : Storage::url($booking->travel_docs_image_two->path) }}" 
                        alt="confirmation hotel"
                    >
                @endif
            </div>
            <div class="confirmation-content" style="height: {{ $confirmationContentHeight }};">
                <h1 style="margin:0; padding:0; font-family:'Ivypresto Display', serif; font-weight:300; font-size:50px; color:#3C3B3B;  letter-spacing: 0.5px;">
                    CONFIRMATION
                </h1>
                <p style="font-family:'Poppins'; font-weight:300; font-size:16px; margin:0 0 20px 30px;">
                    Thank you for taking the time to review all the details of your upcoming trip.
                    <br>
                    We recommend having these travel documents accessible while traveling.
                </p>
                <div style="background-color:black; height:1px;"></div>
                @php
                    if ($group) {
                        $groupedAccommodations = $booking->roomBlocks->groupBy(function ($roomBlock) {
                            return $roomBlock->room->hotel->id;
                        });
                    } else {
                        $groupedAccommodations = $booking->roomArrangements->groupBy('hotel');
                    }
                @endphp
                @foreach ($groupedAccommodations as $hotel_info => $accommodations)
                    @php
                        if ($group) {
                            $hotel_info = $accommodations->first()->room->hotel->name;
                        }
                    @endphp
                    <div class="hotel-block">
                        <div class="hotel-name" style="font-family:'Ivypresto Display', serif; font-weight:300; font-size:32px;  text-transform: uppercase; color:#C7979C;">{{ $hotel_info }}</div>
                        <table style="border-collapse:collapse; width:100%; table-layout:fixed;">
                            <tr>
                                <th style="width:20%; text-align:left; padding:2px 10px;">
                                    <p style="margin:0; padding:0; font-family:'Poppins'; font-size:14px; text-decoration:underline; font-weight:bold; color:#3C3B3B;">Accommodations</p>
                                </th>
                                <th style="width:20%; text-align:left; padding:2px 10px;">
                                    <p style="margin:0; padding:0; font-family:'Poppins'; font-size:14px; text-decoration:underline; font-weight:bold; color:#3C3B3B;">Check In</p>
                                </th>
                                <th style="width:20%; text-align:left; padding:2px 10px;">
                                    <p style="margin:0; padding:0; font-family:'Poppins'; font-size:14px; text-decoration:underline; font-weight:bold; color:#3C3B3B;">Check Out</p>
                                </th>
                                <th style="width:20%; text-align:left; padding:2px 10px;">
                                    <p style="margin:0; padding:0; font-family:'Poppins'; font-size:14px; text-decoration:underline; font-weight:bold; color:#3C3B3B;">Nights</p>
                                </th>
                                <th style="width:20%; text-align:left; padding:2px 10px;">
                                    <p style="margin:0; padding:0; font-family:'Poppins'; font-size:14px; text-decoration:underline; font-weight:bold; color:#3C3B3B;">Bedding</p>
                                </th>
                            </tr>
                            @foreach ($accommodations as $accommodation)
                                @if ($group)
                                <tr>
                                    <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins'; font-size:14px;">
                                        <p style="margin:0; padding:0; color:#3C3B3B;">{{ $accommodation->room->name }}</p>
                                    </td>
                                    <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins'; font-size:14px;">
                                        <p style="margin:0; padding:0; color:#3C3B3B;">{{ $accommodation->pivot->check_in->format('F d, Y')  }}</p>
                                    </td>
                                    <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins'; font-size:14px;">
                                        <p style="margin:0; padding:0; color:#3C3B3B;">{{ $accommodation->pivot->check_out->format('F d, Y')  }}</p>
                                    </td>
                                    <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins'; font-size:14px;">
                                        @php
                                            $nights = $accommodation->pivot->check_in->diffInDays($accommodation->pivot->check_out);
                                            $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                                            $nightsWord = ucfirst($formatter->format($nights));
                                        @endphp
                                        <p style="margin:0; padding:0; color:#3C3B3B;">{{ $nightsWord }} ({{ $nights }}) {{ \Illuminate\Support\Str::plural('Night', $nights) }}</p>
                                    </td>
                                    <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins'; font-size:14px;">
                                        <p style="margin:0; padding:0; color:#3C3B3B;">{{ $accommodation->pivot->bed }}</p>
                                    </td>
                                </tr>
                                @else
                                    <tr>
                                        <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins';">
                                            <p class="montserrat" style="margin:0;padding:0px 10px 0px 10px; color:#3C3B3B;">{{ $accommodation->room }}</p>
                                        </td>
                                        <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins';">
                                            <p class="montserrat" style="margin:0;padding:0px 0px 0px 30px; color:#3C3B3B;">{{ $accommodation->check_in->format('F d, Y') }}</p>
                                        </td>
                                        <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins';">
                                            <p class="montserrat" style="margin:0;padding:0px 0px 0px 25px; color:#3C3B3B;">{{ $accommodation->check_out->format('F d, Y') }}</p>
                                        </td>
                                        <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins';">
                                            @php
                                                $nights = $accommodation->check_in->diffInDays($accommodation->check_out);
                                                $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                                                $nightsWord = ucfirst($formatter->format($nights));
                                            @endphp
                                            <p class="montserrat" style="margin:0;padding:0px 0px 0px 40px; color:#3C3B3B;">{{ $nightsWord }} ({{ $nights }}) {{ \Illuminate\Support\Str::plural('Night', $nights) }}</p>
                                        </td>
                                        <td style="width:20%; vertical-align:top; padding:2px 10px; font-family:'Poppins';">
                                            <p class="montserrat" style="margin:0;padding:0px 0px 0px 20px; color:#3C3B3B;">{{ $accommodation->bed }}</p>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                        </div>
                @endforeach
               <div style="margin:0; padding:0;">
                    @if($group)
                        <ul style="margin:0; margin-left:0;"> 
                        @foreach ($processed_guests as $guestData)
                            @php
                                $guest = $guestData['guest'];
                                $isDuplicateGuest = $guestData['is_duplicate'];
                                $minCheckIn = $guestData['min_check_in'];
                                $maxCheckOut = $guestData['max_check_out'];
                            @endphp
                            <li style="margin:0; padding:0;">
                                <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px"><span style="text-transform: uppercase; font-family: 'Poppins';font-size:14px">{{ $guest->name }}</span> ({{ Carbon\Carbon::parse($minCheckIn)->format('F d, Y') }} - {{ Carbon\Carbon::parse($maxCheckOut)->format('F d, Y') }})</p>
                                    @if($isDuplicateGuest)
                                        <p style="margin:0; margin-top:5px; padding:0; font-size:12px">Notes:</p>
                                        <ol style="margin:0; padding-left:20px; font-family: 'Poppins';"> 
                                            @foreach ($guestData['duplicate_details'] as $detail)
                                                <li style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px">{{ $detail['guest_name'] }} will stay in {{ $detail['client_names'] }} room from {{ Carbon\Carbon::parse($detail['check_in'])->format('F d, Y') }} to {{ Carbon\Carbon::parse($detail['check_out'])->format('F d, Y') }}.</li>
                                            @endforeach
                                        </ol>
                                    @endif
                            </li>
                        @endforeach
                        </ul>
                    @else
                        <ul style="margin:0; margin-left:10px; font-family: 'Poppins'; font-size:12px">
                        @foreach ($processed_guests as $guestData)
                            @php
                                $guest = $guestData['guest'];
                            @endphp
                            <li style="margin:0; padding:0;">
                                <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:14px; "><span style="text-transform: uppercase; font-family: 'Poppins';">{{ $guest->name }}</span> ({{ $guest->check_in->format('F d, Y') }} - {{ $guest->check_out->format('F d, Y') }})</p>
                            </li>
                        @endforeach
                        </ul>
                    @endif
                </div>
                <div style="margin:0; margin-top:10px; margin-left:40px; padding:0; font-family: 'Poppins';">
                    @if($booking->special_requests)
                        <p style="margin:0; padding:0; font-family:'Poppins', sans-serif; font-size:12px; font-weight:400;">
                            <span style="font-family:'Poppins', sans-serif; font-size:12px; font-weight:600;">
                                Special Request:
                            </span>
                            {{ $booking->special_requests }}
                        </p>
                    @endif
                    <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px;">Bedding is not confirmed unless listed within the room category name.</p>
                    <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px;">Guests will receive:</p>
                    <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px;">- Accommodations as noted on itinerary.</p>
                    @if (!$group || ($group && $group->id !== 479))
                    <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px;">- All meals and drinks.</p>
                    @endif
                    <p style="margin:0; padding:0; font-family: 'Poppins'; font-size:12px;">- Non-motorized watersports, activities and shows on property.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <img src="{{ asset('img/logo-white.png') }}" alt="Barefoot Bridal">
            </div>
        </div>
        @if ((($group && $group->transportation) || (!$group && $booking->transportation)) && $grouped_guests)
            @foreach ($grouped_guests as $key => $guests)
                @php
                    $guest = $guests->first();
                    $guestNamesArray = $guests->map(fn($guest) => "{$guest->first_name} {$guest->last_name}")->values();
                    $guestCount = $guestNamesArray->count();

                    if ($guestCount === 1) {
                        $guestNamesFormatted = '<span style="color:#995C64; padding: 0 0 0 5px;">' . $guestNamesArray[0] . '</span>';
                    } elseif ($guestCount === 2) {
                        $guestNamesFormatted = '<span style="color:#995C64; padding: 0 0 0 5px;">' . $guestNamesArray[0] . '</span> <span style="padding: 0 0 0 5px;">and</span> <span style="color:#995C64; padding: 0 0 0 5px;">' . $guestNamesArray[1] . '</span>';
                    } else {
                        $last = $guestNamesArray->pop();
                        $guestNamesFormatted = '<span style="color:#995C64; padding: 0 0 0 5px;">' . $guestNamesArray->join('</span>, <span style="color:#995C64; padding: 0 0 0 5px;">') . '</span> <span style="padding: 0 0 0 5px;">and</span> <span style="color:#995C64; padding: 0 0 0 5px;">' . $last . '</span>';
                    }
                @endphp
                <div class="container">
                    <div class="airport-transfer">
                        <div class="watermark"></div>
                        <h1 style="font-family:'Ivypresto Display', serif; font-weight:300;font-size:50px; margin-top:0px; color:#3C3B3B;">AIRPORT TRANSFERS</h1>
                        <p style="font-family:'Ivypresto Display', serif; font-weight:300; font-size:18px; color:#3C3B3B;">
                            Please be sure to follow these instructions to find your airport transfers and avoid additional charges!
                        </p>
                        <div class="airport-flight">If your flight itinerary information is incorrect, contact us immediately.</div>
                        <div class="airport-flight">The following flight itinerary information is for {!! $guestNamesFormatted !!}.</div>
                        <div class="montserrat" style="font-size:14px;margin-top:5px;color:#3C3B3B">
                            @php
                                $flight_manifest = $guest->flight_manifest;
                            @endphp
                            <table style="width:60%;margin:auto;line-height:0.9;font-size:14px;margin-top:10px; color:#3C3B3B">
                                <tr>
                                    @if ($flight_manifest->arrival_datetime && $flight_manifest->arrivalAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Date:</span>
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->arrival_datetime->setTimezone($flight_manifest->arrivalAirport->timezone)->format('F d, Y') }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    @if ($flight_manifest->departure_datetime && $flight_manifest->departureAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Date:</span>
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->departure_datetime->setTimezone($flight_manifest->departureAirport->timezone)->format('F d, Y') }}</span>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                <tr>
                                    @if ($flight_manifest->arrival_airline && $flight_manifest->arrival_number && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Airline Flight</span> #:
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->arrival_airline }} {{ $flight_manifest->arrival_number }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    @if ($flight_manifest->departure_airline && $flight_manifest->departure_number && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Airline Flight</span> #:
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->departure_airline }} {{ $flight_manifest->departure_number }}</span>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                <tr>
                                    @if ($flight_manifest->arrival_datetime && $flight_manifest->arrivalAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Arrival Time:</span>
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->arrival_datetime->setTimezone($flight_manifest->arrivalAirport->timezone)->format('h:i A') }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    @if ($flight_manifest->departure_datetime && $flight_manifest->departureAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT]))
                                        <td>
                                            <div class="">
                                                <span style="font-family: 'Poppins';color:#3C3B3B">Departure Time:</span>
                                                <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">{{ $flight_manifest->departure_datetime->setTimezone($flight_manifest->departureAirport->timezone)->format('h:i A') }}</span>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @if ($guest->departure_pickup_time)
                                    <tr>
                                        @if ($flight_manifest->arrival_datetime && $flight_manifest->arrivalAirport)
                                            <td>
                                                <div class=""></div>
                                            </td>
                                        @endif
                                        @if ($flight_manifest->departure_datetime && $flight_manifest->departureAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT]))
                                            <td>
                                                <div class="">
                                                    <span style="font-family:'Poppins', sans-serif !important;font-size:14px !important; color:#3C3B3B; margin-top:4px;">Departure pick up time:</span>
                                                    <span style="color:#995C64;font-family: 'Poppins'; font-size:12px;">{{ Carbon\Carbon::parse($guest->departure_pickup_time)->format('h:i A') }}</span>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            </table>
                            <table style="width:95%;margin-left:auto; color:#3C3B3B">
                                <tr>
                                    <td style="width:75%;padding-right:20px;">
                                        <div class="montserrat" style="width:100%;margin-right:auto;font-size:12.5px; color:#3C3B3B">
                                            <div class="">
                                                @php
                                                    $transfer = $group ? $group->airports->firstWhere('airport_id', $flight_manifest->arrival_airport_id ?? $flight_manifest->departure_airport_id)?->transfer : $booking->transfer;
                                                @endphp
                                                @if ($flight_manifest->arrival_datetime && $flight_manifest->arrivalAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_AIRPORT_TO_HOTEL]))
                                                <p style="font-family: 'Poppins'; font-weight:500; margin:10px 0px 0px 0px; padding:0; color:#3C3B3B">Arrival Transfer Procedure:</p>
                                                <div style="font-family:'Poppins' !important; font-size:12px !important; color:#3C3B3B;">
                                                    {!! $transfer ? $transfer->arrival_procedure : '' !!}
                                                </div>
                                                @endif
                                                <p style="font-family: 'Poppins'; font-weight:500; margin:0;padding:0; color:#3C3B3B">If you miss your flight or have changes:</p>
                                                <div style="font-family:'Poppins', sans-serif !important; font-size:12px !important; margin:0 !important; padding:0 !important; color:#3C3B3B !important;">
                                                    <span>{!! $transfer ? strip_tags($transfer->missed_or_changed_flight) : '' !!}</span>
                                                </div>
                                                <ul style="margin-top: 10px; font-size:12px; color:#3C3B3B">
                                                    <li>
                                                        <span style="font-family: 'Poppins'; font-size:12px; color:#3C3B3B">Email:</span>
                                                        <a style="text-decoration:none;color:black; font-family: 'Poppins'; font-size:12px;color:#3C3B3B" href="mailto:customercare@lomas-travel.com">{{ $transfer ? $transfer->email : '' }}</a>
                                                    </li>
                                                    <li>
                                                        <span style="font-family: 'Poppins'; font-size:12px; color:#3C3B3B">Call:</span>
                                                        <a style="text-decoration:none;color:black; font-family: 'Poppins'; font-size:12px; color:#3C3B3B" href="tel:+18445062726">{{ $transfer ? $transfer->primary_phone_number : '' }}</a>
                                                    </li>
                                                    @if ($transfer && $transfer->secondary_phone_number_label && $transfer->secondary_phone_number_value)
                                                        <li>
                                                            <span style="font-family: 'Poppins'; font-size:12px; color:#3C3B3B">{{ $transfer->secondary_phone_number_label }}:</span>
                                                            <a style="text-decoration:none;color:black; font-family: 'Poppins'; font-size:12px;color:#3C3B3B" href="sms:+529988819408 ">{{ $transfer->secondary_phone_number_value }}</a>
                                                        </li>
                                                    @endif
                                                    @if ($transfer && $transfer->whatsapp_number)
                                                        <li>
                                                            <span style="font-family: 'Poppins'; font-size:12px;color:#3C3B3B">WhatsApp Support:</span>
                                                            <a style="text-decoration:none;color:black; font-family: 'Poppins'; font-size:12px; color:#3C3B3B" href="https://wa.me/+529989800732">{{ $transfer->whatsapp_number }}</a>
                                                        </li>
                                                    @endif
                                                </ul>
                                                @if ($flight_manifest->departure_datetime && $flight_manifest->departureAirport && in_array($guest->transportation_type, [ App\Models\Booking::TRANSPORTATION_TYPE_ROUND_TRIP, App\Models\Booking::TRANSPORTATION_TYPE_ONE_WAY_HOTEL_TO_AIRPORT]))
                                                <p style="font-family: 'Poppins'; font-weight:500;margin:10px 0px 0px 0px; color:#3C3B3B">Departure Transfer Procedure:</p>
                                                <div style="font-family:'Poppins', sans-serif !important; font-size:12px !important; margin:0 !important; padding:0 !important; color:#3C3B3B !important;">
                                                    {!! $transfer->departure_procedure ?? '' !!}
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td  style="width:25%">
                                       <div style="
                                            width: 220px;
                                            height: 400px;
                                            background-image: url('{{ ($transfer && $transfer->display_image) ? Storage::url($transfer->display_image->path) : '' }}');
                                            background-size: cover;
                                            background-position: center;
                                            background-repeat: no-repeat;
                                        ">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            @if ($transfer && $transfer->app_image)
                                <div style="width:100%;height:80px;background:#767274;vertical-align:middle;position:absolute;bottom:60;left:0;" class="">
                                    @if ($transfer->app_link)
                                        <a href="{{ $transfer->app_link }}" target="_blank">
                                            <img style="width:100%;height:auto;object-fit:contain;" src="{{ Storage::url($transfer->app_image->path) }}" alt="">
                                        </a>
                                    @else
                                        <img style="width:100%;height:auto;object-fit:contain;" src="{{ Storage::url($transfer->app_image->path) }}" alt="">
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="footer-bottom">
                        <img src="{{ asset('img/logo-white.png') }}" alt="Barefoot Bridal">
                    </div>
                </div>
            @endforeach
        @endif
        <div style="background-color: rgba(228, 220, 213, 0.5);">
            <div class="container">
            @php
                $imageExists = ($hotel && $hotel->travel_docs_image_three) || ($booking && $booking->travel_docs_image_three);
            @endphp
            <div class="travel-issue" style="height: {{ $imageExists ? '25%' : '0' }}; overflow:hidden;">
              @if($imageExists)
                    <img 
                        @if ($hotel && $hotel->travel_docs_image_three)
                            src="{{ Storage::url($hotel->travel_docs_image_three->path) }}"
                        @elseif ($booking->travel_docs_image_three)
                            src="{{ Storage::url($booking->travel_docs_image_three->path) }}"
                        @endif
                        alt=""
                        style="width:100%; height:100%; object-fit:cover; display:block;"
                    >
                @endif
            </div>
            <h1 class="travel-issue-title" style="font-family:'Ivypresto Display', serif; font-weight:300;font-size:50px; margin-top:0px; color:#3C3B3B;">IMPORTANT!</h1>
            <div class="travel-issue-note" style="height: {{ $imageExists ? '56%' : '80%' }}; overflow:auto;">
                @php
                    $destination = $group ? $group->destination : $booking->destination;
                    $provider = $group ? $group->provider : $booking->provider;
                @endphp
                <p><span>In travel issues?</span> If you encounter any issues within 24 hours of travel or during your travels, please call <strong>{{ $provider ? $provider->phone_number : '' }}</strong>.</p>
                <p class="note">
                    <em>Note, that if issues are not reported during your travel, we cannot assist or provide refunds upon your return.</em>
                </p>

                <p><span>Don’t forget your passport book!</span> Ensure you have a passport book with at least six months of validity from your departure date. Check if a visa is required based on your nationality.</p>

                <div style="margin-top:8px !important; margin-bottom:10px !important; color: #F7F5F4 !important; font-size:12px !important; line-height:14px !important; margin-bottom:2px !important;">{!! $destination ? $destination->tax_description : '' !!}</div>
                <p><span>Safety is important!</span></p>
                <ul style="margin:0px;">
                    <li>
                        Only consume bottled water | Be cautious with dairy
                    </li>
                    <li>
                        Use your room safe for valuables - including your passport!
                    </li>
                    <li>
                        Don't forget essential medication.
                    </li>
                    <li>
                        Only use authorized transportation services.
                    </li>
                    <li>
                        Protect yourself from the sun and bugs with sunblock and bug repellant
                    </li>
                </ul>
                <p style="margin-top:12px;">
                <span >Travel-Pro Tips!</span>
                <ul style="margin:0px;">
                    <li>
                        Language: {{ $destination ? $destination->language_description : '' }}
                    </li>
                    <li>
                        Currency: {{ $destination ? $destination->currency_description : '' }}
                    </li>
                    <li>
                        Check your phone carrier international plan options
                    </li>
                </ul>
            </p>
            </div>
            <div class="footer-bottom">
                <img src="{{ asset('img/logo-white.png') }}" alt="Barefoot Bridal">
            </div>
        </div>
        </div>
        <div class="container">
            <div class="checklist-left-strip">
                <div class="vertical-wrapper">
                    <div class="vertical-text">checklist</div>
                </div>
            </div>
            <div class="checklist-checkbox">
                <h2 style="margin:0; padding:0; font-family:'Ivypresto Display', serif; font-weight:300; margin-left:20px; margin-bottom:3px; font-style:none; font-size: 30px;">ESSENTIALS</h2>
                <ul>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Passport & other ID’s</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Boarding pass, travel documents & pen</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Insurance brochure & contact information</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Credit cards, wallet, cash</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Glasses, contact lenses, solution & case</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Tumbler & straw</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Sleeping mask, neck pillow, travel blanket</span></label></li>
                </ul>
                <h2 style="margin:0; padding:0; margin-left:20px; font-family:'Ivypresto Display', serif; font-weight:300; margin-bottom:3px; font-style:none; font-size: 30px;">TOILETRIES</h2>
                <ul>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Comb & Hairbrush</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Dental care products</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Hair Styling Products</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Makeup, remover, chapstick w/ sunscreen</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Perfume/Cologne</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Body Care – deodorant, lotion, shampoo</span></label></li>
                </ul>
                <h2 style="margin:0; padding:0; margin-left:20px; font-family:'Ivypresto Display', serif; font-weight:300; margin-bottom:3px; font-style:none; font-size: 30px;">HEALTH</h2>
                <ul>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Prescription medication</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Pain & fever medicine</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Gastrointestinal medicine</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Feminine products</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Contraceptives</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Sunblock, bug repellent, aloe vera</span></label></li>
                </ul>
                <h2 style="margin:0; padding:0; margin-left:20px; font-family:'Ivypresto Display', serif; font-weight:300; margin-bottom:3px; font-style:none; font-size: 30px;">CLOTHES</h2>
                <ul>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Undergarments, socks, strapless bra</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Wedding & events attire</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Shoes, sandals, flip flops</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Tops and shirts</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Long pants, shorts, skirts</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Swimwear & coverups</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Sunglasses</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Sleepwear</span></label></li>
                    <li><label class="check-item"><input type="checkbox"><span class="check-text" style="font-family: 'Poppins'; font-weight:300;">Jewelry & accessories</span></label></li>
                </ul>
            </div>
        </div>
        <div class="container">
             <div class="bon-voyage">
               <img src="{{ ($destination && $destination->image) ? Storage::url($destination->image->path) : '' }}" alt="">
            </div>
            <div class="bon-voyage-content">
                <h1 class="is-uppercase" style="margin-top:-15px; font-size:100px; font-weight bold; font-family: 'Adore Calligraphy', 'brittany_signatureregular', 'Dancing Script', cursive;">Bon Voyage!</h1>
                <p style="font-family: 'Poppins'; font-weight:400;">We hope you have an amazing trip and look forward to <br> helping you book your next vacation!</p>
            </div>
             <div class="footer-bottom">
                <img src="{{ asset('img/logo-white.png') }}" alt="Barefoot Bridal">
            </div>
        </div> 
</body>

</html>
