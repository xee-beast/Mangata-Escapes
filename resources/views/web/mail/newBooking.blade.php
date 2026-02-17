@extends('web.mail.layouts.email')

@section('title', 'BOOKING')

@section('content')
<p style="margin-bottom:8px">
    @if ($booking->group)
        <span style="font-weight:600; color:#3C3B3B">Group:</span> {{ $booking->group->full_name }}
    @else
        <span style="font-weight:600; color:#3C3B3B">Individual Booking:</span> {{ $booking->full_name }}
    @endif
</p>

<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Client:</span> {{ $booking->clients->first()->name }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Email:</span> {{ $booking->clients->first()->client->email }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Phone Number:</span> {{ $booking->clients->first()->telephone }}</p>
@if ($booking->group && !$booking->group->is_fit)
    <p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Address:</span> {{ $booking->clients->first()->card->address->full_address }}</p>
    <p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Payment:</span> {{ ucfirst($booking->clients->first()->card->type) }} ending in {{ $booking->clients->first()->card->last_digits }}</p>
@endif

@if ($booking->clients->count() > 1)
    <p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Separate Invoice Clients</p>
    <ul>
        @foreach ($booking->clients->skip(1) as $bookingClient)
            <li><span style="font-weight:600;">{{ $bookingClient->name }}</span>: {{ $bookingClient->client->email }}</li>
        @endforeach
    </ul>
@endif

<p style="font-weight:600; color:#3C3B3B">Room Details</p>
@if ($booking->group)
    <ul>
        @foreach ($booking->roomBlocks as $roomBlock)
            <li><span style="font-weight:600; color:#3C3B3B">Hotel:</span> {{ $roomBlock->hotel_block->hotel->name }}</li>
            <li><span style="font-weight:600; color:#3C3B3B">Room:</span> {{ $roomBlock->room->name }}</li>
            <li><span style="font-weight:600; color:#3C3B3B">Bedding Request:</span> {{ $roomBlock->pivot->bed }} (I understand that bedding is a special request and is subject to availability).</li>
            <li><span style="font-weight:600; color:#3C3B3B">Check In Date:</span> {{ $roomBlock->pivot->check_in->format('m/d/Y') }}</li>
            <li><span style="font-weight:600; color:#3C3B3B">Check Out Date:</span> {{ $roomBlock->pivot->check_out->format('m/d/Y') }}</li>
        @endforeach
    </ul>
@else
    <ul>
        <li><span style="font-weight:600; color:#3C3B3B">Hotel Assistance:</span> {{ $booking->hotel_assistance ? 'Yes' : 'No' }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Hotel Preferences:</span> {{ $booking->hotel_preferences }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Hotel Name:</span> {{ $booking->hotel_name }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Room Category:</span> {{ $booking->room_category ? 'Specified' : 'Unspecified' }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Room Category Name:</span> {{ $booking->room_category_name }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Check In Date:</span> {{ $booking->check_in->format('m/d/Y') }}</li>
        <li><span style="font-weight:600; color:#3C3B3B">Check Out Date:</span> {{ $booking->check_out->format('m/d/Y') }}</li>
    </ul>
@endif

<p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Special Requests</p>
<p style="margin-bottom:16px;">{{ $booking->special_requests }}</p>

@if (!$booking->group)
    <p style="margin-bottom:16px;"><span style="font-weight:600; color:#3C3B3B">Budget:</span> ${{ $booking->budget }}</p>
@endif

<p style="font-weight:600; color:#3C3B3B">Guests</p>
<ul>
    @foreach ($booking->guests->sortBy('id') as $index => $guest)
        <li>
            <span style="font-weight:600;">Guest {{ $index + 1 }}:</span>
            <ul>
                <li><span style="font-weight:600; color:#3C3B3B">Name:</span> {{ $guest->name }}</li>
                <li><span style="font-weight:600; color:#3C3B3B">Birth Date:</span> {{ $guest->birth_date->format('m/d/Y') }}</li>
                <li><span style="font-weight:600; color:#3C3B3B">Gender:</span> {{ $guest->gender == 'M' ? 'Male' : 'Female' }}</li>
                <li><span style="font-weight:600; color:#3C3B3B">Invoiced To:</span> {{ $booking->clients->firstWhere('id', $guest->booking_client_id)->name }}</li>
            </ul>
        </li>
    @endforeach
</ul>

@if ($booking->group && $booking->group->transportation)
    <p style="margin-bottom:8px">
        <span style="font-weight:600; color:#3C3B3B">Do you wish to include airport transfers?</span>
        @if($booking->guests->first()->transportation)
            Yes, I want airport transfers.
        @else
            No, I don't want airport transfers.
        @endif
    </p>
    @if(!$booking->guests->first()->transportation)
        <p style="margin-bottom:8px">
            I understand that by declining airport transfers, Barefoot Bridal will not coordinate our pick up from the airport upon our arrival and transfer to the hotel or back to the airport for our flight home. I understand that I will need to coordinate the airport transfers on my own.
        </p>
    @endif
@endif
@if (!$booking->group)
    <p style="margin-bottom:8px">
        <span style="font-weight:600; color:#3C3B3B">Would you like us to quote flights for you?</span>
        @if($booking->guests->first()->transportation)
            Yes, I want flights quoted for me.
            <ul>
              <li><span style="font-weight:600; color:#3C3B3B">Departure Gateway:</span> {{ $booking->departure_gateway }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Flight Preferences:</span> {{ $booking->flight_preferences }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Airline Membership Number:</span> {{ $booking->airline_membership_number }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Known Traveler Number (KTN):</span> {{ $booking->known_traveler_number }}</li>
              <li><span style="font-weight:600; color:#3C3B3B">Message:</span> {{ $booking->flight_message }}</li>
          </ul>
        @else
            No, I don't want flight quotations.
        @endif
    </p>
    @if(!$booking->guests->first()->transportation)
        <p style="margin-bottom:16px;">
            I understand that by declining flight quotations, Barefoot Bridal will not coordinate our pick up from the airport upon our arrival and transfer to the hotel or back to the airport for our flight home. I understand that I will need to coordinate the airport transfers on my own.
        </p>
    @endif
@endif

<p style="margin-bottom:8px">
    <span style="font-weight:600; color:#3C3B3B">Do you wish to purchase travel insurance?</span>
    @if($booking->clients->first()->insurance)
        Yes, I would like to purchase travel insurance and understand that once purchased <span style="font-weight:600;">the cost of travel insurance is non-refundable</span>.
    @else
        No, I am <span style="font-weight:600;">not interested in purchasing travel insurance</span> and acknowledge that I have been offered but choose to decline this coverage.<br />
        I understand the risks in not purchasing travel protection.<br />
        I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after {{ $booking->group ? $booking->group->cancellation_date->format('m/d/Y') : 'the cancellation date' }}.<br />
        I understand that after {{ $booking->group ? $booking->group->cancellation_date->format('m/d/Y') : 'the cancellation date' }}, I will not be able to cancel for a refund even if one or more of the guests on this reservation is/are unable to attend for any reason, including but not limited to:<br />
        <ul>
            <li>Illness</li>
            <li>Testing positive for COVID</li>
            <li>Pregnancy</li>
            <li>Inability to have time off work approved</li>
            <li>Military service</li>
            <li>Weather-related issues including hurricanes, snow storms, or natural disasters that prevent you from being able to travel, or any other reason.</li>
            <li>Or any other reason.</li>
        </ul>
        I understand that the only way to protect my reservation is by purchasing travel insurance.<br />
        I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to {{ $booking->group ? $booking->group->cancellation_date->format('m/d/Y') : 'the cancellation date' }}, that I will not receive a refund and I agree not to dispute my charges in this event.<br />
    @endif
</p>
<p style="margin-bottom:16px;"><span style="font-weight:600; color:#3C3B3B">Travel Insurance Signature:</span> {{ $booking->clients->first()->name }}</p>
@if ($booking->group && !$booking->group->is_fit)
    <p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Payment Amount:</span> ${{ $booking->clients->first()->payments->first()->amount }}</p>

    <p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Payment Authorization:</p>
    <p style="margin-bottom:8px">I understand that payments will be automatically charged to the card I use for my deposit according to the payment structure of
    @php
        $dueDates = $booking->group->due_dates;

        $paymentTexts = $dueDates->map(function($dueDate) {
            if ($dueDate->type == 'nights') {
                return intval($dueDate->amount) . ' night(s) due on ' . $dueDate->date->format('m/d/Y');
            } else if ($dueDate->type == 'percentage') {
                return intval($dueDate->amount) . '% due on ' . $dueDate->date->format('m/d/Y');
            } else if ($dueDate->type == 'price') {
                return '$' . $dueDate->amount . ' due on ' . $dueDate->date->format('m/d/Y');
            }
        })->filter()->toArray();

        $balanceText = '';
        if ($booking->group->balance_due_date) {
            $balanceText = 'balance due on ' . $booking->group->balance_due_date->format('m/d/Y');
        }

        $parts = [];
        if (!empty($paymentTexts)) {
            $parts[] = implode(', ', $paymentTexts);
        }
        if ($balanceText) {
            $parts[] = $balanceText;
        }

        echo implode(' and ', $parts);
    @endphp
    unless I contact Barefoot Bridal in writing at least 3 business days prior via email at <a target="_blank" href="mailto:{{ config('emails.groups') }}">{{ config('emails.groups') }}</a> or text 866-822-7336.</p>
    <p style="margin-bottom:16px;">
        Notwithstanding anything contained in my Cardholder Agreement with the provider that is to the contrary,
        written notice of rejection or cancellation of these arrangements must be received in writing within the time limits stated in the Terms & Conditions.
        If not received, no charge-backs or cancellation will then be accepted.
        My signature on this charge confirmation form is an acknowledgement that I have received and read the Terms & Conditions and that I understand the Cancellation Policy,
        which details this company's policies on payments, cancellations and refunds for the travel arrangements I have made.
        You should review this document thoroughly before finalizing any travel arrangements. Barefoot Bridal cancellation fees are in addition to any supplier cancellation fees.
        I am aware of all cancellation policies and agree not to dispute or attempt to charge back any of the above signed for and acknowledged charges.
    </p>

    <p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Terms & Conditions</p>
    @if($booking->group->terms_and_conditions)
        {!! $booking->group->terms_and_conditions !!}
    @else
        @include('pdf.static.termsConditions', ['group' => $booking->group])
    @endif
    <p style="margin-bottom:16px;"><span style="font-weight:600; color:#3C3B3B">Terms & Conditions Signature:</span> {{ $booking->clients->first()->name }}</p>
@endif

<div style="text-align:center; margin-top:16px;">
    @if ($booking->group)
        <a href="{{ config('app.dashboard_url') . '/groups/' . $booking->group->id . '/bookings/' . $booking->id }}" target="_blank" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">
            Click here to view this booking in the dashboard
        </a>
    @else
        <a href="{{ config('app.dashboard_url') . '/individual-bookings/' . $booking->id }}" target="_blank" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">
            Click here to view this booking in the dashboard
        </a>
    @endif
</div>
@endsection
