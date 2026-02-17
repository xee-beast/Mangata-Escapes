@extends('auth.layout')

@section('title', 'Verify Email - ' . config('app.name', 'Barefootbridal'))

@section('card-title', 'Email Verification')
@section('card-content')

@if (session('resent'))
<p class="has-text-info has-text-justified" role="alert">
    A fresh verification link has been sent to your email address.
</p>
<br>
@endif

<p class="has-text-justified">
    Before proceeding, please check your email for a verification link.
    If you did not receive the email, you could try resending it.
</p>

<br>

<form method="POST" action="{{ route('verification.resend') }}">
    @csrf
    <button type="submit" class="button is-fullwidth is-primary">Resend Verification Email</button>
</form>

@endsection
