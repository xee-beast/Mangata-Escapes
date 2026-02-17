@extends('layout')

@section('styles')
@if (app()->environment('local'))
<link rel="stylesheet" href="{{ mix('css/auth.css') }}">
@else
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endif

@endsection

@section('content')
<div>
    <div class="auth-background">
        <div class="is-background-image" style="background-image: url('{{ asset('img/login-bg.jpg') }}')">
            <div class="auth-background-glass"></div>
        </div>
    </div>
    <div class="container">
        <div class="auth-container columns is-mobile is-centered is-vcentered">
            <div class="column is-10-mobile is-8-tablet is-6-desktop">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-header-title is-size-4 is-centered">@yield('card-title')</h2>
                    </div>
                    <div class="card-content">
                        @yield('card-content')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
