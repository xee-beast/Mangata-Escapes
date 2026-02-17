@extends('web.layouts.layout')

@section('title', 'Password Required - ' . $group->name)

@section('styles')
    <style>
        .couples-password-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f8f8;
            padding: 2rem 1rem;
        }

        .couples-password-card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .couples-password-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
@endsection

@section('menu')
@endsection

@section('content')
    <div class="couples-password-wrapper is-font-family-montserrat">
        <div class="couples-password-card">
            <h2>{{ __('Password Required') }}</h2>

            <p class="mb-15 has-text-centered">
                {{ __('This page is password protected by the couple. Please enter the password to continue.') }}
            </p>

            <form method="POST" action="{{ route('couples.password.verify', ['group' => $group->slug]) }}">
                @csrf

                <div class="field mb-4">
                    <div class="control">
                        <input id="password" type="password" class="input @error('password') is-danger @enderror"
                            name="password" required autofocus placeholder="{{ __('Enter site password') }}">
                    </div>
                    @error('password')
                        <p class="help is-danger" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field">
                    <div class="control">
                        <button type="submit" class="button is-fullwidth is-bg-dusty-rose-color is-bg-hover-mauve-color is-white-color mt-2">
                            {{ __('Submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
