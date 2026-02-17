@extends('web.mail.layouts.email')

@section('title', 'FLIGHT MANIFEST')

@section('content')
<p style="margin-bottom:8px">
    @if ($bookingClient->booking->group)
        <span style="font-weight:600; color:#3C3B3B">Group:</span> {{ $bookingClient->booking->group->full_name }}
    @else
        <span style="font-weight:600; color:#3C3B3B">Individual Booking:</span> {{ $bookingClient->booking->full_name }}
    @endif
</p>

<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Client:</span> {{ $bookingClient->name }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Email:</span> {{ $bookingClient->client->email }}</p>

<p style="font-weight:600; color:#3C3B3B; margin-bottom:8px">Flight Log:</p>

@foreach ($processedGuests as $processedGuest)
    @php
        $guest = $processedGuest['guest'];
        $guest_check_in = Carbon\Carbon::parse($processedGuest['check_in'], 'UTC');
        $guest_check_out = Carbon\Carbon::parse($processedGuest['check_out'], 'UTC');
    @endphp

    <div style="margin-bottom:8px">
        <p style="font-weight:600; margin-bottom:8px">{{ $guest->name }}</p>

        @if ($guest->flight_manifest)
            @php
                if (isset($guest->flight_manifest->arrival_datetime)) {
                    $arrival_datetime = Carbon\Carbon::parse($guest->flight_manifest->arrival_datetime, 'UTC')->setTimezone($guest->flight_manifest->arrivalAirport->timezone)->format('m-d-Y H:i');
                } else {
                    $arrival_datetime = '';
                }

                if (isset($guest->flight_manifest->departure_datetime)) {
                    $departure_datetime = Carbon\Carbon::parse($guest->flight_manifest->departure_datetime, 'UTC')->setTimezone($guest->flight_manifest->departureAirport->timezone)->format('m-d-Y H:i');
                } else {
                    $departure_datetime = '';
                }
            @endphp

            <ul>
                <li><span style="font-weight:600; color:#3C3B3B">Arrival Date & Time:</span> {{ $arrival_datetime }}</li>
            </ul>

            @if($guest->flight_manifest->arrival_datetime && $guest->flight_manifest->arrival_manual)
                <div style="background-color: rgba(199, 151, 156, 0.1);border-left: 4px solid #C7979C;padding: 12px 16px;margin: 12px 0;">
                    <p style="margin:0;">
                        <span style="font-weight:600; color:#3C3B3B;">NOTE:</span>
                        The arrival date &amp; time was added manually. This flight was not found by the system.
                    </p>
                </div>
            @endif

            @php
                $guest_arrival_date = $guest->flight_manifest->arrival_datetime ? Carbon\Carbon::parse($guest->flight_manifest->arrival_datetime, 'UTC')->setTimezone($guest->flight_manifest->arrivalAirport->timezone) : null;
            @endphp

            @if (isset($guest->flight_manifest->arrival_datetime) && !$guest_check_in->isSameDay($guest_arrival_date))
                <div style="background-color: rgba(199, 151, 156, 0.1);border-left: 4px solid #C7979C;padding: 12px 16px;margin: 12px 0;">
                    <p style="margin-bottom:8px">Check In date is {{ $guest_check_in->format('m-d-Y') }} which is different from Arrival date.</p>
                    <p><span style="font-weight:600; color:#3C3B3B">Date Mismatched Alternate:</span> {{ $guest->flight_manifest->arrival_date_mismatch_reason }}</p>
                </div>
            @endif
            <ul>
              <li><span style="font-weight:600; color:#3C3B3B">Arrival Airline:</span> {{ isset($guest->flight_manifest->arrival_airline) ?  $guest->flight_manifest->getAirlineName($guest->flight_manifest->arrival_airline) : '' }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Arrival Flight Number:</span> {{ $guest->flight_manifest->arrival_number }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Arrival Airport:</span> {{ isset($guest->flight_manifest->arrivalAirport) ? $guest->flight_manifest->arrivalAirport->airport_code : '' }}</li>
            </ul>

            <ul>
              <li><span style="font-weight:600; color:#3C3B3B">Departure Date & Time:</span> {{ $departure_datetime }}</li>
            </ul>

            @if($guest->flight_manifest->departure_datetime && $guest->flight_manifest->departure_manual)
                <div style="background-color: rgba(199, 151, 156, 0.1);border-left: 4px solid #C7979C;padding: 12px 16px;margin: 12px 0;">
                    <p><span style="font-weight:600; color:#3C3B3B">NOTE:</span> The departure date & time was added manually. This flight was not found by the system.</p>
                </div>
            @endif

            @php
                $guest_departure_date = $guest->flight_manifest->departure_datetime ? Carbon\Carbon::parse($guest->flight_manifest->departure_datetime, 'UTC')->setTimezone($guest->flight_manifest->departureAirport->timezone) : null;
            @endphp

            @if (isset($guest->flight_manifest->departure_datetime) && !$guest_check_out->isSameDay($guest_departure_date))
                <div style="background-color: rgba(199, 151, 156, 0.1);border-left: 4px solid #C7979C;padding: 12px 16px;margin: 12px 0;">
                    <p style="margin-bottom:8px">Check Out date is {{ $guest_check_out->format('m-d-Y') }} which is different from Departure date.</p>
                    <p><span style="font-weight:600; color:#3C3B3B">Date Mismatched Alternate:</span> {{ $guest->flight_manifest->departure_date_mismatch_reason }}</p>
                </div>
            @endif
            <ul>
              <li><span style="font-weight:600; color:#3C3B3B">Departure Airline:</span> {{ isset($guest->flight_manifest->departure_airline) ? $guest->flight_manifest->getAirlineName($guest->flight_manifest->departure_airline) : '' }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Departure Flight Number:</span> {{ $guest->flight_manifest->departure_number }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Departure Airport:</span> {{ isset($guest->flight_manifest->departureAirport) ? $guest->flight_manifest->departureAirport->airport_code : '' }}</li>
          </ul>
        @else
            <p>Flight Information was not provided for this guest.</p>
        @endif
    </div>
@endforeach

<div style="text-align:center; margin-top:16px">
    @if ($bookingClient->booking->group)
        <a href="{{ config('app.dashboard_url') . '/groups/' . $bookingClient->booking->group->id . '/bookings/' . $bookingClient->booking->id }}" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">Click here to view this booking in the dashboard</a>
    @else
        <a href="{{ config('app.dashboard_url') . '/individual-bookings/' . $bookingClient->booking->id }}" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">Click here to view this booking in the dashboard</a>
    @endif
</div>
@endsection
