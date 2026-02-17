@extends('web.layouts.layout')

@section('title')
{{ $group->name }} | {{ config('app.name') }}
@endsection

@section('meta')
<meta name="description" content=
    "Welcome, Family and Friends! We are so excited to spend an extended weekend with you in {{ $group->destination->name }}, {{ $group->destination->country->name }}.
    Your presence means the world to us as we have the time of our lives and pledge our love to each other in paradise."
>

<meta property="og:title" content="{{ $group->name }}" />
<meta property="og:url" content="{{ route('couples', ['group' => $group->slug]) }}" />
<meta property="og:image" content="{{ is_null($group->image) ? asset('img/couple-1.jpg') : Storage::url($group->image->path) }}" />
<meta property="og:description" content=
    "Welcome, Family and Friends! We are so excited to spend an extended weekend with you in {{ $group->destination->name }}, {{ $group->destination->country->name }}.
    Your presence means the world to us as we have the time of our lives and pledge our love to each other in paradise."
/>

<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('styles')

@endsection

@section('scripts')
@if (app()->environment('local'))
<script src="{{ mix('js/couples/app.js') }}" defer></script>
@else
<script src="{{ asset('js/couples/app.js') }}" defer></script>
@endif
@endsection

@section('menu')
<a href="#welcome" class="navbar-item fade-in" data-scroll data-scroll-offset="100">WELCOME</a>
<a href="#accommodations" class="navbar-item fade-in" data-scroll data-scroll-offset="100">ACCOMMODATIONS</a>
<a href="#booking" class="navbar-item fade-in" data-scroll data-scroll-offset="100">BOOKING</a>
<a href="https://barefootbridal.com/contact" class="navbar-item"  target="_blank">CONTACT</a>
@endsection

@section('content')
@include('web.components.banner')
<article id="forms">
    @include('web.couples.welcome')
    @include('web.couples.accomodations')
    @include('web.couples.booking')
    @include('web.couples.airport_transfers')
    @include('web.couples.faq')
</article>
@endsection
