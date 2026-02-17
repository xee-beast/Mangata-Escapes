@extends('auth.layout')

@section('title', 'Login - ' . config('app.name', 'Barefootbridal'))

@section('card-title', 'Login')
@section('card-content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="field">
        <label for="username" class="label">Username</label>
        <div class="control">
            <input type="text" name="username" id="username" class="input @error('username') is-danger @enderror" value="{{ old('username') }}"
            required autofocus>
        </div>
        @error('username')
        <p class="help is-danger" role="alert">
            {{ $message }}
        </p>
        @enderror
    </div>
    <div class="field">
        <label for="password" class="label">Password</label>
        <div class="control">
            <input type="password" name="password" id="password" class="input @error('password') is-danger @enderror"
            value="{{ old('password') }}" required>
        </div>
        @error('password')
        <p class="help is-danger" role="alert">
            {{ $message }}
        </p>
        @enderror
    </div>
    <div class="field">
        <div class="control">
            <label class="checkbox">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}  style="accent-color: #995C64;">
                Remember me
            </label>
            @if (Route::has('password.request'))
            <label class="is-pulled-right">
                <a href="{{ route('password.request') }}" style="color: #995C64;">Forgot Password?</a>
            </label>
            @endif
        </div>
    </div>
    <div class="field">
        <div class="control">
            <button type="submit" class="button is-fullwidth is-primary">Submit</button>
        </div>
    </div>
</form>
@endsection
