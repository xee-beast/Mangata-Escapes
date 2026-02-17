@extends('web.layouts.layout')

@section('title')
Our Brides | Barefoot Bridal
@endsection

@section('content')
<article id="brides">
    <div class="hero is-large has-glass">
        <div class="hero-body">
            <div class="container has-text-centered">
                <h1 class="title is-size-2-mobile is-uppercase">Our Brides</h1>
            </div>
        </div>
        <img src="{{ asset('img/brides-hero.jpg') }}" class="parallax-background" data-parallax-position="0.6">
    </div>
    <div class="section is-medium">
        <div class="container">
            <h2 class="section-title subtitle is-2 is-size-3-mobile is-uppercase has-text-secondary has-text-weight-normal has-text-centered">Past Barefoot Brides</h2>
            <br>
            <p>
                When our Barefoot Brides become Barefoot Wives, it's so bittersweet. We know it's the end of our professional relationship,
                but we have forged lifelong friendships with so many of these brides.
                It's exciting to hear back from our amazing couples all the details of the happiest days of their lives.
                It's even better when we get to see it in their photographs and videos. We want to share these moments with you,
                with future Barefoot Brides, future destination wedding brides, and help you get inspired and excited for your own big day!
            </p>
            <br>
            <hr>
            <br>
            <div class="columns is-multiline">
                @foreach ($groups as $group)
                <div class="column is-half">
                    <p class="has-text-centered">
                        <b>{{ $group->name }}</b> got married on {{ $group->event_date->format('F j, Y') }} at {{ $group->hotels->first()->hotel->name }}, {{ $group->destination->name }}.
                        <br>
                        {{ $group->past_bride_message }}
                    </p>
                    <div class="glide-container">
                        <div class="glide">
                            <div class="glide__track" data-glide-el="track">
                                <ul class="glide__slides">
                                    @foreach ($group->images as $image)
                                    <li class="glide__slide">
                                        <img src="{{ Storage::url($image->path) }}" loading="lazy" class="glide-image">
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="glide__arrows" data-glide-el="controls">
                                <div class="glide__arrow glide__arrow--left" data-glide-dir="<"><i class="fas fa-angle-left"></i></div>
                                <div class="glide__arrow glide__arrow--right" data-glide-dir=">"><i class="fas fa-angle-right"></i></div>
                            </div>
                            <div class="glide__bullets" data-glide-el="controls[nav]">
                                @foreach ($group->images as $index => $image)
                                <div class="glide__bullet" data-glide-dir="={{ $index }}"><i class="fas fa-circle"></i></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <br>
            <hr>
            <br>
            <h3 class="subtitle is-4 has-text-centered has-text-weight-normal">To all of our beautiful past brides: </h3>
            <p class="has-text-centered">
                We would love for you to send us your beautiful wedding pictures for us to include them on our site. 
                <br>
                Please send us your pictures to {{ config('emails.groups') }} :)
            </p>
        </div>
    </div>
</article>
@endsection
