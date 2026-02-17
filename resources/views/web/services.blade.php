@extends('web.layouts.layout')

@section('title')
Planning a DESTINATION WEDDING? - Our Services | Barefoot Bridal
@endsection

@section('content')
<article id="services">
    <div class="hero is-large has-glass">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Services</h1>
            </div>
        </div>
        <img src="{{ asset('img/services-hero.jpg') }}" class="parallax-background">
    </div>
    <div class="section is-medium">
        <div class="container">
            <div class="columns">
                <div class="column is-6">
                    <h2 class="subtitle is-5 has-text-weight-bold is-uppercase">Full Travel Services</h2>
                    <p>
                        Barefoot Bridal can service any and all of your travel needs! While we cater to destination wedding couples,
                        we are able to assist with any type of travel: honeymoon, anniversary trip, family vacation or reunion, cruises,
                        weekend getaway, group travel, tour travel, or a no-reasons-needed vacation!
                        Our services are not limited to any location or type of travel - we can do it all!
                    </p>
                </div>
                <div class="column content">
                    <h3 class="subtitle has-text-weight-bold is-5">Planning a destination wedding? Here is what Barefoot Bridal provides:</h3>
                    <ul>
                        <li>Prompt and professional responses via e-mail, phone or texts</li>
                        <li>Competitive group rates & room blocks to ensure availability</li>
                        <li>Payment plans</li>
                        <li>Airfare, excursions and roundtrip airport transfers</li>
                        <li>Frequent updates to the bride of all contact with guests</li>
                        <li>A customizable personalized couples webpage</li>
                        <li>Ability for your guests to book online</li>
                        <li>A private Facebook group for your guests</li>
                        <li>A spreadsheet of all your guests travel details</li>
                        <li>Reminders sent to your guests for balance due dates</li>
                        <li>Assistance with honeymoon booking</li>
                        <li>
                            A passionate, caring Destination Wedding and Travel Specialist who has been through
                            the destination wedding process before and who understands what a Bride and Groom need!
                        </li>
                    </ul>
                </div>
            </div>
            <br>
            <div class="has-text-centered">
                <a class="button is-medium is-fat is-rounded is-outlined is-black has-text-weight-normal" href="/contact">LET'S GO BAREFOOT!</a>
            </div>
        </div>
    </div>
</article>
@endsection
