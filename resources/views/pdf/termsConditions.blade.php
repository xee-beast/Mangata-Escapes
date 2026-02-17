@extends('pdf.layout')

@section('title')
Terms & Conditions | Barefoot Bridal
@endsection

@section('styles')
<style>
    .tc-page .header {
        font-size: 72px;
        letter-spacing: 10px;
        padding-left: 7px
    }

    .tc-page .body {
        font-size: 14px;
        text-align: justify;
    }

    .tc-page .body > div {
        margin-bottom: 16px;
    }
</style>
@endsection

@section('body')
<div class="tc-page page">
    <div class="header">
        Terms & Conditions
    </div>

    <div class="body">
        @if (isset($group) && !empty($group))
            @if ($group->terms_and_conditions)
                {!! $group->terms_and_conditions !!}
            @else
                @if ($group->is_fit)
                    @include('pdf.static.fitTermsConditions', ['group' => $group])
                @else
                    @include('pdf.static.termsConditions', ['group' => $group])
                @endif
            @endif
        @elseif (isset($booking) && !empty($booking))
            @if ($booking->terms_and_conditions)
                {!! $booking->terms_and_conditions !!}
            @else
                @include('pdf.static.individualBookingTermsConditions', ['cancellation_date' => $booking->cancellation_date ? $booking->cancellation_date->format('F d, Y') : null])
            @endif
        @endif
    </div>
    <div class="footer">
        <img src="{{ ($path = realpath(public_path('img/footer-logo.png'))) ? 'file://'.$path : asset('img/footer-logo.png') }}">
        <div class="paging">Page 1 of 1</div>
    </div>
</div>
@endsection