@extends('web.mail.layouts.email')

@section('title', 'CARD UPDATE')

@section('content')
<p style="margin-bottom:16px;">
    @if ($clientBooking->booking->group)
        <span style="font-weight:600; color:#3C3B3B">Group:</span> {{ $clientBooking->booking->group->full_name }}
    @else
        <span style="font-weight:600; color:#3C3B3B">Individual Booking:</span> {{ $clientBooking->booking->full_name }}
    @endif
</p>

<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Client:</span> {{ $clientBooking->name }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Email:</span> {{ $clientBooking->client->email }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Credit Card:</span> {{ ucfirst($clientBooking->card->type) }} ending in {{ $clientBooking->card->last_digits }}</p>
<p style="margin-bottom:8px"><span style="font-weight:600; color:#3C3B3B">Address:</span> {{ $clientBooking->card->address->full_address }}</p>
@if ($signedInsurance)
    @php
        if ($clientBooking->booking->group) {
           $cancellationDate = $clientBooking->booking->group->cancellation_date->format('m/d/Y');
        } else {
           $cancellationDate = $clientBooking->booking->cancellation_date ? $clientBooking->booking->cancellation_date->format('m/d/Y') : 'the cancellation date';
        }
    @endphp
    <p style="margin-top:16px; margin-bottom:16px;">
        <span style="font-weight:600; color:#3C3B3B">Do you wish to purchase travel insurance?</span>
        @if($clientBooking->insurance)
            Yes, I would like to purchase travel insurance and understand that once purchased <span style="font-weight:600;">the cost of travel insurance is non-refundable</span>.
        @else
            No, I am <span style="font-weight:600;">not interested in purchasing travel insurance</span> and acknowledge that I have been offered but choose to decline this coverage. I understand the risks in not purchasing travel protection.<br />
            I understand that by declining travel insurance, that I will not be reimbursed for cancelling my reservation after {{ $cancellationDate }}.<br />
            I understand that after {{ $cancellationDate }}, I will not be able to cancel for a refund even if one or more of the guests on this reservation is/are unable to attend for any reason, including but not limited to:<br />
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
            I understand that if I did not purchase travel insurance and wish to cancel, reduce the guest count, downgrade my room category, or make any other changes to the reservation that would have resulted in a refund prior to {{ $cancellationDate }}, that I will not receive a refund and I agree not to dispute my charges in this event.<br />
        @endif
    </p>
    <p style="margin-bottom:16px;"><span style="font-weight:600; color:#3C3B3B">Travel Insurance Signature:</span> {{ $clientBooking->name }}</p>
@endif

<p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Payment Authorization:</p>
<p style="margin-bottom:8px">I understand that payments will be automatically charged to the card I use for my deposit according to the payment structure of
@php
    if ($clientBooking->booking->group) {
        $dueDates = $clientBooking->booking->group->due_dates;
        $balanceDueDate = $clientBooking->booking->group->balance_due_date;
    } else {
        $dueDates = $clientBooking->booking->bookingDueDates;
        $balanceDueDate = $clientBooking->booking->balance_due_date;
    }

    $paymentTexts = $dueDates->map(function($dueDate) {
        if ($dueDate->type == 'nights') {
            return intval($dueDate->amount) . ' night(s) due on ' . $dueDate->date->format('m/d/Y');
        } else if ($dueDate->type == 'percentage') {
            return intval($dueDate->amount) . '% due on ' . $dueDate->date->format('m/d/Y');
        } else if ($dueDate->type == 'price') {
            return '$' . $dueDate->amount . ' due on ' . $dueDate->date->format('m/d/Y');
        }
    })->filter()->toArray();

    $balanceText = 'my booking';
    if ($balanceDueDate) {
        $balanceText = 'balance due on ' . $balanceDueDate->format('m/d/Y');
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
<p style="margin-bottom:8px">
    Notwithstanding anything contained in my Cardholder Agreement with the provider that is to the contrary,
    written notice of rejection or cancellation of these arrangements must be received in writing within the time limits stated in the Terms & Conditions.
    If not received, no charge-backs or cancellation will then be accepted. My signature on this charge confirmation form is an acknowledgement that I have received and read the Terms & Conditions and that I understand the Cancellation Policy,
    which details this company's policies on payments, cancellations and refunds for the travel arrangements I have made.
    You should review this document thoroughly before finalizing any travel arrangements. Barefoot Bridal cancellation fees are in addition to any supplier cancellation fees. I am aware of all cancellation policies and agree not to dispute or attempt to charge back any of the above signed for and acknowledged charges.
</p>

<p style="font-weight:600; color:#3C3B3B margin-bottom:8px">Terms & Conditions:</p>
@if ($clientBooking->booking->group)
    @if ($clientBooking->booking->group->terms_and_conditions)
        {!! $clientBooking->booking->group->terms_and_conditions !!}
    @else
        @if ($clientBooking->booking->group->is_fit)
            @include('pdf.static.fitTermsConditions', ['group' => $clientBooking->booking->group])
        @else
            @include('pdf.static.termsConditions', ['group' => $clientBooking->booking->group])
        @endif
    @endif
@else
    @if ($clientBooking->booking->terms_and_conditions)
        {!! $clientBooking->booking->terms_and_conditions !!}
    @else
        @include('pdf.static.individualBookingTermsConditions', ['cancellation_date' => $clientBooking->booking->cancellation_date ? $clientBooking->booking->cancellation_date->format('F d, Y') : null])
    @endif
@endif
<p style="margin-bottom:16px;"><span style="font-weight:600; color:#3C3B3B">Terms & Conditions Signature:</span> {{ $clientBooking->name }}</p>

<div style="text-align:center;">
    @if ($clientBooking->booking->group)
        <a href="{{ config('app.dashboard_url') . '/groups/' . $clientBooking->booking->group->id . '/bookings/' . $clientBooking->booking->id }}" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">Click here to view this booking in the dashboard</a>
    @else
        <a href="{{ config('app.dashboard_url') . '/individual-bookings/' . $clientBooking->booking->id }}" style="display:inline-block;max-width:400px;width:100%;background-color:#3C3B3B;font-family:'Poppins', sans-serif;font-weight:bold;font-size:18px;line-height:1.5;letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;color:#F7F5F4;text-align:center;vertical-align:middle;padding:10px 40px;box-sizing:border-box;">Click here to view this booking in the dashboard</a>
    @endif
</div>
@endsection
