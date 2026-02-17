@extends('auth.layout')

@section('title', 'Password Reset - ' . config('app.name', 'Barefootbridal'))

@section('card-title', 'Reset Password')
@section('card-content')

@if (session('status'))
<p class="has-text-info" role="alert">
    {{ session('status') }}
</p>
<br>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="field">
        <label for="email" class="label">Email Address</label>
        <div class="control">
            <input type="text" name="email" id="email" class="input @error('email') is-danger @enderror" value="{{ old('email') }}"
            required autofocus>
        </div>
        @error('email')
        <p class="help is-danger" role="alert">
            {{ $message }}
        </p>
        @enderror
    </div>

    <div class="field">
        <div class="control">
            <button type="submit" class="button is-fullwidth is-primary">Send Password Reset Link</button>
        </div>
    </div>
</form>

@endsection
