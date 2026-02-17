@extends('web.layouts.layout')

@section('title')
    Individual Bookings | {{ config('app.name') }}
@endsection

@section('meta')
    <meta name="description" content="As a full service travel agency, we don't just book destination weddings. We book our bride's site visits and honeymoons, your annual family beach vacation, and that once in a lifetime trip you've been dreaming of but didn't know where to start." />
    <meta property="og:title" content="Individual Reservation Form" />
    <meta property="og:url" content="{{ route('individual-bookings.page') }}" />
    <meta property="og:image" content="{{ asset('img/individual-bookings.jpg') }}" />
    <meta property="og:description" content="As a full service travel agency, we don't just book destination weddings. We book our bride's site visits and honeymoons, your annual family beach vacation, and that once in a lifetime trip you've been dreaming of but didn't know where to start." />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('styles')
@endsection

@section('scripts')
    @if (app()->environment('local'))
        <script src="{{ mix('js/bookings/app.js') }}" defer></script>
    @else
        <script src="{{ asset('js/bookings/app.js') }}" defer></script>
    @endif
@endsection

@section('menu')
@endsection

@section('content')
    <article id="individual-bookings" style="!padding:None">
         <div id="forms" class="individual-bookings__section">
            <div class="booking-section-ctn individual-bookings__grid">
                <div class="individual-bookings__image-column">
                    <div class="image-container individual-bookings__hero">
                        <div class="individual-bookings__hero-overlay"></div>
                        <img class="cover-image individual-bookings__hero-image" src="{{ asset('img/individual-bookings.png') }}" alt="Individual FIT Bookings"/>
                    </div>
                </div>
                <div class="individual-bookings__content-column">
                    <div class="content has-text-centered individual-bookings__intro">
                        <h1 class="title is-1 individual-bookings__title">Individual Bookings</h1>
                        <p class="individual-bookings__paragraph individual-bookings__paragraph--lead">
                            We're excited to help you book your reservation!
                        </p>
                        <p class="individual-bookings__paragraph">
                            Use the Quote button below to receive a quote for your reservation. If you've already received a quote, use the Accept Quote button to confirm your reservation.
                        </p>
                        <p class="individual-bookings__paragraph">
                            Contact us via email at <a class="individual-bookings__link" href="mailto:{{ config('emails.groups') }}">{{ config('emails.groups') }}</a> or call/text 866-822-7336 with any questions.
                        </p>
                    </div>
                    <div class="individual-bookings__forms" id="individual-booking-forms">
                        <div>
                            <booking-form custom-css-class="button is-rounded is-outlined is-black custom-booking-button-class" />
                        </div>
                        <div>
                            <payment-form groups-email='{{ config("emails.groups") }}' :countries='@json($countries)' />
                        </div>
                        <div>
                            <fit-quote-form groups-email='{{ config("emails.groups") }}' csrf-token="{{ csrf_token() }}" />
                        </div>
                        <div>
                            <update-card-form groups-email='{{ config("emails.groups") }}' :countries='@json($countries)' />
                        </div>
                        <div>
                            <invoice-form csrf-token="{{ csrf_token() }}" />
                        </div>
                        <div>
                            <flight-manifest-form groups-email='{{ config("emails.groups") }}' :airline='@json($airlines)' />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
@endsection

@section('scripts')
@endsection
