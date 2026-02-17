@extends('auth.layout')

@section('title', 'Password Reset - ' . config('app.name', 'Barefootbridal'))

@section('card-title', 'Reset Password')
@section('card-content')

<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="field">
        <label for="email" class="label">Email Address</label>
        <div class="control">
            <input type="email" name="email" id="email" class="input @error('email') is-danger @enderror" value="{{ $email ?? old('email') }}"
            required readonly>
        </div>
        @error('email')
        <p class="help is-danger" role="alert">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="field">
        <label for="password" class="label">Password</label>
        <div class="control">
            <input type="password" name="password" id="password" class="input @error('password') is-danger @enderror" required autofocus>
        </div>
        @error('password')
        <p class="help is-danger" role="alert">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="field">
        <label for="password_confirmation" class="label">Confirm Password</label>
        <div class="control">
            <input type="password" name="password_confirmation" id="password_confirmation" class="input" required>
        </div>
    </div>

    <div class="field">
        <div class="control">
            <button type="submit" class="button is-fullwidth is-primary">Reset Password</button>
        </div>
    </div>
</form>

@endsection
