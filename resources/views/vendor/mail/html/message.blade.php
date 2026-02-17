@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header')
{{-- Greeting --}}
@if (!empty($greeting))
 {{ $greeting }}
@else
 @lang('Hello!')
@endif
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<img class="footer-logo" src="{{ asset('img/colored-logo.png') }}" alt="{{ config('app.name') }}">
@endcomponent
@endslot
@endcomponent
