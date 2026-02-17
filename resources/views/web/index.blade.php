@extends('web.layouts.layout')

@section('title')
Destination Wedding - Travel Specialists | Barefoot Bridal
@endsection

@section('meta')
<meta name="description" content="Barefoot Bridal has successfully planned travel and weddings for hundreds of brides and their guests to multiple locations.
    You can rest assured that you're in the best, most knowledgeable and experienced hands.">
@endsection

@section('content')
<article id="Home">
    <div id="hero" class="hero is-fullheight has-glass">
        <div class="hero-body">
            <div class="container is-tight has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Destination wedding and travel specialists for the caribbean and mexico</h1>
                <a class="button is-medium is-fat is-rounded is-outlined is-white has-text-weight-normal" href="/contact">LET'S GO BAREFOOT!</a>
            </div>
        </div>
        <div class="hero-foot">
            <div class="container has-text-centered">
                <a href="#intro" class="scroll has-text-white is-size-3" data-scroll data-scroll-offset="100">
                    <span class="scroll-icon"><i class="fas fa-chevron-down"></i></span>
                    <span class="scroll-text is-size-6">SCROLL DOWN</span>
                </a>
            </div>
        </div>
        <img src="{{ asset('img/index-hero.jpg') }}" class="parallax-background">
    </div>
    <div id="intro" class="section is-medium">
        <div class="container is-tight has-text-centered">
            <h2>
                <img src="{{ asset('img/slogan.png') }}" alt="Barefoot Bridal">
            </h2>
            <p>
                Barefoot Bridal has a wealth of knowledge that only brides who have actually planned and
                had their own destination weddings would know and we want to share that knowledge with you!
            </p>
            <br>
            <p>
                We have successfully planned travel and weddings for hundreds of brides and their guests to multiple locations.
                You can rest assured that you're in the best, most knowledgeable and experienced hands.
            </p>
            <br>
            <a class="button is-medium is-fat is-rounded is-outlined is-black has-text-weight-normal" href="/contact">LET'S GO BAREFOOT!</a>
        </div>
    </div>
    <div id="why-go-barefoot">
        <div class="hero is-large has-glass">
            <div class="hero-body">
                <div class="container is-tight has-text-centered">
                    <h2 class="title is-uppercase">Why go barefoot?</h2>
                </div>
            </div>
            <img src="{{ asset('img/index-1.jpg') }}" class="parallax-background">
        </div>
        <div class="section is-medium">
            <div class="container">
                <div class="columns is-variable is-4">
                    <div class="column is-4">
                        <h3 class="subtitle is-4 is-spaced has-text-weight-bold is-uppercase is-uppercase">Personalized service</h3>
                        <p class="is-size-4 has-text-weight-normal is-uppercase">
                            We don't want to just book your wedding travel, we want to get to know you and your vision,
                            so that we can help you plan the b provides one-on-one service catered to your own, personal needs.
                        </p>
                    </div>
                    <div class="column is-4">
                        <h3 class="subtitle is-4 is-spaced has-text-weight-bold is-uppercase">We understand</h3>
                        <p class="is-size-4 has-text-weight-normal is-uppercase">
                            Booking travel is more than just the cost, it's the experience, too.
                            Sometimes it's a destination wedding or a honeymoon. Even if it's for no particular reason,
                            every trip is equally important to us because we know how important it is to you.
                        </p>
                    </div>
                    <div class="column is-4">
                        <h3 class="subtitle is-4 is-spaced has-text-weight-bold is-uppercase">No fees</h3>
                        <p class="is-size-4 has-text-weight-normal is-uppercase">
                            Some agencies charge fees for their time. We never consider working with anyone time wasted,
                            even if they don't book with us. Each person we work with, we consider it a learning experience.
                            We want each bride to choose the agency she wants to work with and not be tied down with fees.
                        </p>
                    </div>
                </div>
                <br>
                <div class="has-text-centered">
                    <a class="button is-medium is-fat is-rounded is-outlined is-black has-text-weight-normal" href="/contact">LET'S GO BAREFOOT!</a>
                </div>
            </div>
        </div>
    </div>
    <div id="how-it-works">
        <div class="hero is-large has-glass">
            <div class="hero-body">
                <div class="container is-tight has-text-centered">
                    <h2 class="title is-uppercase">How it works</h2>
                </div>
            </div>
            <img src="{{ asset('img/index-2.jpg') }}" class="parallax-background">
        </div>
        <div class="section is-medium">
            <div class="container">
                <div class="columns">
                    <div class="column is-3">
                        <h3 class="subtitle is-4 has-text-weight-normal is-uppercase">Step one</h3>
                    </div>
                    <div class="column">
                        <p>
                            Click the button below and fill out the contact form for a free consultation.
                            One of our agents will contact you within one business day.
                        </p>
                    </div>
                </div>
                <hr>
                <div class="columns">
                    <div class="column is-3">
                        <h3 class="subtitle is-4 has-text-weight-normal is-uppercase">Step two</h3>
                    </div>
                    <div class="column">
                        <p>
                            Tell us what you're looking for, where you want to go, and how many guests will be attending.
                            If you're not sure, we can help! From there we'll figure out all the associated costs,
                            promotions, and handle all the contracting for you.
                        </p>
                    </div>
                </div>
                <hr>
                <div class="columns">
                    <div class="column is-3">
                        <h3 class="subtitle is-4 has-text-weight-normal is-uppercase">Step three</h3>
                    </div>
                    <div class="column">
                        <p>
                            RELAX and let us book the destination wedding of your dreams!
                        </p>
                    </div>
                </div>
                <br>
                <div class="has-text-centered">
                    <a class="button is-medium is-fat is-rounded is-outlined is-black has-text-weight-normal" href="/contact">LET'S GO BAREFOOT!</a>
                </div>
            </div>
        </div>
    </div>
</article>
@endsection
