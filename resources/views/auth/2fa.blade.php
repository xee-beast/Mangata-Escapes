@extends('auth.layout')

@section('title', 'Login - ' . config('app.name', 'Barefootbridal'))

@section('card-title', 'Two Factor Authentication')
@section('card-content')
    <p class="mb-4">{{ __('An OTP has been sent to your email address. Please enter it to proceed.') }}</p>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf

        <div class="field mb-4">
            <label for="otp" class="label">{{ __('OTP') }}</label>
            <div class="control">
                <input 
                    id="otp" 
                    type="text" 
                    class="input @error('otp') is-danger @enderror" 
                    name="otp" 
                    required 
                    autofocus 
                    placeholder="{{ __('Enter your OTP') }}">
            </div>
            @error('otp')
                <p class="help is-danger" role="alert">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="field">
            <div class="control">
                <button type="submit" class="button is-fullwidth is-primary mt-2">
                    {{ __('Verify') }}
                </button>
            </div>
        </div>

        <div class="field mt-4">
            <div class="control">
                If you did not recieve yout opt, <a href="{{ route('2fa.resend') }}" class="is-link mt-2">click here</a> to resend
            </div>
        </div>

        <div class="field mt-4">
            <div class="control">
                <a href="{{ route('2fa.cancel') }}" class="button is-fullwidth is-light mt-2">
                    {{ __('Back to Login') }}
                </a>
            </div>
        </div>
    </form>
@endsection
