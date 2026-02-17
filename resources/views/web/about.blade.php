@extends('web.layouts.layout')

@section('title')
Barefoot Bridal is the DESTINATION WEDDING SPECIALIST for you | Barefoot Bridal
@endsection

@section('content')
<article id="about">
    <div class="hero is-large has-glass">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Why go barefoot</h1>
            </div>
        </div>
        <img src="{{ asset('img/about-hero.jpg') }}" class="parallax-background" data-parallax-position="0.9">
    </div>
    <div class="section is-medium">
        <div class="container">
            <h2 class="subtitle is-2 is-size-3-mobile is-uppercase has-text-secondary has-text-weight-normal has-text-centered">Barefoot Bridal is the destination wedding specialist for you.</h2>
            <br>
            <div class="mixed-columns is-even">
                <div class="columns is-variable is-5">
                    <div class="column is-6">
                        <h3 class="subtitle is-5 has-text-weight-bold">Customer Service</h3>
                        <p>
                            We know first-hand how stressful planning a destination wedding can be and how much worse it is with the wrong travel agent.
                            That's why customer service comes first for us.
                            We respond to all emails and calls promptly and professionally.
                        </p>
                    </div>
                    <div class="column is-6">
                        <img src="{{ asset('img/about-1.jfif') }}">
                    </div>
                </div>
                <div class="columns is-variable is-5">
                    <div class="column is-6">
                        <h3 class="subtitle is-5 has-text-weight-bold">Personalized Service</h3>
                        <p>
                            We want to get to know you and learn about your vision for your wedding and travel,
                            so we can help you choose the best options for your wedding weekend.
                            Each bride works directly with one travel specialist,
                            while our team works as a whole to make sure everything is taken care of as quickly and correctly as possible.
                        </p>
                    </div>
                    <div class="column is-6">
                        <img src="{{ asset('img/about-2.jpg') }}">
                    </div>
                </div>
                <div class="columns is-variable is-5">
                    <div class="column is-6">
                        <h3 class="subtitle is-5 has-text-weight-bold">Complimentary Add-On Services</h3>
                        <p>
                            We create a customized page for you on our site that includes all of the information your guests will need
                            for their vacation and allow them to book their trip securely and with ease.
                            Your guests can choose from different payment plans, interest-free.
                            We create a closed or secret Facebook Group for you and your guests to keep your guests informed and excited for their trip.
                            We can send an "email blast" to your guest list with all the information they need to facilitate the booking process.
                            All this, so that your guests have absolutely no difficulties booking!
                        </p>
                    </div>
                    <div class="column is-6">
                        <img src="{{ asset('img/about-3.jpeg') }}">
                    </div>
                </div>
                <div class="columns is-variable is-5">
                    <div class="column is-6">
                        <h3 class="subtitle is-5 has-text-weight-bold">No Agency Fees</h3>
                        <p>
                            Some agencies require consultation and booking fees to make sure that they don't work for free.
                            We never consider working with anyone time wasted. Even if you don't book with us,
                            we appreciate your consideration of our company enough to contact us to begin with. We qualify all of our clients,
                            provide hours of research, and provide our experience and insight, all for FREE! In addition,
                            we handle all your guests' questions and requests, including air and excursions.
                        </p>
                    </div>
                    <div class="column is-6">
                        <img src="{{ asset('img/about-4.jpeg') }}">
                    </div>
                </div>
                <div class="columns is-variable is-5">
                    <div class="column is-6">
                        <h3 class="subtitle is-5 has-text-weight-bold">Best Prices and Price Matches</h3>
                        <p>
                            We have access to over 50 suppliers and wholesalers so that we can get you the best prices available.
                            We use whichever supplier gives our clients the best price and service.
                            If you have received a better price from another travel agent, let us know so we can try to match it.
                            Sometimes, we are able to beat prices you can find online.
                        </p>
                    </div>
                    <div class="column is-6">
                        <img src="{{ asset('img/about-5.jpeg') }}">
                    </div>
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
