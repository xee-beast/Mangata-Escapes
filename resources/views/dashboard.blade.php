@extends('layout')

@section('meta')
<meta name="robots" content="noindex">
@endsection

@section('title')
Dashboard - {{ config('app.name', 'Mangata Escapes') }}
@endsection

@section('scripts')
<script>
	window.DASHBOARD_BASE_PATH = "{{ parse_url(config('app.dashboard_url'), PHP_URL_PATH) ?: '/dashboard' }}";
	window.MEDIA_STORAGE = "{{ config('filesystems.default') === 'local' ? 'local' : 's3' }}";
</script>
@if (app()->environment('local'))
<script defer src="{{ mix('js/dashboard/main.js') }}"></script>
@else
<script defer src="{{ asset('js/dashboard/main.js') }}"></script>
@endif
@endsection

@section('styles')
@if (app()->environment('local'))
<link rel="stylesheet" href="{{ mix('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ mix('js/dashboard/main.css') }}">
@else
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('js/dashboard/main.css') }}">
@endif
@endsection
